<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Entity\Upsell;
// use Fa\Bundle\PromotionBundle\Form\UpsellType;
// use Fa\Bundle\PromotionBundle\Form\UpsellSearchType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\PromotionBundle\Form\UpsellSearchAdminType;

/**
 * This controller is used for upsell management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version 1.0
 */
class UpsellAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return string
     */
    protected function getTableName()
    {
        return 'upsell';
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
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPromotionBundle:Upsell'), $this->getRepositoryTable('FaPromotionBundle:Upsell'), 'fa_promotion_upsell_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('upsell' => array('id', 'type', 'title', 'price', 'status'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPromotionBundle:Upsell'), $data);
        $this->get('fa.sqlsearch.manager')->getQueryBuilder()->andWhere(UpsellRepository::ALIAS.'.upsell_for = :upsell');
        $this->get('fa.sqlsearch.manager')->getQueryBuilder()->setParameter('upsell', 'ad');
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UpsellSearchAdminType::class, null, array('action' => $this->generateUrl('upsell_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'statusArray'      => EntityRepository::getStatusArray($this->container),
            'heading'          => $this->get('translator')->trans('Upsells'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaPromotionBundle:UpsellAdmin:index.html.twig', $parameters);
    }
}
