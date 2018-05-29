<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\lib;

use XMLWriter;
use Fa\Bundle\AdFeedBundle\lib\Export;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;
use Fa\Bundle\AdBundle\Solr\AdMotorsSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Solr\AdAnimalsSolrFieldMapping;
use Fa\Bundle\AdBundle\Solr\AdForSaleSolrFieldMapping;
use Fa\Bundle\AdBundle\Solr\AdPropertySolrFieldMapping;

/**
 * EzyAds feed export class
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class EzyAds extends Export
{

    const CAR_FILE = 'EzAds.xml';
    const ONLY_CAR_FILE = 'EzAdsCars.xml';
    const GARDENING_FILE = 'EzAds_Gardening.xml';
    const ITEMSFORSALE_FILE = 'EzAds_ItemsForSale.xml';
    const PETS_FILE = 'EzAdsPets.xml';
    const PORPERTY_FILE = 'EzAds_Properties.xml';

    /**
     * init feed file
     */
    public function initFile($category)
    {
        if ($category == 'Cars') {
            $this->removeFile(self::CAR_FILE, 'xml');
        } elseif ($category == 'OnlyCars') {
            $this->removeFile(self::ONLY_CAR_FILE, 'xml');
        } elseif ($category == 'Gardening') {
            $this->removeFile(self::GARDENING_FILE, 'xml');
        } elseif ($category == 'Forsale') {
            $this->removeFile(self::ITEMSFORSALE_FILE, 'xml');
        } elseif ($category == 'Pets') {
            $this->removeFile(self::PETS_FILE, 'xml');
        } elseif ($category == 'Property') {
            $this->removeFile(self::PORPERTY_FILE, 'xml');
        }
    }

    public function fixFile($category)
    {
        if ($category == 'Cars') {
            $file = self::CAR_FILE;
        } elseif ($category == 'OnlyCars') {
            $file = self::ONLY_CAR_FILE;
        } elseif ($category == 'Gardening') {
            $file = self::GARDENING_FILE;
        } elseif ($category == 'Forsale') {
            $file = self::ITEMSFORSALE_FILE;
        } elseif ($category == 'Property') {
            $file = self::PORPERTY_FILE;
        } elseif ($category == 'Pets') {
            $file = self::PETS_FILE;
        }

        $start_text = '<?xml version="1.0" encoding="utf-8"?>\n<trovit>';
        $command = "sed -i '1s/^/<ezyads>/' ".$this->rootDir().'/export/xml/'.$file;
        passthru($command, $returnVar);
        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $command = "sed -i '1s/^/<?xml version=\"1.0\" encoding=\"utf-8\"?>/' ".$this->rootDir().'/export/xml/'.$file;

        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $command = "sed -i '\$a</ezyads>' ".$this->rootDir().'/export/xml/'.$file;
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $this->uploadToFTP($this->rootDir().'/export/xml/'.$file, $file);
    }

    public function uploadToFTP($source, $target)
    {
        // set up basic connection
        $conn_id = ftp_connect($this->container->getParameter('fa.ezyads.ftp'));
        // login with username and password
        $login_result = ftp_login($conn_id, 'Ezads_ftp', 'Adsez2015');
        ftp_pasv($conn_id, true);

        // upload a file
        if (ftp_put($conn_id, $target, $source, FTP_ASCII)) {
            echo "successfully uploaded $source\n";
        } else {
            echo "There was a problem while uploading $source\n";
        }

        // close the connection
        ftp_close($conn_id);
    }

    /**
     * write product data
     *
     * @param solr array $ads
     */
    public function writeProductData($ads, $category = null)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $adMotorRepository = $this->em->getRepository('FaAdBundle:Ad');

        foreach ($ads as $ad) {

            $phone_number = $ad[AdSolrFieldMapping::PRIVACY_NUMBER] !== '' ? $ad[AdSolrFieldMapping::PRIVACY_NUMBER] : $ad[AdSolrFieldMapping::USER_PHONE_NUMBER];
            if ($phone_number == '') {
                continue;
            }

            $xmlWriter->startElement('ad');
            $this->addCommonData($xmlWriter, $ad);

            // price
            if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
                $xmlWriter->startElement('price');
                $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
                $xmlWriter->endElement();
            }

            if (isset($ad[AdSolrFieldMapping::PATH][0])) {
                $xmlWriter->startElement('image_url');
                if (isset($ad[AdSolrFieldMapping::AWS][0]) && $ad[AdSolrFieldMapping::AWS][0] == 1) {
                    $imageThumbUrl = CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS][0]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
                } else {
                    $imageThumbUrl = 'http:'.CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS][0]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME][0])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
                }
                $xmlWriter->writeCdata($imageThumbUrl);
                $xmlWriter->endElement();
            }

            //condition
            $new_or_used = $ad[AdSolrFieldMapping::IS_NEW] == 1 ? 'New' : 'Used';
            $xmlWriter->startElement('condition');
            $xmlWriter->writeCdata($new_or_used);
            $xmlWriter->endElement();

            // ad_type
            $ad_type = null;
            if (isset($ad[AdSolrFieldMapping::TYPE_ID])) {
                $ad_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdSolrFieldMapping::TYPE_ID]);
            }

            if ($ad_type) {
                $xmlWriter->startElement('adtype');
                $xmlWriter->writeCdata($ad_type);
                $xmlWriter->endElement();
            }


            $xmlWriter->endElement();
        }

        if ($category == 'Gardening') {
            $this->writeData($xmlWriter->flush(true), self::GARDENING_FILE, FILE_APPEND, 'xml');
        } else {
            $this->writeData($xmlWriter->flush(true), self::ITEMSFORSALE_FILE, FILE_APPEND, 'xml');
        }
    }

    /**
     * write product data
     *
     * @param solr array $ads
     */
    public function writePetsData($ads)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        foreach ($ads as $ad) {

            $phone_number = $ad[AdSolrFieldMapping::PRIVACY_NUMBER] !== '' ? $ad[AdSolrFieldMapping::PRIVACY_NUMBER] : $ad[AdSolrFieldMapping::USER_PHONE_NUMBER];
            if ($phone_number == '') {
                continue;
            }

            $xmlWriter->startElement('ad');
            $this->addCommonData($xmlWriter, $ad);

            // price
            if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
                $xmlWriter->startElement('price');
                $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
                $xmlWriter->endElement();
            }

            if (isset($ad[AdSolrFieldMapping::PATH][0])) {
                $xmlWriter->startElement('image_url');
                if (isset($ad[AdSolrFieldMapping::AWS][0]) && $ad[AdSolrFieldMapping::AWS][0] == 1) {
                    $imageThumbUrl = CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS][0]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
                } else {
                    $imageThumbUrl = 'http:'.CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS][0]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME][0])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
                }
                $xmlWriter->writeCdata($imageThumbUrl);
                $xmlWriter->endElement();
            }
            // age
            $age = null;
            if (isset($ad[AdAnimalsSolrFieldMapping::AGE_ID])) {
               $age = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdAnimalsSolrFieldMapping::AGE_ID]);
            }

            if ($age) {
                $xmlWriter->startElement('age');
                $xmlWriter->writeCdata($age);
                $xmlWriter->endElement();
            }

            // colour
            $colour = null;
            if (isset($ad[AdAnimalsSolrFieldMapping::COLOUR_ID])) {
               $colour = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdAnimalsSolrFieldMapping::COLOUR_ID]);
            }

            if ($colour) {
                $xmlWriter->startElement('colour');
                $xmlWriter->writeCdata($colour);
                $xmlWriter->endElement();
            }

            $gender     = array();
            $gender_ids = null;
            if (isset($ad[AdAnimalsSolrFieldMapping::GENDER_ID])) {
                $gender_ids = $ad[AdAnimalsSolrFieldMapping::GENDER_ID];
                foreach ($gender_ids as $gender_id) {
                    $gender[] = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $gender_id);
                }
            }

            if ($gender) {
                $xmlWriter->startElement('gender');
                $xmlWriter->writeCdata(implode(', ', $gender));
                $xmlWriter->endElement();
            }

            // ad_type
            $ad_type = null;
            if (isset($ad[AdSolrFieldMapping::TYPE_ID])) {
              $ad_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdSolrFieldMapping::TYPE_ID]);
            }

            if ($ad_type) {
              $xmlWriter->startElement('adtype');
              $xmlWriter->writeCdata($ad_type);
              $xmlWriter->endElement();
            }

            $xmlWriter->endElement();
        }

        $this->writeData($xmlWriter->flush(true), self::PETS_FILE, FILE_APPEND, 'xml');
    }

    public function writePropertyData($ads)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        foreach ($ads as $ad) {

            $phone_number = $ad[AdSolrFieldMapping::PRIVACY_NUMBER] !== '' ? $ad[AdSolrFieldMapping::PRIVACY_NUMBER] : $ad[AdSolrFieldMapping::USER_PHONE_NUMBER];
            if ($phone_number == '') {
                continue;
            }

            $xmlWriter->startElement('ad');
            $this->addCommonData($xmlWriter, $ad);

            // price
            if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
                $xmlWriter->startElement('price');
                $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
                $xmlWriter->endElement();
            }

            if (isset($ad[AdSolrFieldMapping::PATH][0])) {
                $xmlWriter->startElement('image_url');
                if (isset($ad[AdSolrFieldMapping::AWS][0]) && $ad[AdSolrFieldMapping::AWS][0] == 1) {
                    $imageThumbUrl = CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS][0]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
                } else {
                    $imageThumbUrl = 'http:'.CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS][0]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME][0])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
                }
                $xmlWriter->writeCdata($imageThumbUrl);
                $xmlWriter->endElement();
            }

            // bedrooms
            $rooms = null;
            if (isset($ad[AdPropertySolrFieldMapping::NUMBER_OF_BEDROOMS_ID])) {
                $rooms = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdPropertySolrFieldMapping::NUMBER_OF_BEDROOMS_ID]);
            }

            if ($rooms) {
                $xmlWriter->startElement('bedrooms');
                $xmlWriter->writeCdata($rooms);
                $xmlWriter->endElement();
            }

            //bathrooms
            $bathrooms = null;
            if (isset($ad[AdPropertySolrFieldMapping::NUMBER_OF_BATHROOMS_ID])) {
                $bathrooms = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdPropertySolrFieldMapping::NUMBER_OF_BATHROOMS_ID]);
            }

            if ($bathrooms) {
                $xmlWriter->startElement('bathrooms');
                $xmlWriter->writeCdata($bathrooms);
                $xmlWriter->endElement();
            }

            // is_furnished
            $is_furnished = null;
            if (isset($ad[AdPropertySolrFieldMapping::FURNISHING_ID])) {
                $is_furnished = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdPropertySolrFieldMapping::FURNISHING_ID]);
            }

            if ($is_furnished) {
                $xmlWriter->startElement('furnishings');
                $xmlWriter->writeCdata($is_furnished);
                $xmlWriter->endElement();
            }

            //features
            $features_ids = null;
            $features = array();
            if (isset($ad[AdPropertySolrFieldMapping::AMENITIES_ID])) {
                $features_ids = $ad[AdPropertySolrFieldMapping::AMENITIES_ID];
                foreach ($features_ids as $features_id) {
                    $features[] = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $features_id);
                }
            }

            if (count($features) > 0) {
                $xmlWriter->startElement('amenities');
                $xmlWriter->writeCdata(implode(', ', $features));
                $xmlWriter->endElement();
            }

            $cat_array = array();
            $cat_array[] = $ad[AdSolrFieldMapping::CATEGORY_ID];


            $property_type = null;
            $rent_or_buy = null;
            for ($i=1; $i <=5; $i++) {
                if (isset($ad['a_parent_category_lvl_'.$i.'_id_i'])) {
                    $cat_array[] = $ad['a_parent_category_lvl_'.$i.'_id_i'];

                    if ($i == 2) {
                        $rent_or_buy = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $ad['a_parent_category_lvl_'.$i.'_id_i']);
                    }

                    if ($i == 3) {
                       $property_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $ad['a_parent_category_lvl_'.$i.'_id_i']);
                    }
                }
            }

            if ($rent_or_buy) {
                $xmlWriter->startElement('rent_or_buy');
                $xmlWriter->writeCdata($rent_or_buy);
                $xmlWriter->endElement();
            }

            if ($property_type) {
                $xmlWriter->startElement('property_type');
                $xmlWriter->writeCdata($property_type);
                $xmlWriter->endElement();
            }

            // ad_type
            $ad_type = null;
            if (isset($ad[AdSolrFieldMapping::TYPE_ID])) {
                $ad_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdSolrFieldMapping::TYPE_ID]);
            }

            if ($ad_type) {
                $xmlWriter->startElement('adtype');
                $xmlWriter->writeCdata($ad_type);
                $xmlWriter->endElement();
            }

            $xmlWriter->endElement();
        }

        $this->writeData($xmlWriter->flush(true), self::PORPERTY_FILE, FILE_APPEND, 'xml');
    }

    /**
     * get trovit property type
     *
     * @return multitype:multitype:number
     */
    protected function getTrovitPropertyTypes()
    {
        $cat = array();
        $cat['For Rent']        = array(680,681,682,683,684,685,695);
        $cat['For Sale']         = array(699,700,701,702,703);
        $cat['Roommate']         = array(718,720,719);
        $cat['Office For Rent']  = array(686);
        $cat['Parking for Rent'] = array(685,684);
        $cat['Land for sale']    = array(710);
        return $cat;
    }


    /**
     * write vehicles data
     *
     * @param solr array $ads
     */
    public function writeVehiclesData($ads, $fileName)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $adMotorRepository = $this->em->getRepository('FaAdBundle:AdMotors');

        foreach ($ads as $ad) {
            $phone_number = $ad[AdSolrFieldMapping::PRIVACY_NUMBER] !== '' ? $ad[AdSolrFieldMapping::PRIVACY_NUMBER] : $ad[AdSolrFieldMapping::USER_PHONE_NUMBER];
            if ($phone_number == '') {
                continue;
            }

            $this->writeCarData($ad, $xmlWriter);
        }

        $this->writeData($xmlWriter->flush(true), $fileName, FILE_APPEND, 'xml');
    }

    /**
     * write car data
     * @param solr object $ad
     *
     */
    public function writeCarData($ad, $xmlWriter)
    {
        $metaData = unserialize($ad[AdMotorsSolrFieldMapping::META_DATA]);

        // do not include ads which fails condition
        if (count($ad[AdSolrFieldMapping::PATH]) < 1 || !isset($metaData['mileage']) || !isset($metaData['engine_size'])) {
            return 0;
        }

        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        $xmlWriter->startElement('ad');

        $this->addCommonData($xmlWriter, $ad);

        //mileage
        $mileage = null;
        if (isset($metaData['mileage'])) {
            $mileage = $metaData['mileage'];
        }

        if ($mileage) {
            $xmlWriter->startElement('mileage');
            $xmlWriter->writeCdata($mileage);
            $xmlWriter->endElement();
        }

        //make
        $make = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $ad[AdMotorsSolrFieldMapping::CATEGORY_MAKE_ID]);

        if ($make != '') {
            $xmlWriter->startElement('make');
            $xmlWriter->writeCdata($make);
            $xmlWriter->endElement();
        }

        //model
        $category = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $ad[AdMotorsSolrFieldMapping::CATEGORY_ID]);
        if ($category != '') {
            $xmlWriter->startElement('model');
            $xmlWriter->writeCdata($category);
            $xmlWriter->endElement();
        }

        // price
        if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
            $xmlWriter->startElement('price');
            $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
            $xmlWriter->endElement();
        }

        //colour
        $color = null;
        $color = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::COLOUR_ID]);
        if ($color == '') {
            $color = isset($metaData['colour']) ? $metaData['colour'] : null;
        }

        if ($color) {
            $xmlWriter->startElement('colour');
            $xmlWriter->writeCdata($color);
            $xmlWriter->endElement();
        }

        //body_type
        $body_type = null;
        $body_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::BODY_TYPE_ID]);
        if ($body_type) {
            $xmlWriter->startElement('body_type');
            $xmlWriter->writeCdata($body_type);
            $xmlWriter->endElement();
        }

        //fuel_type
        $fuel_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::FUEL_TYPE_ID]);

        if ($fuel_type) {
            $xmlWriter->startElement('fuel_type');
            $xmlWriter->writeCdata($fuel_type);
            $xmlWriter->endElement();
        }

        //condition
        $new_or_used = $ad[AdSolrFieldMapping::IS_NEW] == 1 ? 'New' : 'Used';
        $xmlWriter->startElement('condition');
        $xmlWriter->writeCdata($new_or_used);
        $xmlWriter->endElement();



        //engine_size
        $engine_size = null;
        if (isset($metaData['engine_size'])) {
            $engine_size = $metaData['engine_size'];
        }

        if ($engine_size) {
            $xmlWriter->startElement('engine_size');
            $xmlWriter->writeCdata($engine_size);
            $xmlWriter->endElement();
        }

        //registration_year
        $year = null;
        if (isset($metaData['reg_year'])) {
            $year = str_replace('-', ' ', ucfirst($ad[AdMotorsSolrFieldMapping::REG_YEAR]));
        }

        if ($year) {
            $xmlWriter->startElement('registration_year');
            $xmlWriter->writeCdata($year);
            $xmlWriter->endElement();
        }

        //doors
        $no_of_doors = null;
        if (isset($metaData['no_of_doors'])) {
            $no_of_doors= $metaData['no_of_doors'];
        }

        if ($no_of_doors) {
            $xmlWriter->startElement('number_of_doors');
            $xmlWriter->writeCdata($no_of_doors);
            $xmlWriter->endElement();
        }

        //transmission
        $transmission = null;
        if (isset($ad[AdMotorsSolrFieldMapping::TRANSMISSION_ID])) {
            $transmission = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::TRANSMISSION_ID]);
        }

        if ($transmission) {
            $xmlWriter->startElement('transmission');
            $xmlWriter->writeCdata($transmission);
            $xmlWriter->endElement();
        }

        //features
        $features_ids = null;
        $features = array();
        if (isset($metaData['features_id'])) {
            $features_ids = explode(',', $metaData['features_id']);
            foreach ($features_ids as $features_id) {
                $features[] = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $features_id);
            }
        }

        if (count($features) > 0) {
            $xmlWriter->startElement('features');
            $xmlWriter->writeCdata(implode(', ', $features));
            $xmlWriter->endElement();
        }


        //service_history
        $service_history = null;
        if (isset($metaData['service_history_id'])) {
            $features[] = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $metaData['service_history_id']);
        }

        if ($service_history) {
            $xmlWriter->startElement('service_history');
            $xmlWriter->writeCdata($service_history);
            $xmlWriter->endElement();
        }


        //doors
        $no_of_doors = null;
        if (isset($metaData['no_of_doors'])) {
            $no_of_doors= $metaData['no_of_doors'];
        }

        if ($no_of_doors) {
            $xmlWriter->startElement('doors');
            $xmlWriter->writeCdata($no_of_doors);
            $xmlWriter->endElement();
        }

        //mot
        $mot_expiry = null;
        if (isset($metaData['mot_expiry_month']) && isset($metaData['mot_expiry_year'])) {
            $mot_expiry = $metaData['mot_expiry_month'].'/'.$metaData['mot_expiry_year'];
        } elseif (isset($metaData['mot_expiry_year'])) {
            $mot_expiry = $metaData['mot_expiry_year'];
        }

        if ($mot_expiry) {
            $xmlWriter->startElement('mot');
            $xmlWriter->writeCdata($mot_expiry);
            $xmlWriter->endElement();
        }

        //tax
        $tax = null;
        if (isset($metaData['road_tax_expiry_month']) && isset($metaData['road_tax_expiry_year'])) {
            $tax = $metaData['road_tax_expiry_month'].'/'.$metaData['road_tax_expiry_year'];
        } elseif (isset($metaData['road_tax_expiry_year'])) {
            $tax = $metaData['road_tax_expiry_year'];
        }

        if ($tax) {
            $xmlWriter->startElement('tax');
            $xmlWriter->writeCdata($tax);
            $xmlWriter->endElement();
        }

        if (isset($ad[AdSolrFieldMapping::PATH][0])) {
            $xmlWriter->startElement('image_url');
            if (isset($ad[AdSolrFieldMapping::AWS][0]) && $ad[AdSolrFieldMapping::AWS][0] == 1) {
                $imageThumbUrl = CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS][0]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
            } else {
                $imageThumbUrl = 'http:'.CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS][0]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME][0])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
            }
            $xmlWriter->writeCdata($imageThumbUrl);
            $xmlWriter->endElement();
        }

        $xmlWriter->endElement();
    }
    /**
     *
     * @param \XMLWriter  $xmlWriter xml writer
     * @param solr object $ad        solr ad object
     */
    private function addCommonData($xmlWriter, $ad)
    {
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        //id
        $xmlWriter->startElement('ad_ref');
        $xmlWriter->writeCdata($ad[AdSolrFieldMapping::ID]);
        $xmlWriter->endElement();

        //time_modified
        $xmlWriter->startElement('time_modified');
        $xmlWriter->writeCdata(CommonManager::formatDate($ad[AdSolrFieldMapping::UPDATED_AT], $this->container, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT));
        $xmlWriter->endElement();

        //publications
        $printEditions = $this->em->getRepository('FaAdBundle:AdPrint')->getPrintEditionEntries($ad[AdSolrFieldMapping::ID]);
        $xmlWriter->startElement('publications');

        $editions = array();
        foreach ($printEditions as $printEdition) {
            if (strlen($printEdition['code']) == 1) {
                $editions[] = $printEdition['code'].' ';
            } else {
                $editions[] = $printEdition['code'];
            }
        }
        $xmlWriter->writeCdata(implode(',', $editions));

        $xmlWriter->endElement();

        //private_or_trade
        $privateOrTrade = $ad[AdSolrFieldMapping::IS_TRADE_AD] == 1 ? 'Trade' : 'Private';
        $xmlWriter->startElement('private_or_trade');
        $xmlWriter->writeCdata($privateOrTrade);
        $xmlWriter->endElement();

        //title
        $xmlWriter->startElement('title');
        $xmlWriter->writeCdata($ad[AdSolrFieldMapping::TITLE]);
        $xmlWriter->endElement();

        //town
        $city = null;
        if (isset($ad[AdSolrFieldMapping::TOWN_ID][0])) {
            $city = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $ad[AdSolrFieldMapping::TOWN_ID][0]);
        }

        if ($city) {
            $xmlWriter->startElement('town');
            $xmlWriter->writeCdata($city);
            $xmlWriter->endElement();
        }

        // ad_text
        $xmlWriter->startElement('ad_text');

        $ad_text = $ad[AdSolrFieldMapping::DESCRIPTION];
        if (isset($ad[AdSolrFieldMapping::PRICE])) {
            $ad_text = $ad_text.' '.CommonManager::formatCurrency($ad[AdSolrFieldMapping::PRICE], $this->container).',';
        }

        if ($city) {
            $ad_text = $ad_text.' '.$city;
        }

        $ad_text = CommonManager::stripTagsContent(htmlspecialchars_decode($ad_text), '<em><strong><b><i><u><p><ul><li><ol><div><span><a><br>');
        $xmlWriter->writeCdata($ad_text);
        $xmlWriter->endElement();

        $phone_number = $ad[AdSolrFieldMapping::PRIVACY_NUMBER] !== '' ? $ad[AdSolrFieldMapping::PRIVACY_NUMBER] : $ad[AdSolrFieldMapping::USER_PHONE_NUMBER];
        $xmlWriter->startElement('phone_number');
        $xmlWriter->writeCdata($phone_number);
        $xmlWriter->endElement();

        //title
        $xmlWriter->startElement('numberOfImages');
        $xmlWriter->writeCdata($ad[AdSolrFieldMapping::TOTAL_IMAGES]);
        $xmlWriter->endElement();
    }


    /**
     * Add date data
     *
     * @param \XMLWriter  $xmlWriter xml writer
     * @param solr object $ad        solr ad object
     *
     */
    private function addDateData($xmlWriter, $ad)
    {
        // expiration_date
        $xmlWriter->startElement('expiration_date');
        $xmlWriter->writeCdata(CommonManager::formatDate($ad[AdSolrFieldMapping::EXPIRES_AT], $this->container, \IntlDateFormatter::SHORT));
        $xmlWriter->endElement();

        //date
        $xmlWriter->startElement('date');
        $xmlWriter->writeCdata(CommonManager::formatDate($ad[AdSolrFieldMapping::PUBLISHED_AT], $this->container, \IntlDateFormatter::SHORT));
        $xmlWriter->endElement();
    }
    /**
     * add location data
     *
     * @param \XMLWriter  $xmlWriter xml writer
     * @param solr object $ad        solr ad object
     * @param boolean     $lat_long  with lat long or not
     */
    private function addLocationData($xmlWriter, $ad, $lat_long = false)
    {
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        //city
        $city = null;
        if (isset($ad[AdSolrFieldMapping::TOWN_ID][0])) {
            $city = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $ad[AdSolrFieldMapping::TOWN_ID][0]);
        }

        if ($city) {
            $xmlWriter->startElement('city');
            $xmlWriter->writeCdata($city);
            $xmlWriter->endElement();
        }


        //city_area
        $locality = null;
        if (isset($ad[AdSolrFieldMapping::LOCALITY_ID][0])) {
            $locality = $entityCacheManager->getEntityNameById('FaEntityBundle:Locality', $ad[AdSolrFieldMapping::LOCALITY_ID][0]);
        }

        if ($locality) {
            $xmlWriter->startElement('city_area');
            $xmlWriter->writeCdata($locality);
            $xmlWriter->endElement();
        }


        //postcode
        $postcode = isset($ad[AdSolrFieldMapping::POSTCODE][0]) ? $ad[AdSolrFieldMapping::POSTCODE][0] : null;


        if ($postcode) {
            $xmlWriter->startElement('postcode');
            $xmlWriter->writeCdata($postcode);
            $xmlWriter->endElement();
        }


        //region
        $county = null;
        if (isset($ad[AdSolrFieldMapping::DOMICILE_ID][0])) {
            $county = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $ad[AdSolrFieldMapping::DOMICILE_ID][0]);
        }

        if ($county) {
            $xmlWriter->startElement('region');
            $xmlWriter->writeCdata($county);
            $xmlWriter->endElement();
        }

        if ($lat_long) {
            $lat  = isset($ad[AdSolrFieldMapping::LATITUDE][0]) ? $ad[AdSolrFieldMapping::LATITUDE][0] : null;
            $long = isset($ad[AdSolrFieldMapping::LONGITUDE][0]) ? $ad[AdSolrFieldMapping::LONGITUDE][0] : null;

            if ($lat && $long) {
                $xmlWriter->startElement('latitude');
                $xmlWriter->writeCdata($lat);
                $xmlWriter->endElement();

                $xmlWriter->startElement('longitude');
                $xmlWriter->writeCdata($long);
                $xmlWriter->endElement();
            }
        }
    }
}
