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
     * Get user types.
     *
     * @return array
     */
    public static function getUserTypeByRole($roleId)
    {
        $userTypeArray = array(
                          RoleRepository::ROLE_SELLER_ID          => 'private',
                          RoleRepository::ROLE_BUSINESS_SELLER_ID => 'dealer',
                         );
        if (in_array($roleId, $userTypeArray)) {
            return $userTypeArray[$roleId];
        }
    }
}
