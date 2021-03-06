<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Repository;

use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * Seo tool popular search repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CategoryRecommendedSlotRepository extends BaseEntityRepository
{
    const ALIAS = 'crs';

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
     * Remove category recommended slots based on category id.
     *
     * @param integer $categoryId
     */
    public function removeRecordsByCategoryId($categoryId)
    {
        $this->createQueryBuilder(self::ALIAS)
        ->delete()
        ->andWhere(sprintf('%s.category = %d', self::ALIAS, $categoryId))
        ->getQuery()
        ->execute();
    }

    /**
     * Remove category recommended slots based on category id.
     *
     * @param integer $categoryId
     */
    public function removeSlotsByCategoryId($categoryId)
    {
        $this->createQueryBuilder(self::ALIAS)
        ->delete()
        ->andWhere(sprintf('%s.category = %d', self::ALIAS, $categoryId))
        ->andWhere(self::ALIAS.'.is_searchlist = 0')
        ->getQuery()
        ->execute();
    }
    
    /**
     * Remove category recommended slots based on category id.
     *
     * @param integer $categoryId
     */
    public function removeSlotsSearchlistByCategoryId($categoryId)
    {
        $this->createQueryBuilder(self::ALIAS)
        ->delete()
        ->andWhere(sprintf('%s.category = %d', self::ALIAS, $categoryId))
        ->andWhere(self::ALIAS.'.is_searchlist = 1')
        ->getQuery()
        ->execute();
    }

    /**
     * Get category recommended slot table name.
     */
    private function getCategoryRecommendedSlotTableName()
    {
        return $this->_em->getClassMetadata('FaEntityBundle:CategoryRecommendedSlot')->getTableName();
    }

    /**
     * Get category recommended slots
     *
     * @param integer $categoryId Category id.
     * @param object  $container  Container object.
     *
     * @return array
     */
    public function getCategoryRecommendedSlotArrayByCategoryId($categoryId, $container)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getCategoryRecommendedSlotTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$categoryId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }
        $recommendedSlotArray = array();
        $recommendedSlots = $this->createQueryBuilder(self::ALIAS)
        ->leftJoin(self::ALIAS.'.category', CategoryRepository::ALIAS)
        ->andWhere(CategoryRepository::ALIAS.'.has_recommended_slot = 1')
        ->andWhere(self::ALIAS.'.category = :categoryId')
        ->andWhere(self::ALIAS.'.is_searchlist = 0')
        ->setParameter('categoryId', $categoryId)
        ->getQuery()
        ->execute();

        foreach ($recommendedSlots as $recommendedSlot) {
            $recommendedSlotArray[] = array(
                'title' => $recommendedSlot->getTitle(),
                'sub_title' => $recommendedSlot->getSubTitle(),
                'user_id' => $recommendedSlot->getUserId(),
                'url' => $recommendedSlot->getUrl(),
                'display_url' => $recommendedSlot->getDisplayUrl(),
                'cta_text' => $recommendedSlot->getCtaText(),
                'mobile_title' => $recommendedSlot->getMobileTitle(),
                'show_sponsored_lbl' => $recommendedSlot->getShowSponsoredLbl()
            );
        }

        if ($container && count($recommendedSlotArray)) {
            CommonManager::setCacheVersion($container, $cacheKey, $recommendedSlotArray);
        }

        return $recommendedSlotArray;
    }

    /**
     * Get category recommended slots
     *
     * @param integer $categoryId Category id.
     * @param object  $container  Container object.
     *
     * @return array
     */
    public function getCategoryRecommendedSlotSearchlistArrayByCategoryId($categoryId, $container)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getCategoryRecommendedSlotTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$categoryId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }
        $recommendedSlotArray = array();
        $recommendedSlots =array();
        $parentArray = array();
        $parentArray = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId);
        
        if (!empty($parentArray)) {
            arsort($parentArray);
            
            foreach ($parentArray as $categoryId => $categoryname) {
                $recommendedSlots = $this->createQueryBuilder(self::ALIAS)
                ->andWhere(self::ALIAS.'.category = :categoryId')
                ->andWhere(self::ALIAS.'.is_searchlist = 1')
                ->setParameter('categoryId', $categoryId)
                ->orderBy(self::ALIAS.'.creative_group')
                ->getQuery()
                ->execute();
                if (!empty($recommendedSlots)) {
                    break;
                }
            }
        }
        
        if (!empty($recommendedSlots)) {
            foreach ($recommendedSlots as $recommendedSlot) {
                $recommendedSlotArray[] = array(
                    'title' => $recommendedSlot->getTitle(),
                    'sub_title' => $recommendedSlot->getSubTitle(),
                    'slot_filename' => $recommendedSlot->getSlotFilename(),
                    'url' => $recommendedSlot->getUrl(),
                    'creative_group' => $recommendedSlot->getCreativeGroup(),
                    'creative_ord' => $recommendedSlot->getCreativeOrd(),
                    'display_url' => $recommendedSlot->getDisplayUrl(),
                    'cta_text' => $recommendedSlot->getCtaText(),
                    'mobile_title' => $recommendedSlot->getMobileTitle(),
                    'show_sponsored_lbl' => $recommendedSlot->getShowSponsoredLbl()
                );
            }
        }
        
        if ($container && !empty($recommendedSlotArray)) {
            CommonManager::setCacheVersion($container, $cacheKey, $recommendedSlotArray);
        }

        return $recommendedSlotArray;
    }
    
    
    /**
     * Get category recommended slots
     *
     * @param integer $categoryId Category id.
     * @param object  $container  Container object.
     *
     * @return array
     */
    public function getCatRecommendedSlotSearchlistArrByCategoryId($categoryId, $container)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getCategoryRecommendedSlotTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$categoryId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);
            
            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }
        $recommendedSlotArray = array();
        $recommendedSlots =array();
        
        $recommendedSlots = $this->createQueryBuilder(self::ALIAS)
            ->leftJoin(self::ALIAS.'.category', CategoryRepository::ALIAS)
            ->andWhere(CategoryRepository::ALIAS.'.has_recommended_slot_searchlist = 1')
            ->andWhere(self::ALIAS.'.category = :categoryId')
            ->andWhere(self::ALIAS.'.is_searchlist = 1')
            ->setParameter('categoryId', $categoryId)
            ->orderBy(self::ALIAS.'.creative_group')
            ->getQuery()
            ->execute();          

        if (!empty($recommendedSlots)) {
            foreach ($recommendedSlots as $recommendedSlot) {
                $recommendedSlotArray[] = array(
                    'title' => $recommendedSlot->getTitle(),
                    'sub_title' => $recommendedSlot->getSubTitle(),
                    'slot_filename' => $recommendedSlot->getSlotFilename(),
                    'url' => $recommendedSlot->getUrl(),
                    'creative_group' => $recommendedSlot->getCreativeGroup(),
                    'creative_ord' => $recommendedSlot->getCreativeOrd(),
                    'display_url' => $recommendedSlot->getDisplayUrl(),
                    'cta_text' => $recommendedSlot->getCtaText(),
                    'mobile_title' => $recommendedSlot->getMobileTitle(), 
                    'show_sponsored_lbl' => $recommendedSlot->getShowSponsoredLbl(),
                );
            }
        }        
                
        if ($container && !empty($recommendedSlotArray)) {
            CommonManager::setCacheVersion($container, $cacheKey, $recommendedSlotArray);
        }
        
        return $recommendedSlotArray;
    }


    /**
     * Get ad detail category recommended slots
     *
     * @param integer $categoryId Category id.
     * @param object  $container  Container object.
     *
     * @return array
     */
    public function getAdDetailCategoryRecommendedSlotArrayByCategoryId($categoryId, $container)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getCategoryRecommendedSlotTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$categoryId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $recommendedSlotArray = array();
        $categoryPath   = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container);
        if ($categoryPath && is_array($categoryPath)) {
            $categoryIds = array_reverse(array_keys($categoryPath));
            foreach ($categoryIds as $key => $catId) {
                $objCategory = $this->_em->getRepository('FaEntityBundle:Category')->find($catId);
                if ($objCategory && $objCategory->getHasRecommendedSlot()) {
                    $recommendedSlotArray = $this->getCategoryRecommendedSlotArrayByCategoryId($catId, $container);

                    if ($container && count($recommendedSlotArray)) {
                        CommonManager::setCacheVersion($container, $cacheKey, $recommendedSlotArray);
                        return $recommendedSlotArray;
                    }
                }
            }
        }

        return $recommendedSlotArray;
    }

    /**
     * Get ad detail category recommended slots
     *
     * @param integer $categoryId Category id.
     * @param object  $container  Container object.
     *
     * @return array
     */
    public function getAdDetailCategoryRecommendedSlotSearchlistArrayByCategoryId($categoryId, $container)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getCategoryRecommendedSlotTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$categoryId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $recommendedSlotArray = array();
        $categoryPath   = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container);
        if ($categoryPath && is_array($categoryPath)) {
            $categoryIds = array_reverse(array_keys($categoryPath));
            foreach ($categoryIds as $key => $catId) {
                $objCategory = $this->_em->getRepository('FaEntityBundle:Category')->find($catId);
                if ($objCategory && $objCategory->getHasRecommendedSlotSearchlist()) {
                    $recommendedSlotArray = $this->getCategoryRecommendedSlotSearchlistArrayByCategoryId($catId, $container);

                    if ($container && count($recommendedSlotArray)) {
                        CommonManager::setCacheVersion($container, $cacheKey, $recommendedSlotArray);
                        return $recommendedSlotArray;
                    }
                }
            }
        }

        return $recommendedSlotArray;
    }
}
