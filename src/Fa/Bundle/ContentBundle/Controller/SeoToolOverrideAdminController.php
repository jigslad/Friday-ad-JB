<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Repository\SeoToolOverrideRepository;
use Fa\Bundle\ContentBundle\Form\SeoToolOverrideSearchAdminType;

/**
 * This controller is used for static page management.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolOverrideAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'seo_tool_override';
    }

    /**
     * Lists all SeoTool entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaContentBundle:SeoToolOverride'), $this->getRepositoryTable('FaContentBundle:SeoToolOverride'), 'fa_content_seo_tool_override_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array(
            'seo_tool_override' => array('id', 'h1_tag', 'page_title', 'page_url', 'meta_description', 'status'),
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaContentBundle:SeoToolOverride'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $queryBuilder->distinct(SeoToolOverrideRepository::ALIAS.'.id');
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(SeoToolOverrideSearchAdminType::class, null, array('action' => $this->generateUrl('seo_tool_override_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => $this->get('translator')->trans('Seo Tools Override'),
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaContentBundle:SeoToolOverrideAdmin:index.html.twig', $parameters);
    }
}
