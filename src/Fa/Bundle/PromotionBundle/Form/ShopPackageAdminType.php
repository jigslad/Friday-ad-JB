<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\PromotionBundle\Entity\PackageRule;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Fa\Bundle\PromotionBundle\Entity\ShopPackageCredit;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * Shop package admin type form.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ShopPackageAdminType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager object.
     *
     * @var object
     */
    private $em;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Credits block.
     *
     * @var object
     */
    private $noOfCreditblocks = 3;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currency = CommonManager::getCurrencyCode($this->container);

        $builder
            ->add(
                'role',
                EntityType::class,
                array(
                    'required' => false,
                    'class' => 'FaUserBundle:Role',
                    'choice_label' => 'name',
                    'placeholder' => 'Select user type',
                    'label' => 'User type',
                    'query_builder' => function (RoleRepository $er) {
                        return $er->createQueryBuilder(RoleRepository::ALIAS)
                        ->where(RoleRepository::ALIAS.'.name IN (:name)')
                        ->setParameter('name', array('ROLE_BUSINESS_SELLER','ROLE_NETSUITE_SUBSCRIPTION'));
                    }
                )
            )
            ->add('title')
            ->add('sub_title')
            ->add('new_ad_cta', TextType::class, array('label' => 'New package CTA', 'required' => false))
            ->add('renewal_ad_cta', TextType::class, array('label' => 'Upgrade package CTA', 'required' => false))
            ->add('package_for', HiddenType::class, array('required' => true, 'data' => 'shop', 'constraints' => new NotBlank(array('message' => 'Please enter parameter value.'))))
            ->add('description', TextareaType::class, array('attr'=> array('rows' => 10)))
            ->add('price', MoneyType::class, array('currency' => $currency, 'required' => false))
            ->add(
                'duration',
                TextType::class,
                array(
                    'label'    => 'Duration (eg. Days = 2d, Week = 3w, Month = 1m)',
                    'required' => false
                )
            )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices'  => array_flip(EntityRepository::getStatusArray($this->container)),
                )
            )
            ->add(
                'trail',
                ChoiceType::class,
                array(
                    'choices'  => array_flip(EntityRepository::getYesNoArray($this->container, false)),
                    'label' => 'Free trial',
                )
            )
            ->add(
                'category',
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->getCategories()),
                    'mapped'   => false,
                    'attr'     => array('data-size'=>'auto'),
                    'placeholder' => false,
                )
            )
            ->add(
                'upsells',
                EntityType::class,
                array(
                    'multiple' => true,
                    'class' => 'FaPromotionBundle:Upsell',
                    'choice_label' => 'title',
                    'query_builder' => function (UpsellRepository $er) {
                        return $er->createQueryBuilder(UpsellRepository::ALIAS)
                        ->where(UpsellRepository::ALIAS.'.status = 1')
                        ->where(UpsellRepository::ALIAS.'.upsell_for = :upsell_for')
                        ->setParameter('upsell_for', 'shop')
                        ->orderBy(UpsellRepository::ALIAS.'.title', 'ASC');
                    }
                )
            )
            ->add(
                'value',
                ChoiceType::class,
                array(
                    'choices' => array_flip($this->getPackageColor()),
                    'label' => 'Package color',
                    'required' => false,
                    'placeholder' => false,
                )
            )
            ->add(
                'is_admin_package',
                CheckboxType::class,
                array(
                    'required' => false,
                    'label'    => 'Admin only package',
                )
            )
            ->add(
                'monthly_boost_count',
                TextType::class,
                array(
                    'required' => false,
                    'label'=>'Max no. of boost per month',
                )
            )
            ->add(
                'boost_ad_enabled',
                CheckboxType::class,
                array(
                    'required' => false,
                    'label'    => 'Boost button enabled',
                )
            )
            ->add(
                'ad_limit',
                NumberType::class,
                array(
                    'label'    => 'Ad Limit',
                    'required' => false
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function ($event) {
                $package = $event->getData();
                $form    = $event->getForm();
                if ($package->getId()) {
                    $this->addCategoryFields($form, $package);
                    $this->addCreditFields($form, $package);
                }
            }
        );

        $this->addCreditFields($builder, $builder->getForm()->getData());
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * Add credit fields
     *
     * @param object $form    Form object.
     * @param object $package Package object.
     */
    private function addCreditFields($form, $package)
    {
        $shopPackageCredits = null;

        if ($package instanceof Package) {
            if ($package->getId() && $this->container->get('request_stack')->getCurrentRequest()->getMethod() != 'PUT') {
                $shopPackageCredits = $this->em->getRepository('FaPromotionBundle:ShopPackageCredit')->getPackageCreditsByPackageId($package->getId());
            }
        }

        $packageSrNoOptions = $this->em->getRepository('FaPromotionBundle:Package')->getPackageTypeArray();
        // for print durations
        for ($i = 1; $i <= $this->noOfCreditblocks; $i++) {
            $userCreditPackageSrNos = array();
            $userCreditDurationType = null;
            $userCreditDurationValue = null;
            if ($package instanceof Package) {
                if (isset($shopPackageCredits[$i-1]) && $shopPackageCredits[$i-1]->getPackageSrNo()) {
                    $userCreditPackageSrNos = explode(',', $shopPackageCredits[$i-1]->getPackageSrNo());
                    if (count($packageSrNoOptions) == count($userCreditPackageSrNos)) {
                        $userCreditPackageSrNos[] = '-1';
                    }
                }
                if (isset($shopPackageCredits[$i-1]) && $shopPackageCredits[$i-1]->getDuration()) {
                    $userCreditDurationType = substr($shopPackageCredits[$i-1]->getDuration(), -1);
                    $userCreditDurationValue = str_replace($userCreditDurationType, '', $shopPackageCredits[$i-1]->getDuration());
                }
            }
            $form
            ->add('shop_package_credit_id_'.$i, HiddenType::class, array('mapped' => false, 'data' => (isset($shopPackageCredits[$i-1]) ? $shopPackageCredits[$i-1]->getId() : null)))
            ->add(
                'credit_'.$i,
                TextType::class,
                array(
                    'label' => 'Number of credits',
                    'required' => false,
                    'mapped'   => false,
                    'constraints' => array(
                        new Regex(array('pattern' => "/^[0-9]+$/i", 'message' => "The value {{ value }} is not a valid integer value.")),
                    ),
                    'data'     => (isset($shopPackageCredits[$i-1]) ? $shopPackageCredits[$i-1]->getCredit() : null),
                )
            )
            ->add(
                'category_id_'.$i,
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container)),
                    'mapped'   => false,
                    'placeholder' => $this->translator->trans('Please select category.', array(), 'validators'),
                    'data'     => (isset($shopPackageCredits[$i-1]) ? ($shopPackageCredits[$i-1]->getCategory() ? $shopPackageCredits[$i-1]->getCategory()->getId() : null) : null),
                    'required' => false,
                    'label' => 'Credit category',
                )
            )
            ->add(
                'package_sr_no_'.$i,
                ChoiceType::class,
                array(
                    'label' => 'Package type',
                    'placeholder' => 'Any',
                    'choices' => array('All' => '-1') + array_flip($packageSrNoOptions),
                    'multiple'  => true,
                    'expanded'  => true,
                    'mapped'    => false,
                    'data' => $userCreditPackageSrNos,
                    'required' => false,
                )
            )
            ->add('paid_user_only_'.$i, CheckboxType::class, array('mapped' => false, 'label' => 'Only usable with paid business profile', 'required' => false, 'value' => true, 'data' => (isset($shopPackageCredits[$i-1]) ? $shopPackageCredits[$i-1]->getPaidUserOnly() : null)))
            ->add(
                'duration_value_'.$i,
                TextType::class,
                array(
                    'label' => 'Duration',
                    'required' => false,
                    'mapped' => false,
                    'constraints' => array(
                        new Regex(array('pattern' => "/^[0-9]+$/i", 'message' => "The value {{ value }} is not a valid integer value.")),
                    ),
                    'data' => $userCreditDurationValue,
                )
            )
            ->add(
                'duration_type_'.$i,
                ChoiceType::class,
                array(
                    'choices'  => array_flip(array('d' => 'Days', 'w' => 'Weeks', 'm' => 'Months')),
                    'mapped'   => false,
                    'placeholder' => $this->translator->trans('Please select duration.', array(), 'validators'),
                    'data' => $userCreditDurationType,
                    'required' => false,
                    'label' => 'Duration type',
                )
            );
        }
    }

    /**
     * Add category fields.
     *
     * @param object $form    Form object.
     * @param object $package Package object.
     */
    private function addCategoryFields($form, $package)
    {
        $categoryId  = null;

        $locationGroupIds = array();
        if ($package instanceof Package) {
            $categoryLocationArray = $this->em->getRepository('FaPromotionBundle:PackageRule')->getCategoryLocationGroupByPackageIds(array($package->getId()));

            if (count($categoryLocationArray) && isset($categoryLocationArray[$package->getId()]) && isset($categoryLocationArray[$package->getId()]['category_id'])) {
                $categoryId = $categoryLocationArray[$package->getId()]['category_id'];
            }
        } else {
            $categoryId  = $this->getCategoryId($package, true);
        }

        if ($categoryId) {
            $form->add(
                'category',
                ChoiceType::class,
                array(
                        'required' => false,
                        'mapped'   => false,
                        'choices'  => array_flip($this->getCategories()),
                        'data'     => $categoryId,
                        'attr'     => array('data-size'=>'auto'),
                        'placeholder' => false,
                    )
            );
        }
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        for ($i = 1; $i <= $this->noOfCreditblocks; $i++) {
            if ($form->get('credit_'.$i)->getData() || $form->get('category_id_'.$i)->getData() || $form->get('package_sr_no_'.$i)->getData() || $form->get('paid_user_only_'.$i)->getData() || $form->get('duration_value_'.$i)->getData() || $form->get('duration_type_'.$i)->getData() || $form->get('paid_user_only_'.$i)->getData()) {
                if (!$form->get('credit_'.$i)->getData()) {
                    $event->getForm()->get('credit_'.$i)->addError(new \Symfony\Component\Form\FormError('Please enter the number of credits to allocate.'));
                }
                if (!$form->get('category_id_'.$i)->getData()) {
                    $event->getForm()->get('category_id_'.$i)->addError(new \Symfony\Component\Form\FormError('Please select a category.'));
                }
                if (!count($form->get('package_sr_no_'.$i)->getData())) {
                    $event->getForm()->get('package_sr_no_'.$i)->addError(new \Symfony\Component\Form\FormError('Please select a package type or types.'));
                }
                if (!$form->get('duration_value_'.$i)->getData()) {
                    $event->getForm()->get('duration_value_'.$i)->addError(new \Symfony\Component\Form\FormError('Please enter duration.'));
                }
                if (!$form->get('duration_type_'.$i)->getData()) {
                    $event->getForm()->get('duration_type_'.$i)->addError(new \Symfony\Component\Form\FormError('Please select a duration type.'));
                }
            }
        }
        
        if ($form->get('role')->getData()->getid()==9 && !$form->get('ad_limit')->getData()) {
            $event->getForm()->get('ad_limit')->addError(new \Symfony\Component\Form\FormError('Please enter the ad limit.'));
        }
        
        if ($form->get('role')->getData()->getid()==9 && ($form->get('ad_limit')->getData() < ($form->get('credit_1')->getData() + $form->get('credit_2')->getData() + $form->get('credit_3')->getData()))) {
            $event->getForm()->get('credit_1')->addError(new \Symfony\Component\Form\FormError('Credit limit is greater than total Ad limit.'));
        }
        
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $package = $event->getData();
        $form    = $event->getForm();

        if ($form->isValid()) {
            //remove packge rules
            if ($package->getId()) {
                $this->em->getRepository('FaPromotionBundle:PackageRule')->removeRecordsByPackageId($package->getId());
            }

            $categoryId = $form->get('category')->getData();

            $packageRule = new PackageRule();
            $packageRule->setPackage($package);
            if ($categoryId) {
                $package->setShopCategory($this->em->getRepository('FaEntityBundle:Category')->find($categoryId));
                $packageRule->setCategory($this->em->getRepository('FaEntityBundle:Category')->find($categoryId));
            }
            $this->em->persist($packageRule);

            //set category
            $totalCredit = 0;
            
            
            if($form->get('ad_limit')->getData()) {
                if ($form->get('shop_package_credit_id_1')->getData()) {
                    $shopPackageCredit = $this->em->getRepository('FaPromotionBundle:ShopPackageCredit')->find($form->get('shop_package_credit_id_1')->getData());
                } elseif ($form->get('credit_1')->getData() && $form->get('category_id_1')->getData() && $form->get('package_sr_no_1')->getData() && $form->get('duration_value_1')->getData() && $form->get('duration_type_1')->getData()) {
                    $shopPackageCredit = new ShopPackageCredit();
                    $shopPackageCredit->setPackage($package);
                }
                if ($shopPackageCredit) {
                    if ($form->get('credit_1')->getData()) {
                        $shopPackageCredit->setCredit($form->get('credit_1')->getData());
                        $totalCredit = $totalCredit + $form->get('credit_1')->getData();
                    } else {
                        $shopPackageCredit->setCredit($form->get('ad_limit')->getData());
                        $totalCredit = $totalCredit + $form->get('ad_limit')->getData();
                    }
                    
                    if ($form->get('category_id_1')->getData()) {
                        $shopPackageCredit->setCategory($this->em->getReference('FaEntityBundle:Category', $form->get('category_id_1')->getData()));
                    } else {
                        $shopPackageCredit->setCategory($this->em->getReference('FaEntityBundle:Category', CategoryRepository::ADULT_ID));
                    }
                    
                    if ($form->get('package_sr_no_1')->getData()) {
                        $packageSrNoArray = $form->get('package_sr_no_1')->getData();
                        if (isset($packageSrNoArray[0]) && $packageSrNoArray[0] == '-1') {
                            unset($packageSrNoArray[0]);
                        }
                        
                        asort($packageSrNoArray);
                        $shopPackageCredit->setPackageSrNo(implode(',', $packageSrNoArray));
                    } else {
                        $shopPackageCredit->setPackageSrNo(1);
                    }
                    
                    if ($form->get('duration_value_1')->getData() && $form->get('duration_type_1')->getData()) {
                        $shopPackageCredit->setDuration($form->get('duration_value_1')->getData().$form->get('duration_type_1')->getData());
                    } else {
                        $shopPackageCredit->setDuration(null);
                    }
                    
                    if ($form->get('paid_user_only_1')->getData()) {
                        $shopPackageCredit->setPaidUserOnly($form->get('paid_user_only_1')->getData());
                    } else {
                        $shopPackageCredit->setPaidUserOnly(0);
                    }
                    
                    $this->em->persist($shopPackageCredit);
                }
                $remainingCredit = $form->get('ad_limit')->getData() - $totalCredit;
                
                if($remainingCredit > 0) {
                    if ($form->get('shop_package_credit_id_2')->getData()) {
                        $shopPackageCredit = $this->em->getRepository('FaPromotionBundle:ShopPackageCredit')->find($form->get('shop_package_credit_id_2')->getData());
                    } else {
                        $shopPackageCredit = new ShopPackageCredit();
                        $shopPackageCredit->setPackage($package);
                    }
                    if ($shopPackageCredit) {
                        $shopPackageCredit->setCredit($remainingCredit);
                        $shopPackageCredit->setCategory($this->em->getReference('FaEntityBundle:Category', CategoryRepository::ADULT_ID));
                        $shopPackageCredit->setPackageSrNo(1);
                        $shopPackageCredit->setDuration(null);
                        $shopPackageCredit->setPaidUserOnly(0);
                        $this->em->persist($shopPackageCredit);                           
                    }
                }
                $this->em->flush();               
            }  else {
                for ($i = 1; $i <= $this->noOfCreditblocks; $i++) {
                    $shopPackageCredit = null;
                    if ($form->get('shop_package_credit_id_'.$i)->getData()) {
                        $shopPackageCredit = $this->em->getRepository('FaPromotionBundle:ShopPackageCredit')->find($form->get('shop_package_credit_id_'.$i)->getData());
                    } elseif ($form->get('credit_'.$i)->getData() && $form->get('category_id_'.$i)->getData() && $form->get('package_sr_no_'.$i)->getData() && $form->get('duration_value_'.$i)->getData() && $form->get('duration_type_'.$i)->getData()) {
                        $shopPackageCredit = new ShopPackageCredit();
                        $shopPackageCredit->setPackage($package);
                    }
                    if ($shopPackageCredit) {
                        if ($form->get('credit_'.$i)->getData()) {
                            $shopPackageCredit->setCredit($form->get('credit_'.$i)->getData());
                            $totalCredit = $totalCredit + $form->get('credit_'.$i)->getData();
                        } else {
                            $shopPackageCredit->setCredit(null);
                        }
                        
                        if ($form->get('category_id_'.$i)->getData()) {
                            $shopPackageCredit->setCategory($this->em->getReference('FaEntityBundle:Category', $form->get('category_id_'.$i)->getData()));
                        } else {
                            $shopPackageCredit->setCategory(null);
                        }
                        
                        if ($form->get('package_sr_no_'.$i)->getData()) {
                            $packageSrNoArray = $form->get('package_sr_no_'.$i)->getData();
                            if (isset($packageSrNoArray[0]) && $packageSrNoArray[0] == '-1') {
                                unset($packageSrNoArray[0]);
                            }
                            
                            asort($packageSrNoArray);
                            $shopPackageCredit->setPackageSrNo(implode(',', $packageSrNoArray));
                        } else {
                            $shopPackageCredit->setPackageSrNo(null);
                        }
                        
                        if ($form->get('duration_value_'.$i)->getData() && $form->get('duration_type_'.$i)->getData()) {
                            $shopPackageCredit->setDuration($form->get('duration_value_'.$i)->getData().$form->get('duration_type_'.$i)->getData());
                        } else {
                            $shopPackageCredit->setDuration(null);
                        }
                        
                        if ($form->get('paid_user_only_'.$i)->getData()) {
                            $shopPackageCredit->setPaidUserOnly($form->get('paid_user_only_'.$i)->getData());
                        } else {
                            $shopPackageCredit->setPaidUserOnly(0);
                        }
                        
                        $this->em->persist($shopPackageCredit);
                    }
                }
    
                $this->em->flush();
            }
            
            
                      
        }
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
                'data_class' => 'Fa\Bundle\PromotionBundle\Entity\Package'
            )
        );
    }

    /**
     * Get categories.
     */
    public function getCategories()
    {
        return $this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container);
    }

    /**
     * Get package colors.
     */
    public function getPackageColor()
    {
        return array(
            'a:2:{s:11:"title_class";s:13:"pkg-gray-head";s:9:"btn_class";s:13:"primary-btn-3";}' => 'Grey',
            'a:2:{s:11:"title_class";s:13:"pkg-blue-head";s:9:"btn_class";s:13:"primary-btn-1";}' => 'Blue',
            'a:2:{s:11:"title_class";s:14:"pkg-green-head";s:9:"btn_class";s:13:"primary-btn-2";}' => 'Green');
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_promotion_shop_package_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_shop_package_admin';
    }
}
