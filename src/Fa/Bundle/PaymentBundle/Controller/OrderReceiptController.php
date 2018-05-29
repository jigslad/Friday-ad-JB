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
 * @author Amit Limbadia <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class OrderReceiptController extends CoreController
{
    /**
     * Show user's order receipt.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function viewReceiptAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $orderDetail  = array();

        $orderId = $request->get("orderId", null);
        $action  = $request->get("action", null);

        if ($orderId) {
            $data['query_joins']    = array(
                'payment_transaction' => array('payment' => array('type' => 'inner'), 'ad' => array('type' => 'inner'), 'user' => array('type' => 'inner')),
                'payment' => array('delivery_method_option' => array('type' => 'inner')),
                'user' => array('entity_user_status' => array('type' => 'inner'))
            );
            $data['select_fields']  = array(
                'ad' => array('id as ad_id', 'title', 'payment_method_id'),
                'payment' => array('id as payment_id','seller_user_id as seller_id', 'cart_code', 'created_at', 'amount', 'currency', 'value', 'buy_now_status_id', 'seller_user_id'),
                'delivery_method_option' => array('id as delivery_method_id', 'name as deliver_method_name'),
                'user' => array('email as user_email', 'phone as user_phone', 'id as user_id', 'first_name', 'last_name', 'business_name'),
                'entity_user_status' => array('id as user_status_id')
            );
            $data['static_filters'] = PaymentRepository::ALIAS.'.payment_method = :paymentMethod AND '.PaymentRepository::ALIAS.'.id = :orderId';
            $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPaymentBundle:PaymentTransaction'), $data);
            $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
            $queryBuilder->setParameter('paymentMethod', PaymentRepository::PAYMENT_METHOD_PAYPAL_ADAPTIVE);
            $queryBuilder->setParameter('orderId', $orderId);
            $orderDetails = $queryBuilder->getQuery()->getResult();

            if ($orderDetails && is_array($orderDetails) && count($orderDetails) > 0) {
                $orderDetail   = $orderDetails[0];
                $userDataArray = $this->getRepository('FaUserBundle:User')->getUserDataArrayByUserId($orderDetail['seller_user_id']);
                if (is_array($userDataArray) && count($userDataArray) > 0) {
                    $orderDetail['sender_profile_name'] = $this->getRepository('FaUserBundle:User')->getFullNameFromArray($userDataArray[$orderDetail['seller_user_id']]);
                }
            }
        }

        $parameters = array('orderDetail' => $orderDetail, 'action' => $action);

        return $this->render('FaPaymentBundle:OrderReceipt:orderReceipt.html.twig', $parameters);
    }

}
