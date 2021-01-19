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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * AdPostFourthStepAdultType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostFourthStepAdultType extends AdPostType
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
        
        if ($form->has('payment_method_id')) {
            $form->add('paypal_email', EmailType::class, array('label' => 'PayPal email address'))
                    ->add('paypal_first_name', TextType::class, array('label' => 'PayPal first name', 'mapped' => false))
                    ->add('paypal_last_name', TextType::class, array('label' => 'PayPal last name','mapped' => false));
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
        return 'fa_paa_fourth_step_adult';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_fourth_step_adult';
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
        $this->validateBusinessAdField($form, $ad);
        $this->validateYoutubeField($form, $ad);
        $this->validateAdultRates($form, $ad);
        if ($form->has('payment_method_id')) {
            $this->validatePaypalEmail($form, $ad);
        }
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
     * Get step data to render on template.
     *
     * @return array
     */
    public function getFourthStepFields()
    {
        return array();
    }
}
