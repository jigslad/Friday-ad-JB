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

/**
 * Seo tool repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Sagar Lotiya <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'st';

    const HOME_PAGE          = 'hp';
    const ADVERT_DETAIL_PAGE = 'adp';
    const ADVERT_IMG_ALT     = 'aia';
    const ADVERT_LIST_PAGE   = 'alp';

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
     * Get status array.
     *
     * @param Container $container Container identifier.
     * @param boolean   $addEmpty  Flag to show empty message.
     *
     * @return array
     */
    public static function getPageArray($container, $addEmpty = true)
    {
        $translator = CommonManager::getTranslator($container);
        $pageArray  = array();

        if ($addEmpty) {
            $pageArray[''] = $translator->trans('Select page');
        }

        $pageArray[self::ADVERT_DETAIL_PAGE] = $translator->trans('Advert detail page');
        $pageArray[self::HOME_PAGE]          = $translator->trans('Home page');
        $pageArray[self::ADVERT_IMG_ALT]     = $translator->trans('Advert image alt');
        $pageArray[self::ADVERT_LIST_PAGE] = $translator->trans('Advert list page');

        return $pageArray;
    }

    /**
     * Get page name.
     *
     * @param Container $container Container identifier.
     * @param string    $page      Cod of page.
     *
     * @return string
     */
    public static function getPageName($container, $page)
    {
        $pageNameArray = self::getPageNameArray($container);

        if (isset($pageNameArray[$page])) {
            return $pageNameArray[$page];
        } else {
            return null;
        }
    }

    /**
     * Get page name array.
     *
     * @param Container $container Container identifier.
     *
     * @return array
     */
    public static function getPageNameArray($container)
    {
        $translator      = CommonManager::getTranslator($container);
        $pageNameArray = array(
            self::HOME_PAGE          => $translator->trans('Home page'),
            self::ADVERT_DETAIL_PAGE => $translator->trans('Advert detail page'),
            self::ADVERT_IMG_ALT => $translator->trans('Advert image alt'),
            self::ADVERT_LIST_PAGE => $translator->trans('Advert list page'),
        );

        return $pageNameArray;
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
     * Add page filter.
     *
     * @param integer $page Page type.
     */
    protected function addPageFilter($page = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.page = :page')
        ->setParameter('page', $page);
    }

    /**
     * Add no index filter.
     *
     * @param integer $noIndex No idex flag.
     */
    protected function addNoIndexFilter($noIndex = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.no_index = :noIndex')
        ->setParameter('noIndex', $noIndex);
    }

    /**
     * Add no follow filter.
     *
     * @param integer $noFollow No follow flag.
     */
    protected function addNoFollowFilter($noFollow = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.no_follow = :noFollow')
        ->setParameter('noFollow', $noFollow);
    }

    /**
     * Add popular search filter.
     *
     * @param integer $popularSearch Popular search flag.
     */
    protected function addPopularSearchFilter($popularSearch = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.popular_search = :popularSearch')
        ->setParameter('popularSearch', $popularSearch);
    }

    /**
     * Get seo data by page and status.
     *
     * @param string  $page              Page code.
     * @param integer $status            Status.
     * @param integer $id                Id.
     * @param integer $categoryId        Category id.
     * @param boolean $getGeneralSeoRule Get general seo rule if specific rule is not found.
     *
     * @return array
     */
    public function getSeoPageRule($page, $status = 1, $id = null, $categoryId = null, $getGeneralSeoRule = true, $target_url = false)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->where(self::ALIAS.'.page = :page')
        ->setParameter('page', $page)
        ->andWhere(self::ALIAS.'.status = '.$status)
        ->setMaxResults(1);

        if ($id) {
            $qb->andWhere(self::ALIAS.'.id != '.$id);
        }

        if ($target_url) {
            $qb->andWhere(self::ALIAS.'.target_url = :target_url');
            $qb->setParameter('target_url', $target_url);
        } else {
            if ($categoryId) {
                if ($getGeneralSeoRule) {
                    $qb->andWhere(self::ALIAS.'.category = '.$categoryId.' OR '.self::ALIAS.'.category IS NULL');
                } else {
                    $qb->andWhere(self::ALIAS.'.category = '.$categoryId);
                }
            } else {
                $qb->andWhere(self::ALIAS.'.category IS NULL ');
            }
        }


        $entity = $qb->getQuery()
        ->getOneOrNullResult();

        return $entity;
    }

    /**
     * Get seo tool table name.
     */
    private function getSeoToolTableName()
    {
        return $this->_em->getClassMetadata('FaContentBundle:SeoTool')->getTableName();
    }

    /**
     * Get active seo rules key value array.
     *
     * @param string $page      Page code.
     * @param object $container Container identifier.
     *
     * @return mixed
     */
    public function getSeoRulesKeyValueArray($page, $container = null)
    {
        $seoRuleArray = array();

        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getSeoToolTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$page.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }

        $qb = $this->createQueryBuilder(self::ALIAS)
            ->where(self::ALIAS.'.page = :page')
            ->setParameter('page', $page)
            ->andWhere(self::ALIAS.'.status = 1');

        $seoRules = $qb->getQuery()->getResult();

        foreach ($seoRules as $seoRule) {
            $key = $seoRule->getTargetUrl() != '' ? $seoRule->getTargetUrl() : 'global';
            $seoRuleArray[$page.'_'.($seoRule->getCategory() ? $seoRule->getCategory()->getId() : $key)] = array(
                'seo_tool_id' => $seoRule->getId(),
                'h1_tag' => $seoRule->getH1Tag(),
                'meta_description' => $seoRule->getMetaDescription(),
                'meta_keywords' => $seoRule->getMetaKeywords(),
                'page_title' => $seoRule->getPageTitle(),
                'no_index' => $seoRule->getNoIndex(),
                'no_follow' => $seoRule->getNoFollow(),
                'image_alt' => $seoRule->getImageAlt(),
                'image_alt_2' => $seoRule->getImageAlt2(),
                'image_alt_3' => $seoRule->getImageAlt3(),
                'image_alt_4' => $seoRule->getImageAlt4(),
                'image_alt_5' => $seoRule->getImageAlt5(),
                'image_alt_6' => $seoRule->getImageAlt6(),
                'image_alt_7' => $seoRule->getImageAlt7(),
                'image_alt_8' => $seoRule->getImageAlt8(),
                'popular_search' => $seoRule->getPopularSearch(),
                'canonical_url' => $seoRule->getCanonicalUrl(),
                'list_content_title' => $seoRule->getListContentTitle(),
                'list_content_detail' => $seoRule->getListContentDetail(),
                'category_id' => ($seoRule->getCategory() ? $seoRule->getCategory()->getId() : null),
                'top_link' => $seoRule->getTopLink(),
            );
        }

        if ($container && $seoRules) {
            CommonManager::setCacheVersion($container, $cacheKey, $seoRuleArray);
        }

        return $seoRuleArray;
    }

    /**
     * Get active seo page rule detail by category id for solr result.
     *
     * @param object  $adSolrObj  Ad solr obj.
     * @param string  $page       Page code.
     * @param object  $container  Container identifier.
     *
     * @return mixed
     */
    public function getSeoPageRuleDetailForSolrResult($adSolrObj, $page, $container = null)
    {
        $adSolrObj     = get_object_vars($adSolrObj);
        $seoRule       = null;
        $categoryId    = (isset($adSolrObj[AdSolrFieldMapping::CATEGORY_ID]) ? $adSolrObj[AdSolrFieldMapping::CATEGORY_ID] : null);
        $categoryLevel = (isset($adSolrObj[AdSolrFieldMapping::CATEGORY_LEVEL]) ? ($adSolrObj[AdSolrFieldMapping::CATEGORY_LEVEL] - 1) : 0);
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getSeoToolTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$categoryId.'_'.$page.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $seoRuleArray = $this->getSeoRulesKeyValueArray($page, $container);

        if (isset($seoRuleArray[$page.'_'.$categoryId])) {
            $seoRule = $seoRuleArray[$page.'_'.$categoryId];
        } else {
            for ($i=$categoryLevel; $i >= 1; $i--) {
                $parentConst      = 'a_parent_category_lvl_'.$i.'_id_i';
                $parentCategoryId = (isset($adSolrObj[$parentConst]) ? $adSolrObj[$parentConst] : null);
                if (isset($seoRuleArray[$page.'_'.$parentCategoryId])) {
                    $seoRule = $seoRuleArray[$page.'_'.$parentCategoryId];
                    break;
                }
            }
        }

        if (!$seoRule && isset($seoRuleArray[$page.'_global'])) {
            $seoRule = $seoRuleArray[$page.'_global'];
        }

        if ($container) {
            if ($seoRule) {
                CommonManager::setCacheVersion($container, $cacheKey, $seoRule);
            } else {
                CommonManager::setCacheVersion($container, $cacheKey, -1);
            }
        }

        return $seoRule;
    }

    /**
     * Get active seo page rule detail by category id for search result.
     *
     * @param string  $page       Page code.
     * @param integer $categoryId Category id.
     * @param object  $container  Container identifier.
     *
     * @return mixed
     */
    public function getSeoPageRuleDetailForListResult($page, $categoryId = null, $container = null)
    {
        $seoRule       = null;
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getSeoToolTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$categoryId.'_'.$page.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }

        $seoRuleArray = $this->getSeoRulesKeyValueArray($page, $container);

        if ($categoryId) {
            if (isset($seoRuleArray[$page.'_'.$categoryId])) {
                $seoRule = $seoRuleArray[$page.'_'.$categoryId];
            } else {
                $parentCategories = array_reverse(array_keys($this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container)));
                array_shift($parentCategories);

                if (count($parentCategories)) {
                    foreach ($parentCategories as $parentCategoryId) {
                        if (isset($seoRuleArray[$page.'_'.$parentCategoryId])) {
                            $seoRule = $seoRuleArray[$page.'_'.$parentCategoryId];
                            break;
                        }
                    }
                }
            }
        }

        if (!$seoRule && isset($seoRuleArray[$page.'_global'])) {
            $seoRule = $seoRuleArray[$page.'_global'];
        }

        if ($container) {
            if ($seoRule) {
                CommonManager::setCacheVersion($container, $cacheKey, $seoRule);
            } else {
                CommonManager::setCacheVersion($container, $cacheKey, -1);
            }
        }

        return $seoRule;
    }

    /**
     * Get indexable dimensions.
     *
     * @return array
     */
    public static function getIndexableDimesionsArray()
    {
        $indexableDimensionArray = array();

        $oClass = new \ReflectionClass('\Fa\Bundle\ContentBundle\Interfaces\SeoIndexableDimensionInterface');
        $constantArray = $oClass->getConstants();

        foreach ($constantArray as $constKey => $constValue) {
            $displayValue = array_map("strtolower", explode('_', $constKey));
            $displayValue = array_map("ucfirst", $displayValue);
            $indexableDimensionArray[$constValue] = '{'.implode('-', $displayValue).'}';
        }
        asort($indexableDimensionArray);

        return $indexableDimensionArray;
    }

    /**
     * Get new url by old
     *
     * @param string  $name
     * @param object  $container
     *
     * @return string
     */
    public function getCustomizedUrlData($url, $container = null)
    {
        if ($url) {
            if ($container) {
                $tableName   = $this->getEntityTableName();
                $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$url;
                $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

                if ($cachedValue !== false) {
                    //return $cachedValue;
                }
            }

            $qb = $this->createQueryBuilder(self::ALIAS)
                ->andWhere(self::ALIAS.'.target_url LIKE :target_url')
                ->setParameter('target_url', $url.'%')
                ->setMaxResults(1);

            $customized_url = $qb->getQuery()
                ->getOneOrNullResult();

            $data = array();
            if ($customized_url) {
                $data['source_url'] = $customized_url->getSourceUrl();
                $data['target_url'] = $customized_url->getTargetUrl();
                if(isset($data['target_url']) && $data['target_url']=='motors/classic-cars/') {
                    $data['source_url'] = 'motors/cars/?item_motors__reg_year=';
                    $data['source_url'] .= implode('__',CommonManager::getClassicCarsRegYearChoices());
                }

                if ($container) {
                    CommonManager::setCacheVersion($container, $cacheKey, $data);
                }

                return $data;
            }
        }
    }

    /**
     * Get new url by old
     *
     * @param string  $name
     * @param object  $container
     *
     * @return string
     */
    public function getCustomizedTargetUrl($url, $container = null)
    {
        if ($url) {
            if ($container) {
                $tableName   = $this->getEntityTableName();
                $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$url;
                $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

                if ($cachedValue !== false) {
                    return $cachedValue;
                }
            }

            $qb = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.source_url = :source_url')
            ->setParameter('source_url', $url)
            ->setMaxResults(1);

            $customized_url = $qb->getQuery()
            ->getOneOrNullResult();

            if ($customized_url) {
                if ($container) {
                    CommonManager::setCacheVersion($container, $cacheKey, $customized_url->getTargetUrl());
                }

                return $customized_url->getTargetUrl();
            }
        }
    }
    
    /**
     * Get new source url by target url
     *
     * @param string  $targetUrl
     * @param object  $container
     *
     * @return string
     */
    public function findSeoSourceUrlMotorRegYear($targetUrl = '', $container = null)
    {
        if ($targetUrl != '') {
            $regYearsList = [];
            
            if($targetUrl == 'motors/classic-cars/') {
                $regYearsList = CommonManager::getClassicCarsRegYearChoices();
            } else {
                $qb = $this->createQueryBuilder(self::ALIAS)
                ->andWhere(self::ALIAS.'.target_url = :target_url')
                ->setParameter('target_url', $targetUrl)
                ->setMaxResults(1);
                
                $customizedSourceUrl = $qb->getQuery()->getOneOrNullResult();
                if ($customizedSourceUrl) {
                    list($key, $regYears) = explode("=", $customizedSourceUrl->getSourceUrl());
                    if ($regYears != '') {
                        $regYearsList = explode("__", $key);
                    }
                }
            }
            
            
            return $regYearsList;
        }
    }

    /**
     * Get entity table name.
     */
    private function getEntityTableName()
    {
        return $this->_em->getClassMetadata('FaContentBundle:SeoTool')->getTableName();
    }

    /**
     * Get static page urls for landing page.
     *
     * @param string $page
     * @param object $container
     * @param string $location
     *
     * @return array
     */
    public function getLandingPageStaticPageUrlArray($page, $container, $location = 'uk')
    {
        if ($page == 'for-sale') {
            return array(
                       'Shabby chic'        => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'for-sale/shabby-chic-furniture'), true),
                       'Sofa beds'          => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'for-sale/sofa-beds'), true),
                       'Corner sofas'       => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'for-sale/home-garden/corner-sofas'), true),
                       'Bridesmaid dresses' => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'for-sale/bridesmaid-dresses'), true),
                       'Mountain bikes'     => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'for-sale/mountain-bikes'), true),
                   );
        } elseif ($page == 'motors') {
            return array(
                       'Classic cars'         => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/classic-cars'), true),
                       'Cars under £1000'     => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/cars-under-1000'), true),
                       '7 seater cars'        => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/7-seater-cars'), true),
                       'Campervans'           => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/campervans'), true),
                       'Left hand drive cars' => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/left-hand-drive-cars'), true),
                       'Enduro bikes'         => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/enduro-bikes'), true),
                       'Cars under £500'      => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/cars-under-500'), true),
                       'Motocross bikes'      => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/motocross-bikes'), true),
                       'Pit bikes'            => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'motors/pit-bikes'), true),
                   );
        } elseif ($page == 'jobs') {
            return array(
                       'Part-time, Evening & Weekend' => $container->get('router')->generate('listing_page', array('location' => $location, 'page_string' => 'jobs/part-time-evening-weekend'), true),
                   );
        }

        return array();
    }

    /**
     * Get static landing pages
     *
     * @return array
     */
    public function getStaticLandingPages()
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.source_url IS NOT NULL')
        ->andWhere(self::ALIAS.'.target_url IS NOT NULL');

        return $qb->getQuery()->getResult();
    }

    /**
     * Add canonical search filter.
     *
     * @param integer $canonicalSearch Canonical search flag.
     */
    protected function addCanonicalSearchFilter($canonicalSearch = null)
    {
        if ($canonicalSearch) {
            $this->queryBuilder->andWhere($this->getRepositoryAlias().'.canonical_url IS NOT NULL AND '.$this->getRepositoryAlias().'.canonical_url <> \'\'');
        } else {
            $this->queryBuilder->andWhere($this->getRepositoryAlias().'.canonical_url IS NULL OR '.$this->getRepositoryAlias().'.canonical_url = \'\'');
        }
    }

    /**
     * Add canonical search url filter.
     *
     * @param string $url Url.
     */
    protected function addCanonicalUrlFilter($url = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.canonical_url LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $url));
    }

    /**
     * Add list content search filter.
     *
     * @param integer $listContentSearch list content search flag.
     */
    protected function addListContentSearchFilter($listContentSearch = null)
    {
        if ($listContentSearch) {
            $this->queryBuilder->andWhere('('.$this->getRepositoryAlias().'.list_content_title IS NOT NULL AND '.$this->getRepositoryAlias().'.list_content_title <> \'\') OR ('.$this->getRepositoryAlias().'.list_content_detail IS NOT NULL AND '.$this->getRepositoryAlias().'.list_content_detail <> \'\')');
        } else {
            $this->queryBuilder->andWhere('('.$this->getRepositoryAlias().'.list_content_title IS NULL OR '.$this->getRepositoryAlias().'.list_content_title = \'\') OR ('.$this->getRepositoryAlias().'.list_content_detail IS NULL OR '.$this->getRepositoryAlias().'.list_content_detail = \'\')');
        }
    }

    /**
     * Add list content title and detail filter.
     *
     * @param integer $keyword list content title or detail flag.
     */
    protected function addListContentTitleAndDetailFilter($keyword = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.list_content_title LIKE \'%%%s%%\' OR %s.list_content_detail LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $keyword, $this->getRepositoryAlias(), $keyword));
    }

    /**
     * Add basic fields search filter.
     *
     * @param integer $keyword basic fields keyword.
     */
    protected function addBasicFieldsSearchFilter($keyword = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.source_url LIKE \'%%%s%%\' OR %s.target_url LIKE \'%%%s%%\' OR %s.page_title LIKE \'%%%s%%\' OR %s.h1_tag LIKE \'%%%s%%\' OR %s.meta_description LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $keyword, $this->getRepositoryAlias(), $keyword, $this->getRepositoryAlias(), $keyword, $this->getRepositoryAlias(), $keyword, $this->getRepositoryAlias(), $keyword));
    }

    /**
     * Add top link filter.
     *
     * @param integer $topLink Top link flag.
     */
    protected function addTopLinkFilter($topLink = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.top_link = :topLink')
     ->setParameter('topLink', $topLink);
    }
}
