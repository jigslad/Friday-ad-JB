<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This class is used for seo management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class SeoManager
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
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Parse ad detail seo string.
     *
     * @param string $seoString Seo string.
     * @param object $adSolrObj Ad solr object.
     *
     * @return string
     */
    public function parseAdDetailSeoString($seoString, $adSolrObj)
    {
        $adDetailFields = array();
        // get all dimensions.
        $indexableDimensionArray = array_flip(CategoryDimensionRepository::getIndexableDimesionsArray());
        preg_match_all('/\{.*?\}/', $seoString, $seoFields);
        preg_match_all('/\[.*?\{.*?\}.*?\]/', $seoString, $seoBracesFields);

        if (count($seoFields)) {
            foreach ($seoFields[0] as $seoField) {
                $seoField = trim(str_ireplace(array('{', '}'), '', $seoField));
                if (isset($indexableDimensionArray['{'.$seoField.'}'])) {
                    $adDetailField = $indexableDimensionArray['{'.$seoField.'}'];
                    if (!in_array($adDetailField, array('TITLE', 'DESCRIPTION_SHORT', 'DESCRIPTION', 'MAIN_TOWN_ID'))) {
                        $adDetailField = str_ireplace(array('WOMENS_CLOTHES_', 'MENS_CLOTHES_', 'ADULT_SHOE_', 'KIDS_SHOE_'), '', $adDetailField);
                        $repositoryName = 'FaEntityBundle:Entity';
                        if ($adDetailField == 'CATEGORY_ID') {
                            $repositoryName = 'FaEntityBundle:Category';
                        }
                        $adDetailFields[$adDetailField.'|'.$repositoryName] = $adDetailField;
                    }
                    if (!in_array($adDetailField, array('MAKE_ID', 'MODEL_ID')) && !isset($adDetailFields['CATEGORY_ID|FaEntityBundle:Category'])) {
                        $adDetailFields['CATEGORY_ID|FaEntityBundle:Category'] = 'CATEGORY_ID';
                    }
                }
            }
            $adDetailFields = array_unique($adDetailFields);
        }

        $adSolrObj        = get_object_vars($adSolrObj);
        $adCategoryId     = $adSolrObj['a_category_id_i'];
        $adRootCategoryId = $adSolrObj[AdSolrFieldMapping::ROOT_CATEGORY_ID];
        $rootCategoryName = CommonManager::getCategoryClassNameById($adRootCategoryId, true);
        $solrMapping      = 'Fa\Bundle\AdBundle\Solr\Ad'.$rootCategoryName.'SolrFieldMapping::';
        $adDetailArray    = $this->em->getRepository('FaAdBundle:Ad')->getAdDetailAndDimensionFields($adCategoryId, $adSolrObj, $this->container, true, $adDetailFields);

        // replace dimensions.
        if (count($seoFields)) {
            foreach ($seoFields[0] as $seoField) {
                $orgSeoField      = $seoField;
                $seoField         = trim(str_ireplace(array('{', '}'), '', $seoField));
                if (isset($indexableDimensionArray['{'.$seoField.'}'])) {
                    $fieldVal = $this->getSolrFieldValue($solrMapping, $indexableDimensionArray['{'.$seoField.'}'], $adSolrObj, $adDetailArray);
                    if ($fieldVal) {
                        if (count($seoBracesFields)) {
                            foreach ($seoBracesFields[0] as $seoBracesField) {
                                if (strpos($seoBracesField, $orgSeoField) !== false) {
                                    $seoString = preg_replace('/\[(.*?)\{'.$seoField.'}(.*?)\]/', "\${1}".$fieldVal."\${2}", $seoString);
                                }
                            }
                        }
                        $seoString = str_ireplace($orgSeoField, $fieldVal, $seoString);
                    } else {
                        if (count($seoBracesFields)) {
                            foreach ($seoBracesFields[0] as $seoBracesField) {
                                if (strpos($seoBracesField, $orgSeoField) !== false) {
                                    $seoString = str_ireplace($seoBracesField, '', $seoString);
                                }
                            }
                        }
                        $seoString = str_ireplace($orgSeoField, '', $seoString);
                    }
                } else {
                    if (count($seoBracesFields)) {
                        foreach ($seoBracesFields[0] as $seoBracesField) {
                            if (strpos($seoBracesField, $orgSeoField) !== false) {
                                $seoString = str_ireplace($seoBracesField, '', $seoString);
                            }
                        }
                    }
                    $seoString = str_ireplace($orgSeoField, '', $seoString);
                }
            }
        }

        $seoString = preg_replace(array('/(\s*,\s*){2,}/', '/^(\s*,\s*)+/', '/(\s*,\s*)+$/'), array(', ', '', ''), $seoString);
        $seoString = preg_replace(array('/(\s*-\s*){2,}/', '/^(\s*-\s*)+/', '/(\s*-\s*)+$/'), array(' - ', '', ''), $seoString);
        $seoString = trim($seoString);

        // if no seo string then show ad title.
        if (!$seoString && defined($solrMapping.'TITLE') && isset($adSolrObj[constant($solrMapping.'TITLE')])) {
            $seoString = $adSolrObj[constant($solrMapping.'TITLE')];
        }

        return $seoString;
    }

    /**
     * Get seo field value.
     *
     * @param string $solrMapping   Solr mapping class name.
     * @param string $solrFieldName Solr field name
     * @param object $adSolrObj     Ad solr object.
     * @param array  $adDetailArray Ad detail array.
     *
     * @return mixed
     */
    private function getSolrFieldValue($solrMapping, $solrFieldName, $adSolrObj, $adDetailArray)
    {
        $fieldVal           = null;
        $solrFieldName      = str_ireplace(array('WOMENS_CLOTHES_', 'MENS_CLOTHES_', 'ADULT_SHOE_', 'KIDS_SHOE_'), '', $solrFieldName);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $solrMappingField   = $solrMapping.$solrFieldName;

        // if short description then limit the char to 140.
        if ($solrFieldName == 'DESCRIPTION_SHORT' && defined($solrMapping.'DESCRIPTION') && isset($adSolrObj[constant($solrMapping.'DESCRIPTION')])) {
            return CommonManager::trimText($adSolrObj[constant($solrMapping.'DESCRIPTION')], 140, '');
        } elseif ($solrFieldName == 'STATUS_ID' && defined($solrMapping.'STATUS_ID') && isset($adSolrObj[constant($solrMapping.'STATUS_ID')])) {
            if (in_array($adSolrObj[constant($solrMapping.'STATUS_ID')], array(EntityRepository::AD_STATUS_EXPIRED_ID, EntityRepository::AD_STATUS_SOLD_ID))) {
                $fieldVal = $this->em->getRepository('FaEntityBundle:Entity')->getSeoValueById($adSolrObj[constant($solrMapping.'STATUS_ID')], $this->container);
                if (!$fieldVal) {
                    return $fieldVal = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $adSolrObj[constant($solrMapping.'STATUS_ID')]);
                }
            } else {
                return null;
            }
        }

        // parse seller string
        if ($solrFieldName == 'IS_TRADE_AD' && defined($solrMapping.'IS_TRADE_AD') && isset($adSolrObj[constant($solrMapping.'IS_TRADE_AD')])) {
            if ($adSolrObj[constant($solrMapping.'IS_TRADE_AD')]) {
                return $this->translator->trans('Dealer', array(), 'frontend-show-ad');
            } else {
                return $this->translator->trans('Private', array(), 'frontend-show-ad');
            }
        }

        // get field name value from solr.
        if (isset($adDetailArray[$solrFieldName]) && $adDetailArray[$solrFieldName]) {
            $fieldVal = $adDetailArray[$solrFieldName];

            if (is_array($fieldVal)) {
                $fieldVal = implode(', ', $fieldVal);
            }

            return strip_tags($fieldVal);
        } elseif (defined($solrMappingField) && isset($adSolrObj[constant($solrMappingField)])) {
            if ($solrFieldName == 'MAIN_TOWN_ID') {
                $fieldVal = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $adSolrObj[constant($solrMappingField)]);
            } elseif ($solrFieldName == 'CATEGORY_ID') {
                $fieldVal = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $adSolrObj[constant($solrMappingField)]);
            } else {
                $fieldVal = $adSolrObj[constant($solrMappingField)];
            }

            if (is_array($fieldVal)) {
                $fieldVal = implode(', ', $fieldVal);
            }

            return strip_tags($fieldVal);
        } else {
            return false;
        }
    }

    /**
     * Parse seo string.
     *
     * @param string $seoString       Seo string.
     * @param array  $seoReplaceArray Seo replace array.
     *
     * @return string
     */
    public function parseSeoString($seoString, $seoReplaceArray)
    {
        preg_match_all('/\{.*?\}/', $seoString, $seoFields);

        // replace seo fileds.
        if (count($seoFields)) {
            foreach ($seoFields[0] as $seoField) {
                $seoField = strtolower($seoField);
                if (array_key_exists($seoField, $seoReplaceArray) !== false && isset($seoReplaceArray[$seoField])) {
                    $seoString = str_ireplace($seoField, $seoReplaceArray[strtolower($seoField)], $seoString);
                } else {
                    $seoString = str_ireplace($seoField, '', $seoString);
                }
            }
        }

        $seoString = preg_replace(array('/(\s*,\s*){2,}/', '/^(\s*,\s*)+/', '/(\s*,\s*)+$/'), array(', ', '', ''), $seoString);
        $seoString = preg_replace(array('/(\s*-\s*){2,}/', '/^(\s*-\s*)+/', '/(\s*-\s*)+$/'), array(' - ', '', ''), $seoString);
        $seoString = trim($seoString);

        return $seoString;
    }

    /**
     * Parse seo string for ad list.
     *
     * @param string $seoString             Seo string.
     * @param array  $searchParams          Search params array.
     * @param array  $cookieLocationDetails Cookie location array.
     *
     * @return string
     */
    public function parseSeoStringForAdList($seoString, $searchParams, $cookieLocationDetails, $canonical_url=false)
    {
        $parentCategoryIds  = array();
        $categoryClassName  = null;
        $seoReplaceArray    = array();
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $seoLocationName    = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', LocationRepository::COUNTY_ID);
           
        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['locality']) && $cookieLocationDetails['locality']) {
            $seoLocationName = $cookieLocationDetails['locality'];
            if ($this->em->getRepository('FaEntityBundle:Locality')->isDuplicateName($cookieLocationDetails['locality'], $this->container)
                && isset($cookieLocationDetails['town']) && $cookieLocationDetails['town']) {
                $seoLocationName .= ', '.$cookieLocationDetails['town'];
            }
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['county']) && $cookieLocationDetails['county']) {
            $seoLocationName = $cookieLocationDetails['county'];
        } elseif (is_array($cookieLocationDetails) && isset($cookieLocationDetails['town']) && $cookieLocationDetails['town']) {
            //check location is belongs to area or special area
            $seoLocationName = $this->em->getRepository('FaEntityBundle:Location')->getTownLocationNameForSeo($cookieLocationDetails['town'], $this->container);
            if ($this->em->getRepository('FaEntityBundle:Location')->isDuplicateName($cookieLocationDetails['town'], $this->container)
                && isset($cookieLocationDetails['paa_county']) && $cookieLocationDetails['paa_county']) {
                $seoLocationName .= ', '.$cookieLocationDetails['paa_county'];
            }
        }
    
        $seoReplaceArray['{location}'] = ($seoLocationName == 'United Kingdom' ? 'UK' : $seoLocationName);
        if (count($searchParams) && isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
            $seoReplaceArray['{category}'] = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $searchParams['item__category_id']);
            $parentCategories              = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($searchParams['item__category_id'], false, $this->container);
            $parentCategoryIds             = array_keys($parentCategories);
            $rootCategoryId                = (isset($parentCategoryIds[0]) ? $parentCategoryIds[0] : null);
            $categoryClassName             = CommonManager::getCategoryClassNameById($rootCategoryId);
        }

        // get all dimensions.
        $indexableDimensionArray = array_flip(SeoToolRepository::getIndexableDimesionsArray());
        unset($indexableDimensionArray['{Category}'], $indexableDimensionArray['{Location}']);
        preg_match_all('/\{.*?\}/', $seoString, $seoFields);
        preg_match_all('/\[.*?\{.*?\}.*?\]/', $seoString, $seoBracesFields);

        // replace seo fileds from replacement array.
        if (count($seoFields)) {
            foreach ($seoFields[0] as $seoField) {
                $seoField = strtolower($seoField);
                if (array_key_exists($seoField, $seoReplaceArray) !== false && isset($seoReplaceArray[$seoField])) {
                    $seoString = str_ireplace($seoField, $seoReplaceArray[strtolower($seoField)], $seoString);
                }
            }
            $unsetSeofields = array('{Category}', '{Location}');
            foreach ($unsetSeofields as $unsetSeofield) {
                if (in_array($unsetSeofield, $seoFields[0])) {
                    $fieldKey = array_search($unsetSeofield, $seoFields[0]);
                    $seoString = str_ireplace($unsetSeofield, '', $seoString);
                    unset($seoFields[0][$fieldKey]);
                }
            }
        }

        // replace dimensions.
        if (count($seoFields)) {
            foreach ($seoFields[0] as $seoField) {
                $orgSeoField      = $seoField;
                $seoField         = trim(str_ireplace(array('{', '}'), '', $seoField));
                if (isset($indexableDimensionArray['{'.$seoField.'}'])) {
                    $fieldVal = $this->getSeoListFieldValue($indexableDimensionArray['{'.$seoField.'}'], $searchParams, $categoryClassName, $parentCategoryIds);
                    if ($fieldVal) {
                        if (count($seoBracesFields)) {
                            foreach ($seoBracesFields[0] as $seoBracesField) {
                                if (strpos($seoBracesField, $orgSeoField) !== false) {
                                    $seoString = preg_replace('/\[(.*?)\{'.$seoField.'}(.*?)\]/', "\${1}".($fieldVal)."\${2}", $seoString);
                                }
                            }
                        }
                        $seoString = str_ireplace($orgSeoField, $fieldVal, $seoString);
                    } else {
                        if (count($seoBracesFields)) {
                            foreach ($seoBracesFields[0] as $seoBracesField) {
                                if (strpos($seoBracesField, $orgSeoField) !== false) {
                                    $seoString = str_ireplace($seoBracesField, '', $seoString);
                                }
                            }
                        }
                        $seoString = str_ireplace($orgSeoField, '', $seoString);
                    }
                } else {
                    if (count($seoBracesFields)) {
                        foreach ($seoBracesFields[0] as $seoBracesField) {
                            if (strpos($seoBracesField, $orgSeoField) !== false) {
                                $seoString = str_ireplace($seoBracesField, '', $seoString);
                            }
                        }
                    }
                    $seoString = str_ireplace($orgSeoField, '', $seoString);
                }
            }
        }
        
        $seoString = preg_replace(array('/(\s*,\s*){2,}/', '/^(\s*,\s*)+/', '/(\s*,\s*)+$/'), array(', ', '', ''), $seoString);
        $seoString = preg_replace(array('/(\s*-\s*){2,}/', '/^(\s*-\s*)+/', '/(\s*-\s*)+$/'), array(' - ', '', ''), $seoString);
        
        if (!$canonical_url) {
            $seoString = preg_replace('~/{2,}~', '/', $seoString);
        }
        $seoString = trim($seoString);

        return $seoString;
    }

    /**
     * Get seo field value.
     *
     * @param string $fieldName             Field name.
     * @param array  $searchParams          Search params array.
     * @param string $categoryClassName     Field name.
     * @param array  $parentCategoryIds    Second level category id.
     *
     * @return mixed
     */
    private function getSeoListFieldValue($fieldName, $searchParams, $categoryClassName, $parentCategoryIds)
    {
        $fieldVal              = null;
        $entityCacheManager    = $this->container->get('fa.entity.cache.manager');
        $secondLevelCategoryId = (isset($parentCategoryIds[1]) ? $parentCategoryIds[1] : null);

        if ($fieldName != 'type_id') {
            $searchFieldName    = 'item_'.$categoryClassName.'__'.$fieldName;
        } elseif ($fieldName == 'type_id') {
            $searchFieldName    = 'item__ad_'.$fieldName;
        }

        // get field name value from search.
        if (count($searchParams) && isset($searchParams['item__category_id']) && $searchParams['item__category_id'] && $categoryClassName) {
            if (isset($searchParams[$searchFieldName]) && $searchParams[$searchFieldName]) {
                $fieldVal = $searchParams[$searchFieldName];

                if (is_array($fieldVal)) {
                    $fieldValArray = array();
                    if ($fieldName == 'reg_year') {
                        $fieldValArray = $fieldVal;
                    } else {
                        foreach ($fieldVal as $val) {
                            if ($fieldName == 'mileage_range') {
                                $mileageRange = CommonManager::getMileageChoices();
                                if (isset($mileageRange[$val])) {
                                    $fieldValArray[] = $mileageRange[$val];
                                }
                            } elseif ($fieldName == 'engine_size_range') {
                                $engineSizeRange = CommonManager::getEngineSizeChoices();
                                if (isset($engineSizeRange[$val])) {
                                    $fieldValArray[] = $engineSizeRange[$val];
                                }
                            } else {
                                $seoValue = $this->em->getRepository('FaEntityBundle:Entity')->getSeoValueById($val, $this->container);
                                if ($seoValue) {
                                    $fieldValArray[] = $seoValue;
                                } else {
                                    $fieldValArray[] = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $val);
                                }
                            }
                        }
                    }
                    $fieldVal = implode(' ', $fieldValArray);
                } else {
                    $fieldVal = $this->em->getRepository('FaEntityBundle:Entity')->getSeoValueById($adDetailArray[$fieldName], $this->container);
                    if (!$fieldVal) {
                        $fieldVal = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $adDetailArray[$fieldName]);
                    }
                }
            } elseif (in_array($fieldName, array('make_id', 'model_id')) && in_array($secondLevelCategoryId, array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID))) {
                if ($fieldName == 'make_id' && isset($parentCategoryIds[2])) {
                    $fieldVal = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $parentCategoryIds[2]);
                }
                if ($fieldName == 'model_id' && isset($parentCategoryIds[3])) {
                    $fieldVal = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $parentCategoryIds[3]);
                }
            }

            return trim(strip_tags($fieldVal));
        } else {
            return false;
        }
    }
}
