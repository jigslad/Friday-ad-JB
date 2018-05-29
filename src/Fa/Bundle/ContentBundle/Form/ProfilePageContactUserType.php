<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used for contact user from profile page.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ProfilePageContactUserType extends AbstractType
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
        $emailAlertLabel = 'Receive news and promotions from Friday-Ad';
        $loggedInUser = (CommonManager::isAuth($this->container) ? CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser() : null);
        $builder
            ->add(
                'sender_first_name',
                TextType::class,
                array(
                    'label' => 'Name',
                    'constraints' => new NotBlank(array('message' => $this->translator->trans('Please enter name.', array(), 'validators'))),
                    'data' => (($loggedInUser && $loggedInUser->getProfileUsername()) ? $loggedInUser->getProfileUsername() : ($loggedInUser ? $loggedInUser->getProfileName() : null))
                )
            )
            ->add(
                'subject',
                TextType::class,
                array(
                    'label' => 'Subject',
                    'constraints' => new NotBlank(array('message' => $this->translator->trans('Please enter subject of message.', array(), 'validators'))),
                )
            )
            ->add(
                'text_message',
                TextareaType::class,
                array(
                    'attr' => array('rows' => 5),
                    'label' => 'Message',
                    'constraints' => new NotBlank(array('message' => $this->translator->trans('Please enter message.', array(), 'validators'))),
                )
            )
            ->add(
                'email_alert',
                CheckboxType::class,
                array(
                    /** @Ignore */
                    'label' => $emailAlertLabel,
                    'mapped' => false,
                    'value' => 1,
                    'data' => ($loggedInUser ? $loggedInUser->getIsEmailAlertEnabled() : true)
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Send'));

        if (!$loggedInUser) {
            $builder->add(
                'sender_email',
                EmailType::class,
                array(
                    'label' => 'Email',
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please enter email.', array(), 'validators'))),
                        new CustomEmail(array('message' => $this->translator->trans('Please enter valid email.', array(), 'validators')))
                    ),
                    'data' => ($loggedInUser ? $loggedInUser->getEmail() : null)
                )
            );
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
                'data_class' => 'Fa\Bundle\MessageBundle\Entity\Message',
                'translation_domain' => 'frontend-profile-page-contact-user',
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
        return 'fa_content_profile_page_contact_user';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_profile_page_contact_user';
    }
}
