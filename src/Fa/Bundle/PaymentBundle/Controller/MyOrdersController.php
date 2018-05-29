<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;

/**
 * This controller is used for listing user's buy now orders.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class MyOrdersController extends CoreController
{
    /**
     * Show user's buy now orders.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function myOrdersAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();

        $numberOfRecordsPerPage = 10;
        $recordPositionArray = array();

        if ($request->get('orderId')) {
            $recordPositionArray = $this->getRepository('FaPaymentBundle:Payment')->getPageNumberByCartCodeForPurchaseOrder($loggedinUser->getId(), $request->get('orderId'), $this->container, 'orders');
            $recordPage = ceil($recordPositionArray['position'] / $numberOfRecordsPerPage);
            if ($recordPage > 1 && !$request->get('page')) {
                return $this->redirect($this->generateUrl('my_orders', array('page' => $recordPage, 'orderId' => $request->get('orderId'))));
            }
        }
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPaymentBundle:PaymentTransaction'), $this->getRepositoryTable('FaPaymentBundle:PaymentTransaction'), 'fa_my_item_search');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $data['query_joins']    = array(
                                    'payment_transaction' => array('payment' => array('type' => 'inner'), 'ad' => array('type' => 'inner'), 'user' => array('type' => 'inner')),
                                    'payment' => array('delivery_method_option' => array('type' => 'inner')),
                                    'user' => array('entity_user_status' => array('type' => 'inner'))
                                  );
        $data['select_fields']  = array(
            'ad' => array('id as ad_id', 'title', 'payment_method_id'),
            'payment' => array('id as payment_id', 'seller_user_id as seller_id', 'cart_code', 'created_at', 'amount', 'currency', 'value', 'buy_now_status_id'),
            'delivery_method_option' => array('id as delivery_method_id', 'name as deliver_method_name'),
            'user' => array('email as user_email', 'phone as user_phone', 'id as user_id', 'first_name', 'last_name', 'business_name'),
            'entity_user_status' => array('id as user_status_id')
        );
        $data['static_filters'] = PaymentRepository::ALIAS.'.seller_user_id = '.$loggedinUser->getId().' AND '.PaymentRepository::ALIAS.'.payment_method = :paymentMethod';
        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPaymentBundle:PaymentTransaction'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $queryBuilder->setParameter('paymentMethod', PaymentRepository::PAYMENT_METHOD_PAYPAL_ADAPTIVE)
            ->orderBy(PaymentRepository::ALIAS.'.created_at', 'DESC');
        $query        = $queryBuilder->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, $numberOfRecordsPerPage);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        $parameters = array(
            'pagination'  => $pagination,
            'recordPositionArray' => $recordPositionArray,
        );

        return $this->render('FaPaymentBundle:MyOrders:myOrders.html.twig', $parameters);
    }

    /**
     * Update order status.
     *
     * @param integer $orderId Payment id.
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxUpdateOrderStatusAction($orderId, Request $request)
    {
        $error      = '';
        $successMsg = '';
        $sendEmail  = true;
        $adObj      = null;
        if ($this->isAuth()) {
            $sellerUserId  = $this->getLoggedInUser()->getId();
            $paymentObj    = $this->getRepository('FaPaymentBundle:Payment')->findOneBy(array('id' => $orderId, 'seller_user_id' => $sellerUserId));
            $orderStatusId = $request->get('orderStatusId');

            if (!$paymentObj) {
                $error = $this->get('translator')->trans('Invalid order id.', array(), 'frontend-my-orders');
            } else {
                $deliveryMethopOptions = $this->getRepository('FaPaymentBundle:Payment')->getDeliveryStatusOptionsArray($this->container);
                if (in_array($orderStatusId, array_keys($deliveryMethopOptions))) {
                    $this->getEntityManager()->beginTransaction();
                    try {
                        if ($paymentObj->getBuyNowStatusId() == $orderStatusId) {
                            $sendEmail = false;
                        }

                        $paymentObj->setBuyNowStatusId($orderStatusId);
                        $paymentObj->setChangeStatusAt(time());
                        $paymentObj->setIsReviewReminderSent(0);
                        $this->getEntityManager()->persist($paymentObj);
                        $this->getEntityManager()->flush($paymentObj);
                        $this->getEntityManager()->getConnection()->commit();

                        try {
                            // send email to buyer
                            if ($sendEmail && $paymentObj->getPaymentMethod() == PaymentRepository::PAYMENT_METHOD_PAYPAL_ADAPTIVE && $paymentObj->getUser()) {
                                $values = unserialize($paymentObj->getValue());
                                if (is_array($values) && isset($values['paypal']['ad_id'])) {
                                    $adObj = $this->getRepository('FaAdBundle:Ad')->find($values['paypal']['ad_id']);
                                    if ($adObj) {
                                        $this->getRepository('FaAdBundle:Ad')->sendBuyNowBuyerDeliveryStatusEmail($adObj, $paymentObj->getUser(), $paymentObj->getAmount(), $paymentObj->getCartCode(), $orderStatusId, $this->container);
                                    }
                                }
                            }

                            // send email to seller If he hasn't reviewed to buyer yet.
                            if ($orderStatusId == PaymentRepository::BN_CLOSED_ID) {
                                $loggedinUser = $this->getLoggedInUser();
                                if (!$adObj) {
                                    $values = unserialize($paymentObj->getValue());
                                    if (is_array($values) && isset($values['paypal']['ad_id'])) {
                                        $adObj = $this->getRepository('FaAdBundle:Ad')->find($values['paypal']['ad_id']);
                                    }
                                }
                                if ($adObj && $loggedinUser && !$this->getRepository('FaUserBundle:UserReview')->isAdReviewedByUser($adObj->getId(), $loggedinUser->getId(), $paymentObj->getUser()->getId())) {
                                    $this->getRepository('FaAdBundle:Ad')->sendBuyNowSellerClosedStatusEmail($adObj, $paymentObj->getUser(), $paymentObj->getCartCode(), $this->container);
                                }
                            }
                        } catch (\Exception $e) {
                            CommonManager::sendErrorMail($container, 'Error in email: Order updates from seller - delivery status', $e->getMessage(), $e->getTraceAsString());
                        }

                        $successMsg = $this->get('translator')->trans('Order status updated successfully.', array(), 'frontend-my-orders');
                    } catch (\Exception $e) {
                        $this->getEntityManager()->getConnection()->rollback();
                        $error = $this->get('translator')->trans('Problem in updating order status.', array(), 'frontend-my-orders');
                    }
                } else {
                    $error = $this->get('translator')->trans('Invalid order option.', array(), 'frontend-my-orders');
                }
            }
        } else {
            $error = $this->get('translator')->trans('You do not have permission to access this resource.', array(), 'frontend-my-orders');
        }

        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
    }
}
