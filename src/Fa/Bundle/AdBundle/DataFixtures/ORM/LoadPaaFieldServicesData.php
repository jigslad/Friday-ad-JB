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
use Fa\Bundle\AdBundle\Repository\AdServicesRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadPaaFieldServicesData extends LoadPaaFieldData
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

        // Add category dimension fields
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::SERVICES_ID);
        if ($category) {
            $this->addServicesDimensionPaaFields($category);

            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
                'description' => 3,
            );

            $fieldRuleLabel = array(
                'description' => 'Service description',
                'ad_type_id' => 'Ad type',
                'service_type_id' => 'Type'
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'price' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'services_offered_id' => 0,
                'service_type_id' => 0,
                'event_type_id' => 0
            );

            $fieldRuleStep = array (
                'title' => 2,
                'ad_type_id' => 2,
                'description' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'services_offered_id' => 4,
                'service_type_id' => 4,
                'event_type_id' => 4
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

            // Add rule for top level category only
            $this->addTopLevelCategoryPaaFieldRules($category, $this->_em, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array('ad_type_id' => 1), array('ad_type_id' => AdServicesRepository::AD_TYPE_OFFERED_SERVICES_ID), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);

            // Add rule for family and care services
            $this->addFamilyAndCareServicesPaaFieldsRule();

            // Add rule for property and home services
            $this->addPropertyAndHomeServicesPaaFieldsRule();

            // Add rule for health and beauty services
            $this->addHealthAndBeautyServicesPaaFieldsRule();

            // Add rule for celebrations and special occasions
            $this->addCelebrationsAndSpecialOccasionsPaaFieldsRule();
        }
    }

    /**
     * Add rule for family and care services.
     */
    private function addFamilyAndCareServicesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::FAMILY_AND_CARE_SERVICES_ID);

        if ($category) {
            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
                'description' => 3,
            );

            $fieldRuleLabel = array(
                'description' => 'Service description',
                'ad_type_id' => 'Ad type',
                'service_type_id' => 'Type'
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'price' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'services_offered_id' => 0,
                'service_type_id' => 0,
                'event_type_id' => 0,
                'services_offered_id' => 1,
            );

            $fieldRuleStep = array(
                'title' => 2,
                'ad_type_id' => 2,
                'description' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'services_offered_id' => 4,
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array('ad_type_id' => 1), array('ad_type_id' => AdServicesRepository::AD_TYPE_OFFERED_SERVICES_ID), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add rule for property and home services.
     */
    private function addPropertyAndHomeServicesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::PROPERTY_AND_HOME_SERVICES_ID);

        if ($category) {
            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
                'description' => 3,
            );

            $fieldRuleLabel = array(
                'description' => 'Service description',
                'ad_type_id' => 'Ad type',
                'service_type_id' => 'Type'
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'price' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'services_offered_id' => 0,
                'service_type_id' => 0,
                'event_type_id' => 0,
                'services_offered_id' => 1
            );

            $fieldRuleStep = array(
                'title' => 2,
                'ad_type_id' => 2,
                'description' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'services_offered_id' => 4
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array('ad_type_id' => 1), array('ad_type_id' => AdServicesRepository::AD_TYPE_OFFERED_SERVICES_ID), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add rule for health and beauty services.
     */
    private function addHealthAndBeautyServicesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::HEALTH_AND_BEAUTY_SERVICES_ID);

        if ($category) {
            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
                'description' => 3,
            );

            $fieldRuleLabel = array(
                'description' => 'Service description',
                'ad_type_id' => 'Ad type',
                'service_type_id' => 'Type'
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'price' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'services_offered_id' => 0,
                'service_type_id' => 1,
                'event_type_id' => 0,
                'services_offered_id' => 0,
            );

            $fieldRuleStep = array(
                'title' => 2,
                'ad_type_id' => 2,
                'description' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'service_type_id' => 4
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array('ad_type_id' => 1), array('ad_type_id' => AdServicesRepository::AD_TYPE_OFFERED_SERVICES_ID), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add rule for celebrations and special occasions.
     */
    private function addCelebrationsAndSpecialOccasionsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::CELEBRATIONS_AND_SPECIAL_OCCASIONS_ID);

        if ($category) {
            $fieldOrderRequired = array(
                'title' => 1,
                'ad_type_id' => 2,
                'description' => 3,
            );

            $fieldRuleLabel = array(
                'description' => 'Service description',
                'ad_type_id' => 'Ad type',
                'service_type_id' => 'Type'
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'price' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'services_offered_id' => 0,
                'service_type_id' => 0,
                'event_type_id' => 1,
                'services_offered_id' => 0,
            );

            $fieldRuleStep = array(
                'title' => 2,
                'ad_type_id' => 2,
                'description' => 2,
                'location' => 4,
                'personalized_title' => 4,
                'event_type_id' => 4
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array('ad_type_id' => 1), array('ad_type_id' => AdServicesRepository::AD_TYPE_OFFERED_SERVICES_ID), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add Services dimension paa fields.
     *
     * @param string $category
     */
    private function addServicesDimensionPaaFields($category)
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

                $field = $dimensionField.'_id';
                if ($field == 'ad_type_id') {
                        $catDimensionFields[$categoryId][$field]['field']                 = $field;
                        $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                        $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_radio';
                        $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                        $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                } elseif ($field == 'services_offered_id') {
                        $catDimensionFields[$categoryId][$field]['field']                 = $field;
                        $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                        $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_checkbox';
                        $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                        $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                } elseif ($field == 'service_type_id' || $field == 'event_type_id') {
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
        return 4; // the order in which fixtures will be loaded
    }
}
