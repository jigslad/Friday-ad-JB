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
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\FormError;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * This is registration form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class RegistrationType extends AbstractType
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
    private $security_encoder;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param Doctrine                $doctrine         Doctrine object.
     * @param EncoderFactoryInterface $security_encoder Object.
     * @param ContainerInterface      $container        Object.
     */
    public function __construct(Doctrine $doctrine, EncoderFactoryInterface $security_encoder, ContainerInterface $container)
    {
        $this->em                = $doctrine->getManager();
        $this->security_encoder  = $security_encoder;
        $this->container         = $container;
        $this->translator        = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('first_name', TextType::class, array('label' => 'Name'))
        ->add('last_name', TextType::class, array('label' => 'Last name'))
        ->add('email', EmailType::class, array('label' => 'Email address (not publicly displayed)'))
        ->add('phone', TelType::class, array('label' => 'Telephone number (optional)', 'attr' => array('maxlength' => '25')))
        ->add('is_private_phone_number', CheckboxType::class, array('label' => 'Keep my phone number private <br /><b>(recommended)</b>'))
        ->add('contact_through_phone', CheckboxType::class, array('label' => 'Contact by phone'))
        ->add('contact_through_email', CheckboxType::class, array('label' => 'Contact by email', 'data' => true))
        ->add('business_name', TextType::class, array('label' => 'Business name'))
        ->add(
            'business_category_id',
            ChoiceType::class,
            array(
                'multiple' => false,
                'label' => 'Business category',
                'placeholder' => 'Please select category',
                'choices'   => $this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1),
                'choice_translation_domain' => false,
            )
        )
        ->add(
            'user_roles',
            ChoiceType::class,
            array(
                'required'  => true,
                'multiple'  => false,
                'expanded'  => true,
                'mapped'    => false,
                'data' => RoleRepository::ROLE_SELLER,
                'label'     => 'Account status',
                'choices'   => array_flip(RoleRepository::getCustomerRoles($this->container)),
                'constraints' => new NotBlank(array('groups'   => array('registration'), 'message' => $this->translator->trans('Please select account status.', array(), 'validators')))
            )
        )
        ->add(
            'password',
            PasswordType::class,
            array(
                'label' => 'Password',
                'attr' => array('autocomplete' => 'off'),
                'constraints' => array('constraints' => new NotBlank(array('groups' => array('registration'), 'message' => $this->translator->trans('Please enter password.', array(), 'validators')))),
            )
        )
        ->add(
            'show_password',
            CheckboxType::class,
            array(
                'required'  => false,
                'mapped'    => false,
                'label'     => 'Show password',
            )
        );

        $builder->add('Register', SubmitType::class, array('label' => 'Register'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'))
        ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'))
        ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));

        $sessionUserData = $this->container->get('session')->get('register_user_info');
        if (isset($sessionUserData['user_facebook_id']) || isset($sessionUserData['user_google_id'])) {
            $builder->remove('password');
            $builder->remove('show_password');
        }
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

        // check email address only with full account, not check with half account.
        if ($form->get('email')->getData()) {
            $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData(), 'is_half_account' => 0));
            if ($user) {
                $form->get('email')->addError(new FormError($this->translator->trans('An account with this email address already exists, Please %login_anchor%.', array('%login_anchor%' => '<a href="'.$this->container->get('router')->generate('login').'">Log In</a>'), 'validators')));
            }
        }

        if (!$form->get('contact_through_phone')->getData() && !$form->get('contact_through_email')->getData()) {
            $form->get('contact_through_email')->addError(new FormError($this->translator->trans('Please select either contact by phone or email.', array(), 'validators')));
        }

        if ($form->get('contact_through_phone')->getData() && !trim($form->get('phone')->getData())) {
            $form->get('phone')->addError(new FormError($this->translator->trans('Please enter phone number.', array(), 'validators')));
        }

        if ($form->get('is_private_phone_number')->getData() && $form->get('phone')->getData() && substr($form->get('phone')->getData(), 0, 3) == UserRepository::YAC_PRIACY_NUM_PREFIX) {
            $form->get('is_private_phone_number')->addError(new FormError($this->translator->trans(' Please enter a different telephone numbers. We are unable to allocate privacy numbers to 070 numbers.', array(), 'validators')));
        }

        if ($form->get('user_roles')->getData()
            && $form->get('user_roles')->getData() == RoleRepository::ROLE_BUSINESS_SELLER
            && $form->has('business_category_id')
            && $form->get('business_category_id')->getData() == '') {
            $form->get('business_category_id')->addError(new FormError($this->translator->trans('Please select business category.', array(), 'validators')));
        }
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isValid()) {
            $user = $form->getData();
            $role = $form->get('user_roles')->getData();
            if ($role) {
                $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => $role));
                $user->addRole($sellerRole);
                $user->setRole($sellerRole);

                if ($role == RoleRepository::ROLE_SELLER) {
                    $user->setBusinessName(null);
                    $user->setBusinessCategoryId(null);
                } else {
                    $user->setFreeTrialEnable(1);
                }
            }
            $user->setUserName($form->get('email')->getData());

            if ($form->has('password')) {
                $encoder = $this->security_encoder->getEncoder($user);
                $user->setPlainPassword($user->getPassword());
                $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                $user->setPassword($password);
            }

            //set user status
            $userActiveStatus = $this->em->getRepository('FaEntityBundle:Entity')->find(EntityRepository::USER_STATUS_ACTIVE_ID);
            $user->setStatus($userActiveStatus);

            // set guid
            $user->setGuid(CommonManager::generateGuid($form->get('email')->getData()));
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
                'validation_groups' => array('registration'),
                'translation_domain' => 'frontend-register',
                //'constraints' => new UniqueEntity(array('groups'   => array('registration'),'fields'  => 'email','message' => 'An account with this email address already exists, Please <a href="'.$this->container->get('router')->generate('login').'">Login</a>.'))
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'user_registration';
    }
    
    public function getBlockPrefix() 
    {
        return 'user_registration';
    }
}
