<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Dotmailer repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya<amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerNewsletterTypeRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'dnt';

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
     * Get table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getTableName()
    {
        return $this->_em->getClassMetadata('FaDotMailerBundle:DotmailerNewsletterType')->getTableName();
    }

    /**
     * Get key value array.
     *
     * @param object $container Container identifier.
     * @param string $column
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getKeyValueArray($container = null, $column = 'label')
    {
        if ($container) {
            $tableName   = $this->getTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$column;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }

        $newsletterTypesArray = array();
        $newsletterTypes      = $this->getBaseQueryBuilder()
<<<<<<< HEAD
            /* FFR-2855 : added new newsletter types but these should not be visible to end users, hence updated their 'ord' to 0 and querying here accordingly */
            ->where(self::ALIAS.'.ord > 0')
            ->orderBy(self::ALIAS.'.ord', 'ASC')
            ->getQuery()->getResult();

        if ($newsletterTypes && count($newsletterTypes)) {
            foreach ($newsletterTypes as $newsletterType) {
                $method = 'get'.ucfirst($column);
                $newsletterTypesArray[$newsletterType->getId()] = $newsletterType->$method();
            }
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $newsletterTypesArray);
        }

        return $newsletterTypesArray;
    }

    /**
     * Get newsletter types id.
     *
     * @param $categoryIds array
     *
     * @return array
     */
    public function getNewsletterTypeIds($categoryIds)
    {
        $qb = $this->getBaseQueryBuilder()
        ->select(self::ALIAS.'.id')
        ->andWhere(self::ALIAS.'.category_id IN (:categoryIds)')
        ->setParameter('categoryIds', $categoryIds);

        $results = $qb->getQuery()->getArrayResult();
        $ids     = array();
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }

        return $ids;
    }

    /**
     * Get key value array for main category.
     *
     * @param object $container Container identifier.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getMainCategoryArray($container = null)
    {
        if ($container) {
            $tableName   = $this->getTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }

        $newsletterTypesArray = array();
        $newsletterTypes      = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.parent_id = 0')
        ->andWhere(self::ALIAS.'.category_id > 0')
        ->andWhere(self::ALIAS.'.ord > 0')
        ->getQuery()->getResult();

        if ($newsletterTypes && count($newsletterTypes)) {
            foreach ($newsletterTypes as $newsletterType) {
                $newsletterTypesArray[$newsletterType->getId()] = $newsletterType->getLabel();
            }
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $newsletterTypesArray);
        }

        return $newsletterTypesArray;
    }

    /**
     * Get key value array for main category.
     *
     * @param object $container Container identifier.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getChildrens($mainCatId, $container = null)
    {
        if ($container) {
            $tableName   = $this->getTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$mainCatId;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }

        $newsletterTypesArray = array();
        $newsletterTypes      = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.parent_id = '.$mainCatId)
        ->orderBy(self::ALIAS.'.ord', 'ASC')
        ->getQuery()->getResult();

        if ($newsletterTypes && count($newsletterTypes)) {
            foreach ($newsletterTypes as $newsletterType) {
                $newsletterTypesArray[$newsletterType->getId()] = $newsletterType->getLabel();
            }
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $newsletterTypesArray);
        }

        return $newsletterTypesArray;
    }
    
    /**
     * Get key value array for main category.
     *
     * @param object $container Container identifier.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getChildrensWithName($mainCatId, $container = null)
    {
        if ($container) {
            $tableName   = $this->getTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$mainCatId;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);
            
            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }
        
        $newsletterTypesArray = array();
        $newsletterTypes      = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.parent_id = '.$mainCatId)
        ->orderBy(self::ALIAS.'.ord', 'ASC')
        ->getQuery()->getResult();
        
        if ($newsletterTypes && count($newsletterTypes)) {
            foreach ($newsletterTypes as $newsletterType) {
                $newsletterTypesArray[$newsletterType->getId()] = $newsletterType->getName();
            }
        }
        
        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $newsletterTypesArray);
        }
        
        return $newsletterTypesArray;
    }

    /**
     * Get non mapped categories array.
     *
     * @param object $container Container identifier.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getNonMappedCategories($container = null)
    {
        if ($container) {
            $tableName   = $this->getTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }

        $newsletterTypesArray = array();
        $newsletterTypes      = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.parent_id = 0')
        ->andWhere(self::ALIAS.'.category_id = 0 OR '.self::ALIAS.'.category_id IS NULL')
        ->andWhere(self::ALIAS.'.ord > 0')
        ->orderBy(self::ALIAS.'.ord', 'ASC')
        ->getQuery()->getResult();

        if ($newsletterTypes && count($newsletterTypes)) {
            foreach ($newsletterTypes as $newsletterType) {
                $newsletterTypesArray[$newsletterType->getId()] = $newsletterType->getLabel();
            }
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $newsletterTypesArray);
        }

        return $newsletterTypesArray;
    }
    
    
    /**
     * Get all newsletter.
     *
     * @param object $container Container identifier.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getAllNewsletterTypeByOrd($container = null, $notConsiderId = null)
    {
        if ($container) {
            $tableName   = $this->getTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);
            
            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }
        
        $newsletterTypesArray = array();
        $query     = $this->createQueryBuilder(self::ALIAS);
        
        if(!is_null( $notConsiderId)) {
            $query->andWhere(self::ALIAS.'.id != :id')
            ->setParameter('id', $notConsiderId);
        }
        $query->orderBy(self::ALIAS.'.ord', 'ASC');
        $newsletterTypes = $query->getQuery()->getResult();
        
        if ($newsletterTypes && count($newsletterTypes)) {
            foreach ($newsletterTypes as $newsletterType) {
                $newsletterTypesArray[] = $newsletterType->getId();
            }
        }
        
        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $newsletterTypesArray);
        }
        
        return $newsletterTypesArray;
    }
}
