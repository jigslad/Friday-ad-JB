<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2019, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdFeedBundle\Repository\AdFeedRepository;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdImage;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteUser;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdMain;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Gedmo\Sluggable\Util\Urlizer;
use \Curl\MultiCurl;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Entity\AdUserPackage;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;


/**
 * This command is used to create advert when feed status is active.
 * While converting feed to advert, sometimes Feed status is active but advert not created, For this issue this command is created to fix the issue.
 *
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2020 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateFeedAdIdCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
            ->setName('fa:feed:update-feed-ad-id')
            ->setDescription("Update feed ads id and add to solr")
            ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
            ->addOption('trans_id', null, InputOption::VALUE_OPTIONAL, 'feed advert unique Id', null)
            ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'user Id', null)
            ->addOption('start_date', null, InputOption::VALUE_OPTIONAL, 'startDate in unix timestamp', 0)
            ->addOption('end_date', null, InputOption::VALUE_OPTIONAL, 'endDate in unix timestamp', 0)
            ->setHelp(
                <<<EOF
Actions: This cron need not be in cron list. This command can be run manually whenever feed has missed advert but feed status is active. 
Command:
   php bin/console fa:feed:update-feed-ad-id
   php bin/console fa:feed:update-feed-ad-id --trans_id=12345
EOF
            );
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        //get options passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateAdIdWithOffset($input, $output);
        } else {
            $this->updateAdId($input, $output);
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdIdWithOffset($input, $output)
    {
        $qb          = $this->getAdQueryBuilder(false, $input);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $feedAds = $qb->getQuery()->getArrayResult();

        $feedReader  = $this->getContainer()->get('fa_ad.manager.ad_feed_reader');

        //$adparserClass = 'Fa\Bundle\AdFeedBundle\Parser\AdParser';
        //$parser = new $adparserClass($this->getContainer());

        if (!empty($feedAds)) {
            foreach ($feedAds as $feedOrgAd) {
                $this->advert   = array();
                $this->advert  = unserialize($feedOrgAd['ad_text']);

                $uniqueId = $feedOrgAd['unique_id'];
                $origFeed = $this->feedAdvertRequest($uniqueId);
                $parser = $feedReader->createParser($origFeed['AdvertType']);
                $feedAd = $this->em->getRepository('FaAdFeedBundle:AdFeed')->findOneBy(array('unique_id' => $uniqueId));

                if (isset($this->advert['full_data'])) {
                    $originalJson  = unserialize($this->advert['full_data']);
                    $parser->mapAdData($originalJson, $this->advert['ref_site_id']);

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

                if ($this->advert['set_user'] === true && $this->advert['user']['email']!='') {
                    $user = $this->em->getRepository('FaUserBundle:User')->getUserByUsername($this->advert['user']['email']);
                } else {
                    $user = null;
                }
                if($this->advert['unique_id']) {
                    $ad = $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('trans_id' => $this->advert['unique_id']));
                }

                if ($ad || $feedAd->getStatus() != 'R') {
                    if($this->advert['unique_id']) {
                        $adMain = $this->em->getRepository('FaAdBundle:AdMain')->findOneBy(array('trans_id' => $this->advert['unique_id']));
                    }
                    if (isset($this->advert['set_user']) && $this->advert['set_user'] === true) {
                        if (!$user && $this->advert['user']['email'] != '') {
                            $user = $this->setUser($user, $this->advert);
                        }
                    }

                    $this->setAdFeedSiteUser($ad_feed_site, $user);

                    $adMain = $this->setAdMain($this->advert);


                    $ad = $this->setAd($user, $adMain, $ad, $this->advert);
                    echo "{ new }";


                    $this->advert['image_hash'] = isset($this->advert['image_hash']) ? $this->advert['image_hash'] : null;

                    $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('id' => $feedAd->getRefSiteId()));
                    $target_dir = $ad_feed_site->getType().'_'.$ad_feed_site->getRefSiteId();
                    $this->parseAdForImage($originalJson, $target_dir);
                    $this->updateImages($ad,$this->advert);


                    $this->assignOrRemoveAdPackage($ad, $user,$this->advert);
                    $this->setAdLocation($ad,$this->advert);
                    $parser->addChildData($ad);

                    $this->getContainer()->get('fa_ad.entity_listener.ad')->handleSolr($ad);

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

                    $this->sendFeedCallback($feedAd, $this->advert['last_modified']);
                    return $ad;
                } else {
                    echo "X".$feedAd->getTransId()."\n";
                    $this->sendFeedCallback($feedAd, $this->advert['last_modified']);

                }
            }

        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    public function sendFeedCallback($feedAd, $lastModified)
    {
        if ($feedAd->getStatus() != 'R' || ($feedAd->getStatus() == 'R' && date('Y-m-d') == date('Y-m-d', strtotime($lastModified)))) {
            $adFeedCallbackManager = $this->getContainer()->get('fa_ad.feed.callback.manager');
            $adFeedCallbackManager->init($feedAd);
            $adFeedCallbackManager->sendRequest();
        }
    }

    public function updateImages($ad,$origFeed)
    {
        $adImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/image/';
        $imagePath  = $adImageDir.CommonManager::getGroupDirNameById($ad->getId());

        $i = 1;

        $currentImages = $this->em->getRepository('FaAdBundle:AdImage')->getAdImages($ad->getId());

        // todo: download the new feed image here

        // Deleting all old images
        foreach ($currentImages as $image) {
            $adImageManager = new AdImageManager($this->getContainer(), $ad->getId(), $image->getHash(), $imagePath);
            $adImageManager->removeImage();
            $this->em->remove($image);
        }

        // Processing the images again
        foreach ($origFeed['images'] as $img) {
            $filePath = $this->getContainer()->getParameter('fa.feed.data.dir').'/images/'.$img['local_path'];
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

                //$origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, 75, 'ImageMagickManager');
                //$origImage->loadFile($filePath);
                //$origImage->save($imagePath.'/'.$ad->getId().'_'.$hash.'.jpg', 'image/jpeg');
                exec('convert -flatten '.escapeshellarg($filePath).' '.$imagePath.'/'.$ad->getId().'_'.$hash.'.jpg');

                $adImageManager = new AdImageManager($this->getContainer(), $ad->getId(), $hash, $imagePath);
                $adImageManager->createThumbnail();
                $adImageManager->createCropedThumbnail();

                $adImgPath = $imagePath.'/'.$ad->getId().'_'.$hash.'.jpg';
                if (file_exists($adImgPath)) {
                    $adImageManager->uploadImagesToS3($image);
                    unlink($filePath);
                }

                $i++;
            }
        }

        $this->em->flush();
    }

    public function parseAdForImage($ad, $target_dir)
    {
        $i = 0;
        $multi_curl = new MultiCurl();
        if ($ad['EndDate'] == '0001-01-01T00:00:00Z'|| strtotime($ad['EndDate']) >= time()) {
            $multi_curl = $this->downLoadImages($ad, null, $multi_curl, $target_dir);
        }
        $multi_curl->start();
    }
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
        $feedReader = $this->getContainer()->get('fa_ad.manager.ad_feed_reader');

        $Id = $ad['Id'];

        if ($target_dir) {
            $site_dir  = $target_dir;
        } else {
            $site_dir  = $ad_feed_site_download->getAdFeedSite()->getType().'_'.$ad_feed_site_download->getAdFeedSite()->getRefSiteId();
        }

        $group_dir = substr($ad['Id'], 0, 8);

        $idir = $this->getContainer()->getParameter('fa.feed.data.dir').'/images/'.$site_dir.'/'.$group_dir;
        if (!file_exists($idir)) {
            $old     = umask(0);
            mkdir($idir, 0777, true);
            umask($old);
        }

        // todo: Download only max 20 images.
        // todo: Check with @Laura to confirm - the image count.

        $i = 1;
        foreach ($ad['AdvertImages'] as $key => $img) {
            $fileName     = $idir.'/'.$ad['Id'].'_'.basename($img['Uri']);
            $imageArray[] = $fileName;

            // todo: Affiliate ad check should be happening outside the foreach-loop
            // todo: Inside the for-loop the Affiliate is not going to change as its the same ad.

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
     * Remove old deleted images - from NFS [+ S3].
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
    public function setAd($user, $adMain, $ad = null,$origFeed)
    {
        $ad = new Ad();
        $newAd = true;


        if (isset($origFeed['title'])) {
            $ad->setTitle($origFeed['title']);
        }

        if (isset($origFeed['affiliate']) && $origFeed['affiliate'] == 1) {
            $ad->setAffiliate(1);

            if (isset($origFeed['image_count'])) {
                $ad->setImageCount($origFeed['image_count']);
            }

            if (isset($origFeed['track_back_url'])) {
                $ad->setTrackBackUrl($origFeed['track_back_url']);
            }
        }

        if (isset($origFeed['advert_source'])) {
            $ad->setSource($origFeed['advert_source']);
        } else {
            $ad->setSource('Feed advert: source not provided');
        }

        if (isset($origFeed['description'])) {
            $ad->setDescription($origFeed['description']);
        } else {
            $ad->setDescription(null);
        }

        if (isset($origFeed['personalized_title'])) {
            $ad->setPersonalizedTitle($origFeed['personalized_title']);
        } else {
            $ad->setPersonalizedTitle(null);
        }

        if (isset($origFeed['unique_id'])) {
            $ad->setTransId($origFeed['unique_id']);
        }

        if (isset($origFeed['price'])) {
            $ad->setPrice($origFeed['price']);
        } else {
            $ad->setPrice(null);
        }

        $metadata = $this->em->getClassMetaData('Fa\Bundle\AdBundle\Entity\Ad');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $ad->setId($adMain->getId());
        $ad->setAdMain($adMain);

        $ad->setType($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_TYPE_FORSALE_ID));

        if (isset($origFeed['category_id']) && $origFeed['category_id'] > 0) {
            $ad->setCategory($this->em->getReference('FaEntityBundle:Category', $origFeed['category_id']));
        } else {
            $ad->setCategory(null);
        }

        if (isset($origFeed['ad_type_id']) && $origFeed['ad_type_id'] > 0) {
            $ad->setType($this->em->getReference('FaEntityBundle:Entity', $origFeed['ad_type_id']));
        } else {
            $ad->setType(null);
        }

        if (isset($origFeed['delivery_method_option_id']) && $origFeed['delivery_method_option_id'] > 0) {
            $ad->setDeliveryMethodOption($this->em->getReference('FaPaymentBundle:DeliveryMethodOption', $origFeed['delivery_method_option_id']));
        } else {
            $ad->setDeliveryMethodOption(null);
        }

        if (isset($origFeed['payment_method_id']) && $origFeed['payment_method_id'] > 0) {
            $ad->setPaymentMethodId($origFeed['payment_method_id']);
        } else {
            $ad->setPaymentMethodId(null);
        }

        if (isset($origFeed['is_new'])) {
            $ad->setIsNew($origFeed['is_new']);
        } else {
            $ad->setIsNew(1);
        }

        if (isset($origFeed['published_date'])) {
            $ad->setPublishedAt($origFeed['published_date']);
        }

        if (isset($origFeed['end_date'])) {
            $ad->setExpiresAt($origFeed['end_date']);
        }

        if (isset($origFeed['updated_date'])) {
            $ad->setUpdatedAt($origFeed['updated_date']);
        }

        if (isset($origFeed['status']) && $origFeed['status'] == 'A') {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_LIVE_ID));
        } elseif (isset($origFeed['status']) && $origFeed['status'] == 'R') {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_REJECTED_ID));
        } else {
            $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_EXPIRED_ID));
            if (isset($origFeed['end_date'])) {
                $ad->setExpiresAt($origFeed['end_date']);
            } else {
                $ad->setExpiresAt(time());
            }
        }

        $ad->setIsFeedAd(1);
        $ad->setUser($user);
        $ad->setSkipSolr(1); // TO skip solr update

        $rejectedReason = count($origFeed['rejected_reason']) > 0 ? serialize($origFeed['rejected_reason']) : null;

        // Save ad is trade ad or not
        if ($user) {
            $userRoles = $this->em->getRepository('FaUserBundle:User')->getUserRolesArray($user);
            if (!empty($userRoles)) {
                if (in_array(RoleRepository::ROLE_BUSINESS_SELLER, $userRoles) || in_array(RoleRepository::ROLE_NETSUITE_SUBSCRIPTION, $userRoles)) {
                    $ad->setIsTradeAd(1);
                } elseif (in_array(RoleRepository::ROLE_SELLER, $userRoles)) {
                    $ad->setIsTradeAd(0);
                }
            }
        } else {
            if (isset($origFeed['is_trade_ad'])) {
                $ad->setIsTradeAd($origFeed['is_trade_ad']);
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
    public function setAdLocation($ad,$origFeed)
    {
        $ad_location = $this->em->getRepository('FaAdBundle:AdLocation')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_location) {
            $ad_location = new AdLocation();
        }

        $ad_location->setAd($ad);

        if (isset($origFeed['location']['postcode']) && $origFeed['location']['postcode']) {
            $ad_location->setPostcode($origFeed['location']['postcode']);
        } else {
            $ad_location->setPostcode(null);
        }

        if (isset($origFeed['location']['town_id']) && $origFeed['location']['town_id']) {
            $ad_location->setLocationTown($this->em->getReference('FaEntityBundle:Location', $origFeed['location']['town_id']));
            //check for location area
            if ($origFeed['location']['town_id'] == LocationRepository::LONDON_TOWN_ID) {
                //check post Code is exist
                if (isset($origFeed['location']['postcode']) && $origFeed['location']['postcode'] != '') {
                    $getPostalCode = explode(" ", $origFeed['location']['postcode']);
                    $getArea = $this->em->getRepository('FaEntityBundle:LocationPostal')->getAreasByPostCode($getPostalCode[0]);
                    if (!empty($getArea)) {
                        if (count($getArea) == '1') {
                            $ad_location->setLocationArea($this->em->getReference('FaEntityBundle:Location', $getArea[0]['id']));
                        } elseif (count($getArea) > '1') {
                            //get the nearest area for this location
                            $getNearestAreaObj = $this->em->getRepository('FaEntityBundle:Location')->getNearestAreaByPostLatLong($origFeed['location']['postcode'], $origFeed['location']['town_id']);
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

        if (isset($origFeed['location']['latitude']) && $origFeed['location']['latitude']) {
            $ad_location->setLatitude($origFeed['location']['latitude']);
        } else {
            $ad_location->setLatitude(null);
        }

        if (isset($origFeed['location']['longitude']) && $origFeed['location']['longitude']) {
            $ad_location->setLongitude($origFeed['location']['longitude']);
        } else {
            $ad_location->setLongitude(null);
        }

        if (isset($origFeed['location']['county_id']) && $origFeed['location']['county_id']) {
            $ad_location->setLocationDomicile($this->em->getReference('FaEntityBundle:Location', $origFeed['location']['county_id']));
        } else {
            $ad_location->setLocationDomicile(null);
        }

        if (isset($origFeed['location']['countrycode']) && $origFeed['location']['countrycode'] == 'GB') {
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
    public function setForSaleData($ad, $origFeed)
    {
        $ad_forsale = $this->em->getRepository('FaAdBundle:AdForSale')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_forsale) {
            $ad_forsale = new AdForSale();
        }

        $ad_forsale->setAd($ad);

        if ($origFeed['condition']) {
            $ad_forsale->setConditionId($origFeed['condition']);
        }

        $this->em->persist($ad_forsale);
        $this->em->flush();
    }

    public function setAdMain($origFeed)
    {

        $adMain = new AdMain();

        $adMain->setTransId($origFeed['unique_id']);
        $this->em->persist($adMain);
        $this->em->flush($adMain);
        return $adMain;
    }

    public function setAdFeedSiteUser($ad_feed_site, $user)
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


    public function setUser($user, $origFeed)
    {
        if (!$user) {
            $user = new User();
            $user->setUsername($origFeed['user']['email']);
            $user->setPassword(md5($origFeed['user']['email']));
            $user->setEmail($origFeed['user']['email']);
            $userActiveStatus = $this->em->getRepository('FaEntityBundle:Entity')->find(EntityRepository::USER_STATUS_ACTIVE_ID);
            $user->setStatus($userActiveStatus);
            if ($origFeed['user']['role'] == RoleRepository::ROLE_BUSINESS_SELLER) {
                $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_BUSINESS_SELLER));
                $user->addRole($sellerRole);
                $user->setRole($sellerRole);
            } elseif ($origFeed['user']['role'] == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
                $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_NETSUITE_SUBSCRIPTION));
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

        if (in_array(RoleRepository::ROLE_BUSINESS_SELLER, $roles) || in_array(RoleRepository::ROLE_NETSUITE_SUBSCRIPTION, $roles)) {
            if ($user->getBusinessName() == '') {
                $user->setBusinessName($origFeed['user']['business_name']);
            }

            if ($user->getBusinessCategoryId() == '') {
                if (isset($origFeed['user']['business_category_id'])) {
                    $user->setBusinessCategoryId($origFeed['user']['business_category_id']);
                }
            }
        } else {
            if ($user->getZip() == '') {
                $user->setZip($origFeed['user']['poscode']);
            }
            if ($user->getFirstName() == '') {
                if (isset($origFeed['user']['first_name'])) {
                    $user->setFirstName($origFeed['user']['first_name']);
                }
            }
        }

        if ($user->getPhone() == '') {
            $user->setPhone($origFeed['user']['phone']);
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

        if (in_array(RoleRepository::ROLE_BUSINESS_SELLER, $roles) || in_array(RoleRepository::ROLE_NETSUITE_SUBSCRIPTION, $roles)) {
            $user_site = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user));

            if (!$user_site) {
                $user_site = new UserSite();
                $user_site->setUser($user);
            }

            if ($user_site->getWebsiteLink() == '') {
                $user_site->setWebsiteLink($origFeed['user']['website']);
            }

            if ($user_site->getCompanyAddress() == '') {
                $address   = array();
                $address[] = $origFeed['user']['house_name'] != '' ? $origFeed['user']['house_name'] : null ;
                $address[] = $origFeed['user']['local_area'] != '' ? $origFeed['user']['local_area'] : null ;
                $address[] = $origFeed['user']['area'] != '' ? $origFeed['user']['area'] : null ;
                $address[] = $origFeed['user']['town'] != '' ? $origFeed['user']['town'] : null ;
                $address[] = $origFeed['user']['country'] != '' ? $origFeed['user']['country'] : null ;
                $address[] = $origFeed['user']['poscode'] != '' ? $origFeed['user']['poscode'] : null ;
                $address  = array_filter($address);
                $companyAddress = implode(', ', $address);
                $user_site->setCompanyAddress($companyAddress);
            }

            if ($user_site->getPhone1() == '') {
                $user_site->setPhone1($origFeed['user']['phone']);
            }

            if ($user_site->getPhone2() == '') {
                $user_site->setPhone2($origFeed['user']['mobile']);
            }

            if ($user_site->getStatus() == '') {
                $user_site->setStatus(1);
            }

            if ($user_site->getSlug() == '') {
                $this->em->getRepository('FaUserBundle:User')->getUserProfileSlug($user->getId(), $this->getContainer(), false);
            }

            $this->em->persist($user_site);
            $this->em->flush();

            $package = $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);

            if (!$package) {
                $this->em->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($user, null, $this->getContainer(), false);
            }
        }

        return $user;
    }

    /**
     * Update solr index.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdId($input, $output)
    {
        $qb        = $this->getAdQueryBuilder(true, $input);
        $count     = $qb->getQuery()->getSingleScalarResult();
        $step      = 1000;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads : '.$count, true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'="'.$value.'"';
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = ' -d memory_limit=100M';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:feed:update-feed-ad-id '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Get query builder for ads.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder($onlyCount = false, $input)
    {
        $uniqueId = trim($input->getOption('trans_id'));
        $userId = trim($input->getOption('user_id'));
        $startDate = $input->getOption('start_date').' 00:00:01';
        $endDate = $input->getOption('end_date').' 23:59:59';

        $adFeedRepository  = $this->em->getRepository('FaAdFeedBundle:AdFeed');
        $qb = $adFeedRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            $qb->select('COUNT('.AdFeedRepository::ALIAS.'.id)');
        } else {
            $qb->select(AdFeedRepository::ALIAS);
        }

        $qb->where(AdFeedRepository::ALIAS.".status = 'A'");
        $qb->andWhere(AdFeedRepository::ALIAS.'.ad is NULL');

        if (!empty($uniqueId)) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.unique_id = :unique_id')->setParameter('unique_id', $uniqueId);
        }
        if($userId) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.user_id = :user_id')->setParameter('user_id', $userId);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.last_modified >= :startDate')
                ->andWhere(AdFeedRepository::ALIAS.'.last_modified <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }
        if (!$onlyCount) {
            $qb->orderBy(AdFeedRepository::ALIAS.'.id');
        }

        return $qb;
    }

    /**
     * Handle fmgfeedaggregation request.
     *
     * @param string $transId fmgfeedaggregation request.
     *
     * @return string
     */
    protected function feedAdvertRequest($advertId)
    {
        $mode = $this->getContainer()->getParameter('fa.feed.mode');
        $mainUrl = $this->getContainer()->getParameter('fa.feed.'.$mode.'.url');

        $url = $mainUrl.'/Adverts/GetAdvertById?appkey='.$this->getContainer()->getParameter('fa.feed.api.id').'&advertId='.$advertId;

        // Build the HTTP Request Headers
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = json_decode(curl_exec($ch), true);

        return $response;
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
    protected function assignOrRemoveAdPackage($ad, $user = null,$origFeed)
    {
        if (isset($origFeed['package_id']) && $origFeed['package_id'] && $ad->getStatus() && $ad->getStatus()->getId() == EntityRepository::AD_STATUS_LIVE_ID) {
            $this->handleAdPackage($ad, $user, 'assign-if-no-package',$origFeed);
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
    protected function handleAdPackage($ad, $user = null, $type = 'update',$origFeed)
    {
        $deleteManager = $this->getContainer()->get('fa.deletemanager');
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
        } elseif ($type == 'update' && (!$adUserPackage || ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getId() != $origFeed['package_id']))) {
            if ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getId() != $origFeed['package_id']) {
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
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($origFeed['package_id']);
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
        } elseif ($type == 'assign-if-no-package' && !$adUserPackage && $origFeed['package_id']) {
            $adUserPackage = new AdUserPackage();

            // find & set package
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($origFeed['package_id']);
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
            $package = $this->em->getRepository('FaPromotionBundle:Package')->find($origFeed['package_id']);
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
}

