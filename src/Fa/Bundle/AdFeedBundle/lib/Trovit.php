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
 * Trovit feed export class
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class Trovit extends Export
{
    const JOB_FILE = 'TrovitJobXML.xml';
    const VEHICLE_FILE = 'TrovitVehicleXML.xml';
    const PRODUCT_FILE = 'TrovitProductsXML.xml';
    const HOME_FILE = 'TrovitHomeXML.xml';

    /**
     * init feed file
     */
    public function initFile($category)
    {
        if ($category == 'Vehicles') {
            $this->removeFile(self::VEHICLE_FILE, 'xml');
        } elseif ($category == 'Jobs') {
            $this->removeFile(self::JOB_FILE, 'xml');
        } elseif ($category == 'Products') {
            $this->removeFile(self::PRODUCT_FILE, 'xml');
        } elseif ($category == 'Property') {
            $this->removeFile(self::HOME_FILE, 'xml');
        }
    }

    public function fixFile($category)
    {
        if ($category == 'Jobs') {
            $file = self::JOB_FILE;
        } elseif ($category == 'Vehicles') {
            $file = self::VEHICLE_FILE;
        } elseif ($category == 'Products') {
            $file = self::PRODUCT_FILE;
        } elseif ($category == 'Property') {
            $file = self::HOME_FILE;
        }

        $start_text = '<?xml version="1.0" encoding="utf-8"?>\n<trovit>';
        $command = "sed -i '1s/^/<trovit>/' ".$this->rootDir().'/export/xml/'.$file;
        passthru($command, $returnVar);
        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $command = "sed -i '1s/^/<?xml version=\"1.0\" encoding=\"utf-8\"?>/' ".$this->rootDir().'/export/xml/'.$file;

        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Some problem');
        }

        $command = "sed -i '\$a</trovit>' ".$this->rootDir().'/export/xml/'.$file;
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
    public function writeProductData($ads)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $adMotorRepository = $this->em->getRepository('FaAdBundle:Ad');

        foreach ($ads as $ad) {
            try {
                if (trim($ad[AdSolrFieldMapping::DESCRIPTION]) == '' || strlen($ad[AdSolrFieldMapping::DESCRIPTION]) < 30) {
                    continue;
                }

                $xmlWriter->startElement('ad');

                $cat_array = array();
                $cat_array[] = $ad[AdSolrFieldMapping::CATEGORY_ID];

                for ($i=1; $i <=5; $i++) {
                    if (isset($ad['a_parent_category_lvl_'.$i.'_id_i'])) {
                        $cat_array[] = $ad['a_parent_category_lvl_'.$i.'_id_i'];
                    }
                }

                $trovit_category = $this->getTrovitProducts();
                foreach ($trovit_category as $cat => $val) {
                    if (count(array_intersect($cat_array, $val)) > 0) {
                        $tcat = $cat;
                        break;
                    }
                }

                //common data
                $this->addCommonData($xmlWriter, $ad);

                // category
                $xmlWriter->startElement('category');
                $xmlWriter->writeCdata($tcat);
                $xmlWriter->endElement();

                //location
                $this->addLocationData($xmlWriter, $ad);

                // images
                $this->addImageData($xmlWriter, $ad);

                // price
                if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
                    $xmlWriter->startElement('price');
                    $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
                    $xmlWriter->endElement();
                }

                //make
                $brand = null;
                if (isset($ad[AdForSaleSolrFieldMapping::BRAND_ID])) {
                    $brand = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdForSaleSolrFieldMapping::BRAND_ID]);
                }

                if ($brand) {
                    $xmlWriter->startElement('make');
                    $xmlWriter->writeCdata($brand);
                    $xmlWriter->endElement();
                }

                $this->addDateData($xmlWriter, $ad);

                $xmlWriter->endElement();
            } catch (\Exception $e) {
                echo 'Error occurred during subtask => '.$e->getMessage();
            }
        }

        $this->writeData($xmlWriter->flush(true), self::PRODUCT_FILE, FILE_APPEND, 'xml');
    }

    /**
     * get trovit product categories
     *
     * @param  $string
     *
     * @return array
     */
    protected function getTrovitProducts()
    {
        $cat = array();
        $cat['Furniture'] = array(159,269,343,351,300,327,329,330,331,332,336,340,341,342);
        $cat['Sports Equipment'] = array(408,422,425,431);
        $cat['Electronics and Technology'] = array(382,57,71,84,102);
        $cat['Pets'] = array(726,757);
        $cat['Phones'] = array(98);
        $cat['Musical Instruments'] = array(363);
        $cat['Kids'] = array(127,8,143,149,150,151);
        $cat['Photography'] = array(88);
        $cat['Art and Collectables'] = array(40,323);
        $cat['Fashion'] = array(117,104,132,137,147,148,155,156,157);
        $cat['Health and Beauty'] = array(152);
        $cat['Car parts and Accessories'] = array(95,489,490,494);
        return $cat;
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


    public function writePropertyData($ads)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        foreach ($ads as $ad) {
            try {
                $xmlWriter->startElement('ad');

                //common data
                $this->addCommonData($xmlWriter, $ad);

                $cat_array = array();
                $cat_array[] = $ad[AdSolrFieldMapping::CATEGORY_ID];

                for ($i=1; $i <=5; $i++) {
                    if (isset($ad['a_parent_category_lvl_'.$i.'_id_i'])) {
                        $cat_array[] = $ad['a_parent_category_lvl_'.$i.'_id_i'];
                    }
                }

                $trovit_category = $this->getTrovitPropertyTypes();
                foreach ($trovit_category as $cat => $val) {
                    if (count(array_intersect($cat_array, $val)) > 0) {
                        $tcat = $cat;
                        break;
                    }
                }

                // type
                $xmlWriter->startElement('type');
                $xmlWriter->writeCdata($tcat);
                $xmlWriter->endElement();

                //location
                $this->addLocationData($xmlWriter, $ad, $lat_long = true);

                // images
                $this->addImageData($xmlWriter, $ad);

                // price
                if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
                    $xmlWriter->startElement('price');
                    $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
                    $xmlWriter->endElement();
                }

                // rooms
                $rooms = null;
                if (isset($ad[AdPropertySolrFieldMapping::NUMBER_OF_BEDROOMS_ID])) {
                    $rooms = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdPropertySolrFieldMapping::NUMBER_OF_BEDROOMS_ID]);
                }

                if ($rooms) {
                    $xmlWriter->startElement('room');
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
                    $xmlWriter->startElement('is_furnished');
                    $xmlWriter->writeCdata($is_furnished);
                    $xmlWriter->endElement();
                }

                // expiration_date
                $this->addDateData($xmlWriter, $ad);

                $xmlWriter->endElement();
            } catch (\Exception $e) {
                echo 'Error occurred during subtask => '.$e->getMessage();
            }
        }

        $this->writeData($xmlWriter->flush(true), self::HOME_FILE, FILE_APPEND, 'xml');
    }

    /**
     * write vehicles data
     *
     * @param solr array $ads
     */
    public function writeJobData($ads)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        foreach ($ads as $ad) {
            try {
                $xmlWriter->startElement('ad');
                $this->addCommonData($xmlWriter, $ad);
                $this->addLocationData($xmlWriter, $ad);
                $metaData = unserialize($ad[AdJobsSolrFieldMapping::META_DATA]);

                //salary
                $salary = null;
                if (isset($metaData['salary'])) {
                    $salary = $metaData['salary'];

                    if (isset($metaData['salary_type_id'])) {
                        $salary_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $metaData['salary_type_id']);
                        $salary = $salary.' '.$salary_type;
                    }
                }

                if ($salary) {
                    $xmlWriter->startElement('salary');
                    $xmlWriter->writeCdata($salary);
                    $xmlWriter->endElement();
                }

                //experience
                $experience = null;
                if (isset($metaData['years_experience_id'])) {
                    $experience = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $metaData['years_experience_id']);
                    $xmlWriter->startElement('experience');
                    $xmlWriter->writeCdata($experience);
                    $xmlWriter->endElement();
                }

                //studies
                $studies = null;
                if (isset($metaData['education_level_id'])) {
                    $studies = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $metaData['education_level_id']);
                    $xmlWriter->startElement('studies');
                    $xmlWriter->writeCdata($studies);
                    $xmlWriter->endElement();
                }

                //requirements
                $requirements = null;
                if (isset($metaData['additional_job_requirements_id'])) {
                    $requirement_ids = explode(',', $metaData['additional_job_requirements_id']);

                    $requirement_string = array();
                    foreach ($requirement_ids as $requirement_id) {
                        $requirement_string[] = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $requirement_id);
                    }

                    $xmlWriter->startElement('requirements');
                    $xmlWriter->writeCdata(implode(', ', $requirement_string));
                    $xmlWriter->endElement();
                }

                //contract
                $contract_type = null;
                $contract_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdJobsSolrFieldMapping::CONTRACT_TYPE_ID]);
                $contract_type = $this->getTrovitJobContractName($contract_type);

                if ($contract_type) {
                    $xmlWriter->startElement('contract');
                    $xmlWriter->writeCdata($contract_type);
                    $xmlWriter->endElement();
                }

                //category is not required.
                $category = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $ad[AdSolrFieldMapping::CATEGORY_ID]);
                if ($category != '') {
                    $xmlWriter->startElement('category');
                    $xmlWriter->writeCdata($category);
                    $xmlWriter->endElement();
                }

                $this->addDateData($xmlWriter, $ad);

                $xmlWriter->endElement();
            } catch (\Exception $e) {
                echo 'Error occurred during subtask => '.$e->getMessage();
            }
        }

        $this->writeData($xmlWriter->flush(true), self::JOB_FILE, FILE_APPEND, 'xml');
    }

    private function getTrovitJobContractName($contract_type)
    {
        if (!is_array($contract_type)) {
            $contract_type = array($contract_type);
        }

        $contract = array();
        $type     = array();

        $contract['part time']    = 'permanent';
        $contract['full time']    = 'permanent';
        $contract['evenings']     = 'permanent';
        $contract['weekend']      = 'permanent';
        $contract['home working'] = 'permanent';
        $contract['contract']     = 'contract';
        $contract['temporary']    = 'temporary';
        $contract['freelance']    = 'freelance';
        $contract['wanted']       = 'wanted';

        foreach ($contract_type as $key) {
            $key = strtolower($key);
            if (isset($contract[$key])) {
                $type[] = $contract[$key];
            }
        }

        return implode(',', $type);
    }

    /**
     * write commercial vehicle data
     *
     * @param solr $ad ad data
     */
    public function writeCVData($ad, $xmlWriter)
    {
        $metaData = unserialize($ad[AdMotorsSolrFieldMapping::META_DATA]);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        $xmlWriter->startElement('ad');

        $this->addCommonData($xmlWriter, $ad);

        if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
            $xmlWriter->startElement('price');
            $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
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

        $this->addLocationData($xmlWriter, $ad);

        //color
        $color = null;
        $color = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::COLOUR_ID]);
        if ($color == '') {
            $color = isset($metaData['colour']) ? $metaData['colour'] : null;
        }

        if ($color) {
            $xmlWriter->startElement('color');
            $xmlWriter->writeCdata($color);
            $xmlWriter->endElement();
        }

        //year
        $year = null;
        if (isset($metaData['reg_year'])) {
            $year = $ad[AdMotorsSolrFieldMapping::REG_YEAR];
        }

        if ($year) {
            $xmlWriter->startElement('year');
            $xmlWriter->writeCdata($year);
            $xmlWriter->endElement();
        }

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

        //fuel
        $fuel_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::FUEL_TYPE_ID]);

        if ($fuel_type) {
            $xmlWriter->startElement('fuel');
            $xmlWriter->writeCdata($fuel_type);
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

        //seats
        $no_of_seats = null;
        if (isset($metaData['no_of_seats'])) {
            $no_of_seats= $metaData['no_of_seats'];
        }

        if ($no_of_seats) {
            $xmlWriter->startElement('seats');
            $xmlWriter->writeCdata($no_of_seats);
            $xmlWriter->endElement();
        }

        $this->addImageData($xmlWriter, $ad);

        $this->addDateData($xmlWriter, $ad);

        //is_new
        $xmlWriter->startElement('is_new');
        $xmlWriter->writeCdata($ad[AdSolrFieldMapping::IS_NEW]);
        $xmlWriter->endElement();

        //car_type
        $xmlWriter->startElement('car_type');
        $body_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::BODY_TYPE_ID]);
        $xmlWriter->writeCdata($body_type);
        $xmlWriter->endElement();

        $xmlWriter->endElement();
    }

    /**
     * write vehicles data
     *
     * @param solr array $ads
     */
    public function writeVehiclesData($ads)
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $adMotorRepository = $this->em->getRepository('FaAdBundle:AdMotors');

        foreach ($ads as $ad) {
            try {
                $secondLevelCategoryId = null;
                if (isset($ad['a_parent_category_lvl_2_id_i'])) {
                    $secondLevelCategoryId = $ad['a_parent_category_lvl_2_id_i'];
                }

                if (trim($ad[AdSolrFieldMapping::DESCRIPTION]) == '' || strlen($ad[AdSolrFieldMapping::DESCRIPTION]) < 30) {
                    continue;
                }

                if ($secondLevelCategoryId == CategoryRepository::CARS_ID) {
                    $this->writeCarData($ad, $xmlWriter);
                } elseif ($secondLevelCategoryId == CategoryRepository::COMMERCIALVEHICLES_ID) {
                    $this->writeCVData($ad, $xmlWriter);
                }
            } catch (\Exception $e) {
                echo 'Error occurred during subtask => '.$e->getMessage();
            }
        }

        $this->writeData($xmlWriter->flush(true), self::VEHICLE_FILE, FILE_APPEND, 'xml');
    }

    /**
     * write car data
     * @param solr object $ad
     *
     */
    public function writeCarData($ad, $xmlWriter)
    {
        $metaData = unserialize($ad[AdMotorsSolrFieldMapping::META_DATA]);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        $xmlWriter->startElement('ad');

        $this->addCommonData($xmlWriter, $ad);

        if (isset($ad[AdSolrFieldMapping::PRICE]) && $ad[AdSolrFieldMapping::PRICE] > 0) {
            $xmlWriter->startElement('price');
            $xmlWriter->writeCdata($ad[AdSolrFieldMapping::PRICE]);
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

        $this->addLocationData($xmlWriter, $ad);

        //color
        $color = null;
        $color = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::COLOUR_ID]);
        if ($color == '') {
            $color = isset($metaData['colour']) ? $metaData['colour'] : null;
        }

        if ($color) {
            $xmlWriter->startElement('color');
            $xmlWriter->writeCdata($color);
            $xmlWriter->endElement();
        }

        //year
        $year = null;
        if (isset($metaData['reg_year'])) {
            $year = str_replace('-', ' ', ucfirst($ad[AdMotorsSolrFieldMapping::REG_YEAR]));
        }

        if ($year) {
            $xmlWriter->startElement('year');
            $xmlWriter->writeCdata($year);
            $xmlWriter->endElement();
        }

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

        //fuel
        $fuel_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::FUEL_TYPE_ID]);

        if ($fuel_type) {
            $xmlWriter->startElement('fuel');
            $xmlWriter->writeCdata($fuel_type);
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

        //seats
        $no_of_seats = null;
        if (isset($metaData['no_of_seats'])) {
            $no_of_seats= $metaData['no_of_seats'];
        }

        if ($no_of_seats) {
            $xmlWriter->startElement('seats');
            $xmlWriter->writeCdata($no_of_seats);
            $xmlWriter->endElement();
        }

        $this->addImageData($xmlWriter, $ad);

        //is_new
        $xmlWriter->startElement('is_new');
        $xmlWriter->writeCdata($ad[AdSolrFieldMapping::IS_NEW]);
        $xmlWriter->endElement();

        //car_type
        $xmlWriter->startElement('car_type');
        $body_type = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $ad[AdMotorsSolrFieldMapping::BODY_TYPE_ID]);
        $xmlWriter->writeCdata($body_type);
        $xmlWriter->endElement();

        $this->addDateData($xmlWriter, $ad);

        $xmlWriter->endElement();
    }

    /**
     * ad image data
     *
     * @param \XMLWriter  $xmlWriter xml writer
     * @param solr object $ad        ad object
     */
    private function addImageData($xmlWriter, $ad)
    {
        $xmlWriter->startElement('pictures');
        $seoToolRepo = $this->em->getRepository('FaContentBundle:SeoTool');
        $seoRule = $seoToolRepo->getSeoPageRuleDetailForSolrResult($ad, SeoToolRepository::ADVERT_IMG_ALT, $this->container);
        if (isset($ad[AdSolrFieldMapping::ORD]) && isset($ad[AdSolrFieldMapping::HASH]) && isset($ad[AdSolrFieldMapping::PATH])) {
            foreach ($ad[AdSolrFieldMapping::PATH] as $key => $path) {
                $xmlWriter->startElement('picture');
                $xmlWriter->startElement('picture_url');

                $imageThumbUrl = null;
                if (isset($ad[AdSolrFieldMapping::AWS]) && isset($ad[AdSolrFieldMapping::AWS][$key]) && isset($ad[AdSolrFieldMapping::PATH][$key]) && isset($ad[AdSolrFieldMapping::HASH][$key]) && $ad[AdSolrFieldMapping::AWS][$key] == 1) {
                    $imageThumbUrl = CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][$key], $ad[AdSolrFieldMapping::HASH][$key], '300X225', (isset($ad[AdSolrFieldMapping::AWS][$key]) ? $ad[AdSolrFieldMapping::AWS][$key] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME][$key])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][$key] : null);
                } else {
                    if (isset($ad[AdSolrFieldMapping::PATH][$key]) && isset($ad[AdSolrFieldMapping::HASH][$key])) {
                        $imageThumbUrl = 'http:'.CommonManager::getAdImageUrl($this->container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][$key], $ad[AdSolrFieldMapping::HASH][$key], '300X225', (isset($ad[AdSolrFieldMapping::AWS][$key]) ? $ad[AdSolrFieldMapping::AWS][$key] : 0), (isset($ad[AdSolrFieldMapping::IMAGE_NAME][$key])) ? $ad[AdSolrFieldMapping::IMAGE_NAME][$key] : null);
                    }
                }

                $xmlWriter->writeCdata($imageThumbUrl);
                $xmlWriter->endElement();
                $xmlWriter->startElement('picture_title');

                $imageThumbTitle = null;
                if (isset($seoRule['image_alt'])) {
                    $imageThumbTitle = CommonManager::getAdImageAlt($this->container, 'image_alt', $ad, $key);
                } else {
                    $imageThumbTitle = $ad[AdSolrFieldMapping::TITLE].' '.$ad[AdSolrFieldMapping::ID].' '.$key;
                }
                $xmlWriter->writeCdata($imageThumbTitle);
                $xmlWriter->endElement();
                $xmlWriter->endElement();
            }
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
        //id
        $xmlWriter->startElement('id');
        $xmlWriter->writeCdata($ad[AdSolrFieldMapping::ID]);
        $xmlWriter->endElement();

        //url
        $adUrl= $this->container->get('fa_ad.manager.ad_routing')->getDetailUrl($ad);
        $xmlWriter->startElement('url');
        $xmlWriter->writeCdata($adUrl);
        $xmlWriter->endElement();

        //mobile_url
        $adUrl= $this->container->get('fa_ad.manager.ad_routing')->getDetailUrl($ad);
        $xmlWriter->startElement('mobile_url');
        $xmlWriter->writeCdata($adUrl);
        $xmlWriter->endElement();

        $remove_word = array('Sex', 'Cock', 'smeg', 'mobile', 'office', 'steal', 'ass', 'wanted', 'dyke', 'worm', 'amateur', 'bitch', 'rat', 'illegal', 'hand job');
        //url
        $xmlWriter->startElement('title');
        $title = $ad[AdSolrFieldMapping::TITLE];
        $title = str_replace($remove_word, '', $title);
        $xmlWriter->writeCdata($title);
        $xmlWriter->endElement();

        //url
        $xmlWriter->startElement('content');

        $description = $ad[AdSolrFieldMapping::DESCRIPTION];
        $description = str_replace($remove_word, '', $description);
        $description = CommonManager::stripTagsContent(htmlspecialchars_decode($description), '<em><strong><b><i><u><p><ul><li><ol><div><span><a><br>');
        $xmlWriter->writeCdata($description);
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
