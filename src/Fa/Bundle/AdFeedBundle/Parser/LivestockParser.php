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
use Fa\Bundle\AdBundle\Entity\AdAnimals;

/**
 * Livestock parser.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LivestockParser extends AdParser
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
        $this->advert['feed_type'] = 'LivestockAdvert';
        $this->advert['full_data'] = (string) serialize($adArray);
        $this->advert['set_user']  = true;
        $this->advert['status']    = 'A';
        $this->advert['affiliate'] = 0;
        $this->rejectedReason      = null;
        $this->advert['rejected_reason'] = array();
        $this->advert['is_trade_ad'] = 1;
        if (isset($adArray['IsPrivateSeller']) && $adArray['IsPrivateSeller'] != null && $adArray['IsPrivateSeller'] == true) {
            $this->advert['is_trade_ad'] = 0;
        }

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

        $this->advert['category_id'] = $this->getCategoryId($adArray['Details']['AnimalType']);

        if (!$this->advert['category_id']) {
            $this->setRejectAd();
            $this->setRejectedReason('category missing: '.$adArray['Details']['AnimalType']);
        }

        $this->advert['ad_type_id']  = 2891;
        $this->advert['user']['role'] = RoleRepository::ROLE_SELLER;
        $this->advert['user']['business_category_id'] = CategoryRepository::ANIMALS_ID;

        $description = array();

        foreach ($adArray['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);


        if (isset($adArray['Details']['Gender']) && $adArray['Details']['Gender'] != '') {
            $this->advert['animals']['gender_id'] = $this->getEntityId($adArray['Details']['Gender'], 207);
            if (!$this->advert['animals']['gender_id']) {
                $this->advert['animals']['meta_data']['gender'] = $adArray['Details']['Gender'];
            }
        }

        if (isset($adArray['Details']['Colour']) && $adArray['Details']['Colour'] != '') {
            $this->advert['animals']['meta_data']['colour'] = $adArray['Details']['Colour'];
        }

        if (isset($adArray['Details']['Breed']) && $adArray['Details']['Breed'] != '') {
            $this->advert['animals']['meta_data']['breed'] = $adArray['Details']['Breed'];
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
        } elseif (isset($this->advert['status']) && $this->advert['status'] == 'E') {
            $feedAd->setStatus('E');
        } else {
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
        $ad_animals = $this->em->getRepository('FaAdBundle:AdAnimals')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_animals) {
            $ad_animals = new AdAnimals();
        }

        $ad_animals->setAd($ad);

        if (isset($this->advert['animals']['gender_id'])) {
            $ad_animals->setGenderId($this->advert['animals']['gender_id']);
        } else {
            $ad_animals->setGenderId(null);
        }

        if (isset($this->advert['animals']['meta_data'])) {
            $ad_animals->setMetaData(serialize($this->advert['animals']['meta_data']));
        } else {
            $ad_animals->setMetaData(null);
        }

        $this->em->persist($ad_animals);
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
        if ($cat_name == 'sheep') {
            return 775;
        } elseif ($cat_name == 'cattle') {
            return 768;
        } elseif ($cat_name == 'duck') {
            return 770;
        } elseif ($cat_name == 'goat') {
            return 771;
        } elseif ($cat_name == 'goose') {
            return 772;
        } elseif ($cat_name == 'pig') {
            return 773;
        } elseif ($cat_name == 'poultry') {
            return 769;
        } elseif ($cat_name == 'turkey') {
            return 776;
        }
    }
}
