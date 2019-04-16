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
use Fa\Bundle\AdFeedBundle\Entity\AdFeedMapping;
use Fa\Bundle\AdFeedBundle\Entity\AdFeed;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\AdBundle\Entity\Ad;
use Gedmo\Sluggable\Util\Urlizer as Urlizer;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * Business parser.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class BusinessParser extends AdParser
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
        $this->advert['feed_type'] = 'BusinessAdvert';
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
                        $this->advert['track_back_url'] = '';
                        if (count($adArray['ExternalUrls'])) {
                            $this->advert['track_back_url'] = $adArray['ExternalUrls'][0];
                        }
                        if ($this->advert['track_back_url'] == '') {
                            $this->setRejectAd();
                            $this->setRejectedReason('track_back_url: not exists for affiliate advert');
                        }
                    }
                }
            }
        }

        $this->setCommonData($adArray, $siteID);

        $this->advert['is_new'] = '';
        $this->advert['user']['role'] = RoleRepository::ROLE_BUSINESS_SELLER;
        $this->advert['is_trade_ad'] = 1;

        $this->advert['category_id'] = $this->getCategoryId($adArray['Details']['Category']);

        if (!$this->advert['category_id']) {
            $this->setRejectAd();
            $this->setRejectedReason('category missing: '.$adArray['Details']['Category']);
        }

        $description = array();

        foreach ($adArray['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);

        if (isset($adArray['Details']['Tenure']) && $adArray['Details']['Tenure'] != '') {
            $this->advert['for_sale']['business_type_id'] = $this->getEntityId($adArray['Details']['Tenure'], 15);
        }

        // for turn over
        if (isset($adArray['Details']['MinTurnover']) && $adArray['Details']['MinTurnover'] && isset($adArray['Details']['MaxTurnover']) && $adArray['Details']['MaxTurnover']) {
            if ($adArray['Details']['MinTurnover'] == $adArray['Details']['MaxTurnover']) {
                $this->advert['for_sale']['meta_data']['turnover_min'] = $adArray['Details']['MinTurnover'];
            } else {
                $this->advert['for_sale']['meta_data']['turnover_min'] = $adArray['Details']['MinTurnover'];
                $this->advert['for_sale']['meta_data']['turnover_max'] = $adArray['Details']['MaxTurnover'];
            }
        }

        // for net profit
        if (isset($adArray['Details']['MinProfit']) && $adArray['Details']['MinProfit'] && isset($adArray['Details']['MaxProfit']) && $adArray['Details']['MaxProfit']) {
            if ($adArray['Details']['MinProfit'] == $adArray['Details']['MaxProfit']) {
                $this->advert['for_sale']['meta_data']['net_profit_min'] = $adArray['Details']['MinProfit'];
            } else {
                $this->advert['for_sale']['meta_data']['net_profit_min'] = $adArray['Details']['MinProfit'];
                $this->advert['for_sale']['meta_data']['net_profit_max'] = $adArray['Details']['MaxProfit'];
            }
        }

        // for price
        if (isset($adArray['Details']['MinPrice']) && $adArray['Details']['MinPrice'] && isset($adArray['Details']['MaxPrice']) && $adArray['Details']['MaxPrice']) {
            $this->advert['price'] = $adArray['Details']['MinPrice'];
        } else {
            $this->setRejectAd();
            $this->setRejectedReason('price is not specified');
        }

        $feedAd = null;

        if ($ad_feed_site_download) {
            $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());
        } else {
            $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $siteID));
            $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site->getId());
        }

        if (!$feedAd && $adArray['EndDate'] != '0001-01-01T00:00:00Z' && strtotime($adArray['EndDate']) < time()) {
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
        $user = array();

        if ($this->advert['set_user'] === true && $this->advert['user']['email']!="" && $this->advert['user']['email']!=null) {
            $user = $this->getUser($this->advert['user']['email']);
        }

        $feedAd         = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());

        if (($this->advert['set_user'] === true) && $this->advert['user']['email'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('user information: missing ');
        }

        if ($this->advert['set_user'] === true) {
            if (!$user && $this->advert['user']['email'] != '' && $this->advert['user']['email']!=null) {
                $user = $this->setUser($user);
            }
        }

        if (!$feedAd) {
            $feedAd = new AdFeed();
        }
        $getUserStatus = EntityRepository::USER_STATUS_ACTIVE_ID;

        if (!empty($user) && $this->advert['user']['email'] != '') {
            $getUserStatus = $this->em->getRepository('FaUserBundle:User')->getUserStatusByEmail($this->advert['user']['email']);
            $feedAd->setUser($user);
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
            $feedAd->setStatus('R');
        } else {
            $feedAd->setStatus('A');
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
        $ad_forsale = $this->em->getRepository('FaAdBundle:AdForSale')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_forsale) {
            $ad_forsale = new AdForSale();
        }

        $ad_forsale->setAd($ad);

        if (isset($this->advert['for_sale']['business_type_id'])) {
            $ad_forsale->setBusinessTypeId($this->advert['for_sale']['business_type_id']);
        } else {
            $ad_forsale->setBusinessTypeId(null);
        }

        if (isset($this->advert['for_sale']['meta_data'])) {
            $ad_forsale->setMetaData(serialize($this->advert['for_sale']['meta_data']));
        } else {
            $ad_forsale->setMetaData(null);
        }

        $this->em->persist($ad_forsale);
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
        if ($cat_name) {
            $matchedText = null;
            //$mapping = $this->em->getRepository('FaAdFeedBundle:AdFeedMapping')->findOneBy(array('text' => $cat_name));
            $mapping = $this->em->getRepository('FaAdFeedBundle:AdFeedMapping')->getFeedMappingByText($cat_name,$this->advert['ref_site_id']);
            if ($mapping) {
                $matchedText = $mapping->getTarget();
            } else {
                $mapping = new AdFeedMapping();
                $mapping->setText($cat_name);
                $mapping->setRefSiteId(9);
                $this->em->persist($mapping);
                $this->em->flush();
            }

            if ($matchedText) {
                $categoryDetail = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($matchedText, $this->container);
                if (isset($categoryDetail['id'])) {
                    return $categoryDetail['id'];
                }
            }
        }

        return null;
    }
}
