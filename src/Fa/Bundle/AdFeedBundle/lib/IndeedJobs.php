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
use Fa\Bundle\AdBundle\Solr\AdJobsSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;

/**
 * CandP feed export class
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class IndeedJobs extends Export
{
    const JOBS_FILE = 'IndeedJobsXML.xml';

    /**
     * init feed file
     */
    public function initFile()
    {
        $this->removeFile(self::JOBS_FILE, 'xml');
    }

    public function fixFile()
    {
        $file = self::JOBS_FILE;

        $command = "sed -i '1s/^/<source>/' ".$this->rootDir().'/export/xml/'.$file;
        passthru($command, $returnVar);
        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $command = "sed -i '1s/^/<?xml version=\"1.0\" encoding=\"utf-8\"?>/' ".$this->rootDir().'/export/xml/'.$file;

        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $command = "sed -i '\$a</source>' ".$this->rootDir().'/export/xml/'.$file;
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }
    }

    /**
     * write jobs data
     *
     * @param solr $ad ad data
     */
    public function writeJobData($ad, $xmlWriter)
    {
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $em = $this->container->get('doctrine')->getManager();

        $xmlWriter->startElement('job');

        $remove_word = array('Sex', 'Cock', 'smeg', 'mobile', 'office', 'steal', 'ass', 'wanted', 'dyke', 'worm', 'amateur', 'bitch', 'rat', 'illegal', 'hand job');
        //url
        $xmlWriter->startElement('title');
        $title = $ad[AdSolrFieldMapping::TITLE];
        $title = str_replace($remove_word, '', $title);
        $xmlWriter->writeCdata($title);
        $xmlWriter->endElement();

        //date
        $xmlWriter->startElement('date');
        $xmlWriter->writeCdata(gmdate("M, d Y H:i:s T", $ad[AdSolrFieldMapping::PUBLISHED_AT]));
        $xmlWriter->endElement();

        //id
        $xmlWriter->startElement('referencenumber');
        $xmlWriter->writeCdata($ad[AdSolrFieldMapping::ID]);
        $xmlWriter->endElement();

        //url
        $adUrl= $this->container->get('fa_ad.manager.ad_routing')->getDetailUrl($ad);
        $xmlWriter->startElement('url');
        $xmlWriter->writeCdata($adUrl);
        $xmlWriter->endElement();

        //company
        $xmlWriter->startElement('company');
        $xmlWriter->writeCdata($em->getRepository('FaUserBundle:User')->getUserProfileName($ad[AdJobsSolrFieldMapping::USER_ID], $this->container));
        $xmlWriter->endElement();

        $this->addLocationData($xmlWriter, $ad);

        //description
        $xmlWriter->startElement('description');

        $description = $ad[AdSolrFieldMapping::DESCRIPTION];
        $description = str_replace($remove_word, '', $description);
        $description = CommonManager::stripTagsContent(htmlspecialchars_decode($description), '<em><strong><b><i><u><p><ul><li><ol><div><span><a><br>');
        $xmlWriter->writeCdata($description);
        $xmlWriter->endElement();

        $xmlWriter->endElement();
    }

    /**
     * write jobs data
     *
     * @param solr array $ads
     */
    public function writeJobsData($ads, $offset)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        if ($offset == 0) {
            //publisher
            $xmlWriter->startElement('publisher');
            $xmlWriter->writeCdata($this->container->getParameter('service_name'));
            $xmlWriter->endElement();

            //publisher url
            $xmlWriter->startElement('publisherurl');
            $xmlWriter->writeCdata($this->container->getParameter('base_url'));
            $xmlWriter->endElement();

            //date
            $xmlWriter->startElement('lastBuildDate');
            $xmlWriter->writeCdata(gmdate("M, d Y H:i:s T", time()));
            $xmlWriter->endElement();
        }

        foreach ($ads as $ad) {
            $firstLevelCategoryId = null;
            if (isset($ad['a_parent_category_lvl_1_id_i'])) {
                $firstLevelCategoryId = $ad['a_parent_category_lvl_1_id_i'];
            }

            if (trim($ad[AdSolrFieldMapping::DESCRIPTION]) == '' || strlen($ad[AdSolrFieldMapping::DESCRIPTION]) < 30) {
                continue;
            }

            if ($firstLevelCategoryId == CategoryRepository::JOBS_ID) {
                $this->writeJobData($ad, $xmlWriter);
            }
        }

        $this->writeData($xmlWriter->flush(true), self::JOBS_FILE, FILE_APPEND, 'xml');
    }

    /**
     * add location data
     *
     * @param \XMLWriter  $xmlWriter xml writer
     * @param solr object $ad        solr ad object
     */
    private function addLocationData($xmlWriter, $ad)
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

        //state
        $county = null;
        if (isset($ad[AdSolrFieldMapping::DOMICILE_ID][0])) {
            $county = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $ad[AdSolrFieldMapping::DOMICILE_ID][0]);
        }
        if ($county) {
            $xmlWriter->startElement('state');
            $xmlWriter->writeCdata($county);
            $xmlWriter->endElement();
        }

        //country
        $county = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', LocationRepository::COUNTY_ID);
        $xmlWriter->startElement('country');
        $xmlWriter->writeCdata($county);
        $xmlWriter->endElement();

        //postcode
        $postcode = isset($ad[AdSolrFieldMapping::POSTCODE][0]) ? $ad[AdSolrFieldMapping::POSTCODE][0] : null;
        if ($postcode) {
            $xmlWriter->startElement('postcode');
            $xmlWriter->writeCdata($postcode);
            $xmlWriter->endElement();
        }
    }
}
