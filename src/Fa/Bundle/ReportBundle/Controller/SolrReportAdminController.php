<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\PromotionBundle\Form\ShopPackageType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Repository\InActiveUserSolrAdsRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\ReportBundle\Form\SolrReportSearchAdminType;
/**
 * This is the controller for getting Solr report.
 *
 * @author Gaurav Aggarwal <gaurav.aggarwal@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version v1.0
 */
class SolrReportAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function indexAction(Request $request)
    {
    	
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:InActiveUserSolrAds'), $this->getRepositoryTable('FaAdBundle:InActiveUserSolrAds'), 'fa_solr_report');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(SolrReportSearchAdminType::class, null, array(
            'action' => $this->generateUrl('fa_solr_pending_ad_report'),
            'method' => 'GET'
        ));

        if(($request->query->has('status') && $request->query->get('status') == 'success') or ($request->query->has('fa_solr_report') && $request->query->get('fa_solr_report')['status'] == 'success')) {
            $reqStatus = 'success';
        } else {
            $reqStatus = 'pending';
        }

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $query = $this->getRepository('FaAdBundle:InActiveUserSolrAds')->getInActiveUserAdsInSolr($request,$data['search'],$data['sorter']);
        
        // initialize pagination manager service and prepare listing with pagination based of data
        $page = ($request->query->has('page')) ? $request->query->get('page'): 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

    	
    	$parameters = array(
            'heading'       => 'User & Ad Solr Report',
            'form'          => $form->createView(),
    		'pagination'    => $pagination,
            'reqStatus'     => $reqStatus,
            'sorter'        => $data['sorter']
    	);
    	
    	return $this->render('FaReportBundle:solrReportAdmin:inActiveUserAdsInSolr.html.twig', $parameters);
    }
}
