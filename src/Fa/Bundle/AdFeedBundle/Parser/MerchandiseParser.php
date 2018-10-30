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
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdMotors;
use Fa\Bundle\AdBundle\Entity\AdServices;
use Fa\Bundle\AdBundle\Entity\Ad;

/**
 * Merchandise parser.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class MerchandiseParser extends AdParser
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
        $this->advert['feed_type'] = 'MerchandiseAdvert';
        $this->advert['full_data'] = (string) serialize($adArray);
        $this->advert['set_user']  = true;
        $this->advert['status']    = 'A';
        $this->advert['affiliate'] = 0;
        $this->rejectedReason      = null;
        $this->advert['rejected_reason'] = array();

        if (isset($adArray['SiteVisibility']) && is_array($adArray['SiteVisibility'])) {
            foreach ($adArray['SiteVisibility'] as $site) {
                if (isset($site['SiteId']) && $site['SiteId'] == 10) {
                    if ($site['IsMainSite'] === 'false' || $site['IsMainSite'] == false) {
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

        $this->advert['category_id'] = $this->getCategoryId($adArray['Details']['ClassificationCategory']);

        if (!$this->advert['category_id']) {
            $this->setRejectAd();
            $this->setRejectedReason('category missing: '.$adArray['Details']['ClassificationCategory']);
        }

        $this->advert['user']['role'] = RoleRepository::ROLE_BUSINESS_SELLER;

        if ($this->advert['category_id'] == 489) {
            $this->advert['ad_type_id']  = null;
            $this->advert['user']['business_category_id'] = CategoryRepository::FOR_SALE_ID;
        } elseif ($this->advert['category_id'] == 489) {
            $this->advert['ad_type_id']  = 2485;
            $this->advert['user']['business_category_id'] = CategoryRepository::FOR_SALE_ID;
        } elseif ($this->advert['category_id'] == 3412) {
            $this->advert['ad_type_id']  = null;
            $this->advert['user']['business_category_id'] = CategoryRepository::ADULT_ID;
        } else {
            $this->advert['ad_type_id']  = 1;
            $this->advert['delivery_method_option_id']  = 1;
            $this->advert['payment_method_id']  = 1;
            $this->advert['is_new'] = 0;
            $this->advert['user']['business_category_id'] = CategoryRepository::FOR_SALE_ID;
        }

        $description = array();

        foreach ($adArray['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);

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

        $feedAd         = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());

        if (($this->advert['set_user'] === true) && $this->advert['user']['email'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('user information: missing ');
        }

        if (preg_match('/@unknown_kapow.com/', $this->advert['user']['email']) &&  $this->advert['user']['phone'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('email unknown_kapow.com with no phone');
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
        if ($this->advert['category_id'] == 489) {
            $ad_child = $this->em->getRepository('FaAdBundle:AdMotors')->findOneBy(array('ad' => $ad->getId()));
            if (!$ad_child) {
                $ad_child = new AdMotors();
            }
        } elseif ($this->advert['category_id'] == 489) {
            $ad_child = $this->em->getRepository('FaAdBundle:AdServices')->findOneBy(array('ad' => $ad->getId()));
            if (!$ad_child) {
                $ad_child = new AdServices();
            }
        } else {
            $ad_child = $this->em->getRepository('FaAdBundle:AdForSale')->findOneBy(array('ad' => $ad->getId()));
            if (!$ad_child) {
                $ad_child = new AdForSale();
            }
        }

        $ad_child->setAd($ad);

        $this->em->persist($ad_child);
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

        if ($cat_name == 'adult contacts') {
            return 3412;
        } elseif ($cat_name == 'for sale>dining & living room furniture>chairs, stools and other seating') {
            return 182;
        } elseif ($cat_name == 'music & instruments>guitars & amps>guitar cases & accessories') {
            return 366;
        } elseif ($cat_name == 'for sale>furniture') {
            return 180;
        } elseif ($cat_name == 'for sale>dining & living room furniture') {
            return 180;
        } elseif ($cat_name == 'for sale>beds & bedroom furniture>wardrobes, shelving and storage') {
            return 176;
        } elseif ($cat_name == 'music & instruments>cases, racks & stands') {
            return 372;
        } elseif ($cat_name == 'for sale>dining & living room furniture>sofas, armchairs and suites') {
            return 184;
        } elseif ($cat_name == 'for sale>beds & bedroom furniture') {
            return 175;
        } elseif ($cat_name == 'for sale>home fixtures & fittings>mirrors & clocks>mirrors') {
            return 340;
        } elseif ($cat_name == 'for sale>beds & bedroom furniture>mattresses') {
            return 170;
        } elseif ($cat_name == 'ladders and access equipment') {
            return 241;
        } elseif ($cat_name == 'for sale>kitchen appliances & accessories>fridges & freezers') {
            return 307;
        } elseif ($cat_name == 'for sale>gardening stuff>garden furniture') {
            return 269;
        } elseif ($cat_name == 'for sale>beds & bedroom furniture>dressers and chests') {
            return 174;
        } elseif ($cat_name == 'for sale>dining & living room furniture>tables and chairs') {
            return 192;
        } elseif ($cat_name == 'motor services>tyres & alloys') {
            return 489;
        } elseif ($cat_name == 'for sale>office furniture & equipment>desks') {
            return 199;
        } elseif ($cat_name == 'services>building & home services>window, doors & glazing') {
            return 626;
        } elseif ($cat_name == 'for sale>fashion>accessories>women') {
            return 148;
        } elseif ($cat_name == 'for sale>fashion>jewellery and watches') {
            return 155;
        }
    }
}
