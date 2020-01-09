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
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * AdPostForSaleAdminType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostForSaleAdminType extends AdPostAdminType
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
        $dimensionUnit = 'cm';
        if (!$ad->getId()) {
            $categoryId    = $this->request->get('category_id', null);
            $form->add('publish', SubmitType::class);
        } else {
            $categoryId = $ad->getCategory()->getId();

            // add publish button If ad is not live yet
            if ($ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID) {
                $form->add('publish', SubmitType::class);
            }

            $adForSale = $this->em->getRepository('FaAdBundle:AdForSale')->findOneBy(array('ad' => $ad->getId()));
            $metaData = $this->getField('meta_data', $adForSale) ? unserialize($this->getField('meta_data', $adForSale)) : null;
            if ($metaData && isset($metaData['dimensions_unit'])) {
                $dimensionUnit = $metaData['dimensions_unit'];
            }

            // check whether to fill data from moderation or not.
            $ad = $this->getAdObjectWithModeratedData($ad);
        }

        $this->addCategroyPaaFieldsForm($form, $categoryId, $ad);

        $form->add('paa_ordered_fields', HiddenType::class, array('data' => implode(',', $this->orderedFields), 'mapped' => false));
        //$form->add('photo_error', TextType::class, array('mapped' => false));
        if ($form->has('payment_method_id')) {
            $form->add(
                'paypal_email',
                EmailType::class,
                array(
                    'label' => 'PayPal Email Address',
                    'mapped' => false
                )
            )->add(
                'paypal_first_name',
                TextType::class,
                array(
                    'label' => 'PayPal First Name',
                    'mapped' => false
                )
            )->add(
                'paypal_last_name',
                TextType::class,
                array(
                    'label' => 'PayPal Last Name',
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
                    'expanded' => true,
                    'multiple' => false,
                    'choices'  => array_flip($this->em->getRepository('FaEntityBundle:CategoryDimension')->getDimensionUnitOptionsArray($this->container)),
                    'data'     => $dimensionUnit
                )
            );
        }

        $form->add('return_url', HiddenType::class, array('data' => $this->request->get('return_url', null), 'mapped' => false));

        $this->addDetachedAdFields($form, $ad);
        $this->addFutureAdPostFields($form, $ad);
        //$this->addBusinessAdField($form, $ad);

        if ($this->isDetachedAd($ad)) {
            $form->remove('delivery_method_option_id');
            $form->remove('payment_method_id');
            $form->remove('paypal_email');
            $form->remove('paypal_first_name');
            $form->remove('paypal_last_name');
            $form->remove('postage_price');
        }
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
                $data          = $this->request->get('fa_paa_for_sale_admin');

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

                // save paypal email address.
                if (!$this->isDetachedAd($ad)) {
                    $paymentMethodId = null;
                    $paypalEmail     = null;
                    $paypalFirstName = null;
                    $paypalLastName  = null;

                    if ($form->has('payment_method_id')) {
                        $paymentMethodId = $form->get('payment_method_id')->getData();
                    }

                    if ($form->has('paypal_email')) {
                        $paypalEmail = $form->get('paypal_email')->getData();
                        $paypalFirstName = $form->get('paypal_first_name')->getData();
                        $paypalLastName = $form->get('paypal_last_name')->getData();
                    }

                    if ($paypalFirstName && $paypalLastName && in_array($paymentMethodId, array(PaymentRepository::PAYMENT_METHOD_PAYPAL_ID, PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID))) {
                        $adObj = $this->em->getRepository('FaAdBundle:Ad')->find($form->getData()->getId());
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

        $this->validateAdLocation($form);
        $this->validatePrice($form);
        $this->validateDescription($form);

        if (!$this->isDetachedAd($ad)) {
            if ($form->has('payment_method_id')) {
                $this->validatePaypalEmail($form, $ad);
            }
            if ($form->has('delivery_method_option_id')) {
                $this->validatePostagePrice($form);
            }
        }

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
        return 'fa_paa_for_sale_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_for_sale_admin';
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

        if (in_array($paaField['field'], array('leg_id', 'waist_id', 'neck_id', 'size_id', 'age_range_id', 'age_id', 'condition_id'))) {
            $fieldOptions['choices'] = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, true, 'ord'));
        }

        if ($paaField['field'] == 'qty' && !$defaultData) {
            $defaultData = 1;
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
        return CategoryRepository::FOR_SALE_ID;
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
            $adId = $this->container->get('session')->get('admin_ad_id_'.$form->get('admin_ad_counter')->getData());
        }

        $adObj = $this->em->getRepository('FaAdBundle:Ad')->find($adId);

        if ($adObj && $adObj->getUser()) {
            $paymentMethodId = $form->get('payment_method_id')->getData();
            $paypalEmail     = $form->get('paypal_email')->getData();
            $paypalFirstName = $form->get('paypal_first_name')->getData();
            $paypalLastName  = $form->get('paypal_last_name')->getData();

            if (in_array($paymentMethodId, array(PaymentRepository::PAYMENT_METHOD_PAYPAL_ID, PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID))) {
                if (!$paypalEmail || !$paypalFirstName || !$paypalLastName) {
                    $form->get('paypal_email')->addError(new FormError('PayPal account is not verified.'));
                    $form->get('paypal_first_name')->addError(new FormError("PayPal account is not verified."));
                    $form->get('paypal_last_name')->addError(new FormError("PayPal account is not verified."));
                } elseif ($paypalEmail && $paypalFirstName && $paypalLastName) {
                    $isPaypalVerifiedEmail = $this->container->get('fa.paypal.account.verification.manager')->verifyPaypalAccountByEmail($paypalEmail, 'NAME', $paypalFirstName, $paypalLastName);
                    if (!$isPaypalVerifiedEmail) {
                        $form->get('paypal_email')->addError(new FormError('PayPal account is not verified.'));
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

    /**
     * Validate price based on ad type selected.
     *
     * @param object $form Form instance.
     */
    protected function validatePrice($form)
    {
        if ($form->has('ad_type_id') && $form->get('ad_type_id')->getData()) {
            if ($form->get('ad_type_id')->getData() == EntityRepository::AD_TYPE_SWAPPING_ID) {
                if ($form->has('price_text') && $form->get('price_text')->getData() == '') {
                    $form->get('price_text')->addError(new FormError('Value should not be blank.'));
                }
            } else {
                if ($form->has('price') && $form->get('ad_type_id')->getData() != EntityRepository::AD_TYPE_FREETOCOLLECTOR_ID) {
                    if ($form->get('price')->getData() == '') {
                        if ($form->get('ad_type_id')->getData() != EntityRepository::AD_TYPE_WANTED_ID) {
                            $form->get('price')->addError(new FormError('Value should not be blank.'));
                        }
                    } else {
                        if (!preg_match('/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $form->get('price')->getData(), $matches)) {
                            $form->get('price')->addError(new FormError('Price is invalid.'));
                        }
                    }
                }
            }
        }
    }
}
