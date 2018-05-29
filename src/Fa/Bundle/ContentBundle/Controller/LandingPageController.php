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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\ContentBundle\Entity\LandingPage;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\AdBundle\Solr\AdJobsSolrFieldMapping;
use Fa\Bundle\AdBundle\Form\LandingPageAdsNearYouSearchType;
use Fa\Bundle\AdBundle\Form\LandingPageCarSearchType;
use Fa\Bundle\AdBundle\Form\LandingPagePropertySearchType;
use Fa\Bundle\AdBundle\Form\LandingPageJobsSearchType;
use Fa\Bundle\AdBundle\Form\LandingPageAdultSearchType;

/**
 * This controller is used for Landing page.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LandingPageController extends CoreController
{
    /**
     * Landing page.
     *
     * @param Request $request  A Request object.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $landingPage  = null;
        $parameters   = array();
        $searchParams = array();
        $adsNearYou   = array();
        $categoryId   = $request->get('category_id', null);
        $formManager  = $this->get('fa.formmanager');

        if ($categoryId) {
            $landingPage = $this->getRepository('FaContentBundle:LandingPage')->findOneBy(array('category' => $categoryId));
        }

        try {
            if (!$landingPage) {
                throw $this->createNotFoundException();
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage', array(), $this->get('translator')->trans('Unable to find page.'));
        }

        // Set location in cookie
        $cookieValue = $this->getRepository('FaEntityBundle:Location')->setLocationInCookie($request, $this->container);
        if ($cookieValue) {
            $cookieLocationDetails = json_decode($cookieValue, true);
        } else {
            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
        }

        $parameters['landingPage']   = $landingPage;
        $parameters['categoryId']    = $categoryId;
        $parameters['category']      = $request->get('category_string', null);
        $parameters['location']      = ((isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) ? $cookieLocationDetails['location'] : $request->get('location', null));
        $parameters['location_text'] = ((isset($cookieLocationDetails['location_text']) && $cookieLocationDetails['location_text']) ? $cookieLocationDetails['location_text'] : null);
        $parameters['location_slug'] = ((isset($cookieLocationDetails['slug']) && $cookieLocationDetails['slug']) ? $cookieLocationDetails['slug'] : null);

        if ($parameters['location'] == 'uk') {
            unset($parameters['location'], $parameters['location_text'], $parameters['location_slug']);
        }

        if ($parameters['categoryId']) {
            $searchParams['item__category_id'] = $categoryId;
        }

        if (isset($parameters['location']) && $parameters['location']) {
            $searchParams['item__location'] = $parameters['location'];

            $distance = 15;
            if (isset($searchParams['item__category_id']) && $searchParams['item__category_id'] == CategoryRepository::MOTORS_ID) {
                $distance = 30;
            }

            $searchParams['item__distance'] = $distance;
            if (!in_array($categoryId, array(CategoryRepository::JOBS_ID, CategoryRepository::ADULT_ID))) {
                $parameters['adsNearYou']       = $this->getAdsNearYou($searchParams, $cookieLocationDetails);

                $adsNearYouForm               = $formManager->createForm(LandingPageAdsNearYouSearchType::class, null, array('method' => 'GET'));
                $parameters['adsNearYouForm'] = $adsNearYouForm->createView();
            }
        }

        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        if ($categoryId != CategoryRepository::MOTORS_ID) {
            $parameters['popularSearches'] = $this->getEntityManager()->getRepository('FaContentBundle:LandingPagePopularSearch')->getPopularSearchArrayByLandingPageId($landingPage->getId(), $this->container);
        }

        if ($categoryId) {
            if ($categoryId == CategoryRepository::MOTORS_ID) {
                $form                      = $formManager->createForm(LandingPageCarSearchType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_landing_page_search_result')));
                $parameters['form']        = $form->createView();
                $parameters['topCarMakes'] = $this->getTopCarMakes($searchParams);
            } elseif ($categoryId == CategoryRepository::PROPERTY_ID) {
                $form               = $formManager->createForm(LandingPagePropertySearchType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_landing_page_search_result')));
                $parameters['form'] = $form->createView();
            } elseif ($categoryId == CategoryRepository::JOBS_ID) {
                $form               = $formManager->createForm(LandingPageJobsSearchType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_landing_page_search_result')));
                $parameters['form'] = $form->createView();
                $parameters['latestAds'] = $this->getLatestAds($searchParams + array('ad_jobs__is_job_of_week' => 1), $cookieLocationDetails, null, null, false, true);
                $parameters['mostPopularAds'] = array();
                $totalLatestAdsCount = count($parameters['latestAds']);
                if ($totalLatestAdsCount < 12) {
                    $latestAdIds = array();
                    foreach ($parameters['latestAds'] as $latestAd) {
                        $latestAdIds[] = $latestAd[AdSolrFieldMapping::ID];
                    }

                    $latestAds = $this->getLatestAds($searchParams, $cookieLocationDetails, (12 - $totalLatestAdsCount), (count($latestAdIds) ? ' AND -'.AdSolrFieldMapping::ID.': ('.implode(' ', $latestAdIds).')' : null), true);
                    for ($i = 0; $i < (12 - $totalLatestAdsCount); $i++) {
                        if (isset($latestAds[$i])) {
                            $parameters['latestAds'][$i+$totalLatestAdsCount] = $latestAds[$i];
                        }
                    }
                }
                //browse by category
                $browseByCategory = $this->getJobsByCategory($searchParams);
                $parameters['browseByCategorySort'] = array();
                foreach ($browseByCategory as $browseByCategoryId => $browseByCategoryIdCount) {
                    $parameters['browseByCategorySort'][] = array(
                                                                'name' => $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $browseByCategoryId),
                                                                'id' => $browseByCategoryId,
                                                                'count' => $browseByCategoryIdCount,
                                                            );
                }
                if (count($parameters['browseByCategorySort'])) {
                    $parameters['browseByCategorySort'] = CommonManager::msort($parameters['browseByCategorySort'], 'name');
                }
                $jobFeaturedEmployers = $this->getRepository('FaUserBundle:UserUpsell')->getUserArrayWithFeaturedEmployerUpsell($this->container);
                $featuredEmployerIds = array();
                $parameters['featuredEmployers'] = array();
                if (count($jobFeaturedEmployers)) {
                    shuffle($jobFeaturedEmployers);
                    for ($i = 0; $i < 12; $i++) {
                        if (isset($jobFeaturedEmployers[$i])) {
                            $parameters['featuredEmployers'][] = $jobFeaturedEmployers[$i];
                            $featuredEmployerIds[] = $jobFeaturedEmployers[$i]['id'];
                        }
                    }
                }

                $totalFeaturedEmployerCount = count($parameters['featuredEmployers']);
                if ($totalFeaturedEmployerCount < 12) {
                    $featuredEmployers = $this->getFeaturedEmployerForJobs($searchParams, (count($featuredEmployerIds) ? ' AND -'.AdSolrFieldMapping::USER_ID.': ("'.implode('" "', $featuredEmployerIds).'")' : null), false, true);
                    for ($i = 0; $i < (12 - $totalFeaturedEmployerCount); $i++) {
                        if (isset($featuredEmployers[$i])) {
                            $parameters['featuredEmployers'][$i+$totalFeaturedEmployerCount] = $featuredEmployers[$i];
                        }
                    }
                }
            } elseif ($categoryId == CategoryRepository::FOR_SALE_ID) {
                $parameters['mostPopularAds'] = array();
                $parameters['latestAds']      = array();
                $parameters['popularShops']   = $this->getRepository('FaAdBundle:AdForSale')->getPopularShops($this->container, $searchParams);
            } elseif ($categoryId == CategoryRepository::ADULT_ID) {
                $parameters['mostPopularAds'] = array();
                $parameters['latestAds']      = array();
                $parameters['popularShops']   = $this->getRepository('FaAdBundle:AdAdult')->getFeaturedAdultBusinesses($this->container, $searchParams);
                $form               = $formManager->createForm(LandingPageAdultSearchType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_landing_page_search_result')));
                $parameters['form'] = $form->createView();
            }

            if (!in_array($categoryId, array(CategoryRepository::JOBS_ID, CategoryRepository::FOR_SALE_ID, CategoryRepository::ADULT_ID))) {
                $parameters['mostPopularAds'] = $this->getMostPopularAds($searchParams, $cookieLocationDetails);
            }

            if (!in_array($categoryId, array(CategoryRepository::JOBS_ID, CategoryRepository::FOR_SALE_ID))) {
                $parameters['latestAds'] = $this->getLatestAds($searchParams, $cookieLocationDetails);
            }
        }

        $seoLocationName = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', LocationRepository::COUNTY_ID);
        if ($seoLocationName == 'United Kingdom') {
            $seoLocationName = 'UK';
        }

        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['locality']) && $cookieLocationDetails['locality']) {
            $seoLocationName = $cookieLocationDetails['locality'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['county']) && $cookieLocationDetails['county']) {
            $seoLocationName = $cookieLocationDetails['county'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['town']) && $cookieLocationDetails['town']) {
            $seoLocationName = $cookieLocationDetails['town'];
        }

        $parameters['seoFields']       = CommonManager::getSeoFields($landingPage);
        $parameters['searchParams']    = $searchParams;
        $parameters['seoLocationName'] = $seoLocationName;

        return $this->render('FaContentBundle:LandingPage:index.html.twig', $parameters);
    }

    /**
     * Landing page map search.
     *
     * @param  array $searchParams Search parameters.
     * @param  array $cookieLocationDetails Location cookie details.
     * @return array
     */
    private function getAdsNearYou($searchParams = array(), $cookieLocationDetails = array())
    {
        $keywords       = null;
        $data           = array();
        $page           = 1;
        $data['search'] = $searchParams;

        $data['search']['item__distance']  = 1;
        $data['search']['item__status_id'] = EntityRepository::AD_STATUS_LIVE_ID;

        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'search', $data);
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        if (isset($data['search']['item__location']) && $data['search']['item__location'] != LocationRepository::COUNTY_ID) {
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.$data['search']['item__distance'];

            if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['latitude']) && isset($cookieLocationDetails['longitude'])) {
                $data['query_sorter']['item']['geodist'] = 'asc';
            }
        }

        $data['query_sorter']['item']['weekly_refresh_published_at'] = 'desc';
        $data['select_fields'] = array('item' => array('id', 'title', 'latitude', 'longitude'));

        $this->get('fa.solrsearch.manager')->init('ad', $keywords, $data, $page, $this->container->getParameter('fa.search.map.records.per.page'));
        $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();

        return $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
    }

    /**
     * Top car makes.
     *
     * @param  array $searchParams Search parameters.
     * @return array
     */
    private function getTopCarMakes($searchParams = array())
    {
        $data = array();

        if(isset($searchParams['item__location'])) {
            unset($searchParams['item__location']);
        }

        if(isset($searchParams['item__distance'])) {
            unset($searchParams['item__distance']);
        }

        $data['search'] = $searchParams;
        $data['search']['item__category_id'] = CategoryRepository::CARS_ID;
        $data['search']['item__status_id']   = EntityRepository::AD_STATUS_LIVE_ID;

        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'search', $data);
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $data['facet_fields'] = array(AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID => array('min_count' => 1, 'limit' => 60));

        $this->get('fa.solrsearch.manager')->init('ad', null, $data, 1, 1);
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields();

        $topMakes = array();
        if (isset($facetResult[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID])) {
            $topMakes = get_object_vars($facetResult[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID]);
        }

        return $topMakes;
    }

    /**
     * Get latest ads.
     *
     * @param array   $searchParams          Search parameters.
     * @param array   $cookieLocationDetails Location cookie details.
     * @param integer $adLimit               Ad limit.
     * @param string  $staticFilters         Static filters.
     *
     * @return object
     */
    private function getLatestAds($searchParams = array(), $cookieLocationDetails = array(), $adLimit = null, $staticFilters = null, $minimumOneImageFlag = true, $randomSort = false)
    {
        if (!$adLimit) {
            $adLimit = 12;
        }
        $data           = array();
        $data['search'] = $searchParams;
        $data['search']['item__status_id'] = EntityRepository::AD_STATUS_LIVE_ID;

        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'search', $data);
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        if (isset($data['search']['item__location']) && $data['search']['item__location'] != LocationRepository::COUNTY_ID) {
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.$data['search']['item__distance'];
        }

        $data['query_sorter']                         = array();
        if ($randomSort) {
            $data['query_sorter']['item']['random'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        } else {
            $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        // List ads only with images and no affliate
        if ($minimumOneImageFlag) {
            $data['query_filters']['item']['total_images_from_to'] = '1|';
        }
        $data['query_filters']['item']['is_affiliate_ad']      = 0;

        if ($staticFilters) {
            $data['static_filters'] = $staticFilters;
        }
        $this->get('fa.solrsearch.manager')->init('ad', null, $data, 1, $adLimit);
        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['latitude']) && isset($cookieLocationDetails['longitude'])) {
            $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocationDetails['latitude'].', '.$cookieLocationDetails['longitude']);
            $this->get('fa.solrsearch.manager')->setGeoDistQuery($geoDistParams);
        }

        return $this->get('fa.solrsearch.manager')->getSolrResponseDocs();
    }

    /**
     * Get most popular ads in seven days.
     *
     * @param  array $searchParams          Search parameters.
     * @param  array $cookieLocationDetails Location cookie details.
     *
     * @return object
     */
    private function getMostPopularAds($searchParams = array(), $cookieLocationDetails = array())
    {
        $ads     = array();
        $adIds   = $this->getSortedMostPopularAdIds($searchParams, $cookieLocationDetails);

        if (count($adIds)) {
            $data    = array();
            $adLimit = 12;
            $data['query_filters']['item']['id']        = $this->getSortedMostPopularAdIds($searchParams, $cookieLocationDetails);
            $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;

            $this->get('fa.solrsearch.manager')->init('ad', null, $data, 1, $adLimit);
            $solrResponseDocs = $this->get('fa.solrsearch.manager')->getSolrResponseDocs();

            if (count($solrResponseDocs)) {
                foreach ($adIds as $adId) {
                    foreach ($solrResponseDocs as $solrResponseDoc) {
                        if ($solrResponseDoc['id'] == $adId) {
                            $ads[] = $solrResponseDoc;
                        }
                    }
                }
            }
        }

        return $ads;
    }

    /**
     * Get most popular ads in seven days.
     *
     * @param  array $searchParams          Search parameters.
     * @param  array $cookieLocationDetails Location cookie details.
     *
     * @return object
     */
    private function getSortedMostPopularAdIds($searchParams = array(), $cookieLocationDetails = array())
    {
        $data    = array();
        $adLimit = 12;

        if (isset($searchParams['item__category_id'])) {
            $searchParams['item_view_counter__root_category_id'] = $searchParams['item__category_id'];
            unset($searchParams['item__category_id']);
        }

        $data['search'] = $searchParams;

        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'search', $data);
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        if (isset($data['search']['item__location']) && $data['search']['item__location'] != LocationRepository::COUNTY_ID) {
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.$data['search']['item__distance'];
        }

        $data['select_fields'] = array('item_view_counter' => array('id'));
        $data['query_sorter']  = array();
        $data['query_sorter']['item_view_counter']['total_hits_last_7_days'] = array('sort_ord' => 'desc', 'field_ord' => 1);

        $this->get('fa.solrsearch.manager')->init('ad.view.counter', null, $data, 1, $adLimit);
        $ads = $this->get('fa.solrsearch.manager')->getSolrResponseDocs();

        $adIds = array();
        if (count($ads)) {
            foreach($ads as $ad) {
                $adIds[] = $ad['id'];
            }
        }

        return $adIds;
    }

    /**
     * Job categories.
     *
     * @param  array $searchParams Search parameters.
     * @return array
     */
    private function getJobsByCategory($searchParams = array())
    {
        $data = array();

        $data['search'] = $searchParams;
        $data['search']['item__category_id'] = CategoryRepository::JOBS_ID;
        $data['search']['item__status_id']   = EntityRepository::AD_STATUS_LIVE_ID;

        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'search', $data);
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $data['facet_fields'] = array(AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID => array('min_count' => 1));

        $this->get('fa.solrsearch.manager')->init('ad', null, $data, 1, 1);
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields();

        $jobCategories = array();
        if (isset($facetResult[AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID])) {
            $jobCategories = get_object_vars($facetResult[AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID]);
        }

        if (!count($jobCategories)) {
            $data = array();

            $data['search'] = $searchParams;
            $data['search']['item__category_id'] = CategoryRepository::JOBS_ID;
            $data['search']['item__status_id']   = EntityRepository::AD_STATUS_LIVE_ID;

            $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'search', $data);
            $data = $this->get('fa.searchfilters.manager')->getFiltersData();

            $data['facet_fields'] = array(AdSolrFieldMapping::CATEGORY_ID => array('min_count' => 1));

            $this->get('fa.solrsearch.manager')->init('ad', null, $data, 1, 1);
            $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields();

            $jobCategories = array();
            if (isset($facetResult[AdSolrFieldMapping::CATEGORY_ID])) {
                $jobCategories = get_object_vars($facetResult[AdSolrFieldMapping::CATEGORY_ID]);
            }
        }
        return $jobCategories;
    }

    /**
     * Get featured employer.
     *
     * @param array   $searchParams  Search parameters.
     * @param string  $staticFilters Static filters.
     * @param boolean $randomSort    Boolean true / false.
     * @param boolean $hasUserLogo   Boolean true / false.
     *
     * @return array
     */
    private function getFeaturedEmployerForJobs($searchParams, $staticFilters = null, $randomSort = false, $hasUserLogo = false)
    {
        // If ad owner has logo
        if ($hasUserLogo) {
            $searchParams = $searchParams + array('ad_jobs__has_user_logo' => 1);
        }

        $data           = array();
        $data['search'] = $searchParams;
        $data['search']['item__status_id'] = EntityRepository::AD_STATUS_LIVE_ID;

        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'search', $data);
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        if (isset($data['search']['item__location']) && $data['search']['item__location'] != LocationRepository::COUNTY_ID) {
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.$data['search']['item__distance'];
        }

        $data['query_sorter'] = array();
        if ($randomSort) {
            $data['query_sorter']['item']['random'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        } else {
            $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        // List no affliate
        $data['query_filters']['item']['is_affiliate_ad'] = 0;

        $data['select_fields']  = array('item' => array('user_id'), 'ad_jobs' => array('employer_name'));
        $data['group_fields'] = array(
            AdSolrFieldMapping::USER_ID => array('limit' => 1),
        );
        if ($staticFilters) {
            $data['static_filters'] = $staticFilters;
        }
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', null, $data, 1, 12, 0, true);
        $solrResponse = $solrSearchManager->getSolrResponse();
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseGroupFields($solrResponse);
        $userDetails = array();
        if (isset($facetResult[AdSolrFieldMapping::USER_ID]) && isset($facetResult[AdSolrFieldMapping::USER_ID]['groups']) && count($facetResult[AdSolrFieldMapping::USER_ID]['groups'])) {
            $adUsers = $facetResult[AdSolrFieldMapping::USER_ID]['groups'];
            foreach ($adUsers as $userCnt => $adUser) {
                $adUser = get_object_vars($adUser);
                if (isset($adUser['doclist']['docs']) && count($adUser['doclist']['docs'])) {
                    if (isset($adUser['doclist']['docs'][0][AdSolrFieldMapping::USER_ID]) && $adUser['doclist']['docs'][0][AdSolrFieldMapping::USER_ID]) {
                        $userDetails[] = array(
                            'id' => $adUser['doclist']['docs'][0][AdSolrFieldMapping::USER_ID],
                            'employer_name' => $this->getRepository('FaUserBundle:User')->getUserProfileName($adUser['doclist']['docs'][0][AdSolrFieldMapping::USER_ID], $this->container),
                        );
                    }
                }
            }
        }

        return $userDetails;
    }

    /**
     * Get adult searching options.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetAdultSearchingOptionsAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $optionsArray = array();
            $categoryId = (int) trim($request->get('categoryId'));
            if ($categoryId) {
                $dimensionIdsArray = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimesionIdsByCategoryIdAndName($categoryId, array('Travel arrangements', 'Independent or Agency'), $this->container);

                if (count($dimensionIdsArray)) {
                    foreach ($dimensionIdsArray as $dimensionId => $dimensionName) {
                        if ($dimensionName == 'Travel arrangements') {
                            $optionIndex = 'travel_options';
                            $options = '<option value="">Select a travel arrangements</option>';
                        } elseif ($dimensionName == 'Independent or Agency') {
                            $optionIndex = 'independent_options';
                            $options = '<option value="">Select a independent or agency</option>';
                        }
                        $optionValues = $this->getEntityManager()->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($dimensionId, $this->container, true, 'id');
                        foreach ($optionValues as $optionId => $optionValue) {
                            $options .= '<option value="'.$optionId.'">'.$optionValue.'</option>';
                        }
                        if ($optionIndex == 'independent_options') {
                            $options .= '<option value=" ">Either</option>';
                        }
                        $optionsArray[$optionIndex] = $options;
                    }
                }

                return new JsonResponse($optionsArray);
            }
        }

        return new Response();
    }

    /**
     * Show home page location blocks.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showLandingPageLocationBlocksAction($searchParams, Request $request)
    {
        if (isset($searchParams['item__location'])) {
            unset($searchParams['item__location']);
        }
        if (isset($searchParams['item__distance'])) {
            unset($searchParams['item__distance']);
        }

        $data           = array();
        $data['search'] = $searchParams;
        $data['search']['item__status_id'] = EntityRepository::AD_STATUS_LIVE_ID;

        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'search', $data);
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $blocks = array(
            AdSolrFieldMapping::TOWN_ID => array(
                'heading' => $this->get('translator')->trans('Top Towns', array(), 'frontend-search-list-block'),
                'search_field_name' => 'item__location',
                'facet_limit'          => 30,
                'repository'           => 'FaEntityBundle:Location',
            ),
            AdSolrFieldMapping::DOMICILE_ID => array(
                'heading' => $this->get('translator')->trans('Top Counties', array(), 'frontend-search-list-block'),
                'search_field_name' => 'item__location',
                'facet_limit'          => 30,
                'repository'           => 'FaEntityBundle:Location',
            ),
        );

        $data['facet_fields'] = array();
        $data['facet_fields'] = array(
            AdSolrFieldMapping::DOMICILE_ID => array('limit' => $blocks[AdSolrFieldMapping::DOMICILE_ID]['facet_limit'], 'min_count' => 1),
            AdSolrFieldMapping::TOWN_ID     => array('limit' => $blocks[AdSolrFieldMapping::TOWN_ID]['facet_limit'], 'min_count' => 1),
        );

        // initialize solr search manager service and fetch data based of above prepared search options
        $this->get('fa.solrsearch.manager')->init('ad', '', $data);
        $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();
        // fetch result set from solr
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);

        if ($facetResult) {
            $facetResult = get_object_vars($facetResult);
            foreach ($blocks as $solrFieldName => $block) {
                if (isset($facetResult[$solrFieldName]) && count($facetResult[$solrFieldName])) {
                    $blocks[$solrFieldName]['facet'] = get_object_vars($facetResult[$solrFieldName]);
                }
            }
        }

        $parameters = array(
            'blocks'          => $blocks,
            'searchParams'    => $searchParams,
        );

        return $this->render('FaContentBundle:LandingPage:showLandingPageLocationBlocks.html.twig', $parameters);
    }
}
