<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Gedmo\Sluggable\Util\Urlizer;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\UserBundle\Repository\RoleRepository;

/**
 * Ad route manager.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdRoutingManager
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager.
     *
     * @var object
     */
    protected $em;

    public $dimensionOrder = array();


    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct($router, ContainerInterface $container)
    {
        $this->container = $container;
        $this->router = $router;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->setDimensionOrder();
    }


    /**
     * get url for custom listing
     *
     * @param array   $search_params
     * @param string  $custom_url
     * @param boolean $isNearByLocation
     *
     * @return string
     */
    public function getCustomListingUrl($search_params, $custom_url, $isNearByLocation = false)
    {
        if (isset($search_params['item__location']) && $search_params['item__location'] == 2) {
            $location = 'uk';
        } else {
            $location = $this->getLocation($search_params);
        }

        $url = $this->router->generate('listing_page', array('location' => $location, 'page_string' => rtrim($custom_url, '/')), true);

        if ($isNearByLocation && isset($search_params['item__distance']) && $search_params['item__distance'] == 0) {
            $url = $url.'?item__distance=0';
        }
        return $url;
    }

    /**
     * get listing url based on search parameters
     *
     * @param array $search_params
     *
     * @param string $page
     *
     * @return boolean|string
     */
    public function getListingUrl($search_params, $page = null, $submitted = false, $categories = null, $fromCommandLine = false, $parentFullSlug = null, $secondLevelParentFullSlug = null)
    {
        $location    = null;
        $page_string = null;
        $user_slug = null;
        $dimension_slug = null;
        $cookieLocationDetails = null;
        $url = '';
        
        $search_params = array_map(array($this, 'removeEmptyElement'), $search_params);

        
        // From top search keyword category
        if (isset($search_params['keyword_category_id']) && $search_params['keyword_category_id']) {
            $search_params['item__category_id'] = $search_params['keyword_category_id'];
            unset($search_params['keyword_category_id']);
        }

        $getDefaultRadius = '';
       
        if (!isset($search_params['item__distance']) && $fromCommandLine == false) {
            $getDefaultRadius = $this->em->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($search_params, $this->container);
            if ($getDefaultRadius) {
                $search_params['item__distance'] = $getDefaultRadius;
            }
        }

        if (!$fromCommandLine) {
            $cookieLocationDetails = json_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('location'), true);
        }

        if (isset($search_params['item__location']) && $search_params['item__location'] == 2) {
            $location = 'uk';
        // } elseif (is_array($cookieLocationDetails) && isset($search_params['item__location']) && isset($cookieLocationDetails['location'])) {
            //$location = $cookieLocationDetails['slug'];
        } elseif (is_array($cookieLocationDetails) && isset($search_params['item__location']) && isset($cookieLocationDetails['location']) && ($cookieLocationDetails['location']==$search_params['item__location'])  && (isset($search_params['item__area']) && isset($cookieLocationDetails['location_area']) && $cookieLocationDetails['location_area']==$search_params['item__area'])) {
            $location = $cookieLocationDetails['slug'];
        } else {
            $location = $this->getLocation($search_params);
        }
        
        if (isset($search_params['item__user_id']) && $search_params['item__user_id'] != '') {
            $shopUserId = $search_params['item__user_id'];
            unset($search_params['item__user_id']);
            $user_slug = $this->em->getRepository('FaUserBundle:User')->getUserProfileSlug($shopUserId, $this->container);
        }

        if (isset($search_params['tmpLeafLevelCategoryId'])) {
            unset($search_params['tmpLeafLevelCategoryId']);
        }
        if (isset($search_params['item__area'])) {
            unset($search_params['item__area']);
        }
      
        // create url for no category selected
        if ((!isset($search_params['item__category_id']) || $search_params['item__category_id'] == '') && $user_slug == null) {
            unset($search_params['item__location']);
            unset($search_params['item__location_autocomplete']);

            if (isset($search_params['item__distance']) && $search_params['item__distance'] == 15) {
                unset($search_params['item__distance']);
            }

            $query = http_build_query(array_map(array($this, 'removeBlankElement'), $search_params));

            $url = $this->router->generate('listing_page', array(
                'location' => $location,
                'page_string' => 'search',
            ), true).'?'.$query;

            //$url = preg_replace('/\/+/', '/', $url);

            return rtrim($url, '?');
        }

        //to redirect user back to same category level
        if (isset($search_params['leafLevelCategoryId']) && $search_params['leafLevelCategoryId']) {
            $search_params['item__category_id'] = $search_params['leafLevelCategoryId'];
            unset($search_params['leafLevelCategoryId']);
        }

        if (!$categories && isset($search_params['item__category_id'])) {
            $categories = array_keys($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($search_params['item__category_id'], false, $this->container));
        }


        //$category = $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById($search_params['item__category_id'], $this->container);
        $categoryId   = (isset($search_params['item__category_id']) ? $search_params['item__category_id'] : null);

        $parentId = null;
        if (isset($categories[0])) {
            $parentId = $categories[0];
            if ($parentFullSlug == null) {
                $parentFullSlug = $this->em->getRepository('FaEntityBundle:Category')->getFullSlugById($parentId, $this->container);
            }
        }

        // handle dimension rule for car and commercial vehicles
        $secondLevelParentId = null;
        if (isset($categories[1])) {
            $secondLevelParentId       = $categories[1];
            if ($secondLevelParentFullSlug == null) {
                $secondLevelParentFullSlug = $this->em->getRepository('FaEntityBundle:Category')->getFullSlugById($secondLevelParentId, $this->container);
            }
        }
        
        if(!empty($categories)) {
            $locationRadius = array();
            $locationRadius = $this->em->getRepository('FaAdBundle:LocationRadius')->getSingleLocationRadiusByCategoryIds($categories);           
            if (!empty($locationRadius)) {
                $getDefaultRadius =  $locationRadius['defaultRadius'];
            }
        }
        
        $orders = array();
        // get dimension orders for parent category
        if ($parentId) {
            $orders = $this->dimensionOrder[$parentId];
        }

        // unset variables for clean url
        unset($search_params['item__category_id']);
        unset($search_params['item__location']);
        
        if (isset($search_params['item__area'])) {
            unset($search_params['item__area']);
        }

        if (isset($search_params['item__location_autocomplete'])) {
            unset($search_params['item__location_autocomplete']);
        }

        // always set for sale ad type in last order this is to handle customized for sale url
        $onlyForSale = false;

        if (($parentId == CategoryRepository::FOR_SALE_ID || $parentId == CategoryRepository::ANIMALS_ID) && isset($search_params['item__ad_type_id'])) {
            $search_params['item__ad_type_id'] = is_array($search_params['item__ad_type_id']) ? $search_params['item__ad_type_id'] : (array) $search_params['item__ad_type_id'];

            if (in_array($search_params['item__ad_type_id'][0], array(2620, 1, 2763, 2891))) {
                $ad_type_id = $search_params['item__ad_type_id'][0];
                unset($search_params['item__ad_type_id'][0]);
                array_push($search_params['item__ad_type_id'], $ad_type_id);
            }
        }

        if ($parentId == CategoryRepository::MOTORS_ID && isset($search_params['item_motors__fuel_type_id'])) {
            $search_params['item_motors__fuel_type_id'] = is_array($search_params['item_motors__fuel_type_id']) ? $search_params['item_motors__fuel_type_id'] : (array) $search_params['item_motors__fuel_type_id'] ;
            if (in_array($search_params['item_motors__fuel_type_id'][0], array(1627, 2043, 6367, 6404))) {
                $fuel_type_id = $search_params['item_motors__fuel_type_id'][0];
                unset($search_params['item_motors__fuel_type_id'][0]);
                array_push($search_params['item_motors__fuel_type_id'], $fuel_type_id);
            }
        }

        if ($parentId == CategoryRepository::MOTORS_ID && isset($search_params['item_motors__transmission_id'])) {
            $search_params['item_motors__transmission_id'] = is_array($search_params['item_motors__transmission_id']) ? $search_params['item_motors__transmission_id'] : (array) $search_params['item_motors__transmission_id'] ;
            if (in_array($search_params['item_motors__transmission_id'][0], array(2041, 6371, 6408))) {
                $transmission_id= $search_params['item_motors__transmission_id'][0];
                unset($search_params['item_motors__transmission_id'][0]);
                array_push($search_params['item_motors__transmission_id'], $transmission_id);
            }
        }

        $categoryLvl  = null;
        if (isset($secondLevelParentId) && ($secondLevelParentId == CategoryRepository::COMMERCIALVEHICLES_ID) || $secondLevelParentId == CategoryRepository::CARS_ID) {
            //$categoryLvl  = $this->container->get('fa.entity.cache.manager')->getEntityLvlById('FaEntityBundle:Category', $categoryId);
        }

        if (isset($search_params['item__location']) && $search_params['item__location'] == 2) {
            unset($search_params['item__distance']);
        }

        // Commented by Sagar
        /*if (isset($secondLevelParentId) && ( ($secondLevelParentId == CategoryRepository::COMMERCIALVEHICLES_ID && ($categoryLvl == 3 || $categoryLvl == 4)) || ($secondLevelParentId == CategoryRepository::CARS_ID && ($categoryLvl == 3 || $categoryLvl == 4)))) {
            $search_params = $this->getDimensionSlug($search_params, $orders, 1);
        } else {
            $search_params = $this->getDimensionSlug($search_params, $orders);
        }*/

        if (isset($secondLevelParentId) && (($secondLevelParentId == CategoryRepository::COMMERCIALVEHICLES_ID && ($secondLevelParentId != $categoryId)) || ($secondLevelParentId == CategoryRepository::CARS_ID && ($secondLevelParentId != $categoryId)))) {
            $search_params = $this->getDimensionSlug($search_params, $orders, 1);
        } else {
            $search_params = $this->getDimensionSlug($search_params, $orders);
        }

        if ($categoryId) {
            $dimension_slug = $search_params['url'];
            $page_string    = $this->em->getRepository('FaEntityBundle:Category')->getFullSlugById($categoryId, $this->container);
        }

        $pageString   = $page_string;
        $adTypeString = implode('|\/', $this->getAdTypeArray());


        //this is to handle customized for sale url
        if ($dimension_slug != '') {
            if (($parentId == CategoryRepository::FOR_SALE_ID || $parentId == CategoryRepository::ANIMALS_ID)) {
                $dimension_slug = str_replace($this->getAdTypeArray()[0], '', $dimension_slug, $count);
                if ($count > 0) {
                    array_push($search_params['search_param']['item__ad_type_id'], $ad_type_id);
                }
            }

            if ($parentId == CategoryRepository::MOTORS_ID) {
                $dimension_slug = str_replace('petrol', '', $dimension_slug, $count);
                if ($count > 0) {
                    array_push($search_params['search_param']['item_motors__fuel_type_id'], $fuel_type_id);
                }

                $dimension_slug = str_replace('manual', '', $dimension_slug, $count);
                if ($count > 0) {
                    array_push($search_params['search_param']['item_motors__transmission_id'], $transmission_id);
                }
            }

            $pageString .= '/'.$dimension_slug;
        }

        $matches      = array();
        $pageString   = trim($pageString, "/");

        if ($parentId == CategoryRepository::FOR_SALE_ID || $parentId == CategoryRepository::ANIMALS_ID) {
            if (preg_match('/~^'.$adTypeString.'/', $pageString, $matches)) {
                $pageString = str_replace($matches[0], "", $pageString);
                $pageString = preg_replace('/^'.$parentFullSlug.'/', $parentFullSlug.$matches[0], $pageString);
            }
        }

        // change ad type string for property
        if ($parentId == CategoryRepository::PROPERTY_ID) {
            $adTypeString = implode('|\/', array('wanted', 'offered', 'exchange'));
        }

        // decide ad type place
        if (($parentId == CategoryRepository::SERVICES_ID) || ($parentId == CategoryRepository::PROPERTY_ID) || ($parentId == CategoryRepository::COMMUNITY_ID)) {
            if (preg_match('/'.$adTypeString.'/', $pageString, $matches)) {
                $pageString = str_replace($matches[0], "", $pageString);
                $pageString = preg_replace('/^'.$parentFullSlug.'/', $parentFullSlug.'/'.$matches[0], $pageString);
            }
        }
        

        if ($page && $page > 1) {
            $pageString .= '/page-'.$page;
        }

        $pageString = $pageString == '' ? 'search' : $pageString;
        $searchDistance = '';
        
         if ((isset($search_params['search_param']['item__distance']))) {            
            $searchDistance = $search_params['search_param']['item__distance'];
            if ($getDefaultRadius!='' && $getDefaultRadius==$search_params['search_param']['item__distance']) {
                unset($search_params['search_param']['item__distance']);
            } elseif($getDefaultRadius=='' && (($parentId == CategoryRepository::MOTORS_ID && $searchDistance==CategoryRepository::MOTORS_DISTANCE) || ($parentId != CategoryRepository::MOTORS_ID && $searchDistance==CategoryRepository::OTHERS_DISTANCE))) {
                unset($search_params['search_param']['item__distance']);
            }

            //unset($search_params['search_param']['item__distance']);
        }
        
        $query = http_build_query(array_map(array($this, 'removeBlankElement'), $search_params['search_param']));

        // if($searchDistance!='') {
        //     $search_params['search_param']['item__distance'] = $searchDistance;
        // }

        $pageString = preg_replace('/\/+/', '/', rtrim($pageString, '/'));
 
        if ($submitted && $query) {
            $query = rawurldecode($query);
            $targetUrl = $this->em->getRepository('FaContentBundle:SeoTool')->getCustomizedTargetUrl($pageString.'/?'.$query, $this->container);
            if ($targetUrl) {
                $pageString = rtrim($targetUrl, '/');
                return $this->router->generate('listing_page', array(
                        'location' => $location,
                        'page_string' => $pageString,
                ), true);
            }
        }

        if ($user_slug != '') {
            $url = $this->router->generate('show_business_user_ads_location', array(
                'location' => $location,
                'profileNameSlug' => $user_slug,
                'page_string' => $pageString,
            ), true).'?'.rawurldecode($query);
        } else {
            $url = $this->router->generate('listing_page', array(
                'location' => $location,
                'page_string' => $pageString,
            ), true).'?'.rawurldecode($query);
        }
        return rtrim($url, '?');
    }

    /**
     * get category url based on search parameters
     *
     * @param string $location
     * @param string $categoryFullSlug
     *
     * @return string
     */
    public function getCategoryUrl($location, $categoryFullSlug)
    {
        return $this->router->generate('listing_page', array(
            'location' => $location,
            'page_string' => $categoryFullSlug,
        ), true);
    }


    /**
     * get location
     *
     * @param array $search_params
     *
     * @return string
     */
    private function getLocation($search_params)
    {
        $location = null;
        if (isset($search_params['item__location']) && $search_params['item__location'] != '') {
            if (preg_match('/^\d+$/', $search_params['item__location'])) {
                $location = $this->em->getRepository('FaEntityBundle:Location')->getSlugById($search_params['item__location'], $this->container);
            } elseif (preg_match('/^([\d]+,[\d]+)$/', $search_params['item__location'])) {
                $localityTown = explode(',', $search_params['item__location']);
                $location     = $this->em->getRepository('FaEntityBundle:Locality')->getSlugByColumn('id', $localityTown[0], $this->container);
            } else {
                $search_params['item__location'] = trim(str_replace('+', ' ', $search_params['item__location']));
                $location = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodTextByLocation($search_params['item__location'], $this->container, 1);

                if (preg_match('/^\d+$/', $location)) {
                    $location = $this->em->getRepository('FaEntityBundle:Location')->getSlugById($location, $this->container);
                } elseif (preg_match('/^([\d]+,[\d]+)$/', $location)) {
                    $localityTown = explode(',', $location);
                    $location     = $this->em->getRepository('FaEntityBundle:Locality')->getSlugByColumn('id', $localityTown[0], $this->container);
                }
            }

            if (!$location) {
                $location = $this->em->getRepository('FaEntityBundle:Location')->getSlugByName($search_params['item__location'], 3, $this->container);
                if (!$location) {
                    $location = $this->em->getRepository('FaEntityBundle:Locality')->getSlugByColumn('name', $search_params['item__location'], $this->container);
                    if (!$location) {
                        $location = $this->em->getRepository('FaEntityBundle:Location')->getSlugByName($search_params['item__location'], 2, $this->container);
                    }
                }
            }
        }
        if ((isset($search_params['item__area']) && $search_params['item__area'] != '')) {
            $location = $this->em->getRepository('FaEntityBundle:Location')->getSlugById($search_params['item__area'], $this->container);
        }

        return $location = $location != '' ? $location : 'uk';
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
     * get first level parent
     *
     * @param integer $category_id
     *
     * @return object
     */
    private function getSecondLevelParent($category_id)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($category_id, false, $this->container);
        $ak = array();

        $ak = array_keys($cat);
        if (isset($ak['1'])) {
            return $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById($ak['1'], $this->container);
        } else {
            return null;
        }
    }

    /**
     * get dimension slug
     *
     * @param array $search_params
     * @param array $orders
     * @param integer $dimCount
     *
     * @return array
     */
    private function getDimensionSlug($search_params, $orders, $dimCount = 2)
    {
        $returnArray = array();
        $params      = array();

        $i = 1;

        foreach ($orders as $key => $dim) {
            if (isset($search_params[$key]) && $i <= $dimCount) {
                $params[$key] = $search_params[$key];
                $i++;
            }
        }

        $slugArray = array();

        foreach (array_merge($params, $search_params) as $key => $val) {
            if (isset($params[$key])) {
                if (is_array($val)) {
                    $valA = array_values(array_unique($val));
                    $val = $valA[0];
                    unset($valA[0]);
                    $search_params[$key] = $valA;
                } else {
                    unset($search_params[$key]);
                }

                if (preg_match('/engine_size_range/', $key)) {
                    $slug = $val;
                } else {
                    $slug = $this->em->getRepository('FaEntityBundle:Entity')->getSlugById($val, $this->container);
                }

                if ($slug) {
                    $slugArray[] = $slug;
                }
            }
        }

        $returnArray['url']     = implode('/', $slugArray);
        $returnArray['search_param'] = $search_params;

        return $returnArray;
    }

    /**
     * Get ad detail url.
     *
     * @param object  $ad         Ad/Solr ad document object.
     * @param integer $adId
     * @param string  $adTitle
     * @param integer $categoryId
     * @param integer $locationId
     *
     * @return string
     */
    public function getDetailUrl($ad = null, $adId = null, $adTitle = null, $categoryId = null, $locationId = null)
    {
        $categoryString = $this->getAdDetailUrlCategory($ad, $adId, $adTitle, $categoryId, $locationId);
        $locationString = $this->getAdDetailUrlLocation($ad, $adId, $adTitle, $categoryId, $locationId);
        $adString       = $this->getAdDetailUrlTitle($ad, $adId, $adTitle, $categoryId, $locationId);

        if (!$locationString) {
            $locationString = 'uk';
        }
        if (!$categoryString) {
            $categoryString = 'na';
        }
        if (!$adString) {
            $adString = 'na';
        }

        $url = $this->router->generate('ad_detail_page', array(
            'location'        => $locationString,
            'ad_string'       => $adString,
            'category_string' => $categoryString,
            'id'              => $this->getAdDetailUrlId($ad, $adId, $adTitle, $categoryId, $locationId),
        ), true);

        return $url;
    }

    /**
     * Get ad detail url location.
     *
     * @param object  $ad         Ad/Solr ad document object.
     * @param integer $adId
     * @param string  $adTitle
     * @param integer $categoryId
     * @param integer $locationId
     *
     * @return string
     */
    protected function getAdDetailUrlLocation($ad = null, $adId = null, $adTitle = null, $categoryId = null, $locationId = null)
    {
        $locationString = null;

        if ($ad && get_class($ad) == 'SolrObject') {
            //check for location.
            if (!empty($ad[AdSolrFieldMapping::AREA_ID]) && isset($ad[AdSolrFieldMapping::IS_SPECIAL_AREA_LOCATION]) && $ad[AdSolrFieldMapping::IS_SPECIAL_AREA_LOCATION]) {
                $locationString = $this->em->getRepository('FaEntityBundle:Location')->getSlugForDetailAd($ad[AdSolrFieldMapping::AREA_ID][0], $this->container);
            } elseif (!empty($ad[AdSolrFieldMapping::TOWN_ID])) {
                $locationString = $this->em->getRepository('FaEntityBundle:Location')->getSlugForDetailAd($ad[AdSolrFieldMapping::TOWN_ID][0], $this->container);
            } elseif (!empty($ad[AdSolrFieldMapping::DOMICILE_ID])) {
                $locationString = $this->em->getRepository('FaEntityBundle:Location')->getSlugForDetailAd($ad[AdSolrFieldMapping::DOMICILE_ID][0], $this->container);
            }
        } elseif ($adId && $locationId) {
            $locationString = $this->em->getRepository('FaEntityBundle:Location')->getSlugForDetailAd($locationId, $this->container);
        } elseif ($ad) {
            //check for location.
            $domicileTownArray = $this->em->getRepository('FaAdBundle:AdLocation')->getIdArrayByAdId($ad->getId());
            if (isset($domicileTownArray[$ad->getId()])) {
                $domicileTownArray = explode(',', $domicileTownArray[$ad->getId()]);
                if (isset($domicileTownArray[1])) {
                    $locationString = $this->em->getRepository('FaEntityBundle:Location')->getSlugForDetailAd($domicileTownArray[1], $this->container);
                } else {
                    $locationString = $this->em->getRepository('FaEntityBundle:Location')->getSlugForDetailAd($domicileTownArray[0], $this->container);
                }
            }
        }

        return $locationString;
    }

    /**
     * Get ad detail url category.
     *
     * @param object  $ad         Ad/Solr ad document object.
     * @param integer $adId
     * @param string  $adTitle
     * @param integer $categoryId
     * @param integer $locationId
     *
     * @return string
     */
    protected function getAdDetailUrlCategory($ad = null, $adId = null, $adTitle = null, $categoryId = null, $locationId = null)
    {
        $categoryString = null;
        $fullSlugArray  = array();

        if ($ad && get_class($ad) == 'SolrObject' && (int) $ad[AdSolrFieldMapping::CATEGORY_ID]) {
            $fullSlugArray  = explode('/', $this->em->getRepository('FaEntityBundle:Category')->getFullSlugById($ad[AdSolrFieldMapping::CATEGORY_ID], $this->container));
        } elseif ((int) $categoryId) {
            $fullSlugArray  = explode('/', $this->em->getRepository('FaEntityBundle:Category')->getFullSlugById($categoryId, $this->container));
        } elseif ($ad && $ad->getCategory() && $ad->getCategory()->getId()) {
            $fullSlugArray  = explode('/', $this->em->getRepository('FaEntityBundle:Category')->getFullSlugById($ad->getCategory()->getId(), $this->container));
        }

        if (!empty($fullSlugArray)) {
            $categoryString =  isset($fullSlugArray[1]) ? $fullSlugArray[1] : $fullSlugArray[0];
        }

        return $categoryString;
    }

    /**
     * Get ad detail url category.
     *
     * @param object  $ad         Ad/Solr ad document object.
     * @param integer $adId
     * @param string  $adTitle
     * @param integer $categoryId
     * @param integer $locationId
     *
     * @return string
     */
    protected function getAdDetailUrlTitle($ad = null, $adId = null, $adTitle = null, $categoryId = null, $locationId = null)
    {
        $adString = null;

        if ($ad && get_class($ad) == 'SolrObject' && strlen(trim($ad[AdSolrFieldMapping::TITLE]))) {
            $adString = Urlizer::urlize(CommonManager::trimTextByWords($ad[AdSolrFieldMapping::TITLE], 7, ''));
        } elseif ($adId && strlen(trim($adTitle))) {
            $adString = Urlizer::urlize(CommonManager::trimTextByWords($adTitle, 7, ''));
        } elseif ($ad && get_class($ad) != 'SolrObject' && strlen(trim($ad->getTitle()))) {
            $adString = Urlizer::urlize(CommonManager::trimTextByWords($ad->getTitle(), 7, ''));
        }

        return $adString;
    }

    /**
     * Get ad detail url id.
     *
     * @param object  $ad         Ad/Solr ad document object.
     * @param integer $adId
     * @param string  $adTitle
     * @param integer $categoryId
     * @param integer $locationId
     *
     * @return string
     */
    protected function getAdDetailUrlId($ad = null, $adId = null, $adTitle = null, $categoryId = null, $locationId = null)
    {
        if ($ad && get_class($ad) == 'SolrObject') {
            $adId = $ad[AdSolrFieldMapping::ID];
        } elseif ($ad) {
            $adId = $ad->getId();
        }

        return $adId;
    }

    /**
     * set dimension orders
     */
    private function setDimensionOrder()
    {
        $this->dimensionOrder[CategoryRepository::FOR_SALE_ID]= array(
            'item__category_id' => 1,
            'item__ad_type_id' => 2,
            'item_for_sale__brand_id' => 3,
            'item_for_sale__age_range_id' => 5,
            'item_for_sale__size_id' => 6,
            'item_for_sale__business_type_id' => 10,
            'item_for_sale__condition_id' => 11,
            'item_for_sale__colour_id' => 12,
            'item_for_sale__main_colour_id' => 13,
        );

        $this->dimensionOrder[CategoryRepository::JOBS_ID]= array(
            'item__category_id' => 1,
            'item_jobs__contract_type_id' => 2,
        );

        $this->dimensionOrder[CategoryRepository::MOTORS_ID]= array(
            'item__category_id' => 1,
            'item_motors__make_id' => 2,
            'item_motors__manufacturer_id' => 3,
            'item_motors__model_id' => 4,
            'item_motors__colour_id' => 5,
            'item_motors__body_type_id' => 6,
            'item_motors__fuel_type_id' => 7,
            'item_motors__transmission_id' => 8,
            'item_motors__berth_id' => 9,
            'item_motors__part_of_vehicle_id' => 10,
            'item_motors__part_manufacturer_id' => 11,
            'item_motors__tonnage_id' => 12,
            'item_motors__engine_size_range' => 13,
        );

        $this->dimensionOrder[CategoryRepository::SERVICES_ID]= array(
            'item__category_id' => 1,
            'item__ad_type_id' => 2,
            'item_services__services_offered_id' => 3,
            'item_services__service_type_id' => 4,
            'item_services__event_type_id' => 5,
        );

        $this->dimensionOrder[CategoryRepository::PROPERTY_ID]= array(
            'item__category_id' => 1,
            'item__ad_type_id' => 2,
            'item_property__number_of_bedrooms_id' => 3,
            'item_property__room_size_id' => 4,
            'item_property__amenities_id' => 5,
        );

        $this->dimensionOrder[CategoryRepository::ANIMALS_ID]= array(
            'item__category_id' => 1,
            'item__ad_type_id' => 2,
            'item_animals__breed_id' => 3,
            'item_animals__species_id' => 3,
            'item_animals__colour_id' => 4,
            'item_animals__gender_id' => 5,
        );

        $this->dimensionOrder[CategoryRepository::COMMUNITY_ID]= array(
            'item__category_id' => 1,
            'item__ad_type_id' => 2,
            'item_community__level_id' => 3,
            'item_community__experience_level_id' => 3,
        );

        $this->dimensionOrder[CategoryRepository::ADULT_ID]= array(
            'item__category_id' => 1,
        );
    }

    /**
     * get ad type arrays
     *
     * @return multitype:string
     */
    private function getAdTypeArray()
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
            'rescue',
            'loan',
        );
    }

    /**
     * remove blank element
     *
     * @param array|string $value
     *
     * @return array|string
     */
    private function removeBlankElement($value)
    {
        if ((!is_array($value) && strlen($value)) || (is_array($value) && count($value))) {
            //return $value;
            if (is_array($value)) {
                return implode('__', $value);
            }
            return $value;
        }
    }

    /**
     * remove blank element
     *
     * @param array|string $value
     *
     * @return array|string
     */
    private function removeEmptyElement($value)
    {
        if ((!is_array($value) && strlen($value)) || (is_array($value) && count($value))) {
            return $value;
        }
    }

    /**
     * Get url of location wise home page.
     *
     * @param string/integer $location Location id or slug
     *
     * @return string
     */
    public function getLocationHomePageUrl($location)
    {
        $locationSlug       = null;
        $locationRepository = $this->em->getRepository('FaEntityBundle:Location');

        // if integer then get slug by id else pass slu.
        if (preg_match('/^\d+$/', $location)) {
            $locationSlug = $locationRepository->getSlugById($location, $this->container);
        } else {
            $locationSlug = $location;
        }

        $url = $this->router->generate('location_home_page', array(
            'location' => $locationSlug,
        ), true);

        return $url;
    }

    /**
     * get landing page url based on search parameters
     *
     * @param array $search_params
     *
     * @return boolean|string
     */
    public function getCategoryLandingPageUrl($search_params)
    {
        $locationSlug = null;
        $categorySlug = null;

        if (isset($search_params['item__category_id']) && $search_params['item__category_id']) {
            $categorySlug = $this->em->getRepository('FaEntityBundle:Category')->getSlugById($search_params['item__category_id'], $this->container);
        }

        $locationSlug = $this->getLocation($search_params);
        if ($locationSlug == 'uk') {
            $locationSlug = null;
        }

        if ($categorySlug && $locationSlug) {
            return $this->router->generate('landing_page_category_location', array('category_string' => $categorySlug, 'location' => $locationSlug), true);
        }

        if ($categorySlug && !$locationSlug) {
            return $this->router->generate('landing_page_category', array('category_string' => $categorySlug), true);
        }

        return null;
    }

    /**
     * get user profile page url
     *
     * @param integer $userId User id
     *
     * @return boolean|string
     */
    public function getProfilePageUrl($userId)
    {
        $userRole        = $this->em->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);
        $profileNameSlug = $this->em->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);

        if ($userRole == RoleRepository::ROLE_SELLER) {
            return $this->router->generate('show_private_profile_page', array('profileNameSlug' => $profileNameSlug, 'userId' => CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $userId)), true);
        } elseif ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            return $this->router->generate('show_business_profile_page', array('profileNameSlug' => $profileNameSlug), true);
        }

        return null;
    }

    /**
     * get user profile page url
     *
     * @param integer $userId User id
     *
     * @return boolean|string
     */
    public function getProfilePageAdsUrl($userId)
    {
        $userRole        = $this->em->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);
        $profileNameSlug = $this->em->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);

        if ($userRole == RoleRepository::ROLE_SELLER) {
            return $this->router->generate('show_private_user_ads', array('profileNameSlug' => $profileNameSlug, 'userId' => CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $userId), 'pageString' => 'ads'), true);
        } elseif ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            return $this->router->generate('show_business_user_ads', array('profileNameSlug' => $profileNameSlug), true);
        }

        return null;
    }

    /**
    * get Default Radius By Category Id
    *
    * @param integer $categoryId Category id
    *
    * @return boolean|string
    */
    public function getDefaultRadiusByCategoryId($categoryId)
    {
        $parentCategoryIds = array_keys($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container));
        $locationRadius = $this->em->getRepository('FaAdBundle:LocationRadius')->getSingleLocationRadiusByCategoryIds($parentCategoryIds);
        if ($locationRadius) {
            return $locationRadius['defaultRadius'];
        } else {
            return null;
        }
    }
}
