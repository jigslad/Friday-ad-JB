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

use Fa\Bundle\AdBundle\Entity\AdMotors;

// use Fa\Bundle\AdBundle\Entity\Fa\Bundle\AdBundle\Entity;

/**
 * Fa\Bundle\AdBundle\lib\Migration
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class Car
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

    //Van,Kit Car,Tourer
    public function getBodyTypeId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['Hatchback']   = 6357;
        $cType['Saloon']   = 6365;
        $cType['Coupe']   = 6361;
        $cType['4X4']   = 6363;
        $cType['MPV']   = 6364;
        $cType['Estate']   = 6362;
        $cType['Sport']   = 6366;
        $cType['Convertible']   = 6360;

        if (isset($cType[$string])) {
            return $cType[$string];
        } else {
            //  echo $string."\n";
        }
    }

    public function getColorId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['Red'] = 6352;
        $cType['Blue'] = 6341;
        $cType['White'] = 6354;
        $cType['Black'] = 6340;
        $cType['Gold'] = 6344;
        $cType['Yellow'] = 6355;
        $cType['Silver'] = 6338;
        $cType['Grey'] = 6346;
        $cType['Green'] = 6345;
        $cType['Purple'] = 6348;
        $cType['Orange'] = 6350;
        $cType['Cream'] = 6339;
        $cType['Pink'] = 6351;
        $cType['Turquoise'] = 6353;
        $cType['Maroon'] = 6347;
        $cType['Bronze'] = 6342;


        if (isset($cType[$string])) {
            return $cType[$string];
        } else {
            // echo $string."\n";
        }
    }

    //Dual Fuel, Unleaded, Leaded, LPG
    public function getFuelTypeId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['Petrol'] = 6367;
        $cType['Diesel'] = 6368;
        $cType['Electric'] = 6370;
        $cType['Hybrid'] = 6369;

        if (isset($cType[$string])) {
            return $cType[$string];
        } else {
            // echo $string."\n";
        }
    }

    public function getTransmissionId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['Manual']         = 6371;
        $cType['Automatic']      = 6372;
        $cType['Semi-Automatic'] = 6374;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    public function init()
    {
        $string1    = null;
        $string     = null;
        $this->data = array();

        if ($this->meta_text != "") {
            try {
                $string1 = preg_replace('/fieldValue="\/><\/TransMeta>/', 'fieldValue="" /></TransMeta>', $this->meta_text);
                $string = simplexml_load_string($string1);
                libxml_use_internal_errors(true);
            } catch (\Exception $e) {
                return 0;
            }

            if (isset($string->MotorsTransMeta->bodyType)) {
                $this->data['old_body_type'] = trim((string) $string->MotorsTransMeta->bodyType);
                $this->data['body_type_id']  = $this->getBodyTypeId(trim((string) $string->MotorsTransMeta->bodyType));
            }

            if (isset($string->MotorsTransMeta->colour)) {
                $this->data['old_color'] =  trim((string) $string->MotorsTransMeta->colour);
                $this->data['colour_id'] =  $this->getColorId(trim((string) $string->MotorsTransMeta->colour));
            }

            if (isset($string->MotorsTransMeta->fuelType)) {
                $this->data['old_fuel_type'] =  trim((string) $string->MotorsTransMeta->fuelType);
                $this->data['fuel_type_id'] = $this->getFuelTypeId(trim((string) $string->MotorsTransMeta->fuelType));
            }

            if (isset($string->MotorsTransMeta->engineSizeLitre)) {
                $this->data['engine_size']  = trim((string) $string->MotorsTransMeta->engineSizeLitre);
            }

            if (isset($string->MotorsTransMeta->mileage)) {
                $this->data['mileage']  =  trim((string) $string->MotorsTransMeta->mileage);
            }

            if (isset($string->MotorsTransMeta->make)) {
                $this->data['old_make']  = trim((string) $string->MotorsTransMeta->make);
            }

            if (isset($string->MotorsTransMeta->model)) {
                $this->data['old_model']  = trim((string) $string->MotorsTransMeta->model);
            }

            if (isset($string->MotorsTransMeta->regDate)) {
                $this->data['reg_year']  =  date('Y', strtotime(trim((string) $string->MotorsTransMeta->regDate)));
            }

            if (isset($string->MotorsTransMeta->transmission)) {
                $this->data['transmission_id'] = $this->getTransmissionId(trim((string) $string->MotorsTransMeta->transmission));
                $this->data['old_transmission'] = trim((string) $string->MotorsTransMeta->transmission);
            }

            if (isset($string->MotorsTransMeta->hasAbs) && (string) $string->MotorsTransMeta->hasAbs == "true") {
                $this->data['features_id'][] = 1658;
            }

            if (isset($string->MotorsTransMeta->hasAirbags) && (string) $string->MotorsTransMeta->hasAirbags == "true") {
                $this->data['features_id'][] = 1655;
            }

            if (isset($string->MotorsTransMeta->hasAlarm) && (string) $string->MotorsTransMeta->hasAlarm == "true") {
                $this->data['features_id'][] = 1662;
            }

            if (isset($string->MotorsTransMeta->hasAlloyWheels) && (string) $string->MotorsTransMeta->hasAlloyWheels == "true") {
                $this->data['features_id'][] = 1664;
            }

            if (isset($string->MotorsTransMeta->hasCdPlayer) && (string) $string->MotorsTransMeta->hasCdPlayer == "true") {
                $this->data['features_id'][] = 1664;
            }

            if (isset($string->MotorsTransMeta->hasCentralLocking) && (string) $string->MotorsTransMeta->hasCentralLocking == "true") {
                $this->data['features_id'][] = 1653;
            }

            if (isset($string->MotorsTransMeta->hasCruiseControl) && (string) $string->MotorsTransMeta->hasCruiseControl == "true") {
                $this->data['features_id'][] = 1659;
            }

            if (isset($string->MotorsTransMeta->hasDvdPlayer) && (string) $string->MotorsTransMeta->hasDvdPlayer == "true") {
                $this->data['features_id'][] = 1666;
            }

            if (isset($string->MotorsTransMeta->hasElectricSeats) && (string) $string->MotorsTransMeta->hasElectricSeats == "true") {
                $this->data['features_id'][] = 1672;
            }

            if (isset($string->MotorsTransMeta->hasElectricWindows) && (string) $string->MotorsTransMeta->hasElectricWindows == "true") {
                $this->data['features_id'][] = 1652;
            }

            if (isset($string->MotorsTransMeta->hasElectricMirrors) && (string) $string->MotorsTransMeta->hasElectricMirrors == "true") {
                $this->data['features_id'][] = 1667;
            }

            if (isset($string->MotorsTransMeta->hasHeatedSeats) && (string) $string->MotorsTransMeta->hasHeatedSeats == "true") {
                $this->data['features_id'][] = 1668;
            }

            if (isset($string->MotorsTransMeta->hasImmobiliser) && (string) $string->MotorsTransMeta->hasImmobiliser == "true") {
                $this->data['features_id'][] = 1663;
            }

            if (isset($string->MotorsTransMeta->hasParkingSensor) && (string) $string->MotorsTransMeta->hasParkingSensor == "true") {
                $this->data['features_id'][] = 1673;
            }

            if (isset($string->MotorsTransMeta->hasRoofbars) && (string) $string->MotorsTransMeta->hasRoofbars == "true") {
                $this->data['features_id'][] = 1671;
            }

            if (isset($string->MotorsTransMeta->hasSatNav) && (string) $string->MotorsTransMeta->hasSatNav == "true") {
                $this->data['features_id'][] = 1669;
            }

            if (isset($string->MotorsTransMeta->hasSunroof) && (string) $string->MotorsTransMeta->hasSunroof == "true") {
                $this->data['features_id'][] = 1670;
            }

            if (isset($string->MotorsTransMeta->hasTracker) && (string) $string->MotorsTransMeta->hasTracker == "true") {
                $this->data['features_id'][] = 1674;
            }
        }
    }

    public function update()
    {
        if (count($this->data) > 0) {
            $carRepository = $this->em->getRepository('FaAdBundle:AdMotors')->findOneBy(array('ad' => $this->ad_id));

            if (!$carRepository) {
                $carRepository = new AdMotors();
                $carRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
            }

            if (isset($this->data['body_type_id']) && $this->data['body_type_id']) {
                $carRepository->setBodyTypeId($this->data['body_type_id']);
            }

            if (isset($this->data['old_body_type']) && $this->data['old_body_type']) {
                $carRepository->setOldBodyType($this->data['old_body_type']);
            }

            if (isset($this->data['colour_id']) && $this->data['colour_id']) {
                $carRepository->setColourId($this->data['colour_id']);
            }

            if (isset($this->data['old_color']) && $this->data['old_color']) {
                $carRepository->setOldColor($this->data['old_color']);
            }

            if (isset($this->data['fuel_type_id']) && $this->data['fuel_type_id']) {
                $carRepository->setFuelTypeId($this->data['fuel_type_id']);
            }

            if (isset($this->data['old_fuel_type']) && $this->data['old_fuel_type']) {
                $carRepository->setOldFuelType($this->data['old_fuel_type']);
            }

            if (isset($this->data['transmission_id']) && $this->data['transmission_id']) {
                $carRepository->setTransmissionId($this->data['transmission_id']);
            }

            if (isset($this->data['old_transmission']) && $this->data['old_transmission']) {
                $carRepository->setOldTransmission($this->data['old_transmission']);
            }

            if (isset($this->data['old_make']) && $this->data['old_make']) {
                $carRepository->setOldMake($this->data['old_make']);
            }

            if (isset($this->data['old_model']) && $this->data['old_model']) {
                $carRepository->setOldModel($this->data['old_model']);
            }

            $metaData = array();

            if (isset($this->data['engine_size']) && $this->data['engine_size']) {
                $metaData['engine_size'] = $this->data['engine_size'];
            }

            if (isset($this->data['reg_year']) && $this->data['reg_year']) {
                $metaData['reg_year'] = $this->data['reg_year'];
            }

            if (isset($this->data['mileage']) && $this->data['mileage']) {
                $metaData['mileage'] = $this->data['mileage'];
            }

            if (isset($this->data['features_id']) && count($this->data['features_id']) > 0) {
                $metaData['features_id'] = implode(',', $this->data['features_id']);
            }

            if (count($metaData) > 0) {
                $carRepository->setMetaData(serialize($metaData));
            }

            $this->em->persist($carRepository);
            echo "Dimension updated for ".$carRepository->getAd()->getId()."\n";
        }
    }
}
