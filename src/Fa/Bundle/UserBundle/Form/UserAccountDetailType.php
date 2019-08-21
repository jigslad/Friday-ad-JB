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
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Form\FormError;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Encoder\Sha1PasswordEncoder;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is user account detail form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserAccountDetailType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager class object.
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
     * string.
     *
     * @var string
     */
    private $oldEmail;

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
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $loggedInUser = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
        $this->oldEmail = $loggedInUser->getEmail();
        $builder
            ->add(
                'email',
                TextType::class,
                array(
                    'attr' => array('maxlength' => '255'),
                    'label' => 'Email address',
                )
            )
            ->add('phone', TelType::class, array('attr' => array('autocomplete' => 'off', 'maxlength' => '25'), 'trim' => true, 'label' => 'Telephone number (optional)'))
            ->add('is_private_phone_number', CheckboxType::class, array('data' => (($loggedInUser->getIsPrivatePhoneNumber())?true:false),'label' => '<span style="color:#ff0000">Keep my phone number private <br /><br /> Please note this privacy number feature will no longer exist from 1st September 2019. Read more <a href="https://help.friday-ad.co.uk/hc/en-us/articles/360034112434" target="_blank">here</a>.</span>'))
            ->add('contact_through_phone', CheckboxType::class, array('label' => 'Contact by phone'))
            ->add('contact_through_email', CheckboxType::class, array('label' => 'Contact by email')) 
            ->add('save_changes', SubmitType::class, array('label' => 'Save changes'));

        // show old password only if password is blank.
        if ($loggedInUser->getPassword()) {
            $builder->add(
                'new_password',
                PasswordType::class,
                array(
                    'label' => 'New password',
                    'mapped' => false,
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
            ->add(
                'old_password',
                PasswordType::class,
                array(
                    'label' => 'Old password',
                    'mapped' => false,
                )
            );
        }

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $user = $event->getForm()->getData();
                $user->setUserName($event->getForm()->get('email')->getData());
                //if email is changed check for dotmailer.
                if ($this->oldEmail != $event->getForm()->get('email')->getData()) {
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
        $user        = $form->getData();
        $oldPassword = $user->getPassword();
        $encoder     = $this->container->get('security.encoder_factory')->getEncoder($user); //get encoder for hashing pwd later
        $sh1Encoder  = new Sha1PasswordEncoder();

        if (!$form->get('contact_through_phone')->getData() && !$form->get('contact_through_email')->getData()) {
            $form->get('contact_through_email')->addError(new FormError($this->translator->trans('Please select either contact by phone or email.', array(), 'validators')));
        }

        if ($form->get('contact_through_phone')->getData() && $form->get('phone')->getData() == '') {
            $form->get('phone')->addError(new FormError($this->translator->trans('Phone is required.', array(), 'validators')));
        }

        /*if ($form->get('is_private_phone_number')->getData() && $form->get('phone')->getData() && substr($form->get('phone')->getData(), 0, 3) == UserRepository::YAC_PRIACY_NUM_PREFIX) {
            $form->get('is_private_phone_number')->addError(new FormError($this->translator->trans(' Please enter a different telephone numbers. We are unable to allocate privacy numbers to 070 numbers.', array(), 'validators')));
        }*/
        
        if ($form->get('is_private_phone_number')->getData()!='') {
            $form->get('is_private_phone_number')->addError(new FormError($this->translator->trans(' Please uncheck privacy number.', array(), 'validators')));
        }

        if ($form->has('old_password')) {
            $oldPasswordEncoded = $encoder->encodePassword($form->get('old_password')->getData(), $user->getSalt());
            if ($form->get('old_password')->getData() && !$form->get('new_password')->getData()) {
                $form->get('new_password')->addError(new FormError($this->translator->trans('Please enter new password.', array(), 'validators')));
            }

            if (!$form->get('old_password')->getData() && $form->get('new_password')->getData()) {
                $form->get('old_password')->addError(new FormError($this->translator->trans('Please enter old password.', array(), 'validators')));
            } elseif ($form->get('old_password')->getData() && $form->get('new_password')->getData() && !$encoder->isPasswordValid($user->getPassword(), $form->get('old_password')->getData(), $user->getSalt())) {
                if (!$sh1Encoder->isPasswordValid($user->getPassword(), $form->get('old_password')->getData(), $user->getSalt())) {
                    $form->get('old_password')->addError(new FormError($this->translator->trans('Please enter correct old password.', array(), 'validators')));
                }
            }
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
                'validation_groups' => array('user_detail'),
                'constraints' => new UniqueEntity(array('groups' => array('user_detail'), 'fields'  => 'email','message' => $this->translator->trans('An account with this email address already exists.', array(), 'validators'))),
                'translation_domain' => 'frontend-user-account-detail',
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_account_detail';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_account_detail';
    }
}
