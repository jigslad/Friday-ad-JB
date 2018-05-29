<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedMapping;
use Fa\Bundle\AdFeedBundle\Form\AdFeedMappingAdminType;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdFeedBundle\Form\AdFeedMappingSearchAdminType;

/**
 * Delivery method option controller.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FeedMappingAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get name of table.
     */
    protected function getTableName()
    {
        return 'ad_feed_mapping';
    }

    /**
     * Get display word.
     */
    protected function getDisplayWord()
    {
        return 'Feed mapping text';
    }

    /**
     * Lists all Entities.
     *
     * @return Response A Response object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdFeedBundle:AdFeedMapping'), $this->getRepositoryTable('FaAdFeedBundle:AdFeedMapping'), 'fa_ad_feed_ad_feed_mapping_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('ad_feed_mapping' => array('id', 'text', 'target'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdFeedBundle:AdFeedMapping'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdFeedMappingSearchAdminType::class, null, array('action' => $this->generateUrl('ad_feed_mapping_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'          => $this->get('translator')->trans('Feed Mapping'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaAdFeedBundle:FeedMappingAdmin:index.html.twig', $parameters);
    }
}
