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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption;
// use Fa\Bundle\PaymentBundle\Form\DeliveryMethodOptionType;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Form\DeliveryMethodOptionSearchAdminType;

/**
 * Delivery method option controller.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DeliveryMethodOptionAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get name of table.
     */
    protected function getTableName()
    {
        return 'delivery_method_option';
    }

    /**
     * Get display word.
     */
    protected function getDisplayWord()
    {
        return 'Postage Option';
    }

    /**
     * Lists all Entities.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPaymentBundle:DeliveryMethodOption'), $this->getRepositoryTable('FaPaymentBundle:DeliveryMethodOption'), 'fa_payment_delivery_method_option_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('delivery_method_option' => array('id', 'name', 'cost', 'status'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPaymentBundle:DeliveryMethodOption'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(DeliveryMethodOptionSearchAdminType::class, null, array('action' => $this->generateUrl('delivery_method_option_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'statusArray'      => EntityRepository::getStatusArray($this->container),
            'heading'          => $this->get('translator')->trans('Postage Option'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaPaymentBundle:DeliveryMethodOptionAdmin:index.html.twig', $parameters);
    }
}
