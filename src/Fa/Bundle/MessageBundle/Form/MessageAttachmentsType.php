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
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Gedmo\Sluggable\Util\Urlizer;
use Fa\Bundle\MessageBundle\Repository\MessageAttachmentsRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * MessageAttachmentsType form.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class MessageAttachmentsType extends AbstractType
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
     * BuildForm.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', HiddenType::class)
            ->add('session_id', HiddenType::class)
            ->add(
                'fileData',
                FileType::class,
                array(
                    'mapped'     => false,
                    'constraints' => new Assert\File(
                        array(
                        'mimeTypes' => MessageAttachmentsRepository::getAllowedMimeTypes(),
                        'mimeTypesMessage' => $this->translator->trans('Please upload a valid file.', array(), 'validators'),
                        )
                    )
                )
            );

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     *
     * @return void
     */
    public function onPostSubmit(FormEvent $event)
    {
        $objMA           = $event->getData();
        $form            = $event->getForm();
        $objUploadedFile = $form->get('fileData')->getData();

        if ($form->isValid()) {
            if ($form->get('session_id')->getData()) {
                $messageId      = $form->get('session_id')->getData();
                $attachmentPath = $this->container->getParameter('fa.ad.image.tmp.dir');
            } else {
                $messageId = $form->get('message')->getData()->getId();
                $attachmentPath = $this->container->getParameter('fa.message.attachment.dir').'/'.CommonManager::getGroupDirNameById($messageId);
            }
            $webPath          = $this->container->get('kernel')->getRootDir().'/../web';
            $hash             = CommonManager::generateHash();

            $fileOriginalName = $objUploadedFile->getClientOriginalName();
            $fileMimeType     = $objUploadedFile->getMimeType();
            $fileSize         = $objUploadedFile->getSize();
            $fileOriginalName = str_replace(array('"', "'"), '', $fileOriginalName);
            $fileExtension    = substr(strrchr($fileOriginalName, '.'), 1);
            $fileName         = $messageId.'_'.$hash.'.'.$fileExtension;
            $tmpFilePath      = $webPath.DIRECTORY_SEPARATOR.$attachmentPath;

            //upload file.
            $objUploadedFile->move($tmpFilePath, $fileName);

            $objMA->setHash($hash);
            $objMA->setPath($attachmentPath);
            $objMA->setOriginalFileName($fileOriginalName);
            $objMA->setMimeType($fileMimeType);
            $objMA->setSize($fileSize);
            $objMA->setIsImage(0);
            if (substr($fileMimeType, 0, 5) == 'image') {
                $objMA->setIsImage(1);
            }
        }
    }

    /**
     * SetDefaultOptions.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\MessageBundle\Entity\MessageAttachments',
                'csrf_protection'   => false,
            )
        );
    }

    /**
     * GetName.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_message_attachments';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_message_attachments';
    }
}
