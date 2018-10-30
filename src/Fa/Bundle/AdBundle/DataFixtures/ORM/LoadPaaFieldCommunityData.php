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
use Fa\Bundle\AdBundle\Repository\AdCommunityRepository;
use Fa\Bundle\AdBundle\Repository\PaaFieldRuleRepository;

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadPaaFieldCommunityData extends LoadPaaFieldData
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
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Community', 'lvl' => 1));
        if ($category) {
            $this->addCommunityDimensionPaaFields($category);

            $this->addMainCategoryPaaFieldRules($category);
        }

        // Add paa field rules
        $this->addWhatsOnPaaFieldsRule();
        $this->addClassesAndTuitionPaaFieldsRule();
        $this->addEducationalAndLanguagesPaaFieldsRule();
    }

    /**
     * Add what's on paa field rule.
     *
     * @param string $category
     */
    protected function addMainCategoryPaaFieldRules($category)
    {
        $fieldRuleStatus = array(
            'is_new' => 0,
            'qty' => 0,
            'price_text' => 0,
            'delivery_method_option_id' => 0,
            'payment_method_id' => 0,
            'price' => 0,
            'venue_name' => 0,
            'event_start' => 0,
            'event_start_time' => 0,
            'include_end_time' => 0,
            'event_end' => 0,
            'event_end_time' => 0,
            'class_size_id' => 0,
            'equipment_provided_id' => 0,
            'experience_level_id' => 0,
            'availability_id' => 0,
            'level_id' => 0,
        );

        $fieldRuleStep = array(
            'title' => 2,
            'description' => 2,
            'personalized_title' => 4,
            'location' => 4
        );

        $fieldRuleMaxValue = array(
            'title' => 100,
            'description' => 2000,
            'personalized_title' => 140,
        );

        $fieldRuleMinMaxType = array(
            'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
        );

        // Add rule for top level category only
        $this->addTopLevelCategoryPaaFieldRules($category, $this->_em, array(), array(), $fieldRuleStatus, array(), array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
    }

    /**
     * Add what's on paa field rule.
     */
    private function addWhatsOnPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => "What's On", 'lvl' => 2));

        if ($category) {
            $fieldOrderRequired = array(
                'event_start' => 2,
                'event_start_time' => 3,
                'include_end_time' => 4,
                'event_end' => 5,
                'event_end_time' => 6,
                'venue_name' => 7,
                'description' => 8,
                'price' => 16
            );

            $fieldRuleStatus = array(
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'class_size_id' => 0,
                'equipment_provided_id' => 0,
                'experience_level_id' => 0,
                'availability_id' => 0,
                'level_id' => 0
            );

            $fieldRuleStep = array(
                'title' => 2,
                'venue_name' => 2,
                'event_start' => 2,
                'event_start_time' => 2,
                'include_end_time' => 2,
                'event_end' => 2,
                'event_end_time' => 2,
                'description' => 2,
                'price' => 4,
                'personalized_title' => 4,
                'location' => 4
            );

            $fieldRuleLabel = array(
                'title' => "What's is the event name?",
                'price' => "Price"
            );

            $fieldRuleMaxValue = array(
                'venue_name' => 100,
                'title' => 100,
                'description' => 2000,
                'personalized_title' => 140
            );

            $fieldRuleMinMaxType = array(
                'venue_name' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add classes and tution paa field rule.
     */
    private function addClassesAndTuitionPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => "Classes And Tuition", 'lvl' => 2));

        if ($category) {
            $fieldOrderRequired = array(
                'equipment_provided_id' => 12,
                'availability_id' => 2,
                'price' => 3
            );

            $fieldRuleStatus = array(
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'venue_name' => 0,
                'event_start' => 0,
                'include_end_time' => 0,
                'event_start_time' => 0,
                'event_end' => 0,
                'event_end_time' => 0,
                'level_id' => 0
            );

            $fieldRuleStep = array(
                'title' => 2,
                'description' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'class_size_id' => 4,
                'equipment_provided_id' => 4,
                'experience_level_id' => 4,
                'availability_id' => 2,
                'price' => 2
            );

            $fieldRuleLabel = array(
                'price' => 'Price per hour'
            );

            $fieldRuleMaxValue = array(
                'title' => 100,
                'description' => 2000,
                'personalized_title' => 140,
            );

            $fieldRuleMinMaxType = array(
                'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add educational and languages paa field rule.
     */
    private function addEducationalAndLanguagesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => "Educational And Languages", 'lvl' => 3));

        if ($category) {
            $fieldOrderRequired = array(
                'equipment_provided_id' => 12,
                'availability_id' => 2,
                'price' => 3
            );

            $fieldRuleStatus = array(
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'venue_name' => 0,
                'event_start' => 0,
                'event_start_time' => 0,
                'include_end_time' => 0,
                'event_end' => 0,
                'event_end_time' => 0,
                'experience_level_id' => 0,
            );

            $fieldRuleStep = array(
                'title' => 2,
                'description' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'class_size_id' => 4,
                'equipment_provided_id' => 4,
                'availability_id' => 4,
                'level_id' => 4,
                'availability_id' => 2,
                'price' => 2
            );

            $fieldRuleLabel = array(
                'price' => 'Price per hour'
            );

            $fieldRuleMaxValue = array(
                'title' => 100,
                'description' => 2000,
                'personalized_title' => 140,
            );

            $fieldRuleMinMaxType = array(
                'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add Community dimension paa fields.
     *
     * @param string $category
     */
    private function addCommunityDimensionPaaFields($category)
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

                $field = $dimensionField;

                if ($field == 'venue_name') {
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                } elseif ($field == 'event_start' || $field == 'event_end') {
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text_datepicker';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;

                    $catDimensionFields[$categoryId][$field.'_time']['field']                 = $field.'_time';
                    $catDimensionFields[$categoryId][$field.'_time']['label']                 = $dimensionName.' Time';
                    $catDimensionFields[$categoryId][$field.'_time']['field_type']            = 'text_autosuggest';
                    $catDimensionFields[$categoryId][$field.'_time']['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field.'_time']['is_index']              = $isIndex;

                    if ($field == 'event_start') {
                        $catDimensionFields[$categoryId]['include_end_time']['field']                 = 'include_end_time';
                        $catDimensionFields[$categoryId]['include_end_time']['label']                 = 'Include end time';
                        $catDimensionFields[$categoryId]['include_end_time']['field_type']            = 'choice_checkbox';
                        $catDimensionFields[$categoryId]['include_end_time']['category_dimension_id'] = $dimensionId;
                        $catDimensionFields[$categoryId]['include_end_time']['is_index']              = $isIndex;
                    }
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
