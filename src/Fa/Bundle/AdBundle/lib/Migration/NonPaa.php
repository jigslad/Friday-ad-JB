<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\lib\Migration;

use Fa\Bundle\AdBundle\Entity\AdProperty;
use Fa\Bundle\AdBundle\Entity\AdJobs;
use Fa\Bundle\AdBundle\Entity\AdMotors;
use Fa\Bundle\AdBundle\lib\Migration\Car;
use Gedmo\Sluggable\Util\Urlizer as Urlizer;
use Fa\Bundle\AdBundle\Repository\PrintEditionRepository;
use Fa\Bundle\AdBundle\Entity\AdPrint;

/**
 * Fa\Bundle\AdBundle\lib\Migration
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class NonPaa
{

    private $meta_text;

    private $ad_id;

    private $data = array();


    public function __construct($metaText, $ad_id, $em)
    {
        $this->meta_text = $metaText;
        $this->ad_id     = $ad_id;
        $this->em = $em;
        $this->init();

    }

    public function init()
    {
        $string     = null;
        $this->data = array();

        if ($this->meta_text != "") {
            try {
                $this->data = unserialize($this->meta_text);
            } catch (Exception $e) {
                return 0;
            }
        }
    }


    private function getBedrooms($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();
        // mapping done
        $cType['1'] = 2525;
        $cType['2'] = 2526;
        $cType['3'] = 2527;
        $cType['4'] = 2528;
        $cType['5'] = 2529; // TODO: confirm with client which mapping should we use for 5+

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    private function getBathRooms($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();
        // mapping done
        $cType['1'] = 2534;
        $cType['2'] = 2535;
        $cType['3'] = 2536; // TODO: confirm with client which mapping should we use for 5+

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    private function getContractTypeId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['Part-time']      = 2444;
        $cType['Full-time']      = 2445;
        $cType['Evenings']       = 2446;
        $cType['Evening']        = 2446;
        $cType['Weekend']        = 2447;
        $cType['Variable Hours'] = 2448;
        $cType['Temporary']      = 2449;
        $cType['Freelance']      = 2450;
        $cType['Home Working']   = 2451;
        $cType['Wanted']         = 2452;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    public function update()
    {
        if (count($this->data) > 0) {
            $adRepository  = $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $this->ad_id));
            $printEditions = $this->em->getRepository('FaAdBundle:PrintEdition')->getActivePrintEditionCodeArray();

            if ($adRepository) {

            if (isset($this->data['PubInserts']) && $this->data['PubInserts'] != '') {

                  $print_entries = explode(',', $this->data['PubInserts']);

                  foreach ($print_entries as $print_entry) {

                        $print_entries = explode('|', $print_entry);

                        if (isset($print_entries[0]) && isset($print_entries[1]) && isset($printEditions[$print_entries[0]])) {
                          $printAd = $this->em->getRepository('FaAdBundle:AdPrint')->findOneBy(array('ad' => $adRepository));
                          $p = $this->em->getRepository('FaAdBundle:PrintEdition')->find($printEditions[$print_entries[0]]);
                          if (!$printAd) {
                                   $adPrint = new AdPrint();
                                  $adPrint->setAd($adRepository);
                                  $adPrint->setPrintEdition($p);
                                  $adPrint->setDuration('1 weeks');
                                  $adPrint->setSequence(1);
                                  $adPrint->setIsPaid(1);
                                  $adPrint->setPrintQueue(0);
                                  $adPrint->setAdModerateStatus(2);
                                  $adPrint->setInsertDate(strtotime($print_entries[1]));
                                  $this->em->persist($adPrint);
                            }
                        }
                  }
            }

                if ($adRepository->getCategory()) {
                    $category = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryName($adRepository->getCategory()->getId());

                    if ($category == 'motors') {
                        $catPath = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($adRepository->getCategory()->getId());
                        $catPath = implode('/', $catPath);

                        if (strstr($catPath, 'Motors/Cars/')) {
                            $this->updateCarData();
                        }
                    }

                    if ($category == 'property') {
                        $this->updatePropertyData();
                    }

                    if ($category == 'jobs') {
                        $this->updateJobsData();
                    }

                } else {
                    if (5016 == $adRepository->getOldClassId()) {
                        if (isset($this->data['Make']) && (!isset($this->data['Model']))) {
                            $this->data['Model'] = 'Others';
                        }

                        if (isset($this->data['Model']) && $this->data['Model'] != '') {
                            $makeModelUrl = Urlizer::urlize($this->data['Make']).'/'.Urlizer::urlize($this->data['Model']);
                            $category =  $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($this->data['Model'], 'motors/cars/'.$makeModelUrl, null, true);


                            if (!$category) {
                                if ($this->data['Make'] == 'Mercedes-Benz') {
                                    $this->data['Make'] = 'Mercedes';
                                }

                                $makeModelUrl = Urlizer::urlize($this->data['Make']).'/'.Urlizer::urlize('Other');
                                $category =  $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern('Others', 'motors/cars/'.$makeModelUrl, null, true);

                                if (!$category) {
                                    echo 'MISSING Car - '.$this->data['Make'].'/'.$this->data['Model']."\n";
                                }
                            }

                            if (isset($category[0]['id'])) {
                                $adRepository->setCategory($this->em->getReference('FaEntityBundle:Category', $category[0]['id']));
                                $this->em->persist($adRepository);
                                echo 'Non Paa category settings done for CAR AD ID '.$adRepository->getId()."\n";
                            }
                        }
                    } elseif (5055 == $adRepository->getOldClassId()) {
                        if (isset($this->data['Make']) && (!isset($this->data['Model']))) {
                            $this->data['Model'] = 'Others';
                        }

                        if (!isset($this->data['Make'])) {
                            $this->data['Make'] = 'Others';
                            $this->data['Model'] = 'Others';
                        }

                        if (isset($this->data['Model']) && $this->data['Model'] != '') {
                            if (in_array($this->data['Make'], array('SEAT', 'Volkswagen', 'Ford', 'Vauxhall', 'Suzuki'))) {
                                $this->data['Make'] = $this->data['Make'].' Vans trucks';
                            }

                            $makeModelUrl = Urlizer::urlize($this->data['Make']);
                            $category =  $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($this->data['Model'], 'motors/commercial-vehicles/'.$makeModelUrl, null, true);

                            if (!$category) {
                                $makeModelUrl = Urlizer::urlize($this->data['Make']);
                                $category =  $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern('Others', 'motors/commercial-vehicles/'.$makeModelUrl, null, true);

                                if (!$category) {
                                    echo 'MISSING CV - '.$this->data['Make'].'/'.$this->data['Model']."\n";
                                }
                            }

                            if (isset($category[0]['id'])) {
                                $adRepository->setCategory($this->em->getReference('FaEntityBundle:Category', $category[0]['id']));
                                $this->em->persist($adRepository);
                                echo 'Non Paa category settings done for CV AD ID '.$adRepository->getId()."\n";
                            }
                        }

                    }
                }
                //echo "Dimension updated for ".$adRepository->getAd()->getId()."\n";
            }
        }
    }


    public function updateJobsData()
    {
        $jobsRepository = $this->em->getRepository('FaAdBundle:AdJobs')->findOneBy(array('ad' => $this->ad_id));
        $metaData = array();

        if (!$jobsRepository) {
            $jobsRepository = new AdJobs();
            $jobsRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
        }

        if (isset($this->data['Contract']) && $this->data['Contract'] != '') {
            $jobsRepository->setContractTypeId($this->getContractTypeId($this->data['Contract']));
        }

        $this->em->persist($jobsRepository);

        echo 'Jobs data updated for ad ID '.$jobsRepository->getAd()->getId()."\n";
    }


    public function updateCarData()
    {
        $car = new Car(null, null, null);
        $motorsRepository = $this->em->getRepository('FaAdBundle:AdMotors')->findOneBy(array('ad' => $this->ad_id));
        $metaData = array();

        if (!$motorsRepository) {
            $motorsRepository = new AdMotors();
            $motorsRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
        }

        if (isset($this->data['Colour']) && $this->data['Colour'] != '') {
            $motorsRepository->setColourId($car->getColorId($this->data['Colour']));
        }

        if (isset($this->data['BodyType']) && $this->data['BodyType'] != '') {
            $motorsRepository->setBodyTypeId($car->getBodyTypeId($this->data['BodyType']));
        }

        if (isset($this->data['FuelType']) && $this->data['FuelType'] != '') {
            $motorsRepository->setFuelTypeId($car->getFuelTypeId($this->data['FuelType']));
        }

        if (isset($this->data['Transmission']) && $this->data['Transmission'] != '') {
            $motorsRepository->setTransmissionId($car->getTransmissionId($this->data['Transmission']));
        }

        if (isset($this->data['Features']) && $this->data['Features'] != '') {
            $amenities = array();
            $featuresData = explode(',', $this->data['Features']);

            if (in_array('ABS', $featuresData)) {
                $amenities[] = 1658;
            }

            if (in_array('Airbags', $featuresData)) {
                $amenities[] = 1655;
            }

            if (in_array('Alarm', $featuresData)) {
                $amenities[] = 1662;
            }

            if (in_array('CD Player', $featuresData)) {
                $amenities[] = 1664;
            }

            if (in_array('Central Locking', $featuresData)) {
                $amenities[] = 1653;
            }

            if (in_array('Electric Windows', $featuresData)) {
                $amenities[] = 1652;
            }

            if (in_array('Immobiliser', $featuresData)) {
                $amenities[] = 1663;
            }

            if (implode(',', $amenities) != '') {
                $metaData['features_id'] = implode(',', $amenities);
            }


            if (count($metaData) > 0) {
                $motorsRepository->setMetaData(serialize($metaData));
            }

            $motorsRepository->setTransmissionId($car->getTransmissionId($this->data['Features']));

            $this->em->persist($motorsRepository);
            echo 'Car data updated for ad ID '.$motorsRepository->getAd()->getId()."\n";
        }
    }

    public function updatePropertyData()
    {
        $propertyRepository = $this->em->getRepository('FaAdBundle:AdProperty')->findOneBy(array('ad' => $this->ad_id));
        $metaData = array();

        if (!$propertyRepository) {
            $propertyRepository = new AdProperty();
            $propertyRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
        }

        if (isset($this->data['Bedrooms']) && intval($this->data['Bedrooms']) > 0) {
            $propertyRepository->setNumberOfBedroomsId($this->getBedrooms(intval($this->data['Bedrooms'])));
        }

        $amenities = array();

        if (isset($this->data['OutsideSpace']) && $this->data['OutsideSpace'] != '') {
            $outSideSpace = explode(',', $this->data['OutsideSpace']);

            if (in_array('Off Road Parking', $outSideSpace)) {
                $amenities[] = 2550;
            }

            if (in_array('Garden', $outSideSpace)) {
                $amenities[] = 2545;
            }

            if (in_array('Garage', $outSideSpace)) {
                $amenities[] = 2548;
            }

            if (in_array('Private Car Park', $outSideSpace)) {
                $amenities[] = 2551;
            }
        }

        if (isset($this->data['Amenities']) && $this->data['Amenities'] != '') {
            $outSideSpace = explode(',', $this->data['Amenities']);
            if (in_array('Central Heating', $outSideSpace)) {
                $amenities[] = 2541;
            }
            if (in_array('Conservatory', $outSideSpace)) {
                $amenities[] = 2543;
            }
        }

        if (isset($this->data['Bathrooms']) && $this->data['Bathrooms'] != '') {
            $metaData['number_of_bathrooms_id'] = $this->getBathRooms(intval($this->data['Bathrooms']));
        }

        if (implode(',', $amenities) != '') {
            $propertyRepository->setAmenitiesId(implode(',', $amenities));
        }


        $propertyRepository->setMetaData(serialize($metaData));
        $this->em->persist($propertyRepository);

        echo 'Property data updated for ad ID '.$propertyRepository->getAd()->getId()."\n";
    }
}
