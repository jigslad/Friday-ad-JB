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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for listing user's buy now purchases.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class MyPurchasesController extends CoreController
{
    /**
     * Show user's buy now purchases.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function myPurchasesAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $numberOfRecordsPerPage = 10;
        $recordPositionArray = array();

        if ($request->get('orderId')) {
            $recordPositionArray = $this->getRepository('FaPaymentBundle:Payment')->getPageNumberByCartCodeForPurchaseOrder($loggedinUser->getId(), $request->get('orderId'), $this->container, 'purchases');
            $recordPage = ceil($recordPositionArray['position'] / $numberOfRecordsPerPage);
            if ($recordPage > 1 && !$request->get('page')) {
                return $this->redirect($this->generateUrl('my_purchases', array('page' => $recordPage, 'orderId' => $request->get('orderId'))));
            }
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPaymentBundle:PaymentTransaction'), $this->getRepositoryTable('FaPaymentBundle:PaymentTransaction'), 'fa_my_item_search');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $data['query_joins']    = array(
                                    'payment_transaction' => array('payment' => array('type' => 'inner'), 'ad' => array('type' => 'inner')),
                                    'payment' => array('delivery_method_option' => array('type' => 'inner'))
                                  );
        $data['select_fields']  = array(
            'ad' => array('id as ad_id', 'title', 'payment_method_id'),
            'payment' => array('id as payment_id', 'seller_user_id as seller_id', 'cart_code', 'created_at', 'amount', 'currency', 'value', 'buy_now_status_id'),
            'delivery_method_option' => array('id as delivery_method_id', 'name as deliver_method_name'),
            // seller user information
            'user' => array('email as user_email', 'phone as user_phone', 'id as user_id', 'first_name', 'last_name', 'business_name')
        );
        $data['static_filters'] = PaymentRepository::ALIAS.'.user = '.$loggedinUser->getId().' AND '.PaymentRepository::ALIAS.'.payment_method = :paymentMethod';
        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPaymentBundle:PaymentTransaction'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $queryBuilder->setParameter('paymentMethod', PaymentRepository::PAYMENT_METHOD_PAYPAL_ADAPTIVE)
            ->addSelect(UserRepository::ALIAS_R.'.id as buyer_id')
            // seller user join
            ->leftJoin('FaUserBundle:User', UserRepository::ALIAS, 'WITH', UserRepository::ALIAS.'.id = '.PaymentRepository::ALIAS.'.seller_user_id')
            // buyer user join
            ->leftJoin('FaUserBundle:User', UserRepository::ALIAS_R, 'WITH', UserRepository::ALIAS_R.'.id = '.PaymentRepository::ALIAS.'.user')
            ->orderBy(PaymentRepository::ALIAS.'.created_at', 'DESC')
            ->leftJoin(UserRepository::ALIAS.'.status', 'entity_user_status')
            ->addSelect('entity_user_status.id as user_status_id');
        $query        = $queryBuilder->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, $numberOfRecordsPerPage, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        $parameters = array(
            'pagination'  => $pagination,
            'recordPositionArray' => $recordPositionArray,
        );

        return $this->render('FaPaymentBundle:MyPurchases:myPurchases.html.twig', $parameters);
    }
}
