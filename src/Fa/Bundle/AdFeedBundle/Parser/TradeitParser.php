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

use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdFeedBundle\Repository\AdFeedRepository;
use Fa\Bundle\AdFeedBundle\Entity\AdFeed;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdImage;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\CoreBundle\Manager\ThumbnailManager;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteUser;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedSite;
use Fa\Bundle\EntityBundle\Entity\Category;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteDownload;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteStat;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdMain;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Gedmo\Sluggable\Util\Urlizer;
use \Curl\Curl;
use \Curl\MultiCurl;
use Fa\Bundle\AdBundle\Entity\AdMotors;
use Fa\Bundle\AdBundle\Entity\AdJobs;
use Fa\Bundle\AdBundle\Entity\AdServices;
use Fa\Bundle\AdBundle\Entity\AdProperty;
use Fa\Bundle\AdBundle\Entity\AdAnimals;
use Fa\Bundle\AdBundle\Entity\AdCommunity;
use Fa\Bundle\AdBundle\Entity\AdAdult;

/**
 * Ad parser.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class TradeitParser
{
    /**
     * Container service class object.
     *
     * @var object
     */
    protected $container;

    /**
     * Container service class object.
     *
     * @var object
     */
    protected $em;

    /**
     * Advert array.
     *
     * @var array
     */
    protected $advert = array();

    /**
     * Constructor.
     *
     * @param object $container Container interface.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->dir       = $this->container->getParameter('fa.feed.data.dir');
        $this->rejectedReason = '';
    }

    /**
     * Add advert data.
     *
     * @param integer            $force                 Force update or not.
     * @param AdFeedSiteDownload $ad_feed_site_download AdFeedSiteDownload object.
     *
     * @return boolean
     */
    public function add($feedAd, $force)
    {
        $this->advert   = array();
        $this->advert  = unserialize($feedAd->getAdText());

        if (isset($this->advert['full_data']) && ($force == 'remap' || $force == 'iremap')) {
            $originalJson  = unserialize($this->advert['full_data']);
            $this->mapAdData($originalJson, $this->advert['ref_site_id']);
            if (isset($this->advert['status']) && $this->advert['status'] == 'R') {
                $feedAd->setStatus('R');
                if (implode(',', $this->advert['rejected_reason']) != '') {
                    $feedAd->setRemark(implode(',', $this->advert['rejected_reason']));
                }
            } elseif (isset($this->advert['status']) && $this->advert['status'] == '25') {
                $feedAd->setStatus('A');
                $feedAd->setRemark(null);
            } else {
                $feedAd->setStatus('E');
                $feedAd->setRemark(null);
            }

            $feedAd->setAdText(serialize($this->advert));
            $this->em->persist($feedAd);
            $this->em->flush();
        }

        $ad_feed_site   = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $this->advert['ref_site_id']));
        $user = null;
        $ad = $this->getAdByRef($this->advert['unique_id']);

        if ($ad || $feedAd->getStatus() != 'R') {
            $adMain         = $this->getAdMainByRef($this->advert['unique_id']);
            $this->setAdFeedSiteUser($ad_feed_site, $user);

            if (!$force && $feedAd && (md5(serialize($this->advert)) == $feedAd->getHash())) {
                echo "{not changed}";
                return $ad;
            }

            $adMain = $this->setAdMain($adMain);

            if (!$ad) {
                $ad = $this->setAd($user, $adMain, $ad);
                echo "{ new }";
            } else {
                echo "{ updated }";
                $ad = $this->setAd($user, $adMain, $ad);
            }

            $this->advert['image_hash'] = isset($this->advert['image_hash']) ? $this->advert['image_hash'] : null;

            if (($force == 'all')|| !$feedAd || ($this->advert['image_hash'] != $feedAd->getImageHash())) {
                $this->updateImages($ad);
            }

            $this->setAdLocation($ad);
            $feedAd->setImageHash($this->advert['image_hash']);
            $this->addChildData($ad);

            $feedAd->setAd($ad);
            $feedAd->setUser($user);

            if ($this->advert['set_user'] === true) {
                $feedAd->setUserHash($this->advert['user_hash']);
            }

            $feedAd->setHash(md5(serialize($this->advert)));
            $lastRunTime = new \DateTime(date('Y-m-d H:i:s', $this->advert['last_modified']));
            $feedAd->setLastModified($lastRunTime);
            $this->em->persist($feedAd);
            $this->em->flush();
            return $ad;
        } else {
            echo "X".$feedAd->getTransId()."\n";
        }
    }

    /**
     * Update image data.
     *
     * @param object $ad
     */
    protected function updateImages($ad)
    {
        $adImageDir = $this->container->get('kernel')->getRootDir().'/../web/uploads/image/';
        $imagePath  = $adImageDir.CommonManager::getGroupDirNameById($ad->getId());

        $i = 1;

        $currentImages = $this->em->getRepository('FaAdBundle:AdImage')->getAdImages($ad->getId());

        foreach ($currentImages as $image) {
            $adImageManager = new AdImageManager($this->container, $ad->getId(), $image->getHash(), $imagePath);
            $adImageManager->removeImage();
            $this->em->remove($image);
        }


        foreach ($this->advert['images'] as $img) {
            echo $filePath = $this->dir.'/images/'.$img['local_path'];
            $dimension = @getimagesize($filePath);

            if (file_exists($filePath) && $dimension) {
                print_r($img);
                $hash = CommonManager::generateHash();
                CommonManager::createGroupDirectory($adImageDir, $ad->getId());

                $image = new AdImage();
                $image->setHash($hash);
                $image->setPath('uploads/image/'.CommonManager::getGroupDirNameById($ad->getId()));
                $image->setOrd($i);
                $image->setAd($ad);
                $image->setStatus(1);
                $image->setImageName(Urlizer::urlize($ad->getTitle().'-'.$ad->getId().'-'.$i));
                $image->setAws(0);
                $this->em->persist($image);

                $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, 75, 'ImageMagickManager');
                $origImage->loadFile($filePath);
                $origImage->save($imagePath.'/'.$ad->getId().'_'.$hash.'.jpg', 'image/jpeg');

                $adImageManager = new AdImageManager($this->container, $ad->getId(), $hash, $imagePath);
                $adImageManager->createThumbnail();
                $adImageManager->createCropedThumbnail();

                $i++;
            }
        }

        $this->em->flush();
    }


    /**
     * Set ad location.
     *
     * @param object $ad
     */
    protected function setAdLocation($ad)
    {
        $ad_location = $this->em->getRepository('FaAdBundle:AdLocation')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_location) {
            $ad_location = new AdLocation();
        }

        $ad_location->setAd($ad);

        if (isset($this->advert['location']['postcode']) && $this->advert['location']['postcode']) {
            $ad_location->setPostcode($this->advert['location']['postcode']);
        } else {
            $ad_location->setPostcode(null);
        }

        if (isset($this->advert['location']['town_id']) && $this->advert['location']['town_id']) {
            $ad_location->setLocationTown($this->em->getReference('FaEntityBundle:Location', $this->advert['location']['town_id']));
        } else {
            $ad_location->setLocationTown(null);
        }

        if (isset($this->advert['location']['latitude']) && $this->advert['location']['latitude']) {
            $ad_location->setLatitude($this->advert['location']['latitude']);
        } else {
            $ad_location->setLatitude(null);
        }

        if (isset($this->advert['location']['longitude']) && $this->advert['location']['longitude']) {
            $ad_location->setLongitude($this->advert['location']['longitude']);
        } else {
            $ad_location->setLongitude(null);
        }

        if (isset($this->advert['location']['county_id']) && $this->advert['location']['county_id']) {
            $ad_location->setLocationDomicile($this->em->getReference('FaEntityBundle:Location', $this->advert['location']['county_id']));
        } else {
            $ad_location->setLocationDomicile(null);
        }

        if (isset($this->advert['location']['countrycode']) && $this->advert['location']['countrycode'] == 'GB') {
            $ad_location->setLocationCountry($this->em->getReference('FaEntityBundle:Location', 2));
        } else {
            $ad_location->setLocationCountry(null);
        }

        $this->em->persist($ad_location);
    }

    /**
     * Set ad table data.
     *
     * @param object $user
     * @param object $ad
     *
     * @return Ambigous <string, \Fa\Bundle\AdBundle\Entity\Ad>
     */
    protected function setAd($user, $adMain, $ad = null)
    {
        $newAd = false;
        if (!$ad) {
            $ad = new Ad();
            $newAd = true;
        }

        if (isset($this->advert['title'])) {
            $ad->setTitle($this->advert['title']);
        }

        if (isset($this->advert['affiliate']) && $this->advert['affiliate'] == 1) {
            $ad->setAffiliate(1);

            if (isset($this->advert['image_count'])) {
                $ad->setImageCount($this->advert['image_count']);
            }

            if (isset($this->advert['track_back_url'])) {
                $ad->setTrackBackUrl($this->advert['track_back_url']);
            }
        }

        if (isset($this->advert['advert_source'])) {
            $ad->setSource($this->advert['advert_source']);
        } else {
            $ad->setSource('Feed advert: source not provided');
        }

        if (isset($this->advert['description'])) {
            $ad->setDescription($this->advert['description']);
        } else {
            $ad->setDescription(null);
        }

        if (isset($this->advert['personalized_title'])) {
            $ad->setPersonalizedTitle($this->advert['personalized_title']);
        } else {
            $ad->setPersonalizedTitle(null);
        }

        if (isset($this->advert['unique_id'])) {
            $ad->setTransId($this->advert['unique_id']);
        }

        if (isset($this->advert['price'])) {
            $ad->setPrice($this->advert['price']);
        } else {
            $ad->setPrice(null);
        }

        $metadata = $this->em->getClassMetaData('Fa\Bundle\AdBundle\Entity\Ad');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $ad->setId($adMain->getId());
        $ad->setAdMain($adMain);

        $ad->setType($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_TYPE_FORSALE_ID));

        if (isset($this->advert['category_id']) && $this->advert['category_id'] > 0) {
            $ad->setCategory($this->em->getReference('FaEntityBundle:Category', $this->advert['category_id']));
        } else {
            $ad->setCategory(null);
        }

        if (isset($this->advert['ad_type_id']) && $this->advert['ad_type_id'] > 0) {
            $ad->setType($this->em->getReference('FaEntityBundle:Entity', $this->advert['ad_type_id']));
        } else {
            $ad->setType(null);
        }

        if (isset($this->advert['delivery_method_option_id']) && $this->advert['delivery_method_option_id'] > 0) {
            $ad->setDeliveryMethodOption($this->em->getReference('FaPaymentBundle:DeliveryMethodOption', $this->advert['delivery_method_option_id']));
        } else {
            $ad->setDeliveryMethodOption(null);
        }

        if (isset($this->advert['payment_method_id']) && $this->advert['payment_method_id'] > 0) {
            $ad->setPaymentMethodId($this->advert['payment_method_id']);
        } else {
            $ad->setPaymentMethodId(null);
        }

        if (isset($this->advert['is_new'])) {
            $ad->setIsNew($this->advert['is_new']);
        } else {
            $ad->setIsNew(1);
        }

        if (isset($this->advert['published_date'])) {
            $ad->setPublishedAt($this->advert['published_date']);
        }

        if (isset($this->advert['end_date'])) {
            $ad->setExpiresAt($this->advert['end_date']);
        }

        if (isset($this->advert['updated_date'])) {
            $ad->setUpdatedAt($this->advert['updated_date']);
        }

        if (isset($this->advert['status']) && $this->advert['status'] == 'A') {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_LIVE_ID));
        } elseif (isset($this->advert['status']) && $this->advert['status'] == 'R') {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_REJECTED_ID));
        } else {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_EXPIRED_ID));
            if (isset($this->advert['end_date'])) {
                $ad->setExpiresAt($this->advert['end_date']);
            } else {
                $ad->setExpiresAt(time());
            }
        }

        $ad->setIsFeedAd(1);
        $ad->setUser($user);
        $ad->setSkipSolr(1); // TO skip solr update

        $rejectedReason = count($this->advert['rejected_reason']) > 0 ? serialize($this->advert['rejected_reason']) : null;

        // Save ad is trade ad or not
        $ad->setIsTradeAd(null);

        if ($rejectedReason) {
            $ad->setRejectedReason($rejectedReason);
        } else {
            $ad->setRejectedReason(null);
        }

        $this->em->persist($ad);
        $this->em->flush();
        return $ad;
    }

    /**
     * Set ad table data.
     *
     * @param object $user
     * @param object $ad
     *
     * @return Ambigous <string, \Fa\Bundle\AdBundle\Entity\Ad>
     */
    protected function setAdMain($adMain)
    {
        if (!$adMain) {
            $adMain = new AdMain();
        }

        $adMain->setTransId($this->advert['unique_id']);
        $this->em->persist($adMain);
        $this->em->flush();
        return $adMain;
    }

    /**
     * Set ad feed site user.
     *
     * @param AdFeedSite $ad_feed_site
     * @param User       $user
     *
     * @return AdFeedSiteUser
     */
    protected function setAdFeedSiteUser($ad_feed_site, $user)
    {
        $user_details = $this->em->getRepository('FaAdFeedBundle:AdFeedSiteUser')->findOneBy(array('ad_feed_site' => $ad_feed_site, 'user' => $user));

        if (!$user_details) {
            $user_details = new AdFeedSiteUser();
            $user_details->setAdFeedSite($ad_feed_site);
            $user_details->setUser($user);

            $this->em->persist($user_details);
            $this->em->flush();
        }

        return $user_details;
    }


    /**
     * Load feed file.
     *
     * @param string  $file
     * @param integer $force                 Force update or not.
     * @param integer $siteID site id
     * @param mixed   $ad_feed_site_download
     *
     */
    public function load($file, $force, $siteID, $ad_feed_site_download = null)
    {
        $ads = json_decode(file_get_contents($file), true);
        $ads = isset($ads['ads']) ? $ads['ads'] : array();

        $adFeedSiteStat = new AdFeedSiteStat();
        $adFeedSiteStat->setAdFeedSite($ad_feed_site_download->getAdFeedSite());
        $adFeedSiteStat->setAdFeedSiteDownload($ad_feed_site_download);
        $adFeedSiteStat->setTotalNew(0);
        $adFeedSiteStat->setTotalUpdate(0);
        $adFeedSiteStat->setTotalIdle(0);
        $adFeedSiteStat->setTotalClosed(0);
        $adFeedSiteStat->setTotalNotUpdate(0);
        $this->em->persist($adFeedSiteStat);
        //$this->em->flush();

        foreach ($ads as $ad) {
            $this->advert = array();
            $r = $this->mapAdData($ad, $siteID, $ad_feed_site_download);
            if ($r != 'discard') {
                $this->addToFeedAd($ad_feed_site_download);
                echo ".";
            } else {
                echo "^";
            }
        }

        $this->em->flush();
    }

    /**
     * Map ad data.
     *
     * @param array   $adArray Advert array.
     * @param integer $siteID  site id
     */
    public function mapAdData($adArray, $siteID, $ad_feed_site_download = null)
    {
        $this->advert              = array();
        $this->advert['feed_type'] = 'TradeIt';
        $this->advert['full_data'] = (string) serialize($adArray);
        $this->advert['set_user']  = false;
        $this->advert['status']    = 'A';
        $this->advert['affiliate'] = 1;
        $this->rejectedReason      = null;
        $this->advert['rejected_reason'] = array();
        $this->setCommonData($adArray, $siteID);

        $category                    = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($adArray['category_slug']);
        $this->advert['category_id'] = $category['id'];
        $this->advert['parent_category'] = $adArray['parent_category'];
        ;

        if (!$this->advert['category_id']) {
            $this->setRejectAd();
            $this->setRejectedReason('category missing: '.$adArray['category_slug']);
        }

        $feedAd = null;

        if ($ad_feed_site_download) {
            $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());
        } else {
            $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $siteID));
            $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site->getId());
        }

        if ($feedAd) {
            $this->advert['feed_ad_id'] = $feedAd->getId();
        } else {
            $this->addToFeedAd($ad_feed_site_download);
        }

        $this->advert['dimensions']    = $adArray['dimensions'];
        $this->advert['aff_image_url'] = $adArray['ImageThumbURL'];
        $this->advert['track_back_url']    = $adArray['AdURL'];
        $this->advert['aff_no_image']  = $adArray['NumberOfImages'];
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
     * Setcommon data.
     *
     * @param array   $adArray
     * @param integer $siteID
     */
    protected function setCommonData($adArray, $siteID)
    {
        $this->advert['location']        = array();
        $this->advert['user']            = array();

        $this->advert['published_date'] = $adArray['published_date'];
        $this->advert['updated_date']   = $adArray['updated_date'];

        if ($adArray['status'] == '25') {
            $this->advert['status'] = 'A';
        } else {
            $this->advert['status'] = 'E';
            $this->advert['expired_date'] = $adArray['expired_date'];
        }

        $ec = $this->container->get('fa.entity.cache.manager');

        if (isset($adArray['CONDITION_ID'])) {
            $this->advert['condition']   = $this->getConditionId($adArray['CONDITION_ID']);
        }

        $this->advert['advert_source']  = 'trade-it.co.uk';

        $this->advert['title']                     = $adArray['Title'];
        $this->advert['price']                     = $adArray['Price'];
        $this->advert['currency']                  = 'GBP';
        $this->advert['description']               = $adArray['Description'];
        $this->advert['trans_id']                  = $adArray['Id'];
        $this->advert['unique_id']                 = isset($adArray['Id']) ? $adArray['Id'] : null;
        $this->advert['ref_site_id']               = $siteID;
        $this->advert['last_modified']             = $adArray['updated_date'];
        $this->advert['ad_type_id']                = $adArray['ad_type_id'];
        $this->advert['delivery_method_option_id'] = $adArray['delivery_method_option_id'];
        $this->advert['is_new']                    = isset($adArray['IS_NEW']) && $adArray['IS_NEW'] == 'New' ? 1 : 0;
        $location_not_found = false;


        if ($this->advert['unique_id'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('Unique ID not found');
        }

        if ($adArray['Town'] != '') {
            if ($ec->getEntityIdByName('FaEntityBundle:Location', $adArray['Town'])) {
                $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($adArray['Town'], $this->container, 'name');
                $location_not_found = true;
            }
        }

        if ($location_not_found == true) {
            $this->advert['location']['town_id']     = isset($locationArray['town_id']) && $locationArray['town_id'] ? $locationArray['town_id'] : null;
            $this->advert['location']['latitude']    = isset($locationArray['latitude']) && $locationArray['latitude'] ? $locationArray['latitude'] : null;
            $this->advert['location']['longitude']   = isset($locationArray['longitude']) && $locationArray['longitude'] ? $locationArray['longitude'] : null;
            $this->advert['location']['locality_id'] = isset($locationArray['locality_id']) && $locationArray['locality_id'] ? $locationArray['locality_id'] : null;
            $this->advert['location']['county_id']   = isset($locationArray['county_id']) && $locationArray['county_id'] ? $locationArray['county_id'] : null;
            $this->advert['location']['postcode']    = isset($locationArray['postcode']) && $locationArray['postcode'] ? $locationArray['postcode'] : null;
            $this->advert['location']['countrycode'] = 'GB';
        } else {
            $this->setRejectAd();
            $this->setRejectedReason('Location data not found');
        }

        $this->mapAdImages($adArray);
    }


    /**
     * Parse images.
     *
     * @param string $file
     *
     */
    public function parseAdForImage($ad, $target_dir)
    {
        $i = 0;
        $multi_curl = new MultiCurl();
        if ($ad['EndDate'] == '0001-01-01T00:00:00Z') {
            $multi_curl = $this->downLoadImages($ad, null, $multi_curl, $target_dir);
        }
        $multi_curl->start();
    }

    /**
     * Parse images.
     *
     * @param string $file
     *
     */
    public function parseImages($file, $siteID, $ad_feed_site_download)
    {
        $ads = json_decode(file_get_contents($file), true);
        $ads = (isset($ads['ads']) ? $ads['ads'] : array());

        $i = 0;
        $multi_curl = new MultiCurl();
        foreach ($ads as $ad) {
            if ($ad['status'] == '25') {
                $multi_curl = $this->downLoadImages($ad, $ad_feed_site_download, $multi_curl);
                $i++;
            }

            if ($i%5 == 0) {
                echo 'started with -> '.$i.'=>'.var_dump($i%5)."\n";
                $multi_curl->start();
            }
        }
        $multi_curl->start();
    }

    /**
     * download ad images
     *
     * @param ad $ad
     */
    public function downLoadImages($ad, $ad_feed_site_download, $multi_curl, $target_dir = null)
    {
        $imageArray = array();
        $feedReader = $this->container->get('fa_ad.manager.ad_feed_reader');

        $Id = $ad['Id'];

        if ($target_dir) {
            $site_dir  = $target_dir;
        } else {
            $site_dir  = $ad_feed_site_download->getAdFeedSite()->getType().'_'.$ad_feed_site_download->getAdFeedSite()->getRefSiteId();
        }

        $group_dir = substr($ad['Id'], 0, 3);

        $idir = $this->dir.'/images/'.$site_dir.'/'.$group_dir;
        if (!file_exists($idir)) {
            mkdir($idir, 0777, true);
        }

        $i = 1;
        $fileName     = $idir.'/'.$ad['Id'].'_'.basename($ad['ImageThumbURL']);
        $imageArray[] = $fileName;
        if (!file_exists($fileName)) {
            $multi_curl->addDownload($ad['ImageThumbURL'], function ($instance, $tempFile) use ($fileName) {
                try {
                    $fh = @fopen($fileName, 'wb+');
                    stream_copy_to_stream($tempFile, $fh);
                    fclose($fh);
                    echo 'Downloaded '.$fileName."\n";
                } catch (\Exception $e) {
                    echo 'Download failed '.$e->getMessage()."\n";
                }
            });
        } else {
            echo 'Already exists '.$fileName."\n";
        }
        echo $i++;

        echo "\n";

        $this->removeExtraImages($idir, $Id, $imageArray);
        return $multi_curl;
    }

    /**
     * Remove extra images.
     *
     * @param string $dir        Directory path.
     * @param string $productId  Product id.
     * @param array  $imageArray Image array.
     */
    public function removeExtraImages($dir, $productId, $imageArray)
    {
        $files = glob($dir.'/'.$productId.'_*');
        foreach ($files as $file) {
            if (!in_array($file, $imageArray)) {
                if (is_file($file)) {
                    echo $file.": remove old file \n";
                    unlink($file);
                }
            }
        }
    }

    /**
     * Map add data.
     *
     * @param array   $adArray
     * @param boolean $single_image single image only
     */
    public function mapAdImages($adArray, $single_image = false)
    {
        $this->advert['images'] = array();
        $i = 1;

        $this->advert['images'][1]['ord'] = 0;
        $this->advert['images'][1]['ord'] = $i;

        $this->advert['images'][1]['uri']           = $adArray['ImageThumbURL'];
        $this->advert['images'][1]['last_modified'] = $adArray['updated_date'];
        $this->advert['images'][1]['main_image']    = 1;

        $group_dir = substr($this->advert['unique_id'], 0, 3);
        $idir      = $this->advert['feed_type'].'_'.$this->advert['ref_site_id'].'/'.$group_dir;
        $fileName  = $idir.'/'.$this->advert['unique_id'].'_'.basename($adArray['ImageThumbURL']);
        $this->advert['images'][1]['local_path'] = $fileName;

        $this->advert['image_hash'] = md5(serialize($adArray['ImageThumbURL']));
        $this->advert['image_count'] = $adArray['NumberOfImages'];
    }

    /**
     * add data in child table
     *
     * @see \Fa\Bundle\AdFeedBundle\Parser\AdParser::addChildData()
     */
    public function addChildData($ad)
    {
        $dimension = $this->advert['dimensions'];
        $dimension = isset($dimension[0]) ? $dimension[0] : null;

        if (count($dimension) > 0) {
            if ($this->advert['parent_category'] == 'For Sale') {
                $ad_forsale = $this->em->getRepository('FaAdBundle:AdForSale')->findOneBy(array('ad' => $ad->getId()));

                if (!$ad_forsale) {
                    $ad_forsale= new AdForSale();
                }

                $ad_forsale->setAd($ad);

                if (isset($dimension['age_range_id'])) {
                    $ad_forsale->setAgeRangeId($dimension['age_range_id']);
                } else {
                    $ad_forsale->setAgeRangeId(null);
                }

                if (isset($dimension['brand_clothing_id'])) {
                    $ad_forsale->setBrandClothingId($dimension['brand_clothing_id']);
                } else {
                    $ad_forsale->setBrandClothingId(null);
                }

                if (isset($dimension['brand_id'])) {
                    $ad_forsale->setBrandId($dimension['brand_id']);
                } else {
                    $ad_forsale->setBrandId(null);
                }

                if (isset($dimension['business_type_id'])) {
                    $ad_forsale->setBusinessTypeId($dimension['business_type_id']);
                } else {
                    $ad_forsale->setBusinessTypeId(null);
                }

                if (isset($dimension['colour_id'])) {
                    $ad_forsale->setColourId($dimension['colour_id']);
                } else {
                    $ad_forsale->setColourId(null);
                }

                if (isset($dimension['condition_id'])) {
                    $ad_forsale->setConditionId($dimension['condition_id']);
                } else {
                    $ad_forsale->setConditionId(null);
                }

                if (isset($dimension['meta_data'])) {
                    $ad_forsale->setMetaData($dimension['meta_data']);
                } else {
                    $ad_forsale->setMetaData(null);
                }

                if (isset($dimension['size_id'])) {
                    $ad_forsale->setSizeId($dimension['size_id']);
                } else {
                    $ad_forsale->setSizeId(null);
                }

                $this->em->persist($ad_forsale);
            } elseif ($this->advert['parent_category'] == 'Motors') {
                $ad_motors = $this->em->getRepository('FaAdBundle:AdMotors')->findOneBy(array('ad' => $ad->getId()));

                if (!$ad_motors) {
                    $ad_motors = new AdMotors();
                }

                $ad_motors->setAd($ad);

                if (isset($dimension['berth_id'])) {
                    $ad_motors->setBerthId($dimension['berth_id']);
                } else {
                    $ad_motors->setBerthId(null);
                }

                if (isset($dimension['body_type_id'])) {
                    $ad_motors->setBodyTypeId($dimension['body_type_id']);
                } else {
                    $ad_motors->setBodyTypeId(null);
                }

                if (isset($dimension['colour_id'])) {
                    $ad_motors->setColourId($dimension['colour_id']);
                } else {
                    $ad_motors->setColourId(null);
                }

                if (isset($dimension['fuel_type_id'])) {
                    $ad_motors->setFuelTypeId($dimension['fuel_type_id']);
                } else {
                    $ad_motors->setFuelTypeId(null);
                }

                if (isset($dimension['make_id'])) {
                    $ad_motors->setMakeId($dimension['make_id']);
                } else {
                    $ad_motors->setMakeId(null);
                }

                if (isset($dimension['manufacturer_id'])) {
                    $ad_motors->setManufacturerId($dimension['manufacturer_id']);
                } else {
                    $ad_motors->setManufacturerId(null);
                }

                if (isset($dimension['meta_data'])) {
                    $ad_motors->setMetaData($dimension['meta_data']);
                } else {
                    $ad_motors->setMetaData(null);
                }

                if (isset($dimension['model_id'])) {
                    $ad_motors->setModelId($dimension['model_id']);
                } else {
                    $ad_motors->setModelId(null);
                }

                if (isset($dimension['part_manufacturer_id'])) {
                    $ad_motors->setPartManufacturerId($dimension['part_manufacturer_id']);
                } else {
                    $ad_motors->setPartManufacturerId(null);
                }

                if (isset($dimension['part_of_make_id'])) {
                    $ad_motors->setPartOfMakeId($dimension['part_of_make_id']);
                } else {
                    $ad_motors->setPartOfMakeId(null);
                }

                if (isset($dimension['transmission_id'])) {
                    $ad_motors->setTransmissionId($dimension['transmission_id']);
                } else {
                    $ad_motors->setTransmissionId(null);
                }

                $this->em->persist($ad_motors);
            } elseif ($this->advert['parent_category'] == 'Jobs') {
                $ad_jobs = $this->em->getRepository('FaAdBundle:AdJobs')->findOneBy(array('ad' => $ad->getId()));

                if (!$ad_jobs) {
                    $ad_jobs = new AdJobs();
                }

                $ad_jobs->setAd($ad);

                if (isset($dimension['contract_type_id'])) {
                    $ad_jobs->setContractTypeId($dimension['contract_type_id']);
                } else {
                    $ad_jobs->setContractTypeId(null);
                }

                if (isset($dimension['meta_data'])) {
                    $ad_jobs->setMetaData($dimension['meta_data']);
                } else {
                    $ad_jobs->setMetaData(null);
                }

                $this->em->persist($ad_jobs);
            } elseif ($this->advert['parent_category'] == 'Services') {
                $ad_services = $this->em->getRepository('FaAdBundle:AdServices')->findOneBy(array('ad' => $ad->getId()));

                if (!$ad_services) {
                    $ad_services = new AdServices();
                }

                $ad_services->setAd($ad);

                if (isset($dimension['event_type_id'])) {
                    $ad_services->setEventTypeId($dimension['event_type_id']);
                } else {
                    $ad_services->setEventTypeId(null);
                }

                if (isset($dimension['services_offered_id'])) {
                    $ad_services->setServicesOfferedId($dimension['services_offered_id']);
                } else {
                    $ad_services->setServicesOfferedId(null);
                }

                if (isset($dimension['service_type_id'])) {
                    $ad_services->setServiceTypeId($dimension['service_type_id']);
                } else {
                    $ad_services->setServiceTypeId(null);
                }

                if (isset($dimension['meta_data'])) {
                    $ad_services->setMetaData($dimension['meta_data']);
                } else {
                    $ad_services->setMetaData(null);
                }

                $this->em->persist($ad_services);
            } elseif ($this->advert['parent_category'] == 'Property') {
                $ad_property = $this->em->getRepository('FaAdBundle:AdProperty')->findOneBy(array('ad' => $ad->getId()));

                if (!$ad_property) {
                    $ad_property = new AdProperty();
                }

                $ad_property->setAd($ad);

                if (isset($dimension['amenities_id'])) {
                    $ad_property->setAmenitiesId($dimension['amenities_id']);
                } else {
                    $ad_property->setAmenitiesId(null);
                }

                if (isset($dimension['number_of_bedrooms_id'])) {
                    $ad_property->setNumberOfBedroomsId($dimension['number_of_bedrooms_id']);
                } else {
                    $ad_property->setNumberOfBedroomsId(null);
                }

                if (isset($dimension['room_size_id'])) {
                    $ad_property->setRoomSizeId($dimension['room_size_id']);
                } else {
                    $ad_property->setRoomSizeId(null);
                }

                if (isset($dimension['meta_data'])) {
                    $ad_property->setMetaData($dimension['meta_data']);
                } else {
                    $ad_property->setMetaData(null);
                }

                $this->em->persist($ad_property);
            } elseif ($this->advert['parent_category'] == 'Animals') {
                $ad_animals = $this->em->getRepository('FaAdBundle:AdAnimals')->findOneBy(array('ad' => $ad->getId()));

                if (!$ad_animals) {
                    $ad_animals = new AdAnimals();
                }

                $ad_animals->setAd($ad);

                if (isset($dimension['ad_type_id'])) {
                    $ad_animals->setAdTypeId($dimension['ad_type_id']);
                } else {
                    $ad_animals->setAdTypeId(null);
                }

                if (isset($dimension['breed_id'])) {
                    $ad_animals->setBreedId($dimension['breed_id']);
                } else {
                    $ad_animals->setBreedId(null);
                }

                if (isset($dimension['colour_id'])) {
                    $ad_animals->setColourId($dimension['colour_id']);
                } else {
                    $ad_animals->setColourId(null);
                }

                if (isset($dimension['gender_id'])) {
                    $ad_animals->setGenderId($dimension['gender_id']);
                } else {
                    $ad_animals->setGenderId(null);
                }

                if (isset($dimension['meta_data'])) {
                    $ad_animals->setMetaData($dimension['meta_data']);
                } else {
                    $ad_animals->setMetaData(null);
                }

                if (isset($dimension['species_id'])) {
                    $ad_animals->setSpeciesId($dimension['species_id']);
                } else {
                    $ad_animals->setSpeciesId(null);
                }

                $this->em->persist($ad_animals);
            } elseif ($this->advert['parent_category'] == 'Community') {
                $ad_community = $this->em->getRepository('FaAdBundle:AdCommunity')->findOneBy(array('ad' => $ad->getId()));

                if (!$ad_community) {
                    $ad_community = new AdCommunity();
                }

                $ad_community->setAd($ad);

                if (isset($dimension['education_level_id'])) {
                    $ad_community->setEducationLevelId($dimension['education_level_id']);
                } else {
                    $ad_community->setEducationLevelId(null);
                }

                if (isset($dimension['experience_level_id'])) {
                    $ad_community->setExperienceLevelId($dimension['experience_level_id']);
                } else {
                    $ad_community->setExperienceLevelId(null);
                }

                if (isset($dimension['meta_data'])) {
                    $ad_community->setMetaData($dimension['meta_data']);
                } else {
                    $ad_community->setMetaData(null);
                }

                $this->em->persist($ad_community);
            } elseif ($this->advert['parent_category'] == 'Adult') {
                $ad_adult = $this->em->getRepository('FaAdBundle:AdAdult')->findOneBy(array('ad' => $ad->getId()));

                if (!$ad_adult) {
                    $ad_adult = new AdAdult();
                }

                $ad_adult->setAd($ad);

                if (isset($dimension['meta_data'])) {
                    $ad_adult->setMetaData($dimension['meta_data']);
                } else {
                    $ad_adult->setMetaData(null);
                }

                $this->em->persist($ad_adult);
            }
        }
    }

    /**
     * Get condition id.
     *
     * @param string $string
     *
     * @return number
     */
    public function getConditionId($string)
    {
        if ($string == 'New') {
            return 5;
        } elseif ($string == 'Excellent') {
            return 6;
        } elseif ($string == 'Good') {
            return 7;
        } elseif ($string == 'Average') {
            return 8;
        } elseif ($string == 'Poor') {
            return 9;
        } else {
            return 5;
        }
    }

    /**
     * Get feed ad object.
     *
     * @param string $ref
     *
     * @return object
     */
    protected function getFeedAdByRef($ref, $ref_site_id)
    {
        if ($ref) {
            return $this->em->getRepository('FaAdFeedBundle:AdFeed')->findOneBy(array('unique_id' => $ref, 'ref_site_id' => $ref_site_id));
        }
    }

    /**
     * Get ad object by ref.
     *
     * @param string $ref
     *
     * @return object
     */
    protected function getAdByRef($ref)
    {
        if ($ref) {
            return $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('trans_id' => $ref));
        }
    }

    /**
     * Get main ad object by ref.
     *
     * @param string $ref
     *
     * @return object
     */
    protected function getAdMainByRef($ref)
    {
        if ($ref) {
            return $this->em->getRepository('FaAdBundle:AdMain')->findOneBy(array('trans_id' => $ref));
        }
    }

    /**
     * Set reject ad.
     */
    protected function setRejectAd()
    {
        $this->advert['status'] = 'R';
    }

    /**
     * Set rejected reason.
     *
     * @param object $reason
     */
    protected function setRejectedReason($reason)
    {
        $this->advert['rejected_reason'][] = $reason;
    }
}
