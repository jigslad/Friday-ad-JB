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

use Fa\Bundle\AdBundle\Form\LandingPageAdultSearchType;
use Fa\Bundle\ReportBundle\Entity\AdReportDaily;
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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\ReportBundle\Repository\AdReportDailyRepository;
use Fa\Bundle\UserBundle\Solr\UserShopDetailSolrFieldMapping;
use Fa\Bundle\FrontendBundle\Repository\AdultHomepageRepository;


/**
 * This is adult controller for front side.
 *
 * @author Rohini <rohini.subburam@fridaymadiagroup.com>
 * @copyright 2020 Friday Media Group Ltd
 * @version v1.0
 */
class AdultController extends ThirdPartyLoginController
{
    /**
     * Adult Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $recentAd = array();

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
        
        //get latest adult ads
        $categoryList = $this->getRepository('FaEntityBundle:Category')->getNestedLeafChildrenIdsByCategoryId(CategoryRepository::ADULT_ID);
        $latestAdultAds = $this->getHistoryRepository('FaReportBundle:AdReportDaily')->getRecentAdByCategoryArray($categoryList);
        
        //get featured advertisers
        $featuredAdvertisers = $this->getFeaturedAdvertisers($request, $cookieLocationDetails);
        
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
        
        //get blog details from external site
        $blogArray = array(
            '0'=>array('url'=>AdultHomepageRepository::EXTERNAL_BLOG_URL1,'btn'=>AdultHomepageRepository::EXTERNAL_BLOG_BTN1),
            '1'=> array('url'=>AdultHomepageRepository::EXTERNAL_BLOG_URL2,'btn'=>AdultHomepageRepository::EXTERNAL_BLOG_BTN2)            
        );
        $externalSiteBlogDetails  = CommonManager::getWordpressBlogDetails($blogArray);
        
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $seoLocationName    = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', LocationRepository::COUNTY_ID);
        
        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['locality']) && $cookieLocationDetails['locality']) {
            $seoLocationName = $cookieLocationDetails['locality'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['county']) && $cookieLocationDetails['county']) {
            $seoLocationName = $cookieLocationDetails['county'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['town']) && $cookieLocationDetails['town']) {
            $seoLocationName = $cookieLocationDetails['town'];
        }
        
        $bannersArray = $this->getRepository("FaContentBundle:Banner")->getBannersArrayByPage('homepage', $this->container);

        $featureAds  = $this->getAdultFeatureAds($request, $cookieLocationDetails);

        $formManager  = $this->get('fa.formmanager');
        $form               = $formManager->createForm(LandingPageAdultSearchType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_landing_page_search_result')));
        $parameters = array(
            'facebookLoginUrl' => $facebookLoginUrl,
            'homePopularImagesArray' => $homePopularImagesArray,
            'cookieLocationDetails' => $cookieLocationDetails,
            'seoLocationName' => $seoLocationName,
            'businessExposureUsersDetails' => $featuredAdvertisers,
            'form' => $form->createView(),
            'externalSiteBlogDetails' => $externalSiteBlogDetails,
            'bannersArray' => $bannersArray,
            'latestAdultAds' => $latestAdultAds,
            'featureAds' => $featureAds,
        );
        return $this->render('FaFrontendBundle:Adult:index.html.twig', $parameters);
    }
        
    private function getFeaturedAdvertisers($request,$cookieLocationDetails)
    {
        $businessExposureUsers = array();
        $businessExposureMiles = array();
        $categoryId            =  CategoryRepository::ADULT_ID;
        
        $location     = null;
        
        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) {
            $location = $cookieLocationDetails['location'];
        }
        
        $data           = array();
        if ($location) {
            $data['query_filters']['item']['location'] = $location.'|15';
        }
        $data['query_filters']['item']['category_id'] = $categoryId;
        
        // for showing profile page.
        $businessExposureMiles = $this->getRepository('FaPromotionBundle:Package')->getShopPackageProfileExposureUpsellByCategory($categoryId, $this->container);
           
        
        $businessExposureUserDetails = array();
        
        $businessExposureUsers       = array();
        if (count($businessExposureMiles)) {
            foreach ($businessExposureMiles as $businessExposureMile) {
                foreach ($this->getBusinessExposureUser($data, $businessExposureMile) as $businessExposureUser) {
                    $businessExposureUsers[] = $businessExposureUser;
                }
            }
            
            if (!count($businessExposureUsers)) {
                foreach ($businessExposureMiles as $businessExposureMile) {
                    foreach ($this->getBusinessExposureUser($data, $businessExposureMile) as $businessExposureUser) {
                        $businessExposureUsers[] = $businessExposureUser;
                    }
                }
            }
            
            if (!empty($businessExposureUsers)) {
                shuffle($businessExposureUsers);
                $businessPageLimit = count($businessExposureUsers);
                for ($i = 0; $i < $businessPageLimit; $i++) {
                    if (isset($businessExposureUsers[$i])) {
                        $businessExposureUser = $businessExposureUsers[$i];
                        $businessExposureUserDetails[] = array(
                            'user_id' => $businessExposureUser[UserShopDetailSolrFieldMapping::ID],
                            'company_welcome_message' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::COMPANY_WELCOME_MESSAGE]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::COMPANY_WELCOME_MESSAGE] : null),
                            'about_us' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::ABOUT_US]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::ABOUT_US] : null),
                            'company_logo' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::USER_COMPANY_LOGO_PATH]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::USER_COMPANY_LOGO_PATH] : null),
                            'status_id' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::USER_STATUS_ID]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::USER_STATUS_ID] : null),
                            'user_name' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::USER_PROFILE_NAME]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::USER_PROFILE_NAME] : null),
                        );
                    }
                }
                if($businessExposureUserDetails) {
                    $businessExposureUserDetails = array_map("unserialize", array_unique(array_map("serialize", $businessExposureUserDetails)));;
                }
            }
        }
        
        return $businessExposureUserDetails;
    }
    
    private function getBusinessExposureUser($searchParams, $exposureMiles)
    {
        $data               = array();
        $categoryId         = (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['category_id']) && $searchParams['query_filters']['item']['category_id'] ? $searchParams['query_filters']['item']['category_id'] : null);
        $page               = 1;
        $recordsPerPage     = 10;
        $additionaldistance = $distance = 0;
        
        //set ad criteria to search
        if (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['location']) && $searchParams['query_filters']['item']['location']!='') {
            //list($locationId, $distance) = explode('|', $searchParams['query_filters']['item']['location']);
            $varExplodeLoc = explode('|', $searchParams['query_filters']['item']['location']);
            if(!empty($varExplodeLoc)) {
                if(isset($varExplodeLoc[0])  && $varExplodeLoc[0]!='') { $locationId = $varExplodeLoc[0]; }
                if(isset($varExplodeLoc[1]) && $varExplodeLoc[1]!='' ) { $distance = $varExplodeLoc[1]; }
            }
            if ($exposureMiles === 'national') {
                $additionaldistance = 100000;
            } else {
                $additionaldistance = $exposureMiles;
            }
            $data['query_filters']['user_shop_detail']['location'] = $locationId.'|'.($distance+$additionaldistance);
        }
        
        //set ad criteria to search
        $data['static_filters'] = ' AND '.UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_MILES.':'.$exposureMiles;
        if ($categoryId) {
            $data['query_filters']['user_shop_detail']['profile_exposure_category_id'] = $categoryId;
        }
        
        
        
        $data['query_sorter']   = array();
        $data['query_sorter']['user_shop_detail']['random'] = array('sort_ord' => 'desc', 'field_ord' => 2);
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('user.shop.detail', null, $data, $page, $recordsPerPage, 0, true);
        $solrResponse = $solrSearchManager->getSolrResponse();
        $result = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
        
        return $result;
    }
        
    /**
     * Show Adult home page location blocks.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showAdultHomePageLocationBlocksAction(Request $request)
    {
        $searchParams = array();
        // Active ads
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['category_id'] = CategoryRepository::ADULT_ID;
        
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
        
        return $this->render('FaFrontendBundle:Adult:showAdultHomePageLocationBlocks.html.twig', $parameters);
    }

    /**
     * Set location for home page in cookie.
     *
     * @param Request $request A Request object.
     *
     * @return false|string|null
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
     * Get feature ads.
     *
     * @param Request $request A Request object.
     * @param array $cookieLocationDetails Location cookie array.
     * @return array $featureAds Feature ad list.
     */
    private function getAdultFeatureAds($request, $cookieLocationDetails)
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
     * @param string $location Location of user.
     *
     * @param number $cookieLocationDetails Cookie location detail.
     * @param int $distanceRange Distance limit range.
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
        $data['query_filters']['item']['parent_category_lvl_1_id'] = CategoryRepository::ADULT_ID;
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
}

    
