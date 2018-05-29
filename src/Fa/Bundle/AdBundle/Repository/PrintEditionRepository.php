<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\AdBundle\Entity\AdPrint;
use Fa\Bundle\EntityBundle\Repository\LocalityRepository;
use Fa\Bundle\EntityBundle\Repository\LocationGroupLocationRepository;
use Fa\Bundle\AdBundle\Repository\PrintEditionRuleRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This repository is used for print edition management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PrintEditionRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'pe';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Add status filter.
     *
     * @param integer $status Status value.
     */
    protected function addStatusFilter($status = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.status = '.$status);
    }

    /**
     * Get active print edition.
     *
     * @param integer $limit Limit of active publication.
     *
     * @return mixed
     */
    public function getActivePrintEdition($limit = null, $printEditionIds = array())
    {
        $query = $this->getBaseQueryBuilder()
            ->andWhere(self::ALIAS.'.status = 1')
            ->addOrderBy(self::ALIAS.'.name', 'asc');

        if (!is_array($printEditionIds)) {
            $printEditionIds = array($printEditionIds);
        }

        if (count($printEditionIds)) {
            $query->andWhere(self::ALIAS.'.id IN (:printEditionIds)')
                ->setParameter('printEditionIds', $printEditionIds);
        }
        if ($limit) {
            $query->setMaxResults($limit);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Get active print edition array.
     *
     * @return array
     */
    public function getActivePrintEditionArray()
    {
        $printEditionArray = array();

        $printEditions = $this->getActivePrintEdition();

        foreach ($printEditions as $printEdition) {
            $printEditionArray[$printEdition->getId()] = $printEdition->getName();
        }

        return $printEditionArray;
    }

    /**
     * Get active print edition array.
     *
     * @return array
     */
    public function getActivePrintEditionCodeArray()
    {
        $printEditionArray = array();

        $printEditions = $this->getActivePrintEdition();

        foreach ($printEditions as $printEdition) {
            $printEditionArray[$printEdition->getCode()] = $printEdition->getId();
        }

        return $printEditionArray;
    }

    /**
     * Get default prin edition deadline & inser date values.
     *
     * @return array
     */
    public function getDefaultPrinEditionValues()
    {
        $defaultValue['deadline_day_of_week']   = 2;
        $defaultValue['deadline_time_of_day']   = '23:59';
        $defaultValue['insertdate_day_of_week'] = 5;
        $defaultValue['insertdate_time_of_day'] = '23:59';
        $defaultValue['no_of_week']             = '1';

        return $defaultValue;
    }

    /**
     * Get default prin edition deadline & inser date values.
     *
     * @return array
     */
    public function getPrintEditionColumnByTownId($townId = null, $container = null, $column = 'name')
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getPrintEditionTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$column.'_'.$townId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $printEditonName = '';
        $locationGroup   = $this->_em->getRepository('FaEntityBundle:LocationGroupLocation')->getLocationGroupIdByTownDomicile(array($townId));
        if ($locationGroup && is_array($locationGroup)) {
            $objPrintEditionRules = $this->_em->getRepository('FaAdBundle:PrintEditionRule')->getPrintEditionByLocationGroup($locationGroup[0]);
            if ($objPrintEditionRules && count($objPrintEditionRules) > 0) {
                $objPrintEdition = $objPrintEditionRules[0]->getPrintEdition();
                $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $column)));
                $printEditonName = $objPrintEdition->$methodName();
            }
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $printEditonName);
        }

        return $printEditonName;
    }

    /**
     * Get print edition table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getPrintEditionTableName()
    {
        return $this->_em->getClassMetadata('FaAdBundle:PrintEdition')->getTableName();
    }

    /**
     * Get active print edition array.
     *
     * @param array $printEditionIds Exclide print edition id array.
     *
     * @return mixed
     */
    public function getActivePrintEditionArrayByIds($printEditionIds)
    {
        $printEditionIds = array_filter($printEditionIds);
        $printEditionArray = array();
        if (count($printEditionIds)) {
            $printEditionTableName   = $this->_em->getClassMetadata('FaAdBundle:PrintEdition')->getTableName();
            $sql = 'SELECT * FROM '.$printEditionTableName.' as '.self::ALIAS.' WHERE '.self::ALIAS.'.id IN ('.implode(',', $printEditionIds).') AND '.self::ALIAS.'.status = 1 ORDER BY FIELD('.self::ALIAS.'.id, '.implode(',', $printEditionIds).');';
            $stmt = $this->_em->getConnection()->prepare($sql);
            $stmt->execute();
            $printEditions = $stmt->fetchAll();
            foreach ($printEditions as $printEdition) {
                $printEditionArray[$printEdition['id']] = $printEdition['name'];
            }
        }

        return $printEditionArray;
    }

    /**
     * Get other active print edition.
     *
     * @param array $printEditionIds Exclide print edition id array.
     *
     * @return mixed
     */
    public function getActiveOtherPrintEdition($printEditionIds)
    {
        $query = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.status = 1')
        ->addOrderBy(self::ALIAS.'.name', 'asc');

        if (!is_array($printEditionIds)) {
            $printEditionIds = array($printEditionIds);
        }

        if (count($printEditionIds)) {
            $query->andWhere(self::ALIAS.'.id NOT IN (:printEditionIds)')
            ->setParameter('printEditionIds', $printEditionIds);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Get all print edition.
     *
     * @return mixed
     */
    public function getAllPrintEdition()
    {
        $printEditionArray = array();
        $query = $this->getBaseQueryBuilder()
        ->addOrderBy(self::ALIAS.'.name', 'asc');

        $printEditions = $query->getQuery()->getResult();;

        foreach ($printEditions as $printEdition) {
            $printEditionArray[$printEdition->getId()] = $printEdition->getName();
        }

        return $printEditionArray;
    }
}
