<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Repository\BannerRepository;
use Fa\Bundle\ContentBundle\Repository\BannerPageRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\AdBundle\Repository\PrintEditionRepository;
use Fa\Bundle\ContentBundle\Repository\BannerZoneRepository;

/**
 * This class is used for seo management.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class BannerManager
{
    /**
     * Container service class object
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager object.
     *
     * @var object
     */
    private $em;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
    }

    /**
     * Parse ad detail seo string.
     *
     * @param string $seoString Seo string.
     * @param object $adSolrObj Ad solr object.
     *
     * @return string
     */
    public function parseBannerCode($bannerCodeString, $extraParams)
    {
        $objRequest         = $this->container->get('request_stack')->getCurrentRequest();
        $currentZoneId      = $extraParams['zone_id'];
        $currentPageId      = $extraParams['page_id'];
        $params             = $this->container->get('request_stack')->getCurrentRequest()->get('finders');
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $bannerVariables    = array('{page_type}', '{user_type}', '{user_location}', '{county}', '{edition_area}', '{target_id}', '{search_keyword}', '{width}', '{height}', '{hashed_email}');
        $categoryVariables  = array('{category}', '{class}', '{sub_class}', '{sub_sub_class}');
        $allBannerVariables = array_merge($bannerVariables, $categoryVariables);

        if ($extraParams && isset($extraParams['cookieValues'])) {
            $locationArray = json_decode($extraParams['cookieValues'], true);
        } elseif ($params && isset($params['item__location'])) {
            $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getCookieValue($params['item__location'], $this->container);
        } else {
            $locationArray = json_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('location'), true);
        }

        preg_match_all('/\{.*?\}/', $bannerCodeString, $bannerVariables);

        if ($currentZoneId && $currentZoneId == BannerZoneRepository::ZONE_LOGIN_PIXEL_TRACKING) {
            $this->container->get('session')->set('login_pixel_tracking', 1);
        }

        // replace variable values.
        if (count($bannerVariables)) {
            foreach ($bannerVariables[0] as $bannerVariable) {
                //Reset banner variable if not properly parsed.
                foreach ($allBannerVariables as $bVariable) {
                    if (strstr($bannerVariable, $bVariable)) {
                        $bannerVariable = $bVariable;
                    }
                }

                if ($bannerVariable == '{page_type}') {
                    if ($currentPageId && $currentPageId == BannerPageRepository::PAGE_HOME) {
                        $bannerCodeString = str_replace($bannerVariable, 'homepage', $bannerCodeString);
                    } elseif ($currentPageId && $currentPageId == BannerPageRepository::PAGE_AD_DETAILS) {
                        $bannerCodeString = str_replace($bannerVariable, 'ad-details', $bannerCodeString);
                    } elseif ($currentPageId && $currentPageId == BannerPageRepository::PAGE_SEARCH_RESULTS) {
                        $bannerCodeString = str_replace($bannerVariable, 'search-results', $bannerCodeString);
                    } elseif ($currentPageId && $currentPageId == BannerPageRepository::PAGE_LANDING_PAGE) {
                        $bannerCodeString = str_replace($bannerVariable, 'Landing page', $bannerCodeString);
                    } else {
                        $bannerCodeString = str_replace($bannerVariable, '', $bannerCodeString);
                    }
                } elseif ($bannerVariable == '{user_type}') {
                    if (strstr($bannerVariable, '{user_type}')) {
                        $bannerVariable = '{user_type}';
                    }
                    $userType = '';
                    $objUser  = CommonManager::getLoggedInUser($this->container);

                    if (is_object($objUser)) {
                        if ($objUser->getRole()) {
                            $userType = $this->getUserTypeByRole($objUser->getRole()->getId());
                        }
                    }
                    $bannerCodeString = str_replace($bannerVariable, $userType, $bannerCodeString);
                } elseif (in_array($bannerVariable, $categoryVariables)) {
                    $categoryPath = '';
                    $categorySlug = '';
                    if ($currentPageId == BannerPageRepository::PAGE_AD_DETAILS) {
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad'])) {
                                $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($extraParams['ad']['a_category_id_i'], false, $this->container);
                            }
                        }
                    } elseif ($params && isset($params['item__category_id'])) {
                        $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($params['item__category_id'], false, $this->container);
                    } elseif ($currentPageId == BannerPageRepository::PAGE_LANDING_PAGE) {
                        if ($this->container->get('request_stack')->getCurrentRequest()->get('category_id')) {
                            $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($this->container->get('request_stack')->getCurrentRequest()->get('category_id'), false, $this->container);
                        }
                    }
                    if (is_array($categoryPath) && count($categoryPath) > 0) {
                        switch ($bannerVariable) {
                            case '{category}':
                                if (count($categoryPath) > 0) {
                                    $categorySlug = $categoryPath[0]['slug'];
                                }
                                break;
                            case '{class}':
                                if (count($categoryPath) > 1) {
                                    $categorySlug = $categoryPath[1]['slug'];
                                }
                                break;
                            case '{sub_class}':
                                if (count($categoryPath) > 2) {
                                    $categorySlug = $categoryPath[2]['slug'];
                                }
                                break;
                            case '{sub_sub_class}':
                                if (count($categoryPath) > 3) {
                                    $categorySlug = $categoryPath[3]['slug'];
                                }
                                break;
                        }
                    }
                    $bannerCodeString = str_replace($bannerVariable, $categorySlug, $bannerCodeString);
                } elseif ($bannerVariable == '{user_location}') {
                    $townName = '';
                    if ($currentPageId == BannerPageRepository::PAGE_AD_DETAILS) {
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad'])) {
                                $townName = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $extraParams['ad']['a_l_town_id_txt'][0]);
                            }
                        }
                    } elseif ($locationArray && isset($locationArray['town'])) {
                        $townName = $locationArray['town'];
                    }
                    $bannerCodeString = str_replace($bannerVariable, $townName, $bannerCodeString);
                } elseif ($bannerVariable == '{county}') {
                    $countyName = '';
                    if ($currentPageId == BannerPageRepository::PAGE_AD_DETAILS) {
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad'])) {
                                $countyName = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $extraParams['ad']['a_l_domicile_id_txt'][0]);
                            }
                        }
                    } elseif ($locationArray && is_array($locationArray)) {
                        if (isset($locationArray['county'])) {
                            $countyName = $locationArray['county'];
                        } elseif (isset($locationArray['paa_county'])) {
                            $countyName = $locationArray['paa_county'];
                        }
                    }
                    $bannerCodeString = str_replace($bannerVariable, $countyName, $bannerCodeString);
                } elseif ($bannerVariable == '{edition_area}') {
                    $printEditionName = '';
                    if ($currentPageId == BannerPageRepository::PAGE_AD_DETAILS) {
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad'])) {
                                $printEditionName = $this->em->getRepository('FaAdBundle:PrintEdition')->getPrintEditionColumnByTownId($extraParams['ad']['a_l_town_id_txt'][0], $this->container);
                            }
                        }
                    } elseif ($locationArray && isset($locationArray['town_id'])) {
                        $printEditionName = $this->em->getRepository('FaAdBundle:PrintEdition')->getPrintEditionColumnByTownId($locationArray['town_id'], $this->container);
                    }
                    $bannerCodeString = str_replace($bannerVariable, $printEditionName, $bannerCodeString);
                } elseif ($bannerVariable == '{target_id}') {
                    if ($extraParams && isset($extraParams['target_id'])) {
                        $bannerCodeString = str_replace($bannerVariable, $extraParams['target_id'], $bannerCodeString);
                    } else {
                        $bannerCodeString = str_replace($bannerVariable, '', $bannerCodeString);
                    }
                } elseif ($bannerVariable == '{search_keyword}') {
                    if ($params && isset($params['keywords'])) {
                        $bannerCodeString = str_replace($bannerVariable, $params['keywords'], $bannerCodeString);
                    } else {
                        $bannerCodeString = str_replace($bannerVariable, '', $bannerCodeString);
                    }
                } elseif ($bannerVariable == '{width}') {
                    if ($extraParams && isset($extraParams['max_width']) && $extraParams['max_width'] != null) {
                        $bannerCodeString = str_replace($bannerVariable, $extraParams['max_width'], $bannerCodeString);
                    } else {
                        $bannerCodeString = str_replace($bannerVariable, '', $bannerCodeString);
                    }
                } elseif ($bannerVariable == '{height}') {
                    if ($extraParams && isset($extraParams['max_height']) && $extraParams['max_height'] != null) {
                        $bannerCodeString = str_replace($bannerVariable, $extraParams['max_height'], $bannerCodeString);
                    } else {
                        $bannerCodeString = str_replace($bannerVariable, '', $bannerCodeString);
                    }
                } elseif ($bannerVariable == '{hashed_email}') {
                    $objUser = CommonManager::getLoggedInUser($this->container);
                    if ($objUser) {
                        $bannerCodeString = str_replace($bannerVariable, sha1($objUser->getEmail()), $bannerCodeString);
                    } else {
                        $bannerCodeString = str_replace($bannerVariable, '', $bannerCodeString);
                    }
                }
            }
        }

        $bannerCodeString = trim($bannerCodeString);

        return $bannerCodeString;
    }
    
    /**
     * Parse static block seo string.
     *
     * @param string $staticBlockCodeString Seo string.
     * @param array $extraParams.
     *
     * @return string
     */
    public function parseStaticBlockCode($staticBlockCodeString, $extraParams)
    {
        $currentRoute      = $this->container->get('request_stack')->getCurrentRequest()->get('_route');
        $params             = $this->container->get('request_stack')->getCurrentRequest()->get('finders');
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $staticBlockVariables    = array('{page_type}', '{user_type}', '{user_location}', '{county}', '{edition_area}', '{target_id}', '{search_keyword}', '{width}', '{height}', '{hashed_email}');
        $categoryVariables  = array('{category}', '{class}', '{sub_class}', '{sub_sub_class}');
        $allStaticBlockVariables = array_merge($staticBlockVariables, $categoryVariables);
        
        if ($extraParams && isset($extraParams['cookieValues'])) {
            $locationArray = json_decode($extraParams['cookieValues'], true);
        } elseif ($params && isset($params['item__location'])) {
            $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getCookieValue($params['item__location'], $this->container);
        } else {
            $locationArray = json_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('location'), true);
        }
        
        preg_match_all('/\{.*?\}/', $staticBlockCodeString, $staticBlockVariables);
        
        // replace variable values.
        if (count($staticBlockVariables)) {
            foreach ($staticBlockVariables[0] as $staticBlockVariable) {
                //Reset banner variable if not properly parsed.
                foreach ($allStaticBlockVariables as $bVariable) {
                    if (strstr($staticBlockVariable, $bVariable)) {
                        $staticBlockVariable = $bVariable;
                    }
                }
                
                if ($staticBlockVariable == '{page_type}') {
                    if ($currentRoute && ($currentRoute ==  'location_home_page')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'homepage', $staticBlockCodeString);
                    } elseif ($currentRoute && ($currentRoute ==  'ad_detail_page')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'ad-details', $staticBlockCodeString);
                    } elseif ($currentRoute && ($currentRoute ==  'listing_page'|| $currentRoute ==  'motor_listing_page')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'search-results', $staticBlockCodeString);
                    } elseif ($currentRoute && ($currentRoute == 'landing_page_category' || $currentRoute == 'landing_page_category_location')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'Landing page', $staticBlockCodeString);
                    } else {
                        if ($currentRoute=='') {
                            $staticBlockCodeString = str_replace($staticBlockVariable, 'homepage', $staticBlockCodeString);
                        } else {
                            $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                        }
                    }
                } elseif ($staticBlockVariable == '{user_type}') {
                    if (strstr($staticBlockVariable, '{user_type}')) {
                        $staticBlockVariable = '{user_type}';
                    }
                    $userType = '';
                    $objUser  = CommonManager::getLoggedInUser($this->container);
                    
                    if (!empty($objUser)) {
                        if ($objUser->getRole()) {
                            $userType = $this->getUserTypeByRole($objUser->getRole()->getId());
                        }
                    }
                    $staticBlockCodeString = str_replace($staticBlockVariable, $userType, $staticBlockCodeString);
                } elseif (in_array($staticBlockVariable, $categoryVariables)) {
                    $categoryPath = '';
                    $categorySlug = '';
                    if ($currentRoute && ($currentRoute ==  'ad_detail_page')) {
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad'])) {
                                $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($extraParams['ad']['a_category_id_i'], false, $this->container);
                            }
                        }
                    } elseif ($params && isset($params['item__category_id'])) {
                        $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($params['item__category_id'], false, $this->container);
                    } elseif ($currentRoute && ($currentRoute == 'landing_page_category' || $currentRoute == 'landing_page_category_location')) {
                        if ($this->container->get('request_stack')->getCurrentRequest()->get('category_id')) {
                            $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($this->container->get('request_stack')->getCurrentRequest()->get('category_id'), false, $this->container);
                        }
                    } elseif ($currentRoute && ($currentRoute == 'ad_post_first_step' || $currentRoute == 'ad_post_second_step' || $currentRoute == 'ad_post_fourth_step')) {
                        if ($this->container->get('session')->has('paa_first_step_data')) {
                            $postCatData = unserialize($this->container->get('session')->get('paa_first_step_data'));
                        }
                        
                        if (!empty($postCatData) && $postCatData['category_id']!='') {
                            $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($postCatData['category_id'], false, $this->container);
                        }
                    }
                    //echo 'currentRoute==='.$currentRoute;
                    if (is_array($categoryPath) && count($categoryPath) > 0) {
                        switch ($staticBlockVariable) {
                            case '{category}':
                                if (count($categoryPath) > 0) {
                                    $categorySlug = $categoryPath[0]['slug'];
                                }
                                break;
                            case '{class}':
                                if (count($categoryPath) > 1) {
                                    $categorySlug = $categoryPath[1]['slug'];
                                }
                                break;
                            case '{sub_class}':
                                if (count($categoryPath) > 2) {
                                    $categorySlug = $categoryPath[2]['slug'];
                                }
                                break;
                            case '{sub_sub_class}':
                                if (count($categoryPath) > 3) {
                                    $categorySlug = $categoryPath[3]['slug'];
                                }
                                break;
                        }
                    }
                    $staticBlockCodeString = str_replace($staticBlockVariable, $categorySlug, $staticBlockCodeString);
                } elseif ($staticBlockVariable == '{user_location}') {
                    $townName = '';
                    if ($currentRoute && ($currentRoute ==  'ad_detail_page')) {
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad'])) {
                                $townName = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $extraParams['ad']['a_l_town_id_txt'][0]);
                            }
                        }
                    } elseif ($locationArray && isset($locationArray['town'])) {
                        $townName = $locationArray['town'];
                    }
                    $staticBlockCodeString = str_replace($staticBlockVariable, $townName, $staticBlockCodeString);
                } elseif ($staticBlockVariable == '{county}') {
                    $countyName = '';
                    if ($currentRoute && ($currentRoute ==  'ad_detail_page')) {
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad'])) {
                                $countyName = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $extraParams['ad']['a_l_domicile_id_txt'][0]);
                            }
                        }
                    } elseif ($locationArray && is_array($locationArray)) {
                        if (isset($locationArray['county'])) {
                            $countyName = $locationArray['county'];
                        } elseif (isset($locationArray['paa_county'])) {
                            $countyName = $locationArray['paa_county'];
                        }
                    }
                    $staticBlockCodeString = str_replace($staticBlockVariable, $countyName, $staticBlockCodeString);
                } elseif ($staticBlockVariable == '{edition_area}') {
                    $printEditionName = '';
                    if ($currentRoute && ($currentRoute ==  'ad_detail_page')) {
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad'])) {
                                $printEditionName = $this->em->getRepository('FaAdBundle:PrintEdition')->getPrintEditionColumnByTownId($extraParams['ad']['a_l_town_id_txt'][0], $this->container);
                            }
                        }
                    } elseif ($locationArray && isset($locationArray['town_id'])) {
                        $printEditionName = $this->em->getRepository('FaAdBundle:PrintEdition')->getPrintEditionColumnByTownId($locationArray['town_id'], $this->container);
                    }
                    $staticBlockCodeString = str_replace($staticBlockVariable, $printEditionName, $staticBlockCodeString);
                } elseif ($staticBlockVariable == '{target_id}') {
                    if ($extraParams && isset($extraParams['target_id'])) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $extraParams['target_id'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{search_keyword}') {
                    if ($params && isset($params['keywords'])) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $params['keywords'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{width}') {
                    if ($extraParams && isset($extraParams['max_width']) && $extraParams['max_width'] != null) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $extraParams['max_width'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{height}') {
                    if ($extraParams && isset($extraParams['max_height']) && $extraParams['max_height'] != null) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $extraParams['max_height'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{hashed_email}') {
                    $objUser = CommonManager::getLoggedInUser($this->container);
                    if ($objUser) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, sha1($objUser->getEmail()), $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                }
            }
        }
        
        $staticBlockCodeString = trim($staticBlockCodeString);
        
        return $staticBlockCodeString;
    }

    /**
     * Parse static block seo string.
     *
     * @param string $staticBlockCodeString Seo string.
     * @param array $extraParams.
     *
     * @return string
     */
    public function parseStaticBlockGTMCode($staticBlockCodeString, $extraParams)
    {
        $currentRoute      = $this->container->get('request_stack')->getCurrentRequest()->get('_route');
        $params             = $this->container->get('request_stack')->getCurrentRequest()->get('finders');
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $staticBlockVariables    = array('{page_type}', '{user_type}', '{user_location}','{town}', '{county}', '{country}', '{edition_area}', '{london_areas}', '{target_id}', '{ad_published}', '{ad_location}', '{seller_contact_methods}', '{ad_price}', '{ad_id}', '{user_logged}', '{search_keyword}', '{width}', '{height}', '{hashed_email}','{category}', '{class}', '{sub_class}', '{sub_sub_class}');

        if ($extraParams && isset($extraParams['cookieValues'])) {
            $locationArray = json_decode($extraParams['cookieValues'], true);
        } elseif ($params && isset($params['item__location'])) {
            $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getCookieValue($params['item__location'], $this->container);
        } else {
            $locationArray = json_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('location'), true);
        }

        $keywordCategories = array(); $keywordCat = null;
        if(($currentRoute && ($currentRoute ==  'listing_page'|| $currentRoute ==  'motor_listing_page')) && ($extraParams && isset($extraParams['facetResult']))) {
            $mainCategories = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container);
            foreach ($mainCategories as $categoryId=>$categoryName) {
                $adCount = 0;
                $nestedChildrenCategories = $this->em->getRepository('FaEntityBundle:Category')->getNestedLeafChildrenIdsByCategoryId($categoryId, $this->container);
                foreach ($nestedChildrenCategories as $nestedCategoryId) {
                    if (isset($extraParams['facetResult']['a_category_id_i'][$nestedCategoryId])) {
                        $adCount =  $adCount + $extraParams['facetResult']['a_category_id_i'][$nestedCategoryId];
                    }
                }
                if($adCount>0) {
                    array_push($keywordCategories, $categoryName);
                }
            }
            if(!empty($keywordCategories)) { $keywordCat = implode(",",$keywordCategories); }
        }

        preg_match_all('/\{.*?\}/', $staticBlockCodeString, $staticBlockVariables);
        $categoryPath = $locDet = array(); $rootCategoryId = $town = $townLvl = $county = $printEditionName = '';
        $contactMethod = $srchKeyword = $adDet_adId = $selcountry = $adlocation = '';


        if ($currentRoute && $currentRoute ==  'ad_detail_page') {
            if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                if (isset($extraParams['ad'])) {
                    $adDet_adId = $extraParams['ad']['id'];
                    $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($extraParams['ad']['a_category_id_i'], false, $this->container);
                    $selcountry = 'UK';
                    if(isset($extraParams['ad']['a_l_town_id_txt'][0])) {
                        $locDet = $this->em->getRepository('FaEntityBundle:Location')->find($extraParams['ad']['a_l_town_id_txt'][0]);
                        if(!empty($locDet)) {
                            $town = $locDet->getName();
                            $townLvl = $locDet->getLvl();
                            $adlocation = $locDet->getName();
                        }
                        $county = $this->em->getRepository('FaEntityBundle:Location')->getCountyByTownId($extraParams['ad']['a_l_town_id_txt'][0]);
                        $printEditionName = $this->em->getRepository('FaAdBundle:PrintEdition')->getPrintEditionColumnByTownId($extraParams['ad']['a_l_town_id_txt'][0], $this->container);
                    }
                    if(isset($extraParams['ad']['user'])) {
                        if($extraParams['ad']['user']['contact_through_email'] == 1) { $contactMethod = $contactMethod.'Email'; }
                        if($extraParams['ad']['user']['contact_through_phone'] == 1) {
                            if($contactMethod!='') { $contactMethod = $contactMethod.'|Phone'; }
                            else { $contactMethod = $contactMethod.'Phone'; }
                        }
                    }
                }
            }
        } elseif ($currentRoute && ($currentRoute ==  'listing_page'|| $currentRoute ==  'motor_listing_page')) {
            if ($params && isset($params['item__category_id'])) {
                $categoryPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathDetailArrayById($params['item__category_id'], false, $this->container);
                $rootCategoryId = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryId($params['item__category_id'], $this->container);
            }
            if ($params && isset($params['item__location'])) {
                $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getCookieValue($params['item__location'], $this->container);
                if(!empty($locationArray)) {
                    $town = $this->em->getRepository('FaEntityBundle:Location')->find($locationArray['town_id']);
                    $county = $this->em->getRepository('FaEntityBundle:Location')->getCountyByTownId($locationArray['town_id']);
                    $printEditionName = $this->em->getRepository('FaAdBundle:PrintEdition')->getPrintEditionColumnByTownId($locationArray['town_id'], $this->container);
                    $townLvl = $locationArray['lvl'];
                    $selcountry = 'UK';
                }
            }
            if ($params && isset($params['keywords'])) {
                $srchKeyword = $params['keywords'];
            }
        }

        // replace variable values.
        if (!empty($staticBlockVariables)) {
            foreach ($staticBlockVariables[0] as $staticBlockVariable) {

                if ($staticBlockVariable == '{page_type}') {
                    if ($currentRoute && ($currentRoute ==  'location_home_page')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'Homepage', $staticBlockCodeString);
                    } elseif ($currentRoute && ($currentRoute ==  'login' || $currentRoute ==  'fa_user_register')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'Login_Or_Register', $staticBlockCodeString);
                    } elseif ($currentRoute && ($currentRoute ==  'ad_detail_page')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'Ad details', $staticBlockCodeString);
                    } elseif ($currentRoute && ($currentRoute ==  'listing_page'|| $currentRoute ==  'motor_listing_page')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'Search Results', $staticBlockCodeString);
                    } elseif ($currentRoute && ($currentRoute == 'landing_page_category' || $currentRoute == 'landing_page_category_location')) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'Landing page', $staticBlockCodeString);
                    } else {
                        if ($currentRoute=='' || $currentRoute=='fa_frontend_homepage') {
                            $staticBlockCodeString = str_replace($staticBlockVariable, 'Homepage', $staticBlockCodeString);
                        } else {
                            $staticBlockCodeString = str_replace($staticBlockVariable, 'Peripheral_Content', $staticBlockCodeString);
                        }
                    }
                } elseif ($staticBlockVariable == '{user_logged}') {
                    $objUser = CommonManager::getLoggedInUser($this->container);
                    if($objUser) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'true', $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, 'false', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{user_type}') {
                    if (strstr($staticBlockVariable, '{user_type}')) {
                        $staticBlockVariable = '{user_type}';
                    }
                    $userType = '';
                    if ($currentRoute && ($currentRoute ==  'listing_page'|| $currentRoute ==  'motor_listing_page')) {
                        $userType = 'Others';
                        if ($params && isset($params['item__is_trade_ad'])) {
                            $userType = 'Private';
                            if ($params['item__is_trade_ad']==1) {
                                $userType = 'Dealer';
                                if($rootCategoryId==CategoryRepository::FOR_SALE_ID) { $userType = 'Shop'; }
                                elseif($rootCategoryId==CategoryRepository::JOBS_ID) { $userType = 'Recruiter'; }
                            }
                        }
                    } elseif ($currentRoute && ($currentRoute ==  'ad_detail_page')) {
                        $userType = 'Others';
                        if ($extraParams && count($extraParams) > 0 && isset($extraParams['ad'])) {
                            if (isset($extraParams['ad']['user']) && isset($extraParams['ad']['user']['role_id'])) {
                                $userType = 'Private';
                                if($extraParams['ad']['user']['role_id']== RoleRepository::ROLE_BUSINESS_SELLER_ID || $extraParams['ad']['user']['role_id']== RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID) {
                                    $userType = 'Dealer';
                                    if($extraParams['ad']['user']['business_category_id']==CategoryRepository::FOR_SALE_ID) { $userType = 'Shop'; }
                                    elseif($extraParams['ad']['user']['business_category_id']==CategoryRepository::JOBS_ID) { $userType = 'Recruiter'; }
                                }
                            }
                        }
                    }
                    if($userType) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $userType, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('user_type', ['{user_type}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{category}') {
                    if ($categoryPath && isset($categoryPath[0]) && $categoryPath[0]['slug'] != '') {
                        $categorySlug = $categoryPath[0]['slug'];
                        $staticBlockCodeString = str_replace($staticBlockVariable, $categorySlug, $staticBlockCodeString);
                    } elseif($keywordCat) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $keywordCat, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('category',['{category}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{class}') {
                    if ($categoryPath && isset($categoryPath[1]) &&  $categoryPath[1]['slug']!='') {
                        $categorySlug = $categoryPath[1]['slug'];
                        $staticBlockCodeString = str_replace($staticBlockVariable, $categorySlug, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('class',['{class}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{sub_class}') {
                    if ($categoryPath && isset($categoryPath[2]) &&  $categoryPath[2]['slug']!='') {
                        $categorySlug = $categoryPath[2]['slug'];
                        $staticBlockCodeString = str_replace($staticBlockVariable, $categorySlug, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('sub_class',['{sub_class}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{sub_sub_class}') {
                    if ($categoryPath && isset($categoryPath[3]) &&  $categoryPath[3]['slug']!='') {
                        $categorySlug = $categoryPath[3]['slug'];
                        $staticBlockCodeString = str_replace($staticBlockVariable, $categorySlug, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('sub_sub_class',['{sub_sub_class}'])", '', $staticBlockCodeString);
                    }
                 } elseif ($staticBlockVariable == '{user_location}' || $staticBlockVariable == '{town}' || $staticBlockVariable == '{ad_location}') {
                    if ($town) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $town, $staticBlockCodeString);
                    } elseif ($adlocation) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $adlocation, $staticBlockCodeString);
                    } else {
                        if ($staticBlockVariable == '{user_location}') {
                            $staticBlockCodeString = str_replace(".setTargeting('user_location', ['{user_location}'])", '', $staticBlockCodeString);
                        } elseif ($staticBlockVariable == '{town}') {
                            $staticBlockCodeString = str_replace(".setTargeting('town', ['{town}'])", '', $staticBlockCodeString);
                        } elseif ($staticBlockVariable == '{ad_location}') {
                            $staticBlockCodeString = str_replace(".setTargeting('ad_location', ['{ad_location}'])", '', $staticBlockCodeString);
                        }
                    }
                } elseif ($staticBlockVariable == '{london_areas}') {
                    if($townLvl==4) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $town, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('london_areas', ['{london_areas}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{county}') {
                    if($county) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $county, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('county', ['{county}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{country}') {
                    if($selcountry) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $selcountry, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('country', ['{country}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{edition_area}') {
                    if($currentRoute && ($currentRoute ==  'ad_detail_page' || $currentRoute ==  'listing_page'|| $currentRoute ==  'motor_listing_page')) {
                        if($printEditionName) {
                            $staticBlockCodeString = str_replace($staticBlockVariable, $printEditionName.' - Print', $staticBlockCodeString);
                        } else {
                            $staticBlockCodeString = str_replace($staticBlockVariable, 'Non Print', $staticBlockCodeString);
                        }
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('edition_area', ['{edition_area}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{target_id}') {
                    if ($extraParams && isset($extraParams['target_id'])) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $extraParams['target_id'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{ad_published}') {
                    if ($extraParams && isset($extraParams['a_published_at_i']) && $currentRoute && $currentRoute ==  'ad_detail_page') {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $extraParams['a_published_at_i'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('ad_published', ['{ad_published}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{ad_price}') {
                    if ($extraParams && isset($extraParams['a_price_d']) && $currentRoute && $currentRoute ==  'ad_detail_page') {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $extraParams['a_price_d'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('ad_price', ['{ad_price}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{seller_contact_methods}') {
                    if ($contactMethod) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $contactMethod, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('seller_contact_methods', ['{seller_contact_methods}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{search_keyword}') {
                    if ($srchKeyword) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $srchKeyword, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('search_keyword', ['{search_keyword}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{ad_id}') {
                    if ($adDet_adId) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $adDet_adId, $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace(".setTargeting('ad_id',['{ad_id}'])", '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{width}') {
                    if ($extraParams && isset($extraParams['max_width']) && $extraParams['max_width'] != null) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $extraParams['max_width'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{height}') {
                    if ($extraParams && isset($extraParams['max_height']) && $extraParams['max_height'] != null) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, $extraParams['max_height'], $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                } elseif ($staticBlockVariable == '{hashed_email}') {
                    $objUser = CommonManager::getLoggedInUser($this->container);
                    if ($objUser) {
                        $staticBlockCodeString = str_replace($staticBlockVariable, sha1($objUser->getEmail()), $staticBlockCodeString);
                    } else {
                        $staticBlockCodeString = str_replace($staticBlockVariable, '', $staticBlockCodeString);
                    }
                }
            }
        }

        $staticBlockCodeString = trim($staticBlockCodeString);

        return $staticBlockCodeString;
    }

    /**
     * Get user types.
     *
     * @return array
     */
    public static function getUserTypeByRole($roleId)
    {
        $userTypeArray = array(
                          RoleRepository::ROLE_SELLER_ID          => 'private',
                          RoleRepository::ROLE_BUSINESS_SELLER_ID => 'dealer',
                          RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID => 'netsuite subscriber',
                         );
        if (array_key_exists($roleId, $userTypeArray)) {
            return $userTypeArray[$roleId];
        }
    }
}
