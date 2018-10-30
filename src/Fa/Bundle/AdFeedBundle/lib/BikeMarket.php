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
use Fa\Bundle\AdBundle\Solr\AdJobsSolrFieldMapping;
use Fa\Bundle\AdBundle\Solr\AdForSaleSolrFieldMapping;
use Fa\Bundle\AdBundle\Solr\AdPropertySolrFieldMapping;

/**
 * BikeMarket feed export class
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class BikeMarket extends Export
{
    /**
     * init feed file
     */
    public function initFile()
    {
        $this->removeFile('BikeMarketXML.xml', 'xml');
    }

    public function fixFile()
    {
        $command = "sed -i '1s/^/<?xml version=\"1.0\" encoding=\"utf-8\"?>/' ".$this->rootDir().'/export/xml/BikeMarketXML.xml';
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }
    }

    /**
     * write product data
     *
     * @param solr array $ads
     */
    public function writeAdsData($ads)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        $xmlWriter->startElement('ads');

        foreach ($ads as $ad) {
            $adMetaData = array();
            if (isset($ad[AdMotorsSolrFieldMapping::META_DATA]) && $ad[AdMotorsSolrFieldMapping::META_DATA]) {
                $adMetaData = unserialize($ad[AdMotorsSolrFieldMapping::META_DATA]);
            }

            // Start ad node
            $xmlWriter->startElement('ad');

            // Ad url
            $adUrl= $this->container->get('fa_ad.manager.ad_routing')->getDetailUrl($ad);
            $xmlWriter->startElement('ad_url');
            $xmlWriter->writeCdata($adUrl);
            $xmlWriter->endElement();

            // Ad id
            $xmlWriter->startElement('ad_id');
            $xmlWriter->writeCdata($ad[AdSolrFieldMapping::ID]);
            $xmlWriter->endElement();

            // Ad headline
            $xmlWriter->startElement('title');
            $xmlWriter->writeCdata($ad[AdSolrFieldMapping::TITLE]);
            $xmlWriter->endElement();

            // Ad description
            $xmlWriter->startElement('description');

            $description = $ad[AdSolrFieldMapping::DESCRIPTION];
            $remove_word = array('Sex', 'Cock', 'smeg', 'mobile', 'office', 'steal', 'dyke');
            $description = str_replace($remove_word, '', $description);
            $description = CommonManager::stripTagsContent(htmlspecialchars_decode($description), '<em><strong><b><i><u><p><ul><li><ol><div><span><a><br>');
            $xmlWriter->writeCdata($description);
            $xmlWriter->endElement();

            //private_or_trade
            $privateOrTrade = $ad[AdSolrFieldMapping::IS_TRADE_AD] == 1 ? 'Trade' : 'Private';
            $xmlWriter->startElement('private_or_trade');
            $xmlWriter->writeCdata($privateOrTrade);
            $xmlWriter->endElement();

            // Ad first image url
            $this->addImageData($xmlWriter, $ad);

            // Make
            $make = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::MAKE_ID]);
            $xmlWriter->startElement('make');
            $xmlWriter->writeCdata($make);
            $xmlWriter->endElement();

            // Model
            $model = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::MODEL_ID]);
            $xmlWriter->startElement('model');
            $xmlWriter->writeCdata($model);
            $xmlWriter->endElement();

            // Ad price
            $xmlWriter->startElement('price');
            $xmlWriter->writeCdata(isset($ad[AdSolrFieldMapping::PRICE]) ? $ad[AdSolrFieldMapping::PRICE] : null);
            $xmlWriter->endElement();

            // Reg year
            $xmlWriter->startElement('registration_year');
            $xmlWriter->writeCdata((isset($ad[AdMotorsSolrFieldMapping::REG_YEAR]) ? $ad[AdMotorsSolrFieldMapping::REG_YEAR] : null));
            $xmlWriter->endElement();

            // Engine size
            $engineSize = null;
            if (isset($ad[AdMotorsSolrFieldMapping::ENGINE_SIZE]) && $ad[AdMotorsSolrFieldMapping::ENGINE_SIZE]) {
                $engineSize = $ad[AdMotorsSolrFieldMapping::ENGINE_SIZE].' '.$this->em->getRepository('FaAdBundle:AdMotors')->getUnitByField('engine_size');
            }

            $xmlWriter->startElement('engine_size');
            $xmlWriter->writeCdata($engineSize);
            $xmlWriter->endElement();

            // Mileage
            $mileage = null;
            if (isset($ad[AdMotorsSolrFieldMapping::MILEAGE]) && $ad[AdMotorsSolrFieldMapping::MILEAGE]) {
                $mileage = $ad[AdMotorsSolrFieldMapping::MILEAGE];
            }

            $xmlWriter->startElement('mileage');
            $xmlWriter->writeCdata($mileage);
            $xmlWriter->endElement();

            // Condition
            $condition = null;
            if (isset($ad[AdMotorsSolrFieldMapping::CONDITION_ID]) && $ad[AdMotorsSolrFieldMapping::CONDITION_ID]) {
                $condition = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::CONDITION_ID]);
            }

            $xmlWriter->startElement('condition');
            $xmlWriter->writeCdata($condition);
            $xmlWriter->endElement();

            // Service history
            $serviceHistory = null;
            if (isset($adMetaData['service_history_id']) && $adMetaData['service_history_id']) {
                $serviceHistory = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $adMetaData['service_history_id']);
            }

            $xmlWriter->startElement('service_history');
            $xmlWriter->writeCdata($serviceHistory);
            $xmlWriter->endElement();

            // No of owners
            $noOfOwners = null;
            if (isset($adMetaData['no_of_owners']) && $adMetaData['no_of_owners']) {
                $noOfOwners = $adMetaData['no_of_owners'];
            }

            $xmlWriter->startElement('number_of_owners');
            $xmlWriter->writeCdata($noOfOwners);
            $xmlWriter->endElement();

            // MOT expiry month
            $motExpiryMonth = null;
            if (isset($adMetaData['mot_expiry_month']) && $adMetaData['mot_expiry_month']) {
                $motExpiryMonth = CommonManager::getMonthName($adMetaData['mot_expiry_month']);
            }

            $xmlWriter->startElement('mot_expiry_month');
            $xmlWriter->writeCdata($motExpiryMonth);
            $xmlWriter->endElement();

            // Features
            $features = null;
            if (isset($adMetaData['features_id']) && $adMetaData['features_id']) {
                $featureIds = explode(',', $adMetaData['features_id']);
                foreach ($featureIds as $featureId) {
                    $features .= $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $featureId).', ';
                }
            }

            if ($features) {
                $features = rtrim($features, ', ');
            }

            $xmlWriter->startElement('features');
            $xmlWriter->writeCdata($features);
            $xmlWriter->endElement();

            // Ad location
            $this->addLocationData($xmlWriter, $ad);

            // Date Ad Posted
            $adPostedDate = null;
            if (isset($ad[AdSolrFieldMapping::CREATED_AT]) && $ad[AdSolrFieldMapping::CREATED_AT]) {
                $adPostedDate = date('Y-m-d H:i:s', $ad[AdSolrFieldMapping::CREATED_AT]);
            }

            $xmlWriter->startElement('date_ad_posted');
            $xmlWriter->writeCdata($adPostedDate);
            $xmlWriter->endElement();

            // Date Ad Last Updated
            $adUpdatedDate = null;
            if (isset($ad[AdSolrFieldMapping::UPDATED_AT]) && $ad[AdSolrFieldMapping::UPDATED_AT]) {
                $adUpdatedDate = date('Y-m-d H:i:s', $ad[AdSolrFieldMapping::UPDATED_AT]);
            }

            $xmlWriter->startElement('date_ad_last_updated');
            $xmlWriter->writeCdata($adUpdatedDate);
            $xmlWriter->endElement();

            $xmlWriter->endElement();
        }

        $xmlWriter->endElement();

        $this->writeData($xmlWriter->flush(true), 'BikeMarketXML.xml', FILE_APPEND, 'xml');
    }

    /**
     * ad image data
     *
     * @param \XMLWriter  $xmlWriter xml writer
     * @param solr object $ad        ad object
     */
    private function addImageData($xmlWriter, $ad)
    {
        if (isset($ad[AdSolrFieldMapping::PATH][0])) {
            $xmlWriter->startElement('main_image_url');

            if (isset($ad[AdSolrFieldMapping::AWS][0]) && $ad[AdSolrFieldMapping::AWS][0] == 1) {
                $imageThumbUrl = CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
            } else {
                $imageThumbUrl = 'http:'.CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][0], $ad[AdSolrFieldMapping::HASH][0], null, (isset($ad[AdSolrFieldMapping::AWS]) ? $ad[AdSolrFieldMapping::AWS][0] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][0] : null);
            }

            $xmlWriter->writeCdata($imageThumbUrl);
            $xmlWriter->endElement();
        }
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
        // Location (latitude/longitude)
        $location  = null;
        $latitude  = null;
        $longitude = null;

        if (isset($ad[AdSolrFieldMapping::LATITUDE][0]) && $ad[AdSolrFieldMapping::LATITUDE][0]) {
            $latitude = $ad[AdSolrFieldMapping::LATITUDE][0];
        }

        if (isset($ad[AdSolrFieldMapping::LONGITUDE][0]) && $ad[AdSolrFieldMapping::LONGITUDE][0]) {
            $longitude = $ad[AdSolrFieldMapping::LONGITUDE][0];
        }

        if ($latitude && $longitude) {
            $location = $latitude.', '.$longitude;
        }

        $xmlWriter->startElement('location');
        $xmlWriter->writeCdata($location);
        $xmlWriter->endElement();

        //postcode
        $postcode = isset($ad[AdSolrFieldMapping::POSTCODE][0]) ? $ad[AdSolrFieldMapping::POSTCODE][0] : null;

        $xmlWriter->startElement('postcode');
        $xmlWriter->writeCdata($postcode);
        $xmlWriter->endElement();
    }
}
