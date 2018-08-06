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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;


/**
 * This form is used for contact seller.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ContactSellerType extends AbstractType
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
        $emailAlertLabel = 'I\'d like to receive news, offers and promotions from Friday-Ad';
        $thirdPartyEmailAlertLabel = 'I\'d like to receive offers and promotions from third parties';

        $rootCategoryId = $options['rootCategoryId'];
        /*if ($rootCategoryId) {
            $entityCacheManager = $this->container->get('fa.entity.cache.manager');
            $rootCategoryName = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $rootCategoryId);
            $emailAlertLabel = 'Receive emails from Friday-Ad about '.$rootCategoryName;
            $thirdPartyEmailAlertLabel = 'Receive emails from third parties related to '.$rootCategoryName;
        }*/
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
            /* ->add(
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
            ) */
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
                    'label' => $emailAlertLabel,
                    'mapped' => false,
                    'data' => ($loggedInUser ? $loggedInUser->getIsEmailAlertEnabled() : false),
                )
            )
            ->add(
                'third_party_email_alert',
                CheckboxType::class,
                array(
                    'label' => $thirdPartyEmailAlertLabel,
                    'mapped' => false,
                    'data' => ($loggedInUser ? $loggedInUser->getIsThirdPartyEmailAlertEnabled() : false)
                )
            )
            ->add(
                'search_agent',
                CheckboxType::class,
                array(
                    'label' => 'Receive email alerts for adverts like this',
                    'mapped' => false,
                    'value' => 0,
                    'data' => false,
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
                'translation_domain' => 'frontend-contact-seller',
            )
        )->setDefined(array(
                'rootCategoryId',
            ));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_message_contact_seller';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_message_contact_seller';
    }
}
