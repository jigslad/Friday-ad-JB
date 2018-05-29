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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Repository\ApiTokenRepository;

/**
 * This controller is used for trade it.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class TradeItAdApiController extends CoreController
{
    /**
     * Similar ad response.
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function getAdsAction(Request $request)
    {
        $validResponse = $this->validateApiToken($request, ApiTokenRepository::SIMILAR_AD_API_TYPE_ID);
        if ($validResponse) {
            return $validResponse;
        }
        $searchParam    = $request->query->all();
        $similarAdArray = array();
        $buildResponse  = $this->get('fa_ad.tradeit.api.response_build');

        list($similarAdResult, $resultCount) = $this->getAdsSolrResult($request, $searchParam);

        $similarAdArray = array('totalAds' => $resultCount, 'perPage' => 1000);

        foreach ($similarAdResult as $similarAd) {
            $similarAdArray['ads'][] = $buildResponse->init($similarAd);
        }

        return new JsonResponse($similarAdArray);
    }

    /**
     * Get similar ads.
     *
     * @param array $searchParams Search parameters.
     *
     * @return array
     */
    private function getAdsSolrResult($request, $searchParams)
    {
        $data                 = array();
        $page                 = $request->get('page', 1);
        $recordsPerPage       = 1000;
        $data['query_sorter'] = array();
        $locationId           = array(124, 133, 166, 267, 923, 969, 1053);
        $keywords             = (isset($searchParams['adTitle']) ? $searchParams['adTitle'] : null);
        $location             = (isset($searchParams['location']) ? $searchParams['location'] : null);
        $oldCategoryId        = (isset($searchParams['oldCategoryId']) ? $searchParams['oldCategoryId'] : null);

        // get location from name.
        $data['query_filters']['item']['status_id']  = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['is_feed_ad'] = 0;

        if (strlen($keywords)) {
            $data['query_sorter']['item']['score'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        // location filter.
        if ($locationId) {
            $data['query_filters']['item']['county_id'] = $locationId;
        }

        if (!strlen($keywords) || !$locationId) {
            $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        //set ad criteria to search
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);

        $solrResponse = $solrSearchManager->getSolrResponse();
        $resultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);

        return array($this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse), $resultCount);
    }
}
