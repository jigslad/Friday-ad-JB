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
class AdFeedSiteRepository extends EntityRepository
{

    const ALIAS = 'af';

    /**
     * Get all package array.
     *
     * @return array
     */
    public function getFeedSiteArray()
    {
        $query = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', self::ALIAS.'.type');

        $sites = $query->getQuery()->getResult();

        $siteArray = array();
        foreach ($sites as $site) {
            $siteArray[$site['id']] = $site['type'];
        }

        return $siteArray;
    }

    /**
     * Get all feed site ref site id array.
     *
     * @return array
     */
    public function getFeedSiteRefSiteIdsArray()
    {
        $query = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', self::ALIAS.'.type', self::ALIAS.'.ref_site_id');

        $sites = $query->getQuery()->getResult();

        $siteArray = array();
        foreach ($sites as $site) {
            $siteArray[$site['id']] = $site['type'].'_'.$site['ref_site_id'];
        }

        return $siteArray;
    }
}
