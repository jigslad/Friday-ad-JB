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
use Fa\Bundle\ContentBundle\Entity\HomePopularImage;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\ContentBundle\Form\HomePopularImageAdminType;
use Fa\Bundle\ContentBundle\Form\HomePopularImageAdminSearchType;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for user management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class HomePopularImageAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'home_popular_image';
    }

    /**
     * Get word to be displayed on template.
     *
     * @return string
     */
    protected function getDisplayWord()
    {
        return 'Homepage What\'s Popular';
    }

    /**
     * Lists all home popular image entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaContentBundle:HomePopularImage'), $this->getRepositoryTable('FaContentBundle:HomePopularImage'), 'fa_content_home_popular_image_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
                                      'home_popular_image' => array('id', 'path', 'status', 'file_name','created_at'),
                                 );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaContentBundle:HomePopularImage'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(HomePopularImageAdminSearchType::class, null, array('action' => $this->generateUrl('home_popular_image_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'statusArray' => EntityRepository::getStatusArray($this->container),
            'heading'     => 'Homepage What\'s Popular',
            'form'        => $form->createView(),
            'pagination'  => $pagination,
            'sorter'      => $data['sorter'],
        );

        return $this->render('FaContentBundle:HomePopularImageAdmin:index.html.twig', $parameters);
    }
}
