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
use Fa\Bundle\UserBundle\Form\RegistrationType;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * PaaLiteRegistrationType form.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */

class PaaLiteRegistrationType extends RegistrationType
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
        
        $builder->add('Register', 'submit', array('label' => 'Sign up with email'));
        $sessionUserData = $this->container->get('session')->get('paa_lite_user_info');
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
            if ($form->get('email')->getData()) {
                parent::postSubmit($event);
                $sessionData = $this->container->get('session')->get('paa_lite_user_info', array());
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
                $this->container->get('session')->remove('paa_lite_user_info');               
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
        return 'fa_paa_lite_register';
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
