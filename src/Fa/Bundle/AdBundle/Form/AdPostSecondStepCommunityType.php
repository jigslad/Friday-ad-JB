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
use Symfony\Component\Form\FormError;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * AdPostSecondStepType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostSecondStepCommunityType extends AdPostSecondStepType
{
    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $isRenderCategoryDimension = false;

    /**
     * steps.
     *
     * @var integer
     */
    protected $step = 2;

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_second_step_community';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_second_step_community';
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
     * Get form field options.
     *
     * @param array  $paaFieldRule PAA field rule array
     * @param object $ad           Ad instance
     * @param object $verticalObj  Vertical instance
     */
    protected function getPaaFieldOptions($paaFieldRule, $ad = null, $verticalObj = null)
    {
        $fieldOptions = parent::getPaaFieldOptions($paaFieldRule, $ad, $verticalObj);

        $paaField = $paaFieldRule['paa_field'];

        if ($paaFieldRule['default_value']) {
            $fieldOptions['data'] = $paaFieldRule['default_value'];
        }

        if ($paaField['field'] == 'include_end_time') {
            $fieldOptions['choices'] = array('Include end time' => '1');
        }

        return $fieldOptions;
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

        $this->validateEventDate($form);
        $this->validatePrice($form);
        $this->validateDescription($form);
    }

    /**
     * Add date field validation.
     *
     * @param object $form Form instance.
     */
    protected function validateEventDate($form)
    {
        if (!$form->has('event_start')) {
            return true;
        }

        $eventStart = $form->get('event_start')->getData();
        $eventStartTime = $form->get('event_start_time')->getData();
        $eventEnd   = $form->get('event_end')->getData();
        $eventEndTime = $form->get('event_end_time')->getData();
        $includeEndTime = $form->get('include_end_time')->getData();
        $validStartDate = true;
        $validEndDate = true;
        $validStartTime = true;
        $validEndTime = true;

        if ($eventStart || $eventEnd || $eventStartTime || $eventEndTime) {
            if ($eventStart) {
                if (!preg_match('/^\d{1,2}\/\d{1,2}\/(\d{2}|\d{4})$/', $eventStart)) {
                    $form->get('event_start')->addError(new FormError('Event start date is invalid.'));
                    $validStartDate = false;
                } else {
                    $date = explode('/', $eventStart);
                    if (!checkdate($date[1], $date[0], $date[2])) {
                        $form->get('event_start')->addError(new FormError('Event start date is invalid.'));
                        $validStartDate = false;
                    }
                }
            } else {
                if ($eventStartTime) {
                    $form->get('event_start')->addError(new FormError('Please enter event start date.'));
                    $validStartDate = false;
                }
            }

            if ($eventEnd) {
                if (!preg_match('/^\d{1,2}\/\d{1,2}\/(\d{2}|\d{4})$/', $eventEnd)) {
                    $form->get('event_end')->addError(new FormError('Event end date is invalid.'));
                    $validEndDate = false;
                } else {
                    $date = explode('/', $eventEnd);
                    if (!checkdate($date[1], $date[0], $date[2])) {
                        $form->get('event_end')->addError(new FormError('Event end date is invalid.'));
                        $validEndDate = false;
                    }
                }
            } else {
                if ($eventEndTime) {
                    $form->get('event_end')->addError(new FormError('Please enter event end date.'));
                    $validEndDate = false;
                }
            }

            if ($eventStartTime) {
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $eventStartTime)) {
                    $form->get('event_start_time_autocomplete')->addError(new FormError('Event start time is invalid.'));
                    $validStartTime = false;
                }
            }

            if ($eventEndTime) {
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $eventEndTime)) {
                    $form->get('event_end_time_autocomplete')->addError(new FormError('Event end time is invalid.'));
                    $validEndTime = false;
                }
            }

            if (!$includeEndTime && ($eventEnd || $eventEndTime)) {
                $form->get('event_end_time_autocomplete')->addError(new FormError('You can not include end time.'));
                $validEndTime = false;
            }

            if ($validStartDate && $validEndDate && $validStartTime && $validEndTime) {
                $start = ($eventStart ? $eventStart : '').($eventStartTime ?' '.$eventStartTime: '');
                $end   = ($eventEnd ? $eventEnd : '').($eventEndTime ?' '.$eventEndTime: '');

                if ($start != '' && $end != '') {
                    if (strtotime(str_replace('/', '-', $start)) >= strtotime(str_replace('/', '-', $end))) {
                        $form->get('event_start')->addError(new FormError('Event start should be before event end.'));
                    }
                } elseif ($start == '' && $end != '') {
                    $form->get('event_start')->addError(new FormError('Please add event start.'));
                } elseif ($start != '' && $end == '') {
                    if (strtotime(str_replace('/', '-', $start)) <= time()) {
                        $form->get('event_start')->addError(new FormError('Event start should not be before current date and time.'));
                    }
                }
            }
        }
    }

    /**
     * Validate price.
     *
     * @param object $form Form instance.
     */
    protected function validatePrice($form)
    {
        if ($form->has('price')) {
            if ($form->get('price')->getData() != '') {
                if (!preg_match('/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $form->get('price')->getData(), $matches)) {
                    $form->get('price')->addError(new FormError('Price is invalid.'));
                }
            }
        }
    }
}
