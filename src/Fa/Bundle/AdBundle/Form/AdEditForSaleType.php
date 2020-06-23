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
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * AdEditForSaleType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdEditForSaleType extends AdEditType
{
    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $ad            = $this->ad;
        $form          = $event->getForm();
        $categoryId    = $ad->getCategory()->getId();
        $adStatusId    = $ad->getStatus()->getId();
        $verticalObj   = $this->getVerticalObject($ad);
        $metaData      = $this->getField('meta_data', $verticalObj) ? unserialize($this->getField('meta_data', $verticalObj)) : null;
        $dimensionUnit = 'cm';

        if ($metaData && isset($metaData['dimensions_unit'])) {
            $dimensionUnit = $metaData['dimensions_unit'];
        }

        // check whether to fill data from moderation or not.
        $ad = $this->getAdObjectWithModeratedData($ad);

        $this->addCategroyPaaFieldsForm($form, $categoryId, $ad, true, $verticalObj);

        $form->add('paa_ordered_fields', HiddenType::class, array('data' => implode(',', $this->orderedFields), 'mapped' => false));
        //$form->add('photo_error', 'text', array('mapped' => false));
        if ($form->has('payment_method_id')) {
            $form->add(
                'paypal_email',
                EmailType::class,
                array(
                    'label' => 'Paypal email address',
                    'mapped' => false
                )
            )->add(
                'paypal_first_name',
                TextType::class,
                array(
                    'label' => 'Paypal first name',
                    'mapped' => false
                )
            )->add(
                'paypal_last_name',
                TextType::class,
                array(
                    'label' => 'Paypal last name',
                    'mapped' => false
                )
            );
        }
        if ($form->has('delivery_method_option_id')) {
            $form->add(
                'postage_price',
                NumberType::class,
                array(
                    'data' => ($ad && $ad->getPostagePrice() ? $ad->getPostagePrice() : 0),
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

        if (in_array($adStatusId, $this->em->getRepository('FaAdBundle:Ad')->getRepostButtonInEditAdStatus())) {
            $form->add('save', SubmitType::class, array('label' => 'Save and repost'));
        } elseif ($adStatusId == EntityRepository::AD_STATUS_DRAFT_ID) {
            $form->add('save', SubmitType::class, array('label' => 'Save and publish'));
        } else {
            $form->add('save', SubmitType::class, array('label' => 'Save'));
        }

        // Ad specific phone number field for business user.
        //$this->addBusinessAdField($form, $ad);
    }

    /**
     * Callbak method for POST_SUBMIT form event
     *
     * @param object $event Event instance
     */
    public function postSubmit(FormEvent $event)
    {
        $ad   = $this->ad;
        $form = $event->getForm();

        if ($form->isValid()) {
            $isNotNurseryCount = $this->validateNurseryLocation($form,$ad);
            if($isNotNurseryCount == 1) {
                $form->get('location_autocomplete')->addError(new FormError($this->translator->trans('', array(), 'validators')));
            } else {
                $this->saveAdOrSendForModeration($ad);
    
                if ($form->has('payment_method_id')) {
                    // save paypal email address.
                    $paymentMethodId = $form->get('payment_method_id')->getData();
                    $paypalEmail     = $form->get('paypal_email')->getData();
                    $paypalFirstName = $form->get('paypal_first_name')->getData();
                    $paypalLastName  = $form->get('paypal_last_name')->getData();
                    if ($paypalEmail && $paypalFirstName && $paypalLastName && in_array($paymentMethodId, array(PaymentRepository::PAYMENT_METHOD_PAYPAL_ID, PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID))) {
                        $adObj = $this->em->getRepository('FaAdBundle:Ad')->find($ad->getId());
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
        $ad   = $this->ad;
        $form = $event->getForm();

        $this->validatePrice($form);
        $this->validateAdEditLocation($form,$ad);
        $this->validateDescription($form);
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
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_edit_for_sale';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_edit_for_sale';
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

        if ($paaField['field'] == 'qty' && !$defaultData) {
            $defaultData = 1;
        }

        if ($defaultData) {
            $fieldOptions['data'] = $defaultData;
        }

        return $fieldOptions;
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
            $paypalLastName  = $form->get('paypal_last_name')->getData();

            if (in_array($paymentMethodId, array(PaymentRepository::PAYMENT_METHOD_PAYPAL_ID, PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID))) {
                if (!$paypalEmail || !$paypalFirstName || !$paypalFirstName) {
                    $form->get('paypal_email')->addError(new FormError('Paypal account is not verified.'));
                    $form->get('paypal_first_name')->addError(new FormError("Paypal account is not verified."));
                    $form->get('paypal_last_name')->addError(new FormError("Paypal account is not verified."));
                } elseif ($paypalEmail && $paypalFirstName && $paypalLastName) {
                    $isPaypalVerifiedEmail = $this->container->get('fa.paypal.account.verification.manager')->verifyPaypalAccountByEmail($paypalEmail, 'NAME', $paypalFirstName, $paypalLastName);
                    if (!$isPaypalVerifiedEmail) {
                        $form->get('paypal_email')->addError(new FormError('Paypal account is not verified.'));
                        $form->get('paypal_first_name')->addError(new FormError("Paypal account is not verified."));
                        $form->get('paypal_last_name')->addError(new FormError("Paypal account is not verified."));
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
    
    protected function validateNurseryLocation($form,$ad = null) {
        $adIdArray   = array();
        $adIdArray[] = $adId = $ad->getId();
        $getPackageRuleArray = $getActivePackage = array();
        $isNotNurseryCount = 0;
        
        if ($form->has('location') && $form->get('location')->getData()!='') {
            $getLocationId = $form->get('location')->getData();
            $getActivePackage = $this->em->getRepository('FaAdBundle:AdUserPackage')->getAdActiveModerationPackageArrayByAdId($adIdArray);
            if ($getActivePackage) {
                if($getActivePackage[$adId]['package_price']==0) {
                    $getPackageRuleArray = $this->em->getRepository('FaPromotionBundle:PackageRule')->getPackageRuleArrayByPackageId($getActivePackage[$adId]['package_id']);
                    if(!empty($getPackageRuleArray)) {
                        if($getPackageRuleArray[0]['location_group_id']==14) {
                            $nurseryGroupCount = $this->em->getRepository('FaEntityBundle:LocationGroupLocation')->checkIsNurseryGroup($getLocationId);
                            if($nurseryGroupCount==0) {
                                $isNotNurseryCount = 1;
                                return $isNotNurseryCount;
                            }
                        }
                    }
                }
            }
        }
        return $isNotNurseryCount;
    }   
}
