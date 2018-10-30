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

use Fa\Bundle\AdBundle\DataFixtures\ORM\LoadPaaFieldData;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Repository\PaaFieldRuleRepository;

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadPaaFieldForSaleData extends LoadPaaFieldData
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
        $this->addCommonPaaFields($this->_em);

        // Add category dimension fields
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'For Sale', 'lvl' => 1));
        if ($category) {
            $this->addForSaleDimensionPaaFields($category);

            // Add rule for top level category only
            $this->addMainCategoryPaaFieldRules($category);

            // Add category rule.
            $this->addAgriculturalPaaFieldsRule();

            $this->addBabyAndKidsPaaFieldsRule();
            $this->addClothesPaaFieldsRule();

            $this->addBusinessesForSalePaaFieldsRule();
            $this->addOfficeEquipmentPaaFieldsRule();

            $this->addElectronicsPaaFieldsRule();

            $this->addFashionPaaFieldsRule();
            $this->addMenPaaFieldsRule();
            $this->addWomenPaaFieldsRule();
            $this->addShoesMenPaaFieldsRule();
            $this->addShoesWomenPaaFieldsRule();
            $this->addShoesKidsPaaFieldsRule();
            $this->addKidsPaaFieldsRule();

            $this->addHomeAndGardenPaaFieldsRule();
            $this->addFurniturePaaFieldsRule();
            $this->addKitchenPaaFieldsRule();
            $this->addGardenPaaFieldsRule();
            $this->addHealthPaaFieldsRule();
            $this->addHomeDecorPaaFieldsRule();

            $this->addLeisurePaaFieldsRule();
            $this->addMusicalInstrumentsAndAccessoriesPaaFieldsRule();
            $this->addSportsEquipmentPaaFieldsRule();
            $this->addTicketsPaaFieldsRule();
        }
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

        $fieldRuleStatus = array(
            'colour_id' => 0,
            'brand_id' => 0,
            'age_range_id' => 0,
            'dimensions_length' => 0,
            'dimensions_width' => 0,
            'dimensions_height' => 0,
            'business_type_id' => 0,
            'net_profit_min' => 0,
            'net_profit_max' => 0,
            'turnover_min' => 0,
            'turnover_max' => 0,
            'size_id' => 0,
            'waist_id' => 0,
            'leg_id' => 0,
            'neck_id' => 0,
            'power' => 0,
            'event_date' => 0
        );

        $fieldRuleStep = array(
            'ad_type_id' => 2,
            'title' => 2,
            'description' => 2,
            'is_new' => 2,
            'price' => 2,
            'price_text' => 2,
            'personalized_title' => 4,
            'location' => 4,
            'qty' => 4,
            'delivery_method_option_id' => 4,
            'payment_method_id' => 4,
            'condition_id' => 4,
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

        $fieldRuleDefaultValue = array(
            'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
        );

        // Add rule for top level category only
        $this->addTopLevelCategoryPaaFieldRules($category, $this->_em, $fieldOrderRequired, array(), $fieldRuleStatus, array(), array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addAgriculturalPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Agricultural', 'lvl' => 2));

        if ($category) {
            $this->addBrandPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addBabyAndKidsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Baby and Kids', 'lvl' => 2));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleStatus = array(
                'business_type_id' => 0,
                'net_profit_min' => 0,
                'net_profit_max' => 0,
                'turnover_min' => 0,
                'turnover_max' => 0,
                'size_id' => 0,
                'waist_id' => 0,
                'leg_id' => 0,
                'neck_id' => 0,
                'power' => 0,
                'event_date' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'brand_id' => 4,
                'colour_id' => 4,
                'age_range_id' => 4,
                'dimensions_length' => 4,
                'dimensions_width' => 4,
                'dimensions_height' => 4
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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addClothesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Clothes', 'lvl' => 3));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1',
                'brand_id' => '15'
            );

            $fieldRuleStatus = array(
                'business_type_id' => 0,
                'net_profit_min' => 0,
                'net_profit_max' => 0,
                'turnover_min' => 0,
                'turnover_max' => 0,
                'size_id' => 0,
                'waist_id' => 0,
                'leg_id' => 0,
                'neck_id' => 0,
                'power' => 0,
                'event_date' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'brand_id' => 4,
                'colour_id' => 4,
                'age_range_id' => 4,
                'dimensions_length' => 4,
                'dimensions_width' => 4,
                'dimensions_height' => 4
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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addBusinessesForSalePaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Businesses for sale', 'lvl' => 4));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleStatus = array(
                'colour_id' => 0,
                'brand_id' => 0,
                'age_range_id' => 0,
                'dimensions_length' => 0,
                'dimensions_width' => 0,
                'dimensions_height' => 0,
                'size_id' => 0,
                'waist_id' => 0,
                'leg_id' => 0,
                'neck_id' => 0,
                'power' => 0,
                'event_date' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'business_type_id' => 4,
                'net_profit_min' => 4,
                'net_profit_max' => 4,
                'turnover_min' => 4,
                'turnover_max' => 4
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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addOfficeEquipmentPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Office Equipment', 'lvl' => 4));

        if ($category) {
            $this->addBrandPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addElectronicsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Electronics', 'lvl' => 2));

        if ($category) {
            $this->addBrandPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addFashionPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Fashion', 'lvl' => 2));

        if ($category) {
            $this->addBrandColourPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addMenPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Men', 'lvl' => 3));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleStatus = array(
                'brand_id' => 0,
                'age_range_id' => 0,
                'dimensions_length' => 0,
                'dimensions_width' => 0,
                'dimensions_height' => 0,
                'business_type_id' => 0,
                'net_profit_min' => 0,
                'net_profit_max' => 0,
                'turnover_min' => 0,
                'turnover_max' => 0,
                'power' => 0,
                'event_date' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'brand_id' => 4,
                'colour_id' => 4,
                'size_id' => 4,
                'waist_id' => 4,
                'leg_id' => 4,
                'neck_id' => 4
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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addWomenPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Women', 'lvl' => 3));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleStatus = array(
                'brand_id' => 0,
                'age_range_id' => 0,
                'dimensions_length' => 0,
                'dimensions_width' => 0,
                'dimensions_height' => 0,
                'business_type_id' => 0,
                'net_profit_min' => 0,
                'net_profit_max' => 0,
                'turnover_min' => 0,
                'turnover_max' => 0,
                'waist_id' => 0,
                'leg_id' => 0,
                'neck_id' => 0,
                'power' => 0,
                'event_date' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'brand_id' => 4,
                'colour_id' => 4,
                'size_id' => 4
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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addShoesMenPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Men', 'lvl' => 4, 'parent' => CategoryRepository::SHOES_ID));

        if ($category) {
            $this->addShoeSizePaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addShoesWomenPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Women', 'lvl' => 4, 'parent' => CategoryRepository::SHOES_ID));

        if ($category) {
            $this->addShoeSizePaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addShoesKidsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Kids', 'lvl' => 4, 'parent' => CategoryRepository::SHOES_ID));

        if ($category) {
            $this->addShoeSizePaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addKidsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Kids', 'lvl' => 3));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleStatus = array(
                'brand_id' => 0,
                'dimensions_length' => 0,
                'dimensions_width' => 0,
                'dimensions_height' => 0,
                'business_type_id' => 0,
                'net_profit_min' => 0,
                'net_profit_max' => 0,
                'turnover_min' => 0,
                'turnover_max' => 0,
                'size_id' => 0,
                'waist_id' => 0,
                'leg_id' => 0,
                'neck_id' => 0,
                'power' => 0,
                'event_date' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'brand_id' => 4,
                'colour_id' => 4,
                'age_range_id' => 4,
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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addHomeAndGardenPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Home and Garden', 'lvl' => 2));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleStatus = array(
                'brand_id' => 0,
                'age_range_id' => 0,
                'business_type_id' => 0,
                'net_profit_min' => 0,
                'net_profit_max' => 0,
                'turnover_min' => 0,
                'turnover_max' => 0,
                'size_id' => 0,
                'waist_id' => 0,
                'leg_id' => 0,
                'neck_id' => 0,
                'event_date' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'colour_id' => 4,
                'dimensions_length' => 4,
                'dimensions_width' => 4,
                'dimensions_height' => 4,
                'power' => 4,

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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addFurniturePaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->find(CategoryRepository::HOME_AND_GARDEN_FURNITURE_ID);

        if ($category) {
            $this->addHomeAndGardenBrandPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addKitchenPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Home Appliances', 'lvl' => 3));

        if ($category) {
            $this->addHomeAndGardenBrandPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addGardenPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Garden', 'lvl' => 3));

        if ($category) {
            $this->addHomeAndGardenBrandPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addHealthPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Health', 'lvl' => 3));

        if ($category) {
            $this->addHomeAndGardenBrandPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addHomeDecorPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Home Decor', 'lvl' => 3));

        if ($category) {
            $this->addHomeAndGardenBrandPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addLeisurePaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Leisure', 'lvl' => 2));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleStatus = array(
                'brand_id' => 0,
                'age_range_id' => 0,
                'dimensions_length' => 0,
                'dimensions_width' => 0,
                'dimensions_height' => 0,
                'business_type_id' => 0,
                'net_profit_min' => 0,
                'net_profit_max' => 0,
                'turnover_min' => 0,
                'turnover_max' => 0,
                'size_id' => 0,
                'waist_id' => 0,
                'leg_id' => 0,
                'neck_id' => 0,
                'power' => 0,
                'event_date' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'colour_id' => 4
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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addMusicalInstrumentsAndAccessoriesPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Musical Instruments and Accessories', 'lvl' => 3));

        if ($category) {
            $this->addBrandColourPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addSportsEquipmentPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Sports Equipment', 'lvl' => 3));

        if ($category) {
            $this->addBrandColourPaaFieldsRule($category);
        }
    }

    /**
     * This method is used to add post an ad rule for for sale categories.
     */
    private function addTicketsPaaFieldsRule()
    {
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Tickets', 'lvl' => 3));

        if ($category) {
            $fieldOrderRequired = array(
                'ad_type_id' => '1'
            );

            $fieldRuleStatus = array(
                'brand_id' => 0,
                'age_range_id' => 0,
                'dimensions_length' => 0,
                'dimensions_width' => 0,
                'dimensions_height' => 0,
                'business_type_id' => 0,
                'net_profit_min' => 0,
                'net_profit_max' => 0,
                'turnover_min' => 0,
                'turnover_max' => 0,
                'size_id' => 0,
                'waist_id' => 0,
                'leg_id' => 0,
                'neck_id' => 0,
                'power' => 0
            );

            $fieldRuleStep = array(
                'ad_type_id' => 2,
                'title' => 2,
                'description' => 2,
                'is_new' => 2,
                'price' => 2,
                'price_text' => 2,
                'personalized_title' => 4,
                'location' => 4,
                'qty' => 4,
                'delivery_method_option_id' => 4,
                'payment_method_id' => 4,
                'condition_id' => 4,
                'colour_id' => 4,
                'event_date' => 4
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

            $fieldRuleDefaultValue = array(
                'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
            );

            $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Add for sale dimension paa fields.
     *
     * @param string $category
     */
    private function addForSaleDimensionPaaFields($category)
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

                if (in_array($dimensionName, array('Dimensions', 'Net Profit', 'Turnover', 'Power', 'Event Date'))) {
                    if ($dimensionName == 'Dimensions') {
                        foreach (array('Length', 'Width', 'Height') as $dimensionOption) {
                            $field                                                            = $dimensionField.'_'.strtolower($dimensionOption);
                            $catDimensionFields[$categoryId][$field]['field']                 = $field;
                            $catDimensionFields[$categoryId][$field]['label']                 = $dimensionOption;
                            $catDimensionFields[$categoryId][$field]['field_type']            = 'text_float';
                            $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                            $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                        }
                    } elseif ($dimensionName == 'Net Profit' || $dimensionName == 'Turnover') {
                        foreach (array('Min', 'Max') as $dimensionOption) {
                            $field                                                            = $dimensionField.'_'.strtolower($dimensionOption);
                            $catDimensionFields[$categoryId][$field]['field']                 = $field;
                            $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName.' ( '.$dimensionOption.' )';
                            $catDimensionFields[$categoryId][$field]['field_type']            = 'text_float';
                            $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                            $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                        }
                    } elseif ($dimensionName == 'Power') {
                        $catDimensionFields[$categoryId][$dimensionField]['field']                 = $dimensionField;
                        $catDimensionFields[$categoryId][$dimensionField]['label']                 = $dimensionName;
                        $catDimensionFields[$categoryId][$dimensionField]['field_type']            = 'text_float';
                        $catDimensionFields[$categoryId][$dimensionField]['is_index']              = $isIndex;
                        $catDimensionFields[$categoryId][$dimensionField]['category_dimension_id'] = $dimensionId;
                    } elseif ($dimensionName == 'Event Date') {
                        $catDimensionFields[$categoryId][$dimensionField]['field']                 = $dimensionField;
                        $catDimensionFields[$categoryId][$dimensionField]['label']                 = $dimensionName;
                        $catDimensionFields[$categoryId][$dimensionField]['field_type']            = 'text_datepicker';
                        $catDimensionFields[$categoryId][$dimensionField]['is_index']              = $isIndex;
                        $catDimensionFields[$categoryId][$dimensionField]['category_dimension_id'] = $dimensionId;
                    }
                } else {
                    $field = $dimensionField.'_id';

                    if ($field == 'brand_id' || $field == 'colour_id') {
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
     * Add paa field rule for barands.
     *
     * @param object $category
     */
    private function addBrandPaaFieldsRule($category)
    {
        $fieldOrderRequired = array(
            'ad_type_id' => '1'
        );

        $fieldRuleStatus = array(
            'colour_id' => 0,
            'age_range_id' => 0,
            'dimensions_length' => 0,
            'dimensions_width' => 0,
            'dimensions_height' => 0,
            'business_type_id' => 0,
            'net_profit_min' => 0,
            'net_profit_max' => 0,
            'turnover_min' => 0,
            'turnover_max' => 0,
            'size_id' => 0,
            'waist_id' => 0,
            'leg_id' => 0,
            'neck_id' => 0,
            'power' => 0,
            'event_date' => 0
        );

        $fieldRuleStep = array(
            'ad_type_id' => 2,
            'title' => 2,
            'description' => 2,
            'is_new' => 2,
            'price' => 2,
            'price_text' => 2,
            'personalized_title' => 4,
            'location' => 4,
            'qty' => 4,
            'delivery_method_option_id' => 4,
            'payment_method_id' => 4,
            'condition_id' => 4,
            'brand_id' => 4,
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

        $fieldRuleDefaultValue = array(
            'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
        );

        $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
    }

    /**
     * Add paa field rule for barands.
     *
     * @param object $category
     */
    private function addShoeSizePaaFieldsRule($category)
    {
        $fieldOrderRequired = array(
            'ad_type_id' => '1'
        );

        $fieldRuleStatus = array(
            'brand_id' => 0,
            'age_range_id' => 0,
            'dimensions_length' => 0,
            'dimensions_width' => 0,
            'dimensions_height' => 0,
            'business_type_id' => 0,
            'net_profit_min' => 0,
            'net_profit_max' => 0,
            'turnover_min' => 0,
            'turnover_max' => 0,
            'waist_id' => 0,
            'leg_id' => 0,
            'neck_id' => 0,
            'power' => 0,
            'event_date' => 0
        );

        $fieldRuleStep = array(
            'ad_type_id' => 2,
            'title' => 2,
            'description' => 2,
            'is_new' => 2,
            'price' => 2,
            'price_text' => 2,
            'personalized_title' => 4,
            'location' => 4,
            'qty' => 4,
            'delivery_method_option_id' => 4,
            'payment_method_id' => 4,
            'condition_id' => 4,
            'brand_id' => 4,
            'colour_id' => 4,
            'size_id' => 4,
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

        $fieldRuleDefaultValue = array(
            'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
        );

        $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
    }

    /**
     * Add paa field rule for barands.
     *
     * @param object $category
     */
    private function addHomeAndGardenBrandPaaFieldsRule($category)
    {
        $fieldOrderRequired = array(
            'ad_type_id' => '1'
        );

        $fieldRuleStatus = array(
            'age_range_id' => 0,
            'business_type_id' => 0,
            'net_profit_min' => 0,
            'net_profit_max' => 0,
            'turnover_min' => 0,
            'turnover_max' => 0,
            'size_id' => 0,
            'waist_id' => 0,
            'leg_id' => 0,
            'neck_id' => 0,
            'event_date' => 0
        );

        $fieldRuleStep = array(
            'ad_type_id' => 2,
            'title' => 2,
            'description' => 2,
            'is_new' => 2,
            'price' => 2,
            'price_text' => 2,
            'personalized_title' => 4,
            'location' => 4,
            'qty' => 4,
            'delivery_method_option_id' => 4,
            'payment_method_id' => 4,
            'condition_id' => 4,
            'colour_id' => 4,
            'dimensions_length' => 4,
            'dimensions_width' => 4,
            'dimensions_height' => 4,
            'power' => 4,
            'brand_id' => 4,

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

        $fieldRuleDefaultValue = array(
            'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
        );

        $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
    }

    /**
     * Add paa field rule for barands.
     *
     * @param object $category
     */
    private function addBrandColourPaaFieldsRule($category)
    {
        $fieldOrderRequired = array(
            'ad_type_id' => '1'
        );

        $fieldRuleStatus = array(
            'brand_id' => 0,
            'age_range_id' => 0,
            'dimensions_length' => 0,
            'dimensions_width' => 0,
            'dimensions_height' => 0,
            'business_type_id' => 0,
            'net_profit_min' => 0,
            'net_profit_max' => 0,
            'turnover_min' => 0,
            'turnover_max' => 0,
            'size_id' => 0,
            'waist_id' => 0,
            'leg_id' => 0,
            'neck_id' => 0,
            'power' => 0,
            'event_date' => 0
        );

        $fieldRuleStep = array(
            'ad_type_id' => 2,
            'title' => 2,
            'description' => 2,
            'is_new' => 2,
            'price' => 2,
            'price_text' => 2,
            'personalized_title' => 4,
            'location' => 4,
            'qty' => 4,
            'delivery_method_option_id' => 4,
            'payment_method_id' => 4,
            'condition_id' => 4,
            'brand_id' => 4,
            'colour_id' => 4
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

        $fieldRuleDefaultValue = array(
            'ad_type_id' => EntityRepository::AD_TYPE_FORSALE_ID,
        );

        $this->addPaaFieldsRule($this->_em, $category, $fieldOrderRequired, array(), $fieldRuleStatus, array(), $fieldRuleDefaultValue, array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
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
        return 2; // the order in which fixtures will be loaded
    }
}
