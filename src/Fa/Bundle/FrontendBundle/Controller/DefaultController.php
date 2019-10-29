<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Controller\ThirdPartyLoginController;
use Fa\Bundle\ContentBundle\Repository\StaticPageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is default controller for front side.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DefaultController extends ThirdPartyLoginController
{

    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function motorHomeAction(Request $request)
    {
        $url = $this->get('router')->generate('landing_page_category', array(
                'category_string' => 'motors',
        ), true);
        return $this->redirect($url, 301);
    }


    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function redirectToLocationPageAction(Request $request)
    {
        $url = $this->get('router')->generate('show_all_towns_by_county', array(
                'countySlug' => $request->get('location'),
        ), true);

        return $this->redirect($url, 301);
    }

    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function changeLocationAction(Request $request)
    {
        if ($request->get('location') == 'uk') {
            $url = $this->get('router')->generate('fa_frontend_homepage');
        } else {
            $url = $this->get('router')->generate('location_home_page', array(
                    'location' => $request->get('location'),
            ), true);
        }

        return $this->redirect($url, 301);
    }

    /**
     * Change email action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function redirectEmailAction(Request $request)
    {
        if (preg_match('/trade-it.co.uk/', $request->getUri())) {
            $url = 'https://secure.trade-it.co.uk/Paa2/AdManagement.aspx';
        } else {
            $url = $this->get('router')->generate('manage_my_ads_active');
        }

        return $this->redirect($url, 301);
    }


    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function motorLocationAction(Request $request)
    {
        $redirect = $this->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location'), $this->container);
        if ($redirect) {
            $url = $this->container->get('router')->generate('listing_page', array(
                    'location' => 'uk',
                    'page_string' => $redirect,
            ), true);
        } else {
            $locationString = $this->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location').'/', $this->container, true);
            $locationString = $locationString != '' ? $locationString : $request->get('location');
            $url = $this->get('router')->generate('landing_page_category_location', array(
                    'category_string' => 'motors',
                    'location' => $locationString,
            ), true);
        }


        return $this->redirect($url, 301);
    }

    /**
     * Redirect category slug change old urls.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function redirectOldCategorySlugChangeAction(Request $request)
    {
        $queryParams = $request->query->all();
        $oldNewSlugArray = $this->getOldNewSlugArray();
        $oldSlugArray = array_keys($oldNewSlugArray);
        $categorySlugArray = explode('/', $request->get('old_cat_slug'));
        $categorySlugArray = array_filter($categorySlugArray);
        foreach ($categorySlugArray as $categorySlugIndex => $categorySlug) {
            if (in_array($categorySlug, $oldSlugArray)) {
                if ($categorySlug == 'farming-wanted') {
                    unset($categorySlugArray[$categorySlugIndex]);
                    if (!array_search('wanted', $categorySlugArray)) {
                        $forsaleKey = array_search('for-sale', $categorySlugArray);
                        if ($forsaleKey !== false) {
                            $categorySlugArray[$forsaleKey] = $categorySlugArray[$forsaleKey].'/wanted';
                        }
                    }
                } else {
                    $categorySlugArray[$categorySlugIndex] = $oldNewSlugArray[$categorySlug];
                }
            }
        }

        $url = $this->container->get('router')->generate('listing_page', array(
            'location' => $request->get('location'),
            'page_string' => implode('/', $categorySlugArray),
        ), true).(count($queryParams) ? '?'.http_build_query($queryParams) : '');

        return $this->redirect($url, 301);
    }

    /**
     * Get old new slug array.
     *
     * @return array
     */
    private function getOldNewSlugArray()
    {
        return array(
            'house-rubbish-clearance' => 'rubbish-clearance',
            'non-digital-cameras' => 'film-cameras',
            'farming-wanted' => '',
        );
    }

    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function articleRedirectAction(Request $request)
    {
        $redirect = $this->getRepository('FaAdBundle:Redirects')->getNewByOldForArticle(ltrim($request->getPathInfo(), '/'), $this->container);
        if ($redirect) {
            return $this->redirect($redirect, 301);
        } else {
            throw new NotFoundHttpException('Invalid url');
        }
    }

    /**
     * Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function indexAction(Request $request)
    {
        if ($request->get('static_page') == 1) {
            $staticPage = $this->getRepository('FaContentBundle:StaticPage')->findOneBy(array('slug' => $request->get('location'), 'type' => StaticPageRepository::STATIC_PAGE_TYPE_ID));

            if (!$staticPage || !$staticPage->getStatus()) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find static page.'));
            }

            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
            $entityCacheManager    = $this->container->get('fa.entity.cache.manager');
            $seoLocationName       = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', LocationRepository::COUNTY_ID);

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

            $parameters = array(
                    'staticPage'      => $staticPage,
                    'seoFields'       => CommonManager::getSeoFields($staticPage, $staticPage->getTitle()),
                    'seoLocationName' => $seoLocationName
            );

            return $this->render('FaContentBundle:StaticPage:show.html.twig', $parameters);
        }

        // init facebook
        $facebookLoginUrl = null;
        $loggedInUser     = null;
        if ($this->isAuth()) {
            $loggedInUser = $this->getLoggedInUser();
        }

        if (!$loggedInUser || ($loggedInUser && !$loggedInUser->getFacebookId())) {
            $facebookLoginUrl = $this->initFacebook('home_page_facebook_login_register');
            $this->container->get('session')->set('fbHomePageLoginUrl', $facebookLoginUrl);
        }

        // set location in cookie.
        $cookieValue = $this->setLocationInCookie($request);

        // get location from cookie
        if ($cookieValue) {
            $cookieLocationDetails = json_decode($cookieValue, true);
        } else {
            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
        }

        // get recommended ads.
        $recommendedAds = $this->getRecommendedAds($request, $cookieLocationDetails);

        // get latest ads.
        list($latestAds, $locationName, $searchResultUrl) = $this->getLatestAds($request, $cookieLocationDetails);

        // get popular shop
        $popularShopsSearchParams = $this->getPopularShopsSearchParams($cookieLocationDetails);

        // get home popular image array.
        $homePopularImagesArray = $this->getRepository('FaContentBundle:HomePopularImage')->getHomePopularImageArray($this->container);

        if (count($homePopularImagesArray)) {
            $locationSlug = 'uk';
            if (isset($cookieLocationDetails['slug'])) {
                $locationSlug = $cookieLocationDetails['slug'];
            }

            foreach ($homePopularImagesArray as $key => $homePopularImage) {
                if (isset($homePopularImagesArray[$key]) && isset($homePopularImagesArray[$key]['url'])) {
                    $homePopularImagesArray[$key]['url'] = str_ireplace('{location}', $locationSlug, $homePopularImagesArray[$key]['url']);
                }
            }
        }

        // get getFeatureAds ads.
        $featureAds  = $this->getFeatureAds($request, $cookieLocationDetails);
        $userDetails = array();
        if (count($featureAds)) {
            $userIds = array();
            foreach ($featureAds as $featureAd) {
                if (isset($featureAd[AdSolrFieldMapping::USER_ID]) && $featureAd[AdSolrFieldMapping::USER_ID]) {
                    $userIds[] = $featureAd[AdSolrFieldMapping::USER_ID];
                }
            }
            array_unique($userIds);
            $userDetails = $this->getRepository('FaUserBundle:User')->getHomePageFeatureAdUserDetail($userIds);
        }

        //get recently viewed ads.
        $recentlyViewedAds = $this->getRecentlyViewedAds($request);

        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $seoLocationName    = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', LocationRepository::COUNTY_ID);

        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['locality']) && $cookieLocationDetails['locality']) {
            $seoLocationName = $cookieLocationDetails['locality'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['county']) && $cookieLocationDetails['county']) {
            $seoLocationName = $cookieLocationDetails['county'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['town']) && $cookieLocationDetails['town']) {
            $seoLocationName = $cookieLocationDetails['town'];
        }

        $parameters = array(
            'recommendedAds'  => $recommendedAds,
            'latestAds'       => $latestAds,
            'locationName'    => $locationName,
            'searchResultUrl' => $searchResultUrl,
            'recentlyViewedAds' => $recentlyViewedAds,
            'facebookLoginUrl' => $facebookLoginUrl,
            'featureAds' => $featureAds,
            'userDetails' => $userDetails,
            'homePopularImagesArray' => $homePopularImagesArray,
            'cookieLocationDetails' => $cookieLocationDetails,
            'seoLocationName' => $seoLocationName,
            'popularShopsSearchParams' => $popularShopsSearchParams,
        );

        return $this->render('FaFrontendBundle:Default:index.html.twig', $parameters);
    }

    /**
     * Get recommended ads.
     *
     * @param Request $request               A Request object.
     * @param array   $cookieLocationDetails Location cookie array.
     *
     * @return array
     */
    private function getRecommendedAds($request, $cookieLocationDetails)
    {
        $cookies = $request->cookies;

        if ($cookies->has('home_page_search_params')) {
            $searchParams   = unserialize($cookies->get('home_page_search_params'));
            $data           = array();
            $keywords       = (isset($searchParams['keywords']) ? $searchParams['keywords'] : null);
            $page           = 1;
            $recordsPerPage = 12;

            //remove keywords.
            if (isset($searchParams['keywords'])) {
                unset($searchParams['keywords']);
            }
            if (isset($searchParams['item']['location'])) {
                unset($searchParams['item']['location']);
            }

            if (isset($searchParams['item']['distance'])) {
                $searchDistance = $searchParams['item']['distance'];
            } else {
                $searchDistance =  CategoryRepository::OTHERS_DISTANCE;
            }

            $location = null;

            if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) {
                $location = $cookieLocationDetails['location'];
            }

            //set ad criteria to search
            $data['query_filters']                        = $searchParams;
            $data['query_filters']['item']['status_id']   = EntityRepository::AD_STATUS_LIVE_ID;
            $searchParamDistance = (isset($searchParams['item']['distance']))?$searchParams['item']['distance']:CategoryRepository::OTHERS_DISTANCE;
            if ($location) {
                $data['query_filters']['item']['location'] = $location.'|'.$searchDistance;
            }
            $data['query_sorter']                         = array();
            if (strlen($keywords)) {
                $data['query_sorter']['item']['score'] = array('sort_ord' => 'desc', 'field_ord' => 1);
            }
            $data['query_sorter']['item']['published_at'] = array('sort_ord' => 'desc', 'field_ord' => 2);
            $data['static_filters']                       = ' AND -'.AdSolrFieldMapping::ROOT_CATEGORY_ID.':'.CategoryRepository::ADULT_ID;

            // List ads only with images and no affliate
            $data['query_filters']['item']['total_images_from_to'] = '1|';
            $data['query_filters']['item']['is_affiliate_ad']      = 0;

            // initialize solr search manager service and fetch data based of above prepared search options
            $solrSearchManager = $this->get('fa.solrsearch.manager');
            $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
            $solrResponse = $solrSearchManager->getSolrResponse();

            // fetch result set from solr
            return $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
        }

        return null;
    }

    /**
     * Get latest ads.
     *
     * @param Request $request               A Request object.
     * @param array   $cookieLocationDetails Location cookie array.
     *
     * @return array
     */
    private function getLatestAds($request, $cookieLocationDetails)
    {
        return array(
            array(),
            null,
            null
        );

        $location     = null;
        $locationName = null;

        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) {
            $location = $cookieLocationDetails['location'];
        }
        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['locality']) && $cookieLocationDetails['locality']) {
            $locationName = $cookieLocationDetails['locality'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['county']) && $cookieLocationDetails['county']) {
            $locationName = $cookieLocationDetails['county'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['town']) && $cookieLocationDetails['town']) {
            $locationName = $cookieLocationDetails['town'];
        }

        $data           = array();
        $keywords       = null;
        $page           = 1;
        $recordsPerPage = 12;

        //set ad criteria to search
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        if ($location) {
            $data['query_filters']['item']['location'] = $location.'|15';
        }

        $data['query_sorter']                         = array();
        $data['query_sorter']['item']['published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        $data['static_filters']                       = ' AND -'.AdSolrFieldMapping::ROOT_CATEGORY_ID.':'.CategoryRepository::ADULT_ID;

        // List ads only with images and no affliate
        $data['query_filters']['item']['total_images_from_to'] = '1|';
        $data['query_filters']['item']['is_affiliate_ad']      = 0;

        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['latitude']) && isset($cookieLocationDetails['longitude'])) {
            $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocationDetails['latitude'].', '.$cookieLocationDetails['longitude']);
            $solrSearchManager->setGeoDistQuery($geoDistParams);
        }
        $solrResponse = $solrSearchManager->getSolrResponse();

        $routeManager    = $this->container->get('fa_ad.manager.ad_routing');
        $searchResultUrl = $routeManager->getListingUrl(array('item__location' => ($location ? $location : LocationRepository::COUNTY_ID)));
        // fetch result set from solr
        return array(
            $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse),
            $locationName,
            $searchResultUrl,
        );
    }

    /**
     * Get recently viwed ads.
     *
     * @param Request $request A Request object.
     *
     * @return array
     */
    private function getRecentlyViewedAds($request)
    {
        $cookies = $request->cookies;

        if ($cookies->has('ad_view_ids')) {
            $adIds = explode(',', $cookies->get('ad_view_ids'));
            $adIds = array_reverse($adIds);

            if (count($adIds) > 12) {
                $adIds = array_slice($adIds, 0, 12);
            }

            $data           = array();
            $keywords       = null;
            $page           = 1;
            $recordsPerPage = 12;

            //set ad criteria to search
            $data['query_filters']['item']['id']          = $adIds;
            $data['query_filters']['item']['status_id']   = EntityRepository::AD_STATUS_LIVE_ID;
            $data['static_filters']                       = ' AND -'.AdSolrFieldMapping::ROOT_CATEGORY_ID.':'.CategoryRepository::ADULT_ID;

            // initialize solr search manager service and fetch data based of above prepared search options
            $solrSearchManager = $this->get('fa.solrsearch.manager');
            $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
            $solrResponse = $solrSearchManager->getSolrResponse();

            // fetch result set from solr
            $recentlyViewedAds = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);

            if (count($recentlyViewedAds) >= 3) {
                $recentyViwedAdsArray = array();
                foreach ($recentlyViewedAds as $recentlyViewedAd) {
                    $key = array_search($recentlyViewedAd->id, $adIds);
                    $recentyViwedAdsArray[$key] = $recentlyViewedAd;
                }
                ksort($recentyViwedAdsArray);

                return $recentyViwedAdsArray;
            } else {
                return null;
            }
        }

        return null;
    }

    /**
     * Get feature ads.
     *
     * @param Request $request               A Request object.
     * @param array   $cookieLocationDetails Location cookie array.
     *
     * @return array
     */
    private function getFeatureAds($request, $cookieLocationDetails)
    {
        // get location from cookie
        $location = null;

        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) {
            $location = $cookieLocationDetails['location'];
        }

        $featureAds = $this->getFeatureAdsSolrResult($location, $cookieLocationDetails, 30);

        if ($location && count($featureAds) < 12) {
            $featureAds = $this->getFeatureAdsSolrResult($location, $cookieLocationDetails, 200);
        }

        if (count($featureAds) < 12) {
            $featureAds = $this->getFeatureAdsSolrResult();
        }

        return $featureAds;
    }

    /**
     * Get feature ads by distance & location.
     *
     * @param number $distanceRange         Distance limit range.
     * @param number $cookieLocationDetails Cookie location detail.
     * @param string $location              Location of user.
     *
     * @return array
     */
    private function getFeatureAdsSolrResult($location = null, $cookieLocationDetails = null, $distanceRange = 30)
    {
        $data           = array();
        $keywords       = null;
        $page           = 1;
        $recordsPerPage = 12;

        //set ad criteria to search
        $data['query_filters']['item']['status_id']              = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['is_homepage_feature_ad'] = '1';
        if ($location) {
            $data['query_filters']['item']['location'] = $location.'|'.$distanceRange;
        }
        $data['query_sorter']                   = array();
        $data['query_sorter']['item']['random'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['latitude']) && isset($cookieLocationDetails['longitude'])) {
            $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocationDetails['latitude'].', '.$cookieLocationDetails['longitude']);
            $solrSearchManager->setGeoDistQuery($geoDistParams);
        }
        $solrResponse = $solrSearchManager->getSolrResponse();

        return $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
    }

    /**
     * Set location for home page in cookie.
     *
     * @param Request $request  A Request object.
     *
     * @throws NotFoundHttpException
     */
    private function setLocationInCookie(Request $request)
    {
        if ($request->get('location') != null) {
            $locationId = $this->getRepository('FaEntityBundle:Location')->getIdBySlug($request->get('location'), $this->container);

            if (!$locationId) {
                $locationId = $this->getRepository('FaEntityBundle:Locality')->getColumnBySlug('id', $request->get('location'), $this->container);
            }

            if (!$locationId) {
                throw new NotFoundHttpException('Invalid location.');
            }

            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
            if (!$cookieLocationDetails) {
                $cookieLocationDetails = array();
            }

            // clear cookie if slug is of uk else change location
            if ($locationId == LocationRepository::COUNTY_ID) {
                $response = new Response();
                $response->headers->clearCookie('location');
                $response->sendHeaders();

                return json_encode(array(
                    'location' => $locationId,
                    'slug'     => 'uk',
                    'location_text' => 'United Kingdom',
                ));
            } elseif ((!isset($cookieLocationDetails['slug']) || $cookieLocationDetails['slug'] != $request->get('location'))) {
                $cookieValue = $this->getRepository('FaEntityBundle:Location')->getCookieValue($request->get('location'), $this->container, true);

                if (count($cookieValue) && count($cookieValue) !== count(array_intersect($cookieValue, $cookieLocationDetails))) {
                    $response = new Response();
                    $cookieValue = json_encode($cookieValue);

                    $response->headers->clearCookie('location');
                    $response->headers->setCookie(new Cookie('location', $cookieValue, time() + (365*24*60*60*1000), '/', null, false, false));
                    $response->sendHeaders();

                    return $cookieValue;
                }
            }
        }

        return null;
    }

    /**
     * Check and set user location.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxUserLocationAction(Request $request)
    {
        $response = new Response();

        if ($request->isXmlHttpRequest() && $request->get('location') != null) {
            $cookieValue = $this->getRepository('FaEntityBundle:Location')->getCookieValue($request->get('location'), $this->container, false, $request->get('location_area'));
            $locationByValue = $this->getRepository('FaEntityBundle:Location')->getArrayByTownId($request->get('location'), $this->container);
            $categoryId = $request->get('catId');
            
            if($categoryId != '') {
                $srchParam['item__category_id'] = $categoryId;
            }
            
            if (!empty($cookieValue)) {
                $srchParam['item__location']   = $cookieValue['location'];
            } elseif (!empty($locationByValue)) {
                $srchParam['item__location']   = $locationByValue['location'];
            } elseif (strtolower($request->get('location')) == 'uk' || strtolower($request->get('location')) == 'united kingdom') {
                $srchParam['item__location']   = 2;
            } 

            $getDefaultRadius = $this->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($srchParam, $this->container);
            $defDistance = ($getDefaultRadius)?$getDefaultRadius:'';
            
            if($defDistance=='') {
                if($categoryId!='') {
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
                    $defDistance = ($rootCategoryId==CategoryRepository::MOTORS_ID)?CategoryRepository::MOTORS_DISTANCE:CategoryRepository::OTHERS_DISTANCE;
                } else {
                    $defDistance = CategoryRepository::MAX_DISTANCE;
                }
            }
            
            if (!empty($cookieValue)) {
                $location['id'] = $cookieValue['location'];
                $location['text'] = $cookieValue['location_text'];
                $location['slug'] = $cookieValue['slug'];
                $location['area'] = $cookieValue['location_area'];
                $location['default_distance'] = $defDistance;
                
                $cookieValue = json_encode($cookieValue);

                $response->headers->clearCookie('location');
                $response->headers->setCookie(new Cookie('location', $cookieValue, time() + (365*24*60*60*1000), '/', null, false, false));

                $response->setContent(json_encode($location));
            } elseif (!empty($locationByValue)) {
                $location['id']   = $locationByValue['location'] = $request->get('location');
                $location['text'] = $locationByValue['location_text'];
                $location['slug'] = $locationByValue['slug'];
                $location['area'] = null;
                $location['default_distance'] = $defDistance;

                $cookieValue = json_encode($locationByValue);

                $response->headers->clearCookie('location');
                $response->headers->setCookie(new Cookie('location', $cookieValue, time() + (365*24*60*60*1000), '/', null, false, false));

                $response->setContent(json_encode($location));
            } elseif (strtolower($request->get('location')) == 'uk' || strtolower($request->get('location')) == 'united kingdom') {
                $location['id']   = 'uk';
                $location['text'] = 'uk';
                $location['slug'] = 'uk';
                $location['area'] = null;
                $location['default_distance'] = $defDistance;
                $response->headers->clearCookie('location');
                $response->setContent(json_encode($location));
            } else {
                $response->headers->clearCookie('location');
                $response->setContent('{}');
            }

            return $response;
        }

        $response->setContent('{}');
        return $response;
    }

    /**
     * Get user location from postcode.
     *
     * @param Request $request A Request object.
     *
     * @param JsonResponse A JsonResponse object.
     */
    public function ajaxUserLocationByPostcodeAction(Request $request)
    {
        if ($request->isXmlHttpRequest() && $request->get('post_code') != null) {
            $cookieValue = $this->getRepository('FaEntityBundle:Location')->getCookieValue($request->get('post_code'), $this->container);
            if (count($cookieValue)) {
                return new JsonResponse($cookieValue);
            }

            return new JsonResponse();
        }

        return new JsonResponse();
    }

    /**
     * This action is used for registration through facebook.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function homePageFacebookLoginRegisterAction(Request $request)
    {
        $this->removeSession('register_user_info');

        $response = $this->processFacebook($request, 'home_page_facebook_login_register', 'fa_frontend_homepage', true, $this->get('translator')->trans('You have successfully connected with Facebook.', array(), 'frontend-homepage'));

        if (is_array($response)) {
            $this->container->get('session')->set('register_user_info', $response);
            $this->removeSession('fbHomePageLoginUrl');
            return $this->redirect($this->generateUrl('fa_user_register'));
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Facebook (First Name, Last Name, Email).', array(), 'frontend-register'), 'fa_frontend_homepage', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('fa_frontend_homepage');
        } else {
            return $response;
        }
    }

    /**
     * Get heade image.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxHomePageGetHeaderImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $cookies        = $request->cookies;
            $width          = $request->get('screenWidth');
            $key            = null;
            $rootCategoryId = null;
            $screenType     = null;
            // get header image array.
            $headerImagesArray = $this->getRepository('FaContentBundle:HeaderImage')->getHeaderImageArray($this->container);
            // get screen type.
            $screenType = $this->getRepository('FaContentBundle:HeaderImage')->getScreenTypeFromResolutionWidth($width);
            // get location from cookie
            $cookieLocationDetails = CommonManager::getLocationDetailFromParamsOrCookie($request->get('location'), $request, $this->container);

            // append domicile & town.
            if (isset($cookieLocationDetails['town_id']) && $cookieLocationDetails['town_id']) {
                //$key .= $cookieLocationDetails['town_id'].'_';
                $townInfoArray = $this->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($cookieLocationDetails['town_id'], $this->container);
                if (isset($townInfoArray['county_id']) && $townInfoArray['county_id']) {
                    $key .= $townInfoArray['county_id'].'_';
                }
            }

            // append category id
            if ($cookies->has('home_page_search_params')) {
                $searchParams   = unserialize($cookies->get('home_page_search_params'));
                if (isset($searchParams['item']['category_id']) && $searchParams['item']['category_id']) {
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($searchParams['item']['category_id'], $this->container);
                    if ($rootCategoryId) {
                        $key .= $rootCategoryId.'_';
                    }
                }
            }
            // append screen type.
            if ($screenType) {
                $key .= $screenType.'_';
            }

            $imageArray = array('image' => null);
            $key        = trim($key, '_');
            echo '<pre>'; print_r($headerImagesArray);die;
            
            if (isset($headerImagesArray[$key])) {
                if (isset($headerImagesArray[$key]['override_1'])) {
                    $randomKey  = array_rand($headerImagesArray[$key]['override_1'], 1);
                    $imageArray = $headerImagesArray[$key]['override_1'][$randomKey];
                } else {
                    $randomKey  = array_rand($headerImagesArray[$key]['override_'], 1);
                    $imageArray = $headerImagesArray[$key]['override_'][$randomKey];
                }
            } elseif ($rootCategoryId && $screenType && isset($headerImagesArray[$rootCategoryId.'_'.$screenType])) {
                if ($rootCategoryId && $screenType && isset($headerImagesArray[$rootCategoryId.'_'.$screenType]['override_1'])) {
                    $randomKey  = array_rand($headerImagesArray[$rootCategoryId.'_'.$screenType]['override_1'], 1);
                    $imageArray = $headerImagesArray[$rootCategoryId.'_'.$screenType]['override_1'][$randomKey];
                } else {
                    $randomKey  = array_rand($headerImagesArray[$rootCategoryId.'_'.$screenType]['override_'], 1);
                    $imageArray = $headerImagesArray[$rootCategoryId.'_'.$screenType]['override_'][$randomKey];
                }
            } elseif ($screenType && isset($headerImagesArray['all'][$screenType])) {
                if ($screenType && isset($headerImagesArray['all'][$screenType]['override_1'])) {
                    $randomKey  = array_rand($headerImagesArray['all'][$screenType]['override_1'], 1);
                    $imageArray = $headerImagesArray['all'][$screenType]['override_1'][$randomKey];
                } else {
                    $randomKey  = array_rand($headerImagesArray['all'][$screenType]['override_'], 1);
                    $imageArray = $headerImagesArray['all'][$screenType]['override_'][$randomKey];
                }
            }

            return new JsonResponse($imageArray);
        }

        return new Response();
    }

    /**
     * Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxHeaderMenuAction(Request $request)
    {
        return $this->render('FaFrontendBundle:Default:ajaxHeaderMenu.html.twig');
    }

    /**
     * Andoroid os manifest json.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function androidOsManifestJsonAction(Request $request)
    {
        $manifestJsonArray = array(
            'name' => 'Friday-Ad',
            'icons' => array(
                array(
                    'src' => $this->container->getParameter('fa.static.url').'/fafrontend/images/android-icons/48x48.png',
                    "sizes" => "48x48",
                    "type" => "image/png",
                    "density" => 0.75
                ),
                array(
                    'src' => $this->container->getParameter('fa.static.url').'/fafrontend/images/android-icons/72x72.png',
                    "sizes" => "72x72",
                    "type" => "image/png",
                    "density" => 1.0
                ),
                array(
                    'src' => $this->container->getParameter('fa.static.url').'/fafrontend/images/android-icons/96x96.png',
                    "sizes" => "96x96",
                    "type" => "image/png",
                    "density" => 1.5
                ),
                array(
                    'src' => $this->container->getParameter('fa.static.url').'/fafrontend/images/android-icons/144x144.png',
                    "sizes" => "144x144",
                    "type" => "image/png",
                    "density" => 2.0
                ),
                array(
                    'src' => $this->container->getParameter('fa.static.url').'/fafrontend/images/android-icons/192x192.png',
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "density" => 2.5
                ),
                array(
                    'src' => $this->container->getParameter('fa.static.url').'/fafrontend/images/android-icons/512x512.png',
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "density" => 3.0
                ),
            )
        );

        return new JsonResponse($manifestJsonArray);
    }

    /**
     * This action is used to remember over 18 plus in cookies.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function proceed18PlusAjaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new Response();
            $response->headers->setCookie(new Cookie('is_over_18', 1, time() + 1 * 86400));
            $response->sendHeaders();
            return new JsonResponse(array('response' => true));
        }

        return new JsonResponse(array('response' => false));
    }

    /**
     * Get popular shops search params.
     *
     * @param array $cookieLocationDetails Location cookie array.
     *
     * @return array
     */
    private function getPopularShopsSearchParams($cookieLocationDetails)
    {
        $searchParams = array();
        $searchParams['item__category_id'] = CategoryRepository::FOR_SALE_ID;

        if (isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) {
            $searchParams['item__location'] = $cookieLocationDetails['location'];

            $distance = CategoryRepository::OTHERS_DISTANCE;

            $searchParams['item__distance'] = $distance;
        }

        return $searchParams;
    }

    /**
     * Tradeit home redirect action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function tradeItHomeRedirectAction(Request $request)
    {
        $tiPath = $this->container->getParameter('base_url').'/bristol/';

        $queryParams = $request->query->all();
        if (count($queryParams)) {
            $tiPath = $tiPath.'?'.http_build_query($queryParams);
        } else {
            if (substr($tiPath, -1)!='/') {
                $tiPath = $tiPath.'/';
            }
        }
        
        $key = md5($request->getClientIp().$request->headers->get('User-Agent'));

        CommonManager::setCacheVersion($this->container, 'ti_url_'.$key, $this->container->getParameter('ti_base_url').(count($queryParams) ? '?'.http_build_query($queryParams) : ''));

        return $this->redirect($tiPath, 301);
    }

    /**
     * Tradeit redirect action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function tradeItRedirectAction($tiPath = null, Request $request)
    {
        if (preg_match('/^(motors|for-sale|animals|jobs|property)\/$/', $tiPath)) {
            $tiPath .= 'bristol/';
        }

        if (preg_match('/bristol-south-west/', $tiPath)) {
            $tiPath = str_replace('bristol-south-west', 'bristol', $tiPath);
        }

        if (preg_match('/^paa\/first_step/', $tiPath)) {
            $tiPath = rtrim($tiPath, '/');
        }

        if (preg_match('/inbox\/reply\/(sender|receiver|all)\/([0-9]+)/', $tiPath, $matches)) {
            $tiPath = 'inbox/reply/'.$matches[1].'/-'.$matches[2].'/';
        }

        if (preg_match('/inbox\/reply\/email\/([0-9]+)/', $tiPath, $matches)) {
            $tiPath = 'inbox/reply/email/-'.$matches[1].'/';
        }
        
        $queryParams = $request->query->all();
        if (count($queryParams)) {
            $tiPath = $tiPath.'?'.http_build_query($queryParams);
        } else {
            if (substr($tiPath, -1)!='/') {
                $tiPath = $tiPath.'/';
            }
        }
        
        $tiUrl = $this->container->getParameter('ti_base_url').'/'.$tiPath;
        $tiPath = $this->container->getParameter('base_url').'/'.$tiPath;
        
        

        $key = md5($request->getClientIp().$request->headers->get('User-Agent'));

        CommonManager::setCacheVersion($this->container, 'ti_url_'.$key, $tiUrl);

        return $this->redirect($tiPath, 301);
    }

    /**
     * Remove Tradeit url from redis action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxRemoveTiUrlFromRedisAction(Request $request)
    {
        $tiCacheKey = md5($request->getClientIp().$request->headers->get('User-Agent'));
        $tiCacheVal = CommonManager::getCacheVersion($this->container, 'ti_url_'.$tiCacheKey);
        if ($tiCacheVal) {
            CommonManager::removeCache($this->container, 'ti_url_'.$tiCacheKey);
        }

        return new JsonResponse(array('response' => false));
    }

    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function motorTiHomeAction(Request $request)
    {
        $url = $this->get('router')->generate('landing_page_category_location', array(
            'category_string' => 'motors',
            'location' => 'bristol',
        ), true);
        return $this->redirect($url, 301);
    }

    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function motorTiLocationAction(Request $request)
    {
        $redirect = $this->getRepository('FaAdBundle:TiRedirects')->getNewByOld($request->get('location'), $this->container);
        if ($redirect) {
            $url = $this->get('router')->generate('landing_page_category_location', array(
                'category_string' => 'motors',
                'location' => $redirect,
            ), true);
        } else {
            $locationString = $this->getRepository('FaAdBundle:TiRedirects')->getNewByOld($request->get('location').'/', $this->container, true);
            $locationString = $locationString != '' ? $locationString : $request->get('location');
            $url = $this->get('router')->generate('landing_page_category_location', array(
                'category_string' => 'motors',
                'location' => $locationString,
            ), true);
        }


        return $this->redirect($url, 301);
    }

    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function articleTiRedirectAction(Request $request)
    {
        $redirect = $this->getRepository('FaAdBundle:TiRedirects')->getNewByOldForArticle(ltrim($request->getPathInfo(), '/'), $this->container);
        if ($redirect) {
            if (substr($redirect, -1) !== '/') {
                $redirect = $redirect.'/';
            }
            return $this->redirect($redirect, 301);
        } else {
            throw new NotFoundHttpException('Invalid url');
        }
    }

    /**
     * Motor Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function changeTiLocationAction(Request $request)
    {
        if ($request->get('location') == 'bristol-south-west' || $request->get('location') == 'region-w-uk-bristol-south-west') {
            $url = $this->get('router')->generate('location_home_page', array(
                'location' => 'bristol',
            ), true);
        } else {
            $locationString = $this->getRepository('FaAdBundle:TiRedirects')->getNewByOld($request->get('location'), $this->container, true);
            if ($locationString) {
                $url = $this->get('router')->generate('location_home_page', array(
                    'location' => $locationString,
                ), true);
            } else {
                $url = $this->get('router')->generate('location_home_page', array(
                    'location' => $request->get('location'),
                ), true);
            }
        }

        return $this->redirect($url, 301);
    }

    /**
     * Show home page location blocks.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showHomePageLocationBlocksAction(Request $request)
    {
        $searchParams = array();
        // Active ads
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;

        $blocks = array(
            AdSolrFieldMapping::DOMICILE_ID => array(
                'heading' => $this->get('translator')->trans('Top Counties', array(), 'frontend-search-list-block'),
                'search_field_name' => 'item__location',
                'facet_limit'          => 30,
                'repository'           => 'FaEntityBundle:Location',
            ),
            AdSolrFieldMapping::TOWN_ID => array(
                'heading' => $this->get('translator')->trans('Top Towns', array(), 'frontend-search-list-block'),
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
                if (isset($facetResult[$solrFieldName]) && !empty($facetResult[$solrFieldName])) {
                    $blocks[$solrFieldName]['facet'] = get_object_vars($facetResult[$solrFieldName]);
                }
            }
        }

        $parameters = array(
            'blocks'          => $blocks,
            'searchParams'    => $searchParams,
        );

        return $this->render('FaFrontendBundle:Default:showHomePageLocationBlocks.html.twig', $parameters);
    }
}
