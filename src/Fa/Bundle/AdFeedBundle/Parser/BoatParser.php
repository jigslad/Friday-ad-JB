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
use Gedmo\Sluggable\Util\Urlizer as Urlizer;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedMapping;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdFeedBundle\Entity\AdFeed;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\AdBundle\Entity\AdMotors;

/**
 * Boat parser.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class BoatParser extends AdParser
{

    /**
     * Map ad data.
     *
     * @param array   $adArray Advert array.
     * @param integer $siteID  site id
     */
    public function mapAdData($adArray, $siteID, $ad_feed_site_download = null)
    {
        $this->advert              = array();
        $this->advert['feed_type'] = 'BoatAdvert';
        $this->advert['full_data'] = (string) serialize($adArray);
        $this->advert['set_user']  = true;
        $this->advert['status']    = 'A';
        $this->advert['affiliate'] = 0;
        $this->rejectedReason      = null;
        $this->advert['rejected_reason'] = array();

        if (isset($adArray['SiteVisibility']) && is_array($adArray['SiteVisibility'])) {
            foreach ($adArray['SiteVisibility'] as $site) {
                if (isset($site['SiteId']) && $site['SiteId'] == 10) {
                    if (($site['IsMainSite'] === 'false') || ($site['IsMainSite'] === false)) {
                        $this->advert['affiliate'] = 1;
                        $this->advert['set_user'] = false;
                        $this->advert['track_back_url'] = $adArray['TrackbackUrl'];
                        if ($this->advert['track_back_url'] == '') {
                            $this->setRejectAd();
                            $this->setRejectedReason('track_back_url: not exists for affiliate advert');
                        }
                    }
                }
            }
        }

        $this->setCommonData($adArray, $siteID);

        $this->advert['category_id'] = $this->getCategoryId($adArray['Details']['BoatType']);

        if (!$this->advert['category_id']) {
            $this->setRejectAd();
            $this->setRejectedReason('category missing: '.$adArray['Details']['BoatType']);
        }

        $this->advert['ad_type_id']  = 2520;
        $this->advert['user']['role'] = RoleRepository::ROLE_SELLER;
        $this->advert['user']['business_category_id'] = CategoryRepository::MOTORS_ID;

        $description = array();

        foreach ($adArray['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);

        if (isset($adArray['Price']) && $adArray['Price'] != '') {
            $this->advert['property']['rent_per_id'] = 2560;
        }

        if (isset($adArray['Manufacturer']) && $adArray['Manufacturer'] != '') {
            $this->advert['motors']['manufacturer_id'] = $this->getEntityId($adArray['Manufacturer'], 43);
            if (!$this->advert['motors']['manufacturer_id']) {
                $this->advert['motors']['meta_data']['manufacturer'] = $adArray['Manufacturer'];
            }
        }

        if (isset($adArray['Model']) && $adArray['Model'] != '') {
            $this->advert['motors']['meta_data']['model'] = $adArray['Model'];
        }

        if (isset($adArray['Details']['EngineSpecs']) && $adArray['Details']['EngineSpecs'] != '') {
            $specifications = $adArray['Details']['EngineSpecs'];
            foreach ($specifications as $specification) {
                if (isset($specification['Equipment']) && $specification['Equipment'] == 'fuel') {
                    $this->advert['motors']['fuel_type_id'] = $this->getEntityId($specification['Value'], 44);
                } elseif (isset($specification['Equipment']) && $specification['Equipment'] == 'build year') {
                    $this->advert['motors']['meta_data']['year_built'] = $this->getEntityId($specification['Value']);
                }
            }
        }

        $feedAd = null;

        if ($ad_feed_site_download) {
            $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());
        } else {
            $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $siteID));
            $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site->getId());
        }

        if (!$feedAd && $adArray['EndDate'] != '0001-01-01T00:00:00Z') {
        	return 'discard';
        }

        if ($this->advert['user']['email'] == '' && $this->advert['set_user'] == true) {
            $this->setRejectAd();
            $this->setRejectedReason('email is blank');
        }

        if ($feedAd) {
            $this->advert['feed_ad_id'] = $feedAd->getId();
        } else {
            $this->addToFeedAd($ad_feed_site_download);
        }

        $adImages = isset($adArray['AdvertImages']) && count($adArray['AdvertImages']) > 0 ? $adArray['AdvertImages'] : array();
        $this->mapAdImages($adImages, $this->advert['affiliate']);
    }


    /**
     * add to feed ad
     *
     * @param object $ad_feed_site_download
     * @return number
     */
    public function addToFeedAd($ad_feed_site_download)
    {
        $ad_feed_site   = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $this->advert['ref_site_id']));

        if ($this->advert['set_user'] === true) {
            $user = $this->getUser($this->advert['user']['email']);
        }

        $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());

        if (($this->advert['set_user'] === true) && $this->advert['user']['email'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('user information: missing ');
        }

        if ($this->advert['set_user'] === true) {
            if (!$user && $this->advert['user']['email'] != '') {
                $user = $this->setUser($user);
            }
        }

        if (!$feedAd) {
            $feedAd = new AdFeed();
        }

        $feedAd->setTransId($this->advert['trans_id']);
        $feedAd->setUniqueId($this->advert['unique_id']);
        $feedAd->setIsUpdated(1);
        $feedAd->setRefSiteId($ad_feed_site_download->getAdFeedSite()->getId());
        $feedAd->setAdText(serialize($this->advert));
        $feedAd->setLastModified($ad_feed_site_download->getModifiedSince());

        if (isset($this->advert['status']) && $this->advert['status'] == 'R') {
            $feedAd->setStatus('R');
            if (implode(',', $this->advert['rejected_reason']) != '') {
                $feedAd->setRemark(implode(',', $this->advert['rejected_reason']));
            }
        }
        elseif (isset($this->advert['status']) && $this->advert['status'] == 'E') {
            $feedAd->setStatus('E');
        }
        else {
            $feedAd->setStatus('A');
        }

        $this->em->persist($feedAd);
        $this->em->flush();
        $this->advert['feed_ad_id'] = $feedAd->getId();
    }


    /**
     * add data in child table
     *
     * @see \Fa\Bundle\AdFeedBundle\Parser\AdParser::addChildData()
     */
    public function addChildData($ad)
    {
        $ad_motors = $this->em->getRepository('FaAdBundle:AdMotors')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_motors) {
            $ad_motors = new AdMotors();
        }

        $ad_motors->setAd($ad);

        if (isset($this->advert['motors']['manufacturer_id'])) {
            $ad_motors->setManufacturerId($this->advert['motors']['manufacturer_id']);
        } else {
            $ad_motors->setManufacturerId(null);
        }

        if (isset($this->advert['motors']['fuel_type_id'])) {
            $ad_motors->setFuelTypeId($this->advert['motors']['fuel_type_id']);
        } else {
            $ad_motors->setFuelTypeId(null);
        }

        if (isset($this->advert['motors']['meta_data'])) {
            $ad_motors->setMetaData(serialize($this->advert['motors']['meta_data']));
        } else {
            $ad_motors->setMetaData(null);
        }

        $this->em->persist($ad_motors);
    }

    /**
     * get entity id
     *
     * @param string    $string
     * @param integer $dimension_id
     *
     * @return integer or null
     */
    private function getEntityId($string, $dimension_id)
    {
        return $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($dimension_id, trim($string), $this->container);
    }

    /**
     * Get category id.
     *
     * @param string $string Category.
     */
    public function getCategoryId($cat_name = null)
    {
        $cat_name = strtolower($cat_name);
        if ($cat_name == 'sailboats') {
            return 446;
        } elseif ($cat_name == 'motor boats') {
            return 447;
        } elseif ($cat_name == 'ribs & inflatables' || $cat_name == 'other') {
            return 454;
        } elseif ($cat_name == 'commercial boats') {
            return 453;
        } elseif ($cat_name == 'small boats') {
        	return 448;
        } elseif ($cat_name == 'trailers') {
        	return 455;
        } elseif ($cat_name == 'engines') {
        	return 486;
        }
    }
}
