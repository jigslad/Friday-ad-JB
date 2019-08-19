<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\FormError;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * This is user admin form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserAdminType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Secutiry encoder.
     *
     * @var object
     */
    private $securityEncoder;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * string.
     *
     * @var string
     */
    private $oldEmail;

    /**
     * Constructor.
     *
     * @param Doctrine                $doctrine        Doctrine object.
     * @param EncoderFactoryInterface $securityEncoder Object.
     */
    public function __construct(Doctrine $doctrine, EncoderFactoryInterface $securityEncoder, ContainerInterface $container)
    {
        $this->em              = $doctrine->getManager();
        $this->securityEncoder = $securityEncoder;
        $this->container       = $container;
        $this->translator      = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $updateSQL       = '';
        $currentRoleName = null;
        $objUser         = $builder->getData();
        if ($objUser && $objUser->getRole()) {
            $currentRoleName = $objUser->getRole()->getId();
        }

        if ($objUser && $objUser->getId()) {
            $this->oldEmail = $objUser->getEmail();
        }

        $builder
            ->add('first_name', TextType::class, array('attr' => array('maxlength' => '100')))
            ->add('last_name', TextType::class, array('attr' => array('maxlength' => '100')))
            ->add('email', TextType::class, array('attr' => array('maxlength' => '255')))
            ->add('phone', TelType::class, array('attr' => array('autocomplete' => 'off', 'maxlength' => '25'), 'trim' => true))
            //->add('is_private_phone_number', CheckboxType::class, array('label' => 'Keep my phone number private'))
            ->add('contact_through_phone', CheckboxType::class, array('label' => 'Contact me by phone'))
            ->add('contact_through_email', CheckboxType::class, array('label' => 'Contact me by email'))
            ->add('old_meta_xml', TextareaType::class, array('label' => 'Note', 'attr' => array('maxlength' => '500')))
            ->add('business_name', TextType::class, array('label' => 'Business name', 'attr' => array('maxlength' => '100')))
            ->add(
                'business_category_id',
                ChoiceType::class,
                array(
                    'multiple' => false,
                    'label' => 'Business category',
                    'placeholder' => 'Please select category',
                    'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1)),
                )
            )
            ->add(
                'roles',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'multiple' => false,
                    'data' => $currentRoleName,
                    'label' => 'Role',
                    'placeholder' => 'Select Role',
                    'choices' => array_flip($this->em->getRepository('FaUserBundle:Role')->getRoleArray()),
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class)
            ->add('saveAndCreateAd', SubmitType::class);

        if (!$builder->getData()->getId() || $objUser->getIsHalfAccount() == 1) {
            $builder->add(
                'password',
                RepeatedType::class,
                array(
                    'type' => PasswordType::class,
                    'first_options' => array('label' => 'Password', 'constraints' => new NotBlank(array('groups'   => array('create_user'), 'message' => $this->translator->trans('Please enter password.', array(), 'validators')))),
                    'second_options' => array('label' => 'Confirm Password', 'constraints' => new NotBlank(array('groups'   => array('create_user'), 'message' => $this->translator->trans('Please enter confirm password.', array(), 'validators')))),
                    'invalid_message' => 'Password and confirm password not matched.',
                )
            );
        } else {
            $builder->add(
                'password',
                RepeatedType::class,
                array(
                    'type' => PasswordType::class,
                    'first_options' => array('label' => 'Password', 'attr' => array('autocomplete' => 'off')),
                    'second_options' => array('label' => 'Confirm Password', 'attr' => array('autocomplete' => 'off')),
                    'invalid_message' => 'Password and confirm password not matched.',
                )
            );
        }

        $em              = $this->em;
        $container       = $this->container;
        $securityEncoder = $this->securityEncoder;
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($em, $securityEncoder, $container) {
                if ($event->getForm()->isValid()) {
                    $user = $event->getForm()->getData();
                    $user->setUserName($event->getForm()->get('email')->getData());
                    $objOldRole = $user->getRoles();

                    //if email is changed do dotmailer unsubscribe for old email and subscribe for new email
                    if ($user->getId() && $this->oldEmail != $event->getForm()->get('email')->getData()) {
                        $dotmailer = $this->em->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $this->oldEmail, 'opt_in' => 1));
                        if ($dotmailer && $dotmailer->getOptIn()) {
                            //unsubscribe from dotmailer.
                            $this->em->getRepository('FaDotMailerBundle:Dotmailer')->sendUnsubscribeUserFromDotmailerRequest($dotmailer, $this->container);

                            $dotmailer->setEmail($event->getForm()->get('email')->getData());
                            $dotmailer->setGuid(CommonManager::generateGuid($event->getForm()->get('email')->getData()));
                            $this->em->persist($dotmailer);
                            $this->em->flush($dotmailer);
                            //update new email to dotmailer.
                            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:subscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
                        }
                    }

                    //set default status to active
                    if (!$user->getId()) {
                        $userActiveStatus = $em->getRepository('FaEntityBundle:Entity')->find(BaseEntityRepository::USER_STATUS_ACTIVE_ID);
                        if ($userActiveStatus) {
                            $user->setStatus($userActiveStatus);
                        }
                    }
                    $role = $event->getForm()->get('roles')->getData();

                    if ($role != '') {
                        if ($user->getRoles()) {
                            foreach ($user->getRoles() as $userRole) {
                                $user->removeRole($userRole);
                            }
                        }
                        $objRole = $em->getRepository('FaUserBundle:Role')->find($role);
                        $user->addRole($objRole);
                        $user->setRole($objRole);

                        if ($role == RoleRepository::ROLE_BUSINESS_SELLER_ID || $role == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID) {
                            if ($user->getId()) {
                                $userSite = $em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
                                if (!$userSite) {
                                    $userSite = new UserSite();
                                    $userSite->setUser($user);
                                    $em->persist($userSite);
                                    $em->flush($userSite);
                                    $culture = CommonManager::getCurrentCulture($this->container);
                                    CommonManager::removeCache($container, $this->getUserTableName().'|getUserProfileSlug|'.$user->getId().'_'.$culture);
                                }
                            }

                            $user->setImage(null);
                            //Only assign a free package if only new user is creating or user role is changing from private to business
                            if (!$objOldRole || ($objOldRole && count($objOldRole) > 0 && ($objOldRole[0]->getName() != RoleRepository::ROLE_BUSINESS_SELLER || $objOldRole[0]->getName() != RoleRepository::ROLE_NETSUITE_SUBSCRIPTION))) {
                                $em->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($user, 'my_account_user_upgrade', $this->container);
                                $user->setFreeTrialEnable(1);
                            }

                            if ($objOldRole && count($objOldRole) > 0 && $objOldRole[0]->getName() == RoleRepository::ROLE_SELLER && $objRole->getName() == RoleRepository::ROLE_BUSINESS_SELLER && $objRole->getName() == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
                                $updateSQL = "UPDATE ad SET is_trade_ad = '1' WHERE user_id = '".$user->getId()."'";
                            }

                            $em->getRepository('FaUserBundle:User')->removePrivateUserData($user->getId(), $this->container);
                        } elseif ($role == RoleRepository::ROLE_SELLER_ID) {
                            $em->getRepository('FaUserBundle:UserPackage')->closeActivePackage($user);
                            $em->getRepository('FaUserBundle:UserSite')->removeBusinessUserSiteData($user->getId(), $this->container);
                            $user->setBusinessName(null);
                            $user->setBusinessCategoryId(null);

                            if ($objOldRole && count($objOldRole) > 0 && $objOldRole[0]->getName() == RoleRepository::ROLE_BUSINESS_SELLER && $objOldRole[0]->getName() == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION && $objRole->getName() == RoleRepository::ROLE_SELLER) {
                                $updateSQL = "UPDATE ad SET is_trade_ad = '0' WHERE user_id = '".$user->getId()."'";
                            }
                        }

                        if (!empty($updateSQL)) {
                            $stmt = $em->getConnection()->prepare($updateSQL);
                            $stmt->execute();

                            exec('nohup'.' '.$container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-solr-index update --status="A,S,E" --user_id="'.$user->getId().'" >/dev/null &');
                        }
                    }

                    // set guid
                    if (!$user->getGuid()) {
                        $user->setGuid(CommonManager::generateGuid($event->getForm()->get('email')->getData()));
                    }
                }
            }
        );

        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    /**
     * This function is called on pre submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (isset($data['phone'])) {
            $data['phone'] = str_replace(' ', '', $data['phone']);
        }

        $event->setData($data);
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $this->postValidation($form);
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param Form $form object.
     */
    public function postValidation($form)
    {
        $em               = $this->em;
        $role             = $form->get('roles')->getData();
        $totalRoles       = 0;
        $isBusinessSeller = false;
        $objUser          = $form->getData();

        if ($role != '') {
            $totalRoles++;
            if ($objUser && $objUser->getId() && $objUser->getIsHalfAccount() == 0) {
                $old_data       = $this->em->getUnitOfWork()->getOriginalEntityData($form->getData());
                $oldUserRole    = (isset($old_data['role']) ? $old_data['role']->getId() : null);
                $oldBusinessCat = $old_data['business_category_id'];

                if ($oldUserRole != $form->get('roles')->getData() || $oldBusinessCat != $form->get('business_category_id')->getData()) {
                    $shopPackageDetail = $em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackageDetail($objUser->getId());
                    $activeAdCount     = $em->getRepository('FaAdBundle:Ad')->getActiveAdCountForUser($objUser->getId());

                    if (($shopPackageDetail && $shopPackageDetail->getPackage() && !$shopPackageDetail->getPackage()->getPrice()) || (!$shopPackageDetail)) {
                        //nothing to do
                    } else {
                        $form->get('roles')->addError(new FormError($this->translator->trans('You cannot edit user type or business category setting while you have live adverts or a paid profile subscription.', array(), 'validators')));
                    }
                }
            }

            if ($role == RoleRepository::ROLE_BUSINESS_SELLER_ID || $role == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID) {
                $isBusinessSeller = true;
            }
        }

        // In case of create account, check email address only with full account, not check with half account.
        // In case of edit, check with half and full account.
        if (!$objUser->getId() || $objUser->getIsHalfAccount() == 1) {
            if ($form->get('email')->getData()) {
                $fullAccountUser = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData(), 'is_half_account' => 0));
                if ($fullAccountUser) {
                    $form->get('email')->addError(new FormError($this->translator->trans('An account with this email address already exists.', array(), 'validators')));
                }
            }
        } elseif ($objUser->getId() && $objUser->getIsHalfAccount() == 0) {
            $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData()));
            if ($user && $user->getId() != $objUser->getId()) {
                $form->get('email')->addError(new FormError($this->translator->trans('An account with this email address already exists.', array(), 'validators')));
            }
        }

        if (!$form->get('contact_through_phone')->getData() && !$form->get('contact_through_email')->getData()) {
            $form->get('contact_through_phone')->addError(new FormError($this->translator->trans('Please select either contact by phone or email.', array(), 'validators')));
            $form->get('contact_through_email')->addError(new FormError($this->translator->trans('Please select either contact by phone or email.', array(), 'validators')));
        }

        if ($form->get('contact_through_phone')->getData() && $form->get('phone')->getData() == '') {
            $form->get('phone')->addError(new FormError($this->translator->trans('Phone is required.', array(), 'validators')));
        }

        if ($totalRoles <= 0) {
            $form->get('roles')->addError(new FormError($this->translator->trans('Please select atleast one role.', array(), 'validators')));
        }

        if ($isBusinessSeller && $form->has('business_category_id') && $form->get('business_category_id')->getData() == '') {
            $form->get('business_category_id')->addError(new FormError($this->translator->trans('Please select business category.', array(), 'validators')));
        }

        /*if ($form->get('is_private_phone_number')->getData() && $form->get('phone')->getData() && substr($form->get('phone')->getData(), 0, 3) == UserRepository::YAC_PRIACY_NUM_PREFIX) {
            $form->get('is_private_phone_number')->addError(new FormError($this->translator->trans(' Please enter a different telephone numbers. We are unable to allocate privacy numbers to 070 numbers.', array(), 'validators')));
        }*/

        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        if ($user && (($objUser->getId() && $objUser->getId() != $user->getId()) || !$objUser->getId()) && $user->getRole() && in_array($user->getRole()->getId(), array(RoleRepository::ROLE_ADMIN_ID, RoleRepository::ROLE_ADMIN_HIDE_SKIP_PAYMENT_ID)) && ($role == RoleRepository::ROLE_ADMIN_ID || $role == RoleRepository::ROLE_SUPER_ADMIN_ID || $role == RoleRepository::ROLE_ADMIN_HIDE_SKIP_PAYMENT_ID)) {
            $form->get('roles')->addError(new FormError($this->translator->trans('You don\'t have permission to set Admin, Admin hide skip payment and Super admin.', array(), 'validators')));
        } elseif ($user && $objUser->getId() && $objUser->getId() == $user->getId() && $user->getRole() && in_array($user->getRole()->getId(), array(RoleRepository::ROLE_ADMIN_ID, RoleRepository::ROLE_ADMIN_HIDE_SKIP_PAYMENT_ID)) && $role == RoleRepository::ROLE_SUPER_ADMIN_ID) {
            $form->get('roles')->addError(new FormError($this->translator->trans('You don\'t have permission to set Super admin.', array(), 'validators')));
        } elseif ($user && $objUser->getId() && $objUser->getId() == $user->getId() && $user->getRole() && in_array($user->getRole()->getId(), array(RoleRepository::ROLE_ADMIN_HIDE_SKIP_PAYMENT_ID)) && $role == RoleRepository::ROLE_ADMIN_ID) {
            $form->get('roles')->addError(new FormError($this->translator->trans('You don\'t have permission to set Admin hide skip payment.', array(), 'validators')));
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\UserBundle\Entity\User',
                'validation_groups' => array('registration', 'create_user'),
                //'constraints' => new UniqueEntity(array('groups'   => array('registration'), 'fields'  => 'email','message' => 'An account with this email address already exists.'))
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_admin';
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getUserTableName()
    {
        return $this->em->getClassMetadata('FaUserBundle:User')->getTableName();
    }
}
