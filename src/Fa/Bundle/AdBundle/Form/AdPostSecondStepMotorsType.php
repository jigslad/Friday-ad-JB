<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Form;

use Fa\Bundle\AdBundle\Form\AdPostSecondStepType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * AdPostSecondStepType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostSecondStepMotorsType extends AdPostSecondStepType
{
    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $isRenderCategoryDimension = false;

    /**
     * Need to show field in which step in posting
     *
     * @var integer
     */
    protected $step = 2;

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $ad            = $event->getData();
        $form          = $event->getForm();
        $firstStepData = $this->getAdPostStepData('first');

        $this->addCategroyPaaFieldsForm($form, $firstStepData['category_id'], $ad);

        $secondStepData = array_merge($this->orderedFields, $this->getSecondStepFields());

        $form->add('second_step_ordered_fields', HiddenType::class, array('data' => implode(',', $secondStepData)));
    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad   = $event->getData();
        $form = $event->getForm();

        $this->validatePrice($form);
        $this->validateRegNo($form);
        $this->validateDescription($form);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_second_step_motors';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_second_step_motors';
    }

    /**
     * Get step data to render on template.
     *
     * @return array
     */
    public function getSecondStepFields()
    {
        return array(
                   //'save',
               );
    }

    /**
     * Get form field options.
     *
     * @param array  $paaFieldRule PAA field rule array
     * @param object $ad           Ad instance
     * @param object $verticalObj  Vertical instance
     */
    protected function getPaaFieldOptions($paaFieldRule, $ad = null, $verticalObj = null)
    {
        $paaField     = $paaFieldRule['paa_field'];
        $fieldOptions = parent::getPaaFieldOptions($paaFieldRule, $ad, $verticalObj);

        $defaultData = null;
        if (isset($fieldOptions['data']) && $fieldOptions['data']) {
            $defaultData = $fieldOptions['data'];
        }

        if ($this->getPaaFieldType($paaField) == 'range') {
            if (in_array($paaField['field'], array('mot_expiry_year', 'road_tax_expiry_year'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getYearChoices());
                $fieldOptions['placeholder'] = 'Year';
            } elseif (in_array($paaField['field'], array('reg_year'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getRegYearChoices());
                $fieldOptions['placeholder'] = 'Year';
            } elseif (in_array($paaField['field'], array('mot_expiry_month', 'road_tax_expiry_month'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getMonthChoices());
                $fieldOptions['placeholder'] = 'Month';
            }
        }

        if (in_array($paaField['field'], array('no_of_doors', 'no_of_seats'))) {
            $fieldOptions['attr']['class'] = isset($fieldOptions['attr']['class']) ? $fieldOptions['attr']['class'].' door-no' : 'door-no';
        }

        if (in_array($paaField['field'], array('reg_year'))) {
            $fieldOptions['attr']['class'] = isset($fieldOptions['attr']['class']) ? $fieldOptions['attr']['class'].' reg-year' : 'reg-year';
        }

        if (in_array($paaField['field'], array('fuel_type_id'))) {
            $fuelTypeChoices = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, true);
            $petrolKey       = array_search('Petrol', $fuelTypeChoices);

            if ($petrolKey !== false) {
                unset($fuelTypeChoices[$petrolKey]);
                $fuelTypeChoices = array($petrolKey => 'Petrol') + $fuelTypeChoices;
            }

            $fieldOptions['choices'] = array_flip($fuelTypeChoices);
        }

        if ($defaultData) {
            $fieldOptions['data'] = $defaultData;
        }

        return $fieldOptions;
    }

    /**
     * Validate price.
     *
     * @param object $form Form instance.
     */
    protected function validatePrice($form)
    {
        if ($form->has('price')) {
            if ($form->get('price')->getData() == '') {
                $form->get('price')->addError(new FormError('Value should not be blank.'));
            } else {
                if (!preg_match('/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $form->get('price')->getData(), $matches)) {
                    $form->get('price')->addError(new FormError('Price is invalid.'));
                }
            }
        }
    }

    /**
     * Validate reg no.
     *
     * @param object $form Form instance.
     */
    protected function validateRegNo($form)
    {
        if ($form->has('has_reg_no') && $form->get('has_reg_no')->getData() == 1) {
            if ($form->get('reg_no')->getData()) {
                $carWebData = $this->container->get('fa.webcar.manager')->findByVRM($form->get('reg_no')->getData());
                if (isset($carWebData['error'])) {
                    $form->get('reg_no')->addError(new FormError('Please enter correct registration number.'));
                }
            } else {
                $form->get('reg_no')->addError(new FormError('Please enter registration number.'));
            }
        }
    }
}
