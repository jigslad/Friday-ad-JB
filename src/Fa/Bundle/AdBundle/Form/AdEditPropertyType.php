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
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\AdBundle\Form\AdEditType;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;

/**
 * AdEditPropertyType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdEditPropertyType extends AdEditType
{
    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_edit_property';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_edit_property';
    }

    /**
     * (non-PHPdoc)
     * @see \Fa\Bundle\AdBundle\Form\AdPostAdminType::getRootCategoryId()
     *
     * @return integer
     */
    protected function getRootCategoryId()
    {
        return CategoryRepository::PROPERTY_ID;
    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad   = $this->ad;
        $form = $event->getForm();

        $this->validatePrice($form);
        $this->validateAdLocation($form);
        $this->validateDateAvailable($form);
        $this->validateDeposit($form);
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

        if (in_array($paaField['field'], array('rent_per_id'))) {
            $fieldOptions['choices'] = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, false);
        }

        if (in_array($paaField['field'], array('ad_type_id'))) {
            $fieldOptions['choices'] = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, true, 'ord');
        }

        if (in_array($paaField['field'], array('number_of_bedrooms_id', 'number_of_bathrooms_id', 'furnishing_id', 'smoking_allowed_id',
            'pets_allowed_id', 'dss_tenants_allowed_id', 'number_of_rooms_in_property_id',
            'number_of_rooms_available_id', 'rooms_for_id', 'room_size_id', 'ownership_id', 'lease_type_id'))) {
            $fieldOptions['choices']     = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, false);
            $fieldOptions['empty_value'] = 'Select '.strtolower($paaFieldRule['label']);
        }

        if ($defaultData) {
            $fieldOptions['data'] = $defaultData;
        }

        if ($paaField['field'] == 'deposit' && isset($fieldOptions['constraints']) && count($fieldOptions['constraints'])) {
            $constraints = $fieldOptions['constraints'];
            foreach ($fieldOptions['constraints'] as $key => $constraint) {
                if (is_object($constraint) && get_class($constraint) == 'Symfony\Component\Validator\Constraints\Regex') {
                    unset($constraints[$key]);
                }
            }

            $fieldOptions['constraints'] = $constraints;
        }

        return $fieldOptions;
    }

    /**
     * Add date available field validation.
     *
     * @param object $form Form instance.
     */
    protected function validateDateAvailable($form)
    {
        if ($form->has('date_available') && $form->get('date_available')->getData()) {
            $isDateAvailableValid = true;
            if (!preg_match('/^\d{1,2}\/\d{1,2}\/(\d{2}|\d{4})$/', $form->get('date_available')->getData())) {
                $form->get('date_available')->addError(new FormError('Date available is invalid.'));
                $isDateAvailableValid = false;
            }

            if ($isDateAvailableValid) {
                $date = explode('/', $form->get('date_available')->getData());
                if (!checkdate($date[1], $date[0], $date[2])) {
                    $form->get('date_available')->addError(new FormError('Date available is invalid.'));
                    $isDateAvailableValid = false;
                }
            }

            if ($isDateAvailableValid) {
                if (strtotime(str_replace('/', '-', $form->get('date_available')->getData())) < mktime(0, 0, 0, date("m")  , date("d")-1, date("Y"))) {
                    $form->get('date_available')->addError(new FormError('Date available should not be less than today.'));
                }
            }
        }
    }

    /**
     * Validate deposit.
     *
     * @param object $form Form instance.
     */
    protected function validateDeposit($form)
    {
        if ($form->has('deposit') && $form->get('deposit')->getData()) {
            if (!preg_match('/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $form->get('deposit')->getData(), $matches)) {
                $form->get('deposit')->addError(new FormError('Deposit is invalid.'));
            }
        }
    }
}
