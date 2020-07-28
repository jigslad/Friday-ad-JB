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

use Fa\Bundle\AdBundle\Form\AdultHomePageSearchType;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;
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
use Fa\Bundle\UserBundle\Solr\UserShopDetailSolrFieldMapping;
/*use Fa\Bundle\FrontendBundle\Repository\AdultHomepageRepository;*/


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
         // set location in cookie.
        $cookieValue = $this->setLocationInCookie($request);
        $searchParams     = $request->get('searchParams');
        
        // get location from cookie
        if ($cookieValue) {
            $cookieLocationDetails = json_decode($cookieValue, true);
        } else {
            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
        }
        
        if (isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) {
            $searchParams['item__location'] = $cookieLocationDetails['location'];
            
            $distance = CategoryRepository::OTHERS_DISTANCE;
            
            $searchParams['item__distance'] = $distance;
        }
        
        //get latest adult ads
        $latestAdultAds = array();
        $latestAdultAds = $this->getLatestAds($searchParams);
        
        //get featured advertisers
        $featuredAdvertisers = array();
        $featuredAdvertisers = $this->getFeaturedAdvertisers($request, $cookieLocationDetails);
        
        //get blog details from external site
        /* Commenting out the adult blog section based on the business discussion */
        /*$blogArray = array(
            '0'=>array('url'=>AdultHomepageRepository::EXTERNAL_BLOG_URL1,'btn'=>AdultHomepageRepository::EXTERNAL_BLOG_BTN1),
            '1'=> array('url'=>AdultHomepageRepository::EXTERNAL_BLOG_URL2,'btn'=>AdultHomepageRepository::EXTERNAL_BLOG_BTN2)            
        );
        
        $externalSiteBlogDetails = array();
        $externalSiteBlogDetails  = CommonManager::getWordpressBlogDetails($blogArray);*/
        
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

        $featureAds =array();
        $featureAds  = $this->getAdultFeatureAds($request, $cookieLocationDetails);

        $formManager  = $this->get('fa.formmanager');
        $form               = $formManager->createForm(AdultHomePageSearchType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_landing_page_search_result')));
        $parameters = array(
            'cookieLocationDetails' => $cookieLocationDetails,
            'seoLocationName' => $seoLocationName,
            'businessExposureUsersDetails' => $featuredAdvertisers,
            'form' => $form->createView(),
            /*'externalSiteBlogDetails' => $externalSiteBlogDetails,*/
            'bannersArray' => $bannersArray,
            'latestAdultAds' => $latestAdultAds,
            'featureAds' => $featureAds,
        );
        return $this->render('FaFrontendBundle:Adult:index.html.twig', $parameters);
    }

    /**
     * Adult Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     * @return Response
     */
    public function indexNewAction(Request $request)
    {
        // set location in cookie.
        $cookieValue = $this->setLocationInCookie($request);
        $searchParams     = $request->get('searchParams');
        $seoToolRepository = $this->getRepository('FaContentBundle:SeoTool');

        // get location from cookie
        if ($cookieValue) {
            $cookieLocationDetails = json_decode($cookieValue, true);
        } else {
            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
        }

        if (isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) {
            $searchParams['item__location'] = $cookieLocationDetails['location'];

            $distance = CategoryRepository::OTHERS_DISTANCE;

            $searchParams['item__distance'] = $distance;
        }

        //get latest adult ads
        $latestAdultAds = $this->getLatestAds($searchParams);

        //get featured advertisers
        $featuredAdvertisers = $this->getFeaturedAdvertisers($request, $cookieLocationDetails);

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

        $seoFields = [];
        $seoPageRules = $seoToolRepository->getSeoRulesKeyValueArray(SeoToolRepository::HOME_PAGE, $this->container);
        if (! empty($seoPageRules[SeoToolRepository::HOME_PAGE.'_global'])) {
            $seoPageRule = $seoPageRules[SeoToolRepository::HOME_PAGE.'_global'];
            if (! empty($seoPageRule)) {
                $seoFields = CommonManager::getSeoFields($seoPageRule);
            }
        }

        $formManager  = $this->get('fa.formmanager');
        $form               = $formManager->createForm(AdultHomePageSearchType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_landing_page_search_result')));
        $parameters = array(
            'cookieLocationDetails' => $cookieLocationDetails,
            'seoLocationName' => $seoLocationName,
            'businessExposureUsersDetails' => $featuredAdvertisers,
            'form' => $form->createView(),
            'bannersArray' => $bannersArray,
            'latestAdultAds' => array(
                'ads' => $latestAdultAds,
                'img_alts' => empty($latestAdultAds) ? [] : $seoToolRepository->getSeoPageRuleDetailForAds($latestAdultAds, SeoToolRepository::ADVERT_IMG_ALT, $this->container),
                'ad_urls' => empty($latestAdultAds) ? [] : $this->getAdUrls($latestAdultAds),
                'img_paths' => empty($latestAdultAds) ? [] : $this->getAdImagePaths($latestAdultAds),
                'root_category_name' => empty($latestAdultAds) ? [] : $this->getRootCategoryName($latestAdultAds)
            ),
            'featureAds' => array(
                'ads' => $featureAds,
                'img_alts' => empty($featureAds) ? [] : $seoToolRepository->getSeoPageRuleDetailForAds($featureAds, SeoToolRepository::ADVERT_IMG_ALT, $this->container),
                'ad_urls' => empty($featureAds) ? [] : $this->getAdUrls($featureAds),
                'img_paths' => empty($featureAds) ? [] : $this->getAdImagePaths($featureAds),
                'root_category_name' => empty($featureAds) ? [] : $this->getRootCategoryName($featureAds)
            ),
            'seoFields' => $seoFields
        );
        return $this->renderWithTwigParameters('FaFrontendBundle:Adult:indexNew.html.twig', $parameters, $request);
    }

    private function getAdUrls($adObjs)
    {
        $adUrls = [];
        $adRoutingManager = $this->container->get('fa_ad.manager.ad_routing');

        foreach ($adObjs as $adObj) {
            $adUrls[$adObj[AdSolrFieldMapping::ID]] = $adRoutingManager->getDetailUrl($adObj);
        }

        return $adUrls;
    }

    private function getAdImagePaths($adObjs)
    {
        $imagePaths = [];
        $adImageRepository = $this->getRepository('FaAdBundle:AdImage');

        foreach ($adObjs as $adObj) {
            $imagePaths[$adObj[AdSolrFieldMapping::ID]] = $adImageRepository->getImagePath($this->container, $adObj, '300X225', 1);
        }

        return $imagePaths;
    }

    private function getRootCategoryName($adObjs)
    {
        $rootCategoryName = [];

        foreach ($adObjs as $adObj) {
            $rootCategoryName[$adObj[AdSolrFieldMapping::ID]] = CommonManager::getCategoryClassNameById($adObj[AdSolrFieldMapping::ROOT_CATEGORY_ID], true);
        }

        return $rootCategoryName;
    }

    /**
     * @param $request
     * @param $cookieLocationDetails
     * @return array
     */
    private function getFeaturedAdvertisers($request, $cookieLocationDetails)
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
            $data['query_filters']['item']['location'] = $location.'|'.CategoryRepository::OTHERS_DISTANCE;
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
        $featureAds = $this->getFeatureAdsSolrResult($location, $cookieLocationDetails, CategoryRepository::OTHERS_DISTANCE);
        /*if ($location && count($featureAds) < 12) {
            $featureAds = $this->getFeatureAdsSolrResult($location, $cookieLocationDetails, 200);
        }
        if (count($featureAds) < 12) {
            $featureAds = $this->getFeatureAdsSolrResult();
        }*/
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
    private function getFeatureAdsSolrResult($location = null, $cookieLocationDetails = null, $distanceRange = 15)
    {
        $data           = array();
        $keywords       = null;
        $page           = 1;
        $recordsPerPage = 12;
        $resultdata     = array();

        //set ad criteria to search
        $data['query_filters']['item']['status_id']              = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['is_top_ad'] = '1';
        $data['query_filters']['item']['root_category_id'] = CategoryRepository::ADULT_ID;
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

        $resultdata = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
        if(empty($resultdata) || count($resultdata)<=3) {
            $data['query_filters']['item']['is_top_ad'] = '0';
            $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);            
            $solrResponse = $solrSearchManager->getSolrResponse();
            $resultdata = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
        }
        return $resultdata;
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
            $headerImagesArray = $this->getRepository('FaContentBundle:HeaderImage')->getHeaderImageArrayByCatId(CategoryRepository::ADULT_ID, $this->container);
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
     * Get ajax a Ethnicity in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetEthnicityAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = (int) trim($request->get('id'));
            if ($nodeId) {
                $dimension = $this->getRepository('FaEntityBundle:CategoryDimension')->findOneBy(array('category'=>$nodeId,'name'=>'ethnicity'));
                $dimensionsArray = array();
                if(!empty($dimension)){
                    $dimensionsList = $this->getRepository('FaEntityBundle:Entity')->findby(array('category_dimension'=>$dimension->getId()));
                    foreach ($dimensionsList as $dimension){
                        $dimensionsArray[] = array('id' => $dimension->getId(), 'text' => $dimension->getName());
                    }
                }
                return new JsonResponse(array('error'=>'Category Not Found'));
            }
            return new JsonResponse(array('error'=>'id Require'));
        }
        return new JsonResponse(array('error'=>"You Don't have Access"));
    }

    /**
     * Get ajax a Services in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetServicesAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = (int) trim($request->get('id'));
            if ($nodeId) {
                $dimension = $this->getRepository('FaEntityBundle:CategoryDimension')->findOneBy(array('category'=>$nodeId,'name'=>'services'));
                $dimensionsArray = array();
                if(!empty($dimension)){
                    $dimensionsList = $this->getRepository('FaEntityBundle:Entity')->findby(array('category_dimension'=>$dimension->getId()));
                    foreach ($dimensionsList as $dimension){
                        $dimensionsArray[] = array('id' => $dimension->getId(), 'text' => $dimension->getName());
                    }
                    return new JsonResponse($dimensionsArray);
                }
                return new JsonResponse(array('error'=>'Category Not Found'));
            }
            return new JsonResponse(array('error'=>'id Require'));
        }
        return new JsonResponse(array('error'=>"You Don't have Access"));
    }

    /**
     * @return array
     */
    private function getLatestAds($searchParams)
    {
        $latestAdultAds = array();
        $categoryList = $this->getRepository('FaEntityBundle:Category')->getNestedLeafChildrenIdsByCategoryId(CategoryRepository::ADULT_ID);
        $latestAdultAdsList = $this->getRepository('FaAdBundle:Ad')->getRecentAdByCategoryArray($categoryList,$searchParams);
        if(!empty($latestAdultAdsList)){
            $latestAdultAds = $this->getlatestAdSolrResultbyIds($latestAdultAdsList);
        }
        return $latestAdultAds;
    }

    /**
     * @param $id
     * @return |null
     */
    private function getlatestAdSolrResultbyIds($id)
    {
        if (is_array($id)) {
            $id = '(' . implode(' OR ', $id) . ')';
        }

        $data           = array();
        $keywords       = null;
        $page           = 1;
        $recordsPerPage = 1;
        //set ad criteria to search
        $data['query_filters']['item']['id'] = $id;

        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
        $solrResponse = $solrSearchManager->getSolrResponse();

        $data = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
        if(!empty($data)){
            return $data;
        }
        return null;
    }

    private function renderWithTwigParameters($view, $parameters, $request)
    {
        if ($this->isAuth()) {
            //check for own ad.
            $loggedInUser = $this->getLoggedInUser();
            $loggedInUserId = $loggedInUser->getId();
            $userProfileName = $loggedInUser->getProfileName();

            $parameters['totalUnreadMsg'] = $this->getRepository('FaMessageBundle:Message')->getMessageCount($loggedInUserId, 'all', $this->container);
            $parameters['userRole'] = $this->getRepository('FaUserBundle:User')->getUserRole($loggedInUserId, $this->container);
            $parameters['myProfileUrl'] = $this->container->get('fa_ad.manager.ad_routing')->getProfilePageUrl($loggedInUserId);
            $parameters['userLogo'] = CommonManager::getUserLogoByUserId($this->container, $loggedInUserId, true, false, $userProfileName);

            $notifications = $this->getRepository('FaMessageBundle:NotificationMessageEvent')->getActiveNotification($loggedInUserId);
            $parameters['notification_count'] = count($notifications);

            $userCart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedInUserId, $this->container);
            $parameters['userDetail'] = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($userCart->getId());
        } else {
            $parameters['totalUnreadMsg'] = 0;
        }

        $location = $request->get('location');
        $locationDetails = $this->getRepository('FaEntityBundle:Location')->getLocationDetailForHeaderCategories($this->container, $request, $location);
        $parameters['headerCategories'] = $this->getRepository('FaEntityBundle:Category')->getAdultHeaderCategories($this->container, $locationDetails);

        return $this->render($view, $parameters);
    }
}

    
