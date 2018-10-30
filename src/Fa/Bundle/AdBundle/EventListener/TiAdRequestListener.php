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

/**
 * This event listener is used for decide location based route
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class TiAdRequestListener
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
        // check for session timeout for cart/process and checkout uri
        $uri = $event->getRequest()->getUri();

        $request = $event->getRequest();
        $currentRoute = $event->getRequest()->get('_route');
        $params      = $request->attributes->get('_route_params');
        $request->attributes->set('_route_params', array_merge($params, array('page' => 1)));
        if ($currentRoute == 'landing_page_category' || $currentRoute == 'landing_page_category_location') {
            $catObj = $this->getMatchedCategory($request->get('category_string'));

            if ($currentRoute == 'landing_page_category' && !isset($params['path']) && $request->get('category_string')) {
                $params['path'] = '/'.$request->get('category_string');
            } elseif ($currentRoute == 'landing_page_category_location' && !isset($params['path']) && $request->get('category_string') && $request->get('location')) {
                $params['path'] = '/'.$request->get('category_string').'/'.$request->get('location');
            }

            if (isset($params['path'])) {
                $this->redirectOldUrls(ltrim($params['path'], '/'), 'bristol', $request, $event, 'location_home');
            }

            if ($catObj) {
                $request->attributes->set('category_id', $catObj['id']);
            }
        } elseif ($currentRoute ==  'listing_page'|| $currentRoute ==  'ti_motor_listing_page') {
            $queryParams  =  array();
            $searchParams = $request->query->all();

            $redirectString = $request->get('page_string');

            if ($currentRoute ==  'ti_motor_listing_page') {
                $params['path'] = '/'.$request->get('location').'/'.$redirectString.'/';
            }
            if (!isset($params['path']) && $request->get('location') && $redirectString) {
                $params['path'] = '/'.$request->get('location').'/'.$redirectString;
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
                        $urlString1 = null;
                        $urlString2 = null;
                        $checkPath =  explode('/', trim($params['path'], '/'));
                        if (count($checkPath) >= 3) {
                            $urlString1 = $checkPath[1];
                            $urlString2 = $checkPath[1].'/'.$checkPath[2];
                        }
                        $redirect = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOldForAdRef($urlString2, $this->container);
                        if ($redirect) {
                            if (substr($redirect, -1) !== '/') {
                                $redirect = $redirect.'/';
                            }
                            $event->setResponse(new RedirectResponse($redirect, 301));
                        } else {
                            $redirect = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOldForAdRef($urlString1, $this->container);
                            if ($redirect) {
                                if (substr($redirect, -1) !== '/') {
                                    $redirect = $redirect.'/';
                                }
                                $event->setResponse(new RedirectResponse($redirect, 301));
                            } else {
                                if (strpos($adRef, 'ZP0') === 0) {
                                    $url = $this->container->get('router')->generate('landing_page_category', array('category_string' => 'property'), true);
                                    $event->setResponse(new RedirectResponse($url, 301));
                                } elseif (strpos($adRef, 'KP') === 0 || strpos($adRef, 'TT') === 0) {
                                    $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                                    $event->setResponse(new RedirectResponse($url, 301));
                                } elseif (strpos($adRef, 'YI') === 0) {
                                    $url = 'http://www.friday-ad.co.uk'.rtrim($params['path'], '/');
                                    $event->setResponse(new RedirectResponse($url, 301));
                                } elseif (strpos($adRef, 'SN') === 0) {
                                    $url = $this->container->get('router')->generate('landing_page_category', array('category_string' => 'motors'), true);
                                    $event->setResponse(new RedirectResponse($url, 301));
                                }
                            }
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

            $redirectString = $request->get('page_string');
            $locationId = $this->getLocationId($request, $redirectString);

            if (!$locationId) {
                $request->attributes->set('not_found', 1);
            }

            $this->redirectOldUrls($redirectString, $locationId, $request, $event);

            if ($currentRoute ==  'ti_motor_listing_page') {
                $request->attributes->set('not_found', 1);
            }

            foreach ($request->query->all() as $key => $val) {
                if (preg_match('/^(.*)_id$/', $key) || preg_match('/reg_year|mileage_range|engine_size_range/', $key)) {
                    if (preg_match('/mileage_range/', $key)) {
                        $val = str_replace('100000 ', '100000+', $val);
                    }

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

            if (!$catObj && $categoryText != 'search') {
                $request->attributes->set('not_found', 1);
            }

            $dimArray = $this->dimensionArray($categoryText, $adType, $request, $event);
            $request->attributes->set('finders', array_merge_recursive($request->attributes->get('finders'), $dimArray));
        } elseif ($currentRoute ==  'detail_page') {
            return false;
        } elseif ($currentRoute ==  'location_home_page') {
            if (!isset($params['path']) && $request->get('location')) {
                $params['path'] = '/'.$request->get('location');
            }
            // to decide old detail page url
            if (isset($params['path']) && $params['path']) {
                if (preg_match('/[A-Z0-9]{9,10}\/$/', $params['path'], $matches) && isset($matches[0])) {
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
                        } elseif (strpos($adRef, 'SN') === 0) {
                            $url = $this->container->get('router')->generate('landing_page_category', array('category_string' => 'motors'), true);
                            $event->setResponse(new RedirectResponse($url, 301));
                        }
                    }
                }
            }

            $redirectString = $request->get('location');
            $static_page = $this->em->getRepository('FaContentBundle:StaticPage')->getStaticPageLinkArray($this->container, true);
            if (in_array($redirectString, $static_page)) {
                $request->attributes->set('static_page', 1);
            }

            if (isset($params['path']) && $params['path']) {
                $location = trim($params['path'], '/');
                $locationString = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($location, $this->container, true);
                if ($locationString) {
                    $url = $this->container->get('router')->generate('location_home_page', array(
                        'location' => $locationString,
                    ), true);
                    $response = new RedirectResponse($url, 301);
                    $event->setResponse($response);
                }
            } else {
                $redirectString = $request->get('location');
                $this->redirectOldUrls($redirectString, 'bristol', $request, $event, 'location_home');
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

    private function dimensionArray($categoryText, $adType, $request, $event=null)
    {
        $categoryTextArray = explode('/', $categoryText);
        $pageArray = explode('/', $request->get('page_string'));
        $dimensions = array_diff($pageArray, $categoryTextArray);

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

                        if (count($data) > 0 && $event) {
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
            $redirect = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($redirectString, $this->container);

            if ($redirect) {
                $url = null;
                if ($page == 'location_home') {
                    $locationString = 'bristol';
                } else {
                    if ($locationId) {
                        $locationString = $request->get('location');
                    } else {
                        $locationString = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($request->get('location').'/', $this->container, true);
                        if ($locationString == '') {
                            throw new NotFoundHttpException('Invalid location.');
                        }
                    }
                }

                if ($redirect == 'for-sale' || $redirect == 'property') {
                    if ($locationString == 'bristol') {
                        $url = $this->container->get('router')->generate('landing_page_category_location', array(
                            'category_string' => $redirect,
                            'location' => $locationString,
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
            } elseif (preg_match('/query\/|query-/', $redirectString) || preg_match('/popular\//', $redirectString) || preg_match('/advertiser|adverts/', $request->get('location')) || preg_match('/popular-searches\//', $redirectString) || preg_match('/urgent\/|urgent-N-/', $redirectString)) {
                if ($page == 'location_home') {
                    $locationString = 'bristol';
                } else {
                    if ($locationId) {
                        $locationString = $request->get('location');
                    } else {
                        // handles location redirection
                        $locationString = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($request->get('location').'/', $this->container, true);
                        if ($locationString == '') {
                            if (preg_match('/advertiser|adverts/', $request->get('location'))) {
                                $redirect = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($request->get('location').'/'.$redirectString, $this->container);
                                if ($redirect) {
                                    $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                                    $response = new RedirectResponse($url, 301);
                                    $event->setResponse($response);
                                } else {
                                    throw new NotFoundHttpException('Invalid location.');
                                }
                            } else {
                                if (preg_match('/query\/|/query-/', $redirectString)) {
                                    $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                                    $response = new RedirectResponse($url, 301);
                                    $event->setResponse($response);
                                }
                                throw new NotFoundHttpException('Invalid location.');
                            }
                        }
                    }
                }

                if ($locationString) {
                    if ($locationString == 'bristol') {
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
                                $locationString = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($request->get('location').'/', $this->container, true);
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
                            $field = $this->em->getRepository('FaAdBundle:TiMotorsRedirects')->getNewByOld($word, $parent, $first_parent, $this->container);

                            if (!$field && $i == 1) {
                                continue;
                            }

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
                            if (preg_match("/-N-1z1|-N-1z0|-N-2m|-N-2o|-N-2t|-N-2y|-N-31|-N-g2/", $request->get('page_string'), $matches)) {
                                $pageString = substr($request->get('page_string'), 0, strpos($request->get('page_string'), $matches[0])).$matches[0];
                                $locationS   = $request->get('location');
                                $locationString = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($locationS, $this->container, true);
                                if ($locationString) {
                                    $locationS = $locationString;
                                }

                                $redirect = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($pageString, $this->container);

                                if ($redirect) {
                                    if ($redirect == 'homepage') {
                                        $url = $this->container->get('router')->generate('fa_frontend_homepage', array(), true);
                                    } else {
                                        $url = $this->container->get('router')->generate('listing_page', array('location' => $locationS, 'page_string' => rtrim($redirect, '/')), true);
                                    }
                                    $response = new RedirectResponse($url, 301);
                                    $event->setResponse($response);
                                }
                            }


                            $words = explode(' ', $url);
                            $url = str_replace($words[0], '', str_replace(' ', '', $url));
                            if (isset($words[1])) {
                                $redirectString = str_replace($url, '', $redirectString);
                                $redirectString = preg_replace('/\/No-\d+\/$/', '', $redirectString);
                                $redirect = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld(rtrim($redirectString, '/'), $this->container);

                                if ($redirect) {
                                    $url = null;
                                    if ($page == 'location_home') {
                                        $locationString = 'bristol';
                                    } else {
                                        if ($locationId) {
                                            $locationString = isset($locationString) && $locationString != '' ? $locationString : $request->get('location');
                                        } else {
                                            $locationString = $this->em->getRepository('FaAdBundle:TiRedirects')->getNewByOld($request->get('location').'/', $this->container, true);
                                            if ($locationString == '') {
                                                throw new NotFoundHttpException('Invalid location.');
                                            }
                                        }
                                    }

                                    if ($redirect == 'for-sale' || $redirect == 'property') {
                                        if ($locationString == 'bristol') {
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
