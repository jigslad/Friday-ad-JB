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

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadPaaFieldJobsData extends LoadPaaFieldData
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
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Jobs', 'lvl' => 1));
        if ($category) {
            $this->addJobsDimensionPaaFields($category);

            $fieldOrderRequired = array(
                'contract_type_id' => 1,
                'salary_type_id' => 13,
                'salary' => 14,
                'company_web_address' => 15
            );

            $fieldRuleLabel = array(
                'title' => 'Job title',
                'contract_type_id' => 'What kind of contract is it?',
                'salary_type_id' => 'Salary type',
                'company_web_address' => 'Company\'s website address',
                'years_experience_id' => 'Years of experience',
                'education_level_id' => 'Education level',
                'additional_job_requirements_id' => 'Additional job requirements',
                'additional_benefits_id' => 'Additional job benefits'
            );

            $fieldRuleStep = array(
                'title' => 2,
                'contract_type_id' => 2,
                'description' => 2,
                'salary' => 4,
                'company_web_address' => 4,
                'additional_job_requirements_id' => 4,
                'additional_benefits_id' => 4,
                'education_level_id' => 4,
                'years_experience_id' => 4,
                'salary_type_id' => 4,
                'location' => 4
            );

            // Add rule for top level category only
            $this->addTopLevelCategoryPaaFieldRules($category, $this->_em, $fieldOrderRequired, $fieldRuleLabel, array('is_new' => 0, 'qty' => 0, 'price_text' => 0, 'price' => 0, 'ad_type_id' => 0, 'delivery_method_option_id' => 0, 'payment_method_id' => 0, 'personalized_title' => 0), array('contract_type_id' => 1), array(), array('salary' => 0.01), array('salary' => 1000000, 'title' => 100, 'description' => 2000), array('salary' => PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE, 'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH, 'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH), array(), $fieldRuleStep);
        }
    }

    /**
     * Add Jobs dimension paa fields.
     *
     * @param string $category
     */
    private function addJobsDimensionPaaFields($category)
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

                if ($dimensionField == 'salary' || $dimensionField == 'company_web_address') {
                    $field = $dimensionField;
                } else {
                    $field = $dimensionField.'_id';
                }

                if ($field == 'salary') {
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text_float';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                } elseif ($field == 'company_web_address') {
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'text';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                } elseif ($field == 'additional_job_requirements_id' || $field == 'additional_benefits_id') {
                    $catDimensionFields[$categoryId][$field]['field']                 = $field;
                    $catDimensionFields[$categoryId][$field]['label']                 = $dimensionName;
                    $catDimensionFields[$categoryId][$field]['field_type']            = 'choice_checkbox';
                    $catDimensionFields[$categoryId][$field]['category_dimension_id'] = $dimensionId;
                    $catDimensionFields[$categoryId][$field]['is_index']              = $isIndex;
                } elseif ($field == 'education_level_id' || $field == 'years_experience_id' || $field == 'salary_type_id' || $field == 'contract_type_id') {
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
