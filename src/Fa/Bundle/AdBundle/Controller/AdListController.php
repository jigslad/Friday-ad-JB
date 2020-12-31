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

use Fa\Bundle\AdBundle\Form\AdLeftSearchNewType;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;
use Fa\Bundle\EntityBundle\Entity\Entity;
use Fa\Bundle\EntityBundle\Entity\Location;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Solr\AdAnimalsSolrFieldMapping;
use Fa\Bundle\AdBundle\Solr\AdMotorsSolrFieldMapping;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Fa\Bundle\UserBundle\Solr\UserShopDetailSolrFieldMapping;
use Fa\Bundle\UserBundle\Solr\UserSolrFieldMapping;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ReportBundle\Entity\AdFeedClickReportDaily;
use Fa\Bundle\AdBundle\Form\AdTopSearchType;
use Fa\Bundle\AdBundle\Form\AdLeftSearchType;
use Fa\Bundle\UserBundle\Form\UserHalfAccountEmailOnlyType;
use Fa\Bundle\AdBundle\Form\AdLeftSearchDimensionType;

/**
 * This controller is used for ad listing.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdListController extends CoreController
{
    /**
     * Top search.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function topSearchAction(Request $request)
    {
        $formManager = $this->get('fa.formmanager');
        
        $form        = $formManager->createForm(AdTopSearchType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_search_result')));

        $bindSearchParams = array();
        $searchParams     = $request->get('searchParams');

        if ($searchParams && count($searchParams)) {
            foreach ($form->all() as $field) {
                if (isset($searchParams[$field->getName()])) {
                    $bindSearchParams[$field->getName()] = $searchParams[$field->getName()];
                }
            }

            if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
                $bindSearchParams['tmpLeafLevelCategoryId'] = $searchParams['item__category_id'];
                $cat = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($searchParams['item__category_id'], false, $this->container);
                $parent = $this->getRepository('FaEntityBundle:Category')->getCategoryArrayById(key($cat), $this->container);
                if ($parent) {
                    $bindSearchParams['item__category_id'] =  $parent['id'];
                }
            }
            $form->submit($bindSearchParams);
        }

        $parameters = array('form' => $form->createView(), 'searchParams' => $searchParams);

        if ($request->get('isHomeSearch')) {
            return $this->render('FaFrontendBundle:Default:homePageSearch.html.twig', $parameters);
        } elseif ($request->get('isAdultHomeSearch')) {
            return $this->render('FaFrontendBundle:Adult:homePageAdultSearch.html.twig', $parameters);
        } elseif ($request->get('isErrorSearch')) {
            return $this->render('FaFrontendBundle:Default:errorPageSearch.html.twig', $parameters);
        } else {
            return $this->render('FaAdBundle:AdList:topSearch.html.twig', $parameters);
        }
    }


    /**
     * Top search result.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function searchResultAction(Request $request)
    {
        //$requestlocation = ($request->cookies->get('location')!='')?$request->cookies->get('location'):($request->get('location')!=''?$request->get('location'):($request->attributes->get('location')?$request->attributes->get('location'):'uk'));
        $requestlocation = $request->get('location');
        $successPaymentModalbox = false;

        // Redirect to page 1 if page exceed more than 250.
        if ($request->get('page') && $request->get('page') > 250) {
            $url = str_replace('page-'.$request->get('page'), 'page-1', $request->getUri());
            $url = str_replace('page='.$request->get('page'), 'page=1', $url);

            return $this->redirect($url, 301);
        }

        $currentRoute = $request->get('_route');
        //for TI motor redirect
        if ($currentRoute == 'ti_motor_listing_page' && $request->attributes->get('not_found') == 1) {
            $pageString  = $request->get('page_string');
            $locationS   = $requestlocation;

            $locationString = $this->getRepository('FaAdBundle:TiRedirects')->getNewByOld($locationS, $this->container, true);
            if ($locationString) {
                $locationS = $locationString;
            }

            if (preg_match("/-N-1z1|-N-1z0|-N-2m|-N-2o|-N-2t|-N-2y|-N-31|-N-g2/", $request->get('page_string'), $matches)) {
                $pageString = substr($request->get('page_string'), 0, strpos($request->get('page_string'), $matches[0])).$matches[0];
            }

            $redirect = $this->getRepository('FaAdBundle:TiRedirects')->getNewByOld($pageString, $this->container);

            if ($redirect) {
                if ($redirect == 'homepage') {
                    $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                } else {
                    $url = $this->generateUrl('listing_page', array('location' => $locationS, 'page_string' => rtrim($redirect, '/')), true);
                }
                return $this->redirect($url, 301);
            }

            throw new NotFoundHttpException('Invalid location.');
        } elseif ($currentRoute == 'ti_motor_listing_page') {
            $tiPath = $requestlocation.'/'.$request->get('page_string');
            $tiUrl = 'http://'.$this->container->getParameter('fa.ti.motor.host').'/'.$tiPath;
            $key = md5($request->getClientIp().$request->headers->get('User-Agent'));
            $tiPath = $this->container->getParameter('base_url').'/'.$tiPath;
            CommonManager::setCacheVersion($this->container, 'ti_url_'.$key, $tiUrl);

            return $this->redirect($tiPath, 301);
        }

        if ($request->attributes->get('not_found') == 1 && !in_array($currentRoute, array('show_business_user_ads', 'show_business_user_ads_page', 'show_business_user_ads_location'))) {
            $redirect = $this->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('page_string'), $this->container);
            if ($redirect) {
                $url = $this->generateUrl('listing_page', array('location' => $requestlocation, 'page_string' => rtrim($redirect, '/')), true);
                return $this->redirect($url, 301);
            }

            throw new NotFoundHttpException('Invalid location.');
        }

        $pageUrl = $this->getPageUrl($request);
        $findersSearchParams = $request->get('finders');
        if (isset($findersSearchParams['advertgone'])) {
            unset($findersSearchParams['advertgone']);
        }
        $objSeoToolOverride = null;
        if ($pageUrl) {
            $objSeoToolOverride = $this->getRepository('FaContentBundle:SeoToolOverride')->findSeoRuleByPageUrl($pageUrl, $findersSearchParams, $this->container);
        }
        
        //get SEO Source URL for classic-car
        if (strpos($pageUrl, 'motors/classic-cars') !== false && !$request->query->has('item_motors__reg_year')) {
            $getClassicCarRegYear = $this->getRepository('FaContentBundle:SeoTool')->findSeoSourceUrlMotorRegYear('motors/classic-cars/');
            if (!empty($getClassicCarRegYear)) {
                $findersSearchParams['item_motors__reg_year'] = $getClassicCarRegYear;
            }
        }
        
        $isClassicCarPage = 0;
        if (strpos($pageUrl, 'motors/cars') !== false && isset($findersSearchParams['item_motors__reg_year'])) {
            $allUnder25Yrs = 1;
            $get25ysrOlder = date('Y') - 24;

            foreach ($findersSearchParams['item_motors__reg_year'] as $srchRegYr) {
                if ($srchRegYr > $get25ysrOlder) {
                    $allUnder25Yrs = 0;
                    break;
                }
            }
            
            if ($allUnder25Yrs==1) {
                $isClassicCarPage = 1;
            }
        }

        if (!in_array($currentRoute, array('show_business_user_ads', 'show_business_user_ads_page', 'show_business_user_ads_location'))) {
            if (preg_match("/[A-Z]/", $request->getPathInfo())) {
                $url = str_replace($request->getPathInfo(), strtolower($request->getPathInfo()), $request->getUri());
                return $this->redirect($url, 301);
            }
            // set location in cookie
            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
            if (!$cookieLocationDetails) {
                $cookieLocationDetails = array();
            }
            
            $cookieValue = '';
            
            if ($requestlocation != null && $requestlocation != 'uk' && (!isset($cookieLocationDetails['slug']) || $cookieLocationDetails['slug'] != $requestlocation || $requestlocation == LocationRepository::LONDON_TXT)) {
                $cookieValue = $this->getRepository('FaEntityBundle:Location')->getCookieValue($requestlocation, $this->container, true);
                
                if (count($cookieValue) && count($cookieValue) !== count(array_intersect($cookieValue, $cookieLocationDetails))) {
                    $response = new Response();
                    $cookieValue = json_encode($cookieValue);
                    $response->headers->clearCookie('location');
                    $response->headers->setCookie(new Cookie('location', $cookieValue, time() + (365*24*60*60*1000), '/', null, false, false));
                    $response->sendHeaders();
                } else {
                    $cookieValue = json_encode($cookieValue);
                }
            } elseif ($requestlocation != null && $requestlocation == 'uk') {
                $response = new Response();
                $response->headers->clearCookie('location');
                $response->sendHeaders();

                $cookieValue = json_encode(array(
                    'location' => LocationRepository::COUNTY_ID,
                    'slug'     => 'uk',
                    'location_text' => 'United Kingdom',
                ));
            }
        }
        
        $transactionJsArr = [];
        //check session for upgrade Payment has been done successfully
        if ($this->container->get('session')->has('payment_success_for_upgrade') && $this->container->get('session')->has('payment_success_for_upgrade') != '') {
            $successPaymentModalbox 	= true;
            if ($this->container->get('session')->has('upgrade_payment_transaction_id')) {
                $loggedinUser = $this->getLoggedInUser();
                if ($loggedinUser) {
                    $transcations	= $this->getRepository('FaPaymentBundle:Payment')->getTranscationDetailsForGA($this->container->get('session')->get('upgrade_payment_transaction_id'), $loggedinUser, true);
                    $transactionJsArr['getTranscationJs'] = CommonManager::getGaTranscationJs($transcations);
                    $transactionJsArr['getItemJs']        = CommonManager::getGaItemJs($transcations);
                    $transactionJsArr['ga_transaction']   = $transcations;
                }
            }
            $this->container->get('session')->remove('payment_success_for_upgrade');
            $this->container->get('session')->remove('upgrade_payment_success_redirect_url');
            $this->container->get('session')->remove('upgrade_payment_transaction_id');
        }

        // get location from cookie
        if (isset($cookieValue) && $cookieValue) {
            if (is_array($cookieValue)) {
                $cookieValue = json_encode($cookieValue);
            }
            $cookieLocationDetails = json_decode($cookieValue, true);
        } else {
            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
        }
       
        //check view alert tip for location area
        $areaToolTipFlag = false;
        if (!$request->cookies->has('frontend_area_alert_tooltip') && $requestlocation != null && strtolower($requestlocation) == LocationRepository::LONDON_TXT) {
            $response = new Response();
            $response->headers->setCookie(new Cookie('frontend_area_alert_tooltip', $requestlocation, time() + (365*24*60*60*1000), '/', null, false, false));
            $response->sendHeaders();
            $areaToolTipFlag = true;
        }

        $mapFlag = $request->get('map', false);

        $data    = $this->setDefaultParameters($request, $mapFlag, 'finders', $cookieLocationDetails);

        $extendlocation = '';
        $extendRadius = '';
        $setDefRadius = 1;
        $setDefUKLoc = 0;
        $isBusinessPage = 0;
        if (($currentRoute == 'show_business_user_ads' || $currentRoute == "show_business_user_ads_location" || $currentRoute == "show_business_user_ads_page")) {
            $isBusinessPage = 1;
            $setDefRadius = 0;
        }
        if (isset($data['search']) && isset($data['search']['item__distance'])) {
            $setDefRadius = 0;
        }
        if (($requestlocation != null && $requestlocation == 'uk') || (isset($cookieLocationDetails['lvl']) && $cookieLocationDetails['lvl']=='') || (!isset($cookieLocationDetails['lvl']))) {
            //unset($data['search']['item__distance']);
            $setDefUKLoc = 1;
            $setDefRadius = 0;
        }
      
        $locationRadius = array();
        if ($setDefRadius && $isBusinessPage==0) {
            if (isset($findersSearchParams['item__category_id']) && $findersSearchParams['item__category_id']) {
                $parentCategoryIds = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($findersSearchParams['item__category_id'], false, $this->container));
                $locationRadius = $this->getRepository('FaAdBundle:LocationRadius')->getSingleLocationRadiusByCategoryIds($parentCategoryIds);
                if ($locationRadius) {
                    $findersSearchParams['item__distance'] = $data['search']['item__distance'] = $data['query_filters']['item']['distance'] = $locationRadius['defaultRadius'];
                    $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'. (isset($data['search']['item__distance']) ? $data['search']['item__distance'] : '');
                    if ($locationRadius['extendedRadius']>0 && $locationRadius['extendedRadius'] > $locationRadius['defaultRadius']) {
                        $extendlocation = $data['search']['item__location'].'|'. (isset($locationRadius['extendedRadius']) ? $locationRadius['extendedRadius']: '').'|'. (isset($data['search']['item__distance']) ? $data['search']['item__distance'] : '');
                        $extendRadius = (isset($locationRadius['extendedRadius']) ? $locationRadius['extendedRadius']: '');
                    }
                }

                if (!isset($data['search']['item__distance'])) {
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($findersSearchParams['item__category_id'], $this->container);
                    $findersSearchParams['item__distance'] = $data['search']['item__distance'] = ($rootCategoryId==CategoryRepository::MOTORS_ID)?CategoryRepository::MOTORS_DISTANCE:CategoryRepository::OTHERS_DISTANCE;
                    $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'. ((isset($data['search']['item__distance']) ? $data['search']['item__distance'] : ''));
                }
            }
        }

        $defaultRecordsPerPage = $this->container->getParameter('fa.search.records.per.page');
        $keywords       = (isset($data['search']['keywords']) && $data['search']['keywords']) ? $data['search']['keywords']: null;
        $page           = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page']: 1;
        $recordsPerPage = (isset($data['pager']['limit']) && $data['pager']['limit']) ? $data['pager']['limit']: $this->container->getParameter('fa.search.records.per.page');

        // top sorting.
        if ($currentRoute == 'show_business_user_ads' || $currentRoute == "show_business_user_ads_location" || $currentRoute == "show_business_user_ads_page") {
            if (isset($data['query_sorter']) && isset($data['query_sorter']['item']['is_top_ad'])) {
                unset($data['query_sorter']['item']['is_top_ad']);
            }

            if (!$cookieLocationDetails && isset($data['query_sorter']['item']) && isset($data['query_sorter']['item']['geodist'])) {
                unset($data['query_sorter']['item']['geodist']);
            }
        }


        // fetch location from cookie.
        $geoDistParams = array();
        if ($mapFlag) {
            if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['latitude']) && isset($cookieLocationDetails['longitude'])) {
                //$data['query_sorter']['item']['geodist'] = array('sort_ord' => 'asc', 'field_ord' => 1);
            }
        }

        if ($request->attributes->get('customized_page')) {
            list($keywords, $data) = $this->handleCustomizedUrl($data, $request);
        }


        $extendedData = $otherMatchingData = array();
        $extendedData = $otherMatchingData = $data;

        if ($extendlocation) {
            $extendedData['query_filters']['item']['location'] = $extendlocation;
        }


        if ($locationRadius) {
            $otherMatchingData['query_filters']['item']['location'] = $data['search']['item__location'].'| '.CategoryRepository::MAX_DISTANCE.' | '. (isset($locationRadius['extendedRadius']) ? $locationRadius['extendedRadius']: (isset($locationRadius['defaultRadius'])?$locationRadius['defaultRadius']:0));
        } else {
            $otherMatchingData['query_filters']['item']['location'] = $data['search']['item__location'].'| '.CategoryRepository::MAX_DISTANCE.' | '.(isset($data['search']['item__distance'])?$data['search']['item__distance']:0);
        }


        // initialize solr search manager service and fetch data based of above prepared search options
        $this->get('fa.solrsearch.manager')->init('ad', $keywords, $data, $page, $recordsPerPage, 0, true);
        $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();

        // fetch result set from solr
        $result      = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
        $resultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);

        $defaultRadiusPageCount = ($page==0)?1:ceil($resultCount/$recordsPerPage);
        $defaultRadiusLastPageCount = ($resultCount%$recordsPerPage);

        $facetResult = get_object_vars($facetResult);
        
        $rootCategoryId = null;
        if (isset($data['search']['item__category_id'])) {
            $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($data['search']['item__category_id'], $this->container);
        }
        
        //check facet count again in ticket FFR-3375
        $locationFacets = array();
        if (isset($data['search']['item__location'])) {
            $locationResult = $this->getRepository('FaEntityBundle:Location')->find($data['search']['item__location']);
        }
        
        if (!empty($facetResult)) {
           // $locationFacets = $this->getLocationFacetForSearchResult($facetResult, $data);
        }
        

        $extendedResult = array();
        $extendedResultCount = 0;
        $extpage =0;
        $staticOffset = 0;
        
        if ($extendlocation) {
            // initialize solr search manager service and fetch data based of above prepared search options
            if ($page == $defaultRadiusPageCount) {
                $extpage = 1;
                $staticOffset = 0;
            } elseif ($page > $defaultRadiusPageCount && $defaultRadiusPageCount>0 && $page>0) {
                $extpagediff = $page - $defaultRadiusPageCount;
                if ($extpagediff<=0) {
                    $extpage = 1;
                } else {
                    $extpage = $extpagediff;
                }
                $staticOffset = ($extpagediff>0)?((($extpage)*$recordsPerPage) - $defaultRadiusLastPageCount):0;
            } elseif ($page > $defaultRadiusPageCount && $page>0) {
                $extpagediff = $page;
                if ($extpagediff<=0) {
                    $extpage = 1;
                } else {
                    $extpage = $extpagediff;
                }
                $staticOffset = ($extpagediff>0)?((($extpage-1)*$recordsPerPage) - $defaultRadiusLastPageCount):0;
            } else {
                $extpage =1;
                $staticOffset = 0;
            }
            $this->get('fa.solrsearch.manager')->init('ad', $keywords, $extendedData, $extpage, $recordsPerPage, $staticOffset, true);
            $extendedSolrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();
            $extendedResult      = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($extendedSolrResponse);
            $extendedResultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($extendedSolrResponse);
        }

        $getXMiles = 0;
        
        if (!$mapFlag && $setDefUKLoc==0 && $isBusinessPage==0) {
            if (isset($otherMatchingData['search']['item__distance'])) {
                //$otherMatchingData['query_sorter']['item']['geodist'] = array('sort_ord' => 'asc', 'field_ord' => 1);
            }
            $this->get('fa.solrsearch.manager')->init('ad', $keywords, $otherMatchingData, 1, 1, 0, true);
            
            $otherMatchingSolrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();
            
            $otherMatchingResult      = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($otherMatchingSolrResponse);
            $otherMatchingResultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($otherMatchingSolrResponse);

            
            if ($otherMatchingResultCount>0 && !empty($otherMatchingResult)) {
                if (isset($otherMatchingResult[0]['away_from_location'])) {
                    $getXMiles = CommonManager::getClosest($otherMatchingResult[0]['away_from_location']);
                }
            }
        }

        $mergedresult = array_merge($result, $extendedResult);
        $mergedResultCount = $resultCount + $extendedResultCount;


        /*$nextMiles              = 0;
        $expandMilesresultCount = 'XX';
        if (isset($data['search']['item__distance']) && $data['search']['item__distance'] < 200) {
            $nextMiles              = $this->getNextMiles($data['search']['item__distance']);
            $expandMilesresultCount = $this->getExpandDistanceAdCount($request, $keywords, $data, $resultCount, $nextMiles);
        }*/


    
        //$locationFacets = array();
        if (isset($data['search']['item__location']) && $data['search']['item__location'] != LocationRepository::COUNTY_ID && (!isset($data['search']['item__distance']) || (isset($data['search']['item__distance']) && $data['search']['item__distance'] >= 0 && $data['search']['item__distance'] <= CategoryRepository::MAX_DISTANCE))) {
            // $locationFacets =  get_object_vars($facetResult['a_l_town_id_txt']);
            
            // if(isset($facetResult['a_l_area_id_txt']) && !empty(get_object_vars($facetResult['a_l_area_id_txt']))) {
            //     $locationFacets = $locationFacets + get_object_vars($facetResult['a_l_area_id_txt']);
            // }

            if (isset($locationFacets[$data['search']['item__location']])) {
                unset($locationFacets[$data['search']['item__location']]);
            }
            $locationFacets = array_slice(array_unique($locationFacets), 0, 5, true);
        }




        // initialize pagination manager service and prepare listing with pagination based of solr result
        $this->get('fa.pagination.manager')->init($mergedresult, $page, $recordsPerPage, $mergedResultCount);
        $pagination = $this->get('fa.pagination.manager')->getSolrPagination();
        $mapResult = [];
        //for map purpose iterating the advert result
        if (!empty($pagination) && $mapFlag) {
            $mapData = (array) $pagination;
            $prevLatLongArr = array('latitude'=> 0, 'longitude'=>0);
            $existingLatLongArr = array();
            foreach ($pagination as $advert) {
                $mapResult[$advert->id]['adId'] = $advert[AdSolrFieldMapping::ID];
                $mapResult[$advert->id]['title'] = $advert[AdSolrFieldMapping::TITLE];

                $existingLatLongArr = array('latitude'=> $advert[AdSolrFieldMapping::LATITUDE][0], 'longitude'=>$advert[AdSolrFieldMapping::LONGITUDE][0]);

                if ($prevLatLongArr == $existingLatLongArr) {
                    $latOffset  = rand(0, 1000)/10000000;
                    $longOffset = rand(0, 1000)/10000000;
                } else {
                    $latOffset = 0;
                    $longOffset = 0;
                }
                

                if (isset($advert[AdSolrFieldMapping::LATITUDE])) {
                    $mapResult[$advert->id]['latitude'][0] = $advert[AdSolrFieldMapping::LATITUDE][0]+floatval($latOffset);
                    //$mapResult[$advert->id]['latitude'] = $advert[AdSolrFieldMapping::LATITUDE];
                    $prevLatLongArr['latitude'] = $advert[AdSolrFieldMapping::LATITUDE][0];
                }
                if (isset($advert[AdSolrFieldMapping::LONGITUDE])) {
                    $mapResult[$advert->id]['longitude'][0] = $advert[AdSolrFieldMapping::LONGITUDE][0]+floatval($longOffset);
                    //$mapResult[$advert->id]['longitude'] = $advert[AdSolrFieldMapping::LONGITUDE];
                    $prevLatLongArr['longitude'] = $advert[AdSolrFieldMapping::LONGITUDE][0];
                }

                if (isset($advert['away_from_location'])) {
                    $mapResult[$advert->id]['away_from_location'] = $advert['away_from_location'];
                }
            }

            if (!empty($mapResult) && $setDefUKLoc==0 && $isBusinessPage==0) {
                usort($mapResult, function ($a, $b) {
                    return $a['away_from_location'] - $b['away_from_location'];
                });
            }
        }
        // set search params in cookie.
        $this->setSearchParamsCookie($data,$request);
        //Get Recommmended Slots
        $getRecommendedSlots = array();
        $getRecommendedSlots = $this->getRecommendedSlot($data, $keywords, $page, $mapFlag, $request, $rootCategoryId);
        
        //if(!empty($getRecommendedSlots)) { shuffle($getRecommendedSlots); }
        $getRecommendedSrchSlotWise = array();
        $getRecommendedSrchSlots=array();
        if (!empty($getRecommendedSlots)) {
            foreach ($getRecommendedSlots as $getRecommendedSlot) {
                $getRecommendedSrchSlots[$getRecommendedSlot['creative_group']][] = $getRecommendedSlot;
            }
        }
        $recommendedSlotArr = array();
        $recommendedSlotOrder = array();
        if (!empty($getRecommendedSrchSlots)) {
            for ($arj=1;$arj<=8;$arj++) {
                if (isset($getRecommendedSrchSlots[$arj])) {
                    $recommendedSlotArr[$arj] = $getRecommendedSrchSlots[$arj][0];
                    $recommendedSlotOrder[$arj] = $getRecommendedSrchSlots[$arj][0]['creative_ord'];
                    if (isset($_COOKIE['recommended_slot_'.$arj])) {
                        if (isset($getRecommendedSrchSlots[$arj][1]) && $_COOKIE['recommended_slot_'.$arj] == $getRecommendedSrchSlots[$arj][0]['creative_ord']) {
                            $recommendedSlotArr[$arj] = $getRecommendedSrchSlots[$arj][1];
                            $recommendedSlotOrder[$arj] = $getRecommendedSrchSlots[$arj][1]['creative_ord'];
                        } elseif (isset($getRecommendedSrchSlots[$arj][2])) {
                            if ($_COOKIE['recommended_slot_'.$arj]== $getRecommendedSrchSlots[$arj][1]['creative_ord'] || $_COOKIE['recommended_slot_'.$arj]== $getRecommendedSrchSlots[$arj][0]['creative_ord']) {
                                $recommendedSlotArr[$arj] = $getRecommendedSrchSlots[$arj][2];
                                $recommendedSlotOrder[$arj] = $getRecommendedSrchSlots[$arj][2]['creative_ord'];
                            } elseif ($_COOKIE['recommended_slot_'.$arj]== $getRecommendedSrchSlots[$arj][2]) {
                                $recommendedSlotArr[$arj] = $getRecommendedSrchSlots[$arj][0];
                                $recommendedSlotOrder[$arj] = $getRecommendedSrchSlots[$arj][0]['creative_ord'];
                            }
                        }
                    }
                    setcookie('recommended_slot_'.$arj, $recommendedSlotOrder[$arj]);
                }
            }
        }

        if (!empty($recommendedSlotArr)) {
            $getRecommendedSrchSlotWise = $recommendedSlotArr;
        }
        
        $fourLocationsPagination = $fourLocationsDetails = array();
        $fourLocationsResultCount = 0;
        $fourLocationsDetailsWithDistance = array();
        

        if ($mergedResultCount==0 && $setDefUKLoc==0 && $isBusinessPage==0) {
            $fourLocationsData = $fourLocWithDistanceData = array();
            $fourLocationsData = $fourLocWithDistanceData = $data;

            $fourLocationsDetails = $this->getRepository('FaAdBundle:Ad')->getAdsCountBySearchParams($fourLocationsData);

            if (!empty($fourLocationsDetails)) {
                unset($fourLocWithDistanceData['facet_fields']);
                $fourLocationCount = 0;
                foreach ($fourLocationsDetails as $fourLocationsDetail) {
                    $fourLocWithDistanceData['search']['item__location'] = $fourLocationsDetail['town_id'];
                    if ($locationRadius) {
                        $fourLocWithDistanceData['query_filters']['item']['location'] = $fourLocationsDetail['town_id'] . '|'. $locationRadius['defaultRadius'];
                    } else {
                        if ((isset($data['search']['item__distance'])) || (isset($fourLocationsData['query_filters']['item']['distance']))) {
                            $fourLocWithDistanceData['query_filters']['item']['location'] = $fourLocationsDetail['town_id'] . '| '.(isset($data['search']['item__distance'])?$data['search']['item__distance']:(isset($fourLocationsData['query_filters']['item']['distance'])?$fourLocationsData['query_filters']['item']['distance']:''));
                        } else {
                            $fourLocWithDistanceData['query_filters']['item']['location'] = $fourLocationsDetail['town_id'];
                        }
                    }
                    //$fourLocWithDistanceData['query_sorter']['item']['geodist'] = array('sort_ord' => 'asc', 'field_ord' => 1);
                    $this->get('fa.solrsearch.manager')->init('ad', $keywords, $fourLocWithDistanceData);
                    $fourLocWithDistanceSolrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();
                    $fourLocWithDistanceResultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($fourLocWithDistanceSolrResponse);
                    if ($fourLocationCount==4) {
                        break;
                    }
                    if ($fourLocWithDistanceResultCount>0) {
                        $locationUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($data['search'], array('item__location' => $fourLocationsDetail['town_id'])));
                        $fourLocationsDetailsWithDistance[] = array(
                            'id' => $fourLocationsDetail['town_id'],
                            'locationUrl' => $locationUrl,
                            'distance' => $fourLocationsDetail['distance'],
                            'name' => $fourLocationsDetail['name'],
                            'cnt'=> $fourLocWithDistanceResultCount,
                        );
                        $fourLocationCount++;
                    }
                }
            }
        }

        $parameters = array(
            'pagination'             => $pagination,
            'resultCount'            => $resultCount,
            'locationFacets'         => $locationFacets,
            'searchParams'           => $findersSearchParams,
            'keywords'               => $keywords,
            'facetResult'            => $facetResult,
            'topAdResult'            => $this->getSearchResultTopAds($data, $keywords, $page, $mapFlag, $request, $rootCategoryId),
            'searchAgentData'        => array('sorter' => $data['sorter'], 'search' => $data['search']),
            'cookieLocationDetails'  => $cookieLocationDetails,
            //'expandMilesresultCount' => $expandMilesresultCount,
            //'nextMiles'              => $nextMiles,
            'objSeoToolOverride'     => $objSeoToolOverride,
            'rootCategoryId'         => $rootCategoryId,
            'userLiveBasicAd'        => $this->getUserLiveBasicAd($data, null, $page, $mapFlag, $request, $rootCategoryId),
            'successPaymentModalbox' => $successPaymentModalbox,
            'areaToolTipFlag'		 => $areaToolTipFlag,
            'recommendedSlotResult'  => $getRecommendedSrchSlotWise,
            'paymentTransactionJs'	 => $transactionJsArr,
            'mapResult'              => $mapResult,
            'extendedRadius'         => $extendRadius,
            'extendedResultCount'    => $extendedResultCount,
            'fourLocationsDetails'   => $fourLocationsDetailsWithDistance,
            'defaultPages'           => $defaultRadiusPageCount,
            'getXMiles'              => $getXMiles,
            'isClassicCarPage'      => $isClassicCarPage,
        );

        // for business user ads page
        if ($currentRoute == 'show_business_user_ads' || $currentRoute == "show_business_user_ads_location" || $currentRoute == "show_business_user_ads_page") {
            $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('slug' => $request->get('profileNameSlug')));
            if (!$userSite || ($userSite && $userSite->getSlug() != $request->get('profileNameSlug'))) {
                throw new HttpException(410);
            } else {
                $user              = $userSite->getUser();
                $userId            = $user->getId();
                $userDetail        = $this->getRepository('FaUserBundle:User')->getBusinessUserProfileDetail($userId, true, true, $this->container);
                if (!$userDetail['id']) {
                    throw new HttpException(410);
                }
                $activeShopPackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);

                if ($activeShopPackage && $activeShopPackage->getPackage() && $activeShopPackage->getPackage()->getPackageText() == PackageRepository::SHP_PACKAGE_BASIC_TEXT) {
                    $parameters = array(
                        'userDetail' => $userDetail,
                        'pagination'      => $pagination,
                    );

                    return $this->render('FaContentBundle:ProfilePage:showUserAds.html.twig', $parameters);
                } elseif ($activeShopPackage && $activeShopPackage->getPackage() && $activeShopPackage->getPackage()->getPackageText() != PackageRepository::SHP_PACKAGE_BASIC_TEXT) {
                    $parameters = array(
                        'userDetail' => $userDetail,
                        'pagination'      => $pagination,
                        'searchParams'    => $findersSearchParams,
                        'facetResult'     => $facetResult,
                        'locationFacets'  => $locationFacets,
                        'cookieLocationDetails' => $cookieLocationDetails,
                    );

                    return $this->render('FaContentBundle:ProfilePage:showShopUserAds.html.twig', $parameters);
                } else {
                    return $this->handleMessage($this->get('translator')->trans('User does not have any package assigned.', array(), 'frontend-profile-page'), 'fa_frontend_homepage', array(), 'error');
                }
            }
        }

        if ($resultCount && !in_array($currentRoute, array('show_business_user_ads', 'show_business_user_ads_location', 'show_business_user_ads_page'))) {
            // profile categories other than Services & Adults
            if (!in_array($rootCategoryId, array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                $shopParameters = $this->getShopUserBySearchCriteria($data, $request);
                if (!empty($shopParameters)) {
                    $parameters = $parameters + $shopParameters;
                }
            }
            // profile categories Services & Adults
            if ($rootCategoryId) {
                if (in_array($rootCategoryId, array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                    $shopBusinessParameters = $this->getBusinessUserBySearchCriteria($data, $request);
                    if (!empty($shopBusinessParameters)) {
                        $parameters = $parameters + $shopBusinessParameters;
                    }
                }
            }
        }


        $templateName = 'searchResult';
        if ($mapFlag) {
            $templateName = 'mapSearchResult';
        }

        $objResponse = null;
        if (isset($rootCategoryId) && $rootCategoryId == CategoryRepository::ADULT_ID) {
            $objResponse = CommonManager::setCacheControlHeaders();
        }
        return $this->render('FaAdBundle:AdList:'.$templateName.'.html.twig', $parameters, $objResponse);
    }

    /**
     * Set default parameters for sorting and paging for different listing view.
     *
     * @param Request $request               Request object.
     * @param boolean $mapFlag               Boolean flag for map.
     * @param string  $searchType            Left or top search.
     * @param array   $cookieLocationDetails Location cookie value.
     *
     * @return array
     */
    public function setDefaultParametersNew($request, $mapFlag, $searchType, $cookieLocationDetails = array())
    {
        $currentRoute      = $request->get('_route');
        $hasSortField      = true;
        $parentCategoryIds = array();
        // set default sorting for list view
        if (!$request->get('sort_field') && !$mapFlag) {
            $request->request->set('sort_field', 'item__weekly_refresh_published_at');
            $request->request->set('sort_ord', 'desc');
            $hasSortField = false;
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'finders');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // add published at as second sort
        if (!$mapFlag) {
            if ($request->get('sort_field') == 'item__weekly_refresh_published_at') {
                unset($data['query_sorter']['item']['weekly_refresh_published_at']);
            }

            $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
            $data['query_sorter']['item']['boosted_at'] = array('sort_ord' => 'desc', 'field_ord' => 2);

            if (isset($data['search']['item__category_id']) && $data['search']['item__category_id']) {
                $parentCategoryIds = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($data['search']['item__category_id'], false, $this->container));
            }

            if (!$hasSortField && isset($parentCategoryIds[1]) && $parentCategoryIds[1] == CategoryRepository::WHATS_ON_ID) {
                $data['query_sorter']['ad_community']['event_start'] = array('sort_ord' => 'asc', 'field_ord' => 2);
            } else {
                if ((!isset($data['search']['item__distance']) || (isset($data['search']['item__distance']) && $data['search']['item__distance'] >= 0 && $data['search']['item__distance'] <= CategoryRepository::MAX_DISTANCE))) {
                    if (is_array($cookieLocationDetails) &&
                        (!isset($cookieLocationDetails['latitude']) || !$cookieLocationDetails['latitude']) &&
                        (!isset($cookieLocationDetails['longitude']) || !$cookieLocationDetails['longitude']) &&
                        (isset($data['query_sorter']['item']) && isset($data['query_sorter']['item']['geodist']))) {
                        unset($data['query_sorter']['item']['geodist']);
                    }
                }
                if (isset($data['search']['keywords']) && strlen(trim($data['search']['keywords']))) {
                    $data['query_sorter']['item']['score'] = array('sort_ord' => 'desc', 'field_ord' => 5);
                }

                $data['query_sorter']['item']['created_at'] = array('sort_ord' => 'desc', 'field_ord' => 3);
            }
            $data['query_sorter']['item']['is_topad'] = array('sort_ord' => 'desc', 'field_ord' => 4);
        }

        //set default sorting for map
        if ($mapFlag) {
            unset($data['query_sorter']);

            $data['query_sorter']['item']['weekly_refresh_published_at'] = 'desc';
            $data['sorter']['sort_field'] = 'item__weekly_refresh_published_at';
            $data['sorter']['sort_ord'] = 'desc';
            $data['pager']['page']  = 1;
            $data['pager']['limit'] = $this->container->getParameter('fa.search.map.records.per.page');
            $data['select_fields'] = array('item' => array('id', 'title', 'latitude', 'longitude'));
        } else {
            $numberOfRecords = $this->getRepository('FaCoreBundle:ConfigRule')->getNumberOfOrganicResult($this->container);
            if ($numberOfRecords) {
                $data['pager']['limit'] = $numberOfRecords;
            } else {
                $data['pager']['limit'] = $this->container->getParameter('fa.search.records.per.page');
            }
        }

        // Active or expired ads
        if (isset($data['search']['expired_ads']) && $data['search']['expired_ads']) {
            $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_EXPIRED_ID;
        } else {
            $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        }

        // ads with min 1 photo
        if (isset($data['search']['items_with_photo']) && $data['search']['items_with_photo']) {
            $data['query_filters']['item']['image_count'] = '1|';
        }

        $data['facet_fields'] = array(
            'category_ids'          => array('min_count' => 1),
            'is_trade_ad'           => array('min_count' => 0),
            'image_count'           => array('min_count' => 1),
            'town'                  => array('min_count' => 1),
            'area'                  => array('min_count' => 1)
        );

        // ad location filter with distance
        if (isset($data['search']['item__location']) && $data['search']['item__location']) {
            if ($data['search']['item__location'] == 2) {
                $data['facet_fields']['town'] = array('min_count' => 1, 'limit' => 10);
            }

            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.((isset($data['search']['item__distance']) ? $data['search']['item__distance'] : ''));
        }
        $data['query_filters']['item']['is_blocked_ad'] = 0;
        // remove adult results when there is no category selected
        if (!isset($data['search']['item__category_id']) && !in_array($currentRoute, array('show_business_user_ads', 'show_business_user_ads_page', 'show_business_user_ads_location','fa_adult_homepage'))) {
            $data['static_filters'] = ' AND -category_ids:'.CategoryRepository::ADULT_ID;
        }

        $dataSearch = $data['search'];
        unset($dataSearch['item__category_id']);
        unset($dataSearch['item__location']);

        foreach ($dataSearch as $searchKey => $searchItem) {
            if (! empty($searchItem)) {
                if ($dimension = $this->getDimensionName($searchKey)) {
                    $data['query_filters']['item'][$dimension] = $searchItem;
                }
            }
        }

        return $data;
    }

    /**
     * @param $searchKey
     * @return bool|mixed
     */
    private function getDimensionName($searchKey)
    {
        if (strpos($searchKey, '__')) {
            $key = explode('__', $searchKey);
            return str_replace('_id', '', $key[1]);
        } else if (strpos($searchKey, '?') === false) {
            return $searchKey;
        } else {
            return false;
        }
    }

    /**
     * Top search result.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function searchResultNewAction(Request $request)
    {
        $mapFlag                = $request->get('map', false);
        $currentRoute           = $request->get('_route');
        $requestlocation        = $request->get('location');
        $findersSearchParams    = $request->get('finders');
        // set location in cookie
        $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
        if (!$cookieLocationDetails) {
            $cookieLocationDetails = array();
        } else {
            $cookieLocationDetails['location'] = intval($cookieLocationDetails['location']);
        }
        if (!in_array($currentRoute, array('show_business_user_ads', 'show_business_user_ads_page', 'show_business_user_ads_location'))) {
            if (preg_match("/[A-Z]/", $request->getPathInfo())) {
                $url = str_replace($request->getPathInfo(), strtolower($request->getPathInfo()), $request->getUri());
                return $this->redirect($url, 301);
            }

            $cookieValue = '';

            if ($requestlocation != null && $requestlocation != 'uk' && (!isset($cookieLocationDetails['slug']) || $cookieLocationDetails['slug'] != $requestlocation || $requestlocation == LocationRepository::LONDON_TXT)) {
                $cookieValue = $this->getRepository('FaEntityBundle:Location')->getCookieValue($requestlocation, $this->container, true);

                if (count($cookieValue) && count($cookieValue) !== count(array_intersect($cookieValue, $cookieLocationDetails))) {
                    $response = new Response();
                    $cookieValue = json_encode($cookieValue);
                    $response->headers->clearCookie('location');
                    $response->headers->setCookie(new Cookie('location', $cookieValue, time() + (365*24*60*60*1000), '/', null, false, false));
                    $response->sendHeaders();
                } else {
                    $cookieValue = json_encode($cookieValue);
                }
            } elseif ($requestlocation != null && $requestlocation == 'uk') {
                $response = new Response();
                $response->headers->clearCookie('location');
                $response->sendHeaders();

                $cookieValue = json_encode(array(
                    'location' => LocationRepository::COUNTY_ID,
                    'slug'     => 'uk',
                    'location_text' => 'United Kingdom',
                ));
            }
        }

        // get location from cookie
        if (isset($cookieValue) && $cookieValue) {
            if (is_array($cookieValue)) {
                $cookieValue = json_encode($cookieValue);
            }
            $cookieLocationDetails = json_decode($cookieValue, true);
        } else {
            $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
        }

        $data = $this->setDefaultParametersNew($request, $mapFlag, 'finders', $cookieLocationDetails);

        $setDefaultRadius = false;
        if (isset($findersSearchParams['setDefaultRadius'])) {
            $setDefaultRadius = $findersSearchParams['setDefaultRadius'];
            unset($findersSearchParams['setDefaultRadius']);
            unset($data['search']['setDefaultRadius']);
        }

        if (empty($findersSearchParams['item__category_id'])) {
            $findersSearchParams['item__category_id'] = 1;
        }
        $category = $this->getRepository('FaEntityBundle:Category')->findOneBy(['id' => $findersSearchParams['item__category_id']]);

        if (empty($data['static_filters'])) {
            $data['static_filters'] = '';
        }
        if ($findersSearchParams['item__category_id'] != 1) {
            $data['static_filters'] .= ' AND category_full_path:' . $category->getFullSlug();
        }

        $page = $data['pager']['page'];

        $static_filters = '';
        $searchableDimensions = [];
        $root = $category;

        $listingFields = array();
        $adRepository = $this->getRepository('FaAdBundle:Ad');
        if ($findersSearchParams['item__category_id'] != 1) {
            $searchableDimensions = $this->getRepository('FaEntityBundle:CategoryDimension')->getSearchableDimesionsArrayByCategoryId($category->getId(), $this->container);


            $root = $this->getRepository('FaEntityBundle:Category')->getRootNodeByCategory($category->getId());
            $repository = $this->getRepository('FaAdBundle:' . 'Ad' . str_replace(' ', '', $root->getName()));
            $listingFields = $repository->getAdListingFields();

            foreach ($searchableDimensions as $key => $dimension) {
                $nameInLowerCase = str_replace([' ', '.'], ['_', ''], strtolower($dimension['name']));
                if ($nameInLowerCase == 'ad_type') {
                    $solrDimensionFieldName = 'type_id';
                } else {
                    $solrDimensionFieldName = $adRepository->getSolrFieldName($listingFields, $nameInLowerCase);
                }
                $data['facet_fields'] = array_merge(
                    $data['facet_fields'],
                    [
                        $solrDimensionFieldName => array('min_count' => 0)
                    ]
                );

                $searchableDimensions[$key]['solr_field'] = $solrDimensionFieldName;
                $searchableDimensions[$key]['dim_slug']   = $nameInLowerCase;
            }

        }
        $data['static_filters'] .= $static_filters = $this->setDimensionParams($data['search'], $listingFields, $adRepository);

        $keywords       = (isset($data['search']['keywords']) && $data['search']['keywords']) ? $data['search']['keywords']: NULL;
        $recordsPerPage = (isset($data['pager']['limit']) && $data['pager']['limit']) ? $data['pager']['limit']: $this->container->getParameter('fa.search.records.per.page');

        $getDefaultRadius = $this->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($data['search'], $this->container);
        $radius = ($getDefaultRadius)?$getDefaultRadius:CategoryRepository::MAX_DISTANCE;

        if($data['search']['item__location']!=LocationRepository::COUNTY_ID) {
            $data['search']['item__distance'] = $radius;
            $data['query_filters']['item']['distance']= $radius;
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.$radius;
        }


        /* elseif($keywords) {
            $data['search']['item__distance'] = CategoryRepository::KEYWORD_DEFAULT;
            $data['query_filters']['item']['distance']= CategoryRepository::KEYWORD_DEFAULT;
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.CategoryRepository::KEYWORD_DEFAULT;;
        } */

        $this->get('fa.solrsearch.manager')->init('ad.new', $keywords, $data, $page, $recordsPerPage, 0, true);
        $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();

        // fetch result set from solr
        $result      = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
        $resultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);

        $defaultRadiusPageCount = ($page==0)?1:ceil($resultCount/$recordsPerPage);
        $defaultRadiusLastPageCount = ($resultCount%$recordsPerPage);

        $facetResult = get_object_vars($facetResult);

        //$pagination = $this->getSolrResult('ad.new', $keywords, $data, $page, $recordsPerPage, 0, true);

        if ($page == 1) {
            $featuredData = $this->setDefaultParametersNew($request, $mapFlag, 'finders', array());

            if (empty($featuredData['static_filters'])) {
                $featuredData['static_filters'] = '';
            }
            if ($findersSearchParams['item__category_id'] != 1) {
                $featuredData['static_filters'] .= ' AND category_full_path:' . $category->getFullSlug();
            }
            $featuredData['static_filters'] .= ' AND is_topad:true';
            $featuredData['static_filters'] .= $static_filters;

            $topAds = [];
            $viewedTopAdsCookie = $request->cookies->get('viewed_top_ads_'.$root->getId());
            if ($viewedTopAdsCookie) {
                $topAds = explode(',', $viewedTopAdsCookie);
                $featuredData['static_filters'] .= ' AND -id : ('.implode(' ', $topAds).')';
            }

            $keywords = (isset($featuredData['search']['keywords']) && $featuredData['search']['keywords']) ? $featuredData['search']['keywords'] : NULL;
            $page = (isset($featuredData['pager']['page']) && $featuredData['pager']['page']) ? $featuredData['pager']['page'] : 1;

            $featuredPagination = $this->getSolrResult('ad.new', $keywords, $featuredData, 1, 3, 0, true, true);

            if (count($featuredPagination['pagination']) < 3) {
                $topAds = [];
                $featuredData = $this->setDefaultParametersNew($request, $mapFlag, 'finders', array());

                if (empty($featuredData['static_filters'])) {
                    $featuredData['static_filters'] = '';
                }
                if ($findersSearchParams['item__category_id'] != 1) {
                    $featuredData['static_filters'] .= ' AND category_full_path:' . $category->getFullSlug();
                }
                $featuredData['static_filters'] .= ' AND is_topad:true';
                $featuredData['static_filters'] .= $static_filters;

                $keywords = (isset($featuredData['search']['keywords']) && $featuredData['search']['keywords']) ? $featuredData['search']['keywords'] : NULL;
                $page = (isset($featuredData['pager']['page']) && $featuredData['pager']['page']) ? $featuredData['pager']['page'] : 1;

                $featuredPagination = $this->getSolrResult('ad.new', $keywords, $featuredData, 1, 3, 0, true, true);
            }

            $featuredAds = $this->formatAds($featuredPagination['pagination']);
            $viewedTopAds = array_column($featuredAds, 'ad_id');
            $viewedTopAds = array_unique(array_merge($viewedTopAds, $topAds));
            $response = new Response();
            $response->headers->setCookie(new Cookie('viewed_top_ads_'.$root->getId(), implode(',', $viewedTopAds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
            $response->sendHeaders();
        } else {
            $featuredAds = [];
        }


        $getRecommendedSrchSlotWise = array();
        $getRecommendedSrchSlots=array();
        if (! empty($category)) {
            $data['search']['item__category_id'] = $category->getId();
            if ($category->getId() != 1) {
                $categoryParent = $category->getParent()->getId();
            } else {
                $categoryParent = 1;
            }
            $getRecommendedSlots = $this->getRecommendedSlot($data, $keywords, $page, $mapFlag, $request, $categoryParent);
        } else {
            $getRecommendedSlots = NULL;
        }

        if (!empty($getRecommendedSlots)) {
            foreach ($getRecommendedSlots as $getRecommendedSlot) {
                $getRecommendedSrchSlots[$getRecommendedSlot['creative_group']][] = $getRecommendedSlot;
            }
        }
        $recommendedSlotArr = array();
        $recommendedSlotOrder = array();
        if (! $mapFlag) {
            if (!empty($getRecommendedSrchSlots)) {
                for ($arj = 1; $arj <= 8; $arj++) {
                    if (isset($getRecommendedSrchSlots[$arj])) {
                        $recommendedSlotArr[$arj] = $getRecommendedSrchSlots[$arj][0];
                        $recommendedSlotOrder[$arj] = $getRecommendedSrchSlots[$arj][0]['creative_ord'];
                        if (isset($_COOKIE['recommended_slot_' . $arj])) {
                            if (isset($getRecommendedSrchSlots[$arj][1]) && $_COOKIE['recommended_slot_' . $arj] == $getRecommendedSrchSlots[$arj][0]['creative_ord']) {
                                $recommendedSlotArr[$arj] = $getRecommendedSrchSlots[$arj][1];
                                $recommendedSlotOrder[$arj] = $getRecommendedSrchSlots[$arj][1]['creative_ord'];
                            } elseif (isset($getRecommendedSrchSlots[$arj][2])) {
                                if ($_COOKIE['recommended_slot_' . $arj] == $getRecommendedSrchSlots[$arj][1]['creative_ord'] || $_COOKIE['recommended_slot_' . $arj] == $getRecommendedSrchSlots[$arj][0]['creative_ord']) {
                                    $recommendedSlotArr[$arj] = $getRecommendedSrchSlots[$arj][2];
                                    $recommendedSlotOrder[$arj] = $getRecommendedSrchSlots[$arj][2]['creative_ord'];
                                } elseif ($_COOKIE['recommended_slot_' . $arj] == $getRecommendedSrchSlots[$arj][2]) {
                                    $recommendedSlotArr[$arj] = $getRecommendedSrchSlots[$arj][0];
                                    $recommendedSlotOrder[$arj] = $getRecommendedSrchSlots[$arj][0]['creative_ord'];
                                }
                            }
                        }
                        setcookie('recommended_slot_' . $arj, $recommendedSlotOrder[$arj]);
                    }
                }
            }

            if (!empty($recommendedSlotArr)) {
                $getRecommendedSrchSlotWise = $recommendedSlotArr;
            }
        }

        if (! ($user = $this->getLoggedInUser())) {
            $userId = $request->getSession()->getId();
        } else {
            $userId = $user->getId(); 
        }
        $adFavouriteIds = $this->getRepository('FaAdBundle:AdFavorite')->getFavoriteAdByUserId($userId, $this->container);

        //$resultCount = $pagination['resultCount'];
        $defaultRadiusPageCount = ($page==0)?1:ceil($resultCount/$recordsPerPage);
        $defaultRadiusLastPageCount = ($resultCount%$recordsPerPage);

        $setDefRadius = 1;
        $isBusinessPage = 0;
        if (($currentRoute == 'show_business_user_ads' || $currentRoute == "show_business_user_ads_location" || $currentRoute == "show_business_user_ads_page")) {
            $isBusinessPage = 1;
            $setDefRadius = 0;
        }

        if (isset($data['search']) && isset($data['search']['item__distance']) && ! $setDefaultRadius) {
            $setDefRadius = 0;
        }
        if (($requestlocation != null && $requestlocation == 'uk') || (isset($cookieLocationDetails['lvl']) && $cookieLocationDetails['lvl']=='') || (!isset($cookieLocationDetails['lvl']))) {
            $setDefRadius = 0;
        }

        $areaToolTipFlag = false;
        if (!$request->cookies->has('frontend_area_alert_tooltip') && $requestlocation != null && strtolower($requestlocation) == LocationRepository::LONDON_TXT) {
            $response = new Response();
            $response->headers->setCookie(new Cookie('frontend_area_alert_tooltip', $requestlocation, time() + (365*24*60*60*1000), '/', null, false, false));
            $response->sendHeaders();
            $areaToolTipFlag = true;
        }

        $extendRadius = '';
        $extendlocation = '';
        if ($setDefRadius && $isBusinessPage==0) {
            if (isset($findersSearchParams['item__category_id']) && $findersSearchParams['item__category_id']) {
                $parentCategoryIds = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($findersSearchParams['item__category_id'], false, $this->container));
                $locationRadius = $this->getRepository('FaAdBundle:LocationRadius')->getSingleLocationRadiusByCategoryIds($parentCategoryIds);
                if ($locationRadius) {
                    $findersSearchParams['item__distance'] = $data['search']['item__distance'] = $data['query_filters']['item']['distance'] = $locationRadius['defaultRadius'];
                    $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'. (isset($data['search']['item__distance']) ? $data['search']['item__distance'] : '');
                    if ($locationRadius['extendedRadius']>0 && $locationRadius['extendedRadius'] > $locationRadius['defaultRadius']) {
                        $extendlocation = $data['search']['item__location'].'|'. (isset($locationRadius['extendedRadius']) ? $locationRadius['extendedRadius']: '').'|'. (isset($data['search']['item__distance']) ? $data['search']['item__distance'] : '');
                        $extendRadius = (isset($locationRadius['extendedRadius']) ? $locationRadius['extendedRadius']: '');
                    }
                }

                if (!isset($data['search']['item__distance'])) {
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($findersSearchParams['item__category_id'], $this->container);
                    $findersSearchParams['item__distance'] = $data['search']['item__distance'] = ($rootCategoryId==CategoryRepository::MOTORS_ID)?CategoryRepository::MOTORS_DISTANCE:CategoryRepository::OTHERS_DISTANCE;
                    $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'. ((isset($data['search']['item__distance']) ? $data['search']['item__distance'] : ''));
                }
            }
        }

        if (!empty($extendRadius) && $data['search']['item__location']== LocationRepository::LONDON_TOWN_ID) {
            $extendRadius =  CategoryRepository::MAX_DISTANCE;
        } elseif ($keywords && isset($data['search']['item__location']) && $data['search']['item__location']!=2) {
            $extendRadius = CategoryRepository::KEYWORD_EXTENDED;
        }

        if ($request->attributes->get('customized_page')) {
            list($keywords, $data) = $this->handleCustomizedUrl($data, $request);
        }

        $extendedData = $data;

        if ($extendlocation) {
            $extendedData['query_filters']['item']['location'] = $extendlocation;
        }

        $extendedResultCount = 0;
        //$facetResult = $pagination['facetResult'];
        $extendedFacetResult = [];
        //$mergedResultCount = $resultCount;
        //$mergedresult = $pagination['result'];

        $extendedResult = array();
        $extpage =0;
        $staticOffset = 0;

        if ($extendlocation) {
            // initialize solr search manager service and fetch data based of above prepared search options
            if ($page == $defaultRadiusPageCount) {
                $extpage = 1;
                $staticOffset = 0;
            } elseif ($page > $defaultRadiusPageCount && $defaultRadiusPageCount>0 && $page>0) {
                $extpagediff = $page - $defaultRadiusPageCount;
                if ($extpagediff<=0) {
                    $extpage = 1;
                } else {
                    $extpage = $extpagediff;
                }
                $staticOffset = ($extpagediff>0)?((($extpage)*$recordsPerPage) - $defaultRadiusLastPageCount):0;
            } elseif ($page > $defaultRadiusPageCount && $page>0) {
                $extpagediff = $page;
                if ($extpagediff<=0) {
                    $extpage = 1;
                } else {
                    $extpage = $extpagediff;
                }
                $staticOffset = ($extpagediff>0)?((($extpage-1)*$recordsPerPage) - $defaultRadiusLastPageCount):0;
            } else {
                $extpage =1;
                $staticOffset = 0;
            }
            $this->get('fa.solrsearch.manager')->init('ad.new', $keywords, $extendedData, $extpage, $recordsPerPage, $staticOffset, true);
            $extendedSolrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();
            $extendedResult      = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($extendedSolrResponse);
            $extendedResultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($extendedSolrResponse);
        }

        $mergedresult = array_merge($result, $extendedResult);
        $mergedResultCount = $resultCount + $extendedResultCount;

        $this->get('fa.pagination.manager')->init($mergedresult, $page, $recordsPerPage, $mergedResultCount);
        $pagination = $this->get('fa.pagination.manager')->getSolrPagination();

        $mergedAds = $this->formatAds($pagination);

        /*if (! empty($extendRadius)) {
            // initialize solr search manager service and fetch data based of above prepared search options
            if ($page == $defaultRadiusPageCount) {
                $extpage = 1;
                $staticOffset = 0;
            } elseif ($page > $defaultRadiusPageCount && $defaultRadiusPageCount>0 && $page>0) {
                $extpagediff = $page - $defaultRadiusPageCount;
                if ($extpagediff<=0) {
                    $extpage = 1;
                } else {
                    $extpage = $extpagediff;
                }
                $staticOffset = ($extpagediff>0)?((($extpage)*$recordsPerPage) - $defaultRadiusLastPageCount):0;
            } elseif ($page > $defaultRadiusPageCount && $page>0) {
                $extpagediff = $page;
                if ($extpagediff<=0) {
                    $extpage = 1;
                } else {
                    $extpage = $extpagediff;
                }
                $staticOffset = ($extpagediff>0)?((($extpage-1)*$recordsPerPage) - $defaultRadiusLastPageCount):0;
            } else {
                $extpage =1;
                $staticOffset = 0;
            }

            $extendedResult = $this->getExtendedPagination($extendRadius, $keywords, $extendedData, $extpage, $recordsPerPage, $staticOffset, $findersSearchParams);

            $extendedResultCount = $extendedResult['resultCount'];

            $mergedresult = array_merge($pagination['result'], $extendedResult['result']);
            $mergedResultCount = $resultCount + $extendedResultCount;

            $extendedFacetResult = $extendedResult['facetResult'];
        }
        $this->get('fa.pagination.manager')->init($mergedresult, $page, $recordsPerPage, $mergedResultCount);
        $mergedPagination = $this->get('fa.pagination.manager')->getSolrPagination();

        $mergedAds = $this->formatAds($mergedPagination);*/


        if ($findersSearchParams['item__category_id'] == 1) {
            if(!isset($findersSearchParams['item__distance'])){
                if($findersSearchParams['item__location'] == LocationRepository::LONDON_TOWN_ID) {
                    $findersSearchParams['item__distance'] = CategoryRepository::LONDON_DISTANCE;
                } elseif($keywords && isset($data['search']['item__location']) && $data['search']['item__location']!=2) {
                    $findersSearchParams['item__distance'] = CategoryRepository::KEYWORD_DEFAULT;
                } else {
                    $findersSearchParams['item__distance'] = CategoryRepository::MAX_DISTANCE;
                    $findersSearchParams['default_distance'] = true;
                }

            }
        }

        $parameters = [
            'featuredAds'           => $featuredAds,
            'ads'                   => $mergedAds,
            'resultCount'           => $resultCount,
            'recommendedSlotResult' => $getRecommendedSrchSlotWise,
            'recommendedSlotLimit'  => $this->getRepository('FaCoreBundle:Config')->getSponsoredLimit(),
            'pagination'            => $pagination,
            'adFavouriteIds'        => $adFavouriteIds,
            'leftFilters'           => $this->getLeftFilters($category, $root, $facetResult, $resultCount, $searchableDimensions, $findersSearchParams, $data, $extendedFacetResult),
            'currentLocation'       => $requestlocation,
            'searchParams'          => $findersSearchParams,
            'cookieLocationDetails' => $cookieLocationDetails,
            'keywords'              => $keywords,
            'extendedRadius'        => $extendRadius,
            'extendedResultCount'   => $extendedResultCount,
            'facetResult'           => $facetResult,
            'rootCategoryId'        => $root->getId(),
            'areaToolTipFlag'       => $areaToolTipFlag,
            'searchAgentData'        => array('sorter' => $data['sorter'], 'search' => $data['search']),
         ];

        // profile categories other than Services & Adults
        if (!in_array($root->getId(), array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
            $shopParameters = $this->getShopUserBySearchCriteriaNew($data, $request);
            if (!empty($shopParameters)) {
                $parameters = $parameters + $shopParameters;
            }
        }
        // profile categories Services & Adults
        if ($root->getId()) {
            if (in_array($root->getId(), array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                $shopBusinessParameters = $this->getBusinessUserBySearchCriteriaNew($data, $request);
                if (!empty($shopBusinessParameters)) {
                    $parameters = $parameters + $shopBusinessParameters;
                }
            }
        }

        if (isset($findersSearchParams['item__category_id'])) {
            $parameters['selCatDet'] = $this->getRepository('FaEntityBundle:Category')->getCategoryArrayById($findersSearchParams['item__category_id']);
            if (isset($parameters['selCatDet'])) {
                $parameters['selCatName']       = $parameters['selCatDet']['name'];
                $parameters['selCatSlug']       = $parameters['selCatDet']['slug'];
                $parameters['selCatNoIndex']    = $parameters['selCatDet']['no_index'];
                $parameters['selCatNoFollow']   = $parameters['selCatDet']['no_follow'];
                $parameters['selNoIndex']       = $parameters['selCatNoIndex'] == 1 ? 'noindex' : 'index';
                $parameters['selNoFollow']      = $parameters['selCatNoFollow'] == 1 ? 'nofollow' : 'follow';

                $parameters['customized_url'] = $request->get('customized_page');
                if (empty($parameters['customized_url'])) {
                    $targetUrl = $findersSearchParams['item__category_id'];
                } else {
                    $targetUrl = $parameters['customized_url']['target_url'];
                }

                $seoToolRepository = $this->getRepository('FaContentBundle:SeoTool');
                $seoPageRule = $seoToolRepository->getSeoPageRuleDetailForListResult(SeoToolRepository::ADVERT_LIST_PAGE, $targetUrl, $this->container);

                if (empty($seoPageRule)) {
                    $seoPageRule = $seoToolRepository->getSeoPageRuleDetailForListResult(SeoToolRepository::ADVERT_LIST_PAGE, null, $this->container);
                }

                $pageUrl = $this->getPageUrl($request);
                $findersSearchParams = $request->get('finders');
                if (isset($findersSearchParams['advertgone'])) {
                    unset($findersSearchParams['advertgone']);
                }
                $objSeoToolOverride = null;
                if ($pageUrl) {
                    $objSeoToolOverride = $this->getRepository('FaContentBundle:SeoToolOverride')->findSeoRuleByPageUrl($pageUrl, $findersSearchParams, $this->container);
                }

                //get SEO Source URL for classic-car
                if (strpos($pageUrl, 'motors/classic-cars') !== false && !$request->query->has('item_motors__reg_year')) {
                    $getClassicCarRegYear = $this->getRepository('FaContentBundle:SeoTool')->findSeoSourceUrlMotorRegYear('motors/classic-cars/');
                    if (!empty($getClassicCarRegYear)) {
                        $findersSearchParams['item_motors__reg_year'] = $getClassicCarRegYear;
                    }
                }

                $isClassicCarPage = 0;
                if (strpos($pageUrl, 'motors/cars') !== false && isset($findersSearchParams['item_motors__reg_year'])) {
                    $allUnder25Yrs = 1;
                    $get25ysrOlder = date('Y') - 24;

                    foreach ($findersSearchParams['item_motors__reg_year'] as $srchRegYr) {
                        if ($srchRegYr > $get25ysrOlder) {
                            $allUnder25Yrs = 0;
                            break;
                        }
                    }

                    if ($allUnder25Yrs==1) {
                        $isClassicCarPage = 1;
                    }
                }

                if ($objSeoToolOverride) {
                    if ($isClassicCarPage) {
                        $selCatName = $parameters['selCatName'] != 'Cars' ? $parameters['selCatName'] : '';
                        $seoPageRule += ['h1_tag' => str_replace('Manufacturer', $selCatName, $objSeoToolOverride->getH1Tag()), 'page_title' => str_replace('Manufacturer', $selCatName, $objSeoToolOverride->getPageTitle()), 'meta_description'=> str_replace('Manufacturer', $selCatName, $objSeoToolOverride->getMetaDescription()), 'no_index'=> str_replace('Manufacturer', $selCatName, $objSeoToolOverride->getNoIndex()), 'no_follow'=> str_replace('Manufacturer', $selCatName, $objSeoToolOverride->getNoFollow()), 'canonical_url'=> str_replace('Manufacturer', $selCatName, $objSeoToolOverride->getCanonicalUrl())];
                    } else {
                        $seoPageRule += ['h1_tag' => $objSeoToolOverride->getH1Tag(), 'page_title' => $objSeoToolOverride->getPageTitle(), 'meta_description'=> $objSeoToolOverride->getMetaDescription(), 'no_index'=> $objSeoToolOverride->getNoIndex(), 'no_follow'=> $objSeoToolOverride->getNoFollow(), 'canonical_url'=> $objSeoToolOverride->getCanonicalUrl()];
                    }
                }

                if ($seoPageRule) {
                    $parameters['seoFields'] = CommonManager::getSeoFields($seoPageRule);
                }

                if ($request->get('queryString') or strpos($request->get('uri'), '/search')) {
                    $parameters['isUrlIndexable'] = false;
                } else {
                    $parameters['isUrlIndexable'] = $this->getRepository('FaEntityBundle:CategoryDimension')->isUrlIndexableBySearchParams($findersSearchParams, $this->container);
                }
            }
        }

        if ($resultCount && !in_array($currentRoute, array('show_business_user_ads', 'show_business_user_ads_location', 'show_business_user_ads_page'))) {
            // profile categories other than Services & Adults
            if (!in_array($root->getId(), array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                $shopParameters = $this->getShopUserBySearchCriteriaNew($data, $request);
                if (!empty($shopParameters)) {
                    $parameters = $parameters + $shopParameters;
                }
            }
            // profile categories Services & Adults
            if ($root) {
                if (in_array($root->getId(), array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                    $shopBusinessParameters = $this->getBusinessUserBySearchCriteria($data, $request);
                    if (!empty($shopBusinessParameters)) {
                        $parameters = $parameters + $shopBusinessParameters;
                    }
                }
            }
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserHalfAccountEmailOnlyType::class, null, array('method' => 'POST'));

        if ($request->isXmlHttpRequest() && 'POST' === $request->getMethod() && $request->get('is_form_load', null) == null) {
            if ($formManager->isValid($form)) {
                $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData()));
                return new JsonResponse(array('success' => '1', 'user_id' => $user->getId()));
            } else {
                return new JsonResponse(array('success' => '', 'user_id' => '', 'errorMessage' => 'Please enter valid email address.'));
            }
        }

        $isShopPage = 1;
        $parentIdArray = [];
        $form1 = $formManager->createForm(AdLeftSearchNewType::class, array('isShopPage' => $isShopPage, 'parentIdArray' => $parentIdArray, 'searchParams' => $findersSearchParams), array('method' => 'GET', 'action' => ($isShopPage ? $this->generateUrl('shop_user_ad_left_search_result') : $this->generateUrl('ad_left_search_result'))));

        $parameters['createAlertBlock'] = array('form' => $form->createView());
        $parameters['leftFilters']['form'] = $form1->createView();

        if ($mapFlag) {
            $template = 'mapSearchResultNew';
        } else {
            $template = 'searchResultNew';
        }

        return $this->render('FaAdBundle:AdList:' . $template . '.html.twig', $parameters, null);
    }

    /**
     * @param $newRadius
     * @param $keywords
     * @param $extendedData
     * @param $extpage
     * @param $recordsPerPage
     * @param $staticOffset
     * @param $findersSearchParams
     */
    private function getExtendedPagination($newRadius, $keywords, $extendedData, $extpage, $recordsPerPage, $staticOffset, $findersSearchParams)
    {
        $counter = 0;
        $currentRadius = isset($findersSearchParams['item__distance'])?$findersSearchParams['item__distance']:'';

        do {
            unset($extendedResult);
            if (! empty($newRadius)) {
                $extendedData['static_filters'] = str_replace("sfield=store d={$currentRadius}}", "sfield=store d={$newRadius}}", $extendedData['static_filters']);
            }
            $extendedResult = $this->getSolrResult('ad.new', $keywords, $extendedData, $extpage, $recordsPerPage, $staticOffset, true);

            $counter++;
            $currentRadius = $newRadius;
            $newRadius = ($newRadius * 2);

        } while(empty($extendedResult['resultCount']) && $counter < 2);

        return $extendedResult;
    }

    /**
     * @param $searchParams
     * @param $listingFields
     * @param $adRepository
     * @return string
     */
    private function setDimensionParams($searchParams, $listingFields, $adRepository)
    {
        unset($searchParams['item__category_id']);
        if (isset($searchParams['sort_ord'])) unset($searchParams['sort_ord']);
        if (isset($searchParams['sort_field'])) unset($searchParams['sort_field']);
        $staticFilters = '';
        if ($searchParams['item__location'] != LocationRepository::COUNTY_ID) {
            /** @var Location $location */
            //$location = $this->getRepository('FaEntityBundle:Location')->find($searchParams['item__location']);
            $location = $this->getRepository('FaEntityBundle:Location')->getCookieValue($searchParams['item__location'],$this->container);

            $radius = CategoryRepository::MAX_DISTANCE;
            $categoryId = '';
            if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
                $categoryId = $searchParams['item__category_id'];
            }
            if (isset($searchParams['item__distance']) && $searchParams['item__distance']) {
                $radius = $searchParams['item__distance'];
            } else {
                    $getDefaultRadius = $this->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($searchParams, $this->container);
                    $radius = ($getDefaultRadius)?$getDefaultRadius:CategoryRepository::MAX_DISTANCE;
            }

            /*if (!empty($location)) {

                //$level = $location->getLvl();
                //$latitude = $location->getLatitude();
                //$longitude = $location->getLongitude();
                //$locationId = $location->getId();
                $level = (isset($location['locality_id']) && $location['locality_id']!='')?5:$location['lvl'];
                $latitude = $location['latitude'];
                $longitude = $location['longitude'];
                $locationId = (isset($location['locality_id']) && $location['locality_id']!='')?$location['locality_id']:$location['town_id'];

                // Apply Location ID filter Only if:
                // - Lat/Long is empty OR
                // - Location Level <= 2
                if ((empty($latitude) && empty($longitude)) || ($level <= 2)) {
                    // Warning: Locality check here will be wrong - bcoz Location & Locality are different DB tables.
                    $staticFilters = " AND (town: *\:{$locationId}\,* OR domicile: *\:{$locationId}\,* OR locality: *\:{$locationId}\,*)";
                }
                // Apply Lat/Long & Radius filter.
                else {
                    $staticFilters = " AND ({!geofilt pt={$latitude},{$longitude} sfield=store d={$radius}})";
                }
            }*/

        } else {
            $staticFilters = '';
        }
        unset($searchParams['item__location']);
        foreach (array_keys($searchParams) as $key) {
            if (preg_match('/\//', $key)) {
                unset($searchParams[$key]);
                break;
            }
        }

        $staticKeys = [
            'item__price_from',
            'item__price_to',
            'item__ad_type_id',
            'items_with_photo',
            'expired_ads',
            'item__is_trade_ad',
            'item__distance',
            'map',
            'keywords'
        ];
        $diffKeys = array_diff(array_keys($searchParams), $staticKeys);

        foreach ($diffKeys as $diffKey) {
            $solrDimensionFieldName = $adRepository->getSolrFieldName($listingFields, $this->getDimensionName($diffKey));
            if (! isset($searchParams[$solrDimensionFieldName])) {
                $searchParams[$solrDimensionFieldName] = [];
            }

            if (is_array($searchParams[$diffKey])) {
                $searchParams[$solrDimensionFieldName][] = $searchParams[$diffKey][0];
            } elseif (strpos($searchParams[$diffKey], '___') !== false) {
                $searchParams[$solrDimensionFieldName] = array_merge($searchParams[$solrDimensionFieldName], explode('___', $searchParams[$diffKey]));
            } else {
                $searchParams[$solrDimensionFieldName][] = $searchParams[$diffKey];
            }

            unset($searchParams[$diffKey]);
        }

        foreach ($searchParams as $searchKey => $searchItem) {
            if ($searchKey == 'item__price_from') {
              $staticFilters .= ' AND price : [' . $searchItem . ' TO *]';
            } else if ($searchKey == 'item__price_to') {
                $staticFilters .= ' AND price : [* TO ' . $searchItem . ']';
            } else if ($searchKey == 'items_with_photo') {
                $staticFilters .= ' AND image_count : [1 TO *]';
            } else if ($searchKey == 'expired_ads') {
                $staticFilters .= ' AND status_id : ' . EntityRepository::AD_STATUS_EXPIRED_ID;
            } else if ($searchKey == 'item__is_trade_ad') {
                $staticFilters .= ' AND is_trade_ad : ' . $searchItem;
            } else if ($searchKey == 'item__ad_type_id') {
                if (is_array($searchItem)) {
                    $sf = [];
                    foreach ($searchItem as $item) {
                        $sf[] = 'type_id : ' . $item;
                    }

                    $staticFilters .= ' AND (' . implode(' OR ', $sf) .')';
                } else {
                    $staticFilters .= ' AND type_id : ' . $searchItem;
                }
            } else if ($searchKey == 'item__distance' || $searchKey == 'map' || $searchKey == 'keywords') {

            } else {
                $thisFilter = [];
                foreach ($searchItem as $dimItem) {
                    $thisFilter[] = $searchKey .':*'.str_replace('-', '\-', $dimItem).'*';
                }

                $staticFilters .= ' AND (' . implode(' OR ', $thisFilter) .')';
            }
        }

        if (array_key_exists('expired_ads', $searchParams) == false) {
            $staticFilters .= ' AND status_id: ' . EntityRepository::AD_STATUS_LIVE_ID;
        }
        $staticFilters .= ' AND -is_blocked_ad: true';

        return $staticFilters;
    }

    /**
     * @param $categoryObj
     * @param $rootCategory
     * @param $facetResult
     * @param $totalAds
     * @param $dimensions
     * @param $searchParams
     * @param $data
     * @param $extendedFacetResult
     * @return array
     */
    private function getLeftFilters($categoryObj, $rootCategory, $facetResult, $totalAds, $dimensions, $searchParams, $data, $extendedFacetResult=null)
    {
        $params = [];
        foreach ($searchParams as $dimensionSlug => $searchParam) {
            $paramname = $this->getDimensionName($dimensionSlug);
            if ($paramname !== false) {
                if (isset($params[$paramname])) {
                    $selectedValue = $params[$paramname];
                } else {
                    $selectedValue = [];
                }

                if (is_array($searchParam)) {
                    $selectedValue[] = $searchParam[0];
                } elseif (strpos($searchParam, '___') !== false) {
                    $selectedValue = array_merge($selectedValue, explode('___', $searchParam));
                } else {
                    $selectedValue[] = $searchParam;
                }

                $params[$paramname] = $selectedValue;
            }
        }

        $adTypes = [];
        if (in_array('type_id', array_column($dimensions, 'solr_field'))) {
            foreach ($dimensions as $dimension) {
                if($dimension['solr_field'] === 'type_id'){
                    $adTypesResult = $this->getRepository('FaEntityBundle:Entity')->getEntitiesByCategoryDimensionId($dimension['id']);
                }
            }
            $adTypes = [];
            foreach ($adTypesResult as $adType) {
                $adTypes[$adType->getId()] = [
                    'name' => $adType->getName(),
                    'slug' => $adType->getSlug()
                ];
            }
        }

        // get next level categories
        $categoryRepository = $this->getRepository('FaEntityBundle:Category');

        $allCategories = $categoryRepository->getChildrenById($categoryObj->getId(), 'id');
        $categories = [];
        foreach($allCategories as $key => $category) {
            if (! empty($facetResult['category_ids'][$category['id']])) {
                if ($categoryObj->getId() != 1 || ($categoryObj->getId() == 1 && $category['id'] != CategoryRepository::ADULT_ID)) {
                    $categories[$key]          = $category;
                    $categories[$key]['count'] = $facetResult['category_ids'][$category['id']];
                    /*if (isset($extendedFacetResult) && isset($extendedFacetResult['category_ids']) && isset($extendedFacetResult['category_ids'][$category['id']])) {
                        $categories[$key]['count'] += $extendedFacetResult['category_ids'][$category['id']];
                    }*/
                }
            }
        }

        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $keywords       = (isset($data['search']['keywords']) && $data['search']['keywords']) ? $data['search']['keywords']: NULL;

        if (isset($params['is_trade_ad'])) {
            $newData = $data;
            if ($newData['static_filters']) {
                $staticFilters = explode(' AND ', $newData['static_filters']);
                $newStaticFilters = '';

                foreach ($staticFilters as $staticFilter) {
                    if (! empty($staticFilter) && strpos($staticFilter, 'is_trade_ad') === false) {
                        $newStaticFilters .= ' AND '.$staticFilter;
                    }
                }
                $newData['static_filters'] = $newStaticFilters;
                $newData['facet_fields'] = array(
                    'is_trade_ad' => array('min_count' => 0)
                );
            }

            $solrSearchManager->init('ad.new', $keywords, $newData, 1, 1, 0, true);
            $solrResponse = $solrSearchManager->getSolrResponse();
            $facetTradeResult = $solrSearchManager->getSolrResponseFacetFields($solrResponse);

            $tradeFacets = $facetTradeResult['is_trade_ad'];
        } else {
            $tradeFacets = $facetResult['is_trade_ad'];

            /* if (! empty($extendedFacetResult['is_trade_ad'])) {
                $tradeFacets["false"] += $extendedFacetResult['is_trade_ad']["false"];
                $tradeFacets["true"] += $extendedFacetResult['is_trade_ad']["true"];
            } */
        }

        $userTypeLabels = $this->getRepository('FaAdBundle:Ad')->getLeftSearchLabelForUserType($rootCategory->getId());
        $userTypes = [
            0 => [
                'title' => $userTypeLabels['private_user'],
                'url_param' => 'item__is_trade_ad=0',
                'count' => empty($tradeFacets) ? 0 : intval($tradeFacets["false"]),
                'selected' => isset($params['is_trade_ad']) && $params['is_trade_ad'][0] == '0' ? true : false
            ],
            1 => [
                'title' => $userTypeLabels['business_user'],
                'url_param' => 'is_tritem__is_trade_adade_ad=1',
                'count' => empty($tradeFacets) ? 0 : intval($tradeFacets["true"]),
                'selected' => isset($params['is_trade_ad']) && $params['is_trade_ad'][0] == '1' ? true : false
            ]
        ];

        $locationFacets = [];
        $locationFacetArr = [];
        $isSetDefaultDistance = 0;
        if (!empty($facetResult)) {
            if(isset($searchParams['default_distance']) && $searchParams['default_distance']==1) { $isSetDefaultDistance = 1; }
            $locationFacetArr = $this->getLocationFacetForSearchResult($facetResult, $data, $isSetDefaultDistance);
        }

        if (!empty($locationFacetArr)) {
            $locationFacetsIds = [];
            foreach ($locationFacetArr as $jsonValue => $facetCount) {
                $town = get_object_vars(json_decode($jsonValue));
                if(isset($town['id']) && !in_array($town['id'], $locationFacetsIds)) {
                    $locationFacetsIds[] = $town['id'];
                    if($town['id']!=$data['search']['item__location']) {
                        $locationFacets[] = array(
                            'id' => $town['id'],
                            'name' => $town['name'],
                            'slug' => $town['slug'],
                            'count' => $facetCount
                        );
                    }
                }
            }
        }

        /*if (isset($data['search']['item__location']) && $data['search']['item__location'] != LocationRepository::COUNTY_ID && (!isset($data['search']['item__distance']) || (isset($data['search']['item__distance']) && $data['search']['item__distance'] >= 0 && $data['search']['item__distance'] <= CategoryRepository::MAX_DISTANCE))) {
            if (isset($locationFacets[$data['search']['item__location']])) {
                unset($locationFacets[$data['search']['item__location']]);
            }
            $locationFacets = array_slice(array_unique($locationFacets), 0, 5, true);
        }*/


        /*$locationFacetsIds = [];
        if ($searchParams['item__location'] == 2) {
            $eLocationFacets = [];
            if (isset($extendedFacetResult['town'])) {
                foreach ($extendedFacetResult['town'] as $town => $count) {
                    $town = get_object_vars(json_decode($town));
                    $eLocationFacets[$town['id']] = $count;
                }
            }

            foreach ($facetResult['town'] as $town => $count) {
                $town = get_object_vars(json_decode($town));

                if (isset($eLocationFacets[$town['id']])) {
                    $count += $eLocationFacets[$town['id']];
                }

                $newData = $data;
                $newStaticFilters = '';
                if ($newData['static_filters']) {
                    $staticFilters = explode(' AND ', $newData['static_filters']);


                    foreach ($staticFilters as $staticFilter) {
                        if (!empty($staticFilter) && strpos($staticFilter, 'town') === false && strpos($staticFilter, 'sfield=store') === false) {
                            $newStaticFilters .= ' AND ' . $staticFilter;
                        }
                    }
                }
                if (isset($town['id']) && $town['id'] != LocationRepository::COUNTY_ID) {

                    $location = $this->getRepository('FaEntityBundle:Location')->find($town['id']);
                    $radius = CategoryRepository::MAX_DISTANCE;

                    if (!empty($location)) {

                        $level = $location->getLvl();
                        $latitude = $location->getLatitude();
                        $longitude = $location->getLongitude();
                        $locationId = $location->getId();

                        if ((empty($latitude) && empty($longitude)) || ($level <= 2)) {
                            $newStaticFilters .= " AND (town: *\:{$locationId}\,* OR domicile: *\:{$locationId}\,* OR locality: *\:{$locationId}\,*)";
                        } else {
                            $newStaticFilters .= " AND ({!geofilt pt={$latitude},{$longitude} sfield=store d={$radius}})";
                        }
                    }
                }
                $newData['static_filters'] = $newStaticFilters;
                $newData['facet_fields'] = array(
                    'town' => array('min_count' => 1),
                    'area' => array('min_count' => 1),
                    'locality' => array('min_count' => 1)
                );


                if(isset($town['id']) && !in_array($town['id'], $locationFacetsIds)) {
                    $locationFacetsIds[] = $town['id'];
                    if(isset($searchParams['item__location']) && $town['id'] != $searchParams['item__location']) {
                        $locationFacets[] = array(
                            'id' => $town['id'],
                            'name' => $town['name'],
                            'slug' => $town['slug'],
                            'count' => $count
                        );
                    }
                }

                $facetDimResult = $solrSearchManager->getSolrResponseFacetFields($solrResponse);
                if (! empty($facetDimResult)) {
                    $facetDimResult = $facetDimResult['town'];
                    foreach ($facetDimResult as $jsonValue => $facetCount) {
                        $town = get_object_vars(json_decode($jsonValue));
                        if(!in_array($town['id'], $locationFacetsIds)) {
                            $locationFacetsIds[] = $town['id'];
                            $locationFacets[] = array(
                                'id' => $town['id'],
                                'name' => $town['name'],
                                'slug' => $town['slug'],
                                'count' => $facetCount
                            );
                        }
                    }
                }

            }
        } else {
            foreach ($facetResult['town'] as $town => $count) {
                if (!empty($town)) {
                    $town = get_object_vars(json_decode($town));

                    $newData = $data;
                    $newStaticFilters = '';

                    if ($newData['static_filters']) {
                        $staticFilters = explode(' AND ', $newData['static_filters']);

                        foreach ($staticFilters as $staticFilter) {
                            if (!empty($staticFilter) && strpos($staticFilter, 'town') === false && strpos($staticFilter, 'sfield=store') === false) {
                                $newStaticFilters .= ' AND ' . $staticFilter;
                            }
                        }
                    }

                    if (isset($town['id']) && $town['id'] != LocationRepository::COUNTY_ID) {
                        $location = $this->getRepository('FaEntityBundle:Location')->find($town['id']);

                        $radius = CategoryRepository::MAX_DISTANCE;
                        $categoryId = '';
                        if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
                            $categoryId = $searchParams['item__category_id'];
                        }
                        if (isset($searchParams['item__distance']) && $searchParams['item__distance']) {
                            $radius = $searchParams['item__distance'];
                        } elseif($keywords) {
                            $radius = CategoryRepository::KEYWORD_DEFAULT;
                            $searchParams['item__distance'] = false;
                        } else {
                            if ($town['id'] == LocationRepository::LONDON_TOWN_ID) {
                                $radius = CategoryRepository::LONDON_DISTANCE;

                            } else {
                                $newSearchParams['item__category_id'] = isset($searchParams['item__category_id']) ? $searchParams['item__category_id'] : '';
                                $newSearchParams['item__distance'] = isset($searchParams['item__distance']) ? $searchParams['item__distance'] : '';
                                $newSearchParams['item__location'] = isset($town['id']) ? $town['id'] : '';
                                $getDefaultRadius = $this->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($newSearchParams, $this->container);
                                $radius = ($getDefaultRadius) ? $getDefaultRadius : '';
                            }
                        }
                        if ($radius == '') {
                            if ($categoryId != '') {
                                $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
                                $radius = ($rootCategoryId == CategoryRepository::MOTORS_ID) ? CategoryRepository::MOTORS_DISTANCE : CategoryRepository::OTHERS_DISTANCE;
                            } else {
                                $radius = CategoryRepository::MAX_DISTANCE;
                            }
                        }

                        if (!empty($location)) {

                            $level = $location->getLvl();
                            $latitude = $location->getLatitude();
                            $longitude = $location->getLongitude();
                            $locationId = $location->getId();

                            if ((empty($latitude) && empty($longitude)) || ($level <= 2)) {
                                $newStaticFilters .= " AND (town: *\:{$locationId}\,* OR domicile: *\:{$locationId}\,* OR locality: *\:{$locationId}\,*)";
                            } else {
                                $newStaticFilters .= " AND ({!geofilt pt={$latitude},{$longitude} sfield=store d={$radius}})";
                            }
                        }
                    }

                    //$newStaticFilters .= ' AND (town:*parent_id\"\:'.$town['parent_id'].'\,* OR locality:*parent_id\"\:'.$town['parent_id'].'\,* OR domicile:*parent_id\"\:'.$town['parent_id'].'\,* ) AND -(town:*\"id\"\:'.$town['id'].'\,* OR locality:*\"id\"\:'.$town['id'].'\,* OR domicile:*\"id\"\:'.$town['id'].'\,*)';

                    $newData['static_filters'] = $newStaticFilters;
                    $newData['facet_fields'] = array(
                        'town' => array('min_count' => 1),
                        'area' => array('min_count' => 1),
                        'locality' => array('min_count' => 1)
                    );

                    if(isset($town['id']) && !in_array($town['id'], $locationFacetsIds)) {
                        $locationFacetsIds[] = $town['id'];
                        if(isset($searchParams['item__location']) && $town['id'] != $searchParams['item__location']) {
                            $locationFacets[] = array(
                                'id' => $town['id'],
                                'name' => $town['name'],
                                'slug' => $town['slug'],
                                'count' => $count
                            );
                        }
                    }

                    $facetDimResult = $solrSearchManager->getSolrResponseFacetFields($solrResponse);
                    if (!empty($facetDimResult)) {
                        $facetDimResult = $facetDimResult['town'];
                        foreach ($facetDimResult as $jsonValue => $facetCount) {
                            $town = get_object_vars(json_decode($jsonValue));

                            if ($town['id'] != $searchParams['item__location'] && !in_array($town['id'], $locationFacetsIds)) {
                                $locationFacetsIds[] = $town['id'];
                                $locationFacets[] = array(
                                    'id' => $town['id'],
                                    'name' => $town['name'],
                                    'slug' => $town['slug'],
                                    'count' => $facetCount
                                );
                            }
                        }
                    }
                }
            }
        }*/

        $orderedDimensions = [];
        foreach ($dimensions as $dimension) {
            $solrFieldName = $dimensions[$dimension['id']]['solr_field'];
            $facetArraySet = $facetResult[$solrFieldName];
            $extendedFacetSet = [];
            if (isset($extendedFacetResult[$solrFieldName])) {
                foreach($extendedFacetResult[$solrFieldName] as $jsonKey => $eFacetCount) {
                    $value = json_decode($jsonKey);
                    if (is_object($value)) {
                        $entityValue = get_object_vars($value);

                        $key = 'id';
                        if (empty($entityValue[$key])) {
                            $key = 'name';
                        }

                        $extendedFacetSet[$solrFieldName][$entityValue[$key]] = $eFacetCount;
                    } else {
                        if ($dimension['id'] == EntityRepository::AD_TYPE_ID) {
                            $extendedFacetSet[$solrFieldName][$jsonKey] = $eFacetCount;
                        }
                    }
                }
            }

            $selected = '';
            $paramname = strtolower($dimensions[$dimension['id']]['dim_slug']);
            if (isset($params[$paramname])) {
                $selected = $params[$paramname];
            }

            if (! empty($facetArraySet)) {
                foreach ($facetArraySet as $jsonValue => $facetCount) {
                    $value = json_decode($jsonValue);
                    if (is_object($value)) {
                        $entityValue = get_object_vars($value);

                        $key = 'id';
                        if (empty($entityValue[$key])) {
                            $key = 'name';
                        }

                        /*if (isset($extendedFacetSet[$solrFieldName]) && isset($extendedFacetSet[$solrFieldName][$entityValue[$key]])) {
                            $facetCount += $extendedFacetSet[$solrFieldName][$entityValue[$key]];
                        }*/

                        $isSelected = false;
                        if (! empty($selected)) {
                            if (is_array($selected)) {
                                if (in_array($entityValue[$key], $selected) || in_array($entityValue['slug'], $selected)) {
                                    $isSelected = true;
                                }
                            } else {
                                if ($selected == $entityValue[$key]) {
                                    $isSelected = true;
                                }
                            }
                        }

                        $orderedDimensions[$dimension['id']][$entityValue[$key]] = array(
                            'name' => $entityValue['name'],
                            'slug' => isset($entityValue['slug']) ? $entityValue['slug'] : $entityValue['name'],
                            'count' => $facetCount,
                            'selected' => $isSelected
                        );
                    }
                    else {
                        if ($dimension['solr_field'] =='type_id' ) {
                            $isSelected = false;
                            if (! empty($selected)) {
                                if (is_array($selected)) {
                                    if (in_array($jsonValue, $selected)) {
                                        $isSelected = true;
                                    }
                                } else {
                                    if ($selected == $jsonValue) {
                                        $isSelected = true;
                                    }
                                }
                            }

                            /*if (isset($extendedFacetSet[$solrFieldName]) && isset($extendedFacetSet[$solrFieldName][$jsonValue])) {
                                $facetCount += $extendedFacetSet[$solrFieldName][$jsonValue];
                            }*/

                            if (isset($adTypes[$jsonValue])) {
                                $orderedDimensions[$dimension['id']][$jsonValue] = array(
                                    'name' => $adTypes[$jsonValue]['name'],
                                    'slug' => $adTypes[$jsonValue]['slug'],
                                    'count' => $facetCount,
                                    'selected' => $isSelected
                                );
                            }
                            $entityDimensions = $this->getRepository('FaEntityBundle:Entity')->findBy(array('category_dimension' => $dimension['id']));
                            foreach ($entityDimensions as $entityDimension){
                                if (!property_exists($facetArraySet,$entityDimension->getId()))
                                {
                                    $newDataCount = $data;
                                    $newDataCount['search']['item__ad_type_id'][0] = $entityDimension->getId();
                                    $newDataCount['query_filters']['item']['ad_type_id'][0] = $entityDimension->getId();
                                    $newDataCount['query_filters']['item']['ad_type'][0] = $entityDimension->getId();
                                    $newDataCount['static_filters'] = str_replace($jsonValue,(string)$entityDimension->getId(),$newDataCount['static_filters']);
                                    $newSolrData = $this->getSolrResult('ad.new', $keywords, $newDataCount, 1, 2, 0, true);
                                    if($newSolrData['resultCount']) {
                                        $orderedDimensions[$dimension['id']][$entityDimension->getId()] = array(
                                            'name' => $adTypes[$entityDimension->getId()]['name'],
                                            'slug' => $adTypes[$entityDimension->getId()]['slug'],
                                            'count' => $newSolrData['resultCount'],
                                            'selected' => false
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (! empty($selected)) {
                $newData = $data;
                if ($newData['static_filters']) {
                    $staticFilters = explode(' AND ', $newData['static_filters']);
                    $newStaticFilters = '';

                    foreach ($staticFilters as $staticFilter) {
                        if (! empty($staticFilter) && strpos($staticFilter, $solrFieldName) === false) {
                            $newStaticFilters .= ' AND '.$staticFilter;
                        }
                    }
                    $newData['static_filters'] = $newStaticFilters;
                    $newData['facet_fields'] = array(
                        $solrFieldName => array('min_count' => 1)
                    );
                }

                $solrSearchManager->init('ad.new', $keywords, $newData, 1, 1, 0, true);
                $solrResponse = $solrSearchManager->getSolrResponse();
                $facetDimResult = $solrSearchManager->getSolrResponseFacetFields($solrResponse);
                if (! empty($facetDimResult)) {
                    $facetDimResult = $facetDimResult[$solrFieldName];
                    foreach ($facetDimResult as $jsonValue => $facetCount) {
                        $value = json_decode($jsonValue);

                        if (is_object($value)) {
                            $entityValue = get_object_vars($value);
                            $key = 'id';
                            if (empty($entityValue['id'])) {
                                $key = 'name';
                            }

                            if (! isset($orderedDimensions[$dimension['id']][$entityValue[$key]])) {
                                $orderedDimensions[$dimension['id']][$entityValue[$key]] = array(
                                    'name' => $entityValue['name'],
                                    'slug' => isset($entityValue['slug']) ? $entityValue['slug'] : $entityValue['name'],
                                    'count' => $facetCount,
                                    'selected' => false
                                );
                            }
                        } else {
                            if ($dimension['dim_slug'] == 'ad_type') {
                                $isSelected = false;
                                if (! empty($selected)) {
                                    if (is_array($selected)) {
                                        if (in_array($jsonValue, $selected)) {
                                            $isSelected = true;
                                        }
                                    } else {
                                        if ($selected == $jsonValue) {
                                            $isSelected = true;
                                        }
                                    }
                                }
                                if (isset($adTypes[$jsonValue])) {
                                    $orderedDimensions[$dimension['id']][$jsonValue] = array(
                                        'name' => $adTypes[$jsonValue]['name'],
                                        'slug' => $adTypes[$jsonValue]['slug'],
                                        'count' => $facetCount,
                                        'selected' => $isSelected
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        $noImages = 0;
        if (isset($facetResult['image_count'])) {
            $arr = get_object_vars($facetResult['image_count']);
            if (! empty($arr) && isset($arr[0])) {
                $noImages = intval($arr[0]);
            }
        }

        $subCategory = '';
        $lvlCategory = $categoryObj;
        while ($lvlCategory->getLvl() > 1 && $rootCategory->getId() !== $lvlCategory->getParent()->getId()) {
            $subCategory = $lvlCategory = $lvlCategory->getParent();
        }

        return [
            'categories'        => $categories,
            'current_category'  => $categoryObj,
            'parent_category'   => $rootCategory,
            'sub_category'      => $subCategory,
            'user_types'        => $userTypes,
            'image_count'       => $totalAds - $noImages,
            'locationFacets'    => $locationFacets,
            'orderedDimensions' => $orderedDimensions,
            'dimensions'        => $dimensions,
            'items_with_photo'   => isset($params['items_with_photo']),
            'expired_ads'       => isset($params['expired_ads']),
            'showPriceField'    => $categoryObj->getId() > 1 ? CommonManager::showPriceInSearchFilter($categoryObj->getId(), $this->container) : true,
            'userTypeTitle'     => $userTypeLabels['header']
        ];
    }

    /**
     * @param $solrCoreName
     * @param $keywords
     * @param $data
     * @param int $page
     * @param int $recordsPerPage
     * @param int $staticOffset
     * @param bool $exactMatch
     * @param bool $getPagination
     * @return mixed
     */
    private function getSolrResult($solrCoreName, $keywords, $data, $page = 1, $recordsPerPage = 20, $staticOffset = 0, $exactMatch = false, $getPagination = false)
    {
        $solrSearchManager = $this->get('fa.solrsearch.manager');

        $solrSearchManager->init($solrCoreName, $keywords, $data, $page, $recordsPerPage, $staticOffset, $exactMatch);
        $solrResponse = $solrSearchManager->getSolrResponse();

        // fetch result set from solr
        $result      = $solrSearchManager->getSolrResponseDocs($solrResponse);
        $resultCount = $solrSearchManager->getSolrResponseDocsCount($solrResponse);
        if ($recordsPerPage > 3) {
            $facetResult = $solrSearchManager->getSolrResponseFacetFields($solrResponse);
        } else {
            $facetResult = [];
        }

        $parameters = ['result' => $result, 'resultCount' => $resultCount, 'facetResult' => $facetResult];
        if ($getPagination) {
            $this->get('fa.pagination.manager')->init($result, $page, $recordsPerPage, $resultCount);
            $parameters['pagination'] = $this->get('fa.pagination.manager')->getSolrPagination();
        }

        return $parameters;
    }

    /**
     * @param $pagination
     * @return array
     */
    private function formatAds($pagination)
    {
        $ads = [];

        if (! empty($pagination)) {
            foreach ($pagination->getCurrentPageResults() as $key => $ad) {
                $ad = get_object_vars($ad);

                $dim_keys = preg_grep('/^dim_list_*/', array_keys($ad));

                $dimensions = [];
                if (count($dim_keys)) {
                    foreach ($dim_keys as $dim_key) {
                        foreach ($ad[$dim_key] as $key => $value) {
                            $json = json_decode($ad[$dim_key][$key]);
                            if (is_object($json)) {
                                $dim_field = get_object_vars($json);
                                $dimensions[] = array(
                                    'name' => $dim_field['name'],
                                    'listing_class' => empty($dim_field['listing_class']) ? '' : $dim_field['listing_class']
                                );
                            }
                        }

                    }
                }

                /* Show the lowest of location */
                $location = $this->getAdLocation($ad);

                $ads[] = [
                    'ad_id'         => $ad['id'],
                    'ad_title'      => isset($ad['title']) ? $ad['title'] : '',
                    'description'   => isset($ad['description']) ? $ad['description'] : '',
                    'price'         => empty($ad['price']) ? '' : CommonManager::formatCurrency($ad['price'], $this->container),
                    'ad_img'        => isset($ad['thumbnail_url']) ? $ad['thumbnail_url'] : $this->container->getParameter('fa.static.shared.url').'/bundles/fafrontend/images/no-image-grey.svg',
                    'image_count'   => isset($ad['image_count']) ? $ad['image_count'] : 0,
                    'img_alt'       => '',
                    'ad_url'        => isset($ad['ad_detail_url'])?$ad['ad_detail_url']:'',
                    'dimensions'    => $dimensions,
                    'top_ad'        => empty($ad['is_topad']) ? false : true,
                    'urgent_ad'     => empty($ad['is_urgent_ad']) ? false : true,
                    'boosted_ad'    => empty($ad['is_boosted_ad']) ? false : true,
                    'affiliate_ad'  => empty($ad['is_affiliate']) ? false : true,
                    'location'      => $location,
                    'latitude'      => isset($ad['latitude']) ? $ad['latitude'] : null,
                    'longitude'     => isset($ad['longitude']) ? $ad['longitude'] : null,
                    'last_updated'  => empty($ad['weekly_refresh_published_at']) ? $ad['published_at'] : $ad['weekly_refresh_published_at'],
                    'aff_icon_cls'  => ($ad['is_affiliate'] && ($ad['ad_source'] != 'paa' || $ad['ad_source'] != 'paa-app' || $ad['ad_source'] != 'admin')) ? CommonManager::getAffiliateClass($ad['ad_source']) : ''
                ];

            }
        }

        return $ads;
    }

    /**
     * @param $ad
     * @return string|null
     */
    private function getAdLocation($ad)
    {
        $location = NULL;
        if (! empty($ad['area'])) {
            $index = 'area';
        } else if (! empty($ad['locality'])) {
            $index = 'locality';
        } else if (! empty($ad['town'])) {
            $index = 'town';
        } else if (! empty($ad['domicile'])) {
            $index = 'domicile';
        }

        if (isset($index)) {
            $jsonObject = json_decode($ad[$index]);

            if (is_object($jsonObject)) {
                $location = get_object_vars($jsonObject);
                $location = $location = isset($location['location_text'])?$location['location_text']:(isset($location['name'])?$location['name']:'');
            }
        }

        return $location;
    }

    /**
     * @param $ads
     * @param $categoryId
     * @param $rootCategoryId
     *
     * @return array
     */
    private function formatAdsForFeaturedBusiness($ads, $categoryId, $rootCategoryId)
    {
        $formattedAds = [];

        if (! empty($ads)) {
            foreach ($ads as $key => $ad) {
                $seoToolRepository = $this->getRepository('FaContentBundle:SeoTool');
                $seoRule = $seoToolRepository->getSeoPageRuleDetailForSolrResult($ad, SeoToolRepository::ADVERT_IMG_ALT, $this->container);

                $imgAlt = null;
                if (isset($seoRule['img_alt'])) {
                    $imgAlt = CommonManager::getAdImageAlt($this->container, $seoRule['img_alt'], $ad);
                }

                $postFixText = '';
                if ($rootCategoryId == CategoryRepository::PROPERTY_ID && isset($ad['rent_per_id'])) {
                    $postFixText = $this->getRepository('FaAdBundle:AdProperty')->getRentPostFixText($ad['rent_per_id'], $this->container);
                }

                $formattedAds[] = [
                    'ad_id'         => $ad['id'],
                    'ad_title'      => isset($ad['title']) ? $ad['title'] : '',
                    'price'         => empty($ad['price']) ? '' : CommonManager::formatCurrency($ad['price'], $this->container),
                    'ad_img'        => isset($ad['thumbnail_url']) ? $ad['thumbnail_url'] : '',
                    'image_count'   => isset($ad['image_count']) ? $ad['image_count'] : 0,
                    'img_alt'       => $imgAlt,
                    'ad_url'        => $ad['ad_detail_url'],
                    'category_id'   => $categoryId,
                    'post_fix_text' => $postFixText
                ];

            }
        }

        return $formattedAds;
    }

    /**
     * @param $locationFacets
     * @param $data
     * @return array
     */
    private function getLocationFacetForSearchResult($locationFacets, $data, $isSetDefaultDistance=0)
    {
        if (!empty($locationFacets) && !empty($data)) {
            $getCount = [];
            foreach ($locationFacets as $facetKey=>$facet) {
                if ($facetKey == 'a_l_town_id_txt' || $facetKey == 'a_l_area_id_txt' || $facetKey == 'town' || $facetKey == 'area') {
                    $facetResult = get_object_vars($facet);
                    if (!empty($facetResult)) {
                        foreach ($facetResult as $key=>$res) {
                            //get Facet Count for Nearby Town
                            $getCount[$key] = $this->getFacetCountForNearbyTown($key, $data, $isSetDefaultDistance);
                        }
                    }
                }
            }
            return $getCount;
        }
    }

    /**
     * @param $key
     * @param $facetdata
     * @return mixed
     */
    private function getFacetCountForNearbyTown($key, $facetdata, $isSetDefaultDistance=0)
    {
        $keywords       = (isset($facetdata['search']['keywords']) && $facetdata['search']['keywords']) ? $facetdata['search']['keywords']: null;
        if (isset($facetdata['facet_fields']) && isset($facetdata['facet_fields'])) {
            unset($facetdata['facet_fields']);
        }

        $locationId = $key;
        if(CommonManager::isJSON($key)) {
            $townKey = json_decode($key);
            $locationId = isset($townKey->id)?$townKey->id:2;
        }
        $facetdata['search']['item__location'] = $locationId;

        if($locationId==LocationRepository::LONDON_TOWN_ID && $isSetDefaultDistance==1)
        {
            $facetdata['search']['item__distance'] =  CategoryRepository::LONDON_DISTANCE;
        }
        /*if(isset($data['search']['item__distance'])) {
    		$data['search']['item__distance'] = 0;
    	}
    	if(isset($data['query_filters']['item']['distance'])) {
            $data['query_filters']['item']['distance'] = 0;
            $data['query_filters']['item']['location'] = $key."|0";
        }*/
        $radius = CategoryRepository::MAX_DISTANCE;
        $categoryId = '';
        if (isset($facetdata['search']['item__category_id']) && $facetdata['search']['item__category_id']) {
            $categoryId = $facetdata['search']['item__category_id'];
        }
        if (isset($facetdata['search']['item__distance']) && $facetdata['search']['item__distance']) {
            $radius = $facetdata['search']['item__distance'];
        } else {
            $getDefaultRadius = $this->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($facetdata['search'], $this->container);
            $radius = ($getDefaultRadius)?$getDefaultRadius:CategoryRepository::MAX_DISTANCE;
        }

        if (isset($facetdata['search']['item__distance'])) {
            $facetdata['query_filters']['item']['location'] = $locationId.'|'.((isset($facetdata['search']['item__distance']))?($facetdata['search']['item__distance']):'');
        } else {
            $facetdata['query_filters']['item']['location'] = $locationId.'|'.$radius;
        }
        $page               = 1;
        $recordsPerPage     = 2;
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad.new', $keywords, $facetdata, $page, $recordsPerPage, 0, true);
        $solrResponse = $solrSearchManager->getSolrResponse();

        $resultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);
        return $resultCount;
    }

    /**
     *
     * @param  array $data
     * @return array
     */
    private function handleCustomizedUrl($data, $request)
    {
        $queryParams = array();
        $customize_url_data = $request->attributes->get('customized_page');
        $source_url = $customize_url_data['source_url'];
        $source_data = parse_url($source_url);

        if (isset($source_data['query'])) {
            $cqparams = explode('&', $source_data['query']);
        } else {
            $cqparams = array();
        }
        $qa = array();
        $keyword = null;

        foreach ($cqparams as $key => $val) {
            $vparams = explode('=', $val);
            if (count($vparams) == 2) {
                $qa[$vparams[0]] = $vparams[1];
            }
        }

        $request->attributes->set('finders', array_merge_recursive($request->attributes->get('finders'), $qa));

        foreach ($qa as $key => $val) {
            if (preg_match('/^(.*)_id$/', $key) || preg_match('/reg_year|mileage_range|engine_size_range/', $key)) {
                $queryParams[$key] = explode("__", $val);

                if (preg_match('/^(.*)_id$/', $key)) {
                    $queryParams[$key] = array_map('intval', explode("__", $val));
                }
            } else {
                if ($key == 'keywords') {
                    $keyword = $val;
                } else {
                    $queryParams[$key] = $val;
                }
            }
        }

        $request->attributes->set('finders', array_replace_recursive($request->attributes->get('finders'), $queryParams));

        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'finders', array('finders' => $queryParams));
        $queryParams = $this->get('fa.searchfilters.manager')->getFiltersData();
        $data['query_filters'] = array_replace_recursive($queryParams['query_filters'], $data['query_filters']);

        return array($keyword, $data);
    }


    /**
     * Get shop user by search criteria.
     *
     * @param array   $data    Search parameters.
     * @param Request $request Request object.
     *
     * @return array
     */
    private function getShopUserBySearchCriteriaNew($data, $request)
    {
        $shopPackageCategories  = array(CategoryRepository::FOR_SALE_ID, CategoryRepository::MOTORS_ID, CategoryRepository::JOBS_ID, CategoryRepository::PROPERTY_ID, CategoryRepository::ANIMALS_ID, CategoryRepository::COMMUNITY_ID);
        $profileExposureUserIds = array();
        $profileExposureMiles   = array();
        $rootCategoryId = null;

        // for showing profile page.
        if (isset($data['search']['item__category_id'])) {
            $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($data['search']['item__category_id'], $this->container);
            if (in_array($rootCategoryId, $shopPackageCategories)) {
                $profileExposureMiles = $this->getRepository('FaPromotionBundle:Package')->getShopPackageProfileExposureUpsellByCategory($data['search']['item__category_id'], $this->container);
            }
        } else {
            foreach ($shopPackageCategories as $shopPackageCategoryId) {
                $profileExposureMiles = array_merge($profileExposureMiles, $this->getRepository('FaPromotionBundle:Package')->getShopPackageProfileExposureUpsellByCategory($shopPackageCategoryId, $this->container));
            }
            $profileExposureMiles = array_unique($profileExposureMiles);
        }

        if (count($profileExposureMiles)) {
            $viewedProfileExposureUserIds = array();
            if ($request->cookies->get('profile_exposure_user_ids_'.$rootCategoryId) && $request->cookies->get('profile_exposure_user_ids_'.$rootCategoryId) != CommonManager::COOKIE_DELETED) {
                $viewedProfileExposureUserIds = array_filter(explode(',', $request->cookies->get('profile_exposure_user_ids_'.$rootCategoryId)));
            }

            foreach ($profileExposureMiles as $profileExposureMile) {
                if (isset($data['search']['item__category_id']) && isset($rootCategoryId) && $rootCategoryId) {
                    $profileExposureUserIds = array_merge($profileExposureUserIds, $this->getProfileExposureUserNew($data, $profileExposureMile, array($rootCategoryId), $viewedProfileExposureUserIds));
                } else {
                    $profileExposureUserIds = array_merge($profileExposureUserIds, $this->getProfileExposureUserNew($data, $profileExposureMile, $shopPackageCategories, $viewedProfileExposureUserIds));
                }
            }

            if (!count($profileExposureUserIds)) {
                $viewedProfileExposureUserIds = array();
                foreach ($profileExposureMiles as $profileExposureMile) {
                    if (isset($data['search']['item__category_id']) && isset($rootCategoryId) && $rootCategoryId) {
                        $profileExposureUserIds = array_merge($profileExposureUserIds, $this->getProfileExposureUserNew($data, $profileExposureMile, array($rootCategoryId), $viewedProfileExposureUserIds, $rootCategoryId));
                    } else {
                        $profileExposureUserIds = array_merge($profileExposureUserIds, $this->getProfileExposureUserNew($data, $profileExposureMile, $shopPackageCategories, $viewedProfileExposureUserIds, $rootCategoryId));
                    }
                }
            }

            if (count($profileExposureUserIds)) {
                $profileUserDetail          = $profileExposureUserIds[array_rand($profileExposureUserIds)];
                $profileUserId              = $profileUserDetail['id'];
                if (isset($profileUserDetail['location'])) {
                    $data['query_filters']['item']['location'] = $profileUserDetail['location'];
                }
                $profileExposureUserAds = $this->getProfileExposureUserAdsNew($profileUserId, $this->getRepository('FaEntityBundle:Category')->find($rootCategoryId), $data);
                $parameters['profileExposureUserAds'] = $profileExposureUserAds;
                $parameters['profileUserId']          = $profileUserId;
                $parameters['profileUserDetail']      = $this->getRepository('FaUserBundle:User')->getProfileExposureUserDetailForAdList($profileUserId, $this->container);

                $viewedProfileExposureUserIds[] = $profileUserId;
                $response = new Response();
                $response->headers->setCookie(new Cookie('profile_exposure_user_ids_'.$rootCategoryId, implode(',', $viewedProfileExposureUserIds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
                $response->sendHeaders();

                return $parameters;
            }

            $response = new Response();
            $response->headers->setCookie(new Cookie('profile_exposure_user_ids_'.$rootCategoryId, implode(',', $viewedProfileExposureUserIds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
            $response->sendHeaders();

            return array();
        }

        return array();
    }

    /**
     * Get shop user by search criteria.
     *
     * @param array   $data    Search parameters.
     * @param Request $request Request object.
     *
     * @return array
     */
    private function getShopUserBySearchCriteria($data, $request)
    {
        $shopPackageCategories  = array(CategoryRepository::FOR_SALE_ID, CategoryRepository::MOTORS_ID, CategoryRepository::JOBS_ID, CategoryRepository::PROPERTY_ID, CategoryRepository::ANIMALS_ID, CategoryRepository::COMMUNITY_ID);
        $profileExposureUserIds = array();
        $profileExposureMiles   = array();
        $rootCategoryId = null;

        // for showing profile page.
        if (isset($data['search']['item__category_id'])) {
            $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($data['search']['item__category_id'], $this->container);
            if (in_array($rootCategoryId, $shopPackageCategories)) {
                $profileExposureMiles = $this->getRepository('FaPromotionBundle:Package')->getShopPackageProfileExposureUpsellByCategory($data['search']['item__category_id'], $this->container);
            }
        } else {
            foreach ($shopPackageCategories as $shopPackageCategoryId) {
                $profileExposureMiles = array_merge($profileExposureMiles, $this->getRepository('FaPromotionBundle:Package')->getShopPackageProfileExposureUpsellByCategory($shopPackageCategoryId, $this->container));
            }
            $profileExposureMiles = array_unique($profileExposureMiles);
        }

        if (count($profileExposureMiles)) {
            $viewedProfileExposureUserIds = array();
            if ($request->cookies->get('profile_exposure_user_ids_'.$rootCategoryId) && $request->cookies->get('profile_exposure_user_ids_'.$rootCategoryId) != CommonManager::COOKIE_DELETED) {
                $viewedProfileExposureUserIds = array_filter(explode(',', $request->cookies->get('profile_exposure_user_ids_'.$rootCategoryId)));
            }

            foreach ($profileExposureMiles as $profileExposureMile) {
                if (isset($data['search']['item__category_id']) && isset($rootCategoryId) && $rootCategoryId) {
                    $profileExposureUserIds = array_merge($profileExposureUserIds, $this->getProfileExposureUser($data, $profileExposureMile, array($rootCategoryId), $viewedProfileExposureUserIds));
                } else {
                    $profileExposureUserIds = array_merge($profileExposureUserIds, $this->getProfileExposureUser($data, $profileExposureMile, $shopPackageCategories, $viewedProfileExposureUserIds));
                }
            }

            if (!count($profileExposureUserIds)) {
                $viewedProfileExposureUserIds = array();
                foreach ($profileExposureMiles as $profileExposureMile) {
                    if (isset($data['search']['item__category_id']) && isset($rootCategoryId) && $rootCategoryId) {
                        $profileExposureUserIds = array_merge($profileExposureUserIds, $this->getProfileExposureUser($data, $profileExposureMile, array($rootCategoryId), $viewedProfileExposureUserIds, $rootCategoryId));
                    } else {
                        $profileExposureUserIds = array_merge($profileExposureUserIds, $this->getProfileExposureUser($data, $profileExposureMile, $shopPackageCategories, $viewedProfileExposureUserIds, $rootCategoryId));
                    }
                }
            }

            if (count($profileExposureUserIds)) {
                $profileUserDetail          = $profileExposureUserIds[array_rand($profileExposureUserIds)];
                $profileUserId              = $profileUserDetail['id'];
                if (isset($profileUserDetail['location'])) {
                    $data['query_filters']['item']['location'] = $profileUserDetail['location'];
                }
                $profileExposureUserAds = $this->getProfileExposureUserAds($profileUserId, $data);
                $parameters['profileExposureUserAds'] = $profileExposureUserAds;
                $parameters['profileUserId']          = $profileUserId;
                $parameters['profileUserDetail']      = $this->getRepository('FaUserBundle:User')->getProfileExposureUserDetailForAdList($profileUserId, $this->container);

                $viewedProfileExposureUserIds[] = $profileUserId;
                $response = new Response();
                $response->headers->setCookie(new Cookie('profile_exposure_user_ids_'.$rootCategoryId, implode(',', $viewedProfileExposureUserIds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
                $response->sendHeaders();

                return $parameters;
            }

            $response = new Response();
            $response->headers->setCookie(new Cookie('profile_exposure_user_ids_'.$rootCategoryId, implode(',', $viewedProfileExposureUserIds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
            $response->sendHeaders();

            return array();
        }

        return array();
    }

    /**
     * Set search param cookie.
     *
     * @param array $data Search parameters
     */
    private function setSearchParamsCookie($data,$request)
    {
        $currentRoute = $request->get('_route');
        if ((isset($data['search']) && count($data['search'])) || (isset($data['query_filters']) && count($data['query_filters']))) {
            if (isset($data['search']['item__category_id']) || isset($data['search']['keywords'])) {
                $rootCategoryId = null;
                $searchParams   = (isset($data['query_filters']) && count($data['query_filters'])) ? $data['query_filters'] : array();

                if (isset($data['search']['item__category_id'])) {
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($data['search']['item__category_id'], $this->container);
                }
                if (isset($data['search']['keywords'])) {
                    $searchParams['keywords'] = $data['search']['keywords'];
                }

                if (!isset($data['search']['item__category_id']) || (isset($data['search']['item__category_id']) && $rootCategoryId == CategoryRepository::ADULT_ID && $currentRoute == 'fa_adult_homepage')) {
                    $response = new Response();
                    $response->headers->setCookie(new Cookie('home_page_search_params', serialize($searchParams), time() + (365*24*60*60*1000), '/', null, false, false));
                    $response->sendHeaders();
                }
            }
        }
    }

    /**
     * Top search result.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function topSearchResultAction(Request $request)
    {
        return new Response();
    }

    /**
     * Ad landing page search result.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function landingPageSearchResultAction(Request $request)
    {
        return new Response();
    }

    /**
     * Left search.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function leftSearchAction(Request $request, $isShopPage = false)
    {
        $bindSearchParams = array();
        $basicParams      = array();
        $searchParams     = $request->get('searchParams');
        $parentIdArray = array();
        if (isset($searchParams['item_motors__make_id']) && $searchParams['item_motors__make_id']) {
            if (is_array($searchParams['item_motors__make_id']) && count($searchParams['item_motors__make_id'])) {
                $parentIdArray['model_id'] = $searchParams['item_motors__make_id'][0];
            } else {
                $parentIdArray['model_id'] = $searchParams['item_motors__make_id'];
            }
        }

        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(AdLeftSearchType::class, array('isShopPage' => $isShopPage, 'parentIdArray' => $parentIdArray), array('method' => 'GET', 'action' => ($isShopPage ? $this->generateUrl('shop_user_ad_left_search_result') : $this->generateUrl('ad_left_search_result'))));

        foreach ($searchParams as $key => $val) {
            if (in_array($key, array('keywords', 'item__price_from', 'item__price_to', 'item__distance', 'item__category_id', 'item__location', 'item__is_trade_ad', 'item__user_id'))) {
                $basicParams[$key] = $val;
            }
        }
        
        if (isset($searchParams['item__location']) && $searchParams['item__location']) {
            $locationText = $this->getRepository('FaEntityBundle:Postcode')->getPostCodTextByLocation($searchParams['item__location'], $this->container);
            if (!$locationText) {
                if (preg_match('/^\d+$/', $searchParams['item__location'])) {
                    $locationText = $this->getRepository('FaEntityBundle:Location')->getLocationNameWithParentNameById($searchParams['item__location'], $this->container, false);
                } elseif (preg_match('/^([\d]+,[\d]+)$/', $searchParams['item__location'])) {
                    $localityTown = explode(',', $searchParams['item__location']);
                    $locality     = $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Locality', $localityTown[0]);
                    $town         = $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $localityTown[1]);
                    $locationText = $locality.','.$town;
                }
                
                if (!$locationText) {
                    if ($searchParams['item__location'] == LocationRepository::COUNTY_ID) {
                        $locationText = null;
                    } else {
                        $locationText = $this->getRepository('FaEntityBundle:Location')->getLocationTextByLocation($searchParams['item__location'], $this->container);
                        if (!$locationText) {
                            $locationText = $this->getRepository('FaEntityBundle:Locality')->getLocationTextByLocation($searchParams['item__location'], $this->container);
                        }
                    }
                    
                    //$searchParams['item__location'] = $locationText;
                }
            }
            $searchParams['item__location_autocomplete'] = $locationText;
        }

        $dimensions = array();
        if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
            $dimensions = $this->getRepository('FaEntityBundle:CategoryDimension')->getSearchableDimesionsArrayWithFieldByCategoryId($searchParams['item__category_id'], $this->container);
        }

        if ($searchParams && count($searchParams)) {
            foreach ($form->all() as $field) {
                if (isset($searchParams[$field->getName()])) {
                    if (isset($dimensions[$field->getName()]['search_type']) && in_array($dimensions[$field->getName()]['search_type'], array('choice_single', 'choice_link', 'choice_radio'))) {
                        $bindSearchParams[$field->getName()] = $searchParams[$field->getName()][0];
                    } else {
                        $bindSearchParams[$field->getName()] = $searchParams[$field->getName()];
                    }
                }
            }
            $form->submit($bindSearchParams);
        }
        $parameters = array('form' => $form->createView(), 'locationFacets' => $request->get('locationFacets'), 'facetResult' => $request->get('facetResult', 'facetResult'), 'basicParams' => $basicParams, 'isShopPage' => $isShopPage, 'cookieLocationDetails' => $request->get('cookieLocationDetails'), 'leftFilters' => $request->get('leftFilters'));

        return $this->render('FaAdBundle:AdList:leftSearch.html.twig', $parameters);
    }

    /**
     * Left search result.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function leftSearchResultAction(Request $request)
    {
        return new Response();
    }

    /**
     * Left search result for shop user ads.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function shopUserAdsLeftSearchResultAction(Request $request)
    {
        return new Response();
    }

    /**
     * Set default parameters for sorting and paging for different listing view.
     *
     * @param Request $request               Request object.
     * @param boolean $mapFlag               Boolean flag for map.
     * @param string  $searchType            Left or top search.
     * @param array   $cookieLocationDetails Location cookie value.
     *
     * @return array
     */
    public function setDefaultParameters($request, $mapFlag, $searchType, $cookieLocationDetails = array())
    {
        $currentRoute      = $request->get('_route');
        $hasSortField      = true;
        $parentCategoryIds = array();
        // set default sorting for list view
        if (!$request->get('sort_field') && !$mapFlag) {
            $request->request->set('sort_field', 'item__weekly_refresh_published_at');
            $request->request->set('sort_ord', 'desc');
            $hasSortField = false;
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'finders');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();
        
        // add published at as second sort
        if (!$mapFlag) {
            if ($request->get('sort_field') == 'item__weekly_refresh_published_at') {
                unset($data['query_sorter']['item']['weekly_refresh_published_at']);
            }

            if (isset($data['search']['item__category_id']) && $data['search']['item__category_id']) {
                $parentCategoryIds = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($data['search']['item__category_id'], false, $this->container));
            }

            //$data['query_sorter']['item']['weekly_refresh_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
            // if 1st level category is what's on then sort by event end.
            if (!$hasSortField && isset($parentCategoryIds[1]) && $parentCategoryIds[1] == CategoryRepository::WHATS_ON_ID) {
                $data['query_sorter']['ad_community']['event_start'] = array('sort_ord' => 'asc', 'field_ord' => 1);
            } /*elseif (!$hasSortField && isset($parentCategoryIds[0]) && $parentCategoryIds[0] == CategoryRepository::SERVICES_ID) {
                //if service category then sort by nearest first else by rating
                if (isset($data['search']['item__location']) && $data['search']['item__location'] != LocationRepository::COUNTY_ID && (!isset($data['search']['item__distance']) || (isset($data['search']['item__distance']) && $data['search']['item__distance'] >= 0 && $data['search']['item__distance'] <= 200))) {
                    $data['query_sorter']['item']['geodist'] = array('sort_ord' => 'asc', 'field_ord' => 1);
                    $request->attributes->set('sort_field', 'item__geodist');
                    $request->attributes->set('sort_ord', 'asc');
                } else {
                    $data['query_sorter']['user']['rating'] = array('sort_ord' => 'desc', 'field_ord' => 1);
                    $request->attributes->set('sort_field', 'user__rating');
                    $request->attributes->set('sort_ord', 'desc');
                }
            }*/ else {
            if ((!isset($data['search']['item__distance']) || (isset($data['search']['item__distance']) && $data['search']['item__distance'] >= 0 && $data['search']['item__distance'] <= CategoryRepository::MAX_DISTANCE))) {
                    if (is_array($cookieLocationDetails) &&
                       (!isset($cookieLocationDetails['latitude']) || !$cookieLocationDetails['latitude']) &&
                       (!isset($cookieLocationDetails['longitude']) || !$cookieLocationDetails['longitude']) &&
                       (isset($data['query_sorter']['item']) && isset($data['query_sorter']['item']['geodist']))) {
                        unset($data['query_sorter']['item']['geodist']);
                    }
                }
                if (isset($data['search']['keywords']) && strlen(trim($data['search']['keywords']))) {
                    $data['query_sorter']['item']['score'] = array('sort_ord' => 'desc', 'field_ord' => 4);
                }

                //$data['query_sorter']['item']['created_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
                $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 2);
            }
            $data['query_sorter']['item']['is_top_ad'] = array('sort_ord' => 'desc', 'field_ord' => 3);
        }

        //set default sorting for map
        if ($mapFlag) {
            unset($data['query_sorter']);

            if (isset($data['search']['item__location']) && $data['search']['item__location'] != LocationRepository::COUNTY_ID && (!isset($data['search']['item__distance']) || (isset($data['search']['item__distance']) && $data['search']['item__distance'] >= 0 && $data['search']['item__distance'] <= CategoryRepository::MAX_DISTANCE))) {
                if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['latitude']) && isset($cookieLocationDetails['longitude'])) {
                    //$data['query_sorter']['item']['geodist'] = 'asc';
                }
            }

            $data['query_sorter']['item']['weekly_refresh_published_at'] = 'desc';
            $data['sorter']['sort_field'] = 'item__weekly_refresh_published_at';
            $data['sorter']['sort_ord'] = 'desc';
            $data['pager']['page']  = 1;
            $data['pager']['limit'] = $this->container->getParameter('fa.search.map.records.per.page');
            $data['select_fields'] = array('item' => array('id', 'title', 'latitude', 'longitude'));
        } else {
            $numberOfRecords = $this->getRepository('FaCoreBundle:ConfigRule')->getNumberOfOrganicResult($this->container);
            if ($numberOfRecords) {
                $data['pager']['limit'] = $numberOfRecords;
            } else {
                $data['pager']['limit'] = $this->container->getParameter('fa.search.records.per.page');
            }
        }

        // Active or expired ads
        if (isset($data['search']['expired_ads']) && $data['search']['expired_ads']) {
            $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_EXPIRED_ID;
        } else {
            $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        }

        // ads with min 1 photo
        if (isset($data['search']['items_with_photo']) && $data['search']['items_with_photo']) {
            $data['query_filters']['item']['image_count'] = '1|';
        }

        $data['facet_fields'] = array(
                                    AdSolrFieldMapping::TOWN_ID     => array('limit' => 5, 'min_count' => 1),
                                    AdSolrFieldMapping::CATEGORY_ID => array('min_count' => 1),
                                    AdSolrFieldMapping::ROOT_CATEGORY_ID => array('min_count' => 1),
                                    AdSolrFieldMapping::AREA_ID		=> array('limit' => 5, 'min_count' => 1)
                                );
    
        // Add dimension filters facets
        if (isset($data['search']['item__category_id']) && $data['search']['item__category_id']) {
            $categoryName = $this->getRepository('FaEntityBundle:Category')->getRootCategoryName($data['search']['item__category_id'], $this->container, true);
            $dimensions   = $this->getRepository('FaEntityBundle:CategoryDimension')->getSearchableDimesionsArrayByCategoryId($data['search']['item__category_id'], $this->container);
            $solrMapping  = '';
            if ($categoryName) {
                $solrMapping = 'Fa\Bundle\AdBundle\Solr\Ad'.$categoryName.'SolrFieldMapping::';
            }

            foreach ($dimensions as $dimensionId => $dimension) {
                $dimensionName   = $dimension['name'];
                $searchTypeArray = explode('_', $dimension['search_type']);
                if ($searchTypeArray[0] == 'choice') {
                    $dimensionField = str_replace(array('(', ')', ',', '?', '|', '.', '/', '\\', '*', '+', '-', '"', "'"), '', $dimensionName);
                    $dimensionField = str_replace(' ', '_', strtoupper($dimensionField)).'_ID';
                    $facetField     = $solrMapping.$dimensionField;

                    if ($dimensionField == 'AD_TYPE_ID') {
                        $facetField = 'Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping::TYPE_ID';
                    }

                    if (defined($facetField)) {
                        $data['facet_fields'][constant($facetField)] = array('min_count' => 1);
                    }
                }
            }
        }

        // ad location filter with distance
        if (isset($data['search']['item__location']) && $data['search']['item__location']) {
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.((isset($data['search']['item__distance']) ? $data['search']['item__distance'] : ''));
        }
        //$data['query_filters']['item']['is_blocked_ad'] = 0;
        // remove adult results when there is no category selected
        if (!isset($data['search']['item__category_id']) && !in_array($currentRoute, array('show_business_user_ads', 'show_business_user_ads_page', 'show_business_user_ads_location','fa_adult_homepage'))) {
            $data['static_filters'] = ' AND -'.AdSolrFieldMapping::ROOT_CATEGORY_ID.':'.CategoryRepository::ADULT_ID;
        }

        return $data;
    }

    /**
     * Left search modal.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxLeftSearchDimensionModalAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $searchParams        = array();
            $searchParams        = unserialize($request->get('searchParams'));
            $dimensionSearchType = $request->get('dimensionSearchType');

            $request->request->set('searchParams', $searchParams);
            $formManager = $this->get('fa.formmanager');
            $form        = $formManager->createForm(AdLeftSearchDimensionType::class, null, array('method' => 'GET', 'action' => $this->generateUrl('ad_left_search_dimension_result')));

            $bindSearchParams = array();
            if ($searchParams && count($searchParams)) {
                foreach ($form->all() as $field) {
                    if (isset($searchParams[$field->getName()])) {
                        if ($dimensionSearchType == 'choice_link') {
                            $bindSearchParams[$field->getName()] = (isset($searchParams[$field->getName()][0]) ? $searchParams[$field->getName()][0] : '');
                        } elseif ($dimensionSearchType == 'choice_checkbox') {
                            $bindSearchParams[$field->getName()] = $searchParams[$field->getName()];
                        }
                    }
                }
                $form->submit($bindSearchParams);
            }

            $parameters = array('form' => $form->createView());
            if ($dimensionSearchType == 'choice_link') {
                return $this->render('FaAdBundle:AdList:ajaxLeftSearchLinkDimensionModal.html.twig', $parameters);
            } elseif ($dimensionSearchType == 'choice_checkbox') {
                return $this->render('FaAdBundle:AdList:ajaxLeftSearchCheckboxDimensionModal.html.twig', $parameters);
            }
        }

        return new Response();
    }

    /**
     * Find top ads randomly as defined slots.
     *
     * @param array   $data           Search parameters.
     * @param string  $keywords       Keywords.
     * @param array   $page           Page.
     * @param boolean $mapFlag        Boolean flag for map.
     * @param Request $request        Request object.
     * @param integer $rootCategoryId Root category id.
     *
     * @return array
     */
    private function getSearchResultTopAds($data, $keywords = null, $page = 1, $mapFlag = false, $request, $rootCategoryId)
    {
        $topAdResult = $this->getTopAds($data, $keywords, $page, $mapFlag, true, $request, $rootCategoryId);

        if (!count($topAdResult)) {
            $topAdResult = $this->getTopAds($data, $keywords, $page, $mapFlag, false, $request, $rootCategoryId);
        }

        return $topAdResult;
    }
    
    
    /**
     * Find top ads randomly as defined slots.
     *
     * @param array   $data           Search parameters.
     * @param string  $keywords       Keywords.
     * @param array   $page           Page.
     * @param boolean $mapFlag        Boolean flag for map.
     * @param boolean $skipAds        Boolean flag for skip viewed ads.
     * @param Request $request        Request object.
     * @param integer $rootCategoryId Root category id.
     *
     * @return array
     */
    private function getTopAds($data, $keywords = null, $page = 1, $mapFlag = false, $skipAds = true, $request, $rootCategoryId)
    {
        $finalTopAdResult = array();
        if (!$mapFlag && $page == 1) {
            $data['query_filters']['item']['is_top_ad'] = '1';
            $data['query_sorter']['item'] = array('random' => 'desc');
            unset($data['facet_fields']);

            if ($skipAds) {
                $viewedTopAds = array_filter(explode(',', $request->cookies->get('top_ads_search_result_'.$rootCategoryId)));
                if (count($viewedTopAds)) {
                    $viewedTopAds = array_unique($viewedTopAds);
                    if (isset($data['static_filters'])) {
                        $data['static_filters'] = $data['static_filters'].' AND -'.AdSolrFieldMapping::ID.':('.implode(' ', $viewedTopAds).')';
                    } else {
                        $data['static_filters'] = ' AND -'.AdSolrFieldMapping::ID.':('.implode(' ', $viewedTopAds).')';
                    }
                }
            } else {
                $viewedTopAds = array();
            }

            $recordsPerPage = $this->getRepository('FaCoreBundle:Configrule')->getListingTopAdSlots($this->container);
            $this->get('fa.solrsearch.manager')->init('ad.new', $keywords, $data, $page, $recordsPerPage, 0, true);
            $topAdResult = $this->get('fa.solrsearch.manager')->getSolrResponseDocs();
            $topAdCount = count($topAdResult);

            $newTopAdResult = array();
            $time_of_boosted_ad = array();

            if ($topAdCount) {
                foreach ($topAdResult as $key=>$topAd) {
                    $viewedTopAds[] = $topAd->id;

                    if (isset($topAd->a_is_boosted_b) && $topAd->a_is_boosted_b == 1) {
                        $newTopAdResult[$topAd->a_boosted_at_i] = $topAd;
                        unset($topAdResult[$key]);
                    }
                }
                $viewedTopAds = array_unique($viewedTopAds);
            }
            krsort($newTopAdResult);
            $finalTopAdResult = array_merge($newTopAdResult, $topAdResult);

            $response = new Response();
            $response->headers->setCookie(new Cookie('top_ads_search_result_'.$rootCategoryId, implode(',', $viewedTopAds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
            $response->sendHeaders();
        }

        return $finalTopAdResult;
    }
    
    /**
     * Find User Live Basic Ad.
     *
     * @param array   $data           Search parameters.
     * @param string  $keywords       Keywords.
     * @param array   $page           Page.
     * @param boolean $mapFlag        Boolean flag for map.
     * @param Request $request        Request object.
     * @param integer $rootCategoryId Root category id.
     *
     * @return array
     */
    private function getUserLiveBasicAd($data, $keywords = null, $page = 1, $mapFlag = false, $request, $rootCategoryId)
    {
        $loggedinUser     = $this->getLoggedInUser();
        $getBasicAdResult = null;
        $availablePackageIds = [];
        if (!empty($loggedinUser)) {
            $user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
            if (!empty($user)) {
                if ($rootCategoryId == 'null') {
                    return false;
                }
                //check for this package category upgrade featured top is enabled
                $isFeaturedTopisEnabledForCateg = $this->getRepository('FaAdBundle:Ad')->checkIsfeaturedUpgradeEnabledForCategory($rootCategoryId, $this->container);
                if (empty($isFeaturedTopisEnabledForCateg)) {
                    return false;	//featured upgrade is not enable for this root category
                }
                //get basic Live advert if exist
                $getBasicAdResult = $this->getRepository('FaAdBundle:Ad')->getLastBasicPackageAdvertForUpgrade($rootCategoryId, $user->getId(), null, $this->container);
            }
        }
        return $getBasicAdResult;
    }

    public function getAdDetailForMapBoxAction($adId, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $data['query_filters']['item']['id'] = $adId;
            $this->get('fa.solrsearch.manager')->init('ad', null, $data, 1, 1);
            $adResult = $this->get('fa.solrsearch.manager')->getSolrResponseDocs();
            if ($adResult) {
                return $this->render('FaAdBundle:AdList:mapListDetail.html.twig', array('ad' => $adResult[0]));
            }

            return new Response('No advert detail found.');
        }

        return new Response();
    }

    /**
     * Show listing blocks.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showListingSeoBlocksAction(Request $request, $searchParams, $location = null, $seoPageRule = null, $orgRequest = null)
    {
        //remove ads with image filter
        if (isset($searchParams['search']['items_with_photo'])) {
            unset($searchParams['search']['items_with_photo']);
        }

        $parentCategoryIds = array();
        $seoSearchParams   = array();
        $categoryId        = null;
        $mapFlag           = $request->get('map', 0);
        $searchQueryString = $orgRequest->getQueryString();

        $page = $orgRequest->get('page');
        if (!$page) {
            $routeParams = $orgRequest->attributes->get('_route_params');
            if (isset($routeParams['page'])) {
                $page = $routeParams['page'];
            }
        }
        // set seo search parameters and solr search parameters.
        if ($mapFlag) {
            $seoSearchParams['map'] = $mapFlag;
        }
        if (isset($searchParams['search']['item__location']) && $searchParams['search']['item__location']) {
            $seoSearchParams['item__location'] = $searchParams['search']['item__location'];
        }
        if (isset($searchParams['search']['item__category_id']) && $searchParams['search']['item__category_id']) {
            $categoryId        = $searchParams['search']['item__category_id'];
            $parentCategoryIds = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container));
            $indexableDimensionFieldArray = $this->getRepository('FaEntityBundle:CategoryDimension')->getIndexableDimensionFieldsArrayByCategoryId($categoryId, $this->container);
            $seoSearchParams['item__category_id']         = $categoryId;
            $data['query_filters']['item']['category_id'] = $categoryId;

            // set all indexable fields.
            if (count($indexableDimensionFieldArray)) {
                foreach ($indexableDimensionFieldArray as $indexableDimensionField) {
                    if (isset($searchParams['search'][$indexableDimensionField]) && $searchParams['search'][$indexableDimensionField]) {
                        $explodeRes                                            = explode('__', $indexableDimensionField);
                        $data['query_filters'][$explodeRes[0]][$explodeRes[1]] = $searchParams['search'][$indexableDimensionField];
                        $seoSearchParams[$indexableDimensionField]             = $searchParams['search'][$indexableDimensionField];
                    }
                }
            }
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'));

        // Active ads
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        // fetch location from cookie.
        $cookieLocation = CommonManager::getLocationDetailFromParamsOrCookie($location, $request, $this->container);

        $blocks = $this->getListingBlockParams($categoryId, $parentCategoryIds, $cookieLocation, $seoPageRule, $seoSearchParams);
        
        $data['facet_fields'] = array();
        if (empty($cookieLocation)) {
            $data['facet_fields'] = array(
                AdSolrFieldMapping::DOMICILE_ID => array('limit' => $blocks[AdSolrFieldMapping::DOMICILE_ID]['facet_limit'], 'min_count' => 1),
                AdSolrFieldMapping::TOWN_ID     => array('limit' => $blocks[AdSolrFieldMapping::TOWN_ID]['facet_limit'], 'min_count' => 1),
                AdSolrFieldMapping::AREA_ID		=> array('limit' => 9, 'min_count' => 1),
            );
        } else {
            if (isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
                //$data['query_sorter']['item']['geodist'] = 'asc';
            }

            // for jobs and property and all their children categories need to show county seo box.
            if (isset($parentCategoryIds[0]) && in_array($parentCategoryIds[0], array(CategoryRepository::JOBS_ID, CategoryRepository::PROPERTY_ID))) {
                $data['facet_fields'] = array(
                    AdSolrFieldMapping::DOMICILE_ID => array('limit' => 9, 'min_count' => 1),
                    AdSolrFieldMapping::TOWN_ID     => array('limit' => $blocks[AdSolrFieldMapping::TOWN_ID]['facet_limit'], 'min_count' => 1),
                    AdSolrFieldMapping::AREA_ID		=> array('limit' => 9, 'min_count' => 1),
                );
            } else {
                $data['facet_fields'] = array(
                    AdSolrFieldMapping::TOWN_ID => array('limit' => $blocks[AdSolrFieldMapping::TOWN_ID]['facet_limit'], 'min_count' => 1),
                    AdSolrFieldMapping::AREA_ID => array('limit' => 9, 'min_count' => 1),
                );
            }
        }

        // set facet categorywise.
        list($blocks, $data) = $this->getSeoBlocksByCategory($categoryId, $parentCategoryIds, $blocks, $cookieLocation, $searchParams, $data, $seoPageRule, $seoSearchParams);
        
        // initialize solr search manager service and fetch data based of above prepared search options
        $this->get('fa.solrsearch.manager')->init('ad', '', $data);
        if (!empty($cookieLocation)) {
            if (isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
                $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocation['latitude'].','.$cookieLocation['longitude']);
                $this->get('fa.solrsearch.manager')->setGeoDistQuery($geoDistParams);
            }
        }
        $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();

        // fetch result set from solr
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);
        
        if ($facetResult) {
            $facetResult = get_object_vars($facetResult);
            foreach ($blocks as $solrFieldName => $block) {
                if ($solrFieldName == 'a_m_make_id_i' && isset($seoSearchParams['item_motors__model_id'])) {
                    unset($seoSearchParams['item_motors__model_id']);
                }
                if (isset($facetResult[$solrFieldName]) && !empty($facetResult[$solrFieldName])) {
                    $blocks[$solrFieldName]['facet'] = get_object_vars($facetResult[$solrFieldName]);
                    //get Location Areas
                    if ($solrFieldName == AdSolrFieldMapping::TOWN_ID && isset($facetResult[AdSolrFieldMapping::AREA_ID]) && !empty($facetResult[AdSolrFieldMapping::AREA_ID])) {
                        $blocks[$solrFieldName]['facet'] = $blocks[$solrFieldName]['facet'] + get_object_vars($facetResult[AdSolrFieldMapping::AREA_ID]);
                    }
                }
            }
        }
        
        //to show seo block for For Sale, Jobs, Property
        if (isset($blocks[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID]) && isset($parentCategoryIds[0]) && in_array($parentCategoryIds[0], array(CategoryRepository::JOBS_ID, CategoryRepository::PROPERTY_ID, CategoryRepository::FOR_SALE_ID))) {
            $tmpSearchParams = array();
            $tmpSearchParams['search']['item__category_id'] = $parentCategoryIds[0];
            $tmpSearchParams['search']['item__location'] = LocationRepository::COUNTY_ID;
            $facetResult = $this->getDimensionFacetByField(AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID, 'item__category_id', $blocks[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID]['facet_limit'], $tmpSearchParams, $this->container, null, false, $cookieLocation);
            if (count($facetResult)) {
                $blocks[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID]['facet'] = array($categoryId => 1) + $facetResult;
            }
            if (!count($facetResult)) {
                $blocks[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID]['facet'] = array($categoryId => 1) + $this->getDimensionFacetByField(AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID, 'item__category_id', $blocks[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID]['facet_limit'], $tmpSearchParams, $this->container, null, false, $cookieLocation);
            }
        } elseif (isset($blocks[AdAnimalsSolrFieldMapping::SPECIES_ID.'_UK']) && isset($parentCategoryIds[2]) && isset($parentCategoryIds[2]) == CategoryRepository::BIRDS) {
            $tmpSearchParams = array();
            $tmpSearchParams['search']['item__category_id'] = $parentCategoryIds[2];
            $tmpSearchParams['search']['item__location'] = LocationRepository::COUNTY_ID;
            $facetResult = $this->getDimensionFacetByField(AdAnimalsSolrFieldMapping::SPECIES_ID, 'item_animals__species_id', $blocks[AdAnimalsSolrFieldMapping::SPECIES_ID]['facet_limit'], $tmpSearchParams, $this->container, null, false, $cookieLocation);
            if (count($facetResult)) {
                $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($seoSearchParams, array('item__location' => LocationRepository::COUNTY_ID)));
                $blocks[AdAnimalsSolrFieldMapping::SPECIES_ID.'_UK']['facet'] = array('0' => array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categoryId), 'url' => $searchResultUrl)) + $facetResult;
            }
        } elseif (isset($blocks[AdAnimalsSolrFieldMapping::BREED_ID.'_UK']) && isset($parentCategoryIds[2]) && in_array($parentCategoryIds[2], array(CategoryRepository::DOGS_AND_PUPPIES, CategoryRepository::HORSES, CategoryRepository::CATS_AND_KITTENS))) {
            $tmpSearchParams = array();
            $tmpSearchParams['search']['item__category_id'] = $parentCategoryIds[2];
            $tmpSearchParams['search']['item__location'] = LocationRepository::COUNTY_ID;
            $facetResult = $this->getDimensionFacetByField(AdAnimalsSolrFieldMapping::BREED_ID, 'item_animals__breed_id', $blocks[AdAnimalsSolrFieldMapping::BREED_ID]['facet_limit'], $tmpSearchParams, $this->container, null, false, $cookieLocation);
            if (count($facetResult)) {
                $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($seoSearchParams, array('item__location' => LocationRepository::COUNTY_ID)));
                $blocks[AdAnimalsSolrFieldMapping::BREED_ID.'_UK']['facet'] = array('0' => array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categoryId), 'url' => $searchResultUrl)) + $facetResult;
            }
        } elseif (isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK']) && isset($parentCategoryIds[1]) && in_array($parentCategoryIds[1], array(CategoryRepository::BOATS_ID, CategoryRepository::FARM_ID))) {
            $tmpSearchParams = array();
            $tmpSearchParams['search']['item__category_id'] = $parentCategoryIds[1];
            $tmpSearchParams['search']['item__location'] = LocationRepository::COUNTY_ID;
            $facetResult = $this->getDimensionFacetByField(AdMotorsSolrFieldMapping::MANUFACTURER_ID, 'item_motors__manufacturer_id', $blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK']['facet_limit'], $tmpSearchParams, $this->container, null, false, $cookieLocation);
            if (count($facetResult)) {
                $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($seoSearchParams, array('item__location' => LocationRepository::COUNTY_ID)));
                $blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK']['facet'] = array('0' => array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categoryId), 'url' => $searchResultUrl)) + $facetResult;
            }
        } elseif (isset($blocks[AdMotorsSolrFieldMapping::MAKE_ID.'_UK']) && isset($parentCategoryIds[0]) && in_array($parentCategoryIds[0], array(CategoryRepository::MOTORS_ID))) {
            $maxParentCategoryCnt = (count($parentCategoryIds) - 1);
            $tmpSearchParams = array();
            $tmpSearchParams['search']['item__category_id'] = (isset($parentCategoryIds[$maxParentCategoryCnt]) ? $parentCategoryIds[$maxParentCategoryCnt] : null);
            $tmpSearchParams['search']['item__location'] = LocationRepository::COUNTY_ID;
            $facetResult = $this->getDimensionFacetByField(AdMotorsSolrFieldMapping::MAKE_ID, 'item_motors__make_id', $blocks[AdMotorsSolrFieldMapping::MAKE_ID.'_UK']['facet_limit'], $tmpSearchParams, $this->container, null, false, $cookieLocation);
            if (count($facetResult)) {
                $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($seoSearchParams, array('item__location' => LocationRepository::COUNTY_ID)));
                $blocks[AdMotorsSolrFieldMapping::MAKE_ID.'_UK']['facet'] = array('0' => array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categoryId), 'url' => $searchResultUrl)) + $facetResult;
            }
        }

        //switch position of top maked in uk and town
        if (isset($parentCategoryIds[1]) && (isset($blocks[AdMotorsSolrFieldMapping::MAKE_ID.'_UK']) || (isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']) && count($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']))) && isset($blocks[AdMotorsSolrFieldMapping::MAKE_ID]) && in_array(isset($parentCategoryIds[1]), array(CategoryRepository::CARS_ID, CategoryRepository::MOTORHOMES_ID, CategoryRepository::MOTORHOMES_AND_CARAVANS_STATIC_CARAVANS_ID, CategoryRepository::COMMERCIALVEHICLES_ID, CategoryRepository::MOTORBIKES_ID, CategoryRepository::MOTORBIKES_MOTORBIKES_ID, CategoryRepository::MOTORBIKES_QUAD_BIKES_ID, CategoryRepository::MOTORHOMES_AND_CARAVANS_CARAVANS_ID))) {
            if (isset($blocks[AdMotorsSolrFieldMapping::MAKE_ID.'_UK'])) {
                $blocks = CommonManager::arraySwapAssoc(AdMotorsSolrFieldMapping::MAKE_ID.'_UK', AdMotorsSolrFieldMapping::MAKE_ID, $blocks);
            }
            if (isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']) && isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']) && count($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']) && isset($blocks[AdMotorsSolrFieldMapping::MAKE_ID]) && isset($blocks[AdMotorsSolrFieldMapping::MAKE_ID]['facet']) && count($blocks[AdMotorsSolrFieldMapping::MAKE_ID]['facet'])) {
                $blocks = CommonManager::arraySwapAssoc(CategoryRepository::MOTORS_ID.'_top_links', AdMotorsSolrFieldMapping::MAKE_ID, $blocks);
            }
        }

        //switch position of model
        if (isset($parentCategoryIds[1]) && (isset($blocks[AdMotorsSolrFieldMapping::MODEL_ID.'_UK']) || (isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']) && count($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']))) && isset($blocks[AdMotorsSolrFieldMapping::MODEL_ID]) && in_array(isset($parentCategoryIds[1]), array(CategoryRepository::CARS_ID, CategoryRepository::MOTORHOMES_ID, CategoryRepository::MOTORHOMES_AND_CARAVANS_STATIC_CARAVANS_ID, CategoryRepository::COMMERCIALVEHICLES_ID, CategoryRepository::MOTORBIKES_ID, CategoryRepository::MOTORBIKES_MOTORBIKES_ID, CategoryRepository::MOTORBIKES_QUAD_BIKES_ID, CategoryRepository::MOTORHOMES_AND_CARAVANS_CARAVANS_ID))) {
            if (isset($blocks[AdMotorsSolrFieldMapping::MODEL_ID.'_UK']) && isset($blocks[AdMotorsSolrFieldMapping::MODEL_ID.'_UK']['facet']) && count($blocks[AdMotorsSolrFieldMapping::MODEL_ID.'_UK']['facet']) && isset($blocks[AdMotorsSolrFieldMapping::MODEL_ID]) && isset($blocks[AdMotorsSolrFieldMapping::MODEL_ID]['facet']) && count($blocks[AdMotorsSolrFieldMapping::MODEL_ID]['facet'])) {
                $blocks = CommonManager::arraySwapAssoc(AdMotorsSolrFieldMapping::MODEL_ID.'_UK', AdMotorsSolrFieldMapping::MODEL_ID, $blocks);
            }
            if (isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']) && isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']) && count($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']) && isset($blocks[AdMotorsSolrFieldMapping::MODEL_ID]) && isset($blocks[AdMotorsSolrFieldMapping::MODEL_ID]['facet']) && count($blocks[AdMotorsSolrFieldMapping::MODEL_ID]['facet'])) {
                $blocks = CommonManager::arraySwapAssoc(CategoryRepository::MOTORS_ID.'_top_links', AdMotorsSolrFieldMapping::MODEL_ID, $blocks);
            }
        }

        //switch position of manufacturer
        if (isset($parentCategoryIds[1]) && (isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK']) || (isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']) && count($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']))) && isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID]) && in_array(isset($parentCategoryIds[1]), array(CategoryRepository::CARS_ID, CategoryRepository::MOTORHOMES_ID, CategoryRepository::MOTORHOMES_AND_CARAVANS_STATIC_CARAVANS_ID, CategoryRepository::COMMERCIALVEHICLES_ID, CategoryRepository::MOTORBIKES_ID, CategoryRepository::MOTORBIKES_MOTORBIKES_ID, CategoryRepository::MOTORBIKES_QUAD_BIKES_ID, CategoryRepository::MOTORHOMES_AND_CARAVANS_CARAVANS_ID))) {
            if (isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK']) && isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK']['facet']) && count($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK']['facet']) && isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID]) && isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID]['facet']) && count($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID]['facet'])) {
                $blocks = CommonManager::arraySwapAssoc(AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK', AdMotorsSolrFieldMapping::MANUFACTURER_ID, $blocks);
            }
            if (isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']) && isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']) && count($blocks[CategoryRepository::MOTORS_ID.'_top_links']['facet']) && isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID]) && isset($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID]['facet']) && count($blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID]['facet'])) {
                $blocks = CommonManager::arraySwapAssoc(CategoryRepository::MOTORS_ID.'_top_links', AdMotorsSolrFieldMapping::MANUFACTURER_ID, $blocks);
            }
        }
        $parameters = array(
            'blocks'          => $blocks,
            'seoSearchParams' => $seoSearchParams,
            'categoryId'      => $categoryId,
            'seoPageRule'     => $seoPageRule,
            'page'            => $page,
            'cookieLocation'  => $cookieLocation,
            'searchParams'    => $searchParams,
            'searchQueryString'=> $searchQueryString,
        );
        return $this->render('FaAdBundle:AdList:showListingSeoBlocks.html.twig', $parameters);
    }

    /**
     * Get seo blocks facet by category.
     *
     * @param integer $categoryId        Category id.
     * @param array   $parentCategoryIds Array of parent categories.
     * @param array   $blocks            Block array.
     * @param array   $cookieLocation    Array of location.
     * @param array   $searchParams      Array of search parameters.
     * @param array   $data              Array of facet fields.
     *
     * @return array
     */
    private function getSeoBlocksByCategory($categoryId, $parentCategoryIds, $blocks, $cookieLocation, $searchParams, $data, $seoPageRule, $seoSearchParams)
    {
        $rootCategoryId = (isset($parentCategoryIds[0]) ? $parentCategoryIds[0] : null);

        if ($rootCategoryId) {
            // check for cars and commercial vehicles
            if (isset($parentCategoryIds[1]) && in_array($parentCategoryIds[1], array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID))) {
                if (count($parentCategoryIds) >= 3) {
                    if (isset($blocks[AdMotorsSolrFieldMapping::MAKE_ID])) {
                        unset($blocks[AdMotorsSolrFieldMapping::MAKE_ID]);
                    }
                    if (isset($blocks[CategoryRepository::MOTORS_ID.'_top_links'])) {
                        unset($blocks[CategoryRepository::MOTORS_ID.'_top_links']);
                    }
                    $topModelLinks = array();
                    if (!empty($cookieLocation) && !empty($seoPageRule) && isset($seoPageRule['seo_tool_id'])) {
                        $topModelLinks = $this->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($seoPageRule['seo_tool_id'], $this->container);
                        $blocks = CommonManager::insertBeforeArray($blocks, AdSolrFieldMapping::TOWN_ID, array(CategoryRepository::MOTORS_ID.'_top_links' => array(
                            'heading' => $this->get('translator')->trans('Top Models', array(), 'frontend-search-list-block'),
                            'is_category_specific' => false,
                            'is_top_links' => true,
                            'facet' => $topModelLinks
                        )));
                    }
                    if (!empty($cookieLocation) && empty($topModelLinks)) {
                        $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($seoSearchParams, array('item__location' => LocationRepository::COUNTY_ID)));
                        $blocks = CommonManager::insertBeforeArray($blocks, AdSolrFieldMapping::TOWN_ID, array(AdMotorsSolrFieldMapping::MODEL_ID.'_UK' => array(
                            'heading' => $this->get('translator')->trans('Top Models', array(), 'frontend-search-list-block'),
                            'search_field_name' => 'item__category_id',
                            'is_category_specific' => true,
                            'facet_limit'          => 19,
                            'dimension_name'       => 'model',
                            'repository'           => 'FaEntityBundle:Category',
                            'show_all_link'        => false,
                            'facet'                => array('0' => array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categoryId), 'url' => $searchResultUrl)) + $this->getDimensionFacetByField(AdMotorsSolrFieldMapping::CATEGORY_ID, 'item__category_id', 19, $searchParams, $this->container, ' AND (a_parent_category_lvl_3_id_i : '.$parentCategoryIds[2].')', true, $cookieLocation),
                            'first_entry_as_uk' => true,
                        )));
                    }
                    $blocks = CommonManager::insertBeforeArray($blocks, AdSolrFieldMapping::TOWN_ID, array(AdMotorsSolrFieldMapping::MODEL_ID =>  array(
                        'heading' => $this->get('translator')->trans('Top Models'.(isset($cookieLocation['location_text']) ? ' in '.$cookieLocation['location_text'] : null), array(), 'frontend-search-list-block'),
                        'search_field_name' => 'item__category_id',
                        'is_category_specific' => true,
                        'facet_limit'          => 25,
                        'dimension_name'       => 'model',
                        'repository'           => 'FaEntityBundle:Category',
                        'show_all_link'        => false,
                        'facet'                => $this->getDimensionFacetByField(AdMotorsSolrFieldMapping::CATEGORY_ID, 'item__category_id', (!empty($cookieLocation) ? 10 : 25), $searchParams, $this->container, ' AND (a_parent_category_lvl_3_id_i : '.$parentCategoryIds[2].')', true, $cookieLocation),
                    )));                    
                } else {
                    $solrFieldName = AdMotorsSolrFieldMapping::MAKE_ID;
                    $blocks[$solrFieldName]['facet'] = $this->getDimensionFacetByField(AdMotorsSolrFieldMapping::CATEGORY_MAKE_ID, 'item__category_id', (!empty($cookieLocation) ? 10 : 25), $searchParams, $this->container, ' AND (a_category_id_i : ('.$parentCategoryIds[1].') OR a_parent_category_lvl_2_id_i : ('.$parentCategoryIds[1].') OR a_parent_category_lvl_3_id_i : ('.$parentCategoryIds[1].'))', true, $cookieLocation);
                    $blocks[$solrFieldName]['repository'] = 'FaEntityBundle:Category';
                    $blocks[$solrFieldName]['search_field_name'] = 'item__category_id';
                    if (empty($cookieLocation)) {
                        $blocks[$solrFieldName]['show_all_link'] = true;
                    }
                    if (!empty($cookieLocation) && !isset($blocks[CategoryRepository::MOTORS_ID.'_top_links']) && isset($blocks[AdMotorsSolrFieldMapping::MAKE_ID.'_UK'])) {
                        $solrFieldName = AdMotorsSolrFieldMapping::MAKE_ID.'_UK';
                        $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($seoSearchParams, array('item__location' => LocationRepository::COUNTY_ID)));
                        $blocks[$solrFieldName]['facet'] = array('0' => array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categoryId), 'url' => $searchResultUrl)) + $this->getDimensionFacetByField(AdMotorsSolrFieldMapping::CATEGORY_MAKE_ID, 'item__category_id', $blocks[AdMotorsSolrFieldMapping::MAKE_ID.'_UK']['facet_limit'], $searchParams, $this->container, ' AND (a_parent_category_lvl_2_id_i : '.$parentCategoryIds[1].')', true, $cookieLocation);
                        $blocks[$solrFieldName]['repository'] = 'FaEntityBundle:Category';
                        $blocks[$solrFieldName]['search_field_name'] = 'item__category_id';
                        $blocks[$solrFieldName]['first_entry_as_uk'] = true;
                    }
                }
            } else {
                //remove make if cat is not Boats, Farm, Motorbikes, Motorhomes & Caravans.
                if ($rootCategoryId == CategoryRepository::MOTORS_ID && (!isset($parentCategoryIds[1]) || !in_array($parentCategoryIds[1], array(CategoryRepository::MOTORBIKES_ID, CategoryRepository::MOTORHOMES_AND_CARAVANS_ID)))) {
                    unset($blocks[AdMotorsSolrFieldMapping::MAKE_ID]);
                }

                if (in_array($rootCategoryId, array(CategoryRepository::MOTORS_ID, CategoryRepository::ANIMALS_ID))) {
                    foreach ($blocks as $solrFieldName => $block) {
                        $blocksTmpSearchParams = $searchParams;
                        if ($solrFieldName == 'a_m_make_id_i' && isset($blocksTmpSearchParams['search']) && isset($blocksTmpSearchParams['search']['item_motors__model_id'])) {
                            unset($blocksTmpSearchParams['search']['item_motors__model_id']);
                        }
                        if ($block['is_category_specific'] && (!isset($block['first_entry_as_uk']))) {
                            $categoryDimensions = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimesionsByCategoryId($categoryId, $this->container);
                            $categoryDimensions = array_map('strtolower', $categoryDimensions);
                            if (!empty($categoryDimensions) && in_array($block['dimension_name'], $categoryDimensions)) {
                                if (isset($searchParams['search'][$block['search_field_name']]) || !empty($cookieLocation)) {
                                    $blocks[$solrFieldName]['facet'] = $this->getDimensionFacetByField($solrFieldName, $block['search_field_name'], $block['facet_limit'], $blocksTmpSearchParams, $this->container, null, true, $cookieLocation);
                                } else {
                                    $data['facet_fields'][$solrFieldName] = array('limit' => $block['facet_limit'], 'min_count' => 1);
                                }
                                $categoryDimensions = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimesionsByCategoryId($categoryId, $this->container);
                                $categoryDimensions = array_map('strtolower', $categoryDimensions);

                                if (empty($cookieLocation) && ($rootCategoryId == CategoryRepository::ANIMALS_ID || $categoryId == CategoryRepository::MOTORHOMES_ID || $categoryId == CategoryRepository::MOTORHOMES_AND_CARAVANS_CARAVANS_ID || ($rootCategoryId == CategoryRepository::MOTORS_ID && count($parentCategoryIds) == 2))) {
                                    $blocks[$solrFieldName]['show_all_link'] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return array($blocks, $data);
    }

    /**
     * Get blocks.
     *
     * @param integer $categoryId        Category id.
     * @param array   $parentCategoryIds Array of parent categories.
     * @param array   $cookieLocation    Array of location.
     *
     * @return array
     */
    private function getListingBlockParams($categoryId, $parentCategoryIds, $cookieLocation, $seoPageRule, $seoSearchParams)
    {
        $locationFlag = !empty($cookieLocation);
        $blocks = array();
        $rootCategoryId = (isset($parentCategoryIds[0]) ? $parentCategoryIds[0] : null);
        if ($rootCategoryId == CategoryRepository::FOR_SALE_ID) {
            $forsaleTopLinkArray = array();
            if (count($seoPageRule) && isset($seoPageRule['seo_tool_id'])) {
                $forsaleTopLinkArray = $this->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($seoPageRule['seo_tool_id'], $this->container);
            }
            if (count($forsaleTopLinkArray)) {
                $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($seoSearchParams, array('item__location' => LocationRepository::COUNTY_ID)));
                array_unshift($forsaleTopLinkArray, array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categoryId), 'url' => $searchResultUrl));
                $blocks = $blocks + array(
                    CategoryRepository::FOR_SALE_ID.'_top_links' => array(
                        'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-search-list-block'),
                        'is_category_specific' => false,
                        'is_top_links' => true,
                        'facet' => $forsaleTopLinkArray
                    )
                );
            } else {
                if (isset($parentCategoryIds[2])) {
                    $blocks = $blocks+ array(
                        AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID => array(
                            'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-search-list-block'),
                            'search_field_name' => 'item__category_id',
                            'is_category_specific' => true,
                            'is_top_links' => false,
                            'facet_limit' => 19,
                            'repository'  => 'FaEntityBundle:Category',
                            'first_entry_as_uk' => true,
                            'removeOtherParams' => true,
                        )
                    );
                }
            }
        } elseif ($rootCategoryId == CategoryRepository::MOTORS_ID && $locationFlag) {
            $motorTopLinkArray = array();
            if (count($seoPageRule) && isset($seoPageRule['seo_tool_id'])) {
                $motorTopLinkArray = $this->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($seoPageRule['seo_tool_id'], $this->container);
            }
            if (count($motorTopLinkArray)) {
                $heading = $this->get('translator')->trans('Popular Searches', array(), 'frontend-search-list-block');
                if (isset($parentCategoryIds[1]) && in_array($parentCategoryIds[1], array(CategoryRepository::BOATS_ID, CategoryRepository::FARM_ID))) {
                    $heading = $this->get('translator')->trans('Top Manufacturers', array(), 'frontend-search-list-block');
                }
                $blocks =$blocks + array(
                    CategoryRepository::MOTORS_ID.'_top_links' => array(
                        'heading' => $heading,
                        'is_category_specific' => false,
                        'is_top_links' => true,
                        'facet' => $this->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($seoPageRule['seo_tool_id'], $this->container)
                    ));
            } else {
                if (isset($parentCategoryIds[1]) && in_array($parentCategoryIds[1], array(CategoryRepository::BOATS_ID, CategoryRepository::FARM_ID))) {
                    $blocks = $blocks + array(
                        AdMotorsSolrFieldMapping::MANUFACTURER_ID.'_UK' => array(
                            'heading' => $this->get('translator')->trans('Top Manufacturers', array(), 'frontend-search-list-block'),
                            'search_field_name' => 'item_motors__manufacturer_id',
                            'is_category_specific' => true,
                            'is_top_links' => false,
                            'facet_limit' => 19,
                            'repository'  => 'FaEntityBundle:Entity',
                            'first_entry_as_uk' => true,
                        ));
                } else {
                    $blocks = $blocks + array(
                        AdMotorsSolrFieldMapping::MAKE_ID.'_UK' => array(
                            'heading' => $this->get('translator')->trans('Top Makes in UK', array(), 'frontend-search-list-block'),
                            'search_field_name' => 'item_motors__make_id',
                            'is_category_specific' => true,
                            'is_top_links' => false,
                            'facet_limit' => 19,
                            'repository'  => 'FaEntityBundle:Entity',
                            'first_entry_as_uk' => true,
                        ));
                }
            }
        } elseif ($rootCategoryId == CategoryRepository::ANIMALS_ID && $locationFlag) {
            $animalsTopLinkArray = array();
            if (count($seoPageRule) && isset($seoPageRule['seo_tool_id'])) {
                $animalsTopLinkArray = $this->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($seoPageRule['seo_tool_id'], $this->container);
            }
            if (count($animalsTopLinkArray)) {
                $heading = $this->get('translator')->trans('Popular Searches', array(), 'frontend-search-list-block');
                if (isset($parentCategoryIds[2]) && in_array($parentCategoryIds[2], array(CategoryRepository::BIRDS))) {
                    $heading = $this->get('translator')->trans('Top Species', array(), 'frontend-search-list-block');
                }
                $blocks = $blocks + array(
                    CategoryRepository::ANIMALS_ID.'_top_links' => array(
                        'heading' => $heading,
                        'is_category_specific' => false,
                        'is_top_links' => true,
                        'facet' => $this->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($seoPageRule['seo_tool_id'], $this->container)
                    ));
            } elseif (isset($parentCategoryIds[2]) && in_array($parentCategoryIds[2], array(CategoryRepository::BIRDS))) {
                $blocks = $blocks + array(
                    AdAnimalsSolrFieldMapping::SPECIES_ID.'_UK' => array(
                        'heading' => $this->get('translator')->trans('Top Species', array(), 'frontend-search-list-block'),
                        'search_field_name' => 'item_animals__species_id',
                        'is_category_specific' => true,
                        'is_top_links' => false,
                        'facet_limit' => 19,
                        'repository'  => 'FaEntityBundle:Entity',
                        'first_entry_as_uk' => true,
                    ));
            } elseif (isset($parentCategoryIds[2]) && in_array($parentCategoryIds[2], array(CategoryRepository::DOGS_AND_PUPPIES, CategoryRepository::HORSES, CategoryRepository::CATS_AND_KITTENS))) {
                $blocks = $blocks + array(
                    AdAnimalsSolrFieldMapping::BREED_ID.'_UK' => array(
                        'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-search-list-block'),
                        'search_field_name' => 'item_animals__breed_id',
                        'is_category_specific' => true,
                        'is_top_links' => false,
                        'facet_limit' => 19,
                        'repository'  => 'FaEntityBundle:Entity',
                        'first_entry_as_uk' => true,
                    ));               
            }
        } elseif (in_array($rootCategoryId, array(CategoryRepository::PROPERTY_ID, CategoryRepository::JOBS_ID))) {
            $jobPropertyTopLinkArray = array();
            if (count($seoPageRule) && isset($seoPageRule['seo_tool_id'])) {
                $jobPropertyTopLinkArray = $this->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($seoPageRule['seo_tool_id'], $this->container);
            }
            if (count($jobPropertyTopLinkArray)) {
                $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl(array_merge($seoSearchParams, array('item__location' => LocationRepository::COUNTY_ID)));
                array_unshift($jobPropertyTopLinkArray, array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categoryId), 'url' => $searchResultUrl));
                $blocks = $blocks + array(
                    $rootCategoryId.'_top_links' => array(
                        'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-search-list-block'),
                        'is_category_specific' => false,
                        'is_top_links' => true,
                        'facet' => $jobPropertyTopLinkArray
                    )
                );
            } else {
                if (isset($parentCategoryIds[2])) {
                    $blocks = $blocks + array(
                        AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID => array(
                            'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-search-list-block'),
                            'search_field_name' => 'item__category_id',
                            'is_category_specific' => true,
                            'is_top_links' => false,
                            'facet_limit'          => 19,
                            'repository'           => 'FaEntityBundle:Category',
                            'first_entry_as_uk' => true,
                            'removeOtherParams' => true,
                        )
                    );
                }
            }
        }
        
        if ($rootCategoryId == CategoryRepository::ANIMALS_ID) {
            if (isset($parentCategoryIds[2]) && in_array($parentCategoryIds[2], array(CategoryRepository::DOGS_AND_PUPPIES, CategoryRepository::HORSES, CategoryRepository::CATS_AND_KITTENS))) {
                $blocks = $blocks + array(
                    AdAnimalsSolrFieldMapping::BREED_ID => array(
                        'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-search-list-block'),
                        'search_field_name' => 'item_animals__breed_id',
                        'is_category_specific' => true,
                        'is_top_links' => false,
                        'facet_limit'          => ($locationFlag ? 10 : 25),
                        'dimension_name'       => 'breed',
                        'repository'           => 'FaEntityBundle:Entity',
                    ));
            } elseif (isset($parentCategoryIds[2]) && in_array($parentCategoryIds[2], array(CategoryRepository::BIRDS))) {
                $blocks = $blocks + array(
                    AdAnimalsSolrFieldMapping::SPECIES_ID => array(
                        'heading' => $this->get('translator')->trans('Top Species'.(isset($cookieLocation['location_text']) ? ' in '.$cookieLocation['location_text'] : null), array(), 'frontend-search-list-block'),
                        'search_field_name' => 'item_animals__species_id',
                        'is_category_specific' => true,
                        'is_top_links' => false,
                        'facet_limit'          => ($locationFlag ? 10 : 25),
                        'dimension_name'       => 'species',
                        'repository'           => 'FaEntityBundle:Entity',
                    ));
            }
        } elseif($rootCategoryId == CategoryRepository::MOTORS_ID) {
            if (isset($parentCategoryIds[1]) && in_array($parentCategoryIds[1], array(CategoryRepository::BOATS_ID, CategoryRepository::FARM_ID))) {
                $blocks = $blocks + array(
                    AdMotorsSolrFieldMapping::MANUFACTURER_ID => array(
                        'heading' => $this->get('translator')->trans('Top Manufacturers'.(isset($cookieLocation['location_text']) ? ' in '.$cookieLocation['location_text'] : null), array(), 'frontend-search-list-block'),
                        'search_field_name' => 'item_motors__manufacturer_id',
                        'is_category_specific' => true,
                        'is_top_links' => false,
                        'facet_limit'          => ($locationFlag ? 10 : 25),
                        'dimension_name'       => 'manufacturer',
                        'repository'           => 'FaEntityBundle:Entity',
                    ));
            } else {
                $blocks = $blocks + array(
                    AdMotorsSolrFieldMapping::MAKE_ID => array(
                        'heading' => $this->get('translator')->trans('Top Makes', array(), 'frontend-search-list-block'),
                        'search_field_name' => 'item_motors__make_id',
                        'is_category_specific' => true,
                        'is_top_links' => false,
                        'facet_limit'          => ($locationFlag ? 10 : 25),
                        'dimension_name'       => 'make',
                        'repository'           => 'FaEntityBundle:Entity',
                    ));
            }
        }
        $blocks = $blocks + array(
            AdSolrFieldMapping::DOMICILE_ID => array(
                'heading' => $this->get('translator')->trans('Top Counties', array(), 'frontend-search-list-block'),
                'search_field_name' => 'item__location',
                'is_category_specific' => false,
                'is_top_links' => false,
                'facet_limit'          => 10,
                'repository'           => 'FaEntityBundle:Location',
            ),
            AdSolrFieldMapping::TOWN_ID => array(
                'heading' => $this->get('translator')->trans('Top Cities/Towns', array(), 'frontend-search-list-block'),
                'search_field_name' => 'item__location',
                'is_category_specific' => false,
                'is_top_links' => false,
                'facet_limit'          => 20,
                'repository'           => 'FaEntityBundle:Location',
            ),
        );

        return $blocks;
    }

    /**
     * Get facet by parameters.
     *
     * @param string  $solrFieldName   Solr mapping field name.
     * @param string  $searchFieldName Solr search field name.
     * @param integer $facetLimit      Facet limit.
     * @param array   $searchParams    Array of search params.
     * @param object  $container       Container identifier.
     * @param string  $staticFilters   Solr search static filters.
     *
     * @return array
     */
    public function getDimensionFacetByField($solrFieldName, $searchFieldName, $facetLimit, $searchParams, $container, $staticFilters = null, $removeSearchFieldName = true, $cookieLocation = null)
    {
        // Active ads
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        if ($removeSearchFieldName && isset($searchParams['search'][$searchFieldName])) {
            unset($searchParams['search'][$searchFieldName]);
        }

        // static filters.
        if ($staticFilters) {
            $appendStaticfilters = '';
            $appendStaticfilters = $this->getAppendableStaticFilters($searchParams);
            $data['static_filters'] = $staticFilters.$appendStaticfilters;
        }

        if (isset($searchParams['search']['item__category_id']) && $searchParams['search']['item__category_id']) {
            $categoryId                   = $searchParams['search']['item__category_id'];
            $indexableDimensionFieldArray = $this->getRepository('FaEntityBundle:CategoryDimension')->getIndexableDimensionFieldsArrayByCategoryId($categoryId, $this->container);
            $data['query_filters']['item']['category_id'] = $categoryId;

            if (!empty($indexableDimensionFieldArray)) {
                foreach ($indexableDimensionFieldArray as $indexableDimensionField) {
                    if (isset($searchParams['search'][$indexableDimensionField]) && $searchParams['search'][$indexableDimensionField]) {
                        $explodeRes                                            = explode('__', $indexableDimensionField);
                        $data['query_filters'][$explodeRes[0]][$explodeRes[1]] = $searchParams['search'][$indexableDimensionField];
                    }
                }
            }
        }
        if (isset($searchParams['search']['item__location']) && $searchParams['search']['item__location']) {
            $data['query_filters']['item']['location'] = $searchParams['search']['item__location'].'|30';
        }

        $data['facet_fields'][$solrFieldName] = array('limit' => $facetLimit, 'min_count' => 1);
        // initialize solr search manager service and fetch data based of above prepared search options
        $container->get('fa.solrsearch.manager')->init('ad', '', $data);
        
        if (!empty($cookieLocation)) {
            if (isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
                if (isset($searchParams['search']['item__location']) && $searchParams['search']['item__location']) {
                    $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocation['latitude'] . ',' . $cookieLocation['longitude'], 'd' => 30);
                } else {
                    $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocation['latitude'].','.$cookieLocation['longitude']);
                }
                $this->get('fa.solrsearch.manager')->setGeoDistQuery($geoDistParams);
            }
        }
        $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();
        $facetResult  = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);

        $facetArray = array();
        if (isset($facetResult[$solrFieldName]) && !empty($facetResult[$solrFieldName])) {
            $facetArray = get_object_vars($facetResult[$solrFieldName]);
        }

        return $facetArray;
    }


    /**
     * Get profile exposure user.
     *
     * @param array   $searchParams                 Search parameters.
     * @param string  $exposureMiles                Miles.
     * @param array   $shopPackageCategories        Shop package categories.
     * @param boolean $viewedProfileExposureUserIds Skip viewed users.
     *
     * @return array
     */
    private function getProfileExposureUserNew($searchParams, $exposureMiles, $shopPackageCategories = array(), $viewedProfileExposureUserIds)
    {
        $userIds            = array();
        $data               = array();
        $keywords           = (isset($searchParams['search']['keywords']) ? $searchParams['search']['keywords'] : null);
        $page               = 1;
        $recordsPerPage     = 2;

        //set ad criteria to search
        if (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['location']) && $searchParams['query_filters']['item']['location']) {
            list($locationId, $distance) = explode('|', $searchParams['query_filters']['item']['location']);
            if ($exposureMiles === 'national') {
                $additionaldistance = 100000;
            } else {
                $additionaldistance = $exposureMiles;
            }
            $searchParams['query_filters']['item']['location'] = $locationId.'|'.(intval($distance)+intval($additionaldistance));
        }

        $data['select_fields']  = array('item' => array('user_id'));
        $data['query_filters']  = (isset($searchParams['query_filters']) ? $searchParams['query_filters'] : array());
        $data['static_filters'] = ' AND has_profile_exposure:1 AND profile_exposure_miles:'.$exposureMiles;
        if (count($shopPackageCategories)) {
            $data['static_filters'] = $data['static_filters'].' AND (shop_package_category:'.implode(' OR shop_package_category:', $shopPackageCategories).')';
        }

        if (count($viewedProfileExposureUserIds)) {
            $viewedProfileExposureUserIds = array_unique($viewedProfileExposureUserIds);
            if (isset($data['static_filters'])) {
                $data['static_filters'] = $data['static_filters'].' AND -user_id: ("'.implode('" "', $viewedProfileExposureUserIds).'")';
            } else {
                $data['static_filters'] = ' AND -user_id: ("'.implode('" "', $viewedProfileExposureUserIds).'")';
            }
        }

        $data['query_sorter']   = array();
        if (strlen($keywords)) {
            $data['query_sorter']['item']['score'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }
        //$data['query_sorter']['item']['random'] = array('sort_ord' => 'desc', 'field_ord' => 2);
        $data['group_fields'] = array(
            'user_id' => array('limit' => 1),
        );
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad.new', $keywords, $data, $page, $recordsPerPage, 0, true);
        $solrResponse = $solrSearchManager->getSolrResponse();
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseGroupFields($solrResponse);
        if (isset($facetResult['user_id']) && isset($facetResult['user_id']['groups']) && count($facetResult['user_id']['groups'])) {
            $adUsers = $facetResult['user_id']['groups'];
            foreach ($adUsers as $userCnt => $adUser) {
                $adUser = get_object_vars($adUser);
                if (isset($adUser['groupValue']) && $adUser['groupValue']) {
                    $userIds[$userCnt] = array(
                        'id' => $adUser['groupValue'],
                    );
                    if (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['location']) && $searchParams['query_filters']['item']['location']) {
                        $userIds[$userCnt]['location'] = $searchParams['query_filters']['item']['location'];
                    }
                }
            }
        }

        return $userIds;
    }

    /**
     * Get profile exposure user.
     *
     * @param array   $searchParams                 Search parameters.
     * @param string  $exposureMiles                Miles.
     * @param array   $shopPackageCategories        Shop package categories.
     * @param boolean $viewedProfileExposureUserIds Skip viewed users.
     *
     * @return array
     */
    private function getProfileExposureUser($searchParams, $exposureMiles, $shopPackageCategories = array(), $viewedProfileExposureUserIds)
    {
        $userIds            = array();
        $data               = array();
        $keywords           = (isset($searchParams['search']['keywords']) ? $searchParams['search']['keywords'] : null);
        $page               = 1;
        $recordsPerPage     = 2;
        $additionaldistance = 0;

        //set ad criteria to search
        if (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['location']) && $searchParams['query_filters']['item']['location']) {
            list($locationId, $distance) = explode('|', $searchParams['query_filters']['item']['location']);
            if ($exposureMiles === 'national') {
                $additionaldistance = 100000;
            } else {
                $additionaldistance = $exposureMiles;
            }
            $searchParams['query_filters']['item']['location'] = $locationId.'|'.(intval($distance)+intval($additionaldistance));
        }

        $data['select_fields']  = array('item' => array('user_id'));
        $data['query_filters']  = (isset($searchParams['query_filters']) ? $searchParams['query_filters'] : array());
        $data['static_filters'] = ' AND '.AdSolrFieldMapping::HAS_PROFILE_EXPOSURE.':1 AND '.AdSolrFieldMapping::PROFILE_EXPOSURE_MILES.':'.$exposureMiles;
        if (count($shopPackageCategories)) {
            $data['static_filters'] = $data['static_filters'].' AND ('.AdSolrFieldMapping::SHOP_PACKAGE_CATEGORY_ID.':'.implode(' OR '.AdSolrFieldMapping::SHOP_PACKAGE_CATEGORY_ID.':', $shopPackageCategories).')';
        }

        if (count($viewedProfileExposureUserIds)) {
            $viewedProfileExposureUserIds = array_unique($viewedProfileExposureUserIds);
            if (isset($data['static_filters'])) {
                $data['static_filters'] = $data['static_filters'].' AND -'.AdSolrFieldMapping::USER_ID.': ("'.implode('" "', $viewedProfileExposureUserIds).'")';
            } else {
                $data['static_filters'] = ' AND -'.AdSolrFieldMapping::USER_ID.': ("'.implode('" "', $viewedProfileExposureUserIds).'")';
            }
        }

        $data['query_sorter']   = array();
        if (strlen($keywords)) {
            $data['query_sorter']['item']['score'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }
        $data['query_sorter']['item']['random'] = array('sort_ord' => 'desc', 'field_ord' => 2);
        $data['group_fields'] = array(
            AdSolrFieldMapping::USER_ID => array('limit' => 1),
        );
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage, 0, true);
        $solrResponse = $solrSearchManager->getSolrResponse();
        $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseGroupFields($solrResponse);
        if (isset($facetResult[AdSolrFieldMapping::USER_ID]) && isset($facetResult[AdSolrFieldMapping::USER_ID]['groups']) && count($facetResult[AdSolrFieldMapping::USER_ID]['groups'])) {
            $adUsers = $facetResult[AdSolrFieldMapping::USER_ID]['groups'];
            foreach ($adUsers as $userCnt => $adUser) {
                $adUser = get_object_vars($adUser);
                if (isset($adUser['groupValue']) && $adUser['groupValue']) {
                    $userIds[$userCnt] = array(
                        'id' => $adUser['groupValue'],
                    );
                    if (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['location']) && $searchParams['query_filters']['item']['location']) {
                        $userIds[$userCnt]['location'] = $searchParams['query_filters']['item']['location'];
                    }
                }
            }
        }

        return $userIds;
    }

    /**
     * Get profile exposure user ads.
     *
     * @param integer $userId       A Request object.
     * @param array   $searchParams Search parameters.
     * @param object  $rootCategory Category object
     *
     * @return array
     */
    private function getProfileExposureUserAdsNew($userId, $rootCategory, $searchParams)
    {
        $profileExposureUserAds = $this->getProfileExposureUserAdsSolrResultNew($userId, $rootCategory, $searchParams);
        if (!empty($profileExposureUserAds)) {
            if (count($profileExposureUserAds)>=3) {
                $adIds = array();
                foreach ($profileExposureUserAds as $profileExposureUserAd) {
                    $adIds[] = $profileExposureUserAd['id'];
                }
                $profileExposureUserOtherAds = $this->getProfileExposureUserAdsSolrResultNew($userId, $rootCategory, array(), $adIds);
                $i = count($profileExposureUserAds);
                foreach ($profileExposureUserOtherAds as $profileExposureUserOtherAd) {
                    if ($i < 3) {
                        $profileExposureUserAds[$i] = $profileExposureUserOtherAd;
                        $i++;
                    } else {
                        break;
                    }
                }
            }
        }
        return $profileExposureUserAds;
    }

    /**
     * Get profile exposure user ads.
     *
     * @param integer $userId       A Request object.
     * @param array   $searchParams Search parameters.
     *
     * @return array
     */
    private function getProfileExposureUserAds($userId, $searchParams)
    {
        $profileExposureUserAds = $this->getProfileExposureUserAdsSolrResult($userId, $searchParams);
        if (!empty($profileExposureUserAds)) {
            if (count($profileExposureUserAds)>=3) {
                $adIds = array();
                foreach ($profileExposureUserAds as $profileExposureUserAd) {
                    $adIds[] = $profileExposureUserAd[AdSolrFieldMapping::ID];
                }
                $profileExposureUserOtherAds = $this->getProfileExposureUserAdsSolrResult($userId, array(), $adIds);
                $i = count($profileExposureUserAds);
                foreach ($profileExposureUserOtherAds as $profileExposureUserOtherAd) {
                    if ($i < 3) {
                        $profileExposureUserAds[$i] = $profileExposureUserOtherAd;
                        $i++;
                    } else {
                        break;
                    }
                }
            }
        }
        return $profileExposureUserAds;
    }

    /**
     * Get profile exposure user ads.
     *
     * @param integer $userId       A Request object.
     * @param object  $rootCategory Category object
     * @param array   $searchParams Search parameters.
     * @param array   $adIds
     *
     * @return array
     */
    private function getProfileExposureUserAdsSolrResultNew($userId, $rootCategory, $searchParams = array(), $adIds = array())
    {
        $data                 = array();
        $page                 = 1;
        $recordsPerPage       = 3;
        $data['query_sorter'] = array();
        $keywords = null;
        $data['static_filters'] = '';

        if (count($searchParams)) {
            $adRepository = $this->getRepository('FaAdBundle:Ad');

            $repository = $this->getRepository('FaAdBundle:' . 'Ad' . str_replace(' ', '', $rootCategory->getName()));
            $listingFields = $repository->getAdListingFields();
            $data['static_filters']  = (isset($searchParams['search']) ? $this->setDimensionParams($searchParams['search'], $listingFields, $adRepository) : array());

            if(isset($searchParams['query_filters']['item']['distance'])) {
                $data['query_filters']['item']['distance'] = 200;
                $data['query_filters']['item']['location'] = $searchParams['search']['item__location'].'|200';
            } else {
                $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
            }
        } else {
            $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        $data['static_filters']  .= ' AND user_id: "' . $userId . '" AND -is_blocked_ad: true';

        $data['static_filters'] .= ' AND status_id: ' . EntityRepository::AD_STATUS_LIVE_ID;

        if (!empty($adIds)) {
            $data['static_filters'] .= ' AND -id: ('.implode(' ', $adIds).')';
        }

        //set ad criteria to search
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad.new', $keywords, $data, $page, $recordsPerPage, 0, true);

        $solrResponse = $solrSearchManager->getSolrResponse();

        return $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
    }

    /**
     * Get profile exposure user ads.
     *
     * @param integer $userId       A Request object.
     * @param array   $searchParams Search parameters.
     *
     * @return array
     */
    private function getProfileExposureUserAdsSolrResult($userId, $searchParams = array(), $adIds = array())
    {
        $data                 = array();
        $page                 = 1;
        $recordsPerPage       = 3;
        $data['query_sorter'] = array();
        $keywords = null;
        $data['query_filters']['item']['status_id']   = EntityRepository::AD_STATUS_LIVE_ID;
        if (count($searchParams)) {
            //$keywords               = (isset($searchParams['search']['keywords']) ? $searchParams['search']['keywords'] : null);
            $data['query_filters']  = (isset($searchParams['query_filters']) ? $searchParams['query_filters'] : array());
            
            /*if (strlen($keywords)) {
                $data['query_sorter']['item']['score'] = array('sort_ord' => 'desc', 'field_ord' => 1);
            } else {
                $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
            }*/
            if(isset($searchParams['query_filters']['item']['distance'])) {
                $data['query_filters']['item']['distance']=200;
                $data['query_filters']['item']['location']= $searchParams['search']['item__location'].'|200';
                //$data['query_sorter']['item']['geodist'] = array('sort_ord' => 'asc', 'field_ord' => 1);
            } else {
                $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
            } 
        } else {
            $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        $data['query_filters']['item']['user_id'] = $userId;
        if (!empty($adIds)) {
            $data['static_filters'] = ' AND -'.AdSolrFieldMapping::ID.': ('.implode(' ', $adIds).')';
        }
        //set ad criteria to search
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage, 0, true);

        $solrResponse = $solrSearchManager->getSolrResponse();

        return $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
    }

    /**
     * Get shop user by search criteria.
     *
     * @param array   $data    Search parameters.
     * @param Request $request Request object.
     *
     * @return array
     */
    private function getBusinessUserBySearchCriteriaNew($data, $request)
    {
        $shopPackageCategories = array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID);
        $businessExposureMiles = array();
        $userRepository        = $this->getRepository('FaUserBundle:User');

        // for showing profile page.
        $rootCategoryId = 1;
        if (isset($data['search']['item__category_id'])) {
            $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($data['search']['item__category_id'], $this->container);
            if (in_array($rootCategoryId, $shopPackageCategories)) {
                $businessExposureMiles = $this->getRepository('FaPromotionBundle:Package')->getShopPackageProfileExposureUpsellByCategory($data['search']['item__category_id'], $this->container);
            }
        }

        $businessExposureUserDetails = $businessTopExposureUserDetails = array();
        $topBusiness = $this->getRepository('FaCoreBundle:ConfigRule')->getTopBusiness($data['search']['item__category_id'], $this->container);
        $viewedBusinessExposureUserIds = array_filter(explode(',', $request->cookies->get('business_exposure_user_ids_'.$rootCategoryId)));

        if ($topBusiness) {
            $businessExposureTopUser = $userRepository->getTopbusinessUserDetailForAdList($topBusiness, $this->container);
            if(!empty($businessExposureTopUserAds) && isset($businessExposureTopUser[0]['user_id'])) {
                $businessTopExposureUserDetails[] = array(
                    //'businessExposureUserAds' => $businessExposureTopUserAds,
                    'businessUserId'          => $businessExposureTopUser[0]['user_id'],
                    'businessUserDetail'      => $userRepository->getProfileExposureUserDetailForAdList($businessExposureTopUser[0]['user_id'], $this->container),
                );
            }
            $parameters['businessTopExposureUsersDetailsWithoutAd'] = $businessTopExposureUserDetails;
            $viewedBusinessExposureUserIds[] = (!empty($businessExposureTopUser) && isset($businessExposureTopUser[0]['user_id'])) ? $businessExposureTopUser[0]['user_id']:'';
        }

        $businessExposureUsers = $businessExposureUsersWithoutAd = array();

        if (!empty($businessExposureMiles)) {
            $businessExposureMiles = array_unique($businessExposureMiles);
            foreach ($businessExposureMiles as $businessExposureMile) {
                $varBusUsers = $this->getBusinessExposureUser($data, $businessExposureMile, $viewedBusinessExposureUserIds);
                foreach ($varBusUsers as $businessExposureUser) {
                    $businessExposureUsers[] = $businessExposureUser;
                    $viewedBusinessExposureUserIds[] = $businessExposureUser['id'];
                }

                $varBusUsersWithoutAd = $this->getBusinessExposureUserWithoutAd($data, $businessExposureMile);
                foreach ($varBusUsersWithoutAd as $businessExposureUserWithoutAd) {
                    $businessExposureUsersWithoutAd[] = $businessExposureUserWithoutAd;
                }
            }

            if (empty($businessExposureUsers)) {
                $viewedBusinessExposureUserIds = array();
                foreach ($businessExposureMiles as $businessExposureMile) {
                    foreach ($this->getBusinessExposureUser($data, $businessExposureMile, array()) as $businessExposureUser) {
                        $businessExposureUsers[] = $businessExposureUser;
                        $viewedBusinessExposureUserIds[] = $businessExposureUser['id'];
                    }

                    foreach ($this->getBusinessExposureUserWithoutAd($data, $businessExposureMile) as $businessExposureUserWithoutAd) {
                        $businessExposureUsersWithoutAd[] = $businessExposureUserWithoutAd;
                    }
                }
            }

            $response = new Response();
            $response->headers->setCookie(new Cookie('business_exposure_user_ids_'.$rootCategoryId, implode(',', $viewedBusinessExposureUserIds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
            $response->sendHeaders();

            if (!empty($businessExposureUsers)) {
                shuffle($businessExposureUsers);
                $businessPageLimit = 14;
                for ($i = 0; $i < $businessPageLimit; $i++) {
                    if (isset($businessExposureUsers[$i])) {
                        $businessExposureUser = $businessExposureUsers[$i];
                        $businessExposureUserAds = $this->getProfileExposureUserAdsNew($businessExposureUser[UserShopDetailSolrFieldMapping::ID], $this->getRepository('FaEntityBundle:Category')->find($rootCategoryId), $data);
                        if(!empty($businessExposureUserAds)) {
                            $businessExposureUserDetails[] = array(
                                'businessExposureUserAds' => $this->formatAdsForFeaturedBusiness($businessExposureUserAds, $data['search']['item__category_id'], $rootCategoryId),
                                'businessUserId'          => $businessExposureUser[UserShopDetailSolrFieldMapping::ID],
                                'businessUserDetail'      => $userRepository->getProfileExposureUserDetailForAdList($businessExposureUser[UserShopDetailSolrFieldMapping::ID], $this->container),
                            );
                        }
                    }
                }
                if ($businessExposureUserDetails) {
                    $businessExposureUserDetails = array_map("unserialize", array_unique(array_map("serialize", $businessExposureUserDetails)));
                }
            }
        }

        $parameters['businessExposureUsersDetails'] = $businessExposureUserDetails;

        $businessExposureUserDetailsWithoutAd = array();
        if (!empty($businessExposureUsersWithoutAd)) {
            shuffle($businessExposureUsersWithoutAd);
            foreach ($businessExposureUsersWithoutAd as $businessExposureUserWithoutAd) {
                $businessExposureUserDetailsWithoutAd[] = array(
                    'businessUserId'          => $businessExposureUserWithoutAd[UserShopDetailSolrFieldMapping::ID],
                    'businessUserDetail'      => $userRepository->getProfileExposureUserDetailForAdList($businessExposureUserWithoutAd[UserShopDetailSolrFieldMapping::ID], $this->container),
                );
            }
            if ($businessExposureUserDetailsWithoutAd) {
                $businessExposureUserDetailsWithoutAd = array_map("unserialize", array_unique(array_map("serialize", $businessExposureUserDetailsWithoutAd)));
            }
        }


        $parameters['businessExposureUsersDetailsWithoutAd'] = $businessExposureUserDetailsWithoutAd;

        return $parameters;
    }

    /**
     * Get shop user by search criteria.
     *
     * @param array   $data    Search parameters.
     * @param Request $request Request object.
     *
     * @return array
     */
    private function getBusinessUserBySearchCriteria($data, $request)
    {
        $shopPackageCategories = array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID);
        $businessExposureUsers = array();
        $businessExposureMiles = array();
        $categoryId            = (isset($data['query_filters']) && isset($data['query_filters']['item']['category_id']) && $data['query_filters']['item']['category_id'] ? $data['query_filters']['item']['category_id'] : null);

        // for showing profile page.
        if (isset($data['search']['item__category_id'])) {
            $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($data['search']['item__category_id'], $this->container);
            if (in_array($rootCategoryId, $shopPackageCategories)) {
                $businessExposureMiles = $this->getRepository('FaPromotionBundle:Package')->getShopPackageProfileExposureUpsellByCategory($data['search']['item__category_id'], $this->container);
            }
        }
        
        $businessExposureUserDetails = $businessTopExposureUserDetails = array();
        $topBusiness = $this->getRepository('FaCoreBundle:ConfigRule')->getTopBusiness($data['search']['item__category_id'], $this->container);
        $viewedBusinessExposureUserIds = array_filter(explode(',', $request->cookies->get('business_exposure_user_ids_'.$rootCategoryId)));

        if ($topBusiness) {
            $businessExposureTopUser = $this->getRepository('FaUserBundle:User')->getTopbusinessUserDetailForAdList($topBusiness, $this->container);           
            //$businessExposureTopUserAds = $this->getProfileExposureUserAds($businessExposureTopUser[0]['user_id'], $data);
            if(!empty($businessExposureTopUserAds) && isset($businessExposureTopUser[0]['user_id'])) {
                $businessTopExposureUserDetails[] = array(
                    //'businessExposureUserAds' => $businessExposureTopUserAds,
                    'businessUserId'          => $businessExposureTopUser[0]['user_id'],
                    'businessUserDetail'      => $this->getRepository('FaUserBundle:User')->getProfileExposureUserDetailForAdList($businessExposureTopUser[0]['user_id'], $this->container),
                );
            }
            $parameters['businessTopExposureUsersDetailsWithoutAd'] = $businessTopExposureUserDetails;
            $viewedBusinessExposureUserIds[] = (!empty($businessExposureTopUser) && isset($businessExposureTopUser[0]['user_id'])) ? $businessExposureTopUser[0]['user_id']:'';            
        }
       
        $businessExposureUsers = $businessExposureUsersWithoutAd = array();
        
        if (!empty($businessExposureMiles)) {
            $businessExposureMiles = array_unique($businessExposureMiles);           
            foreach ($businessExposureMiles as $businessExposureMile) {
                $varBusUsers = $this->getBusinessExposureUser($data, $businessExposureMile, $viewedBusinessExposureUserIds);                
                foreach ($varBusUsers as $businessExposureUser) {
                    $businessExposureUsers[] = $businessExposureUser;
                    $viewedBusinessExposureUserIds[] = $businessExposureUser['id'];
                }
                
                $varBusUsersWithoutAd = $this->getBusinessExposureUserWithoutAd($data, $businessExposureMile);                
                foreach ($varBusUsersWithoutAd as $businessExposureUserWithoutAd) {
                    $businessExposureUsersWithoutAd[] = $businessExposureUserWithoutAd;
                }
            }
            
            if (empty($businessExposureUsers)) {
                $viewedBusinessExposureUserIds = array();
                foreach ($businessExposureMiles as $businessExposureMile) {
                    foreach ($this->getBusinessExposureUser($data, $businessExposureMile, array()) as $businessExposureUser) {
                        $businessExposureUsers[] = $businessExposureUser;
                        $viewedBusinessExposureUserIds[] = $businessExposureUser['id'];
                    }
                    
                    foreach ($this->getBusinessExposureUserWithoutAd($data, $businessExposureMile) as $businessExposureUserWithoutAd) {
                        $businessExposureUsersWithoutAd[] = $businessExposureUserWithoutAd;
                    }
                }
            }
            
            $response = new Response();
            $response->headers->setCookie(new Cookie('business_exposure_user_ids_'.$rootCategoryId, implode(',', $viewedBusinessExposureUserIds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
            $response->sendHeaders();

            if (!empty($businessExposureUsers)) {
                shuffle($businessExposureUsers);               
                //$businessPageLimit = $this->getRepository('FaCoreBundle:ConfigRule')->getBusinessPageSlots($categoryId, $this->container);
                $businessPageLimit = 14;
                for ($i = 0; $i < $businessPageLimit; $i++) {
                    if (isset($businessExposureUsers[$i])) {                        
                        $businessExposureUser = $businessExposureUsers[$i];
                        $businessExposureUserAds = $this->getProfileExposureUserAds($businessExposureUser[UserShopDetailSolrFieldMapping::ID], $data);                        
                       
                        /*$businessExposureUserDetails[] = array(
                            'user_id' => $businessExposureUser[UserShopDetailSolrFieldMapping::ID],
                            'company_welcome_message' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::COMPANY_WELCOME_MESSAGE]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::COMPANY_WELCOME_MESSAGE] : null),
                            'about_us' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::ABOUT_US]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::ABOUT_US] : null),
                            'company_logo' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::USER_COMPANY_LOGO_PATH]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::USER_COMPANY_LOGO_PATH] : null),
                            'status_id' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::USER_STATUS_ID]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::USER_STATUS_ID] : null),
                            'user_name' => (isset($businessExposureUser[UserShopDetailSolrFieldMapping::USER_PROFILE_NAME]) ? $businessExposureUser[UserShopDetailSolrFieldMapping::USER_PROFILE_NAME] : null),
                        );*/
                        if(!empty($businessExposureUserAds)) {
                            $businessExposureUserDetails[] = array(
                                'businessExposureUserAds' => $businessExposureUserAds,
                                'businessUserId'          => $businessExposureUser[UserShopDetailSolrFieldMapping::ID],
                                'businessUserDetail'      => $this->getRepository('FaUserBundle:User')->getProfileExposureUserDetailForAdList($businessExposureUser[UserShopDetailSolrFieldMapping::ID], $this->container),                          
                            );
                        }
                    }
                }                
                if ($businessExposureUserDetails) {
                    $businessExposureUserDetails = array_map("unserialize", array_unique(array_map("serialize", $businessExposureUserDetails)));
                }
            }
        }
        
        $parameters['businessExposureUsersDetails'] = $businessExposureUserDetails;
        
        $businessExposureUserDetailsWithoutAd = array();
        if (!empty($businessExposureUsersWithoutAd)) {
            shuffle($businessExposureUsersWithoutAd);
            foreach ($businessExposureUsersWithoutAd as $businessExposureUserWithoutAd) {
                $businessExposureUserDetailsWithoutAd[] = array(
                    'businessUserId'          => $businessExposureUserWithoutAd[UserShopDetailSolrFieldMapping::ID],
                    'businessUserDetail'      => $this->getRepository('FaUserBundle:User')->getProfileExposureUserDetailForAdList($businessExposureUserWithoutAd[UserShopDetailSolrFieldMapping::ID], $this->container),
                );
             }
             if ($businessExposureUserDetailsWithoutAd) {
                 $businessExposureUserDetailsWithoutAd = array_map("unserialize", array_unique(array_map("serialize", $businessExposureUserDetailsWithoutAd)));
            }
        }
    
    
        $parameters['businessExposureUsersDetailsWithoutAd'] = $businessExposureUserDetailsWithoutAd;
    

        return $parameters;
    }

    /**
     * Get profile exposure user.
     *
     * @param array   $searchParams                  Search parameters.
     * @param string  $exposureMiles                 Miles.
     * @param array   $viewedBusinessExposureUserIds Array of viewed user ids.
     *
     * @return array
     */
    private function getBusinessExposureUser($searchParams, $exposureMiles, $viewedBusinessExposureUserIds = array())
    {
        $data               = array();
        $keywords           = (isset($searchParams['search']['keywords']) ? $searchParams['search']['keywords'] : null);
        $categoryId         = (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['category_id']) && $searchParams['query_filters']['item']['category_id'] ? $searchParams['query_filters']['item']['category_id'] : null);
        $page               = 1;
        $recordsPerPage     = $this->getRepository('FaCoreBundle:ConfigRule')->getBusinessPageSlots($categoryId, $this->container);
        $additionaldistance = $distance = 0; 
        $data['static_filters'] = '';
        //set ad criteria to search
        if (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['location']) && $searchParams['query_filters']['item']['location']!='') {
            //list($locationId, $distance) = explode('|', $searchParams['query_filters']['item']['location']);
            $varExplodeLoc = explode('|', $searchParams['query_filters']['item']['location']);
            if (!empty($varExplodeLoc)) {
                if (isset($varExplodeLoc[0])  && $varExplodeLoc[0]!='') {
                    $locationId = $varExplodeLoc[0];
                }
                if (isset($varExplodeLoc[1]) && $varExplodeLoc[1]!='') {
                    $distance = intval($varExplodeLoc[1]);
                }
            }
            
            if ($exposureMiles === 'national') {
                $additionaldistance = 100000;
            } else {
                $additionaldistance = intval($exposureMiles);
            }
            $data['query_filters']['user_shop_detail']['location'] = $locationId.'|'.($distance+$additionaldistance);
         }

         //set ad criteria to search
         $data['static_filters'] .= ' AND '.UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_MILES.':[0 TO '.$exposureMiles.']';
         
        
        if ($categoryId) {
            $srchCateIds = $this->getRepository('FaEntityBundle:Category')->getNestedChildrenIdsByCategoryId($categoryId,$this->container);
            if(!empty($srchCateIds)) {
                $data['static_filters'] .= ' AND (';
                $varFirstRec = 1;
                foreach ($srchCateIds as $srchCateId)  {
                    if($varFirstRec!=1) {
                        $data['static_filters'] .= ' OR ';
                    }
                    $data['static_filters'] .= UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_CATEGORY_ID.':'.$srchCateId;
                    $varFirstRec = $varFirstRec+1;
                }
                $data['static_filters'] .= ')';
            }
            //$data['query_filters']['user_shop_detail']['profile_exposure_category_id'] = $categoryId;
            //$data['static_filters'] .= ' AND '.UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_CATEGORY_ID.': ("'.implode('" "', $srchCateIds).'")';
        }
        
        $data['static_filters'] .= ' AND '.UserShopDetailSolrFieldMapping::USER_LIVE_ADS_COUNT.': [1 TO *]';
        
        if (!empty($viewedBusinessExposureUserIds)) {
            $viewedBusinessExposureUserIds = array_unique($viewedBusinessExposureUserIds);
            if (isset($data['static_filters'])) {
                $data['static_filters'] = $data['static_filters'].' AND -'.UserShopDetailSolrFieldMapping::ID.': ("'.implode('" "', $viewedBusinessExposureUserIds).'")';
            } else {
                $data['static_filters'] = ' AND -'.UserShopDetailSolrFieldMapping::ID.': ("'.implode('" "', $viewedBusinessExposureUserIds).'")';
            }
        }

        $data['query_sorter']   = array();
        if (strlen($keywords)) {
            $data['query_sorter']['user_shop_detail']['score'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }
        $data['query_sorter']['user_shop_detail']['random'] = array('sort_ord' => 'desc', 'field_ord' => 2);
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('user.shop.detail', $keywords, $data, $page, 24, 0, true);
        $solrResponse = $solrSearchManager->getSolrResponse();
        $result = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
        
        return $result;
    }
    
    /**
     * Get profile exposure user.
     *
     * @param array   $searchParams                  Search parameters.
     * @param string  $exposureMiles                 Miles.
     * @param array   $viewedBusinessExposureUserIds Array of viewed user ids.
     *
     * @return array
     */
    private function getBusinessExposureUserWithoutAd($searchParams, $exposureMiles)
    {
        $data               = array();
        $keywords           = (isset($searchParams['search']['keywords']) ? $searchParams['search']['keywords'] : null);
        $categoryId         = (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['category_id']) && $searchParams['query_filters']['item']['category_id'] ? $searchParams['query_filters']['item']['category_id'] : null);
        $page               = 1;

        $additionaldistance = $distance = 0;
        $data['static_filters'] = '';
        //set ad criteria to search
        if (isset($searchParams['query_filters']) && isset($searchParams['query_filters']['item']['location']) && $searchParams['query_filters']['item']['location']!='') {
            //list($locationId, $distance) = explode('|', $searchParams['query_filters']['item']['location']);
            $varExplodeLoc = explode('|', $searchParams['query_filters']['item']['location']);
            if (!empty($varExplodeLoc)) {
                if (isset($varExplodeLoc[0])  && $varExplodeLoc[0]!='') {
                    $locationId = $varExplodeLoc[0];
                }
                if (isset($varExplodeLoc[1]) && $varExplodeLoc[1]!='') {
                    $distance = intval($varExplodeLoc[1]);
                }
            }
            
            if ($exposureMiles === 'national') {
                $additionaldistance = 100000;
            } else {
                $additionaldistance = intval($exposureMiles);
            }
            $data['query_filters']['user_shop_detail']['location'] = $locationId.'|'.($distance+$additionaldistance);
        }
        
        //set ad criteria to search
        $data['static_filters'] .= ' AND '.UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_MILES.':[0 TO '.$exposureMiles.']';
        
        
        if ($categoryId) {
            $srchCateIds = $this->getRepository('FaEntityBundle:Category')->getNestedChildrenIdsByCategoryId($categoryId,$this->container);
            if(!empty($srchCateIds)) {
                $data['static_filters'] .= ' AND (';
                $varFirstRec = 1;
                foreach ($srchCateIds as $srchCateId)  {
                    if($varFirstRec!=1) {
                        $data['static_filters'] .= ' OR ';
                    }
                    $data['static_filters'] .= UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_CATEGORY_ID.':'.$srchCateId;
                    $varFirstRec = $varFirstRec+1;
                }
                $data['static_filters'] .= ')';
            }
            //$data['query_filters']['user_shop_detail']['profile_exposure_category_id'] = $categoryId;
            //$data['static_filters'] .= ' AND '.UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_CATEGORY_ID.': ("'.implode('" "', $srchCateIds).'")';
        }
        
        $data['static_filters'] .= ' AND '.UserShopDetailSolrFieldMapping::ABOUT_US.': [* TO *]';
        
        $data['static_filters'] .= ' AND '.UserShopDetailSolrFieldMapping::USER_LIVE_ADS_COUNT.': 0';
        
        $data['query_sorter']   = array();
        if (strlen($keywords)) {
            $data['query_sorter']['user_shop_detail']['score'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }
        $data['query_sorter']['user_shop_detail']['random'] = array('sort_ord' => 'desc', 'field_ord' => 2);
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('user.shop.detail', $keywords, $data, $page, 24, 0, true);
        $solrResponse = $solrSearchManager->getSolrResponse();
        $result = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);

        return $result;
    }

    /**
     * Load children categories based on parent.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxLeftSearchLoadChildrenCategoriesAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $searchParams = unserialize($request->get('searchParams'));
            $facetResult  = $this->getRepository('FaEntityBundle:Category')->getCategoryFacetBySearchParams($searchParams, $this->container);

            $parameters = array('searchParams' => $searchParams, 'facetResult' => $facetResult);
            return $this->render('FaAdBundle:AdList:leftSearchCategoryLinksMobile.html.twig', $parameters);
        }

        return new Response();
    }

    /**
     * This action is used for create user half account.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function createAlertBlockAction(Request $request)
    {
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserHalfAccountEmailOnlyType::class, null, array('method' => 'POST'));

        if ($request->isXmlHttpRequest() && 'POST' === $request->getMethod() && $request->get('is_form_load', null) == null) {
            if ($formManager->isValid($form)) {
                $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData()));
                return new JsonResponse(array('success' => '1', 'user_id' => $user->getId()));
            } else {
                return new JsonResponse(array('success' => '', 'user_id' => '', 'errorMessage' => 'Please enter valid email address.'));
            }
        }

        $parameters = array('form' => $form->createView());
        return $this->render('FaAdBundle:AdList:createAlertBlock.html.twig', $parameters);
    }

    /**
     * This action is used for fetch expanded distance count.
     *
     * @param Request $request A Request object.
     * @param Array   $keyword     keyword array.
     * @param Array   $data        data array.
     * @param Integer $resultCount result count.
     * @param Integer $nextMiles   next miles.
     *
     * @return Response A Response object.
     */
    public function getExpandDistanceAdCount($request, $keywords, $data, $resultCount, $nextMiles)
    {
        return 0;
        $currentUrl  = $request->getUri();
        $cacheKey    = 'getExpandDistanceAdCount_'.md5($currentUrl);
        $cachedValue = CommonManager::getCacheVersion($this->container, $cacheKey);

        if ($cachedValue !== false) {
            return $cachedValue;
        }

        $dataNext = $data;
        $dataNext['search']['item__distance'] = $nextMiles;
        $dataNext['query_filters']['item']['distance'] = $nextMiles;
        $dataNext['query_filters']['item']['location'] = $dataNext['search']['item__location'].'|'.$nextMiles;

        // initialize solr search manager service and fetch data based of above prepared search options
        $this->get('fa.solrsearch.manager')->init('ad', $keywords, $dataNext, 1, 10, 0, true);
        $solrResponseNext = $this->get('fa.solrsearch.manager')->getSolrResponse();

        // fetch result set from solr
        $resultNext             = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponseNext);
        $expandMilesresultCount = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponseNext);
        $expandMilesresultCount = $expandMilesresultCount - $resultCount;

        CommonManager::setCacheVersion($this->container, $cacheKey, $expandMilesresultCount, 600);

        return $expandMilesresultCount;
    }

    /**
     * This action is used for get next miles
     *
     * @param Integer $currentDistance integer
     *
     * @return Integer.
     */
    public function getNextMiles($currentDistance)
    {
        return 0;
        $nextMiles = 0;
        $milesArray = $this->getRepository('FaEntityBundle:Location')->getDistanceOptionsArray($this->container);

        if ($milesArray && isset($currentDistance)) {
            $index = array_search($currentDistance, array_keys($milesArray));
            $index = $index + 1;
            $count = 0;
            foreach ($milesArray as $key => $value) {
                if ($count == $index) {
                    $nextMiles = $key;
                }
                $count++;
            }
        }
        return $nextMiles;
    }

    /**
     * This action is used for get page url.
     *
     * @param Request $request object
     *
     * @return String.
     */
    public function getPageUrl($request)
    {
        $pageUrl = $request->getPathInfo();
        if ($request->getQueryString() != '') {
            $pageUrl .= '?' . $request->getQueryString();
        }

        if ($pageUrl) {
            $pageUrlArray = array_filter(explode('/', $pageUrl));

            if ($pageUrlArray && is_array($pageUrlArray) && count($pageUrlArray) > 0) {
                $count = 1;
                $pageUrl = '';
                foreach ($pageUrlArray as $key => $value) {
                    if ($count > 1) {
                        $pageUrl = $pageUrl . '/' . $value;
                    }
                    $count++;
                }
                $pageUrl = trim($pageUrl, '/');
            }
        }

        return $pageUrl;
    }

    /**
     * This action is used to update feed ad view count.
     *
     * @param Request $request object
     *
     * @return String.
     */
    public function ajaxUpdateFeedAdViewCountAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $adId = $request->get('adId');
            $createdAt = CommonManager::getTimeStampFromStartDate(date("Y-m-d"));
            $adDetail = $this->getRepository('FaAdFeedBundle:AdFeed')->findOneByAd($adId);
            $adFeedSiteId = $adDetail->getRefSiteId();
            $adFeedClick = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:AdFeedClickReportDaily')->findOneBy(array('ad_id' => $adId, 'ad_feed_site_id' => $adFeedSiteId, 'created_at' => $createdAt));

            $this->historyEntityManager = $this->container->get('doctrine')->getManager('history');

            if (!$adFeedClick) {
                $adFeedClick = new AdFeedClickReportDaily();
                $adFeedClick->setAdId($adId);
                $adFeedClick->setAdFeedSiteId($adFeedSiteId);
                $adFeedClick->setCreatedAt($createdAt);
                $adFeedClick->setView('1');

                $this->historyEntityManager->persist($adFeedClick);
                $this->historyEntityManager->flush($adFeedClick);
            } else {
                $adFeedClick->setAdId($adId);
                $adFeedClick->setAdFeedSiteId($adFeedSiteId);
                $adFeedClick->setCreatedAt($createdAt);
                $adFeedClick->setView($adFeedClick->getView() + 1);
                $this->historyEntityManager->persist($adFeedClick);
                $this->historyEntityManager->flush($adFeedClick);
            }
        }

        return new JsonResponse();
    }

    public function getAppendableStaticFilters($searchParams)
    {
        $appendQueryFilters = '';
        if (isset($searchParams['search']['item_motors__colour_id']) && $searchParams['search']['item_motors__colour_id']) {
            if (!empty($searchParams['search']['item_motors__colour_id'])) {
                $itemMotorsColourIds = $searchParams['search']['item_motors__colour_id'];
                $appendQueryFilters .= ' AND (';
                //$itemMotorsColourIds = implode(',',$searchParams['search']['item_motors__colour_id']);
                foreach ($itemMotorsColourIds as $itemMotorsColourId) {
                    $appendQueryFilters .= "(a_m_colour_id_i : (".$itemMotorsColourId.")) OR ";
                }
                $appendQueryFilters = rtrim($appendQueryFilters, " OR ");
                $appendQueryFilters .= ')';
            }
        }

        if (isset($searchParams['search']['item_motors__body_type_id']) && $searchParams['search']['item_motors__body_type_id']) {
            if (!empty($searchParams['search']['item_motors__body_type_id'])) {
                $itemMotorsBodyTypeIds = $searchParams['search']['item_motors__body_type_id'];
                $appendQueryFilters .= ' AND (';
                //$itemMotorsColourIds = implode(',',$searchParams['search']['item_motors__colour_id']);
                foreach ($itemMotorsBodyTypeIds as $itemMotorsBodyTypeId) {
                    $appendQueryFilters .= "(a_m_body_type_id_i : (".$itemMotorsBodyTypeId.")) OR ";
                }
                $appendQueryFilters = rtrim($appendQueryFilters, " OR ");
                $appendQueryFilters .= ')';
            }
        }

        if (isset($searchParams['search']['item_motors__fuel_type_id']) && $searchParams['search']['item_motors__fuel_type_id']) {
            if (!empty($searchParams['search']['item_motors__fuel_type_id'])) {
                $itemMotorsFuelTypeIds = $searchParams['search']['item_motors__fuel_type_id'];
                $appendQueryFilters .= ' AND (';
                //$itemMotorsColourIds = implode(',',$searchParams['search']['item_motors__colour_id']);
                foreach ($itemMotorsFuelTypeIds as $itemMotorsFuelTypeId) {
                    $appendQueryFilters .= "(a_m_fuel_type_id_i : (".$itemMotorsFuelTypeId.")) OR ";
                }
                $appendQueryFilters = rtrim($appendQueryFilters, " OR ");
                $appendQueryFilters .= ')';
            }
        }

        if (isset($searchParams['search']['item_motors__reg_year']) && $searchParams['search']['item_motors__reg_year']) {
            if (!empty($searchParams['search']['item_motors__reg_year'])) {
                $itemMotorsRegYears = $searchParams['search']['item_motors__reg_year'];
                $appendQueryFilters .= ' AND (';
                //$itemMotorsColourIds = implode(',',$searchParams['search']['item_motors__colour_id']);
                foreach ($itemMotorsRegYears as $itemMotorsRegYear) {
                    $appendQueryFilters .= "(a_m_reg_year_s : (".$itemMotorsRegYear.")) OR ";
                }
                $appendQueryFilters = rtrim($appendQueryFilters, " OR ");
                $appendQueryFilters .= ')';
            }
        }

        if (isset($searchParams['search']['item_motors__transmission_id']) && $searchParams['search']['item_motors__transmission_id']) {
            if (!empty($searchParams['search']['item_motors__transmission_id'])) {
                $itemMotorsTransmissionIds = $searchParams['search']['item_motors__transmission_id'];
                $appendQueryFilters .= ' AND (';
                //$itemMotorsColourIds = implode(',',$searchParams['search']['item_motors__colour_id']);
                foreach ($itemMotorsTransmissionIds as $itemMotorsTransmissionId) {
                    $appendQueryFilters .= "(a_m_transmission_id_i : (".$itemMotorsTransmissionId.")) OR ";
                }
                $appendQueryFilters = rtrim($appendQueryFilters, " OR ");
                $appendQueryFilters .= ')';
            }
        }

        /*if(isset($searchParams['search']['item_motors__mileage_range']) && $searchParams['search']['item_motors__mileage_range']) {
            if (count($searchParams['search']['item_motors__mileage_range'])) {
                $itemMotorsMileageRanges = $searchParams['search']['item_motors__mileage_range'];
                $appendQueryFilters .= ' AND (';
                //$itemMotorsColourIds = implode(',',$searchParams['search']['item_motors__colour_id']);
                foreach($itemMotorsMileageRanges as $itemMotorsMileageRange) {
                    $appendQueryFilters .= "(a_m_mileage_d : (".$itemMotorsMileageRange.")) OR ";
                }
                $appendQueryFilters = rtrim($appendQueryFilters," OR ");
                $appendQueryFilters .= ')';

            }
        }*/

        if (isset($searchParams['search']['item_motors__condition_id']) && $searchParams['search']['item_motors__condition_id']) {
            if (!empty($searchParams['search']['item_motors__condition_id'])) {
                $itemMotorsConditionIds = $searchParams['search']['item_motors__condition_id'];
                $appendQueryFilters .= ' AND (';
                //$itemMotorsColourIds = implode(',',$searchParams['search']['item_motors__colour_id']);
                foreach ($itemMotorsConditionIds as $itemMotorsConditionId) {
                    $appendQueryFilters .= "(a_m_condition_id_i : (".$itemMotorsConditionId.")) OR ";
                }
                $appendQueryFilters = rtrim($appendQueryFilters, " OR ");
                $appendQueryFilters .= ')';
            }
        }
        
        return $appendQueryFilters;
    }

    /**
     * Find recommended slots.
     *
     * @param array   $data           Search parameters.
     * @param string  $keywords       Keywords.
     * @param array   $page           Page.
     * @param boolean $mapFlag        Boolean flag for map.
     * @param Request $request        Request object.
     * @param integer $rootCategoryId Root category id.
     *
     * @return array
     */
    private function getRecommendedSlot($data, $keywords = null, $page = 1, $mapFlag = false, $request, $rootCategoryId)
    {
        $recommendeSlotResult = array();
        if (isset($data['search']['item__category_id']) && $data['search']['item__category_id']) {
            $recommendeSlotResult = $this->getRepository('FaEntityBundle:CategoryRecommendedSlot')->getCatRecommendedSlotSearchlistArrByCategoryId($data['search']['item__category_id'], $this->container);
        }
        return $recommendeSlotResult;
    }

    /**
    * Find top ads randomly as defined slots.
    *
    * @param array $data
    *            Search parameters.
    * @param string $keywords
    *            Keywords.
    * @param int $page
    *            Page.
    * @param boolean $mapFlag
    *            Boolean flag for map.
    * @param Request $request
    *            Request object.
    * @param integer $rootCategoryId
    *            Root category id.
    *
    * @return array
    */
    private function getSearchResultBoostedAds($data, $keywords = null, $page = 1, $mapFlag = false, $request, $rootCategoryId)
    {
        $boostedAdResult = $this->getBoostedAds($data, $keywords, $page, $mapFlag, true, $request, $rootCategoryId);

        if (!count($boostedAdResult)) {
            $boostedAdResult = $this->getBoostedAds($data, $keywords, $page, $mapFlag, false, $request, $rootCategoryId);
        }

        return $boostedAdResult;
    }

    /**
     * Find top ads randomly as defined slots.
     *
     * @param array $data
     *            Search parameters.
     * @param string $keywords
     *            Keywords.
     * @param int $page
     *            Page.
     * @param boolean $mapFlag
     *            Boolean flag for map.
     * @param boolean $skipAds
     *            Boolean flag for skip viewed ads.
     * @param Request $request
     *            Request object.
     * @param integer $rootCategoryId
     *            Root category id.
     *
     * @return array
     */
    private function getBoostedAds($data, $keywords = null, $page = 1, $mapFlag = false, $skipAds = true, $request, $rootCategoryId)
    {
        /** @var \Fa\Bundle\CoreBundle\Manager\SolrSearchManager $solrManager */
        $solrManager = $this->get('fa.solrsearch.manager');

        $boostedAdResult = array();
        if (!$mapFlag && $page == 1) {
            $recordsPerPage = $this->container->getParameter('fa.default.listing_topad_slots');
            $solrManager->init('ad', $keywords, $data, $page, $recordsPerPage, 0, true);
            //print_r($query = $solrManager->getSolrQuery());
            $boostedAdResult = $solrManager->getSolrResponseDocs();
            $boostedAdCount = count($boostedAdResult);

            if ($boostedAdCount) {
                foreach ($boostedAdResult as $boostedAd) {
                    $viewedBoostedAds[] = $boostedAd['id'];
                }
                $viewedBoostedAds = array_unique($viewedBoostedAds);
            }

            $response = new Response();
            $response->headers->setCookie(new Cookie('boosted_ads_search_result_' . $rootCategoryId, implode(',', $viewedBoostedAds), CommonManager::getTimeStampFromEndDate(date('Y-m-d'))));
            $response->sendHeaders();
        }

        return $boostedAdResult;
    }
}
