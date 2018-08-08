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
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\PromotionBundle\Entity\PackageRule;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\LocationGroupRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\EmailBundle\Entity\EmailTemplate;
use Fa\Bundle\EmailBundle\Repository\EmailTemplateRepository;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Fa\Bundle\PromotionBundle\Entity\PackagePrint;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

/**
 * PackageAdminType form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PackageAdminType extends AbstractType
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
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
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
                        ->where(RoleRepository::ALIAS.'.type = :roleType')
                        ->setParameter('roleType', 'C');
                    }
                )
            )
            ->add(
                'email_template',
                EntityType::class,
                array(
                    'required' => false,
                    'class' => 'FaEmailBundle:EmailTemplate',
                    'choice_label' => 'name',
                    'placeholder' => 'Select Email Template',
                    'label' => 'Email Template',
                    'query_builder' => function (EmailTemplateRepository $er) {
                        return $er->createQueryBuilder(EmailTemplateRepository::ALIAS)
                        ->where(EmailTemplateRepository::ALIAS.'.type = '.EmailTemplateRepository::PACKAGE_TYPE_ID);
                    }
                )
            )
            ->add(
                'label',
                TextType::class,
                array(
                    'label'    => 'Label',
                )
            )
            ->add(
                'title',
                TextType::class,
                array(
                    'label'    => 'Name',
                )
            )
            ->add('sub_title')
            ->add('new_ad_cta')
            ->add('renewal_ad_cta')
            ->add('description')
            //->add('upgrade_description')
            ->add('package_for', HiddenType::class, array('required' => true, 'data' => 'ad'))
            ->add('price', MoneyType::class, array('currency' => $currency, 'required' => false))
            ->add('admin_price', MoneyType::class, array('currency' => $currency, 'required' => false))
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
                'upsells',
                EntityType::class,
                array(
                    'required' => false,
                    'multiple' => true,
                    'class' => 'FaPromotionBundle:Upsell',
                    'choice_label' => 'title',
                    'query_builder' => function (UpsellRepository $er) {
                        return $er->createQueryBuilder(UpsellRepository::ALIAS)
                        ->where(UpsellRepository::ALIAS.'.status = 1')
                        ->where(UpsellRepository::ALIAS.'.upsell_for = :upsell_for')
                        ->setParameter('upsell_for', 'ad')
                        ->orderBy(UpsellRepository::ALIAS.'.title', 'ASC');
                    }
                )
            )
            ->add(
                'location_group',
                ChoiceType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'label' => 'Location Group',
                    'multiple' => true,
                    'choices' => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getLocationGroupsKeyValueArray()),
                )
            )
            ->add(
                'package_sr_no',
                ChoiceType::class,
                array(
                    'label' => 'Package Sr No.',
                    'placeholder' => 'Select Sr No.',
                    'choices' => array_flip($this->em->getRepository('FaPromotionBundle:Package')->getPackageTypeArray()),
                    'required' => false,
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
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        //category fields
        $builder->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                1,
                'category'
            )
        )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                2,
                'category'
            )
        )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                3,
                'category'
            )
        )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                4,
                'category'
            )
        );

        $this->addPrintDurationFields($builder, $builder->getForm()->getData());

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) {
                $package   = $event->getData();
                $form      = $event->getForm();
                $upsells   = $form->get('upsells')->getData();
                $upsellIds = array();

                if (count($upsells)) {
                    foreach ($upsells as $upsell) {
                        $upsellIds[$upsell->getType()] = $upsell->getId();
                    }

                    // for unique upsells per package.
                    /*
                    $samePackageUpsells = $this->container->get('doctrine')->getManager()->getRepository('FaPromotionBundle:Package')->checkPackageByUpsellLocationGroupCategory($upsellIds, $package->getId(), $form->get('location_group')->getData(), $this->getCategoryId($form), $form->get('role')->getData());

                    if ($samePackageUpsells) {
                        $event->getForm()->get('upsells')->addError(new \Symfony\Component\Form\FormError('Same package with combination of user type, category, location group and upsell already exists.'));
                    }*/
                }

                $locationGroupIds = $form->get('location_group')->getData();
                //check for print upsell and print location group
                $printUpsellIds = $this->em->getRepository('FaPromotionBundle:Upsell')->getPrintUpsellIdsArray();
                if (count(array_intersect($printUpsellIds, $upsellIds)) && count(array_intersect(array(LocationGroupRepository::NON_PRINT_LOCATION_GROUP_ID), $locationGroupIds))) {
                    $event->getForm()->get('location_group')->addError(new \Symfony\Component\Form\FormError('Please select only print location group.'));
                }

                //check for print duration.
                $printPublicationUpsellIds = $this->em->getRepository('FaPromotionBundle:Upsell')->getPrintPublicationUpsellIdsArray();
                if (count(array_intersect($printPublicationUpsellIds, $upsellIds)) && $form->get('printduration_count')->getData() == 0) {
                    $event->getForm()->get('printduration_error')->addError(new \Symfony\Component\Form\FormError('Please select at least one print duration.'));
                }

                // admin package is selected then admin price is mandatory.(0 or more)
                // if NOT admin package then regular price is mandatory either 0 or more
                if ($form->has('is_admin_package') && $form->get('is_admin_package')->getData() == 1) {
                    $printdurationCount = 0;
                    if ($form->has('printduration_count') && $form->get('printduration_count')->getData()) {
                        $printdurationCount = $form->get('printduration_count')->getData();
                    }

                    if ($printdurationCount > 0) {
                        for ($i = 1; $i <= $printdurationCount; $i++) {
                            $field = 'print_admin_price_'.$i;
                            if ($form->has($field) && $form->get($field)->getData() === null) {
                                $event->getForm()->get($field)->addError(new \Symfony\Component\Form\FormError('Please enter admin price. (Enter 0 for free pkg)'));
                            }
                        }
                    } else {
                        if ($form->has('admin_price') && $form->get('admin_price')->getData() === null) {
                            $event->getForm()->get('admin_price')->addError(new \Symfony\Component\Form\FormError('Please enter admin price. (Enter 0 for free pkg)'));
                        }
                    }
                } else {
                    $printdurationCount = 0;
                    if ($form->has('printduration_count') && $form->get('printduration_count')->getData()) {
                        $printdurationCount = $form->get('printduration_count')->getData();
                    }

                    if ($printdurationCount > 0) {
                        for ($i = 1; $i <= $printdurationCount; $i++) {
                            $field = 'print_price_'.$i;
                            if ($form->has($field) && $form->get($field)->getData() === null) {
                                $event->getForm()->get($field)->addError(new \Symfony\Component\Form\FormError('Please enter price. (Enter 0 for free pkg)'));
                            }
                        }
                    } else {
                        if ($form->has('price') && $form->get('price')->getData() === null) {
                            $event->getForm()->get('price')->addError(new \Symfony\Component\Form\FormError('Please enter price. (Enter 0 for free pkg)'));
                        }
                    }
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function ($event) {
                $package = $event->getData();
                $form    = $event->getForm();

                $isAdminPackage = null;
                if ($package->getId()) {
                    $this->addCategoryLocationGroupFields($form, $package);
                    $isAdminPackage = $package->getIsAdminPackage();
                }

                $form->add(
                    'is_admin_package',
                    CheckboxType::class,
                    array(
                        'required' => false,
                        'label'    => 'Admin only package',
                        'data'     => ($isAdminPackage ? $isAdminPackage : null)
                    )
                );
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function ($event) {
                $package = $event->getData();
                $form    = $event->getForm();
                $this->addCategoryLocationGroupFields($form, $package);
                $this->addPrintDurationFields($form, $package);
            }
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Add category location group fields.
     *
     * @param object $form    Form object.
     * @param object $package Package object.
     */
    private function addCategoryLocationGroupFields($form, $package)
    {
        $categoryId         = null;
        $locationGroupIds   = array();
        if ($package instanceof Package) {
            $categoryLocationArray = $this->em->getRepository('FaPromotionBundle:PackageRule')->getCategoryLocationGroupByPackageIds(array($package->getId()));
            if (count($categoryLocationArray) && isset($categoryLocationArray[$package->getId()]) && isset($categoryLocationArray[$package->getId()]['category_id'])) {
                $categoryId = $categoryLocationArray[$package->getId()]['category_id'];
            }
            if (count($categoryLocationArray) && isset($categoryLocationArray[$package->getId()]) && isset($categoryLocationArray[$package->getId()]['location'])) {
                $locationGroupIds = array_keys($categoryLocationArray[$package->getId()]['location']);
            }
        } else {
            $categoryId         = $this->getCategoryId($package, true);
            $locationGroupIds   = isset($package['location_group']) ? $package['location_group'] : array();
        }
        //for category
        if ($categoryId) {
            $categoryPath     = array_keys($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, true, $this->container));
            $categoryPathTemp = $categoryPath;

            if (count($categoryPath) > 5) {
                end($categoryPath);
                $categoryPath[4] = $categoryPath[key($categoryPath)];
            } elseif (count($categoryPath) < 5) {
                $categoryPath[count($categoryPath)+1] = $categoryPath[count($categoryPath)-1];
            }
            $categoryPath = array_slice($categoryPath, 0, 5);

            for ($i=1; $i < count($categoryPath); $i++) {
                $choices = array('' => 'Select Category '.$i) + ($i == 4 ? $this->em->getRepository('FaEntityBundle:Category')->getNestedChildrenKeyValueArrayByParentId($categoryPathTemp[$i-1]) :$this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($categoryPathTemp[$i-1]));
                $choices = $this->em->getRepository('FaEntityBundle:Category')->showDuplicateCategoriesForSubscriber($choices);
                $form->add(
                    'category_'.$i,
                    ChoiceType::class,
                    array(
                        'required' => false,
                        'mapped'   => false,
                        'choices'  => array_flip($choices),
                        'data'     => isset($categoryPath[$i]) ? $categoryPath[$i] : null,
                    )
                );
            }
        }

        //for location groups
        if (count($locationGroupIds)) {
            $form->add(
                'location_group',
                ChoiceType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                    'choices' => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getLocationGroupsKeyValueArray()),
                    'data'     => $locationGroupIds,
                )
            );
        }
    }

    /**
     * Add print duration fields.
     *
     * @param object $form    Form object.
     * @param object $package Package object.
     */
    private function addPrintDurationFields($form, $package)
    {
        $currencySymbol        = CommonManager::getCurrencySymbol(null, $this->container);
        $printdurationCount    = 0;
        $packagePrintDurations = null;

        if ($package instanceof Package) {
            if ($package->getId() && $this->container->get('request_stack')->getCurrentRequest()->getMethod() != 'PUT') {
                $packagePrintDurations = $this->em->getRepository('FaPromotionBundle:PackagePrint')->getPackagePrintByPackageId($package->getId());
                $printdurationCount = count($packagePrintDurations);
            }
        } else {
            $printdurationCount = isset($package['printduration_count']) ? $package['printduration_count'] : $printdurationCount;
        }

        $form->add(
            'printduration_error',
            TextType::class,
            array(
                'required' => false,
                'mapped' => false,
                'data'     => $printdurationCount,
            )
        );

        $form->add(
            'printduration_count',
            HiddenType::class,
            array(
                'required' => false,
                'mapped' => false,
                'data'     => $printdurationCount,
            )
        );

        // for print durations
        for ($i = 1; $i <= $printdurationCount; $i++) {
            $form->add(
                'print_duration_'.$i,
                TextType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'data'     => (isset($packagePrintDurations[$i-1]) ? $packagePrintDurations[$i-1]->getDuration() : null),
                    /** @Ignore */
                    'label' => 'Duration '.$i.' (eg. Week = 3w)',
                    'constraints' => array(
                        new NotBlank(array('message' => "Please enter duration.")),
                        new Regex(array('pattern' => "/^[w0-9 ]+$/i", 'message' => "The value {{ value }} is not a valid duration.")),
                        new Length(array('max' => 12, 'maxMessage' => "Duration cannot be longer than {{ limit }} digits long.")),
                    )
                )
            );

            $form->add(
                'print_price_'.$i,
                TextType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'data'     => (isset($packagePrintDurations[$i-1]) ? $packagePrintDurations[$i-1]->getPrice() : null),
                    /** @Ignore */
                    'label' => 'Price '.$currencySymbol,
                    'constraints' => array(
                        new Regex(array('pattern' => "/^[0-9.]+$/i", 'message' => "The value {{ value }} is not a valid float value.")),
                        new Length(array('max' => 12, 'maxMessage' => "Price cannot be longer than {{ limit }} digits long.")),
                    )
                )
            );

            $form->add(
                'print_admin_price_'.$i,
                TextType::class,
                array(
                    'required' => false,
                    'mapped' => false,
                    'data'     => (isset($packagePrintDurations[$i-1]) ? $packagePrintDurations[$i-1]->getAdminPrice() : null),
                    /** @Ignore */
                    'label' => 'Admin Price '.$currencySymbol,
                    'constraints' => array(
                        new Regex(array('pattern' => "/^[0-9.]+$/i", 'message' => "The value {{ value }} is not a valid float value.")),
                        new Length(array('max' => 12, 'maxMessage' => "Admin price cannot be longer than {{ limit }} digits long.")),
                    )
                )
            );
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
            $insertPackageRuleFlag = true;
            $insertPackagePrintFlag = true;
            $locationGroupIds = $form->get('location_group')->getData();
            $categoryId       = $this->getCategoryId($form);

            //remove packge rules
            if ($package->getId()) {
                //for package rule entry.
                $packageRuleArray = $this->em->getRepository('FaPromotionBundle:PackageRule')->getPackageRuleArrayByPackageId($package->getId());
                $packageRuleFormArray = array();
                if (count($locationGroupIds)) {
                    foreach ($locationGroupIds as $locationGroupId) {
                        $packageRuleFormArray[] = array(
                            'location_group_id' => $locationGroupId,
                            'category_id' => $categoryId,
                        );
                    }
                } else {
                    $packageRuleFormArray[] = array(
                        'location_group_id' => null,
                        'category_id' => $categoryId,
                    );
                }

                if (md5(serialize($packageRuleArray)) == md5(serialize($packageRuleFormArray))) {
                    $insertPackageRuleFlag = false;
                }

                if ($insertPackageRuleFlag) {
                    $this->em->getRepository('FaPromotionBundle:PackageRule')->removeRecordsByPackageId($package->getId());
                }

                //for package print entry
                $packagePrintArray = $this->em->getRepository('FaPromotionBundle:PackagePrint')->getPackagePrintArrayByPackageId($package->getId());
                $packagePrintFormArray = array();
                if ($form->get('printduration_count')->getData() > 0) {
                    for ($i = 1; $i <= $form->get('printduration_count')->getData(); $i++) {
                        if ($form->get('print_duration_'.$i)->getData()) {
                            $packagePrintFormArray[] = array(
                                'duration' => $form->get('print_duration_'.$i)->getData(),
                                'price' => $form->get('print_price_'.$i)->getData(),
                                'admin_price' => $form->get('print_admin_price_'.$i)->getData(),
                            );
                        }
                    }
                }

                if (md5(serialize($packagePrintArray)) == md5(serialize($packagePrintFormArray))) {
                    $insertPackagePrintFlag = false;
                }

                if ($insertPackagePrintFlag) {
                    $this->em->getRepository('FaPromotionBundle:PackagePrint')->removeRecordsByPackageId($package->getId());
                }
            }

            if ($categoryId) {
                $categoryPathArray = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container);
                $categoryName = implode(' -> ', array_values($categoryPathArray));
                $package->setCategoryName($categoryName ? $categoryName : null);
            }

            //save location & category wise if its selcted else simple save
            if ($insertPackageRuleFlag) {
                if (count($locationGroupIds)) {
                    foreach ($locationGroupIds as $locationGroupId) {
                        $packageRule = new PackageRule();
                        $packageRule->setPackage($package);
                        $packageRule->setLocationGroup($this->em->getRepository('FaEntityBundle:LocationGroup')->find($locationGroupId));
                        if ($categoryId) {
                            $packageRule->setCategory($this->em->getRepository('FaEntityBundle:Category')->find($categoryId));
                        }
                        $this->em->persist($packageRule);
                        $this->em->flush();
                    }
                } else {
                    $packageRule = new PackageRule();
                    $packageRule->setPackage($package);
                    if ($categoryId) {
                        $packageRule->setCategory($this->em->getRepository('FaEntityBundle:Category')->find($categoryId));
                    }
                    $this->em->persist($packageRule);
                    $this->em->flush();
                }
            }

            //save packase serial number & text
            $packageSrNo = $form->get('package_sr_no')->getData();
            if ($packageSrNo) {
                $packageSrNoArray = $this->em->getRepository('FaPromotionBundle:Package')->getPackageTypeArray();
                if (isset($packageSrNoArray[$packageSrNo])) {
                    $package->setPackageText($packageSrNoArray[$packageSrNo]);
                }
            }
            // save print duration price.
            if ($insertPackagePrintFlag && $form->get('printduration_count')->getData() > 0) {
                for ($i = 1; $i <= $form->get('printduration_count')->getData(); $i++) {
                    if ($form->get('print_duration_'.$i)->getData()) {
                        //set first duration price as package price
                        if ($i == 1) {
                            $package->setPrice($form->get('print_price_'.$i)->getData());
                        }
                        $packagePrint = new PackagePrint();
                        $packagePrint->setPackage($package);
                        $packagePrint->setDuration($form->get('print_duration_'.$i)->getData());
                        $packagePrint->setPrice($form->get('print_price_'.$i)->getData());
                        $packagePrint->setAdminPrice($form->get('print_admin_price_'.$i)->getData());
                        $this->em->persist($packagePrint);
                        $this->em->flush();
                    }
                }
            }
        }
    }

    /**
     * Get category id based on posted form data.
     *
     * @param object  $form        Form instance.
     * @param boolean $isArrayFlag Array flag.
     *
     * @return integer
     */
    private function getCategoryId($form, $isArrayFlag = false)
    {
        $categoryId = null;
        if (!$isArrayFlag) {
            $category1  = $form->get('category_1')->getData();
            $category2  = $form->get('category_2')->getData();
            $category3  = $form->get('category_3')->getData();
            $category4  = $form->get('category_4')->getData();
        } else {
            $category1  = isset($form['category_1']) ? $form['category_1'] : null;
            $category2  = isset($form['category_2']) ? $form['category_2'] : null;
            $category3  = isset($form['category_3']) ? $form['category_3'] : null;
            $category4  = isset($form['category_4']) ? $form['category_4'] : null;
        }

        if ($category4) {
            $categoryId = $category4;
        } elseif ($category3) {
            $categoryId = $category3;
        } elseif ($category2) {
            $categoryId = $category2;
        } elseif ($category1) {
            $categoryId = $category1;
        }

        return $categoryId;
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
        return 'fa_promotion_package_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_package_admin';
    }
}
