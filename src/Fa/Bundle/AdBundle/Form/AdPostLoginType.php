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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Encoder\Sha1PasswordEncoder;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * AdPostLoginType form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostLoginType extends AbstractType
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
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param object $container Container instance.
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
        $builder
        ->add(
            'username',
            EmailType::class,
            array(
                'attr' => array('autocomplete' => 'off','class' => 'username_cls'), 
                'label' => 'Email address',
                'constraints' => array(
                    new Assert\NotBlank(array('groups'   => array('olduser', 'newuser'), 'message' => $this->translator->trans('Please enter email address.', array(), 'validators'))),
                    new Assert\Email(array('groups'   => array('olduser', 'newuser'), 'message' => $this->translator->trans('The email "{{ value }}" is not a valid email.', array(), 'validators')))
                )
            )
        )
        ->add(
            'user_type',
            ChoiceType::class,
            array(
                'attr' => array('autocomplete' => 'off'),
                'expanded' => true,
                'multiple' => false,
                'choices'  => array("I have an account" => '1', "I'm new to Friday-Ad" => '2'),
                'data'     => 1,
                'constraints' => array(
                    new NotBlank(array('groups'   => array('olduser', 'newuser'), 'message' => $this->translator->trans('Please select user type.', array(), 'validators')))
                )
            )
        )
        ->add(
            'password',
            PasswordType::class,
            array(
                'label' => 'Password',
                'constraints' => array(
                    new NotBlank(array('groups'   => array('olduser'), 'message' => $this->translator->trans('Please enter password.', array(), 'validators')))
                )
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
        )
        ->add('save', SubmitType::class, array('label' => 'Next step: add more details'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('username' => $data['username'], 'is_half_account' => 0));
            if (!$user) {
                $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $data['username'], 'is_half_account' => 0));
            }

            if ($data['user_type'] == 1) {
                if (!$user) {
                    $form->addError(new FormError($this->translator->trans('Invalid email or password.', array(), 'validators')));
                } elseif ($user && (!$user->getStatus() || $user->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID)) {
                    $form->addError(new FormError($this->translator->trans('Your status is not active.', array(), 'validators')));
                } else {
                    // encode the password
                    $factory = $this->container->get('security.encoder_factory');
                    $encoder = $factory->getEncoder($user);
                    if (!$encoder->isPasswordValid($user->getPassword(), $data['password'], null)) {
                        // check with SHA1
                        $encoder = new Sha1PasswordEncoder();
                        if (!$encoder->isPasswordValid($user->getPassword(), $data['password'], $user->getSalt())) {
                            $form->addError(new FormError($this->translator->trans('Invalid email or password.', array(), 'validators')));
                        }
                    }

                    $userRolesArray = array();
                    foreach ($user->getRoles() as $userRole) {
                        $userRolesArray[] = $userRole->getName();
                    }

                    $roleToCheck = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:Role')->getRoleArrayByType('C');

                    if (!count(array_intersect($roleToCheck, $userRolesArray))) {
                        $form->addError(new FormError($this->translator->trans('You do not have enough credential to login.', array(), 'validators')));
                    }
                }
            } else if ($data['user_type'] != 1 && $user) {
                $form->get('username')->addError(new FormError($this->translator->trans('This email address already has an account - please enter password below and login.', array(), 'validators')));
            }
        }
    }

    /**
     * Set defaultOptions.
     *
     * @param OptionsResolver $resolver
     *
     * @return array
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => null,
                'translation_domain' => 'frontend-paa-login',
                'validation_groups' => function (FormInterface $form) {
                    $data = $form->getData();
                    $groups = array();
                    switch ($data['user_type']) {
                        case '1':
                            $groups = array('olduser', 'newuser');
                            break;
                        case '2':
                            $groups = array('newuser');
                            break;
                        default:
                            $groups = array('olduser', 'newuser');
                            break;
                    }
                    return $groups;
                },
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
        return 'fa_paa_login';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_login';
    }
}
