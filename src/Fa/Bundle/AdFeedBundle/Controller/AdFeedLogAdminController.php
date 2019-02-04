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
use Fa\Bundle\AdFeedBundle\Form\AdFeedLogSearchAdminType;

/**
 * Delivery method option controller.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdFeedLogAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get name of table.
     */
    protected function getTableName()
    {
        return 'ad_feed';
    }

    /**
     * Get display word.
     */
    protected function getDisplayWord()
    {
        return 'Feed log';
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
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdFeedBundle:AdFeed'), $this->getRepositoryTable('FaAdFeedBundle:AdFeed'), 'fa_ad_feed_ad_feed_log_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['query_joins']['ad_feed']['ad']  = array('type' => 'left');
        $data['query_joins']['ad_feed']['user'] = array('type' => 'left');
        $data['select_fields']  = array('ad_feed' => array('id', 'trans_id', 'unique_id', 'status', 'created_at', 'last_modified', 'remark'), 'user' => array('id as user_id', 'email'), 'ad' => array('id as ad_id'));

        //print_r($data);exit;
        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdFeedBundle:AdFeed'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();
       

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, false, array('distinct' => false));
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdFeedLogSearchAdminType::class, null, array('action' => $this->generateUrl('ad_feed_log_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'          => $this->get('translator')->trans('Feed Log'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaAdFeedBundle:FeedLogAdmin:index.html.twig', $parameters);
    }

    /**
     * view imported add details
     *
     * @param integer $id
     *
     * return void
     */
    public function viewAction($id)
    {
        CommonManager::removeAdminBackUrl($this->container);
        $ad_feed = $this->getRepository('FaAdFeedBundle:AdFeed')->find($id);

        if ($ad_feed) {
            $parameters = array(
                    'heading'          => $this->get('translator')->trans('Imported Ad Detail'),
                    'ad_feed'          => $ad_feed,
            );

            return $this->render('FaAdFeedBundle:FeedLogAdmin:view.html.twig', $parameters);
        }
    }
}
