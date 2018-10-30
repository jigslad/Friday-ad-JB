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

        // check for session timeout for cart/process and checkout uri
        $uri = $event->getRequest()->getUri();
        
        //redirect greate-london slug
        if (preg_match('/greate-london/', $uri)) {
            $locationUrl = str_replace('greate-london', 'greater-london', $uri);
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/for-sale\/home-garden\/aids\//', $uri)) { //redirect aids FFR-2083
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
            $locationUrl = ConfigRepository::LIVE_CAMS_URL;
            $response = new RedirectResponse($locationUrl, 301);
            $event->setResponse($response);
        } elseif (preg_match('/avon/', $uri)) {
            $uriSplit = explode('/', $uri);
            if ((isset($uriSplit[3]) && $uriSplit[3] == 'avon')) {
                $uriSplit[3] = 'county-bristol';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            } elseif ((isset($uriSplit[4]) && $uriSplit[4] == 'avon')) {
                $uriSplit[4] = 'county-bristol';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            }
        } elseif (preg_match('/cleveland/', $uri)) {
            $uriSplit = explode('/', $uri);
            if ((isset($uriSplit[3]) && $uriSplit[3] == 'cleveland')) {
                $uriSplit[3] = 'north-yorkshire';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            } elseif ((isset($uriSplit[4]) && $uriSplit[4] == 'cleveland')) {
                $uriSplit[4] = 'north-yorkshire';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            }
        } elseif (preg_match('/north-humberside/', $uri)) {
            $uriSplit = explode('/', $uri);
            if ((isset($uriSplit[3]) && $uriSplit[3] == 'north-humberside')) {
                $uriSplit[3] = 'east-yorkshire';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            } elseif ((isset($uriSplit[4]) && $uriSplit[4] == 'north-humberside')) {
                $uriSplit[4] = 'east-yorkshire';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            }
        } elseif (preg_match('/south-humberside/', $uri)) {
            $uriSplit = explode('/', $uri);
            if ((isset($uriSplit[3]) && $uriSplit[3] == 'south-humberside')) {
                $uriSplit[3] = 'lincolnshire';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            } elseif ((isset($uriSplit[4]) && $uriSplit[4] == 'south-humberside')) {
                $uriSplit[4] = 'lincolnshire';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            }
        } elseif (preg_match('/south-wirral/', $uri)) {
            $uriSplit = explode('/', $uri);
            if ((isset($uriSplit[3]) && $uriSplit[3] == 'south-wirral')) {
                $uriSplit[3] = 'cheshire';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            } elseif ((isset($uriSplit[4]) && $uriSplit[4] == 'south-wirral')) {
                $uriSplit[4] = 'cheshire';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            }
        } elseif (preg_match('/middlesex-ashford/', $uri)) {
            $uriSplit = explode('/', $uri);
            if ((isset($uriSplit[3]) && $uriSplit[3] == 'middlesex-ashford')) {
                $uriSplit[3] = 'surrey-ashford';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            } elseif ((isset($uriSplit[4]) && $uriSplit[4] == 'middlesex-ashford')) {
                $uriSplit[4] = 'surrey-ashford';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            }
        } elseif (preg_match('/surrey-richmond/', $uri)) {
            $uriSplit = explode('/', $uri);
            if ((isset($uriSplit[3]) && $uriSplit[3] == 'surrey-richmond')) {
                $uriSplit[3] = 'greater-london-richmond';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            } elseif ((isset($uriSplit[4]) && $uriSplit[4] == 'surrey-richmond')) {
                $uriSplit[4] = 'greater-london-richmond';
                $locationUrl = implode('/', $uriSplit);
                $response = new RedirectResponse($locationUrl, 301);
                $event->setResponse($response);
            }
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

                if ($catObj) {
                    $check = false;
                    $request->attributes->set('cat_full_slug', $catObj['full_slug']);

                    $parent   = $this->getFirstLevelParent($catObj['id']);

                    if (($catObj['id'] == CategoryRepository::MOTORS_ID) || ($parent['id'] == CategoryRepository::MOTORS_ID)) {
                        $queryParams['item__distance']  =  $request->get('item__distance') == '' ? CategoryRepository::MOTORS_DISTANCE : $request->get('item__distance');
                    } else {
                        $queryParams['item__distance']  =  $request->get('item__distance') == '' ? CategoryRepository::OTHERS_DISTANCE : $request->get('item__distance');
                    }
                    
                    
                    //check location belongs to area
                    if (preg_match('/^\d+$/', $locationId) && is_null($request->get('item__distance'))) {
                        $isLocationArea = $this->em->getRepository('FaEntityBundle:Location')->find($locationId);
                        if (!empty($isLocationArea) && $isLocationArea && $isLocationArea->getLvl() == '4') {
                            $queryParams['item__distance'] = $queryParams['item__distance']/CategoryRepository::AREA_DISTANCE_DIVISION;
                        }
                    }
                    
                    $request->attributes->set('finders', array_merge($queryParams, array('item__category_id' => $catObj['id'], 'item__location' => $locationId)));
                } else {
                    $queryParams['item__distance'] = isset($queryParams['item__distance']) && $queryParams['item__distance'] != null ? $queryParams['item__distance'] : CategoryRepository::OTHERS_DISTANCE;
                    $request->attributes->set('finders', array_merge($queryParams, array('item__location' => $locationId)));
                }

                if (!strpos($categoryText, '/')) {
                    $check = false;
                }
            }
            
            if (!$catObj && $categoryText != 'search') {
                $request->attributes->set('not_found', 1);
            }
            
            $dimArray = $this->dimensionArray($categoryText, $adType, $request, $event);
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

                    if (($catObj['id'] == CategoryRepository::MOTORS_ID) || ($parent['id'] == CategoryRepository::MOTORS_ID)) {
                        $queryParams['item__distance']  =  $request->get('item__distance') == '' ? 30 : $request->get('item__distance');
                    } else {
                        $queryParams['item__distance']  =  $request->get('item__distance') == '' ? 15 : $request->get('item__distance');
                    }

                    $request->attributes->set('finders', array_merge($queryParams, array('item__category_id' => $catObj['id'], 'item__location' => $locationId)));
                } else {
                    $queryParams['item__distance'] = isset($queryParams['item__distance']) && $queryParams['item__distance'] != null ? $queryParams['item__distance'] : 15;
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
}
