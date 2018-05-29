<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;
use Fa\Bundle\DotMailerBundle\Entity\DotmailerFilter;
use Fa\Bundle\DotMailerBundle\Form\DotmailerSearchAdminType;
use Fa\Bundle\DotMailerBundle\Form\DotmailerFilterAdminType;

/**
 * This controller is used for dotmailer management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Search dotmailer newsletters.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(DotmailerSearchAdminType::class, null, array('action' => $this->generateUrl('dotmailer_list_admin'), 'method' => 'GET'));

        $parameters = array(
                          'heading'    => $this->get('translator')->trans('Create marketing filter'),
                          'form'       => $form->createView()
                      );

        return $this->render('FaDotMailerBundle:DotmailerAdmin:index.html.twig', $parameters);
    }

    /**
     * List dotmailer newsletters.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
    {
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaDotMailerBundle:Dotmailer'), $this->getRepositoryTable('FaDotMailerBundle:Dotmailer'), 'fa_dotmailer_dotmailer_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
                                    'dotmailer' => array('id as dotmailer_id', 'email', 'first_name', 'last_name', 'business_name', 'role_id'),
                                 );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaDotMailerBundle:Dotmailer'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        if (isset($data['search'])) {
            if (isset($data['search']['dotmailer__fad_user']) && isset($data['search']['dotmailer__ti_user'])) {
                $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.fad_user = 1 OR '.DotmailerRepository::ALIAS.'.ti_user = 1');
            } elseif (isset($data['search']['dotmailer__fad_user'])) {
                $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.fad_user = 1');
            } elseif (isset($data['search']['dotmailer__ti_user'])) {
                $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.ti_user = 1');
            }
        }

        $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.opt_in = 1');
        $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.is_suppressed = 0');
        $queryBuilder->distinct();
        $query = $queryBuilder->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // Search filters
        $searchParams = array_filter($data['search']);

        $dotmailerFilter = new DotmailerFilter();
        $formManager = $this->get('fa.formmanager');
        $filterForm  = $formManager->createForm(DotmailerFilterAdminType::class, $dotmailerFilter, array('action' => $this->generateUrl('dotmailer_filter_create_admin'), 'method' => 'POST'));
        $filterForm->get('filters')->setData(serialize($searchParams));

        $parameters = array(
            'heading'       => $this->get('translator')->trans('Create marketing filter'),
            'form'          => $filterForm->createView(),
            'pagination'    => $pagination,
            'sorter'        => $data['sorter'],
            'searchParams' => $searchParams
        );

        return $this->render('FaDotMailerBundle:DotmailerAdmin:list.html.twig', $parameters);
    }
}
