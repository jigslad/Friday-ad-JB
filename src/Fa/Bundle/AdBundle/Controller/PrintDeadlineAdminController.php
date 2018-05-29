<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\AdBundle\Entity\PrintDeadline;
// use Fa\Bundle\AdBundle\Form\PrintDeadlineType;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Form\PrintDeadlineAdminSearchType;

/**
 * This controller is used for print deadline crud management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PrintDeadlineAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'print_deadline';
    }

    /**
     * Lists all PrintDeadline entities.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:PrintDeadline'), $this->getRepositoryTable('FaAdBundle:PrintDeadline'), 'fa_ad_print_deadline_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array('print_deadline' => array('id', 'day_of_week', 'time_of_day'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdBundle:PrintDeadline'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $query        = $queryBuilder->distinct()->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 2);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(PrintDeadlineAdminSearchType::class, null, array('action' => $this->generateUrl('print_deadline_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
                          'heading'    => $this->get('translator')->trans('Print Deadline'),
                          'form'       => $form->createView(),
                          'pagination' => $pagination,
                          'sorter'     => $data['sorter'],
                      );

        return $this->render('FaAdBundle:PrintDeadlineAdmin:index.html.twig', $parameters);
    }
}
