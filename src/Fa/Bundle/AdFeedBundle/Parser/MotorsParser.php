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
use Fa\Bundle\AdBundle\Entity\AdMotors;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedMapping;
use Fa\Bundle\AdFeedBundle\Entity\AdFeed;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
/**
 * Car parser.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class MotorsParser extends AdParser
{

    /**
     * Map ad data.
     *
     * @param array   $adArray Advert array.
     * @param integer $siteID  site id
     */
    public function mapAdData($adArray, $siteID, $ad_feed_site_download = null)
    {
        $this->advert = array();
        $this->advert['feed_type'] = 'ClickEditVehicleAdvert';
        $this->advert['full_data'] = (string) serialize($adArray);
        $this->advert['set_user']  = true;
        $this->rejectedReason      = null;
        $this->advert['ad_status']  = true;
        $this->advert['status']    = 'A';
        $this->advert['rejected_reason'] = array();
        $this->advert['affiliate'] = 0;

        $configRulePackageId = $this->em->getRepository('FaCoreBundle:ConfigRule')->getClickEditVehicleAdvertsPackageId($this->container);

        if ($configRulePackageId) {
            $this->advert['package_id'] = $configRulePackageId;
        }

        $this->setCommonData($adArray, $siteID);
        $this->advert['user']['business_category_id'] = CategoryRepository::MOTORS_ID;

        $this->advert['personalized_title'] = isset($adArray['Details']['Summary']) ? $adArray['Details']['Summary'] : null;

        $description = array();

        if (is_array($adArray['Descriptions'])) {
            foreach ($adArray['Descriptions'] as $d) {
                $description[] = $d['Text'];
            }
        } else {
            $description[] = isset($adArray['Descriptions']) ? $adArray['Descriptions'] : null;
        }

        $description[] = isset($adArray['Details']['Keywords']) && $adArray['Details']['Keywords'] != '' ? $adArray['Details']['Keywords'] : null;

        $this->advert['description'] = implode('\n', $description);

        if (isset($adArray['Details']['VehicleType']) && $adArray['Details']['VehicleType'] == 'Car') {
            $this->setCarData($adArray);
        } elseif (isset($adArray['Details']['VehicleType']) && ($adArray['Details']['VehicleType'] == 'Van' || $adArray['Details']['VehicleType'] == 'Truck')) {
            $this->setCVData($adArray);
        } elseif (isset($adArray['Details']['VehicleType']) && ($adArray['Details']['VehicleType'] == 'Motorhome')) {
            $this->setMotorhomesData($adArray);
        } elseif (isset($adArray['Details']['VehicleType']) && ($adArray['Details']['VehicleType'] == 'Caravan')) {
            $this->setCaravanData($adArray);
        } elseif (isset($adArray['Details']['VehicleType']) && ($adArray['Details']['VehicleType'] == 'Motorbike' || $adArray['Details']['VehicleType'] == 'Bike')) {
            $this->setMotorbikeData($adArray);
        } else {
            $this->setRejectAd();
            $this->setRejectedReason('Unknown vehicle type: '.$adArray['Details']['VehicleType']);
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

        if (!isset($this->advert['category_id'])) {
            $this->setRejectAd();
            $this->setRejectedReason('Category not found');
        }

        if ($feedAd) {
            $this->advert['feed_ad_id'] = $feedAd->getId();
        } else {
            $this->addToFeedAd($ad_feed_site_download);
        }

        if ($this->advert['user']['email'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('email is blank');
        }

        $adImages = isset($adArray['AdvertImages']) && count($adArray['AdvertImages']) > 0 ? $adArray['AdvertImages'] : array();
        $this->mapAdImages($adImages);
    }

    public function addToFeedAd($ad_feed_site_download)
    {
        $ad_feed_site   = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $this->advert['ref_site_id']));
        $user           = $this->getUser($this->advert['user']['email']);
        $feedAd         = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());

        if (preg_match('/@email_unknown_clickedit.com/', $this->advert['user']['email']) &&  $this->advert['user']['phone'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('email unknown click edit with no phone');
        }

        if ($this->advert['user']['email'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('email is blank');
        }

        if (!$user && $this->advert['user']['email'] != '') {
            //$this->setRejectAd();
            $user = $this->setUser($user);
        }

        if (!$feedAd) {
            $feedAd = new AdFeed();
        }

        $getUserStatus = EntityRepository::USER_STATUS_ACTIVE_ID;
        
        if(!empty($user) && $this->advert['user']['email'] != '') {
            $getUserStatus = $this->em->getRepository('FaUserBundle:User')->getUserStatusByEmail($this->advert['user']['email']);
        } 

        $feedAd->setUser($user);
        $feedAd->setIsUpdated(1);
        $feedAd->setTransId($this->advert['trans_id']);
        $feedAd->setUniqueId($this->advert['unique_id']);
        $feedAd->setRefSiteId($ad_feed_site_download->getAdFeedSite()->getId());
        $feedAd->setAdText(serialize($this->advert));
        $feedAd->setLastModified($ad_feed_site_download->getModifiedSince());

        if (isset($this->advert['status']) && $this->advert['status'] == 'R') {
            $feedAd->setStatus('R');
            if (implode(',', $this->advert['rejected_reason']) != '') {
                $feedAd->setRemark(implode(',', $this->advert['rejected_reason']));
            }
        } elseif($getUserStatus != EntityRepository::USER_STATUS_ACTIVE_ID) {
            $feedAd->setRemark('User account is blocked/inactive');
            $feedAd->setUser($user);
            $feedAd->setStatus('R');
        } elseif (isset($this->advert['status']) && $this->advert['status'] == 'E') {
            $feedAd->setStatus('E');
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
     * set car data
     *
     * @param array $adArray
     */
    private function setCarData($adArray)
    {
        if ($adArray['Model'] != '') {
            $makeUrl  = Urlizer::urlize($adArray['Manufacturer']);
            $parent_cat= $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($adArray['Manufacturer'], 'motors/cars/', $this->container, false, $makeUrl);

            if (!count($parent_cat) > 0) {
                $matchedText = $this->getMatchedText('Car'.'#'.$adArray['Manufacturer']);
                if ($matchedText != '') {
                    $TargetCat= $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($matchedText, $this->container);
                    if (isset($TargetCat['id']) && $TargetCat['name']) {
                        $parent_cat[] = array('id' => $TargetCat['id'], 'name' => $TargetCat['name']);
                    }
                }
            }

            if (count($parent_cat) > 0) {
                $parent_cat_id = $parent_cat[0]['id'];
                $parent_name   = $parent_cat[0]['name'];
                $this->setModelId($adArray, $parent_cat_id, $parent_name);
            }
        }

        if (!isset($this->advert['category_id'])) {
            $this->setRejectAd();
            $this->rejectedReason = 'Category missing: '.$adArray['Model'].'#'.$adArray['Manufacturer'];
        }

        if (isset($adArray['Details']['BodyType']) && $adArray['Details']['BodyType'] != '') {
            $numberOfDoors = null;
            if (isset($adArray['Details']['NumberOfDoors']) && $adArray['Details']['NumberOfDoors'] != '') {
                $numberOfDoors = $adArray['Details']['NumberOfDoors'];
            }
            $this->advert['motors']['body_type_id'] = $this->getBodyTypeId($adArray['Details']['BodyType'], 50, $numberOfDoors);
        }

        if (isset($adArray['Details']['Colour']) && $adArray['Details']['Colour'] != '') {
            $this->advert['motors']['colour_id'] = $this->getColorId($adArray['Details']['Colour'], 49);

            if (!$this->advert['motors']['colour_id']) {
                $this->advert['motors']['meta_data']['colour'] = $adArray['Details']['Colour'];
            }
        }

        if (isset($adArray['Details']['FuelType']) && $adArray['Details']['FuelType'] != '') {
            $this->advert['motors']['fuel_type_id'] = $this->getFuelTypeId($adArray['Details']['FuelType'], 52);
        }

        if (isset($adArray['Details']['Transmission']) && $adArray['Details']['Transmission'] != '') {
            $this->advert['motors']['transmission_id'] = $this->getTransmissionId($adArray['Details']['Transmission'], 53);
        }

        if (isset($adArray['Details']['Mileage']) && $adArray['Details']['Mileage'] != '') {
            $this->advert['motors']['meta_data']['mileage'] = $adArray['Details']['Mileage'] ;
        }

        if (isset($adArray['Details']['Keywords']) && $adArray['Details']['Keywords'] != '') {
            $keywords = explode(',', $adArray['Details']['Keywords']);
            $f = array();
            foreach ($keywords as $keyword) {
                $id = $this->getFeaturesId(trim($keyword), 64);
                if ($id) {
                    $f[] = $id;
                }
            }
            $this->advert['motors']['meta_data']['features_id'] = implode(',', $f);
        }

        if (isset($adArray['Details']['EngineSize']) && $adArray['Details']['EngineSize'] != '') {
            $this->advert['motors']['meta_data']['engine_size'] = $adArray['Details']['EngineSize'] ;
        }

        if (isset($adArray['Details']['NumberOfDoors']) && $adArray['Details']['NumberOfDoors'] != '') {
            $this->advert['motors']['meta_data']['no_of_doors'] = $adArray['Details']['NumberOfDoors'] ;
        }

        if (isset($adArray['Details']['Year']) && $adArray['Details']['Year'] != '') {
            $this->advert['motors']['meta_data']['reg_year'] = $adArray['Details']['Year'] ;
        }
    }

    /**
     * set model id
     *
     * @param string $name
     * @param integer $parent_cat_id
     * @param string $parent_name
     *
     * @return void
     */
    private function setModelId($adArray, $parent_cat_id, $parent_name, $cat_text = 'Car')
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByNameAndParentId($adArray['Model'], $parent_cat_id, $this->container);
        if ($cat) {
            $this->advert['category_id'] = $cat['id'];
        } else {
            $clean_model = strtolower(str_replace(array('-', '.', ' ', '/', '+', strtolower($parent_name)), '', strtolower($adArray['Model'])));
            $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByNameAndParentId($clean_model, $parent_cat_id, $this->container, true);
            if ($cat) {
                $this->advert['category_id'] = $cat['id'];
            } else {
                $matchedText = $this->getMatchedText($cat_text.'#'.$adArray['Manufacturer'].'#'.$adArray['Model']);

                if ($matchedText != '') {
                    $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($matchedText, $this->container);
                } else {
                    $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByNameAndParentId('Others', $parent_cat_id, $this->container);
                }

                if ($cat) {
                    $this->advert['category_id'] = $cat['id'];
                }
            }
        }
    }

    /**
     * get matched string
     *
     * @param string text
     *
     * @return string
     */
    private function getMatchedText($text)
    {
        $mapping = $this->em->getRepository('FaAdFeedBundle:AdFeedMapping')->findOneBy(array('text' => $text));

        if ($mapping) {
            return $mapping->getTarget();
        } else {
            $mapping = new AdFeedMapping();
            $mapping->setText($text);
            $this->em->persist($mapping);
            $this->em->flush();
        }
    }

    /**
     * set motor homes data
     *
     * @param array $adArray
     *
     * @return void
     */
    private function setMotorbikeData($adArray)
    {
        $matchedName = 'Motorbikes';
        $cat= $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($matchedName, 'motors/motorbikes/motorcycles', $this->container);
        $this->advert['category_id'] = $cat[0]['id'];

        if (isset($adArray['Details']['Keywords']) && $adArray['Details']['Keywords'] != '') {
            $keywords = explode(',', $adArray['Details']['Keywords']);
            $f = array();
            foreach ($keywords as $keyword) {
                $id = $this->getFeaturesId(trim($keyword), 125);
                if ($id) {
                    $f[] = $id;
                }
            }
            $this->advert['motors']['meta_data']['features_id'] = implode(',', $f);
        }

        if (isset($adArray['Details']['Year']) && $adArray['Details']['Year'] != '') {
            $this->advert['motors']['meta_data']['reg_year'] = $adArray['Details']['Year'] ;
        }

        if (isset($adArray['Manufacturer']) && $adArray['Manufacturer'] != '') {
            $this->advert['motors']['make_id'] = $this->getMotorMakeId($adArray['Manufacturer'], 114);

            if (!$this->advert['motors']['make_id']) {
                $this->advert['motors']['meta_data']['make'] = $adArray['Manufacturer'];
                if (isset($adArray['Model']) && $adArray['Model'] != '') {
                	$this->advert['motors']['meta_data']['model'] = $adArray['Model'];
                }
            } else {
            	if (isset($adArray['Model']) && $adArray['Model'] != '') {
            		$this->advert['motors']['model_id']  = $this->getBikeModelId($adArray['Model'], $this->advert['motors']['make_id']);
            		if (!$this->advert['motors']['model_id']) {
            			$this->advert['motors']['meta_data']['model'] = $adArray['Model'];
            		}
            	}
            }
        }

    }


    /**
     * get model id
     *
     * @param unknown $string
     * @param unknown $make_id
     */
    public function getBikeModelId($string, $make_id)
    {
    	$entity = $this->em->getRepository('FaEntityBundle:Entity')->findOneBy(array('name' => $string, 'parent_id' => $make_id));

    	if ($entity) {
    		return $entity->getId();
    	}
    }

    /**
     * set motor homes data
     *
     * @param array $adArray
     *
     * @return void
     */
    private function setCaravanData($adArray)
    {
        $matchedName = 'Caravans';

        $matchedUrl  = Urlizer::urlize($matchedName);
        $cat= $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($matchedName, 'motors/motorhomes-caravans/', $this->container);
        $this->advert['category_id'] = $cat[0]['id'];

        if (isset($adArray['Details']['Keywords']) && $adArray['Details']['Keywords'] != '') {
            $keywords = explode(',', $adArray['Details']['Keywords']);
            $f = array();
            foreach ($keywords as $keyword) {
                $id = $this->getFeaturesId(trim($keyword), 133);
                if ($id) {
                    $f[] = $id;
                }
            }
            $this->advert['motors']['meta_data']['features_id'] = implode(',', $f);
        }

        if (isset($adArray['Details']['Year']) && $adArray['Details']['Year'] != '') {
            $this->advert['motors']['meta_data']['reg_year'] = $adArray['Details']['Year'] ;
        }

        if (isset($adArray['Manufacturer']) && $adArray['Manufacturer'] != '') {
            $this->advert['motors']['make_id'] = $this->getMotorMakeId($adArray['Manufacturer'], 128);

            if (!$this->advert['motors']['make_id']) {
                $this->advert['motors']['meta_data']['make'] = $adArray['Manufacturer'];
            }
        }

        if (isset($adArray['Model']) && $adArray['Model'] != '') {
            $this->advert['motors']['model_id'] = null;
            if (isset($this->advert['motors']['make_id']) && $this->advert['motors']['make_id']) {
                $this->advert['motors']['model_id'] = $this->getModelId($adArray['Model'], $this->advert['motors']['make_id']);
            }

            if (!$this->advert['motors']['model_id']) {
                $this->advert['motors']['meta_data']['model'] = $adArray['Model'];
            }
        }
    }

    /**
     * set motor homes data
     *
     * @param array $adArray
     *
     * @return void
     */
    private function setMotorhomesData($adArray)
    {
        if ($adArray['Details']['VehicleType'] == 'Motorhome') {
            $matchedName = 'Motorhomes';
        }

        $matchedUrl  = Urlizer::urlize($matchedName);
        $cat= $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($matchedName, 'motors/motorhomes-caravans/', $this->container);
        $this->advert['category_id'] = $cat[0]['id'];

        if (isset($adArray['Details']['Keywords']) && $adArray['Details']['Keywords'] != '') {
            $keywords = explode(',', $adArray['Details']['Keywords']);
            $f = array();
            foreach ($keywords as $keyword) {
                $id = $this->getFeaturesId(trim($keyword), 133);
                if ($id) {
                    $f[] = $id;
                }
            }
            $this->advert['motors']['meta_data']['features_id'] = implode(',', $f);
        }

        if (isset($adArray['Details']['Mileage']) && $adArray['Details']['Mileage'] != '') {
            $this->advert['motors']['meta_data']['mileage'] = $adArray['Details']['Mileage'] ;
        }

        if (isset($adArray['Details']['EngineSize']) && $adArray['Details']['EngineSize'] != '') {
            $this->advert['motors']['meta_data']['engine_size'] = $adArray['Details']['EngineSize'] ;
        }

        if (isset($adArray['Details']['Year']) && $adArray['Details']['Year'] != '') {
            $this->advert['motors']['meta_data']['reg_year'] = $adArray['Details']['Year'] ;
        }

        if (isset($adArray['Manufacturer']) && $adArray['Manufacturer'] != '') {
            $this->advert['motors']['make_id'] = $this->getMotorMakeId($adArray['Manufacturer'], 250);

            if (!$this->advert['motors']['make_id']) {
                $this->advert['motors']['meta_data']['make'] = $adArray['Manufacturer'];
            }
        }

        if (isset($adArray['Model']) && $adArray['Model'] != '') {
            $this->advert['motors']['model_id'] = null;
            if (isset($this->advert['motors']['make_id']) && $this->advert['motors']['make_id']) {
                $this->advert['motors']['model_id'] = $this->getModelId($adArray['Model'], $this->advert['motors']['make_id']);
            }

            if ($this->advert['motors']['model_id'] != '') {
                $this->advert['motors']['meta_data']['model'] = $adArray['Model'];
            }
        }
    }

    /**
     * set motorhomes and van data
     *
     * @param array $adArray
     */
    private function setCVData($adArray)
    {
        if ($adArray['Model'] != '') {
            $makeUrl  = Urlizer::urlize($adArray['Manufacturer']);
            $parent_cat= $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($adArray['Manufacturer'], 'motors/commercial-vehicles/', $this->container, false, $makeUrl);

            if (!count($parent_cat) > 0) {
                $matchedText = $this->getMatchedText('CV'.'#'.$adArray['Manufacturer']);
                if ($matchedText != '') {
                    $TargetCat= $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($matchedText, $this->container);
                    if (isset($TargetCat['id']) && $TargetCat['name']) {
                        $parent_cat[] = array('id' => $TargetCat['id'], 'name' => $TargetCat['name']);
                    }
                }
            }

            if (count($parent_cat) > 0) {
                $parent_cat_id = $parent_cat[0]['id'];
                $parent_name   = $parent_cat[0]['name'];
                $this->setModelId($adArray, $parent_cat_id, $parent_name, 'CV');
            }
        }

        if (isset($adArray['Details']['BodyType']) && $adArray['Details']['BodyType'] != '') {
            $this->advert['motors']['body_type_id'] = $this->getBodyTypeId($adArray['Details']['BodyType'], 76);
        }

        if (isset($adArray['Details']['Colour']) && $adArray['Details']['Colour'] != '') {
            $this->advert['motors']['colour_id'] = $this->getColorId($adArray['Details']['Colour'], 75);

            if (!$this->advert['motors']['colour_id']) {
                $this->advert['motors']['meta_data']['colour'] = $adArray['Details']['Colour'];
            }
        }

        if (isset($adArray['Details']['FuelType']) && $adArray['Details']['FuelType'] != '') {
            $this->advert['motors']['fuel_type_id'] = $this->getFuelTypeId($adArray['Details']['FuelType'], 78);
        }

        if (isset($adArray['Details']['Transmission']) && $adArray['Details']['Transmission'] != '') {
            $this->advert['motors']['transmission_id'] = $this->getTransmissionId($adArray['Details']['Transmission'], 79);
        }

        if (isset($adArray['Details']['Mileage']) && $adArray['Details']['Mileage'] != '') {
            $this->advert['motors']['meta_data']['mileage'] = $adArray['Details']['Mileage'] ;
        }

        if (isset($adArray['Details']['Keywords']) && $adArray['Details']['Keywords'] != '') {
            $keywords = explode(',', $adArray['Details']['Keywords']);
            $f = array();
            foreach ($keywords as $keyword) {
                $id = $this->getFeaturesId(trim($keyword), 90);
                if ($id) {
                    $f[] = $id;
                }
            }
            $this->advert['motors']['meta_data']['features_id'] = implode(',', $f);
        }

        if (isset($adArray['Details']['EngineSize']) && $adArray['Details']['EngineSize'] != '') {
            $this->advert['motors']['meta_data']['engine_size'] = $adArray['Details']['EngineSize'] ;
        }

        if (isset($adArray['Details']['Year']) && $adArray['Details']['Year'] != '') {
            $this->advert['motors']['meta_data']['reg_year'] = $adArray['Details']['Year'] ;
        }

        if (isset($adArray['Details']['NumberOfDoors']) && $adArray['Details']['NumberOfDoors'] != '') {
            $this->advert['motors']['meta_data']['no_of_doors'] = $adArray['Details']['NumberOfDoors'] ;
        }

        if (!isset($this->advert['category_id'])) {
            $this->setRejectAd();
            $this->rejectedReason = 'Category missing: '.$adArray['Details']['VehicleType'];
        }
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

        if (isset($this->advert['motors']['body_type_id'])) {
            $ad_motors->setBodyTypeId($this->advert['motors']['body_type_id']);
        } else {
            $ad_motors->setBodyTypeId(null);
        }

        if (isset($this->advert['motors']['fuel_type_id'])) {
            $ad_motors->setFuelTypeId($this->advert['motors']['fuel_type_id']);
        } else {
            $ad_motors->setFuelTypeId(null);
        }

        if (isset($this->advert['motors']['colour_id'])) {
            $ad_motors->setColourId($this->advert['motors']['colour_id']);
        } else {
            $ad_motors->setColourId(null);
        }

        if (isset($this->advert['motors']['make_id'])) {
            $ad_motors->setMakeId($this->advert['motors']['make_id']);
        } else {
            $ad_motors->setMakeId(null);
        }

        if (isset($this->advert['motors']['model_id'])) {
        	$ad_motors->setModelId($this->advert['motors']['model_id']);
        } else {
        	$ad_motors->setModelId(null);
        }

        if (isset($this->advert['motors']['transmission_id'])) {
            $ad_motors->setTransmissionId($this->advert['motors']['transmission_id']);
        } else {
            $ad_motors->setTransmissionId(null);
        }

        if (isset($this->advert['motors']['meta_data'])) {
            $ad_motors->setMetaData(serialize($this->advert['motors']['meta_data']));
        } else {
            $ad_motors->setMetaData(null);
        }

        $this->em->persist($ad_motors);
    }


    /**
     * get body type id
     *
     * @param string  $string
     * @param integer $dimension_id
     * @param integer $numberOfDoors
     *
     * @return integer or null
     */
    private function getBodyTypeId($string, $dimension_id, $numberOfDoors=null)
    {
        $string = $this->getBodyTypeText($string, $numberOfDoors);
        return $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($dimension_id, trim($string), $this->container);
    }

    /**
     * get make id
     *
     * @param string    $string
     * @param integer $dimension_id
     *
     * @return integer or null
     */
    private function getMotorMakeId($string, $dimension_id)
    {
        return $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($dimension_id, trim($string), $this->container);
    }

    /**
     * get model id
     *
     * @param unknown $string
     * @param unknown $make_id
     */
    public function getModelId($string, $make_id)
    {
        $entity = $this->em->getRepository('FaEntityBundle:Entity')->findOneBy(array('name' => $string, 'parent_id' => $make_id));

        if ($entity) {
            return $entity->getId();
        }
    }

    /**
     * get features id
     *
     * @param string  $string
     * @param integer $dimension_id
     *
     * @return integer
     */
    private function getFeaturesId($string, $dimension_id)
    {
        return $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($dimension_id, trim($string), $this->container);
    }

    /**
     * get color id
     *
     * @param string  $string
     * @param integer $dimension_id
     *
     * @return integer or null
     */
    private function getColorId($string, $dimension_id)
    {
        return $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($dimension_id, trim($string), $this->container);
    }

    /**
     * get fuel type
     *
     * @param string  $string fuel name
     * @param integer $dimension_id
     *
     * @return integer or null
     */
    public function getFuelTypeId($string, $dimension_id)
    {
        if (strtolower($string) == 'turbo diesel') {
            $string = 'diesel';
        }

        $string = trim($string);
        $id = $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($dimension_id, $string, $this->container);

        if ($id) {
            return $id;
        }
    }

    /**
     * get transmission id
     *
     * @param string $string
     *
     * @return integer or null
     */
    public function getTransmissionId($string, $dimension_id)
    {
        return $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($dimension_id, $string, $this->container);
    }

    /**
     * Get category id.
     *
     * @param string $string Category.
     */
    public function getCategoryId($cat_name = null)
    {
        return $this->container->get('fa.entity.cache.manager')->getEntityIdByName('FaEntityBundle:Category', $cat_name);
    }


    /**
     * Get category id.
     *
     * @param string $bodyTypeText  Body type text received from source.
     * @param string $numberOfDoors Number of doors received from source.
     */
    public function getBodyTypeText($bodyTypeText, $numberOfDoors = null)
    {
        $bodyTypeTextLower = strtolower($bodyTypeText);
        if ($bodyTypeTextLower == 'hatchback' && $numberOfDoors && $numberOfDoors == 3) {
            return '3 Door Hatchback';
        } elseif ($bodyTypeTextLower == 'hatchback' && $numberOfDoors && $numberOfDoors == 5) {
            return '5 Door Hatchback';
        } elseif ($bodyTypeTextLower == 'saloon' && $numberOfDoors && $numberOfDoors == 2) {
            return '2 Door Saloon';
        } elseif ($bodyTypeTextLower == 'saloon' && $numberOfDoors && $numberOfDoors == 4) {
            return '4 Door Saloon';
        } elseif ($bodyTypeTextLower == 'mpv' || $bodyTypeTextLower == 'six seater mpv' || $bodyTypeTextLower == 'seven seater mpv') {
            return 'Mpv';
        } elseif ($bodyTypeTextLower == 'cabriolet' || $bodyTypeTextLower == 'roadster' || $bodyTypeTextLower == 'spider' || $bodyTypeTextLower == 'spyder') {
            return 'Convertible';
        } elseif ($bodyTypeTextLower == 'tourer estate' || $bodyTypeTextLower == '7 seater estate' || $bodyTypeTextLower == '6 seater estate') {
            return 'Estate';
        } else {
            return $bodyTypeText;
        }
    }
}
