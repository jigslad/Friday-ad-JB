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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * AdPostFourthStepPropertyType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostFourthStepPropertyType extends AdPostType
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
    protected $step = 4;

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('preview', SubmitType::class, array('label' => 'Preview my ad'))
        ->add('save', SubmitType::class, array('label' => 'Next step: Your Ad type'))
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'))
        ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
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

        $fourthStepData = array_merge($this->orderedFields, $this->getFourthStepFields());

        $form->add('fourth_step_ordered_fields', HiddenType::class, array('data' => implode(',', $fourthStepData)));

        //$form->add('photo_error', 'text');

        // Ad specific phone number field for business user.
        //$this->addBusinessAdField($form, $ad);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_fourth_step_property';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_fourth_step_property';
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => null,
                'translation_domain' => 'frontend-paa-fourth-step',
            )
        );
    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad   = $event->getData();
        $form = $event->getForm();

        $this->validateAdLocation($form);
        $this->validateAdImageLimit($form);
        $this->validateDateAvailable($form);
        $this->validateDeposit($form);
        $this->validateBusinessAdField($form);
        $this->validateYoutubeField($form);
    }

    /**
     * Get step data to render on template.
     *
     * @return array
     */
    public function getFourthStepFields()
    {
        return array();
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

        if (in_array($paaField['field'], array('number_of_bedrooms_id', 'number_of_bathrooms_id', 'furnishing_id', 'smoking_allowed_id',
            'pets_allowed_id', 'dss_tenants_allowed_id', 'number_of_rooms_in_property_id',
            'number_of_rooms_available_id', 'rooms_for_id', 'room_size_id', 'ownership_id', 'lease_type_id'))) {
            $fieldOptions['choices']     = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, false));
            $fieldOptions['placeholder'] = 'Select '.strtolower($paaFieldRule['label']);
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
                $form->get('date_available')->addError(new FormError($this->translator->trans('Date available is invalid.', array(), 'validators')));
                $isDateAvailableValid = false;
            }

            if ($isDateAvailableValid) {
                $date = explode('/', $form->get('date_available')->getData());
                if (!checkdate($date[1], $date[0], $date[2])) {
                    $form->get('date_available')->addError(new FormError($this->translator->trans('Date available is invalid.', array(), 'validators')));
                    $isDateAvailableValid = false;
                }
            }

            if ($isDateAvailableValid) {
                if (strtotime(str_replace('/', '-', $form->get('date_available')->getData())) < mktime(0, 0, 0, date("m")  , date("d")-1, date("Y"))) {
                    $form->get('date_available')->addError(new FormError($this->translator->trans('Date available should not be less than today.', array(), 'validators')));
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
                $form->get('deposit')->addError(new FormError($this->translator->trans('Deposit is invalid.', array(), 'validators')));
            }
        }
    }
}
