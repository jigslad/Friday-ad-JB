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
use Fa\Bundle\UserBundle\Form\RegistrationType;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * AdPostRegistrationType form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostRegistrationType extends RegistrationType
{
    /**
     * Security_encoder.
     *
     * @var object
     */
    private $security_encoder;

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
     * @param Doctrine                $doctrine
     * @param EncoderFactoryInterface $security_encoder
     * @param ContainerInterface      $container
     */
    public function __construct(Doctrine $doctrine, EncoderFactoryInterface $security_encoder, ContainerInterface $container)
    {
        parent::__construct($doctrine, $security_encoder, $container);
        $this->container         = $container;
        $this->em                = $doctrine->getManager();
        $this->security_encoder  = $security_encoder;
        $this->translator        = CommonManager::getTranslator($container);
    }

    /**
     * Build Form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $emailAlertLabel = 'I\'d like to receive news, offers and promotions by email from Friday-Ad';
        $thirdPartyEmailAlertLabel = 'I\'d like to receive offers and promotions by email on behalf of carefully chosen partners';

        $builder->add('Register', SubmitType::class, array('label' => 'Next step: add more details'));
        $builder->remove('business_category_id');
        $builder->add('business_category_id', HiddenType::class);
        $builder->add(
            'user_roles',
            ChoiceType::class,
            array(
                'required'  => true,
                'multiple'  => false,
                'expanded'  => true,
                'mapped'    => false,
                'label'     => 'Account status',
                'choices'   => array_flip(RoleRepository::getCustomerRoles($this->container)),
                'constraints' => new NotBlank(array('groups'   => array('registration'), 'message' => $this->translator->trans('Please select account status.', array(), 'validators')))
            )
        )
        ->add(
            'is_email_alert_enabled',
            CheckboxType::class,
            array(
                'label' => $emailAlertLabel,
                'value' => true,
            )
        )
        ->add(
            'is_third_party_email_alert_enabled',
            CheckboxType::class,
            array(
                'label' => $thirdPartyEmailAlertLabel,
                'value' => true,
            )
        )
        ->add('is_terms_agreed', CheckboxType::class, array(
            'required' => true,
            'mapped' => false,
            'constraints' => array(
                'constraints' => new NotBlank(array(
                    'groups' => array(
                        'registration'
                    ),
                    'message' => $this->translator->trans("You must accept Friday-Ad's terms and conditions in order to register.", array(), 'validators')
                ))
            )
        ));
        $sessionUserData = $this->container->get('session')->get('paa_user_info');
        if (isset($sessionUserData['user_facebook_id']) || isset($sessionUserData['user_google_id'])) {
            $builder->remove('password');
            $builder->remove('show_password');
        }
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->isValid()) {
            parent::postSubmit($event);
            $sessionData = $this->container->get('session')->get('paa_user_info', array());
            if (count($sessionData)) {
                $user = $form->getData();
                if (isset($sessionData['user_facebook_id']) && $sessionData['user_facebook_id']) {
                    $user->setFacebookId($sessionData['user_facebook_id']);
                }
                $userEmail = $form->get('email')->getData();
                if (isset($sessionData['user_is_facebook_verified']) && $sessionData['user_is_facebook_verified'] && $userEmail == $sessionData['user_email']) {
                    $user->setIsFacebookVerified(1);
                }

                if (isset($sessionData['user_google_id']) && $sessionData['user_google_id']) {
                    $user->setGoogleId($sessionData['user_google_id']);
                }
            }
            $this->container->get('session')->remove('paa_user_info');
        }
    }
    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_registration';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_registration';
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
                //'constraints' => new UniqueEntity(array('groups'   => array('registration'),'fields'  => 'email','message' => 'An account with this email address already exists, Please <a href="'.$this->container->get('router')->generate('ad_post_third_step').'">Login</a>.'))
            )
        );
    }
}
