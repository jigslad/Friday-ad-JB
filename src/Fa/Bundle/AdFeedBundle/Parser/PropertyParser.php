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
use Fa\Bundle\AdBundle\Entity\AdProperty;

/**
 * Property parser.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PropertyParser extends AdParser
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
        $this->advert['feed_type'] = 'PropertyAdvert';
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

        $category_text =  str_replace(' ', '_', strtolower(trim($adArray['Details']['PropertyType'].' '.$adArray['Details']['PropertyStatus'])));
        $this->advert['category_id'] = $this->getCategoryId($category_text);

        if (!isset($this->advert['category_id'])) {
            $this->setRejectAd();
            $this->setRejectedReason('Category not found '.$category_text);
        }

        $this->advert['ad_type_id']  = 2520;
        $this->advert['user']['role'] = RoleRepository::ROLE_BUSINESS_SELLER;
        $this->advert['user']['business_category_id'] = CategoryRepository::PROPERTY_ID;

        $description = array();

        foreach ($adArray['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);

        if (isset($adArray['Price']) && $adArray['Price'] != '') {
            $this->advert['property']['rent_per_id'] = 2560;
        }

        if (isset($adArray['Details']['Bedrooms']) && $adArray['Details']['Bedrooms'] != '') {
            if ($adArray['Details']['Bedrooms'] >= 9) {
                $this->advert['property']['number_of_bedrooms_id'] = $this->getEntityId('9+', 165);
            } else {
                $this->advert['property']['number_of_bedrooms_id'] = $this->getEntityId($adArray['Details']['Bedrooms'], 165);
            }
            if (!$this->advert['property']['number_of_bedrooms_id']) {
                $this->advert['property']['meta_data']['number_of_bedrooms'] = $adArray['Details']['Bedrooms'];
            }
        }

        if (isset($adArray['Details']['Bathrooms']) && $adArray['Details']['Bathrooms'] != '') {
            if ($adArray['Details']['Bathrooms'] >= 9) {
                $this->advert['property']['meta_data']['number_of_bathrooms_id'] = $this->getEntityId('5+', 166);
            } else {
                $this->advert['property']['meta_data']['number_of_bathrooms_id'] = $this->getEntityId($adArray['Details']['Bathrooms'], 166);
            }
            if (!$this->advert['property']['meta_data']['number_of_bathrooms_id']) {
                $this->advert['property']['meta_data']['number_of_bathrooms'] = $adArray['Details']['Bathrooms'];
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

        if ($feedAd) {
            $this->advert['feed_ad_id'] = $feedAd->getId();
        } else {
            $this->addToFeedAd($ad_feed_site_download);
        }

        if ($this->advert['user']['email'] == '' && $this->advert['set_user'] == true) {
            $this->setRejectAd();
            $this->setRejectedReason('email is blank');
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

        $ad             = $this->getAdByRef($this->advert['unique_id']);
        $feedAd         = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());

        if (($this->advert['set_user'] === true) && $this->advert['user']['email'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('user email is: missing ');
        }

        if ($this->advert['set_user'] === true) {
            if (!$user && $this->advert['user']['email'] != '') {
                $user = $this->setUser($user);
            }
        }

        if (!$feedAd) {
            $feedAd = new AdFeed();
        }
        $getUserStatus = EntityRepository::USER_STATUS_ACTIVE_ID;

        if (!empty($user) && $this->advert['user']['email'] != '') {
            $getUserStatus = $this->em->getRepository('FaUserBundle:User')->getUserStatusByEmail($this->advert['user']['email']);
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
        } elseif ($getUserStatus != EntityRepository::USER_STATUS_ACTIVE_ID) {
            $feedAd->setRemark('User account is blocked/inactive');
            $feedAd->setUser($user);
            $feedAd->setStatus('R');
        } else {
            $feedAd->setStatus('A');
            $feedAd->setUser($user);
            $feedAd->setRemark('');
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
        $ad_property = $this->em->getRepository('FaAdBundle:AdProperty')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_property) {
            $ad_property = new AdProperty();
        }

        $ad_property->setAd($ad);

        if (isset($this->advert['property']['number_of_bedrooms_id'])) {
            $ad_property->setNumberOfBedroomsId($this->advert['property']['number_of_bedrooms_id']);
        } else {
            $ad_property->setNumberOfBedroomsId(null);
        }

        if (isset($this->advert['property']['meta_data'])) {
            $ad_property->setMetaData(serialize($this->advert['property']['meta_data']));
        } else {
            $ad_property->setMetaData(null);
        }

        $this->em->persist($ad_property);
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
        if ($cat_name == 'flat_to_let') {
            return 681;
        } elseif ($cat_name == 'house_to_let') {
            return 680;
        } elseif ($cat_name == 'room_to_let') {
            return 682;
        } elseif ($cat_name == 'garage_to_let') {
            return 684;
        } elseif ($cat_name == 'office_to_let') {
            return 688;
        } elseif ($cat_name == 'small industrial_to_let') {
            return 687;
        } elseif ($cat_name == 'small farm_to_let') {
            return 694;
        } elseif ($cat_name == 'small mobile_to_let') {
            return 683;
        }
    }
}
