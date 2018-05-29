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
 * NewsNow feed export class
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class NewsNow extends Export
{
    /**
     * init feed file
     */
    public function initFile()
    {
        $this->removeFile('NewsNowIndex.xml', 'xml');
        $this->removeFile('NewsNowXML.xml', 'xml');
    }

    public function fixFile()
    {
        echo 'Removing older files'."\n";
        $files = glob($this->rootDir().'/export/xml/NewsNowXML*');

        foreach ($files as $file) {
            if ($file !== $this->rootDir().'/export/xml/NewsNowXML.xml') {
                $this->removeFile(basename($file), 'xml');
            }
        }

        $command = "sed -i '1s/^/<newsnow>/' ".$this->rootDir().'/export/xml/NewsNowXML.xml';
        passthru($command, $returnVar);
        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $command = "sed -i '1s/^/<?xml version=\"1.0\" encoding=\"utf-8\"?>/' ".$this->rootDir().'/export/xml/NewsNowXML.xml';
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $command = "sed -i '\$a</newsnow>' ".$this->rootDir().'/export/xml/NewsNowXML.xml';
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        echo 'Generating multiple files'."\n";
        $command = "xml_split -g10000 ".$this->rootDir().'/export/xml/NewsNowXML.xml';
        passthru($command, $returnVar);
        $files = glob($this->rootDir().'/export/xml/NewsNowXML-*');

        $i = 0;
        $new_files = array();
        echo 'Processing multiple files'."\n";
        foreach ($files as $file) {
            if ($i == 0) {
                $i++;
                $this->removeFile(basename($file), 'xml');
                continue;
            }

            rename($file, $this->rootDir().'/export/xml/NewsNowXML'.$i.'.xml');
            $command = "sed -i 's/<xml_split:root xmlns:xml_split=\"http:\/\/xmltwig.com\/xml_split\">/<rubrikk>/' ".$this->rootDir().'/export/xml/NewsNowXML'.$i.'.xml';
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                new \Exception('Some problem');
            }

            $command = "sed -i 's/<\/xml_split:root>/<\\/rubrikk>/' ".$this->rootDir().'/export/xml/NewsNowXML'.$i.'.xml';
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                new \Exception('Some problem');
            }
            $new_files[] = 'NewsNowXML'.$i.'.xml';
            $i++;
        }

        $this->initFile();

        $this->writeNewsNowIndex($new_files);
        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }
    }

    public function writeNewsNowIndex($files)
    {
        $c = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $c .= '<links>'."\n";

        foreach ($files as $file) {
            $c .= '<loc>'.$file.'</loc>'."\n";
        }

        $c .= '</links>'."\n";

        $this->writeData($c, 'NewsNowIndex.xml', FILE_APPEND, 'xml');
    }

    /**
     * write product data
     *
     * @param solr array $ads
     */
    public function writeAdsData($ads, $categoryMapping = array())
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        foreach ($ads as $ad) {
            $category     = null;
            $mainCategory = null;

            // For cars and commercial vehicle ads category is static
            if (isset($ad[AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID]) && in_array($ad[AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID], array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID))) {
                $category     = 'Cars & Vans for Sale';
                $mainCategory = 'Cars & Vehicles';
            }

            if (!$category) {
                $oldCategoryIds = $this->em->getRepository('FaEntityBundle:MappingCategory')->getOldCategoryIds($ad[AdSolrFieldMapping::CATEGORY_ID]);
                if (count($oldCategoryIds)) {
                    foreach ($oldCategoryIds as $oldCategoryId) {
                        if (isset($categoryMapping[$oldCategoryId]) && $categoryMapping[$oldCategoryId]) {
                            $category     = $categoryMapping[$oldCategoryId]['category'];
                            $mainCategory = $categoryMapping[$oldCategoryId]['main_category'];
                            break;
                        }
                    }
                }
            }

            // if no category found then no need to export ad.
            if (!$category) {
                echo 'Missing category in ad id : '.$ad['id']."\n";
                continue;
            }

            // Start ad node
            $xmlWriter->startElement('ad');

            // Ad url
            $adUrl= $this->container->get('fa_ad.manager.ad_routing')->getDetailUrl($ad);
            $xmlWriter->startElement('ad__url');
            $xmlWriter->writeCdata($adUrl);
            $xmlWriter->endElement();

            // Ad headline
            $xmlWriter->startElement('ad__headline');
            $xmlWriter->writeCdata($ad[AdSolrFieldMapping::TITLE]);
            $xmlWriter->endElement();

            // Ad description
            $xmlWriter->startElement('ad__description');

            $description = $ad[AdSolrFieldMapping::DESCRIPTION];
            $remove_word = array('Sex', 'Cock', 'smeg', 'mobile', 'office', 'steal', 'dyke');
            $description = str_replace($remove_word, '', $description);
            $description = CommonManager::stripTagsContent(htmlspecialchars_decode($description), '<em><strong><b><i><u><p><ul><li><ol><div><span><a><br>');
            $xmlWriter->writeCdata($description);
            $xmlWriter->endElement();

            // Ad first image url
            $this->addImageData($xmlWriter, $ad);

            // Ad root category
            $xmlWriter->startElement('maincategory_original');
            $xmlWriter->writeCdata($mainCategory);
            $xmlWriter->endElement();

            // Ad category
            $xmlWriter->startElement('category_original');
            $xmlWriter->writeCdata($category);
            $xmlWriter->endElement();

            // Ad location
            $this->addLocationData($xmlWriter, $ad);

            // Ad price
            if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
                $xmlWriter->startElement('ad__price');
                $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
                $xmlWriter->endElement();
            }

            $xmlWriter->endElement();
        }

        $this->writeData($xmlWriter->flush(true), 'NewsNowXML.xml', FILE_APPEND, 'xml');
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
            $xmlWriter->startElement('ad__imageurl');

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
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        //city
        $city = null;
        if (isset($ad[AdSolrFieldMapping::TOWN_ID][0])) {
            $city = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $ad[AdSolrFieldMapping::TOWN_ID][0]);
        }

        if ($city) {
            $xmlWriter->startElement('location__municipality_city');
            $xmlWriter->writeCdata($city);
            $xmlWriter->endElement();
        }

        //city_area
        $locality = null;
        if (isset($ad[AdSolrFieldMapping::LOCALITY_ID][0])) {
            $locality = $entityCacheManager->getEntityNameById('FaEntityBundle:Locality', $ad[AdSolrFieldMapping::LOCALITY_ID][0]);
        }

        if ($locality) {
            $xmlWriter->startElement('location__postal_name');
            $xmlWriter->writeCdata($locality);
            $xmlWriter->endElement();
        }

        //postcode
        $postcode = isset($ad[AdSolrFieldMapping::POSTCODE][0]) ? $ad[AdSolrFieldMapping::POSTCODE][0] : null;

        if ($postcode) {
            $xmlWriter->startElement('location__zip_postal_code');
            $xmlWriter->writeCdata($postcode);
            $xmlWriter->endElement();
        }
    }
}
