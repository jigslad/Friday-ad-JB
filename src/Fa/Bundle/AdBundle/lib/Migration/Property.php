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

/**
 * Fa\Bundle\AdBundle\lib\Migration
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class Property
{
    private $meta_text;

    private $ad_id;

    private $container;

    private $data = array();


    public function __construct($metaText, $ad_id, $em, $container)
    {
        $this->meta_text = $metaText;
        $this->amenities = $metaText;
        $this->ad_id     = $ad_id;
        $this->em = $em;
        $this->container = $container;
        $this->init();
    }

    public function init()
    {
        $this->data = array();
        $string   = null;

        if ($this->meta_text != "") {
            try {
                $string = simplexml_load_string($this->meta_text);
                libxml_use_internal_errors(false);
            } catch (\Exception $e) {
                return 0;
            }

            if (isset($string->PropertyTransMeta->bedrooms)) {
                $this->data['bedroom_id'] =  $this->getBedrooms((string) $string->PropertyTransMeta->bedrooms);
            }

            if (isset($string->PropertyTransMeta->bathrooms)) {
                $this->data['bathrooms_id'] =  $this->getBathRooms((string) $string->PropertyTransMeta->bathrooms);
            }

            if (isset($string->PropertyTransMeta->furnishings)) {
                $this->data['furnishings'] =  $this->getFurnishings((string) $string->PropertyTransMeta->furnishings);
            }

            if (isset($string->PropertyTransMeta->hasCentralHeating)) {
                $this->data['central_heating'] =  (string) $string->PropertyTransMeta->hasCentralHeating;
            }

            if (isset($string->PropertyTransMeta->hasAirCon)) {
                $this->data['air_conditionig'] =  (string) $string->PropertyTransMeta->hasAirCon;
            }

            if (isset($string->PropertyTransMeta->hasConervatory)) {
                $this->data['conervatory'] =  (string) $string->PropertyTransMeta->hasConervatory;
            }

            if (isset($string->PropertyTransMeta->hasFireplace)) {
                $this->data['fire_places'] =  (string) $string->PropertyTransMeta->hasFireplace;
            }

            if (isset($string->PropertyTransMeta->hasSwimmingPool)) {
                $this->data['swiming_pool'] =  (string) $string->PropertyTransMeta->hasSwimmingPool;
            }

            if (isset($string->PropertyTransMeta->hasGarden)) {
                $this->data['garden'] =  (string) $string->PropertyTransMeta->hasGarden;
            }

            if (isset($string->PropertyTransMeta->hasSharedGarden)) {
                $this->data['shared_garden'] =  (string) $string->PropertyTransMeta->hasSharedGarden;
            }

            if (isset($string->PropertyTransMeta->hasRoofTerrace)) {
                $this->data['roof_terrace'] =  (string) $string->PropertyTransMeta->hasRoofTerrace;
            }

            if (isset($string->PropertyTransMeta->hasBalcony)) {
                $this->data['balcony'] =  (string) $string->PropertyTransMeta->hasBalcony;
            }

            if (isset($string->PropertyTransMeta->hasGarage)) {
                $this->data['garage'] =  (string) $string->PropertyTransMeta->hasGarage;
            }

            if (isset($string->PropertyTransMeta->hasDriveway)) {
                $this->data['drive_way'] =  (string) $string->PropertyTransMeta->hasDriveway;
            }

            if (isset($string->PropertyTransMeta->hasOffRoadParking)) {
                $this->data['off_road_parking'] =  (string) $string->PropertyTransMeta->hasOffRoadParking;
            }

            if (isset($string->PropertyTransMeta->hasPrivateCarpark)) {
                $this->data['private_car_park'] =  (string) $string->PropertyTransMeta->hasPrivateCarpark;
            }
        }
    }

    private function getFurnishings($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();

        $cType['furnished'] =  2556;
        $cType['part furnished'] = 2557;
        $cType['unfurnished'] = 2558;

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

    private function getRentPerForRent($string)
    {
        $cType  = array();

        $cType['week'] =  2559;
        $cType['month'] = 2560;
        $cType['year'] = 2561;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    private function getRentPerForShare($string)
    {
        $cType  = array();

        $cType['week'] =  2581;
        $cType['month'] = 2582;
        $cType['year'] = 2583;

        if (isset($cType[$string])) {
            return $cType[$string];
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

    private function getPropertyType($string)
    {
        echo  $string = strtolower(trim($string))."\n";
        return $string;
    }

    private function getAmenities()
    {
        $amenities = array();

        if (isset($this->data['central_heating']) && $this->data['central_heating'] == 'true') {
            $amenities[] = 2541;
        }

        if (isset($this->data['air_conditionig']) && $this->data['air_conditionig'] == 'true') {
            $amenities[] = 2542;
        }

        if (isset($this->data['conervatory']) && $this->data['conervatory'] == 'true') {
            $amenities[] = 2543;
        }

        if (isset($this->data['fire_places']) && $this->data['fire_places'] == 'true') {
            $amenities[] = 2540;
        }

        if (isset($this->data['swiming_pool']) && $this->data['swiming_pool'] == 'true') {
            $amenities[] = 2539;
        }

        if (isset($this->data['garden']) && $this->data['garden'] == 'true') {
            $amenities[] = 2545;
        }

        if (isset($this->data['shared_garden']) && $this->data['shared_garden'] == 'true') {
            $amenities[] = 2546;
        }

        if (isset($this->data['roof_terrace']) && $this->data['roof_terrace'] == 'true') {
            $amenities[] = 2547;
        }

        if (isset($this->data['balcony']) && $this->data['balcony'] == 'true') {
            $amenities[] = 2549;
        }

        if (isset($this->data['garage']) && $this->data['garage'] == 'true') {
            $amenities[] = 2548;
        }

        if (isset($this->data['drive_way']) && $this->data['drive_way'] == 'true') {
            $amenities[] = 2544;
        }

        if (isset($this->data['off_road_parking']) && $this->data['off_road_parking'] == 'true') {
            $amenities[] = 2550;
        }

        if (isset($this->data['private_car_park']) && $this->data['private_car_park'] == 'true') {
            $amenities[] = 2551;
        }

        return implode(',', $amenities);
    }

    public function update()
    {
        if (count($this->data) > 0) {
            $AdRepository = $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $this->ad_id));

            $rent_per_id = null;
            $sc = $this->getSecondLevelParent($AdRepository->getCategory()->getId());

            $propertyRepository = $this->em->getRepository('FaAdBundle:AdProperty')->findOneBy(array('ad' => $this->ad_id));
            $metaData = array();

            if (!$propertyRepository) {
                $propertyRepository = new AdProperty();
                $propertyRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
            }

            if (isset($this->data['bedroom_id']) && $this->data['bedroom_id'] != '') {
                $propertyRepository->setNumberOfBedroomsId($this->data['bedroom_id']);
            }

            if (isset($this->data['bathrooms_id']) && $this->data['bathrooms_id'] != '') {
                $metaData['number_of_bathrooms_id'] = $this->data['bathrooms_id'];
            }

            if (isset($this->data['furnishings']) && $this->data['furnishings'] != '') {
                $metaData['furnishing_id'] = $this->data['furnishings'];
            }

            if ($sc['name'] ==  'For Rent') {
                $rentPer =  $this->getRentPerForRent($AdRepository->getPriceOldText());
                if ($rentPer) {
                    echo $AdRepository->getStatus()->getId();
                    $metaData['rent_per_id'] = $rentPer;
                }
            } elseif ($sc['name'] ==  'Share') {
                $rentPer =  $this->getRentPerForShare($AdRepository->getPriceOldText());
                if ($rentPer) {
                    $metaData['rent_per_id'] = $rentPer;
                }
            }

            if ($this->getAmenities()) {
                $propertyRepository->setAmenitiesId($this->getAmenities());
            }

            $propertyRepository->setMetaData(serialize($metaData));

            $this->em->persist($propertyRepository);
            echo "Dimension updated for ".$propertyRepository->getAd()->getId()."\n";
        }
    }

    /**
     * get first level parent
     *
     * @param integer $category_id
     *
     * @return object
     */
    private function getSecondLevelParent($category_id)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($category_id, false, $this->container);
        $ak = array();

        $ak = array_keys($cat);
        if (isset($ak['1'])) {
            return $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById($ak['1'], $this->container);
        } else {
            return null;
        }
    }
}
