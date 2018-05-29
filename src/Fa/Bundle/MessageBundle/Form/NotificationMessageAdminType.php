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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\MessageBundle\Repository\NotificationMessageRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Static page admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class NotificationMessageAdminType extends AbstractType
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
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
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
            ->add('name', TextType::class, array('required' => true))
            ->add('message', TextareaType::class, array('required' => true))
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => EntityRepository::getStatusArray($this->container)
                )
            )
            ->add(
                'notification_type',
                ChoiceType::class,
                array(
                    'choices' => EntityRepository::getNotificationTypeArray($this->container)
                )
            )
            ->add('save', SubmitType::class);
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
                'data_class'        => 'Fa\Bundle\MessageBundle\Entity\NotificationMessage',
                'validation_groups' =>  array('default'),
            )
        );
    }

    /**
     * Upload image.
     *
     * @param object $notificationMessage Notification message object.
     * @param string $fileName            File name.
     *
     * @return void
     */
    public function uploadImage($notificationMessage, $fileName)
    {
        if ($fileName) {
            $notificationMessage->getFile()->move($notificationMessage->getUploadRootDir(), $fileName);
            $notificationMessage->setFile(null);
        }
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_message_notification_message_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_message_notification_message_admin';
    }
}
