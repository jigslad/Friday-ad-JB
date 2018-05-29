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
 * This controller is used for similar ad api.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SimilarAdApiController extends CoreController
{
    /**
     * Similar ad response.
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function getSimilarAdsAction(Request $request)
    {
        $validResponse = $this->validateApiToken($request, ApiTokenRepository::SIMILAR_AD_API_TYPE_ID);
        if ($validResponse) {
            return $validResponse;
        }
        $searchParam    = $request->query->all();
        $similarAdArray = array();
        $buildResponse  = $this->get('fa_ad.similarads.api.response_build');

        list($similarAdResult, $categoryId) = $this->getSimilarAdsSolrResult($searchParam);

        foreach ($similarAdResult as $similarAd) {
            $similarAdArray[] = $buildResponse->init($similarAd);
        }

        if (count($similarAdArray) < 10 && $categoryId) {
            $parentCategoryIds = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container));
            array_pop($parentCategoryIds);
            $searchParam['category_id'] = (isset($parentCategoryIds[count($parentCategoryIds) - 1]) ? $parentCategoryIds[count($parentCategoryIds) - 1] : null);
            list($similarAdResultParent, $categoryId) = $this->getSimilarAdsSolrResult($searchParam);
            foreach ($similarAdResultParent as $similarAdParent) {
                if (count($similarAdArray) < 10) {
                    $similarAdArray[] = $buildResponse->init($similarAdParent);
                } else {
                    break;
                }
            }
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
    private function getSimilarAdsSolrResult($searchParams = array())
    {
        $data                 = array();
        $page                 = 1;
        $recordsPerPage       = 10;
        $data['query_sorter'] = array();
        $categoryId           = null;
        $locationId           = null;
        $keywords             = (isset($searchParams['adTitle']) ? $searchParams['adTitle'] : null);
        $location             = (isset($searchParams['location']) ? $searchParams['location'] : null);
        $oldCategoryId        = (isset($searchParams['oldCategoryId']) ? $searchParams['oldCategoryId'] : null);

        // get category id from old cat id.
        if ($oldCategoryId && !isset($searchParams['category_id'])) {
            $oldCatObj  = $this->getRepository('FaEntityBundle:MappingCategory')->find($oldCategoryId);
            if ($oldCatObj) {
                $categoryId = $oldCatObj->getNewId();
            }
        } elseif (isset($searchParams['category_id']) && $searchParams['category_id']) {
            $categoryId = $searchParams['category_id'];
        }

        // get location from name.
        if ($location) {
            $locationObj  = $this->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $location, 'lvl' => 3));
            if ($locationObj) {
                $locationId = $locationObj->getId();
            }
        }
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        if (strlen($keywords)) {
            $data['query_sorter']['item']['score'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }
        // category filter.
        if ($categoryId) {
            $data['query_filters']['item']['category_id'] = $categoryId;
        }
        // location filter.
        if ($locationId) {
            $data['query_filters']['item']['location'] = $locationId.'|15';
            if ($locationObj && $locationObj->getLatitude() && $locationObj->getLongitude()) {
                $geoDistParams = array('sfield' => 'store', 'pt' => $locationObj->getLatitude().', '.$locationObj->getLongitude());
                $this->get('fa.solrsearch.manager')->setGeoDistQuery($geoDistParams);

                $data['query_sorter']['item']['geodist'] = array('sort_ord' => 'asc', 'field_ord' => 1);
            }
        }

        if (!strlen($keywords) || !$locationId) {
            $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        // Remove affiliate ads from similar ads.
        $data['query_filters']['item']['is_affiliate_ad'] = 0;

        $data['select_fields'] = array('item' => array('path', 'ord', 'hash', 'aws', 'price', 'title', 'description', 'category_id', 'root_category_id', 'id', 'image_name', 'main_town_id'));
        //set ad criteria to search
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);

        $solrResponse = $solrSearchManager->getSolrResponse();

        return array($this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse), $categoryId);
    }
}
