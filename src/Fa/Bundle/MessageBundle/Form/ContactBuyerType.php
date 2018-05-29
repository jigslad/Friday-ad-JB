<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * This form is used for contact user.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ContactBuyerType extends AbstractType
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
        $loggedInUser = (CommonManager::isAuth($this->container) ? CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser() : null);
        $builder
            ->add(
                'sender_first_name',
                TextType::class,
                array(
                    'label' => 'Name',
                    'constraints' => new NotBlank(array('message' => $this->translator->trans('Please enter name.', array(), 'validators'))),
                    'data' => ($loggedInUser ? $loggedInUser->getProfileName() : null)
                )
            )
            ->add(
                'sender_email',
                EmailType::class,
                array(
                    'label' => 'Email',
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please enter email.', array(), 'validators'))),
                        new CustomEmail(array('message' => 'Please enter valid email.'))
                    ),
                    'data' => ($loggedInUser ? $loggedInUser->getEmail() : null)
                )
            )
            ->add(
                'attachment',
                FileType::class,
                array(
                    'label'       => 'Attach CV',
                    'constraints' => new Assert\File(
                        array(
                            'mimeTypes' => array(
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/rtf',
                                'text/rtf',
                                'application/pdf',
                            ),
                            'mimeTypesMessage' => $this->translator->trans('Please upload a valid file (Only allowed PDF, DOC, DOCX, or RTF).', array(), 'validators'),
                        )
                    )
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
            ->add('save', SubmitType::class, array('label' => 'Send'));
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
                'translation_domain' => 'frontend-contact-seller',
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
        return 'fa_message_contact_buyer';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_message_contact_buyer';
    }
}
