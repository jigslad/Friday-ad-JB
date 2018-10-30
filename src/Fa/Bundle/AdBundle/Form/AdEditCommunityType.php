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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Form\AdEditType;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;

/**
 * AdEditCommunityType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdEditCommunityType extends AdEditType
{
    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_edit_community';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_edit_community';
    }

    /**
     * (non-PHPdoc)
     * @see \Fa\Bundle\AdBundle\Form\AdPostAdminType::getRootCategoryId()
     *
     * @return integer
     */
    protected function getRootCategoryId()
    {
        return CategoryRepository::COMMUNITY_ID;
    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad   = $this->ad;
        $form = $event->getForm();

        $this->validatePrice($form);
        $this->validateEventDate($form);
        $this->validateAdLocation($form);
        $this->validateDescription($form);
        $this->validateBusinessAdField($form);
        $this->validateYoutubeField($form);
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

        if ($paaField['field'] == 'include_end_time') {
            $fieldOptions['choices'] = array('Include end time' => '1');
        }

        if (in_array($paaField['field'], array('class_size_id', 'experience_level_id', 'availability_id', 'level_id'))) {
            $fieldOptions['choices']     = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, false));
            $fieldOptions['placeholder'] = 'Select '.$paaField['label'];
        }

        if ($defaultData) {
            $fieldOptions['data'] = $defaultData;
        }

        return $fieldOptions;
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
