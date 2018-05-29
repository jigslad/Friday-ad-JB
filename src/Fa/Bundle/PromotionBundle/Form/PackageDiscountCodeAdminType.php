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
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\EntityBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * PackageDiscountCode form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PackageDiscountCodeAdminType extends AbstractType
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
        $categoryId = null;
        $roleIds = array();
        $packageSrNos = array();
        $expiresAt = null;
        $packageSrNoOptions = $this->em->getRepository('FaPromotionBundle:Package')->getPackageTypeArray();
        if ($builder->getData()->getId()) {
            if ($builder->getData()->getExpiresAt()) {
                $expiresAt = date('d/m/Y', $builder->getData()->getExpiresAt());
            }
            if ($builder->getData()->getRoleIds()) {
                $roleIds = explode(',', $builder->getData()->getRoleIds());
            }
            if ($builder->getData()->getPackageSrNo()) {
                $packageSrNos = explode(',', $builder->getData()->getPackageSrNo());
                if (count($packageSrNoOptions) == count($packageSrNos)) {
                    $packageSrNos[] = '-1';
                }
            }
            $categoryId = ($builder->getData()->getCategory() ? $builder->getData()->getCategory()->getId() : null);
        }

        $builder
            ->add(
                'code',
                TextType::class,
                array(
                    'label' => 'Code',
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please enter code.', array(), 'validators'))),
                        new Length(array('min' => 4, 'max' => 20)),
                    )
                )
            )
            ->add(
                'discount_type',
                ChoiceType::class,
                array(
                    'label' => 'Discount type',
                    'choices' => array_flip($this->em->getRepository('FaPromotionBundle:PackageDiscountCode')->getPackageDiscountTypeArray()),
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please select discount type.', array(), 'validators'))),
                    )
                )
            )
            ->add(
                'discount_value',
                TextType::class,
                array(
                    'label' => 'Discount value',
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please enter discount value.', array(), 'validators'))),
                        new Regex(array('pattern' => "/^[0-9.]+$/i", 'message' => 'Save discount value without £ or % sign.'))
                    )
                )
            )
            ->add(
                'package_sr_no',
                ChoiceType::class,
                array(
                    'label' => 'Package type',
                    'placeholder' => 'Any',
                    'choices' => array('All' => '-1') + array_flip($packageSrNoOptions),
                    'multiple'  => true,
                    'expanded'  => true,
                    'mapped'    => false,
                    'required' => false,
                    'data' => $packageSrNos,
                )
            )
            ->add('admin_only_package', CheckboxType::class, array('label' => 'Admin only packages', 'required' => false, 'value' => true))
            ->add('paid_user_only', CheckboxType::class, array('label' => 'Restrict to users with Paid Profile', 'required' => false, 'value' => true))
            ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'category_id', 'category_id_json', 'FaEntityBundle:Category', $categoryId, array('label' => 'Category')))
            ->add(
                'role_ids',
                ChoiceType::class,
                array(
                    'required'  => false,
                    'multiple'  => true,
                    'expanded'  => true,
                    'mapped'    => false,
                    'label'     => 'User role',
                    'choices'   => array_flip(RoleRepository::getUserTypes()),
                    'data' => $roleIds,
                )
            )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices'  => array_flip(EntityRepository::getStatusArray($this->container)),
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please select status.', array(), 'validators'))),
                    )
                )
            )
            ->add(
                'description',
                TextareaType::class,
                array(
                    'label' => 'Description',
                    'required' => false,
                    'attr' => array('rows' => '5'),
                    'constraints' => array(
                        new Length(array('max' => 1000, 'maxMessage' => $this->translator->trans('Description can not be longer than {{ limit }} characters.', array(), 'validators'))),
                    )
                )
            )
            ->add(
                'expires_at',
                TextType::class,
                array(
                    'label' => 'Expires at',
                    'required' => false,
                    'attr' => array('class' => 'fdatepicker'),
                    'data' => $expiresAt,
                )
            )
            ->add(
                'emails',
                TextareaType::class,
                array(
                    'label' => 'Specific User(s) (Add comma separated email addresses)',
                    'required' => false,
                    'attr' => array('rows' => '5'),
                )
            )
            ->add(
                'total_limit',
                TextType::class,
                array(
                    'label' => 'Total limit',
                    'required' => false,
                    'constraints' => array(
                        new Regex(array('pattern' => "/^[0-9]+$/i", 'message' => "The value {{ value }} is not a valid integer value.")),
                    ),
                )
            )
            ->add(
                'user_limit',
                TextType::class,
                array(
                    'label' => 'Limit per user',
                    'required' => false,
                    'constraints' => array(
                        new Regex(array('pattern' => "/^[0-9]+$/i", 'message' => "The value {{ value }} is not a valid integer value.")),
                    ),
                )
            )
            ->add(
                'monthly_user_limit',
                TextType::class,
                array(
                    'label' => 'Monthly limit per user',
                    'required' => false,
                    'constraints' => array(
                        new Regex(array('pattern' => "/^[0-9]+$/i", 'message' => "The value {{ value }} is not a valid integer value.")),
                    ),
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $packageDiscountCode = $event->getData();
        $form                = $event->getForm();

        if ($form->isValid()) {
            //set category
            if ($form->get('category_id')->getData()) {
                $packageDiscountCode->setCategory($this->em->getReference('FaEntityBundle:Category', $form->get('category_id')->getData()));
            } else {
                $packageDiscountCode->setCategory(null);
            }

            if ($form->get('expires_at')->getData()) {
                $packageDiscountCode->setExpiresAt(CommonManager::getTimeStampFromEndDate($form->get('expires_at')->getData()));
            }

            if ($form->get('role_ids')->getData()) {
                $roleIdsArray = $form->get('role_ids')->getData();
                asort($roleIdsArray);
                $packageDiscountCode->setRoleIds(implode(',', $roleIdsArray));
            } else {
                $packageDiscountCode->setRoleIds(null);
            }

            if ($form->get('package_sr_no')->getData()) {
                $packageSrNoArray = $form->get('package_sr_no')->getData();
                if (isset($packageSrNoArray[0]) && $packageSrNoArray[0] == '-1') {
                    unset($packageSrNoArray[0]);
                }

                asort($packageSrNoArray);
                $packageDiscountCode->setPackageSrNo(implode(',', $packageSrNoArray));
            } else {
                $packageDiscountCode->setPackageSrNo(null);
            }
        }
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $form       = $event->getForm();
        $totalLimit = trim($form->get('total_limit')->getData());
        $userLimit  = trim($form->get('user_limit')->getData());
        $monthlyUserLimit = trim($form->get('monthly_user_limit')->getData());

        if ($userLimit && $monthlyUserLimit) {
            $event->getForm()->get('user_limit')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('You are not able to add \'Limit per user\' AND \'Monthly limit per user\'. Please add one only.', array(), 'validators')));
        } else {
            if ($totalLimit) {
                if ($userLimit && $userLimit > $totalLimit) {
                    $event->getForm()->get('user_limit')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Limit per user may not exceed the total limit for this code', array(), 'validators')));
                }

                if ($monthlyUserLimit && $monthlyUserLimit > $totalLimit) {
                    $event->getForm()->get('monthly_user_limit')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Monthly limit per user may not exceed the total limit for this code', array(), 'validators')));
                }
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
                'data_class' => 'Fa\Bundle\PromotionBundle\Entity\PackageDiscountCode',
                new UniqueEntity(array('fields' => array('code'), 'errorPath' => 'code', 'message' => $this->translator->trans('Code already exist.', array(), 'validators')))
            )
        );
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_promotion_package_discount_code_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_package_discount_code_admin';
    }
}
