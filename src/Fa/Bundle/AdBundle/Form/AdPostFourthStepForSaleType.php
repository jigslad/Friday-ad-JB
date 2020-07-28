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
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * AdPostFourthStepForSaleType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostFourthStepForSaleType extends AdPostType
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
        $ad            = $event->getData();
        $form          = $event->getForm();
        $firstStepData = $this->getAdPostStepData('first');

        $this->addCategroyPaaFieldsForm($form, $firstStepData['category_id'], $ad);

        $fourthStepData = array_merge($this->orderedFields, $this->getFourthStepFields());

        $form->add('fourth_step_ordered_fields', HiddenType::class, array('data' => implode(',', $fourthStepData)));

        //$form->add('photo_error', TextType::class);
        if ($form->has('payment_method_id')) {
            $form->add(
                'paypal_email',
                EmailType::class,
                array(
                    'label' => 'PayPal email address',
                )
            )->add(
                'paypal_first_name',
                TextType::class,
                array(
                    'label' => 'PayPal first name',
                    'mapped' => false
                )
            )->add(
                'paypal_last_name',
                TextType::class,
                array(
                    'label' => 'PayPal last name',
                    'mapped' => false
                )
            );
        }
        if ($form->has('delivery_method_option_id')) {
            $form->add(
                'postage_price',
                NumberType::class,
                array(
                    'data' => ($ad && $ad->getPostagePrice() ? $ad->getPostagePrice() : null),
                )
            );
        }
        if (in_array('dimensions_length', $this->orderedFields)) {
            $form->add(
                'dimensions_unit',
                ChoiceType::class,
                array(
                     'mapped'   => false,
                     'expanded' => false,
                     'multiple' => false,
                     'choices'  => array_flip($this->em->getRepository('FaEntityBundle:CategoryDimension')->getDimensionUnitOptionsArray($this->container)),
                     'data'     => 'cm'
                 )
            );
        }

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
        return 'fa_paa_fourth_step_for_sale';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_fourth_step_for_sale';
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

        if ($form->has('payment_method_id')) {
            $this->validatePaypalEmail($form, $ad);
        }

        if ($form->has('delivery_method_option_id')) {
            $this->validatePostagePrice($form);
        }

        $this->validateBusinessAdField($form);
        $this->validateYoutubeField($form);
    }

    /**
     * Callbak method for postSubmit form event.
     *
     * @param object $event event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        $ad   = $event->getData();
        $form = $event->getForm();

        if ($form->has('payment_method_id')) {
            $paymentMethodId = $form->get('payment_method_id')->getData();
            $paypalEmail     = $form->get('paypal_email')->getData();
            $paypalFirstName = $form->get('paypal_first_name')->getData();
            $paypalLastName  = $form->get('paypal_last_name')->getData();

            // save paypal email address.
            if ($form->isValid() && $paypalEmail && $paypalFirstName && $paypalLastName && in_array($paymentMethodId, array(PaymentRepository::PAYMENT_METHOD_PAYPAL_ID, PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID))) {
                if (is_object($ad) && $ad->getId()) {
                    $adId = $ad->getId();
                } else {
                    $adId = $this->container->get('session')->get('ad_id');
                }

                $adObj = $this->em->getRepository('FaAdBundle:Ad')->find($adId);
                if ($adObj) {
                    $userObj = $adObj->getUser();
                    $userObj->setPaypalEmail($paypalEmail);
                    $userObj->setPaypalFirstName($paypalFirstName);
                    $userObj->setPaypalLastName($paypalLastName);
                    $userObj->setIsPaypalVefiried(1);
                    $this->em->persist($userObj);
                    $this->em->flush($userObj);
                }
            }
        }
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

        if (in_array($paaField['field'], array('leg_id', 'waist_id', 'neck_id', 'size_id', 'age_range_id', 'age_id', 'condition_id'))) {
            $fieldOptions['choices'] = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, true, 'ord'));
        }

        if ($defaultData) {
            $fieldOptions['data'] = $defaultData;
        }

        return $fieldOptions;
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
     * Validate paypal email address.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance.
     */
    protected function validatePaypalEmail($form, $ad)
    {
        if (is_object($ad) && $ad->getId()) {
            $adId = $ad->getId();
        } else {
            $adId = $this->container->get('session')->get('ad_id');
        }

        $adObj = $this->em->getRepository('FaAdBundle:Ad')->find($adId);

        if ($adObj && $adObj->getUser()) {
            $paymentMethodId = $form->get('payment_method_id')->getData();
            $paypalEmail     = $form->get('paypal_email')->getData();
            $paypalFirstName = $form->get('paypal_first_name')->getData();
            $paypalLastName = $form->get('paypal_last_name')->getData();

            if (in_array($paymentMethodId, array(PaymentRepository::PAYMENT_METHOD_PAYPAL_ID, PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID))) {
                if (!$paypalEmail || !$paypalFirstName || !$paypalLastName) {
                    $form->get('paypal_email')->addError(new FormError("PayPal account is not verified."));
                    $form->get('paypal_first_name')->addError(new FormError("PayPal account is not verified."));
                    $form->get('paypal_last_name')->addError(new FormError("PayPal account is not verified."));
                } elseif ($paypalEmail && $paypalFirstName && $paypalLastName) {
                    $isPaypalVerifiedEmail = $this->container->get('fa.paypal.account.verification.manager')->verifyPaypalAccountByEmail($paypalEmail, 'NAME', $paypalFirstName, $paypalLastName);
                    if (!$isPaypalVerifiedEmail) {
                        $form->get('paypal_email')->addError(new FormError("PayPal account is not verified."));
                        $form->get('paypal_first_name')->addError(new FormError("PayPal account is not verified."));
                        $form->get('paypal_last_name')->addError(new FormError("PayPal account is not verified."));
                    }
                }
            }
        }
    }

    /**
     * Validate postage price.
     *
     * @param object $form Form instance.
     */
    protected function validatePostagePrice($form)
    {
        $deliveryMethodId = $form->get('delivery_method_option_id')->getData();
        $postagePrice     = $form->get('postage_price')->getData();

        if ($postagePrice && (!is_numeric($postagePrice) || (is_numeric($postagePrice) && $postagePrice < 0)) && in_array($deliveryMethodId, array(DeliveryMethodOptionRepository::POSTED_ID, DeliveryMethodOptionRepository::POSTED_OR_COLLECT_ID))) {
            $form->get('postage_price')->addError(new FormError("Please enter valid postage price."));
        }
    }
}
