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
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageUpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdUserPackage;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;

/**
 * Ad parser.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
abstract class AdParser
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
     * Map ad data.
     *
     * @param array   $adArray Advert array.
     * @param integer $siteID  site id
     */
    abstract public function mapAdData($adArray, $siteID, $ad_feed_site_download);


    abstract public function addChildData($ad);

    /**
     * Get category id.
     *
     * @param string $string Category.
     */
    abstract public function getCategoryId($cat_name = null);

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
     * Get user object.
     *
     * @param string $email Email address.
     *
     * @return object
     */
    protected function getUser($email)
    {
        if ($email != '') {
            return $this->em->getRepository('FaUserBundle:User')->getUserByUsername($email);
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
     * Set user data.
     *
     * @param $user
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    protected function setUser($user)
    {
        if (!$user) {
            $user = new User();
            $user->setUsername($this->advert['user']['email']);
            $user->setPassword(md5($this->advert['user']['email']));
            $user->setEmail($this->advert['user']['email']);
            $userActiveStatus = $this->em->getRepository('FaEntityBundle:Entity')->find(EntityRepository::USER_STATUS_ACTIVE_ID);
            $user->setStatus($userActiveStatus);
            if ($this->advert['user']['role'] == RoleRepository::ROLE_BUSINESS_SELLER) {
                $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_BUSINESS_SELLER));
                $user->addRole($sellerRole);
                $user->setRole($sellerRole);
            } else {
                $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_SELLER));
                $user->addRole($sellerRole);
                $user->setRole($sellerRole);
            }

            $user->setIsFeedUser(1);
            $this->em->persist($user);
        }

        $user_roles = $user->getRoles();

        $roles = array();
        foreach ($user_roles as $role) {
            $roles[] = $role->getName();
        }

        $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_BUSINESS_SELLER));

        if (in_array(RoleRepository::ROLE_BUSINESS_SELLER, $roles)) {
            if ($user->getBusinessName() == '') {
                $user->setBusinessName($this->advert['user']['business_name']);
            }

            if ($user->getBusinessCategoryId() == '') {
                if (isset($this->advert['user']['business_category_id'])) {
                    $user->setBusinessCategoryId($this->advert['user']['business_category_id']);
                }
            }
        } else {
            if ($user->getZip() == '') {
                $user->setZip($this->advert['user']['poscode']);
            }
            if ($user->getFirstName() == '') {
                if (isset($this->advert['user']['first_name'])) {
                    $user->setFirstName($this->advert['user']['first_name']);
                }
            }
        }

        if ($user->getPhone() == '') {
            $user->setPhone($this->advert['user']['phone']);
        }

        if ($user->getPhone() == '') {
            $user->setContactThroughPhone(0);
        } else {
            $user->setContactThroughPhone(1);
        }

        if (preg_match('/@email_unknown_clickedit.com/', $user->getEmail())) {
            $user->setContactThroughEmail(0);
        } else {
            $user->setContactThroughEmail(1);
        }

        $user->setIsFeedUser(1);
        $this->em->persist($user);
        $this->em->flush();

        if (in_array(RoleRepository::ROLE_BUSINESS_SELLER, $roles)) {
            $user_site = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user));

            if (!$user_site) {
                $user_site = new UserSite();
                $user_site->setUser($user);
            }

            if ($user_site->getWebsiteLink() == '') {
                $user_site->setWebsiteLink($this->advert['user']['website']);
            }

            if ($user_site->getCompanyAddress() == '') {
                $address   = array();
                $address[] = $this->advert['user']['house_name'] != '' ? $this->advert['user']['house_name'] : null ;
                $address[] = $this->advert['user']['local_area'] != '' ? $this->advert['user']['local_area'] : null ;
                $address[] = $this->advert['user']['area'] != '' ? $this->advert['user']['area'] : null ;
                $address[] = $this->advert['user']['town'] != '' ? $this->advert['user']['town'] : null ;
                $address[] = $this->advert['user']['country'] != '' ? $this->advert['user']['country'] : null ;
                $address[] = $this->advert['user']['poscode'] != '' ? $this->advert['user']['poscode'] : null ;
                $address  = array_filter($address);
                $companyAddress = implode(', ', $address);
                $user_site->setCompanyAddress($companyAddress);
            }

            if ($user_site->getPhone1() == '') {
                $user_site->setPhone1($this->advert['user']['phone']);
            }

            if ($user_site->getPhone2() == '') {
                $user_site->setPhone2($this->advert['user']['mobile']);
            }

            if ($user_site->getStatus() == '') {
                $user_site->setStatus(1);
            }

            if ($user_site->getSlug() == '') {
                $this->em->getRepository('FaUserBundle:User')->getUserProfileSlug($user->getId(), $this->container, false);
            }

            $this->em->persist($user_site);
            $this->em->flush();

            $package = $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);

            if (!$package) {
                $this->em->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($user, null, $this->container, false);
            }
        }

        return $user;
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
        $ads = $ads['AdvertCollection'];

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

        if (count($ads)) {
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
        if ($ad['EndDate'] == '0001-01-01T00:00:00Z'|| strtotime($ad['EndDate']) >= time()) {
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
        $ads = $ads['AdvertCollection'];

        $i = 0;
        $multi_curl = new MultiCurl();
        if (count($ads)) {
            foreach ($ads as $ad) {
                if ($ad['EndDate'] == '0001-01-01T00:00:00Z' || strtotime($ad['EndDate']) >= time()) {
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
    }

    /**
     * download ad images
     *
     * @param ad $ad
     */
    public function downLoadImages($ad, $ad_feed_site_download, $multi_curl, $target_dir = null)
    {
        //get is affiliate site or not
        $affiliate = 0;
        if (isset($ad['SiteVisibility']) && is_array($ad['SiteVisibility'])) {
            foreach ($ad['SiteVisibility'] as $site) {
                if (isset($site['SiteId']) && $site['SiteId'] == 10) {
                    if (($site['IsMainSite'] === 'false') || ($site['IsMainSite'] === false)) {
                        $affiliate = 1;
                    }
                }
            }
        }

        $imageArray = array();
        $feedReader = $this->container->get('fa_ad.manager.ad_feed_reader');

        $Id = $ad['Id'];

        if ($target_dir) {
            $site_dir  = $target_dir;
        } else {
            $site_dir  = $ad_feed_site_download->getAdFeedSite()->getType().'_'.$ad_feed_site_download->getAdFeedSite()->getRefSiteId();
        }

        $group_dir = substr($ad['Id'], 0, 8);

        $idir = $this->dir.'/images/'.$site_dir.'/'.$group_dir;
        if (!file_exists($idir)) {
            mkdir($idir, 0777, true);
        }

        $i = 1;
        foreach ($ad['AdvertImages'] as $key => $img) {
            $fileName     = $idir.'/'.$ad['Id'].'_'.basename($img['Uri']);
            $imageArray[] = $fileName;
            if (!$affiliate || ($affiliate && $img['IsMainImage'])) {
                if (!file_exists($fileName)) {
                    $multi_curl->addDownload($img['Uri'], function ($instance, $tempFile) use ($fileName) {
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
            }
        }//end if

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
     * Add advert data.
     *
     * @param integer            $force                 Force update or not.
     * @param AdFeedSiteDownload $ad_feed_site_download AdFeedSiteDownload object.
     *
     * @return boolean
     */
    public function updateUser($feedAd, $force)
    {
        $this->advert   = array();
        $this->advert  = unserialize($feedAd->getAdText());
        if ((isset($this->advert['set_user']) && $this->advert['set_user'] === true) || $force == 1) {
            if ($this->advert['user']['email'] != '') {
                $user = $this->getUser($this->advert['user']['email']);
                if ((md5(serialize($this->advert)) != $feedAd->getUserHash())) {
                    $user = $this->setUser($user);
                    echo 'Information update for '.$user->getUsername()."\n";
                }
            }
        }
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
            if ($force == 'iremap') {
                $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('id' => $feedAd->getRefSiteId()));
                $target_dir = $ad_feed_site->getType().'_'.$ad_feed_site->getRefSiteId();
                $this->parseAdForImage($originalJson, $target_dir);
            }

            if (isset($this->advert['status']) && $this->advert['status'] == 'R') {
                $feedAd->setStatus('R');
                if (implode(',', $this->advert['rejected_reason']) != '') {
                    $feedAd->setRemark(implode(',', $this->advert['rejected_reason']));
                }
            } elseif (isset($this->advert['status']) && $this->advert['status'] == 'E') {
                $feedAd->setStatus('E');
            } else {
                $feedAd->setStatus('A');
                $feedAd->setRemark(null);
            }

            $feedAd->setAdText(serialize($this->advert));
            $this->em->persist($feedAd);
            $this->em->flush();
        }

        $ad_feed_site   = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $this->advert['ref_site_id']));

        if ($this->advert['set_user'] === true) {
            $user = $this->getUser($this->advert['user']['email']);
        } else {
            $user = null;
        }

        $ad = $this->getAdByRef($this->advert['unique_id']);

        if ($ad || $feedAd->getStatus() != 'R') {
            $adMain         = $this->getAdMainByRef($this->advert['unique_id']);

            if (isset($this->advert['set_user']) && $this->advert['set_user'] === true) {
                if (!$user && $this->advert['user']['email'] != '') {
                    $user = $this->setUser($user);
                }
            }

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

            $this->assignOrRemoveAdPackage($ad, $user);
            $this->setAdLocation($ad);
            $this->addChildData($ad);

            $feedAd->setAd($ad);
            $feedAd->setUser($user);
            $feedAd->setImageHash($this->advert['image_hash']);
            if ($this->advert['set_user'] === true) {
                $feedAd->setUserHash($this->advert['user_hash']);
            }
            $feedAd->setHash(md5(serialize($this->advert)));
            //$run_time = gmdate('Y-m-d\TH:i:s\Z',$this->advert['last_modified']);
            $lastRunTime = new \DateTime($this->advert['last_modified']);
            $feedAd->setLastModified($lastRunTime);
            $this->em->persist($feedAd);
            $this->em->flush();

            if (!$force) {
                $this->sendFeedCallback($feedAd, $this->advert['last_modified']);
            }

            return $ad;
        } else {
            echo "X".$feedAd->getTransId()."\n";
            if (!$force) {
                $this->sendFeedCallback($feedAd, $this->advert['last_modified']);
            }
        }
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
        if ($user) {
            $userRoles = $this->em->getRepository('FaUserBundle:User')->getUserRolesArray($user);
            if (count($userRoles)) {
                if (in_array(RoleRepository::ROLE_BUSINESS_SELLER, $userRoles)) {
                    $ad->setIsTradeAd(1);
                } elseif (in_array(RoleRepository::ROLE_SELLER, $userRoles)) {
                    $ad->setIsTradeAd(0);
                }
            }
        } else {
            if (isset($this->advert['is_trade_ad'])) {
                $ad->setIsTradeAd($this->advert['is_trade_ad']);
            } else {
                $ad->setIsTradeAd(null);
            }
        }

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
            //check for location area
            if ($this->advert['location']['town_id'] == LocationRepository::LONDON_TOWN_ID) {
                //check post Code is exist
                if (isset($this->advert['location']['postcode']) && $this->advert['location']['postcode'] != '') {
                    $getPostalCode = explode(" ", $this->advert['location']['postcode']);
                    $getArea = $this->em->getRepository('FaEntityBundle:LocationPostal')->getAreasByPostCode($getPostalCode[0]);
                    if (!empty($getArea)) {
                        if (count($getArea) == '1') {
                            $ad_location->setLocationArea($this->em->getReference('FaEntityBundle:Location', $getArea[0]['id']));
                        } elseif (count($getArea) > '1') {
                            //get the nearest area for this location
                            $getNearestAreaObj = $this->em->getRepository('FaEntityBundle:Location')->getNearestAreaByPostLatLong($this->advert['location']['postcode'], $this->advert['location']['town_id']);
                            if (!empty($getNearestAreaObj) && $getNearestAreaObj->getLvl() == 4) {
                                $ad_location->setLocationArea($getNearestAreaObj);
                            }
                        }
                    }
                }
            }
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
     * Set for sale data.
     *
     * @param object $ad
     */
    protected function setForSaleData($ad)
    {
        $ad_forsale = $this->em->getRepository('FaAdBundle:AdForSale')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_forsale) {
            $ad_forsale = new AdForSale();
        }

        $ad_forsale->setAd($ad);

        if ($this->advert['condition']) {
            $ad_forsale->setConditionId($this->advert['condition']);
        }

        $this->em->persist($ad_forsale);
        $this->em->flush();
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
            $filePath = $this->dir.'/images/'.$img['local_path'];
            $dimension = @getimagesize($filePath);

            if (file_exists($filePath) && $dimension) {
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

        $this->advert['published_date'] = strtotime($adArray['StartDate']);
        $this->advert['updated_date'] = strtotime($adArray['LastModified']);

        if (($adArray['EndDate'] == '0001-01-01T00:00:00Z' || strtotime($adArray['EndDate']) >= time()) && $this->advert['status'] == 'A') {
            $this->advert['status'] = 'A';
        } elseif ($adArray['EndDate'] != '0001-01-01T00:00:00Z' && strtotime($adArray['EndDate']) < time() && $this->advert['status'] == 'A') {
            $this->advert['status'] = 'E';
            $this->advert['end_date'] = strtotime($adArray['EndDate']);
        }

        $ec = $this->container->get('fa.entity.cache.manager');

        if (isset($adArray['Details']['Condition'])) {
            $this->advert['condition']   = $this->getConditionId($adArray['Details']['Condition']);
        }

        if (isset($adArray['AdvertSource'])) {
            if (in_array(strtolower($adArray['AdvertSource']), array('kapow scrape', 'not specified')) && $this->advert['affiliate'] == 1) {
                if (isset($adArray['SiteVisibility']) && is_array($adArray['SiteVisibility'])) {
                    foreach ($adArray['SiteVisibility'] as $site) {
                        if (($site['IsMainSite'] === 'true') || ($site['IsMainSite'] === true)) {
                            $this->advert['advert_source'] = CommonManager::addHttpToUrl($site['Site']);
                        }
                    }
                }
            }

            if (!isset($this->advert['advert_source']) || $this->advert['advert_source'] == '') {
                $this->advert['advert_source']  = CommonManager::addHttpToUrl($adArray['AdvertSource']);
            }
        }

        $this->advert['title']         = $adArray['Title'];
        $this->advert['price']         = $adArray['Price'];
        $this->advert['currency']      = $adArray['Currency'];
        $this->advert['description']   = isset($adArray['Descriptions'][0]['Text']) ? $adArray['Descriptions'][0]['Text'] : null;
        $this->advert['trans_id']      = $adArray['OriginatorsReference'];
        $this->advert['unique_id']     = isset($adArray['Id']) ? $adArray['Id'] : null;
        $this->advert['ref_site_id']   = $siteID;
        $this->advert['last_modified'] = $adArray['LastModified'];
        $location_not_found = false;


        if ($this->advert['unique_id'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('Unique ID not found');
        }

        if ($adArray['AdvertType'] == 'MotorhomeAdvert' || $adArray['AdvertType'] == 'ClickEditVehicleAdvert' || $adArray['AdvertType'] == 'JobAdvert') {
            $locationArray = array();
            if ($adArray['Advertiser']['Postcode']) {
                $locationArray = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodInfoArrayByLocation($adArray['Advertiser']['Postcode'], $this->container, true);
            }
            if (count($locationArray) > 0) {
                $location_not_found = true;
            } else {
                $townstring = explode(',', $adArray['Advertiser']['TownCity']);
                if (isset($townstring[0]) && $townstring[0]) {
                    if ($ec->getEntityIdByName('FaEntityBundle:Location', $townstring[0])) {
                        $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($townstring[0], $this->container, 'name');
                        $location_not_found = true;
                    } else {
                        $locationArray = $this->em->getRepository('FaEntityBundle:Locality')->getLocalityInfoArrayById($townstring[0], $this->container, 'name');
                        $location_not_found = true;
                    }
                }
            }
        } else {
            if ($adArray['Town'] != '') {
                $this->advert['location']['locality']   = $adArray['Locality'];
                $townstring = explode(',', $adArray['Town']);

                if ($townstring[0] && $ec->getEntityIdByName('FaEntityBundle:Location', $townstring[0])) {
                    $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($townstring[0], $this->container, 'name');
                    $location_not_found = true;
                } elseif ($townstring[0]) {
                    $locationArray = $this->em->getRepository('FaEntityBundle:Locality')->getLocalityInfoArrayById($townstring[0], $this->container, 'name');
                    $location_not_found = true;
                }
            } else {
                $locationArray = array();
                if ($adArray['Advertiser']['Postcode']) {
                    $locationArray = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodInfoArrayByLocation($adArray['Advertiser']['Postcode'], $this->container, true);
                }

                if (count($locationArray) > 0) {
                    $location_not_found = true;
                } else {
                    $townstring = explode(',', $adArray['Advertiser']['TownCity']);
                    if (isset($townstring[0]) && $townstring[0]) {
                        if ($ec->getEntityIdByName('FaEntityBundle:Location', $townstring[0])) {
                            $locationArray = $this->em->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($townstring[0], $this->container, 'name');
                            $location_not_found = true;
                        } else {
                            $locationArray = $this->em->getRepository('FaEntityBundle:Locality')->getLocalityInfoArrayById($townstring[0], $this->container, 'name');
                            $location_not_found = true;
                        }
                    }
                }
            }
        }

        if ($location_not_found == true) {
            if (count($locationArray) < 1 && $adArray['Advertiser']['Postcode']) {
                // Fall back to advertiser
                $locationArray = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodInfoArrayByLocation($adArray['Advertiser']['Postcode'], $this->container, true);
            }

            if (count($locationArray) > 0) {
                $this->advert['location']['town_id']     = isset($locationArray['town_id']) && $locationArray['town_id'] ? $locationArray['town_id'] : null;
                $this->advert['location']['latitude']    = isset($locationArray['latitude']) && $locationArray['latitude'] ? $locationArray['latitude'] : null;
                $this->advert['location']['longitude']   = isset($locationArray['longitude']) && $locationArray['longitude'] ? $locationArray['longitude'] : null;
                $this->advert['location']['locality_id'] = isset($locationArray['locality_id']) && $locationArray['locality_id'] ? $locationArray['locality_id'] : null;
                $this->advert['location']['county_id']   = isset($locationArray['county_id']) && $locationArray['county_id'] ? $locationArray['county_id'] : null;
                
                if (isset($locationArray['town_id']) && ($locationArray['town_id'] == LocationRepository::LONDON_TOWN_ID || (isset($locationArray['lvl']) && $locationArray['lvl'] == 4))) {
                    $this->advert['location']['postcode']    = isset($adArray['Advertiser']['Postcode']) && $adArray['Advertiser']['Postcode'] ? $adArray['Advertiser']['Postcode'] : null;
                } else {
                    $this->advert['location']['postcode']    = isset($locationArray['postcode']) && $locationArray['postcode'] ? $locationArray['postcode'] : null;
                }
                $this->advert['location']['countrycode'] = 'GB';
            } else {
                $this->setRejectAd();
                $this->setRejectedReason('Location data not found');
            }
        } else {
            $this->setRejectAd();
            $this->setRejectedReason('Location data not found');
        }

        //TODO: Need to refine user mapping
        $this->advert['user']['email']          = $adArray['Advertiser']['CanonicalEmail'];
        $this->advert['user']['business_name']  = $adArray['Advertiser']['BrokerName'];
        $this->advert['user']['area']           = $adArray['Advertiser']['AddressLine1'];
        $this->advert['user']['local_area']     = $adArray['Advertiser']['AddressLine2'];
        $this->advert['user']['road']           = $adArray['Advertiser']['AddressLine3'];
        $this->advert['user']['house_name']     = $adArray['Advertiser']['AddressLine4'];
        $this->advert['user']['town']           = $adArray['Advertiser']['TownCity'];
        $this->advert['user']['poscode']        = $adArray['Advertiser']['Postcode'];
        $this->advert['user']['country']        = $adArray['Advertiser']['Country'];
        $this->advert['user']['country_code']   = $adArray['Advertiser']['CountryCode'];
        $this->advert['user']['phone']          = $adArray['Advertiser']['Telephone'];
        $this->advert['user']['mobile']         = $adArray['Advertiser']['Mobile'];
        $this->advert['user']['website']        = $adArray['Advertiser']['Website'];
        $this->advert['user_hash']              = md5(serialize($this->advert['user']).'X');
        $this->advert['user']['role']           = RoleRepository::ROLE_BUSINESS_SELLER;
    }

    /**
     * Map add data.
     *
     * @param array   $imageArray
     * @param boolean $single_image single image only
     */
    public function mapAdImages($imageArray, $single_image = false)
    {
        $this->advert['images'] = array();
        $i = 1;
        foreach ($imageArray as $image) {
            if ($image['IsMainImage']) {
                $this->advert['images'][$i]['ord'] = 0;
            } else {
                $this->advert['images'][$i]['ord'] = $i;
            }

            $this->advert['images'][$i]['uri']           = $image['Uri'];
            $this->advert['images'][$i]['last_modified'] = $image['LastModified'];
            $this->advert['images'][$i]['main_image']    = $image['IsMainImage'];

            $group_dir = substr($this->advert['unique_id'], 0, 8);
            $idir      = $this->advert['feed_type'].'_'.$this->advert['ref_site_id'].'/'.$group_dir;
            $fileName  = $idir.'/'.$this->advert['unique_id'].'_'.basename($image['Uri']);
            $this->advert['images'][$i]['local_path'] = $fileName;

            if ($single_image) {
                break;
            }
            $i++;
        }

        $this->advert['image_hash'] = md5(serialize($this->advert['images']));
        $this->advert['image_count'] = count($imageArray);
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
        if ($string == 'new') {
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
     * Assign ad package
     *
     * @param object $ad
     * @param object $user
     */
    protected function assignOrRemoveAdPackage($ad, $user = null)
    {
        if (isset($this->advert['package_id']) && $this->advert['package_id'] && $ad->getStatus() && $ad->getStatus()->getId() == EntityRepository::AD_STATUS_LIVE_ID) {
            $this->handleAdPackage($ad, $user, 'assign-if-no-package');
        } else {
            //$this->handleAdPackage($ad, $user, 'remove');
        }
    }

    /**
     * Remove or assign package
     *
     * @param object $ad
     * @param object $user
     * @param string $type
     */
    protected function handleAdPackage($ad, $user = null, $type = 'update')
    {
        $deleteManager = $this->container->get('fa.deletemanager');
        $adId = $ad->getId();
        $adUserPackage = $this->em->getRepository('FaAdBundle:AdUserPackage')->findOneBy(array('ad_id' => $adId, 'status' => AdUserPackageRepository::STATUS_ACTIVE), array('id' => 'DESC'));

        if ($adUserPackage && $type == 'remove') {
            //remove ad use package upsell
            $objAdUserPackageUpsells = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
            if ($objAdUserPackageUpsells  && $type == 'remove') {
                foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                    $deleteManager->delete($objAdUserPackageUpsell);
                }
            }
            //remove ad user package
            $deleteManager->delete($adUserPackage);
        } elseif ($type == 'update' && (!$adUserPackage || ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getId() != $this->advert['package_id']))) {
            if ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getId() != $this->advert['package_id']) {
                //remove ad use package upsell
                $objAdUserPackageUpsells = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
                if ($objAdUserPackageUpsells) {
                    foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                        $deleteManager->delete($objAdUserPackageUpsell);
                    }
                }

                //remove ad user package
                $deleteManager->delete($adUserPackage);
            }

            $this->em->getRepository('FaAdBundle:AdUserPackage')->clear();
            $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->clear();
            $adUserPackage = new AdUserPackage();

            // find & set package
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($this->advert['package_id']);
            $adUserPackage->setPackage($package);

            // set ad
            $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackage->setAdMain($adMain);
            $adUserPackage->setAdId($adId);
            $adUserPackage->setStatus(AdUserPackageRepository::STATUS_ACTIVE);
            $adUserPackage->setStartedAt(time());
            if ($package->getDuration()) {
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            // set user
            if ($user) {
                $adUserPackage->setUser($user);
            }

            $adUserPackage->setPrice($package->getPrice());
            $adUserPackage->setDuration($package->getDuration());
            $this->em->persist($adUserPackage);
            $this->em->flush();
        } elseif ($type == 'assign-if-no-package' && !$adUserPackage && $this->advert['package_id']) {
            $adUserPackage = new AdUserPackage();

            // find & set package
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($this->advert['package_id']);
            $adUserPackage->setPackage($package);

            // set ad
            $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackage->setAdMain($adMain);
            $adUserPackage->setAdId($adId);
            $adUserPackage->setStatus(AdUserPackageRepository::STATUS_ACTIVE);
            $adUserPackage->setStartedAt(time());
            if ($package->getDuration()) {
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            // set user
            if ($user) {
                $adUserPackage->setUser($user);
            }

            $adUserPackage->setPrice($package->getPrice());
            $adUserPackage->setDuration($package->getDuration());
            $this->em->persist($adUserPackage);
            $this->em->flush();
        }

        if (isset($adUserPackage) && $adUserPackage && $adUserPackage->getId() && $type == 'update') {
            $packageUpsellIds = array();
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($this->advert['package_id']);
            foreach ($package->getUpsells() as $upsell) {
                $this->addAdUserPackageUpsell($ad, $adUserPackage, $upsell);
                $packageUpsellIds[] = $upsell->getId();
            }
            $objAdUserPackageUpsells = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
            foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                if (!in_array($objAdUserPackageUpsell->getUpsell()->getId(), $packageUpsellIds)) {
                    $deleteManager->delete($objAdUserPackageUpsell);
                }
            }
        }

        $isWeeklyRefresh = $this->em->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($ad->getId());

        // Check weekly refresh upsell purchased then set weekly_refresh_at field
        if ($isWeeklyRefresh && !$ad->getWeeklyRefreshAt()) {
            $ad->setWeeklyRefreshAt(time());
        } elseif ($isWeeklyRefresh && $ad->getWeeklyRefreshAt() && $ad->getPublishedAt() && $ad->getWeeklyRefreshAt() < $ad->getPublishedAt()) {
            $ad->setWeeklyRefreshAt($ad->getPublishedAt());
        } elseif ($isWeeklyRefresh === false) {
            $ad->setWeeklyRefreshAt(null);
        }

        $this->em->persist($ad);
        $this->em->flush($ad);
    }

    /**
     * Add ad user package upsell
     *
     * @param object $ad
     * @param object $adUserPackage
     * @param object $upsell
     */
    protected function addAdUserPackageUpsell($ad, $adUserPackage, $upsell)
    {
        $adId = $ad->getId();
        $adUserPackageUpsellObj = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findOneBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId(), 'status' => 1, 'upsell' => $upsell->getId()));
        if (!$adUserPackageUpsellObj) {
            $adUserPackageUpsell = new AdUserPackageUpsell();
            $adUserPackageUpsell->setUpsell($upsell);

            // set ad user package id.
            if ($adUserPackage) {
                $adUserPackageUpsell->setAdUserPackage($adUserPackage);
            }

            // set ad
            $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackageUpsell->setAdMain($adMain);
            $adUserPackageUpsell->setAdId($adId);

            $adUserPackageUpsell->setValue($upsell->getValue());
            $adUserPackageUpsell->setValue1($upsell->getValue1());
            $adUserPackageUpsell->setDuration($upsell->getDuration());
            $adUserPackageUpsell->setStatus(1);
            $adUserPackageUpsell->setStartedAt(time());
            if ($upsell->getDuration()) {
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($upsell->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            $this->em->persist($adUserPackageUpsell);
            $this->em->flush();
        }
    }

    /**
     * Send feed callback
     *
     * @param object $feedAd Object of feed ad.
     */
    protected function sendFeedCallback($feedAd, $lastModified)
    {
        if ($feedAd->getStatus() != 'R' || ($feedAd->getStatus() == 'R' && date('Y-m-d') == date('Y-m-d', strtotime($lastModified)))) {
            $adFeedCallbackManager = $this->container->get('fa_ad.feed.callback.manager');
            $adFeedCallbackManager->init($feedAd);
            $adFeedCallbackManager->sendRequest();
        }
    }
}
