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
use Fa\Bundle\AdBundle\Repository\AdAnimalsRepository;
use Fa\Bundle\AdBundle\Repository\PaaFieldRuleRepository;

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadPaaFieldAnimalsData extends LoadPaaFieldData
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
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Animals', 'lvl' => 1));
        if ($category) {
            $this->addAnimalsDimensionPaaFields($category);

            // Add rule for top level category only
            $this->addMainCategoryPaaFieldRules($category);

            // Add rule for few categories
            $this->addPetsPaaFieldsRule();
            $this->addPetAccessoriesPaaFieldsRule();
            $this->addBirdsPaaFieldsRule();
            $this->addCatsAndKittensPaaFieldsRule();
            $this->addDogsAndPuppiesPaaFieldsRule();
            $this->addHorsesPaaFieldsRule();
            $this->addLivestockPaaFieldsRule();
        }

        // Add paa rule for category pets.
    }

    /**
     * Add what's on paa field rule.
     *
     * @param string $category
     */
    protected function addMainCategoryPaaFieldRules($category)
    {
        $fieldOrderRequired = array(
            'ad_type_id' => '1'
        );

        $fieldRuleStatus = array (
            'is_new' => 0,
            'qty' => 0,
            'price_text' => 0,
            'delivery_method_option_id' => 0,
            'payment_method_id' => 0,
            'condition_id' => 0,
            'species_id' => 0,
            'breed_id' => 0,
            'height_id' => 0,
            'gender_id' => 0,
            'age_id' => 0,
            'colour_id' => 0,
            'ad_type_id' => 0,
        );

        $fieldRuleStep = array (
            'title' => 2,
            'description' => 2,
            'price' => 2,
            'personalized_title' => 4,
            'location' => 4
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
        $this->addTopLevelCategoryPaaFieldRules($category, $this->_em, $fieldOrderRequired, array(), $fieldRuleStatus, array(), array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
    }

    /**
     * This method is used to add post an ad rule for pets category.
     */
    private function addPetsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Pets'));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleDefaultValue = array(
                'ad_type_id' => AdAnimalsRepository::AD_TYPE_FOR_SALE_ID_PETS,
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'condition_id' => 0,
                'species_id' => 0,
                'breed_id' => 0,
                'height_id' => 0,
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'gender_id' => 4,
                'age_id' => 4,
                'colour_id' => 4

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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for pets category.
     */
    private function addPetAccessoriesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Pet Accessories'));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleDefaultValue = array(
                'ad_type_id' => AdAnimalsRepository::AD_TYPE_FOR_SALE_ID_PETS,
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'species_id' => 0,
                'breed_id' => 0,
                'height_id' => 0,
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'gender_id' => 4,
                'age_id' => 4,
                'colour_id' => 4,
                'condition_id' => 4,
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for pets category.
     */
    private function addBirdsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Birds'));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleDefaultValue = array(
                'ad_type_id' => AdAnimalsRepository::AD_TYPE_FOR_SALE_ID_PETS,
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'condition_id' => 0,
                'breed_id' => 0,
                'height_id' => 0,
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'gender_id' => 4,
                'age_id' => 4,
                'colour_id' => 4,
                'species_id' => 4,
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for pets category.
     */
    private function addDogsAndPuppiesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Dogs and Puppies'));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleDefaultValue = array(
                'ad_type_id' => AdAnimalsRepository::AD_TYPE_FOR_SALE_ID_PETS,
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'condition_id' => 0,
                'species_id' => 0,
                'height_id' => 0,
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'gender_id' => 4,
                'age_id' => 4,
                'colour_id' => 4,
                'breed_id' => 4,
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for pets category.
     */
    private function addCatsAndKittensPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Cats and Kittens'));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleDefaultValue = array(
                'ad_type_id' => AdAnimalsRepository::AD_TYPE_FOR_SALE_ID_PETS,
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'condition_id' => 0,
                'species_id' => 0,
                'height_id' => 0,
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'gender_id' => 4,
                'age_id' => 4,
                'colour_id' => 4,
                'breed_id' => 4,
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for Horses category.
     */
    private function addHorsesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Horses'));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleDefaultValue = array(
                'ad_type_id' => AdAnimalsRepository::AD_TYPE_FOR_SALE_ID_HORSES,
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'condition_id' => 0,
                'species_id' => 0,
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'price' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'gender_id' => 4,
                'age_id' => 4,
                'colour_id' => 4,
                'breed_id' => 4,
                'height_id' => 4
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for Livestock category.
     */
    private function addLivestockPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Livestock'));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleDefaultValue = array(
                'ad_type_id' => AdAnimalsRepository::AD_TYPE_FOR_SALE_ID_LIVESTOCK,
            );

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0,
                'price_text' => 0,
                'condition_id' => 0,
                'species_id' => 0,
                'breed_id' => 0,
                'height_id' => 0
            );

            $fieldRuleStep = array (
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'gender_id' => 4,
                'age_id' => 4,
                'colour_id' => 4,
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

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add animals dimension paa fields.
     *
     * @param string $category
     */
    private function addAnimalsDimensionPaaFields($category)
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

                if ($field == 'breed_id' || $field == 'colour_id' || $field == 'species_id') {
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text_autosuggest';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                } elseif ($field == 'ad_type_id') {
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_radio';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                } elseif ($field == 'gender_id') {
                        $catDimensionFields[$categoryId][$field]['field']                 = $field;
                        $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                        $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_checkbox';
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
