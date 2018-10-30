<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Parser;

use Fa\Bundle\AdFeedBundle\Parser\AdParser;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * Gun parser.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class GunParser
{
    const MAKE = 'MAKE';

    /**
     * Map ad data.
     *
     * @param array $adArray Advert array.
     */
    public function mapAdData($adArray)
    {
        $this->advert['feed_type'] = 'gun';
        $this->setCommonData($adArray);
        $this->advert['category_id'] = $this->getCategoryId();
        $this->advert['title']       = $adArray['Manufacturer'].' '.$adArray['Model'];
        $this->mapAdImages($adArray['AdvertImages']);
        $this->advert['full_data'] = (string) serialize($adArray);
    }

    /**
     * Add advert data.
     *
     * @param integer            $force                 Force update or not.
     * @param AdFeedSiteDownload $ad_feed_site_download AdFeedSiteDownload object.
     *
     * @return boolean
     */
    protected function add($force, $adFeedSiteStat)
    {
        $ad = parent::add($force, $adFeedSiteStat);
        $this->setForSaleData($ad);
    }

    /**
     * Get category id.
     *
     * @param string $string Category.
     *
     * @return integer
     */
    public function getCategoryId($cat_name = null)
    {
        return 435;
    }
}
