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
class LoadPaaFieldPropertyData extends LoadPaaFieldData
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
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::PROPERTY_ID);
        if ($category) {
            $this->addPropertyDimensionPaaFields($category);

            // Add rule for top level category only
            $this->addMainCategoryPaaFieldRules($category);

            // Add paa field rules
            $this->addForRentPaaFieldsRule();
            $this->addSharePaaFieldsRule();
            $this->addForSalePaaFieldsRule();
        }
    }

    /**
     * Add property category paa field rule.
     *
     * @param object $category
     */
    private function addMainCategoryPaaFieldRules($category)
    {
        if ($category) {
            $fieldRuleStatus = array(
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'has_video' => 0
            );

            $fieldRuleRequired = array(
                'is_new' => 0,
                'qty' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0
            );

            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
            );

            $fieldRuleLabel = array(
                'ad_type_id' => 'Ad type',
                'number_of_bedrooms_id' => 'Number of bedrooms',
                'number_of_bathrooms_id' => 'Number of bathrooms',
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'number_of_bedrooms_id' => 4,
                'number_of_bathrooms_id' => 4,
                'amenities_id' => 4
            );

            $fieldRuleMaxValue = array (
                'title' => 100,
                'description' => 2000,
                'personalized_title' => 140,
            );

            $fieldRuleMinMaxType = array (
                'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            );

            $this->addTopLevelCategoryPaaFieldRules($category, $this->_em, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add for rent paa field rule.
     */
    private function addForRentPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::PROPERTY_FOR_RENT_ID);

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'has_video' => 0
            );

            $fieldRuleRequired = array(
                'is_new' => 0,
                'qty' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'rent_per_id' => 1,
            );

            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
                'rent_per_id' => 6,
            );

            $fieldRuleLabel = array(
                'ad_type_id' => 'Ad type',
                'number_of_bedrooms_id' => 'Number of bedrooms',
                'number_of_bathrooms_id' => 'Number of bathrooms',
                'bills_included_in_rent_id' => 'Bills included in rent',
                'date_available' => 'Date available',
                'rental_length' => 'Rental length in months',
                'pets_allowed_id' => 'Pets allowed?',
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'rent_per_id' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'number_of_bedrooms_id' => 4,
                'number_of_bathrooms_id' => 4,
                'amenities_id' => 4,
                'furnishing_id' => 4,
                'bills_included_in_rent_id' => 4,
                'deposit' => 4,
                'date_available' => 4,
                'rental_length' => 4,
                'smoking_allowed_id' => 4,
                'pets_allowed_id' => 4,
                'dss_tenants_allowed_id' => 4,
            );

            $fieldRuleMaxValue = array (
                'title' => 100,
                'description' => 2000,
                'personalized_title' => 140,
            );

            $fieldRuleMinMaxType = array (
                'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add share paa field rule.
     */
    private function addSharePaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::PROPERTY_SHARE_ID);

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'has_video' => 0
            );

            $fieldRuleRequired = array(
                'is_new' => 0,
                'qty' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'rent_per_id' => 1,
            );

            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
                'rent_per_id' => 6,
                'number_of_rooms_in_property_id' => 25,
                'number_of_rooms_available_id' => 26,
            );

            $fieldRuleLabel = array(
                'ad_type_id' => 'Ad type',
                'number_of_bedrooms_id' => 'Number of bedrooms',
                'number_of_bathrooms_id' => 'Number of bathrooms',
                'bills_included_in_rent_id' => 'Bills included in rent',
                'date_available' => 'Date available',
                'rental_length' => 'Rental length in months',
                'pets_allowed_id' => 'Pets allowed?',
                'number_of_rooms_in_property_id' => 'Number of rooms in property',
                'number_of_rooms_available_id' => 'Number of rooms available',
                'room_size_id' => 'Room size'
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'rent_per_id' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'number_of_bedrooms_id' => 4,
                'number_of_bathrooms_id' => 4,
                'amenities_id' => 4,
                'furnishing_id' => 4,
                'bills_included_in_rent_id' => 4,
                'deposit' => 4,
                'date_available' => 4,
                'rental_length' => 4,
                'smoking_allowed_id' => 4,
                'pets_allowed_id' => 4,
                'dss_tenants_allowed_id' => 4,
                'number_of_rooms_in_property_id' => 4,
                'number_of_rooms_available_id' => 4,
                'rooms_for_id' => 4,
                'room_size_id' => 4,
            );

            $fieldRuleMaxValue = array (
                'title' => 100,
                'description' => 2000,
                'personalized_title' => 140,
            );

            $fieldRuleMinMaxType = array (
                'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add forsale paa field rule.
     */
    private function addForSalePaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::PROPERTY_FOR_SALE_ID);

        if ($category) {
            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'has_video' => 0
            );

            $fieldRuleRequired = array(
                'is_new' => 0,
                'qty' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0
            );

            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
            );

            $fieldRuleLabel = array(
                'ad_type_id' => 'Ad type',
                'number_of_bedrooms_id' => 'Number of bedrooms',
                'number_of_bathrooms_id' => 'Number of bathrooms',
                'lease_type_id' => 'Lease type'
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'rent_per_id' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'number_of_bedrooms_id' => 4,
                'number_of_bathrooms_id' => 4,
                'amenities_id' => 4,
                'ownership_id' => 4,
                'lease_type_id' => 4,
            );

            $fieldRuleMaxValue = array (
                'title' => 100,
                'description' => 2000,
                'personalized_title' => 140,
            );

            $fieldRuleMinMaxType = array (
                'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add property dimension paa fields.
     *
     * @param string $category
     */
    private function addPropertyDimensionPaaFields($category)
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

                if ($dimensionField == 'date_available' ) {
                    $field                                                            = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['field']                 = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text_datepicker';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } elseif (in_array($dimensionField, array('rental_length'))) {
                    $field                                                            = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['field']                 = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text_int';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } elseif (in_array($dimensionField, array('deposit'))) {
                    $field                                                            = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['field']                 = $dimensionField;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text_float';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } elseif (in_array($dimensionField, array('amenities', 'bills_included_in_rent', 'rooms_for'))) {
                    $field                                                            = $dimensionField.'_id';
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_checkbox';
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                } elseif (in_array($dimensionField, array('ad_type', 'rent_per'))) {
                    $field                                                            = $dimensionField.'_id';
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_radio';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
               } else {
                    $field = $dimensionField.'_id';
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_single';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                }
            }

            // Save dimensions paa fields
            $this->saveDimensionPaaFields($this->_em, $catDimensionFields);
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
