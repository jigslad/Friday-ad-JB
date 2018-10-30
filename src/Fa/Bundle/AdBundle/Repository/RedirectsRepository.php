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
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * redirects repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version 1.0
 */
class RedirectsRepository extends EntityRepository
{
    const ALIAS = 'red';

    /**
     * Get new url by old
     *
     * @param string  $name
     * @param object  $container
     *
     * @return string
     */
    public function getNewByOld($old, $container = null, $location = false)
    {
        if ($old) {
            if ($container) {
                $tableName   = $this->getEntityTableName();
                $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$old.'_'.$location;
                $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

                if ($cachedValue !== false) {
                    return $cachedValue;
                }
            }

            $qb = $this->createQueryBuilder(self::ALIAS)
                ->setMaxResults(1);
            $qb->addOrderBy(self::ALIAS.'.id', 'DESC');

            if ($location) {
                $qb->andWhere(self::ALIAS.'.is_location = :is_location');
                $qb->setParameter('is_location', '1');
                $qb->andWhere(self::ALIAS.'.old LIKE :old1 OR '.self::ALIAS.'.old LIKE :old2 ');
                $qb->setParameter('old1', $old);
                $qb->setParameter('old2', $old.'/');
            } else {
                $qb->andWhere(self::ALIAS.'.old LIKE :old');
                $qb->setParameter('old', $old.'%');
            }

            $redirect = $qb->getQuery()
                ->getOneOrNullResult();


            if ($redirect) {
                if ($container) {
                    CommonManager::setCacheVersion($container, $cacheKey, $redirect->getNew());
                }

                return $redirect->getNew();
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
    public function getNewByOldForArticle($old, $container = null)
    {
        if ($old) {
            if ($container) {
                $tableName   = $this->getEntityTableName();
                $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$old;
                $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

                if ($cachedValue !== false) {
                    return $cachedValue;
                }
            }

            $qb = $this->createQueryBuilder(self::ALIAS)
            ->setMaxResults(1);
            $qb->andWhere(self::ALIAS.'.is_location = :is_location');
            $qb->setParameter('is_location', '2');
            $qb->andWhere(self::ALIAS.'.old LIKE :old1 OR '.self::ALIAS.'.old LIKE :old2 ');
            $qb->setParameter('old1', $old);
            $qb->setParameter('old2', $old.'/');
            $qb->addOrderBy(self::ALIAS.'.id', 'DESC');

            $redirect = $qb->getQuery()
            ->getOneOrNullResult();

            if ($redirect) {
                if ($container) {
                    CommonManager::setCacheVersion($container, $cacheKey, $redirect->getNew());
                }

                return $redirect->getNew();
            }
        }
    }

    /**
     * Get entity table name.
     */
    private function getEntityTableName()
    {
        return $this->_em->getClassMetadata('FaAdBundle:Redirects')->getTableName();
    }
}
