<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\AdBundle\DataFixtures\ORM\LoadPaaFieldData;
use Fa\Bundle\AdBundle\Repository\PaaFieldRuleRepository;
use Fa\Bundle\AdBundle\Entity\PaaField;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadPaaFieldMotorsData extends LoadPaaFieldData
{
    /**
     * Entity manager.
     *
     * @var object
     */
    private $_em;

    /**
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\FixtureInterface::load()
     *
     * @param object $em
     */
    public function load(ObjectManager $em)
    {
        return false;

        $this->_em = $em;

        // Add common fields
        //$this->addCommonPaaFields($this->_em);

        // Add top level category paa field rules for Motors
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Motors', 'lvl' => 1));
        if ($category) {
            $this->addMotorsCommonPaaFields($this->_em, $category);
            $this->addMotorsDimensionPaaFields($category);

            // Add rule for top level category only
            $this->addTopLevelCategoryPaaFieldRules($category, $this->_em, array(), array(), array('is_new' => 0, 'qty' => 0, 'price_text' => 0, 'ad_type_id' => 0, 'delivery_method_option_id' => 0, 'payment_method_id' => 0, 'has_reg_no' => 0, 'reg_no' => 0));

            // Add paa field rules
            $this->addBoatsPaaFieldsRule();
            $this->addCarsPaaFieldsRule();
            $this->addCommercialVehiclesPaaFieldsRule();
            $this->addFarmPaaFieldsRule();
            $this->addHorseboxesAndTrailersPaaFieldsRule();
            $this->addMotorbikesPaaFieldsRule();
            $this->addMotorhomesAndCaravansPaaFieldsRule();
            $this->addCaravansPaaFieldsRule();
            $this->addStaticCaravansPaaFieldsRule();
            $this->addMotorhomesPaaFieldsRule();
            $this->addMotorsServicesPaaFieldsRule();
            $this->addPartsAndAccessoriesServicesPaaFieldsRule();
        }
    }

    /**
     * Add boats paa field rule.
     */
    private function addBoatsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Boats', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'reg_year' => 0,
                'transmission_id' => 0,
                'engine_size' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'no_of_owners' => 0,
                'mileage' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'features_id' => 0,
                'service_history_id' => 0,
                'mot_expiry_month' => 0,
                'mot_expiry_year' => 0,
                'fuel_economy' => 0,
                'make_id' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'berth_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldRuleStep = array (
                'manufacturer_id' => 2,
                'model' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'fuel_type_id' => 4,
                'year_built' => 4,
                'boat_length' => 4,
                'condition_id' => 4,
                'personalized_title' => 4,
                'location' => 4
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), array(), array(), array(), array(), array(), $fieldRuleStep);
        }
    }

    /**
     * Add cars paa field rule.
     */
    private function addCarsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Cars', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'manufacturer_id' => 0,
                'model' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'make_id' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'berth_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 1,
                'reg_no' => 1,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'location' => 1,
                'title' => 2,
                'personalized_title' => 3,
                'description' => 4,
                'service_history_id' => 5,
                'condition_id' => 6,
                'mot_expiry_month' => 7,
                'mot_expiry_year' => 8,
                'features_id' => 11,
            );

            $fieldRuleMinValue = array (
                'no_of_doors' => 1,
                'no_of_seats' => 1,
                'no_of_owners' => 1,
                //'ncap_rating' => 1,
                //'top_speed' => 1,
                //'fuel_economy' => 1,
                //'062mph' => 1,
                'engine_size' => 1
            );

            $fieldRuleMaxValue = array (
                'no_of_doors' => 10,
                'no_of_seats' => 20,
                'no_of_owners' => 100,
                //'ncap_rating' => 5,
                //'top_speed' => 200,
                //'fuel_economy' => 200,
                //'062mph' => 100,
                'engine_size' => 10000
            );

            $fieldRuleMinMaxType = array (
                'no_of_doors' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                'no_of_seats' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                'no_of_owners' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'ncap_rating' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'top_speed' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'fuel_economy' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'062mph' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                'engine_size' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE
            );

            $fieldRuleLabel = array(
                'no_of_doors'  => 'No. of doors',
                'no_of_seats'  => 'No. of seats',
                'no_of_owners' => 'No. of owners',
                'mileage'      => 'How many miles are on the clock?',
                'mot_expiry_month' => 'MOT expiry month'
            );

            $fieldRuleRequired = array(
                'description' => 0
            );

            $fieldRuleStep = array (
                'colour_id' => 2,
                'body_type_id' => 2,
                'reg_year' => 2,
                'fuel_type_id' => 2,
                'transmission_id' => 2,
                'no_of_doors' => 2,
                'no_of_seats' => 2,
                'mileage' => 2,
                'engine_size' => 2,
                'price' => 2,
                'has_reg_no' => 2,
                'reg_no' => 2,
                'title' => 4,
                'description' => 4,
                'location' => 4,
                'fuel_economy' => 2,
                '062mph' => 2,
                'top_speed' => 2,
                'ncap_rating' => 2,
                'features_id' => 4,
                'service_history_id' => 4,
                'condition_id' => 4,
                'mot_expiry_month' => 4,
                'mot_expiry_year' => 4,
                'co2_emissions' => 2,
                'personalized_title' => 4,
                'no_of_owners' => 4
            );

            $fieldRuleDefaultValue = array(
                'has_reg_no' => 1,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, $fieldRuleDefaultValue, $fieldRuleMinValue, $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add commercial vehicles paa field rule.
     */
    private function addCommercialVehiclesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Commercial Vehicles', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'manufacturer_id' => 0,
                'model' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'make_id' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'berth_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 1,
                'reg_no' => 1,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'location' => 1,
                'title' => 2,
                'personalized_title' => 3,
                'description' => 4,
                'service_history_id' => 5,
                'condition_id' => 6,
                'mot_expiry_month' => 7,
                'mot_expiry_year' => 8,
                'features_id' => 11,
            );

            $fieldRuleMinValue = array (
                'no_of_doors' => 1,
                'no_of_seats' => 1,
                'no_of_owners' => 1,
                //'ncap_rating' => 1,
                //'top_speed' => 1,
                //'fuel_economy' => 1,
                //'062mph' => 1,
                'engine_size' => 1
            );

            $fieldRuleMaxValue = array (
                'no_of_doors' => 10,
                'no_of_seats' => 20,
                'no_of_owners' => 100,
                //'ncap_rating' => 5,
                //'top_speed' => 200,
                //'fuel_economy' => 200,
                //'062mph' => 100,
                'engine_size' => 10000
            );

            $fieldRuleMinMaxType = array (
                'no_of_doors' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                'no_of_seats' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                'no_of_owners' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'ncap_rating' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'top_speed' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'fuel_economy' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'062mph' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                'engine_size' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE
            );

            $fieldRuleLabel = array(
                'no_of_doors'  => 'No. of doors',
                'no_of_seats'  => 'No. of seats',
                'no_of_owners' => 'No. of owners',
                'mileage'      => 'How many miles are on the clock?',
                'mot_expiry_month' => 'MOT expiry month'
            );

            $fieldRuleRequired = array(
                'description' => 0
            );

            $fieldRuleStep = array (
                'colour_id' => 2,
                'body_type_id' => 2,
                'reg_year' => 2,
                'fuel_type_id' => 2,
                'transmission_id' => 2,
                'no_of_doors' => 2,
                'no_of_seats' => 2,
                'mileage' => 2,
                'engine_size' => 2,
                'price' => 2,
                'has_reg_no' => 2,
                'reg_no' => 2,
                'title' => 4,
                'description' => 4,
                'location' => 4,
                'fuel_economy' => 2,
                '062mph' => 2,
                'top_speed' => 2,
                'ncap_rating' => 2,
                'features_id' => 4,
                'service_history_id' => 4,
                'condition_id' => 4,
                'mot_expiry_month' => 4,
                'mot_expiry_year' => 4,
                'co2_emissions' => 2,
                'personalized_title' => 4,
                'no_of_owners' => 4
            );

            $fieldRuleDefaultValue = array(
                'has_reg_no' => 1,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, $fieldRuleDefaultValue, $fieldRuleMinValue, $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add farm paa field rule.
     */
    private function addFarmPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Farm', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'model' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'make_id' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'engine_size' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'features_id' => 0,
                'service_history_id' => 0,
                'mot_expiry_month' => 0,
                'mot_expiry_year' => 0,
                'fuel_economy' => 0,
                'make_id' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'berth_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldRuleMinValue = array (
                'no_of_owners' => 1
            );

            $fieldRuleMaxValue = array (
                'no_of_owners' => 100
            );

            $fieldRuleMinMaxType = array (
                'no_of_owners' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE
            );

            $fieldRuleLabel = array(
                'no_of_owners' => 'No. of owners',
                'mileage'      => 'How many miles are on the clock?'
            );

            $fieldRuleStep = array (
                'manufacturer_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'transmission_id' => 4,
                'fuel_type_id' => 4,
                'reg_year' => 4,
                'mileage' => 4,
                'no_of_owners' => 4,
                'condition_id' => 4,
                'location' => 4,
                'personalized_title' => 4
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), $fieldRuleMinValue, $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add horseboxes and trailers paa field rule.
     */
    private function addHorseboxesAndTrailersPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Horseboxes And Trailers', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'model' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'engine_size' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'no_of_owners' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'service_history_id' => 0,
                'mot_expiry_month' => 0,
                'mot_expiry_year' => 0,
                'fuel_economy' => 0,
                'berth_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldType = array(
                'make_id' => 'text_autosuggest'
            );

            $fieldRuleLabel = array(
                'mileage'=> 'How many miles are on the clock?'
            );

            $fieldRuleStep = array (
                'make_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'number_of_stalls_id' => 4,
                'living_accommodation_id' => 4,
                'condition_id' => 4,
                'mileage' => 4,
                'reg_year' => 4,
                'tonnage_id' => 4,
                'features_id' => 4,
                'location' => 4,
                'personalized_title' => 4
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), array(), array(), $fieldType, $fieldRuleStep);
        }
    }

    /**
     * Add motorbikes paa field rule.
     */
    private function addMotorbikesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Motorbikes', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'manufacturer_id' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'fuel_economy' => 0,
                'ncap_rating' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'berth_id' => 0,
                'part_of_vehicle_id' => 0,
                'model' => 0,
                'has_reg_no' => 1,
                'reg_no' => 1,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'location' => 1,
                'title' => 2,
                'personalized_title' => 3,
                'description' => 4,
                'service_history_id' => 5,
                'condition_id' => 6,
                'mot_expiry_month' => 7,
                'mot_expiry_year' => 8,
                'features_id' => 9,
            );

            $fieldRuleMinValue = array (
                'no_of_owners' => 1,
                //'top_speed' => 1,
                //'062mph' => 1,
                'engine_size' => 1
            );

            $fieldRuleMaxValue = array (
                'no_of_owners' => 100,
                //'top_speed' => 200,
                //'062mph' => 100,
                'engine_size' => 10000
            );

            $fieldRuleMinMaxType = array (
                'no_of_owners' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'top_speed' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                //'062mph' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE,
                'engine_size' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE
            );

            $fieldRuleLabel = array(
                'no_of_owners' => 'No. of owners',
                'mileage'=> 'How many miles are on the clock?',
                'mot_expiry_month' => 'MOT expiry month'
            );

            $fieldRuleRequired = array(
                'description' => 0
            );

            $fieldRuleStep = array (
                'make_id' => 2,
                'model_id' => 2,
                'reg_year' => 2,
                'mileage' => 2,
                'engine_size' => 2,
                'price' => 2,
                'has_reg_no' => 2,
                'reg_no' => 2,
                'title' => 4,
                'description' => 4,
                'service_history_id' => 4,
                'mot_expiry_month' => 4,
                'mot_expiry_year' => 4,
                'condition_id' => 4,
                'features_id' => 4,
                'top_speed' => 2,
                '062mph' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'no_of_owners' => 4
            );

            $fieldRuleDefaultValue = array(
                'has_reg_no' => 1,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, $fieldRuleDefaultValue, $fieldRuleMinValue, $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add motorhomes and caravans paa field rule.
     */
    private function addMotorhomesAndCaravansPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Motorhomes and Caravans', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'manufacturer_id' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'transmission_id' => 0,
                'engine_size' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'no_of_owners' => 0,
                'mileage' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'service_history_id' => 0,
                'mot_expiry_month' => 0,
                'mot_expiry_year' => 0,
                'fuel_economy' => 0,
                'ncap_rating' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldRuleLabel = array(
                'berth_id'=> 'Berths'
            );

            $fieldRuleStep = array (
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'berth_id' => 4,
                'reg_year' => 4,
                'features_id' => 4,
                'location' => 4,
                'personalized_title' => 4,
                'condition_id' => 4,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), array(), array(), array(), $fieldRuleStep);
        }
    }

    /**
     * Add caravans paa field rule.
     */
    private function addCaravansPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Caravans', 'lvl' => 3));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'manufacturer_id' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'transmission_id' => 0,
                'engine_size' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'no_of_owners' => 0,
                'mileage' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'service_history_id' => 0,
                'mot_expiry_month' => 0,
                'mot_expiry_year' => 0,
                'fuel_economy' => 0,
                'ncap_rating' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldType = array(
                'make_id' => 'text_autosuggest'
            );

            $fieldRuleLabel = array(
                'berth_id'=> 'Berths'
            );

            $fieldRuleStep = array (
                'make_id' => 2,
                'model' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'berth_id' => 4,
                'reg_year' => 4,
                'features_id' => 4,
                'location' => 4,
                'personalized_title' => 4,
                'condition_id' => 4,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), array(), array(), $fieldType, $fieldRuleStep);
        }
    }

    /**
     * Add caravans paa field rule.
     */
    private function addStaticCaravansPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Static Caravans', 'lvl' => 3));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'manufacturer_id' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'transmission_id' => 0,
                'engine_size' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'no_of_owners' => 0,
                'mileage' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'service_history_id' => 0,
                'mot_expiry_month' => 0,
                'mot_expiry_year' => 0,
                'fuel_economy' => 0,
                'ncap_rating' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldType = array(
                'make_id' => 'text_autosuggest'
            );

            $fieldRuleLabel = array(
                'berth_id'=> 'Berths'
            );

            $fieldRuleStep = array (
                'make_id' => 2,
                'model' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'berth_id' => 4,
                'reg_year' => 4,
                'features_id' => 4,
                'location' => 4,
                'personalized_title' => 4,
                'condition_id' => 4,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), array(), array(), $fieldType, $fieldRuleStep);
        }
    }

    /**
     * Add motorhomes paa field rule.
     */
    private function addMotorhomesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Motorhomes', 'lvl' => 3));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'manufacturer_id' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'transmission_id' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'no_of_owners' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'fuel_economy' => 0,
                'ncap_rating' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldType = array(
                'make_id'  => 'text_autosuggest',
                'model_id' => 'text_autosuggest'
            );

            $fieldRuleMinValue = array (
                'engine_size' => 1
            );

            $fieldRuleMaxValue = array (
                'engine_size' => 10000
            );

            $fieldRuleMinMaxType = array (
                'engine_size' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE
            );

            $fieldRuleLabel = array(
                'mileage'=> 'How many miles are on the clock?',
                'berth_id'=> 'Berths',
                'mot_expiry_month' => 'MOT expiry month'
            );

            $fieldRuleStep = array (
                'make_id' => 2,
                'model_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'berth_id' => 4,
                'reg_year' => 4,
                'features_id' => 4,
                'engine_size' => 4,
                'mileage' => 4,
                'service_history_id' => 4,
                'mot_expiry_month' => 4,
                'mot_expiry_year' => 4,
                'location' => 4,
                'personalized_title' => 4,
                'condition_id' => 4,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), $fieldRuleMinValue, $fieldRuleMaxValue, $fieldRuleMinMaxType, $fieldType, $fieldRuleStep);
        }
    }

    /**
     * Add motors services paa field rule.
     */
    private function addMotorsServicesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Motors Services', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'manufacturer_id' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'condition_id' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'transmission_id' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'no_of_owners' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'reg_year' => 0,
                'fuel_economy' => 0,
                'ncap_rating' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'part_of_vehicle_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'part_manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldRuleStep = array (
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'features_id' => 4,
                'location' => 4,
                'personalized_title' => 4
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), array(), array(), array(), array(), array(), $fieldRuleStep);
        }
    }

    /**
     * Add parts and accessories paa field rule.
     */
    private function addPartsAndAccessoriesServicesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Parts And Accessories', 'lvl' => 2));

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'ad_type_id' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'year_built' => 0,
                'boat_length' => 0,
                'condition_id' => 0,
                'colour_id' => 0,
                'body_type_id' => 0,
                'transmission_id' => 0,
                'no_of_doors' => 0,
                'no_of_seats' => 0,
                'no_of_owners' => 0,
                '062mph' => 0,
                'top_speed' => 0,
                'ncap_rating' => 0,
                'reg_year' => 0,
                'fuel_economy' => 0,
                'ncap_rating' => 0,
                'number_of_stalls_id' => 0,
                'living_accommodation_id' => 0,
                'tonnage_id' => 0,
                'features_id' => 0,
                'has_reg_no' => 0,
                'reg_no' => 0,
                'manufacturer_id' => 0
            );

            $fieldOrderRequired = array(
                'price' => 34,
                'features_id' => 35
            );

            $fieldRuleLabel = array(
                'part_manufacturer_id' => 'Part Manufacturer',
                'part_of_vehicle_id' => 'Part of the vehicle',
            );

            $fieldRuleStep = array (
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'part_manufacturer_id' => 2,
                'part_of_vehicle_id' => 2,
                'location' => 4,
                'personalized_title' => 4
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), array(), array(), array(), $fieldRuleStep);
        }
    }


    /**
     * Add motors dimension paa fields.
     *
     * @param string $category
     */
    private function addMotorsDimensionPaaFields($category)
    {
        $catDimensionFields = array();
        $dimensions         = $this->getCategoryDimensions($category, $this->_em);

        if (count($dimensions)) {
            foreach ($dimensions as $dimension) {
                $dimensionId   = $dimension['id'];
                $dimensionName = $dimension['name'];
                $categoryId    = $dimension['category_id'];
                $isIndex       = $dimension['is_index'] ? $dimension['is_index'] : 0;

                $dimensionField = str_replace(array('(', ')', ',', '?', '|', '.', '/', '\\', '*', '+', '-', '"', "'"), '', $dimensionName);
                $dimensionField = str_replace(' ', '_', strtolower($dimensionField));

                if ($dimensionField == 'model' && (!in_array($categoryId, array(CategoryRepository::MOTORBIKES_ID, CategoryRepository::MOTORHOMES_ID)))) {
                    $field                                                            = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['field']                 = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } elseif (in_array($dimensionField, array('engine_size', 'no_of_doors', 'no_of_seats', 'no_of_owners', 'mileage', 'top_speed', 'ncap_rating', 'co2_emissions'))) {
                    $field                                                            = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['field']                 = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text_int';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } elseif (in_array($dimensionField, array('fuel_economy', '062mph', 'boat_length'))) {
                    $field                                                            = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['field']                 = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text_float';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } elseif (in_array($dimensionField, array('mot_expiry'))) {
                    foreach (array('Month', 'Year') as $dimensionOption) {
                        $field                                                            = $dimensionField.'_'.strtolower($dimensionOption);
                        $catDimensionFields[$categoryId][$field]['field']                 = $field;
                        $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName.' '.$dimensionOption;
                        $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_range';
                        $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                        $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    }
                } elseif (in_array($dimensionField, array('reg_year', 'year_built'))) {
                    $field                                                            = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['field']                 = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_range';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } elseif (in_array($dimensionField, array('features'))) {
                    $field                                                            = $dimensionField.'_id';
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_checkbox';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } else {
                    $field = $dimensionField.'_id';

                    if ($field == 'manufacturer_id' || $field == 'make_id' || ($field == 'model_id' && $categoryId == CategoryRepository::MOTORHOMES_ID) || $field == 'part_of_vehicle_id' || $field == 'part_manufacturer_id' || $field == 'colour_id') {
                        $catDimensionFields[$categoryId][$field]['field']                 = $field;
                        $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                        $catDimensionFields[$categoryId][$field]['field_type']            = 'text_autosuggest';
                        $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                        $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    } else {
                        $catDimensionFields[$categoryId][$field]['field']                 = $field;
                        $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                        $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_single';
                        $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                        $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    }
                }
            }

            // Save dimensions paa fields
            $this->saveDimensionPaaFields($this->_em, $catDimensionFields);
        }
    }

    /**
     * Save dimension paa fields.
     *
     * @param object $em
     * @param object $category
     */
    protected function addMotorsCommonPaaFields($em, $category)
    {
        $commonFields = array();

        $field                                         = 'has_reg_no';
        $commonFields[$field]['field']                 = $field;
        $commonFields[$field]['label']                 = 'Do you know your registration number?';
        $commonFields[$field]['field_type']            = 'choice_boolean';
        $commonFields[$field]['is_index']              = 0;

        $field                                         = 'reg_no';
        $commonFields[$field]['field']                 = $field;
        $commonFields[$field]['label']                 = 'Registration number';
        $commonFields[$field]['field_type']            = 'text';
        $commonFields[$field]['is_index']              = 0;

        foreach ($commonFields as $field => $fieldOptions) {
            $paaField = new PaaField();
            $paaField->setField($field);
            $paaField->setLabel($fieldOptions['label']);
            $paaField->setFieldType($fieldOptions['field_type']);
            $paaField->setIsIndex($fieldOptions['is_index']);
            $paaField->setCategory($category);

            $em->persist($paaField);
            $em->flush();
        }
    }

    /**
     * Get order.
     *
     * @see \Doctrine\Common\DataFixtures\OrderedFixtureInterface::getOrder()
     *
     * @return integer
     */
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
}
