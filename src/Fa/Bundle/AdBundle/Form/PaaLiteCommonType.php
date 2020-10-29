<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
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
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdImage;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
// use Fa\Bundle\EntityBundle\Repository\PaaFieldRuleRepository;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\CoreBundle\Manager\ThumbnailManager;
use Fa\Bundle\EntityBundle\Entity\Category;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageUpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdUserPackage;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * PaaLiteCommonType form.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class PaaLiteCommonType extends PaaLiteType
{

    /**
     * Container service class object.
     *
     * @var object
     */
    protected $container;

    /**
     * Request service class object.
     *
     * @var object
     */
    protected $request;
    
    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $isRenderCategoryDimension = false;

    /**
     * Need to render form step.
     *
     * @var boolean
     */
    protected $formstep = null;

    /**
     * Need to render campaign name
     *
     * @var string
     */
    protected $campaign_name = null;

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->campaign_name = isset($options['data']['campaign_name']) ? $options['data']['campaign_name'] : null;

        $builder->add('category_id', HiddenType::class);
        $this->addCategoryChoiceFields($builder, $this->campaign_name);
        if ($this->container->get('session')->has('paa_skip_login_step')) {
            $builder->add('save', SubmitType::class, array(
                'label' => 'Save my ad'
            ));
        } else {
            $builder->add('save', SubmitType::class, array(
                'label' => 'Save my ad'
            ));
        }
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array(
            $this,
            'preSetData'
        ));
        $builder->addEventListener(FormEvents::SUBMIT, array(
            $this,
            'onSubmit'
        ));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
    }

    /**
         * Callbak method for PRE_SET_DATA form event.
         *
         * @param object $event
         *            Event instance.
         */
    public function preSetData(FormEvent $event)
    {
        $ad = $event->getData();
        $form = $event->getForm();
        
        $this->addCampaignPaaLiteFieldsForm($form, $this->campaign_name, $ad);
        //$this->resetCategoryCookie();
        /*foreach ($form->all() as $field) {
        }*/
        if (in_array('payment_method_id', $this->orderedFields)) {
            $form->add(
                    'paypal_email',
                    EmailType::class,
                    array(
                        'label' => 'PayPal email address',
                        'mapped' => false
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
            //array_push($this->orderedFields,'paypal_email','paypal_first_name','paypal_last_name');
        }
        if (in_array('delivery_method_option_id', $this->orderedFields)) {
            $form->add('postage_price', NumberType::class);
            //array_push($this->orderedFields,'postage_price');
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
                )
            );
            //array_push($this->orderedFields,'dimensions_unit');
        }

        $paaLiteOrderedData = array_merge($this->orderedFields, $this->getPaaLiteOrderedFields());
        $form->add('paa_lite_ordered_fields', HiddenType::class, array('data' => implode(',', $paaLiteOrderedData)));
    }

    

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_lite_common';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_lite_common';
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'translation_domain' => 'frontend-paa-lite-common'
        ));
    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event
     *            Event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad = $event->getData();
        $form = $event->getForm();
        $explodePaaFlds = explode(',', $ad['paa_lite_ordered_fields']);

        $this->validateAdLocation($form);

        if (in_array('has_reg_no', $explodePaaFlds)) {
            $this->validateRegNo($form);
        }
        
        if ($form->has('price') || $form->has('price_text')) {
            $this->validatePrice($form);
        }

        if (in_array('description', $explodePaaFlds)) {
            $this->validateDescription($form);
        }

        if (in_array('youtube_video_url', $explodePaaFlds)) {
            $this->validateYoutubeField($form);
        }
        if (in_array('photo_error', $explodePaaFlds)) {
            $this->validateAdImageLimit($form);
        }

        if (in_array('travel_arrangements_id', $explodePaaFlds)) {
            $this->validateAdultRates($form);
        }
        if (in_array('business_phone', $explodePaaFlds)) {
            $this->validateBusinessAdField($form);
        }

        if ($form->has('payment_method_id')) {
            $this->validatePaypalEmail($form, $ad);
        }

        if ($form->has('delivery_method_option_id')) {
            $this->validatePostagePrice($form);
        }
        if ($form->has('event_start')) {
            $this->validateEventDate($form);
        }
        if ($form->has('date_available')) {
            $this->validateDateAvailable($form);
        }
        if ($form->has('deposit')) {
            $this->validateDeposit($form);
        }
    }

    /**
     * Callbak method for POST_SUBMIT form event
     *
     * @param object $event Event instance
     */
    public function postSubmit(FormEvent $event)
    {
        $ad   = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            $this->saveAdOrSendForModeration($ad);
            $this->resetCategoryCookie();
            if ($this->container->get('session')->has('paa_image_id')) {
                $this->container->get('session')->remove('paa_image_id');
            }
        } else {
            //echo 'form is not validated properly';
        }
    }

    public function resetCategoryCookie()
    {
        if (isset($_COOKIE['fa_paa_lite_common_category_1'])) {
            unset($_COOKIE['fa_paa_lite_common_category_1']);
            setcookie('fa_paa_lite_common_category_1', "", time() - 3600);
        }
        if (isset($_COOKIE['fa_paa_lite_common_category_2'])) {
            unset($_COOKIE['fa_paa_lite_common_category_2']);
            setcookie('fa_paa_lite_common_category_2', "", time() - 3600);
        }
        if (isset($_COOKIE['fa_paa_lite_common_category_3'])) {
            unset($_COOKIE['fa_paa_lite_common_category_3']);
            setcookie('fa_paa_lite_common_category_3', "", time() - 3600);
        }
        if (isset($_COOKIE['fa_paa_lite_common_category_4'])) {
            unset($_COOKIE['fa_paa_lite_common_category_4']);
            setcookie('fa_paa_lite_common_category_4', "", time() - 3600);
        }
        if (isset($_COOKIE['fa_paa_lite_common_category_5'])) {
            unset($_COOKIE['fa_paa_lite_common_category_5']);
            setcookie('fa_paa_lite_common_category_5', "", time() - 3600);
        }
        if (isset($_COOKIE['fa_paa_lite_common_category_6'])) {
            unset($_COOKIE['fa_paa_lite_common_category_6']);
            setcookie('fa_paa_lite_common_category_6', "", time() - 3600);
        }
        return true;
    }

    /**
     * Save ad or send for modertaion.
     *
     * @param object $ad Ad object.
     *
     * @return object
     */
    
    protected function saveAdOrSendForModeration($formdata)
    {
        $data                 = $this->request->get($this->getName());
        $data['user_id']      = CommonManager::getLoggedInUser($this->container)->getId();
        $data['ad_status_id'] = EntityRepository::AD_STATUS_IN_MODERATION_ID;
        $user = CommonManager::getLoggedInUser($this->container);
        $adPostManager = $this->container->get('fa_ad.manager.ad_post');

        $getCampaign = $this->em->getRepository('FaAdBundle:Campaigns')->getCampaignBySlug($this->campaign_name);
        $data['campaign_id'] = ($getCampaign)?$getCampaign[0]->getId():null;
        $campaign = ($getCampaign)?$getCampaign[0]:null;

        $this->container->get('session')->set('redirect_to_cart', 0);

        //Save Ad
        $ad = $adPostManager->savePaaLiteAd($data, $campaign);
        if ($this->container->get('session')->get('redirect_to_cart')==0) {
            $adPostManager->sendAdForModerationPaaLite($ad, $data);
            $this->container->get('session')->set('show_ad_live_popup', 1);
        }
    }

    /**
     * Add category choice field.
     *
     * @param object $builder
     * @param string $campaign_name
     */
    private function addCategoryChoiceFields($builder, $campaign_name)
    {
        $getCampaign = $this->em->getRepository('FaAdBundle:Campaigns')->getCampaignBySlug($campaign_name);
        
        if (!empty($getCampaign)) {
            $getCategoryId = $getCampaign[0]->getCategory()->getId();
            $getCategoryLevel = $getCampaign[0]->getCategory()->getLvl();
            $CategoryNxtLevel = $getCategoryLevel+1;
        }

        $totalLevel = $this->em->getRepository('FaEntityBundle:Category')->getMaxLevel($this->container);

        if ($totalLevel) {
            for ($i = 1; $i <= $totalLevel; $i++) {
                if ($i == 1) {
                    $optionArray = array(
                        'placeholder' =>  'Please select category',
                        'attr'        => array('class' => 'fa-select category category_'.$i),
                    );
                } else {
                    $optionArray = array(
                        'placeholder' => 'Please select subcategory',
                        'attr'        => array('class' => 'fa-select category category_'.$i),
                    );
                }
                $builder->addEventSubscriber(
                    new AddCategoryChoiceFieldSubscriber(
                        $this->container,
                        $i,
                        'category',
                        $optionArray,
                        null,
                        $totalLevel,
                        true
                    )
                );
            }
        }
    }

    /**
     * Get PaaLiteOrderedFields
     *
     * @return array
     */
    public function getPaaLiteOrderedFields()
    {
        return array();
    }
}
