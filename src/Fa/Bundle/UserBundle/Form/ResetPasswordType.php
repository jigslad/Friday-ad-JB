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
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is registration form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ResetPasswordType extends AbstractType
{
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
     * Constructor.
     *
     * @param Doctrine                $doctrine         Doctrine object.
     * @param EncoderFactoryInterface $security_encoder Object.
     */
    public function __construct(Doctrine $doctrine, EncoderFactoryInterface $security_encoder)
    {
        $this->em                = $doctrine->getManager();
        $this->security_encoder  = $security_encoder;
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
        ->add('password', RepeatedType::class, array(
            'type' => 'password',
            'required' => true,
            'first_options' => array('label' => 'Password', 'constraints' => array(new NotBlank(array('groups' => array('reset-password'))))),
            'second_options' => array('label' => 'Confirm Password', 'constraints' => array(new NotBlank(array('groups' => array('reset-password'))))),
            'invalid_message' => 'Password and confirm password not matched.',
        ));

        $builder->add('Reset', SubmitType::class);

        //$em                = $this->em;
        $security_encoder = $this->security_encoder;
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($security_encoder) {
                $form = $event->getForm();
                if ($form->isValid()) {
                    $user = $event->getForm()->getData();
                    $encoder = $security_encoder->getEncoder($user);
                    $user->setPlainPassword($user->getPassword());
                    $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                    $user->setPassword($password);
                }
            }
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fa\Bundle\UserBundle\Entity\User',
            'validation_groups' => array('reset-password'),
        ));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_reset_password';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_reset_password';
    }
}
