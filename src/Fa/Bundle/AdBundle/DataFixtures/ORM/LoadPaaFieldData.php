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
use Fa\Bundle\AdBundle\Entity\PaaField;
use Fa\Bundle\AdBundle\Entity\PaaFieldRule;
use Fa\Bundle\EntityBundle\Entity\Category;
use Symfony\Component\Validator\Constraints\Count;

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
abstract class LoadPaaFieldData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Add common paa fields.
     */
    protected function addCommonPaaFields($em)
    {
        $fields = array(
                     'title'                     => array('label' => 'Ad title', 'field_type' => 'text'),
                     'description'               => array('label' => 'Description', 'field_type' => 'textarea_tinymce'),
                     'is_new'                    => array('label' => 'Is ad new', 'field_type' => 'choice_radio'),
                     'price'                     => array('label' => 'Price', 'field_type' => 'text_float'),
                     'price_text'                => array('label' => 'Swap Price Text', 'field_type' => 'text'),
                     'location'                  => array('label' => 'Location', 'field_type' => 'text'),
                     'qty'                       => array('label' => 'Quantity', 'field_type' => 'integer'),
                     'delivery_method_option_id' => array('label' => 'Delivery method', 'field_type' => 'choice_radio'),
                     'payment_method_id'         => array('label' => 'Payment method', 'field_type' => 'choice_radio'),
                     'personalized_title'        => array('label' => 'Subtitle', 'field_type' => 'text'),
                     'has_video'                 => array('label' => 'Has video', 'field_type' => 'choice_radio'),
                  );

        foreach ($fields as $field => $fieldOptions) {
            $paaField = new PaaField();
            $paaField->setField($field);
            $paaField->setLabel($fieldOptions['label']);
            $paaField->setFieldType($fieldOptions['field_type']);
            $em->persist($paaField);
            $em->flush();
        }
    }

    /**
     * Get category dimensions.
     *
     * @param string $category
     * @param object $em
     */
    protected function getCategoryDimensions($category, $em)
    {
        $children = $em->getRepository('FaEntityBundle:Category')->getNodesHierarchyQuery($category)->getArrayResult();

        $categoryIds[] = $category->getId();
        foreach ($children as $child) {
            $categoryIds[] = $child['id'];
        }

        return $em->getRepository('FaEntityBundle:CategoryDimension')
        ->getBaseQueryBuilder()
        ->select('cd.id', 'cd.name', 'cd.status', 'c.id as category_id', 'cd.is_index')
        ->leftJoin('cd.category', 'c')
        ->andWhere('cd.category IN (:category_id)')->setParameter('category_id', $categoryIds)
        ->getQuery()
        ->getArrayResult();
    }

    /**
     * Save dimension paa fields.
     *
     * @param object $em
     * @param array  $catDimensionFields
     */
    protected function saveDimensionPaaFields($em, $catDimensionFields = array())
    {
        if (count($catDimensionFields)) {
            foreach ($catDimensionFields as $categoryId => $fields) {
                $category = $em->getRepository('FaEntityBundle:Category')->find($categoryId);
                foreach ($fields as $field => $fieldOptions) {
                    $paaField = new PaaField();
                    $paaField->setField($field);
                    $paaField->setLabel($fieldOptions['label']);
                    $paaField->setFieldType($fieldOptions['field_type']);
                    $paaField->setCategory($category);

                    if (isset($fieldOptions['category_dimension_id']) && $fieldOptions['category_dimension_id']) {
                        $paaField->setCategoryDimensionId($fieldOptions['category_dimension_id']);
                    }

                    if (isset($fieldOptions['is_index'])) {
                        $paaField->setIsIndex($fieldOptions['is_index']);
                    }

                    $em->persist($paaField);
                    $em->flush();
                }
            }
        }
    }

    /**
     * Add top level category paa field rules.
     *
     * @param object $category
     * @param object $em
     * @param array  $fieldOrderRequired
     * @param array  $fieldRuleLabel
     * @param array  $fieldRuleStatus
     * @param array  $fieldRuleRequired
     * @param array  $fieldRuleDefaultValue
     * @param array  $fieldRuleMinValue
     * @param array  $fieldRuleMaxValue
     * @param array  $fieldRuleMinMaxType
     * @param array  $fieldType
     * @param array  $fieldRuleStep
     */
    protected function addTopLevelCategoryPaaFieldRules($category, $em, $fieldOrderRequired = array(), $fieldRuleLabel = array(), $fieldRuleStatus = array(), $fieldRuleRequired = array(), $fieldRuleDefaultValue = array(), $fieldRuleMinValue = array(), $fieldRuleMaxValue = array(), $fieldRuleMinMaxType = array(), $fieldType = array(), $fieldRuleStep = array())
    {
        $paaFields       = array();
        $adTypeField     = array();
        $commonPaaFields = $em->getRepository('FaAdBundle:PaaField')->getCommonPaaFields();

        foreach ($commonPaaFields as $field => $paaField) {
            $paaFields[$paaField->getField()] = $paaField;
        }

        $dimensionPaaFields = $em->getRepository('FaAdBundle:PaaField')->getPaaFieldsByCategoryId($category->getId());

        foreach ($dimensionPaaFields as $field => $paaField) {
            if ($paaField->getField() == 'ad_type_id') {
                $adTypeField[$paaField->getField()] = $paaField;
            } else {
                $paaFields[$paaField->getField()] = $paaField;
            }
        }

        $paaFields = $adTypeField + $paaFields;

        $ord = 1;
        foreach ($paaFields as $paaField) {
            $ord = $this->savePaaFieldRule($em, $paaField, $category, $ord, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, $fieldRuleDefaultValue, $fieldRuleMinValue, $fieldRuleMaxValue, $fieldRuleMinMaxType, $fieldType, $fieldRuleStep);
        }

        $em->flush();
    }

    /**
     * This method is used to ad post an ad rule.
     *
     * @param object $em
     * @param object $category
     * @param array  $fieldOrderRequired
     * @param array  $fieldRuleLabel
     * @param array  $fieldRuleStatus
     * @param array  $fieldRuleRequired
     * @param array  $fieldRuleDefaultValue
     * @param array  $fieldRuleMinValue
     * @param array  $fieldRuleMaxValue
     * @param array  $fieldRuleMinMaxType
     * @param array  $fieldType
     * @param array  $fieldRuleStep
     */
    protected function addPaaFieldsRule($em, $category, $fieldOrderRequired = array(), $fieldRuleLabel = array(), $fieldRuleStatus = array(), $fieldRuleRequired = array(), $fieldRuleDefaultValue = array(), $fieldRuleMinValue = array(), $fieldRuleMaxValue = array(), $fieldRuleMinMaxType = array(), $fieldType = array(), $fieldRuleStep = array())
    {
        $paaFieldsData = $em->getRepository('FaAdBundle:PaaField')->getPaaFieldsByCategoryAncestor($category->getId());
        $ord = 1;
        foreach ($paaFieldsData as $paaFieldData) {
            if (isset($paaFieldData['is_rule']) && $paaFieldData['is_rule'] == true) {
                $paaFieldRule = $paaFieldData['data'];
                $paaField = $paaFieldRule->getPaaField();
            } else {
                $paaField = $paaFieldData['data'];
            }
            $ord = $this->savePaaFieldRule($em, $paaField, $category, $ord, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, $fieldRuleRequired, $fieldRuleDefaultValue, $fieldRuleMinValue, $fieldRuleMaxValue, $fieldRuleMinMaxType, $fieldType, $fieldRuleStep);
        }
        $em->flush();
    }

    /**
     * This method is used to save paa field rule.
     *
     * @param object  $em
     * @param object  $paaField
     * @param object  $category
     * @param integer $ord
     * @param array   $fieldOrderRequired
     * @param array   $fieldRuleLabel
     * @param array   $fieldRuleStatus
     * @param array   $fieldRuleRequired
     * @param array   $fieldRuleDefaultValue
     * @param array   $fieldRuleMinValue
     * @param array   $fieldRuleMaxValue
     * @param array   $fieldRuleMinMaxType
     * @param array   $fieldType
     * @param array  $fieldRuleStep
     *
     * @return integer
     */
    protected function savePaaFieldRule($em, $paaField, $category, $ord, $fieldOrderRequired = array(), $fieldRuleLabel = array(), $fieldRuleStatus = array(), $fieldRuleRequired = array(), $fieldRuleDefaultValue = array(), $fieldRuleMinValue = array(), $fieldRuleMaxValue = array(), $fieldRuleMinMaxType = array(), $fieldType = array(), $fieldRuleStep = array())
    {
        $fieldRuleLabel1 = array(
            'price'      => "What's your price?",
            'price_text' => "What would you like in exchange?",
            'is_new'     => 'New or used?',
            'has_video'  => 'Has video?',
            'ad_type_id' => 'What kind of ad it is?',
        );

        $fieldRuleLabel = array_merge($fieldRuleLabel1, $fieldRuleLabel);

        $fieldRuleStatus1 = array(
            'has_video' => '0',
            'remake_id' => '0',
        );

        $fieldRuleStatus = array_merge($fieldRuleStatus1, $fieldRuleStatus);

        $fieldRuleRequired1 = array(
            'title'                     => '1',
            'description'               => '1',
            'price'                     => '1',
            'is_new'                    => '1',
            'location'                  => '1',
            'qty'                       => '1',
            'ad_type_id'                => '1',
            'delivery_method_option_id' => '1',
            'payment_method_id'         => '1'
        );

        $fieldRuleRequired = array_merge($fieldRuleRequired1, $fieldRuleRequired);

        $fieldRuleDefaultValue1 = array('qty' => '1');

        $fieldRuleDefaultValue = array_merge($fieldRuleDefaultValue1, $fieldRuleDefaultValue);

        $paaFieldRule = new PaaFieldRule();
        $paaFieldRule->setPaaField($paaField);
        $paaFieldRule->setCategory($category);
        $paaFieldRule->setIsRecommended(0);

        //$paaFieldRule->setOrd($ord++);

        $field = $paaField->getField();

        if (isset($fieldOrderRequired[$field])) {
            $paaFieldRule->setOrd($fieldOrderRequired[$field]);
        } else {
            $ord = $this->findTheOrder($ord, $fieldOrderRequired);
            $paaFieldRule->setOrd($ord++);
        }

        if (isset($fieldRuleLabel[$field])) {
            $paaFieldRule->setLabel($fieldRuleLabel[$field]);
        } else {
            $paaFieldRule->setLabel($paaField->getLabel());
        }

        if (isset($fieldRuleStatus[$field])) {
            $paaFieldRule->setStatus($fieldRuleStatus[$field]);
        } else {
            $paaFieldRule->setStatus(1);
        }

        if (isset($fieldRuleRequired[$field])) {
            $paaFieldRule->setIsRequired($fieldRuleRequired[$field]);
        } else {
            $paaFieldRule->setIsRequired(0);
        }

        if (isset($fieldRuleDefaultValue[$field])) {
            $paaFieldRule->setDefaultValue($fieldRuleDefaultValue[$field]);
        }

        if (isset($fieldRuleMinValue[$field])) {
            $paaFieldRule->setMinValue($fieldRuleMinValue[$field]);
        }

        if (isset($fieldRuleMaxValue[$field])) {
            $paaFieldRule->setMaxValue($fieldRuleMaxValue[$field]);
        }

        if (isset($fieldRuleMinMaxType[$field])) {
            $paaFieldRule->setMinMaxType($fieldRuleMinMaxType[$field]);
        }

        if (isset($fieldType[$field])) {
            $paaFieldRule->setFieldType($fieldType[$field]);
        } else {
            $paaFieldRule->setFieldType($paaField->getFieldType());
        }

        if (isset($fieldRuleStep[$field])) {
            $paaFieldRule->setStep($fieldRuleStep[$field]);
        }

        $em->persist($paaFieldRule);

        return $ord;
    }

    /**
     * This method is used to save paa field rule.
     *
     * @param object $em
     * @param object $paaField
     * @param object $category
     * @param array  $fieldOrderRequired
     */
    protected function findTheOrder($currentOrder, $fieldOrderRequired = array())
    {
        if (count($fieldOrderRequired) > 0) {
            $values = array_values($fieldOrderRequired);
            if (!in_array($currentOrder, $values)) {
                return $currentOrder;
            } else {
                $currentOrder = $currentOrder + 1;
                return $this->findTheOrder($currentOrder, $fieldOrderRequired);
            }
        }

        return $currentOrder;
    }
}
