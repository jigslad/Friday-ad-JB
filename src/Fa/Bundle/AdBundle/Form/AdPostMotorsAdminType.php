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
use Fa\Bundle\AdBundle\Entity\AdMotors;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * AdPostMotorsAdminType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostMotorsAdminType extends AdPostAdminType
{
    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $isRenderCategoryDimension = false;

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add(
            'category_id',
            HiddenType::class,
            array(
                'mapped' => false,
                'data' => $this->request->get('category_id', null)
            )
        )
        ->add(
            'user_id',
            HiddenType::class,
            array(
                'mapped' => false,
                'data' => $this->request->get('user_id', null)
            )
        )
        ->add('save', SubmitType::class)
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'))
        ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $ad   = $event->getData();
        $form = $event->getForm();

        // New form
        if (!$ad->getId()) {
            $categoryId = $this->request->get('category_id', null);
            $form->add('publish', SubmitType::class);
        } else {
            $categoryId = $ad->getCategory()->getId();

            // add publish button If ad is not live yet
            if ($ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID) {
                $form->add('publish', SubmitType::class);
            }

            // check whether to fill data from moderation or not.
            $ad = $this->getAdObjectWithModeratedData($ad);
        }

        $this->addCategroyPaaFieldsForm($form, $categoryId, $ad);

        $form->add('paa_ordered_fields', HiddenType::class, array('data' => implode(',', $this->orderedFields), 'mapped' => false));
        //$form->add('photo_error', 'text', array('mapped' => false));

        $form->add('return_url', HiddenType::class, array('data' => $this->request->get('return_url', null), 'mapped' => false));

        $this->addDetachedAdFields($form, $ad);
        $this->addFutureAdPostFields($form, $ad);
        //$this->addBusinessAdField($form, $ad);
    }

    /**
     * Callbak method for POST_SUBMIT form event
     *
     * @param object $event Event instance
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $ad   = $event->getData();

        if ($form->isValid()) {
            if ($form->getData()->getId()) {
                $adPostManager = $this->container->get('fa_ad.manager.ad_post');
                $data          = $this->request->get('fa_paa_motors_admin');

                $data['user_id']      = ($ad->getUser() ? $ad->getUser()->getId() : 'no_user');
                $data['category_id']  = $ad->getCategory()->getId();
                $preStatusId          = $ad->getStatus()->getId();

                if ($ad->getStatus()->getId() == EntityRepository::AD_STATUS_IN_MODERATION_ID) {
                    $data['ad_status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                } else {
                    $data['ad_status_id'] = $ad->getStatus()->getId();
                }
                $adPostManager->saveAd($data, $form->getData()->getId(), true, true);

                if ($preStatusId == EntityRepository::AD_STATUS_IN_MODERATION_ID) {
                    $this->em->getRepository('FaAdBundle:AdModerate')->applyModerationOnLiveAd($ad->getId(), $this->container, false);
                }
            }
        }
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

        $this->validateRegNo($form);
        $this->validatePrice($form);
        $this->validateAdLocation($form);
        $this->validateDescription($form);

        if ($ad && $ad->getId()) {
            $this->validateAdImageLimit($form, $ad);
        } else {
            $this->validateAdImageLimit($form);
        }

        $this->validateDetachedAdFields($form, $ad);
        $this->validateFutureAdPostFields($form, $ad);
        $this->validateBusinessAdField($form, $ad);
        $this->validateYoutubeField($form, $ad);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_motors_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_motors_admin';
    }

    /**
     * Get form field options.
     *
     * @param array  $paaFieldRule PAA field rule array
     * @param object $ad           Ad instance
     * @param object $verticalObj  Vertical instance
     */
    protected function getPaaFieldOptions($paaFieldRule, $categoryId, $ad = null, $verticalObj = null)
    {
        $paaField     = $paaFieldRule['paa_field'];
        $fieldOptions = parent::getPaaFieldOptions($paaFieldRule, $categoryId, $ad, $verticalObj);

        $defaultData = null;
        if (isset($fieldOptions['data']) && $fieldOptions['data']) {
            $defaultData = $fieldOptions['data'];
        }

        if ($this->getPaaFieldType($paaField) == 'range') {
            if (in_array($paaField['field'], array('mot_expiry_year', 'road_tax_expiry_year'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getYearChoices());
                $fieldOptions['placeholder'] = 'Year';
            } elseif (in_array($paaField['field'], array('reg_year', 'year_built'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getRegYearChoices());
                $fieldOptions['placeholder'] = 'Year';
            } elseif (in_array($paaField['field'], array('mot_expiry_month', 'road_tax_expiry_month'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getMonthChoices());
                $fieldOptions['placeholder'] = 'Month';
            }
        }

        if (in_array($paaField['field'], array('tonnage_id', 'berth_id', 'condition_id', 'number_of_stalls_id'))) {
            $fieldOptions['choices']     = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, false));
            $fieldOptions['placeholder'] = 'Select '.$paaField['label'];
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
     * (non-PHPdoc)
     * @see \Fa\Bundle\AdBundle\Form\AdPostAdminType::getRootCategoryId()
     *
     * @return integer
     */
    protected function getRootCategoryId()
    {
        return CategoryRepository::MOTORS_ID;
    }

    /**
     * (non-PHPdoc)
     * @see \Fa\Bundle\AdBundle\Form\AdPostAdminType::setModerationValue()
     *
     * @param array $values
     */
    protected function setModerationValue($value = array())
    {
        $this->moderationValue = $value;
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
