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
 * Ad feed site download repository.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdFeedSiteDownloadRepository extends EntityRepository
{

    const ALIAS = 'afsd';

    /**
     * Get ad images.
     *
     * @param integer $ref_id
     * @param string  $status
     *
     * @return Doctrine_Collection
     */
    public function getLatestModifiedTime($ref_id, $status = "A")
    {
        return $this->createQueryBuilder(self::ALIAS)
        ->where(self::ALIAS.'.status = :status')
        ->andWhere(self::ALIAS.'.ad_feed_site = :ref_id')
        ->addOrderBy(self::ALIAS.'.id', 'DESC')
        ->setParameter('status', $status)
        ->setMaxResults(1)
        ->setParameter('ref_id', $ref_id)
        ->getQuery()->getOneOrNullResult();
    }

    /**
     * Get ad images.
     *
     * @param integer $ref_id
     * @param string  $status
     *
     * @return Doctrine_Collection
     */
    public function getLatestModifiedTimeForDownload($ref_id)
    {
        return $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.last_run_time IS NOT NULL')
        ->andWhere(self::ALIAS.'.ad_feed_site = :ref_id')
        ->addOrderBy(self::ALIAS.'.id', 'DESC')
        ->setMaxResults(1)
        ->setParameter('ref_id', $ref_id)
        ->getQuery()->getOneOrNullResult();
    }

    /**
     * delete pending download
     *
     * @param integer $ref_id referance id
     */
    public function deletePendingDownloadsOnNewDownload($ref_id)
    {

        $qb = $this->createQueryBuilder(self::ALIAS)
        ->where(self::ALIAS.'.status = :status')
        ->andWhere(self::ALIAS.'.ad_feed_site = :ref_id')
        ->setParameter('ref_id', $ref_id)
        ->setParameter('status', 'P');

        $pendingDownloads = $qb->getQuery()->getResult();

        foreach ($pendingDownloads as $download) {
            $download->setStatus('A');
            $this->_em->persist($download);
        }

        $this->_em->flush();
    }
}
