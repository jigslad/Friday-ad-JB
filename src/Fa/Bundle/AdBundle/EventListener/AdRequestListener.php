<?php


/**
 * This file is part of the AdBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fa\Bundle\AdBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as FaEntityRepo;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;
use Entity\Category;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Fa\Bundle\ContentBundle\Repository\SeoConfigRepository;
use Fa\Bundle\ArchiveBundle\Repository\ArchiveAdRepository;
use Fa\Bundle\ContentBundle\Repository\StaticPageRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * This event listener is used for decide location based route
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdRequestListener
{

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
    }

    /**
     * match urls
     *
     * @param GetResponseEvent $event
     *
     * @return void|boolean
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        CommonManager::extendLoggedInUserSessionLength($this->container);
        
        $request = $event->getRequest();
        // check for session timeout for cart/process and checkout uri
        $uri = $request->getUri();
        
        $supported_images = array('.gif','.jpg','.jpeg','.png');
        
        foreach ($supported_images as $imageExt) {
            if (strpos($uri, $imageExt) !== false && substr($uri, -1) == '/') {
                $uri = substr($uri, 0, -1);
                $response = new RedirectResponse($uri, 301);
                $event->setResponse($response);
            }
        }
        
        // If the ad-detail page url is having an entity in string, then forward to Ad-listings
        /*if ($this->_route($request) == 'ad_detail_page') {
            $request = $this->redirectAdDetailPage($request);
        } elseif ($this->isListingPageRoute($request)) {
            $request = $this->redirectAdListingPage($request);
        }*/
        
        //echo 'location==='.$request->get('location');
        
        //redirect greate-london slug
       if (preg_match('/for-sale\/home-garden\/aids\//', $uri)) { //redirect aids FFR-2083
            $locationUrl = str_replace('for-sale/home-garden/aids/', 'for-sale/home-garden/health/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/business-office\/office\/stationary\//', $uri)) { //redirect aids FFR-2390
            $locationUrl = str_replace('for-sale/business-office/office/stationary/', 'for-sale/business-office/office/stationery/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/([a-z\-]+)\/business-office\/office\/stationary\//', $uri, $matches)) {
            $locationUrl = str_replace('for-sale/'.$matches[1].'/business-office/office/stationary/', 'for-sale/'.$matches[1].'/business-office/office/stationery/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/antiques-collectables\/gramaphones-radiograms\//', $uri)) {
            $locationUrl = str_replace('for-sale/antiques-collectables/gramaphones-radiograms/', 'for-sale/antiques-collectables/gramophones-radiograms/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/([a-z\-]+)\/antiques-collectables\/gramaphones-radiograms\//', $uri, $matches)) {
            $locationUrl = str_replace('for-sale/'.$matches[1].'/antiques-collectables/gramaphones-radiograms/', 'for-sale/'.$matches[1].'/antiques-collectables/gramophones-radiograms/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/community\/whats-on\/entertainment\/restaurants\//', $uri)) { //redirect aids FFR-2410
            $locationUrl = str_replace('community/whats-on/entertainment/restaurants/', 'community/in-your-area/restaurants/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/jobs\/driving-warehouse\//', $uri)) { //FFR-2421
            $locationUrl = str_replace('jobs/driving-warehouse/', 'jobs/automotive-jobs/driver-jobs/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/electronics\/cameras-photography\/non-digital-camera-accessories\//', $uri)) { //FFR-2222
            $locationUrl = str_replace('for-sale/electronics/cameras-photography/non-digital-camera-accessories/', 'for-sale/electronics/cameras-photography/camera-accessories/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/electronics\/cameras-photography\/digital-camera-accessories\//', $uri)) {
            $locationUrl = str_replace('for-sale/electronics/cameras-photography/digital-camera-accessories/', 'for-sale/electronics/cameras-photography/camera-accessories/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/([a-z\-]+)\/electronics\/cameras-photography\/non-digital-camera-accessories\//', $uri, $matches)) {
            $locationUrl = str_replace('for-sale/'.$matches[1].'/electronics/cameras-photography/non-digital-camera-accessories/', 'for-sale/'.$matches[1].'/electronics/cameras-photography/camera-accessories/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/([a-z\-]+)\/electronics\/cameras-photography\/digital-camera-accessories\//', $uri, $matches)) {
            $locationUrl = str_replace('for-sale/'.$matches[1].'/electronics/cameras-photography/digital-camera-accessories/', 'for-sale/'.$matches[1].'/electronics/cameras-photography/camera-accessories/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/adult\/phone-cam-chat\//', $uri)) {
            $locationUrl = str_replace('adult/phone-cam-chat/', 'adult/', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);                                   
        } elseif (preg_match('/car-hire/', $uri)) {
            $uriSplit = explode('/', $uri);
            if (in_array("car-hire", $uriSplit)) {
                $locationUrl = str_replace('car-hire', 'vehicle-hire', $uri);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            }
        } elseif (preg_match('/bristol\/celebrations-special-occasions\/20-years-old-male-prostitute-for-you-16359610/', $uri)) {
            throw new HttpException(410);
        }
        
        

        if (preg_match('/cart\/process/', $uri) || preg_match('/checkout/', $uri)) {
            if ($this->container->get('session')->has('lastActivityTime')) {
                $minutes = round(abs(time() - $this->container->get('session')->get('lastActivityTime')) / 60, 2);
                if ($minutes >= 30) {
                    $url = $this->container->get('router')->generate('logout', array(), true);
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }
            }
            $this->container->get('session')->set('lastActivityTime', time());
        } else {
            $this->container->get('session')->set('lastActivityTime', time());
        }

        $request = $event->getRequest();
        $currentRoute = $event->getRequest()->get('_route');
        $params      = $request->attributes->get('_route_params');
        $request->attributes->set('_route_params', array_merge($params, array('page' => 1)));

        // handle TI redirects
        $tiCacheKey = md5($request->getClientIp().$request->headers->get('User-Agent'));
        $tiCacheVal = CommonManager::getCacheVersion($this->container, 'ti_url_'.$tiCacheKey);
        if ($tiCacheVal && !$request->isXmlHttpRequest()) {
            $request->attributes->set('ti_url', $tiCacheVal);
            $response = new Response();
            $response->headers->setCookie(new Cookie('ti_url', $tiCacheVal, time() + (24 * 3600 * 365)));
            $response->headers->setCookie(new Cookie('new_ti_url', $tiCacheVal, time() + (24 * 3600 * 365)));
            $response->sendHeaders();

            if (!in_array($currentRoute, array('trade_it_redirect_home', 'trade_it_redirect', 'trade_it_redirect_without_slash'))) {
                $tiAdRequestListener = new TiAdRequestListener($this->container);
                $tiReturn = $tiAdRequestListener->onKernelRequest($event);
                if ($tiReturn !== false) {
                    return $tiReturn;
                }
            }
        }
        
        $uri = $request->getUri();        
        
        if ($this->_301($request)) {
            return true;
        }

        /*$tiUrl = $request->get('ti_url');
        if ($tiUrl) {
            $tiRouteName   = null;
            $tiRouteParams = array();
            $urlParams = parse_url($tiUrl);
            if (isset($urlParams['scheme']) && isset($urlParams['host']) && isset($urlParams['path'])) {
                $refererUrl    = str_replace(array($urlParams['scheme'].'://'.$urlParams['host'], $request->getBaseURL()), '', $urlParams['path']);
                try {
                    $tiRouteDetails = $this->container->get('router')->match($refererUrl);

                    if (isset($tiRouteDetails['path']) && $tiRouteDetails['path']) {
                        $tiRouteDetails = $this->container->get('router')->match($tiRouteDetails['path']);
                    }
                    $tiRouteName = $tiRouteDetails['_route'];
                    $unsetFields = array(
                        '_route',
                        '_controller',
                        'path',
                        'permanent',
                        'scheme',
                        'httpPort',
                        'httpsPort',
                    );
                    foreach ($unsetFields as $unsetField) {
                        if (isset($tiRouteDetails[$unsetField])) {
                            unset($tiRouteDetails[$unsetField]);
                        }
                    }
                    $tiRouteParams = $tiRouteDetails;
                } catch (ResourceNotFoundException $e) {
                    $tiRouteName = null;
                }

                if ($tiRouteName) {
                    if ($tiRouteName == 'landing_page_category') {
                        $tiRouteParams['location'] = 'bristol';
                        $tiRouteName = 'landing_page_category_location';
                    } elseif ($tiRouteName == 'fa_frontend_homepage') {
                        $tiRouteParams['location'] = 'bristol';
                        $tiRouteName = 'location_home_page';
                    } elseif (in_array($tiRouteName, array('listing_page', 'location_home_page')) && isset($tiRouteParams['location']) && $tiRouteParams['location'] == 'bristol-south-west') {
                        $tiRouteParams['location'] = 'bristol';
                    } elseif ($tiRouteName == 'ad_detail_page_by_id' || $tiRouteName == 'ad_detail_page') {
                        $adObj = $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('ti_ad_id' => $tiRouteParams['id']));
                        if ($adObj) {
                            $tiRouteParams['id'] = $adObj->getId();
                        }
                    }

                    if (isset($urlParams['query']) && $urlParams['query']) {
                        parse_str($urlParams['query'], $queryParamsArray);
                        $tiRouteParams = array_merge($tiRouteParams, $queryParamsArray);
                    }

                    $tiRouteParams['utm_source'] = 'trade-it-redirect';
                    $tiRouteParams['utm_medium'] = 'referral';
                    $tiRouteParams['utm_campaign'] = $tiUrl;

                    $url = $this->container->get('router')->generate($tiRouteName, $tiRouteParams, true);
                    $url = rtrim($url, '/');
                    $response = new RedirectResponse($url, 301);
                    $event->setResponse($response);
                }
            }
        }*/

        if ($currentRoute == 'landing_page_category' || $currentRoute == 'landing_page_category_location') {
            $catObj = $this->getMatchedCategory($request->get('category_string'));

            if ($catObj && $catObj['id'] == CategoryRepository::ADULT_ID) {
                $location = ($request->get('location') ? $request->get('location') : 'uk');
                $url = $this->container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => $request->get('category_string')), true);
                $event->setResponse(new RedirectResponse($url, 301));
            }

            if (isset($params['path'])) {
                $this->redirectOldUrls(ltrim($params['path'], '/'), 'uk', $request, $event, 'location_home');
            }

            if ($catObj) {
                $request->attributes->set('category_id', $catObj['id']);
            }
        } elseif ($currentRoute ==  'listing_page'|| $currentRoute ==  'motor_listing_page') {
            $queryParams  =  array();
            $searchParams = $request->query->all();
            $redirectString = $request->get('page_string');

            if ($currentRoute ==  'motor_listing_page') {
                $params['path'] = '/'.$request->get('location').'/'.$redirectString.'/';
            }
           
            // to decide old detail page url
            if (isset($params['path']) && $params['path']) {
                if (preg_match('/-[A-Z0-9]{9,10}\/$/', $params['path'], $matches) && isset($matches[0])) {
                    $adRef = str_replace(array('/','-'), '', $matches[0]);
                    $oldAd    = $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('ad_ref' => $adRef));
                    $feedAd    = $this->em->getRepository('FaAdFeedBundle:AdFeed')->findOneBy(array('trans_id' => $adRef));
                    if ($oldAd) {
                        $routeManager = $this->container->get('fa_ad.manager.ad_routing');
                        $url = $routeManager->getDetailUrl($oldAd);
                        $event->setResponse(new RedirectResponse($url, 301));
                    } elseif ($feedAd && $feedAd->getAd()) {
                        $routeManager = $this->container->get('fa_ad.manager.ad_routing');
                        $url = $routeManager->getDetailUrl($feedAd->getAd());
                        $event->setResponse(new RedirectResponse($url, 301));
                    } else {
                        if (strpos($adRef, 'ZP0') === 0) {
                            $url = $this->container->get('router')->generate('landing_page_category', array('category_string' => 'property'), true);
                            $event->setResponse(new RedirectResponse($url, 301));
                        } elseif (strpos($adRef, 'KP') === 0 || strpos($adRef, 'TT') === 0) {
                            $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                            $event->setResponse(new RedirectResponse($url, 301));
                        }
                    }
                }

                if (preg_match('/-N-/', $params['path'])) {
                    $urlString =  explode('/', $params['path']);
                    if (isset($urlString[1])) {
                        $request->attributes->set('location', $urlString[1]);
                        unset($urlString[1]);
                        $pString =  implode('/', $urlString);
                        $request->attributes->set('page_string', ltrim($pString, '/'));
                    }
                }
            }

            //If keyword is numbers only and if it is greater than or equals to 5 digit, then directly search for ad id.
            if (isset($searchParams['keywords']) && preg_match('/(\d{5,})/', $searchParams['keywords'])) {
                $objAd = $this->em->getRepository('FaAdBundle:Ad')->find($searchParams['keywords']);
                if ($objAd) {
                    $routeManager = $this->container->get('fa_ad.manager.ad_routing');
                    $url          = $routeManager->getDetailUrl($objAd);
                    $event->setResponse(new RedirectResponse($url));
                }
            }

            $redirectString = $request->get('page_string');
            $locationId = $this->getLocationId($request, $redirectString);
            
            if (!$locationId) {
                $request->attributes->set('not_found', 1);
            }

            $this->redirectOldUrls($redirectString, $locationId, $request, $event);

            if ($currentRoute ==  'motor_listing_page') {
                $request->attributes->set('not_found', 1);
            }

            foreach ($request->query->all() as $key => $val) {
                if (preg_match('/^(.*)_id$/', $key) || preg_match('/reg_year|mileage_range|engine_size_range/', $key)) {
                    $queryParams[$key] = explode("__", $val);

                    if (preg_match('/^(.*)_id$/', $key)) {
                        $queryParams[$key] = array_map('intval', explode("__", $val));
                    }
                } else {
                    $queryParams[$key] = $val;
                }
            }
            
            $request->attributes->set('finders', array_merge($queryParams, array('item__location' => $locationId)));

            $categoryText = $request->get('page_string');
            $check  = true;
            $adType = null;
            $matches = null;
            $adTypeString = implode('\/|\/', $this->getAdTypeArray());
                       
            $forsaleFlag  = false;

            if (strpos($categoryText, "for-sale") === 0) {
                $forsaleFlag = true;
            }

            if (strpos($categoryText, "property") === 0) {
                $adTypeString = implode('\/|\/', array('wanted', 'offered', 'exchange'));
            }

            while ($check) {
                // handle url for for sale category
                if (preg_match('/'.$adTypeString.'/', $categoryText, $matches) && !$forsaleFlag) {
                    $adType = $matches[0];
                    $categoryText = (preg_replace('/'.$adTypeString.'/', '/', $categoryText));
                    $categoryText = preg_replace('/\/+/', '/', $categoryText);
                } elseif (preg_match('/~^'.$adTypeString.'/', $categoryText, $matches) && $forsaleFlag) {
                    $adType = $matches[0];
                    $categoryText = (preg_replace('/~^'.$adTypeString.'/', '/', $categoryText));
                    $categoryText = preg_replace('/\/+/', '/', $categoryText);
                }

                if (preg_match('/page-\d+\/$/', $categoryText, $matches)) {
                    $page = str_replace(array('page-', '/'), '', $matches[0]);
                    $request->attributes->set('page', $page);
                }

                $categoryText =  substr($categoryText, 0, strrpos($categoryText, '/'));
                
                                
                $catObj = $this->getMatchedCategory($categoryText);
                $this->getCatRedirects($redirectString, $categoryText, $locationId, $request, $event);
                                
                if ($catObj) {
                    $this->getCatRedirects($redirectString, $catObj['full_slug'], $locationId, $request, $event);
                    /*if($catObj['status']!=1) {
                        $this->redirectParentCatUrls($redirectString,$catObj['id'], $locationId, $request, $event);
                    } */
                    
                    $check = false;
                    $request->attributes->set('cat_full_slug', $catObj['full_slug']);
                    //$categoryText = $catObj['full_slug'].'/';
                    
                    //$parent   = $this->getFirstLevelParent($catObj['id']);

                    $getDefaultRadius = $this->em->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($searchParams, $this->container);
                    if ($request->get('item__distance')) {
                        $searchParams['item__distance']  =  $request->get('item__distance');
                    } else {
                        $searchParams['item__distance']  =  ($getDefaultRadius)?$getDefaultRadius:'';
                    }

                    /*if (($catObj['id'] == CategoryRepository::MOTORS_ID) || ($parent['id'] == CategoryRepository::MOTORS_ID)) {
                        $queryParams['item__distance']  =  $request->get('item__distance') == '' ? CategoryRepository::MOTORS_DISTANCE : $request->get('item__distance');
                    } else {
                        $queryParams['item__distance']  =  $request->get('item__distance') == '' ? CategoryRepository::OTHERS_DISTANCE : $request->get('item__distance');
                    }*/

                    //check location belongs to area
                    if (preg_match('/^\d+$/', $locationId) && is_null($request->get('item__distance'))) {
                        $isLocationArea = $this->em->getRepository('FaEntityBundle:Location')->find($locationId);
                        if (!empty($isLocationArea) && $isLocationArea && $isLocationArea->getLvl() == '4' && isset($queryParams['item__distance'])) {                            
                            $queryParams['item__distance'] = $queryParams['item__distance']/CategoryRepository::AREA_DISTANCE_DIVISION;
                        }
                    }

                    if (isset($searchParams['item__distance'])) {
                        $request->attributes->set('finders', array_merge($queryParams, array('item__distance' => $searchParams['item__distance'])));
                    }
                    
                    $request->attributes->set('finders', array_merge($queryParams, array('item__category_id' => $catObj['id'], 'item__location' => $locationId)));
                } else {
                    $request->attributes->set('finders', array_merge($queryParams, array('item__location' => $locationId)));
                }

                if (!strpos($categoryText, '/')) {
                    $check = false;
                }
            }
            
            if (!$catObj && $categoryText != 'search') {
                $request->attributes->set('not_found', 1);
            }
            if($catObj) {
                $dimArray = $this->dimensionArray($catObj['full_slug'], $adType, $request, $event);
            } else {
                $dimArray = $this->dimensionArray($categoryText, $adType, $request, $event);
            }
            $request->attributes->set('finders', array_merge_recursive($request->attributes->get('finders'), $dimArray));
        } elseif ($currentRoute ==  'detail_page') {
            return false;
        } elseif ($currentRoute == 'show_business_user_ads' || $currentRoute == "show_business_user_ads_location" || $currentRoute == "show_business_user_ads_page") {
            $queryParams    =  array();
            $searchParams   = $request->query->all();
            $redirectString = $request->get('page_string');
            $locationId     = $this->getLocationId($request);

            $locationId = $locationId ? $locationId : 2;
            foreach ($request->query->all() as $key => $val) {
                if (preg_match('/^(.*)_id$/', $key) || preg_match('/reg_year|mileage_range|engine_size_range/', $key)) {
                    $queryParams[$key] = explode("__", $val);

                    if (preg_match('/^(.*)_id$/', $key)) {
                        $queryParams[$key] = array_map('intval', explode("__", $val));
                    }
                } else {
                    $queryParams[$key] = $val;
                }
            }

            $userId = $this->em->getRepository('FaUserBundle:UserSite')->getUserIdBySlug($request->get('profileNameSlug'), $this->container);
            if ($userId) {
                $queryParams['item__user_id'] = $userId;
            }
            $request->attributes->set('finders', array_merge($queryParams, array('item__location' => $locationId)));

            $categoryText = $request->get('page_string');
            $check  = true;
            $adType = null;
            $matches = null;
            $adTypeString = implode('|\/', $this->getAdTypeArray());

            $forsaleFlag  = false;

            if (strpos($categoryText, "for-sale") === 0) {
                $forsaleFlag = true;
            }

            if (strpos($categoryText, "property") === 0) {
                $adTypeString = implode('|\/', array('wanted', 'offered', 'exchange'));
            }

            while ($check) {
                // handle url for for sale category
                if (preg_match('/'.$adTypeString.'/', $categoryText, $matches) && !$forsaleFlag) {
                    $adType = $matches[0];
                    $categoryText = (preg_replace('/'.$adTypeString.'/', '', $categoryText));
                    $categoryText = preg_replace('/\/+/', '/', $categoryText);
                } elseif (preg_match('/~^'.$adTypeString.'/', $categoryText, $matches) && $forsaleFlag) {
                    $adType = $matches[0];
                    $categoryText = (preg_replace('/~^'.$adTypeString.'/', '', $categoryText));
                    $categoryText = preg_replace('/\/+/', '/', $categoryText);
                }


                if (preg_match('/page-\d+\/$/', $categoryText, $matches)) {
                    $page = str_replace(array('page-', '/'), '', $matches[0]);
                    $request->attributes->set('page', $page);
                }

                $categoryText =  substr($categoryText, 0, strrpos($categoryText, '/'));

                $catObj = $this->getMatchedCategory($categoryText);

                if ($catObj) {
                    $check = false;
                    $request->attributes->set('cat_full_slug', $catObj['full_slug']);

                    $parent   = $this->getFirstLevelParent($catObj['id']);

                    $getDefaultRadius = $this->em->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($queryParams, $this->container);
                    if ($request->get('item__distance')) {
                        $queryParams['item__distance']  =  $request->get('item__distance');
                    } else {
                        $queryParams['item__distance']  =  ($getDefaultRadius)?$getDefaultRadius:'';
                    }

                    /* if (($catObj['id'] == CategoryRepository::MOTORS_ID) || ($parent['id'] == CategoryRepository::MOTORS_ID)) {
                         $queryParams['item__distance']  =  $request->get('item__distance') == '' ? 30 : $request->get('item__distance');
                     } else {
                         $queryParams['item__distance']  =  $request->get('item__distance') == '' ? 15 : $request->get('item__distance');
                     }*/

                    $request->attributes->set('finders', array_merge($queryParams, array('item__category_id' => $catObj['id'], 'item__location' => $locationId)));
                } else {
                    $queryParams['item__distance'] = isset($queryParams['item__distance']) && $queryParams['item__distance'] != null ? $queryParams['item__distance'] : CategoryRepository::OTHERS_DISTANCE;
                    $request->attributes->set('finders', array_merge($queryParams, array('item__location' => $locationId)));
                }

                if (!strpos($categoryText, '/')) {
                    $check = false;
                }
            }

            $dimArray = $this->dimensionArray($categoryText, $adType, $request, $event);


            $request->attributes->set('finders', array_merge_recursive($request->attributes->get('finders'), $dimArray));
        } elseif ($currentRoute ==  'location_home_page') {
            $redirectString = $request->get('location');
            $static_page = $this->em->getRepository('FaContentBundle:StaticPage')->getStaticPageLinkArray($this->container, true);
            if (in_array($redirectString, $static_page)) {
                $request->attributes->set('static_page', 1);
            }

            if ((isset($params['path']) && $params['path']) || (isset($params['location']) && $params['location'])) {
                $location = isset($params['path'])?trim($params['path'], '/'):(isset($params['location'])?trim($params['location'], '/'):'');
                $locationString = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($location, $this->container, true);
                if ($locationString) {
                    $request->attributes->set('location', $locationString);
                    $setCookieValue = $this->em->getRepository('FaEntityBundle:Location')->setLocationInCookie($request, $this->container);

                    $url = $this->container->get('router')->generate('location_home_page', array(
                            'location' => $locationString,
                    ), true);
                    $response = new RedirectResponse($url, 301);
                    $event->setResponse($response);
                }
            } else {
                $redirectString = $request->get('location');
                $this->redirectOldUrls($redirectString, 'uk', $request, $event, 'location_home');
            }
        } elseif ($currentRoute ==  'show_all_towns_by_county') {
            if (isset($params['countySlug']) && $params['countySlug']) {
                $location = isset($params['countySlug'])?trim($params['countySlug'], '/'):'';
                $locationString = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($location, $this->container, true);
                if ($locationString) {
                    $request->attributes->set('location', $locationString);
                    $setCookieValue = $this->em->getRepository('FaEntityBundle:Location')->setLocationInCookie($request, $this->container);
                    $url = $this->container->get('router')->generate('show_all_towns_by_county', array(
                            'countySlug' => $locationString,
                        ), true);
                    $response = new RedirectResponse($url, 301);
                    $event->setResponse($response);
                }
            }
        } else {
            return false;
        }
    }
    
    private function getCatRedirects($redirectString, $categoryText, $locationId, $request, $event, $page = null)
    {
        $url = null;
        
        $redirect = $this->em->getRepository('FaAdBundle:Redirects')->getCategoryRedirects($categoryText, $this->container);
        if ($redirect) {
            if ($locationId) {
                $locationString = $request->get('location');
            } else {
                $locationString = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location').'/', $this->container, true);
                if ($locationString == '') {
                    throw new NotFoundHttpException('Invalid location.');
                }
            }
            if (substr($redirectString, -1)!='/') {
                $redirectString = $redirectString.'/';
            }
            
            $adType = null;
            $matches = null;
            $adTypeString = implode('\/|\/', $this->getAdTypeArray());
            
            $forsaleFlag  = false;
            
            if (strpos($redirectString, "for-sale") === 0) {
                $forsaleFlag = true;
            }
            
            if (strpos($redirectString, "property") === 0) {
                $adTypeString = implode('\/|\/', array('wanted', 'offered', 'exchange'));
            }
            
            // handle url for for sale category
            if (preg_match('/'.$adTypeString.'/', $redirectString, $matches) && !$forsaleFlag) {
                $adType = $matches[0];
            } elseif (preg_match('/~^'.$adTypeString.'/', $redirectString, $matches) && $forsaleFlag) {
                $adType = $matches[0];
            }
            
           
            $newCatText = $categoryText;
            $newRedirect = $redirect;
            
            if ($adType) {
                $adType = ltrim($adType, '/');
                $adType = rtrim($adType, '/');
                $explodeReirectString = explode('/', $redirectString);
                $adTypePos = array_search($adType, $explodeReirectString);

                if ($adTypePos==1) {
                    $explodeCatText = explode('/', $categoryText);
                    $rootCat = $explodeCatText[0];
                    array_shift($explodeCatText);
                    $implodeRemainingCat = implode($explodeCatText, '/');
                    $newCatText = $rootCat.'/'.$adType.'/'.$implodeRemainingCat;
                    
                    $explodeRedirect = explode('/', $redirect);
                    array_shift($explodeRedirect);
                    $implodeRemainingRedirect = implode($explodeRedirect, '/');
                    $newRedirect = $rootCat.'/'.$adType.'/'.$implodeRemainingRedirect;
                }
            }
            $newRedirect = str_replace('[location]/','',$newRedirect);
            if($newRedirect!=$redirectString) {
                $url = $this->container->get('router')->generate('listing_page', array(
                    'location' => $locationString,
                    'page_string' => str_replace($newCatText, $newRedirect, $redirectString),
                ), true);
                $url = str_replace('//', '/', $url);
            
                $response = new RedirectResponse($url, 301);
                $event->setResponse($response);
            }
        }
    }
    

    private function getLocationId($request, $redirectString = null)
    {
        $locationId = null;
        
        if (!preg_match('/^\d+$/', $request->get('location'))) {
            $locationId = $this->em->getRepository('FaEntityBundle:Location')->getIdBySlug($request->get('location'), $this->container);
        }

        if (!$locationId) {
            $locationId = $this->em->getRepository('FaEntityBundle:Locality')->getColumnBySlug('id', $request->get('location'), $this->container);
        }

        if (!$locationId && preg_match('/^[A-Za-z0-9-]{3,8}$/', $request->get('location')) && preg_match('/-N-|—N-/', $redirectString)) {
            $locationId = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodTextByLocation($request->get('location'), $this->container, 1, true);
            if (preg_match('/^([\d]+,[\d]+)$/', $locationId)) {
                $localityTown = explode(',', $locationId);
                $location     = $this->em->getRepository('FaEntityBundle:Locality')->getSlugByColumn('id', $localityTown[0], $this->container);
                $request->attributes->set('location', $location);
            } elseif (preg_match('/^\d+$/', $locationId)) {
                $lslug = $this->em->getRepository('FaEntityBundle:Location')->getSlugById($locationId);
                $request->attributes->set('location', $lslug);
            }
        }
        
        return $locationId;
    }

    private function dimensionArray($categoryText, $adType, $request, $event = null)
    {
        $categoryTextArray = explode('/', $categoryText);
        $pageArray = explode('/', $request->get('page_string'));
        $dimensions = array_diff($pageArray, $categoryTextArray);


        if (CommonManager::isConsicutiveSameValueInArray($pageArray)) {
            $request->attributes->set('not_found', 1);
        }

        if ($adType) {
            $dimensions[] = trim($adType, '/');
        }
        $dimArray   = array();
        $dimensions = array_filter($dimensions);
        $dimensions = array_unique($dimensions);
        $parentDimention = null;

        foreach ($dimensions as $dim) {
            if ($dim != '' && !preg_match('/page-\d+$/', $dim)) {
                $dimensionFieldPrefix = 'item';
                $catString =  explode('/', $request->get('cat_full_slug'));

                $dimensionObj = $this->getMatchedDimension($dim, implode("/", $catString), $parentDimention);
                if ($dimensionObj) {
                    $rootCatName = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryName($dimensionObj->getCategoryDimension()->getCategory()->getId(), $this->container);
                    $dimensionFieldPrefix = $dimensionFieldPrefix.'_'.$rootCatName;

                    $dimensionField = str_replace(array('(', ')', ',', '?', '|', '.', '/', '\\', '*', '+', '-', '"', "'"), '', $dimensionObj->getCategoryDimension()->getName());
                    $dimensionField = str_replace(' ', '_', strtolower($dimensionField)).'_id';

                    if ($dimensionField == 'ad_type_id') {
                        $dimensionField = 'item__'.$dimensionField;
                    } else {
                        $dimensionField = $dimensionFieldPrefix.'__'.$dimensionField;
                    }

                    $dimArray[$dimensionField][] = $dimensionObj->getId();
                    $parentDimention = $dimensionObj->getId();
                } else {
                    $engineSize = CommonManager::getEngineSizeChoices();
                    if (isset($engineSize[$dim])) {
                        $dimArray['item_motors__engine_size_range'][] = $dim;
                    } else {
                        $categoryText =  $request->get('page_string');
                        if (preg_match('/page-\d+\/$/', $categoryText, $matches)) {
                            $page = str_replace(array('page-', '/'), '', $matches[0]);
                            $request->attributes->set('page', $page);
                            $categoryText =  str_replace($matches[0], '', $categoryText);
                        }
                        $data = array();
                        $data = $this->em->getRepository('FaContentBundle:SeoTool')->getCustomizedUrlData($categoryText, $this->container);

                        if (!empty($data) > 0 && $event) {
                            if (!$request->attributes->get('customized_page')) {
                                $request->attributes->set('page_string', strtok($data['source_url'], '?'));
                                $request->attributes->set('customized_page', $data);
                                $this->onKernelRequest($event);
                            }
                            /*$targetCatText = strtok($data['source_url'],'?');
                            $targetCatText =  substr($targetCatText, 0, strrpos($targetCatText, '/'));
                            $catObj = $this->getMatchedCategory($targetCatText);
                            if ($catObj) {
                                $request->attributes->set('finders', array_merge($request->attributes->get('finders'), array('item__category_id' => $catObj['id'])));
                            }
                            $request->attributes->set('customized_page', $data);*/
                        } else {
                            $request->attributes->set('not_found', 1);
                        }
                    }
                }
            }
        }
        return $dimArray;
    }

    /**
     * get matched category
     *
     * @param string $category
     *
     * @return object|boolean
     */
    public function getMatchedCategory($category)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($category, $this->container);
        if ($cat) {
            return $cat;
        } else {
            /*$seoPopularSearchUrl = $this->em->getRepository('FaContentBundle:SeoToolPopularSearch')->findBy(array('url'=>'/'.$category.'/'));        
            if(empty($seoPopularSearchUrl)) {
                $explodeCatArr = explode('/', $category);
                if (!empty($explodeCatArr) && count($explodeCatArr)>1) {
                    array_pop($explodeCatArr);
                    $newCatText = implode('/', $explodeCatArr);
                    return $this->getMatchedCategory($newCatText);
                } else {
                    return false;
                }            
            } else { return false; }*/
            return false;
        }
    }

    /**
     * get first level parent
     *
     * @param integer $category_id
     *
     * @return object
     */
    private function getFirstLevelParent($category_id)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($category_id, false, $this->container);
        return $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById(key($cat), $this->container);
    }
    
    /**
     * redirects old urls
     *
     * @return void
     */
    private function redirectParentCatUrls($redirectString, $catId, $locationId, $request, $event, $page = null)
    {
        $url = null;
        $getCatObj = $this->em->getRepository('FaEntityBundle:Category')->find($catId);
        $parentcat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($getCatObj->getParent()->getFullSlug(), $this->container);
        if ($parentcat) {
            if ($locationId) {
                $locationString = $request->get('location');
            } else {
                $locationString = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location').'/', $this->container, true);
                if ($locationString == '') {
                    throw new NotFoundHttpException('Invalid location.');
                }
            }
            $categoryFullSlug = $parentcat['full_slug'];
            
            $url = $this->container->get('router')->generate('listing_page', array(
                'location' => $locationString,
                'page_string' => $categoryFullSlug,
            ), true);
            $response = new RedirectResponse($url, 301);
            $event->setResponse($response);
        }
    }

    /**
     * redirects old urls
     *
     * @return void
     */
    private function redirectOldUrls($redirectString, $locationId, $request, $event, $page = null)
    {
        if (preg_match('/-N-|—N-|N-/', $redirectString)) {
            $redirectString = preg_replace('/\/No-\d+\/$/', '', $redirectString);
            $redirectString = preg_replace('/\/+/', '/', $redirectString);
            $redirect = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($redirectString, $this->container);

            if ($redirect) {
                $url = null;
                if ($page == 'location_home') {
                    $locationString = 'uk';
                } else {
                    if ($locationId) {
                        $locationString = $request->get('location');
                    } else {
                        $locationString = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location').'/', $this->container, true);
                        if ($locationString == '') {
                            throw new NotFoundHttpException('Invalid location.');
                        }
                    }
                }

                if ($redirect == 'for-sale' || $redirect == 'property') {
                    if ($locationString == 'uk') {
                        $url = $this->container->get('router')->generate('landing_page_category', array(
                                'category_string' => $redirect,
                        ), true);
                    } else {
                        $url = $this->container->get('router')->generate('landing_page_category_location', array(
                                'category_string' => $redirect,
                                'location' => $locationString,
                        ), true);
                    }
                } elseif ($redirect == 'homepage') {
                    $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                } else {
                    $url = $this->container->get('router')->generate('listing_page', array(
                            'location' => $locationString,
                            'page_string' => $redirect,
                    ), true);
                }

                $response = new RedirectResponse($url, 301);
                $event->setResponse($response);
            } elseif (preg_match('/popular\//', $redirectString) || preg_match('/advertiser|adverts/', $request->get('location')) || preg_match('/popular-searches\//', $redirectString) || preg_match('/urgent\/|urgent-N-/', $redirectString)) {
                if ($page == 'location_home') {
                    $locationString = 'uk';
                } else {
                    if ($locationId) {
                        $locationString = $request->get('location');
                    } else {
                        // handles location redirection
                        $locationString = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location').'/', $this->container, true);
                        if ($locationString == '') {
                            if (preg_match('/advertiser|adverts/', $request->get('location'))) {
                                $redirect = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location').'/'.$redirectString, $this->container);
                                if ($redirect) {
                                    $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                                    $response = new RedirectResponse($url, 301);
                                    $event->setResponse($response);
                                } else {
                                    throw new NotFoundHttpException('Invalid location.');
                                }
                            } else {
                                throw new NotFoundHttpException('Invalid location.');
                            }
                        }
                    }
                }

                if ($locationString) {
                    if ($locationString == 'uk') {
                        $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                    } else {
                        $url = $this->container->get('router')->generate('location_home_page', array(
                                'location' => $locationString,
                        ), true);
                    }
                    $response = new RedirectResponse($url, 301);
                    $event->setResponse($response);
                }
            } else {
                $parts = explode('-N-', $redirectString);
                if (isset($parts[1])) {
                    $url   = str_replace('Z', ' Z', trim($parts[1], '/'));
                    if ($url) {
                        $words = explode(' ', $url);
                        $NewSearchParams = array();
                        $field = array();

                        if ($page == 'location_home') {
                            $NewSearchParams['item__location'] = 2;
                        } else {
                            if ($locationId) {
                                $NewSearchParams['item__location'] = $locationId;
                            } else {
                                // handles location redirection
                                $locationString = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location').'/', $this->container, true);
                                if ($locationString) {
                                    $locationId = $this->em->getRepository('FaEntityBundle:Location')->getIdBySlug($locationString, $this->container);
                                    if (!$locationId) {
                                        $locationId = $this->em->getRepository('FaEntityBundle:Locality')->getColumnBySlug('id', $locationString, $this->container);
                                    }
                                }
                                $NewSearchParams['item__location'] = $locationId ? $locationId : null;
                            }
                        }

                        $i = 1;
                        $parent = null;
                        $first_parent = null;
                        foreach ($words as $word) {
                            $field = $this->em->getRepository('FaAdBundle:MotorsRedirects')->getNewByOld($word, $parent, $first_parent, $this->container);

                            if ($i == 1) {
                                $first_parent = isset($field['parent']) && $field['parent'] ? $field['parent'] : null;
                            }

                            $parent = $first_parent.'-'.$word;

                            $i++;

                            if (isset($field['field']) && is_array($field['field'])) {
                                $NewSearchParams = array_merge($NewSearchParams, $field['field']);
                            }
                        }

                        if (isset($NewSearchParams['item__category_id'])) {
                            $routeManager = $this->container->get('fa_ad.manager.ad_routing');
                            $url = $routeManager->getListingUrl($NewSearchParams, 1);
                            $event->setResponse(new RedirectResponse($url, 301));
                        } else {
                            $words = explode(' ', $url);
                            $url = str_replace($words[0], '', str_replace(' ', '', $url));
                            if (isset($words[1])) {
                                $redirectString = str_replace($url, '', $redirectString);
                                $redirectString = preg_replace('/\/No-\d+\/$/', '', $redirectString);
                                $redirect = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld(rtrim($redirectString, '/'), $this->container);

                                if ($redirect) {
                                    $url = null;
                                    if ($page == 'location_home') {
                                        $locationString = 'uk';
                                    } else {
                                        if ($locationId) {
                                            $locationString = $request->get('location');
                                        } else {
                                            $locationString = $this->em->getRepository('FaAdBundle:Redirects')->getNewByOld($request->get('location').'/', $this->container, true);
                                            if ($locationString == '') {
                                                throw new NotFoundHttpException('Invalid location.');
                                            }
                                        }
                                    }

                                    if ($redirect == 'for-sale' || $redirect == 'property') {
                                        if ($locationString == 'uk') {
                                            $url = $this->container->get('router')->generate('landing_page_category', array(
                                                'category_string' => $redirect,
                                            ), true);
                                        } else {
                                            $url = $this->container->get('router')->generate('landing_page_category_location', array(
                                                'category_string' => $redirect,
                                                'location' => $locationString,
                                            ), true);
                                        }
                                    } elseif ($redirect == 'homepage') {
                                        $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                                    } else {
                                        $url = $this->container->get('router')->generate('listing_page', array(
                                            'location' => $locationString,
                                            'page_string' => $redirect,
                                        ), true);
                                    }

                                    $response = new RedirectResponse($url, 301);
                                    $event->setResponse($response);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * get ad type arrays
     *
     * @return multitype:string
     */
    public function getAdTypeArray()
    {
        return array(
                    'for-sale',
                    'wanted',
                    'swapping',
                    'free-to-collector',
                    'part-time',
                    'full-time',
                    'evenings',
                    'weekend',
                    'contract',
                    'temporary',
                    'freelance',
                    'home-working',
                    'offered',
                    'exchange',
                    'wanted',
                    'rescue',
                    'share',
                    'loan',
                    );
    }

    /**
     * get matched dimensions
     *
     * @param string  $slug
     * @param string  $category
     * @param integer $parentDimention
     *
     * @return object
     */
    public function getMatchedDimension($slug, $category, $parentDimention)
    {
        $urlKeysPattern = '^('.$category.').*$|^.*\|\|('.$category.').*$';

        $qb = $this->em->getRepository('FaEntityBundle:Entity')->createQueryBuilder(FaEntityRepo::ALIAS)
                    ->andWhere(FaEntityRepo::ALIAS.'.slug = :slug_text')
                    ->andWhere("regexp(".FaEntityRepo::ALIAS.".url_keys, '".$urlKeysPattern."') != false")
                    ->setParameter('slug_text', $slug);

        if ($parentDimention) {
            $qb->andWhere(FaEntityRepo::ALIAS.'.parent_id = :parent_id OR '.FaEntityRepo::ALIAS.'.parent_id = 0')
            ->setParameter('parent_id', $parentDimention);
        }

        $entities = $qb->getQuery()->getResult();

        if ($entities) {
            return $entities[0];
        }
    }
    
/*** Added for seo_config ***/
/**
 * Check if the request is homepage request.
 *
 * @param $request
 * @return bool
 */
    protected function isHomepageRoute($request)
    {
        return $this->_route($request) == 'location_home_page';
    }
    
    /**
     * Check if the request is listing page request.
     *
     * @param $request
     * @return bool
     */
    protected function isListingPageRoute($request)
    {
        return $this->_route($request) == 'listing_page';
    }
    
    /**
     * Check if the request is homepage request.
     *
     * @param $request
     * @return bool
     */
    protected function isLandingpageRoute($request)
    {
        return $this->_route($request) == 'landing_page_category';
    }
    
    /**
     * Check if the request is homepage request.
     *
     * @param $request
     * @return bool
     */
    protected function isLandingpageLocationRoute($request)
    {
        return $this->_route($request) == 'landing_page_category_location';
    }
    
    /**
     * Check if the request is homepage request.
     *
     * @param $request
     * @return bool
     */
    protected function isMotorListingpageRoute($request)
    {
        return $this->_route($request) == 'motor_listing_page';
    }
    
    /**
     * Get the route name.
     *
     * @param $request
     * @return mixed
     */
    protected function _route($request)
    {
        return $request->get('_route');
    }
    
    /**
     * Get route with given name and parameters.
     *
     * @param       $name
     * @param array $parameters
     * @param bool  $pathType
     * @return mixed
     */
    protected function getRoute($name, $parameters = [], $pathType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        // Getting Url Encoded string here.
        
        /** @var Router $router */
        $router = $this->container->get('router');
        
        return $router->generate($name, $parameters, $pathType);
    }
    
    /**
     * Redirect to given path with code.
     *
     * @param     $path
     * @param int $code
     */
    protected function redirect($path, $code = 302)
    {
        // Getting Url Encoded string here.
        $path = urldecode($path);
        if (!is_bool(strpos($path, '?'))) {
            $path = rtrim($path, '/');
        } else {
            $path = rtrim($path, '/') . '/';
        }
        
        $response = new RedirectResponse(strtolower($path), $code);
        $response->send();
    }
    
    /**
     * Check if the id is currently an Ad or an Archive ad.
     *
     * @param $adId
     * @return bool
     */
    protected function isWasAd($adId)
    {
        if (empty($adId)) {
            return false;
        }
        
        return $this->isAd($adId)
        ? true
        : $this->isArchivedAd($adId);
    }
    
    /**
     * Check if the id is an ad.
     *
     * @param $adId
     * @return null|bool|Object
     */
    protected function isAd($adId)
    {
        if (empty($adId)) {
            return false;
        }
        
        /** @var AdRepository $adRepository */
        $adRepository = $this->em->getRepository('FaAdBundle:Ad');
        
        return $adRepository->findOneBy(['id' => $adId]);
    }
    
    /**
     * Check if the id is an ad.
     *
     * @param $adId
     * @return bool
     */
    protected function isArchivedAd($adId)
    {
        if (empty($adId)) {
            return false;
        }
        
        /** @var ArchiveAdRepository $archiveAdRepository */
        $archiveAdRepository = $this->em->getRepository('FaArchiveBundle:ArchiveAd');
        
        return !empty($archiveAdRepository->find($adId));
    }
    
    /**
     * Checks if the given name is a location or region.
     *
     * @param $name
     * @return bool
     */
    protected function isLocation(&$name)
    {
        if (empty($name)) {
            false;
        }
        
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->em->getRepository('FaEntityBundle:Location');
        
        $location = $locationRepository->findOneBy([
            'url' => slug($name),
            'lvl' => [1, 2, 3, 4],
        ]);
        
        return !empty($location)
        ? true
        : ($this->isLocality($name)
            ? true
            : $this->isRegion($name)
            );
    }
    
    /**
     * Check if the given location is a County.
     *
     * @param $name
     * @return bool
     */
    protected function isCounty(&$name)
    {
        return $this->isLocationLevel($name, [2]);
    }
    
    /**
     * Check if the given location is a town.
     *
     * @param $name
     * @return bool
     */
    protected function isTown(&$name)
    {
        return $this->isLocationLevel($name, [3]);
    }
    
    /**
     * Check if the given is a location on level.
     * Level 1: UK
     * Level 2: County
     * Level 3: Town
     *
     * Locality Table: Further divisions of Town
     * Region Table: Main Regional Divisions of UK
     *
     * @param       $location
     * @param array $level
     * @return bool
     */
    protected function isLocationLevel($location, $level = [])
    {
        $level = array_wrap($level);
        
        if (empty($name)) {
            false;
        }
        
        if (empty($level)) {
            $level = [1];
        }
        
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->em->getRepository('FaEntityBundle:Location');
        
        $location = $locationRepository->findOneBy([
            'url' => slug($location),
            'lvl' => $level,
        ]);
        
        return !empty($location);
    }
    
    /**
     * Check if the given name is a region or not.
     *
     * @param $name
     * @return bool
     */
    protected function isRegion(&$name)
    {
        if (empty($name)) {
            return false;
        }
        
        /** @var RegionRepository $regionRepository */
        $regionRepository = $this->em->getRepository('FaEntityBundle:Region');
        
        return !empty($regionRepository->findOneBy([
            'slug' => slug($name),
        ]));
    }
    
    /**
     * Checks if the given name is a locality.
     *
     * @param $name
     * @return bool
     */
    protected function isLocality(&$name)
    {
        if (empty($name)) {
            return false;
        }
        
        /** @var LocalityRepository $localityRepository */
        $localityRepository = $this->em->getRepository('FaEntityBundle:Locality');
        
        return !empty($localityRepository->findOneBy([
            'url' => slug($name),
        ]));
    }
    
    /**
     * Check if the given name matches a slug in category table with levels - 1, 2, 3, 4
     *
     * @param       $name
     * @param array $levels
     * @return bool|Category
     */
    protected function isCategory(&$name, $levels = [1, 2, 3, 4])
    {
        if (empty($name)) {
            false;
        }
        
        /*$defaultListAllSlug = str_replace('-for-sale', '', $this->container->getParameter('fa.list_all_adverts_url_slug'));
        
        if ($name == $this->getLegacyListingSlug()) {
            //  This value is being appended with '-for-sale' slug in some cases. So this is a one place to change those.
            $name = $defaultListAllSlug;
            return true;
        }
        
        if ($name == $defaultListAllSlug) {
            return true;
        }*/
        
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->em->getRepository('FaEntityBundle:Category');
        
        return $categoryRepository->findOneBy([
            'slug' => slug($name),
            'lvl' => $levels,
            'status' => 1,
        ]);
    }
    
    /**
     * Check if the given slug is an entity or not.
     *
     * @param      $slug
     * @param bool $boolRequired
     * @return bool|Object
     */
    protected function isEntity($slug, $boolRequired = true)
    {
        if (empty($slug)) {
            false;
        }
        
        /** @var EntityRepository $entityRepository */
        $entityRepository = $this->em->getRepository('FaEntityBundle:Entity');
        
        /** @var Entity $entity */
        $entity = $entityRepository->findOneBy([
            'slug' => $slug,
            'status' => 1,
        ]);
        
        if (empty($entity)) {
            $entity = $entityRepository->findOneBy([
                'name' => revert_slug($slug),
                'status' => 1,
            ]);
        }
        
        return $boolRequired
        ? !empty($entity)
        : $entity;
    }
    
    /**
     * Check if the given entity name is a given dimension filter.
     *
     * @param        $slug
     * @param string $dimension
     * @return bool
     */
    protected function isDimensionFilter($slug, $dimension = 'af-species')
    {
        $name = revert_slug($slug);
        
        try {
            $conn = $this->em->getConnection();
            
            $stmt = $conn->prepare("
                select
                    *
                from categories_dimensions_entities as cde
                inner join entity e
                  ON cde.entity_id = e.id
                  and cde.status = 1
                  and e.name = '{$name}'
                inner join category_dimension cd
                  ON cde.category_dimension_id = cd.id
                  and cd.keyword = '{$dimension}'
            ");
            
            $stmt->execute();
            
            return !empty($stmt->fetchAll());
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get the Legacy 'All Adverts Listing' url slug.
     *
     * @return string
     */
    protected function getLegacyListingSlug()
    {
        return 'list-all-adverts';
    }
    
    /**
     * Get the Seo Config array.
     *
     * @param null $type
     * @return array
     */
    public function getSeoConfigs($type = null)
    {
        $data = !empty($this->seoConfigs)
        ? $this->seoConfigs
        : $this->querySeoConfigs();
        
        return $type
        ? data_get($data, $type, [])
        : $data;
    }
    
    /**
     * Query the Seo Configs.
     *
     * @return array
     */
    protected function querySeoConfigs()
    {
        /** @var SeoConfigRepository $seoConfigRepository */
        $seoConfigRepository = $this->em->getRepository('FaContentBundle:SeoConfig');
        
        $data = $seoConfigRepository->getBaseQueryBuilder()
        ->andWhere(SeoConfigRepository::ALIAS . '.status = 1')
        ->getQuery()
        ->getArrayResult();
        
        $configs = [];
        foreach ($data as $config) {
            $type = data_get($config, 'type');
            $values = json_decode(data_get($config, 'data'), true, 512);
            
            $forceFormat = false;
            if (in_array($type, [SeoConfigRepository::REDIRECTS])) {
                $forceFormat = 'normal';
            }
            
            if (is_array($values) && !is_associative_array($values)) {
                $values = $this->generateValueArray($values, $forceFormat);
            }
            
            $configs[$type] = $values;
        }
        
        $this->seoConfigs = $configs;
        
        return $configs;
    }
    
    /**
     * Generate the data array from the ':' separated strings.
     *
     * @param array $values
     * @param bool  $forceFormat
     * @return array
     */
    protected function generateValueArray($values = [], $forceFormat = false)
    {
        $data = [];
        foreach ($values as $item) {
            if (!is_array($item) && !$forceFormat && !is_bool(strpos($item, ':'))) {
                if (count($items = explode(':', $item)) <= 2) {
                    list($key, $value) = array_slice($items, 0, 2);
                    $data[$key] = $value;
                    continue;
                } else {
                    $data[] = $item;
                    continue;
                }
            } elseif ($forceFormat == 'normal') {
                // Forcing the format to normal array with all values as-such
                $data[] = $item;
                continue;
            } elseif (!is_array($item) && $forceFormat == 'assoc') {
                // Forcing array to be assoc, where the first value before first ':' will be the key.
                $items = explode(':', $item);
                $key = array_first($items);
                unset($items[0]);
                $value = implode(':', $items);
                $data[$key] = $value;
                continue;
            }
            
            // Any other unprecedented situation.
            $data[] = $item;
        }
        
        return $data;
    }
    
    /**
     * Get the default Location slug.
     *
     * @return string
     */
    protected function getDefaultLocation()
    {
        return $this->container->getParameter('fa.default.location_slug');
    }
    
    /**
     * Get Keyword Search Config.
     *
     * @return array
     */
    public function getKeywordSearchConfig()
    {
        return array_map(function (&$value) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }, $this->getSeoConfigs(SeoConfigRepository::KEYWORD_SEARCH_CONFIG));
    }
    
    /**
     * Check if the url is crawl-able & return url. Else return 'non-crawalble' string.
     *
     * @param $url
     * @return string
     */
    public function isCrawlUrl($url)
    {
        $url = strtolower($url);
        if (empty($crawlConfigs = $this->getCrawlConfig()) || $url == '#') {
            return $url;
        }
        
        // Get the path & query Segments
        $segments = parse_url($url);
        $query = parse_query(data_get($segments, 'query', ''));
        $path = array_values(array_filter(explode('/', strtolower(data_get($segments, 'path', ''))), function ($item) {
            return !empty($item) && !substr_exist($item, '.php');
        }));
            
            // Prep Location Name, Category Name
            $location = strtolower(data_get($path, '0', ''));
            $categoryName = str_replace('-for-sale', '', strtolower(data_get($path, '1', '')));
            $entities = array_slice($path, 2);
            
            // Expand the query param values.
            $queryValues = [];
            $query = array_wrap($query);
            foreach ($query as $queryItem) {
                $queryValues = array_merge($queryValues, explode('__', $queryItem));
            }
            
            $entities = array_unique(array_merge($entities, $queryValues));
            
            $urlConfig = [
                "category" => [],
                "dimension" => [],
                "region" => false,
                "county" => false,
                "town" => false,
            ];
            
            if (!empty($location) && $location != $this->getDefaultLocation()) {
                
                if ($this->isRegion($location)) {
                    $urlConfig['region'] = true;
                } elseif ($this->isCounty($location)) {
                    $urlConfig['county'] = true;
                } elseif ($this->isTown($location)) {
                    $urlConfig['town'] = true;
                }
            }
            
            if (!empty($categoryName) && $categoryName != env('fa.list_all_adverts_url_slug')) {
                if (!is_bool($category = $this->isCategory($categoryName)) && !empty($category)) {
                    $urlConfig['category'][] = $category->getId();
                }
            }
            
            foreach ($entities as $entityName) {
                if (!empty($entity = $this->isEntity($entityName, false)) && !empty($dimension = $entity->getCategoryDimension())) {
                    
                    $dimensionId = $dimension->getId();
                    if (!in_array($dimensionId, $urlConfig['dimension'])) {
                        $urlConfig['dimension'][] = $dimension->getId();
                    }
                }
            }
            
            $nonCrawlUrl = false;
            foreach ($crawlConfigs as $crawlConfig) {
                
                // Is Category Filter enabled
                if (!empty($categories = data_get($crawlConfig, 'category', []))) {
                    
                    // Does Crawl Category config match with URL categories.
                    if (empty(array_intersect(data_get($urlConfig, 'category', []), $categories))) {
                        continue;
                    }
                }
                
                
                // Is Dimension Filter enabled
                if (!empty($dimensions = data_get($crawlConfig, 'dimension'))) {
                    // Does Crawl Dimension config match with URL dimensions.
                    $intersectDimensions = array_intersect(data_get($urlConfig, 'dimension', []), $dimensions);
                    if (empty($intersectDimensions) || (!empty($intersectDimensions) && count($intersectDimensions) != count($dimensions))) {
                        continue;
                    }
                }
                
                // Is region filter enabled
                if (data_get($crawlConfig, 'region', false)) {
                    if (!data_get($urlConfig, 'region', false)) {
                        continue;
                    }
                }
                
                // Is county filter enabled
                if (data_get($crawlConfig, 'county', false)) {
                    if (!data_get($urlConfig, 'county', false)) {
                        continue;
                    }
                }
                
                // Is town filter enabled
                if (data_get($crawlConfig, 'town', false)) {
                    if (!data_get($urlConfig, 'town', false)) {
                        continue;
                    }
                }
                
                $nonCrawlUrl = true;
            }
            
            if ($nonCrawlUrl) {
                return 'javascript:;';
            }
            
            return $url;
    }
    
    /**
     * Get Crawl Config.
     *
     * @return array
     */
    protected function getCrawlConfig()
    {
        /** @var AdRequestListener $adRequestListener */
        $adRequestListener = $this->container->get('fa_ad_kernel.request.listener');
        $crawlConfigs = $adRequestListener->getSeoConfigs(SeoConfigRepository::CRAWL_CONFIG);
        
        return array_map(function ($crawlConfig) {
            return [
                'category' => array_filter(array_wrap(data_get($crawlConfig, 'category', [])), function (&$categoryId) {
                $categoryId = (int)$categoryId;
                return $categoryId > 0;
                }),
                'dimension' => array_filter(array_wrap(data_get($crawlConfig, 'dimension', [])), function (&$dimensionId) {
                $dimensionId = (int)$dimensionId;
                return $dimensionId > 0;
                }),
                'region' => filter_var(data_get($crawlConfig, 'region', false), FILTER_VALIDATE_BOOLEAN),
                'county' => filter_var(data_get($crawlConfig, 'county', false), FILTER_VALIDATE_BOOLEAN),
                'town' => filter_var(data_get($crawlConfig, 'town', false), FILTER_VALIDATE_BOOLEAN),
                ];
        }, $crawlConfigs);
    }
    
    /**
     * Redirect Ad Detail page to Ad listing page.
     *
     * @param  $request
     * @return Object
     */
    protected function redirectAdDetailPage(&$request)
    {
        $adId = $request->get('id', 0);
        $adString = $request->get('ad_string');
        
        if ($this->isEntity("{$adString}-{$adId}")) {
            
            $request->attributes->set('_route', 'listing_page');
            $pageString = $request->get('category_string') . '/' . "{$adString}-{$adId}";
            $routeParams = [
                'location' => $request->get('location'),
                'page_string' => $pageString,
            ];
            
            $request->attributes->set('_controller', 'Fa\Bundle\AdBundle\Controller\AdListController::searchResultAction');
            $request->attributes->set('_route_params', $routeParams);
            $request->attributes->set('page_string', $pageString);
            $request->attributes->set('_forwarded_from_', 'ad_detail_page');
            $request->attributes->set('_forwarded_to_', 'listing_page');
            
            $request->attributes->remove('category_string');
            $request->attributes->remove('ad_string');
            $request->attributes->remove('id');
        }
        
        return $request;
    }
    
    /**
     * Redirect Ad Listing page to Ad Detail page.
     *
     * @param $request
     * @return object
     */
    protected function redirectAdListingPage($request)
    {
        $pageStringParts = array_filter(explode('/', $request->get('page_string')));
        $categoryString = array_shift($pageStringParts);
        $adStringAndAdIdParts = explode('-', array_first($pageStringParts));
        $adId = array_last($adStringAndAdIdParts);
        unset($adStringAndAdIdParts[count($adStringAndAdIdParts) - 1]);
        $adStringLength = strlen(implode('-', $adStringAndAdIdParts));
        
        if (($adStringLength > 0 && $adStringLength < 7 && $this->isAd($adId)) || ($adStringLength >= 7 && $this->isAd($adId))) {
            
            $request->attributes->set('_route', 'ad_detail_page');
            $adString = array_first($adStringAndAdIdParts);
            $routeParams = [
                'location' => $request->get('location'),
                'category_string' => $categoryString,
                'ad_string' => $adString,
                'id' => $adId,
            ];
            
            $request->attributes->set('location', $request->get('location'));
            $request->attributes->set('category_string', $categoryString);
            $request->attributes->set('ad_string', $adString);
            $request->attributes->set('id', $adId);
            
            $request->attributes->set('_controller', 'Fa\Bundle\AdBundle\Controller\AdController::showAdAction');
            $request->attributes->set('_route_params', $routeParams);
            $request->attributes->set('_forwarded_to_', 'ad_detail_page');
            $request->attributes->set('_forwarded_from_', 'listing_page');
            
            $request->attributes->remove('page_string');
        }
        
        return $request;
    }
    
    /**
     * Check if the given $region is a legacy region.
     *
     * @param $region
     * @return mixed
     */
    protected function isLegacyRegion($region)
    {
        return data_get($this->getSeoConfigs(SeoConfigRepository::REGION_ALIAS), slug($region));
    }
    
    /**
     * Check if the given $location is a legacy location.
     *
     * @param $location
     * @return mixed
     */
    protected function isLegacyLocation($location)
    {
        return data_get($this->getSeoConfigs(SeoConfigRepository::LOCATION_ALIAS), slug($location));
    }
    
    /**
     * Rebuild the request with the given parameters.
     *
     * @param $request
     * @param array   $params
     */
    protected function reBuildRequest(&$request, $params = [])
    {
        $request->attributes->set('path', implode('/', $params));
        $routeParams = $request->attributes->get('_route_params', []);
        $location = $this->getDefaultLocation();
        
        foreach ($params as $key => $param) {
            $possibleLocation = strtolower($param);
            
            if ($this->isLocation($possibleLocation) || ($possibleLocation = $this->isLegacyLocation($possibleLocation))) {
                unset($params[$key]);
                $location = $possibleLocation;
                break;
            } elseif ($possibleRegion = $this->isLegacyRegion(strtolower($param))) {
                unset($params[$key]);
                $location = $possibleRegion;
                break;
            }
        }
        
        $routeParams['location'] = $location;
        $routeParams['page_string'] = implode('/', ($params ? $params : []));
        
        if (!empty($queryParams = $request->query->all())) {
            $routeParams['page_string'] .= '?' . http_build_query($queryParams);
        }
        
        $request->attributes->set('_route_params', $routeParams);
        
        $request->attributes->set('location', $routeParams['location']);
        $request->attributes->set('page_string', $routeParams['page_string']);
    }
    
    /**
     * Will enable the flag to recognize for redirect.
     *
     * @param $request
     */
    protected function enableRedirect(&$request)
    {
        $request->attributes->set('_redirect', true);
    }
    
    
    /**
     * Get the request parts. 
     *
     * @param $request
     * @return array
     */
    protected function getPathParts($request)
    {
        if (!empty($this->getLocation($request)) && !empty($request->get('page_string'))) {
            $path = $this->getLocation($request) . '/' . $request->get('page_string');
        } else {
            $path = $request->get('path');
            
            if (!$path) {
                $path = $this->getLocation($request);
            }
        }
        
        $path = $this->cleanPath($path);
        
        if (substr_exist($path, '?')) {
            $path = array_first(explode('?', $path), null, '');
        }
        
        $parts = explode('/', $path);
        $scriptName = basename($request->server->get('SCRIPT_NAME'));
        $scriptFilePosition = array_search($scriptName, $parts);
        if (!is_bool($scriptFilePosition)) {
            unset($parts[$scriptFilePosition]);
            $parts = array_values($parts);
        }
        
        return array_filter($parts);
    }
    
    /**
     * Get the request location.
     *
     * @param $request
     * @return mixed
     */
    protected function getLocation($request)
    {
        return $request->get('location');
    }
    
    /**
     * Clean the path string.
     *
     * @param $uriPath
     * @return mixed
     */
    protected function cleanPath($uriPath)
    {
        $path = ltrim(rtrim($uriPath, '/'), '/');
        return $path;
    }
    
    /**
     * Handle Region & Location Aliasing.
     *
     * @param $request
     */
    protected function handleLocationAndRegionAliases(&$request)
    {
        $legacyFlag = false;
        $pathParts = $this->getPathParts($request);
        
        foreach ($pathParts as &$pathPart) {
            
            if ($region = $this->isLegacyRegion($pathPart)) {
                $pathPart = $region;
                $legacyFlag = true;
            }
            
            if ($location = $this->isLegacyLocation($pathPart)) {
                $pathPart = $location;
                $legacyFlag = true;
            }
        }
        
        if ($legacyFlag) {
            $this->reBuildRequest($request, $pathParts);
            $this->enableRedirect($request);
        }
    }
    
    /**
     * Redirect the legacy Main Category Level redirects.
     *
     * @param $request
     */
    protected function handleLegacyCategoryRedirects(&$request)
    {
        $legacyFlag = false;
        $legacyUrlPart = $this->getSeoConfigs(SeoConfigRepository::CATEGORY_ALIAS);
        $pathParts = $this->getPathParts($request);
        
        foreach ($legacyUrlPart as $legacy => $new) {
            if (!is_bool($pos = array_search($legacy, $pathParts)) && $this->isCategory($new, [1])) {
                $pathParts[$pos] = $new;
                $legacyFlag = true;
            }
        }
        
        if ($legacyFlag) {
            $this->reBuildRequest($request, $pathParts);
            $this->enableRedirect($request);
        }
    }
    
    /**
     * Check if the redirect flag is set. Redirect if flag is set.
     *
     * @param $request
     */
    protected function redirectIfRequired($request)
    {
        if ($request->get('_redirect')) {
            
            $pageString = trim(str_replace(['app_dev.php'], [''], $request->get('page_string')), '/');
            $pageString = array_filter(explode('?', $pageString));
            
            if (!empty($queryParams = $request->query->all())) {
                $pageString[1] = http_build_query($queryParams);
            }
            
            if (count($pageString) > 1) {
                $pageString[0] = rtrim($pageString[0], '/') . '/';
            }
            
            if (empty($location = $this->getLocation($request))) {
                $pathParts = explode('/', $pageString[0]);
                $location = array_shift($pathParts);
                $pageString[0] = implode('/', $pathParts);
            }
            
            
            if($this->isHomepageRoute($request)) {
                $this->redirect($this->getRoute('location_home_page', [
                    'location' => $location ? $location : $this->getDefaultLocation(),
                ]), 301);
            } elseif($this->isLandingpageRoute($request) || $this->isLandingpageLocationRoute($request)) {
                $this->redirect($this->getRoute('listing_page', [
                    'location' => $location ? $location : $this->getDefaultLocation(),
                    'page_string' => $request->get('category_string'),
                ]), 301);
            } else {
                $this->redirect($this->getRoute('listing_page', [
                    'location' => $location ? $location : $this->getDefaultLocation(),
                    'page_string' => implode('?', array_filter($pageString)),
                ]), 301);
            }
        }
    }
    
    /**
     * Handle the protocol redirection.
     *
     * @param $request
     */
    protected function handleProtocolRedirects(&$request)
    {
        $uri = $request->getUri();
        $siteDomain = $this->container->getParameter('site.domain');
        $siteName = trim(strtolower($this->container->getParameter('site.name')));
        $subDomain = array_first(explode('.', $siteDomain));
        $isLiveSite = ($siteName == $subDomain) && !in_array($subDomain, ['fmtinew', 'stage', 'devnew']);
        
        // HTTP >> HTTPS for all sub-domains.
        // Non WWW to WWW version only for Live sites without sub-domain.
        if (!$request->get('_redirect') && (substr_exist($uri, 'http://') || (!substr_exist($uri, 'www.') && $isLiveSite))) {
            $uri = str_replace('http://', 'https://', $uri);
            
            if (!substr_exist($uri, 'www.') && $isLiveSite) {
                $uri = str_replace('https://', 'https://www.', $uri);
            }
            
            if (!substr_exist($uri, '?')) {
                $uri = rtrim($uri, '/') . '/';
            }
            
            $this->redirect($uri, 301);
        }
    }
    
    /**
     * Check if the incoming request needs to be redirected with code 301 and redirect.
     *
     * @param $request
     * @return bool
     * @throws \Twig_Error
     */
    protected function _301($request)
    {
        
        if ($request->get('location')!='') {
            $this->handleLocationAndRegionAliases($request);
        }
        
        if ($request->get('category_string')!='' || $request->get('category_id')!='') {
            $this->handleLegacyCategoryRedirects($request);
        }
        
        $this->handleProtocolRedirects($request);
        
        $this->redirectIfRequired($request);
        return false;
    }
    
    
}
