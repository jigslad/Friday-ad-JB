<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserConfigRuleRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'ucr';

    /**
     * Get query builder.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Get user config rule table name.
     */
    private function getUserConfigRuleTableName()
    {
        return $this->_em->getClassMetadata('FaUserBundle:UserConfigRule')->getTableName();
    }

    /**
     * Get config wise config rules query.
     *
     * @param integer $configId
     * @param string  $limit
     *
     * @return object
     */
    public function getUserConfigRulesQueryBuilder($configId, $limit = null)
    {
        $queryBuilder = $this->getBaseQueryBuilder()
        ->select(self::ALIAS, ConfigRepository::ALIAS)
        ->innerJoin(self::ALIAS.'.config', ConfigRepository::ALIAS)
        ->where(self::ALIAS.'.config = '.$configId)
        ->orderBy(self::ALIAS.'.id', 'desc');

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder;
    }

    /**
     * Get active highest paypal commission.
     *
     * @param integer $userId    User id.
     * @param object  $container Container object.
     *
     * @return number
     */
    public function getActivePaypalCommission($userId, $container = null)
    {
        $paypalCommissionVal = 0;
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getUserConfigRuleTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$userId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        // check with date range only.
        $queryBuilder = $this->getUserConfigRulesQueryBuilder(ConfigRepository::PAYPAL_COMMISION, 1)
        ->orderBy(self::ALIAS.'.value', 'desc')
        ->andWhere('('.time().' >= '.self::ALIAS.'.period_from AND '.time().' <= '.self::ALIAS.'.period_to) OR ('.time().' >= '.self::ALIAS.'.period_from AND '.self::ALIAS.'.period_to IS NULL) OR ('.self::ALIAS.'.period_from IS NULL AND '.time().' <= '.self::ALIAS.'.period_to)')
        ->andWhere(self::ALIAS.'.user = '.$userId)
        ->andWhere(self::ALIAS.'.status = 1');

        $paypalCommision = $queryBuilder->getQuery()->getOneOrNullResult();

        // check with status only.
        if (!$paypalCommision) {
            $queryBuilder = $this->getUserConfigRulesQueryBuilder(ConfigRepository::PAYPAL_COMMISION, 1)
            ->orderBy(self::ALIAS.'.value', 'desc')
            ->andWhere('('.self::ALIAS.'.period_from IS NULL AND '.self::ALIAS.'.period_to IS NULL'.')')
            ->andWhere(self::ALIAS.'.user = '.$userId)
            ->andWhere(self::ALIAS.'.status = 1');

            $paypalCommision = $queryBuilder->getQuery()->getOneOrNullResult();
        }

        if ($paypalCommision) {
            $paypalCommissionVal = $paypalCommision->getValue();
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $paypalCommissionVal);
        }

        return $paypalCommissionVal;
    }
}
