<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * Seo tool override repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolOverrideRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'sto';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Add status filter.
     *
     * @param integer Status entity type.
     */
    protected function addStatusFilter($status = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.status = '.$status);
    }

    /**
     * Get seo tool override table name.
     */
    private function getSeoToolOverrideTableName()
    {
        return $this->_em->getClassMetadata('FaContentBundle:SeoToolOverride')->getTableName();
    }

    /**
     * Get entity table name.
     */
    private function getEntityTableName()
    {
        return $this->_em->getClassMetadata('FaContentBundle:SeoToolOverride')->getTableName();
    }

    /**
     * Add basic fields search filter.
     *
     * @param integer $keyword basic fields keyword.
     */
    protected function addBasicFieldsSearchFilter($keyword = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.page_url LIKE \'%%%s%%\' OR %s.page_title LIKE \'%%%s%%\' OR %s.h1_tag LIKE \'%%%s%%\' OR %s.meta_description LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $keyword, $this->getRepositoryAlias(), $keyword, $this->getRepositoryAlias(), $keyword, $this->getRepositoryAlias(), $keyword));
    }

    /**
     * Find active seo rule by page url.
     *
     * @param string $page         Page url.
     * @param array  $searchParams Search parameters.
     * @param object $container    Container identifier.
     *
     * @return mixed
     */
    public function findSeoRuleByPageUrl($fullpageUrl, $searchParams, $container = null)
    {
        $objSeoToolOverride = null;
        $splitpageUrl = explode('?', $fullpageUrl);
        $pageUrl = '';
        $pageUrl = $splitpageUrl[0];
        $queryContent = $queryItem = '';
        $isClassicCar = 0;
        $getCatFromUrl = $objSeoToolOverrideArr = $objSeoToolOverrideNew = array();
        if (isset($splitpageUrl[1])) {
            $queryContent = explode('=', $splitpageUrl[1]);
            $queryItem = $queryContent[0];
            if ($queryItem=='item_motors__reg_year') {
                $getCatFromUrl = explode('/', $pageUrl);
                if (isset($getCatFromUrl[1]) && $getCatFromUrl[1]=='cars') {
                    $allUnder25Yrs = 1;
                    $get25ysrOlder = date('Y') - 24;

                    foreach ($searchParams['item_motors__reg_year'] as $srchRegYr) {
                        if ($srchRegYr > $get25ysrOlder) {
                            $allUnder25Yrs = 0;
                            break;
                        }
                    }
                    
                    if ($allUnder25Yrs==1) {
                        $isClassicCar = 1;
                        $pageUrl = 'motors/cars/{Manufacturer}/item_motors__reg_year';
                    }
                }
            }
        }
        
        if ($pageUrl!== '') {
            $objSeoToolOverride = $this->getSeoToolOverrideObj($pageUrl, $container);
        }
        
        if (!$objSeoToolOverride) {
            if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
                $indexableDimesionsArray = SeoToolRepository::getIndexableDimesionsArray();
                $indexableKeyArray = array();
                $categoryIndexableDimensions = array();
                $dimensionFilters = $this->_em->getRepository('FaEntityBundle:CategoryDimension')->getSearchableDimesionsArrayByCategoryId($searchParams['item__category_id'], $container);
                $twigCoreService = $container->get('fa.twig.core_extension');
                $rootCategoryName = $this->_em->getRepository('FaEntityBundle:Category')->getRootCategoryName($searchParams['item__category_id'], $container);

                foreach ($dimensionFilters as $dimensionId => $dimension) {
                    $dimensionName = $twigCoreService->getDimensionFieldNameFromName($dimension['name'], $rootCategoryName, $dimension['search_type']);
                    $dimensionNameRes = explode('__', $dimensionName);
                    $dimensionName = $dimensionNameRes[(count($dimensionNameRes) - 1)];
                    if ($dimension['is_index']) {
                        $categoryIndexableDimensions[$dimensionName] = $dimensionName;
                    }
                }
                foreach ($searchParams as $searchParamKey => $searchParamValue) {
                    $searchParamKeyRes = explode('__', $searchParamKey);
                    $searchParamKey = $searchParamKeyRes[(count($searchParamKeyRes) - 1)];

                    if (array_key_exists($searchParamKey, $categoryIndexableDimensions)) {
                        if ($searchParamKey == 'ad_type_id') {
                            $searchParamKey = 'type_id';
                        }
                        $indexableKeyArray[] = $searchParamKey;
                        $indexableValueArray[] = $searchParamValue;
                    }
                }
                $url = $this->_em->getRepository('FaEntityBundle:Category')->getFullSlugById($searchParams['item__category_id'], $container);
                $parentFullSlug = $url;
                $parentId = $this->_em->getRepository('FaEntityBundle:Category')->getRootCategoryId($searchParams['item__category_id'], $container);
                $adTypeString = '{Ad-Type}';
                $adTypeCategoryArr = array(CategoryRepository::FOR_SALE_ID,CategoryRepository::PROPERTY_ID,CategoryRepository::SERVICES_ID,CategoryRepository::ANIMALS_ID);
            
                if (count($indexableKeyArray)) {
                    $indexableKeyArray = array_unique($indexableKeyArray);
                    foreach ($indexableKeyArray as $indexableKey) {
                        if (isset($indexableDimesionsArray[$indexableKey])) {
                            if (in_array($parentId, $adTypeCategoryArr) && $indexableDimesionsArray[$indexableKey] == $adTypeString) {
                                $url .= '';
                            } else {
                                $url .= '/'.$indexableDimesionsArray[$indexableKey];
                            }
                        }
                    }
                }
                $catOrDimension = array();
                $firdimensionUrl=array();
                $remDimensionUrl = array();
                $dimensionUrl = array();
                if (in_array($parentId, $adTypeCategoryArr) && in_array('type_id', $indexableKeyArray)) {
                    $splitParentFullSlug = explode('/', $parentFullSlug);
                    $resturl = preg_replace('/'.preg_quote($splitParentFullSlug[0], '/').'/', '', $url, 1);
                    $url = $splitParentFullSlug[0].'/'.$adTypeString.$resturl;
                    $newParentUrl = str_replace($splitParentFullSlug[0], '', $parentFullSlug);
                    $getDimensionSlug = explode($newParentUrl.'/', rtrim($pageUrl, '/'));
                    $splitPageUrl11 = explode('/', $pageUrl);
                    if (count($splitPageUrl11)>1) {
                        $firdimensionUrl[] = $splitPageUrl11[1];
                    }
                    if (count($getDimensionSlug)>1) {
                        $remDimensionUrl = explode('/', $getDimensionSlug[1]);
                    } else {
                        $remDimensionUrl = array();
                    }
                    $dimensionUrl = array_merge($firdimensionUrl, $remDimensionUrl);
                } else {
                    if (rtrim($parentFullSlug, '/') != rtrim($pageUrl, '/')) {
                        $getDimensionSlug = explode($parentFullSlug.'/', rtrim($pageUrl, '/'));
                        if (count($getDimensionSlug)>1) {
                            $dimensionUrl = explode('/', $getDimensionSlug[1]);
                        } else {
                            $dimensionUrl = array();
                        }
                    }
                }
            
                $allCommoUrl = $url;
                if (!empty($indexableKeyArray) && !empty($dimensionUrl)) {
                    $indexArrayStart = 0;
                    foreach ($indexableKeyArray as $indexableKey) {
                        if (isset($dimensionUrl[$indexArrayStart])) {
                            $catOrDimension[$indexableKey] = $dimensionUrl[$indexArrayStart];
                        }
                        $indexArrayStart++;
                    }
                }
           
                if (count($catOrDimension)) {
                    foreach ($catOrDimension as $catOrDimensionKey=>$catOrDimensionValue) {
                        if(!isset($indexableDimesionsArray[$catOrDimensionKey])){
                            continue;
                        }
                        $exacturl = str_replace($indexableDimesionsArray[$catOrDimensionKey], $catOrDimensionValue, $url);
                        $objSeoToolOverride = $this->getSeoToolOverrideObj($exacturl, $container);
                        if ($objSeoToolOverride!='') {
                            break;
                        }
                    }
                }
                
               
                if (!$objSeoToolOverride) {
                    $objSeoToolOverride = $this->getSeoToolOverrideObj($allCommoUrl, $container);
                }
            }
        }
        if (!$objSeoToolOverride) {
            $objSeoToolOverride = $this->getSeoToolOverrideObj($fullpageUrl, $container);
        }
        
        return $objSeoToolOverride;
    }

    public function getSeoToolOverrideObj($url, $container = null)
    {
        if (substr($url, (strlen($url)-1), 1) == '/') {
            $pageUrlWithoutSlash = substr($url, 0, strlen($url));
            $pageUrlWithSlash    = $url;
        } else {
            $pageUrlWithSlash    = $url.'/';
            $pageUrlWithoutSlash = $url;
        }

        $pageUrlMD5 = md5($pageUrlWithSlash);
        $culture    = CommonManager::getCurrentCulture($container);
        $tableName  = $this->getSeoToolOverrideTableName();
        $cacheKey   = $tableName.'|'.__FUNCTION__.'|'.$pageUrlMD5.'_'.$culture;
        if ($container) {
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);
            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $qb = $this->createQueryBuilder(self::ALIAS)
              ->where('('.self::ALIAS.'.page_url = :pageUrl1 OR '.self::ALIAS.'.page_url = :pageUrl2)')
              ->setParameter('pageUrl1', $pageUrlWithoutSlash)
              ->setParameter('pageUrl2', $pageUrlWithSlash)
              ->andWhere(self::ALIAS.'.status = 1');
 
        $objSeoToolOverride = $qb->getQuery()->getOneOrNullResult();
        if ($objSeoToolOverride!='') {
            if ($container && $objSeoToolOverride) {
                CommonManager::setCacheVersion($container, $cacheKey, $objSeoToolOverride, 86400);
            }
        }
        return $objSeoToolOverride;
    }

    /**
     * Find active seo rule by page url.
     *
     * @param string $page         Page url.
     * @param array  $searchParams Search parameters.
     * @param object $container    Container identifier.
     *
     * @return mixed
     */
    public function findSeoRuleByPageUrlOnly($pageUrl, $container = null)
    {
        $objSeoToolOverride = null;

        if (substr($pageUrl, (strlen($pageUrl)-1), 1) == '/') {
            $pageUrlWithoutSlash = substr($pageUrl, 0, strlen($pageUrl));
            $pageUrlWithSlash    = $pageUrl;
        } else {
            $pageUrlWithSlash    = $pageUrl.'/';
            $pageUrlWithoutSlash = $pageUrl;
        }

        $pageUrlMD5 = md5($pageUrlWithSlash);
        $culture    = CommonManager::getCurrentCulture($container);
        $tableName  = $this->getSeoToolOverrideTableName();
        $cacheKey   = $tableName.'|'.__FUNCTION__.'|'.$pageUrlMD5.'_'.$culture;

        if ($container) {
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $qb = $this->createQueryBuilder(self::ALIAS)
        ->where('('.self::ALIAS.'.page_url = :pageUrl1 OR '.self::ALIAS.'.page_url = :pageUrl2)')
        ->setParameter('pageUrl1', $pageUrlWithoutSlash)
        ->setParameter('pageUrl2', $pageUrlWithSlash)
        ->andWhere(self::ALIAS.'.status = 1');

        $objSeoToolOverride = $qb->getQuery()->getOneOrNullResult();

        if ($container && $objSeoToolOverride) {
            CommonManager::setCacheVersion($container, $cacheKey, $objSeoToolOverride, 86400);
        }

        return $objSeoToolOverride;
    }
}
