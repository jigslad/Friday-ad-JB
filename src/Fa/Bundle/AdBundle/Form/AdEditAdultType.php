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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * AdEditAdultType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdEditAdultType extends AdEditType
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
    
    
    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_edit_adult';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_edit_adult';
    }

    /**
     * (non-PHPdoc)
     * @see \Fa\Bundle\AdBundle\Form\AdPostAdminType::getRootCategoryId()
     *
     * @return integer
     */
    protected function getRootCategoryId()
    {
        return CategoryRepository::ADULT_ID;
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

        $this->validateAdLocation($form);
        $this->validateDescription($form);
        $this->validateBusinessAdField($form);
        $this->validateYoutubeField($form);
        $this->validateAdultRates($form, $ad);
        if ($form->has('payment_method_id')) {
            $this->validatePaypalEmail($form, $ad);
        }
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
}
