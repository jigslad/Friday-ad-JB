<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Ad feed site repository.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdFeedRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'af';

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
     * Add status filter to existing query object.
     *
     * @param string $status Text
     */
    protected function addStatusFilter($status)
    {
        $this->queryBuilder->andWhere(self::ALIAS.'.status = :ad_feed_status');
        $this->queryBuilder->setParameter('ad_feed_status', $status);
    }

    /**
     * Add trans_id filter to existing query object.
     *
     * @param string $trans_id Text
     */
    protected function addTransIdFilter($trans_id)
    {
        $this->queryBuilder->andWhere(self::ALIAS.'.trans_id = :ad_feed_trans_id');
        $this->queryBuilder->setParameter('ad_feed_trans_id', $trans_id);
    }

    /**
     * Add unique_id filter to existing query object.
     *
     * @param string $unique_id Text
     */
    protected function addUniqueIdFilter($unique_id)
    {
        $this->queryBuilder->andWhere(self::ALIAS.'.unique_id = :ad_feed_unique_id');
        $this->queryBuilder->setParameter('ad_feed_unique_id', $unique_id);
    }

    /**
     * Add ref_site_id filter to existing query object.
     *
     * @param string $ref_site_id Text
     */
    protected function addRefSiteIdFilter($ref_site_id)
    {
        $this->queryBuilder->andWhere(self::ALIAS.'.ref_site_id = :ad_feed_ref_site_id');
        $this->queryBuilder->setParameter('ad_feed_ref_site_id', $ref_site_id);
    }

    /**
     * Check feed ad is expired or not
     *
     * @param string $ad Text
     * @return boolean true/false
     */
    public function isFeedAdExpired($ad){
        $res = $this->getBaseQueryBuilder()
            ->where(self::ALIAS . '.ad = ' . $ad)
            ->andWhere(self::ALIAS . ".status = 'E'")
            ->getQuery()->getOneOrNullResult();
        if(($res) && (count($res))) {
            return true;
        } else {
            return false;
        }
    }
}
