<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Delivery method option repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 */
class DeliveryMethodOptionRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'dmo';

    const COLLECTION_ONLY_ID   = 1;
    const POSTED_ID            = 2;
    const POSTED_OR_COLLECT_ID = 3;

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
     * Add package status filter to existing query object.
     *
     * @param integer $status
     */
    protected function addStatusFilter($status = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.status = '.$status);
    }

    /**
     * Get delivery method array.
     *
     * @param object  $container Container identifier.
     *
     * @return array
     */
    public function getDeliveryMethodOptionArray($container = null)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $methodOptionArray = array();
        $options           = $this->getBaseQueryBuilder()->where(self::ALIAS.'.status = 1')->getQuery()->getResult();

        foreach ($options as $option) {
            $methodOptionArray[$option->getId()] = $option->getName();
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $methodOptionArray);
        }

        return $methodOptionArray;
    }

    /**
     * Get table name.
     */
    private function getTableName()
    {
        return $this->_em->getClassMetadata('FaPaymentBundle:DeliveryMethodOption')->getTableName();
    }
}
