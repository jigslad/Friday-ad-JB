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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Manager\UserSiteImageManager;
use Fa\Bundle\CoreBundle\Manager\ThumbnailManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * UserSiteImageType form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserSiteImageType extends AbstractType
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
            ->add('user_site', HiddenType::class)
            ->add(
                'fileData',
                FileType::class,
                array(
                    'mapped'     => false,
                    'constraints' => new Assert\File(
                        array(
                        'mimeTypes' => array(
                                'image/gif',
                                'image/jpeg',
                                'image/bmp',
                                'image/x-ms-bmp',
                                'image/png',
                            ),
                        'mimeTypesMessage' => $this->translator->trans('Please upload a valid jpg, gif, png, bmp.', array(), 'validators'),
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
        $image = $event->getData();
        $form  = $event->getForm();
        $uploadedFile = $form->get('fileData')->getData();

        if ($form->isValid()) {
            $userSiteId = $form->get('user_site')->getData()->getId();
            $imagePath = $this->container->getParameter('fa.user.site.image.dir').'/'.CommonManager::getGroupDirNameById($userSiteId);
            $maxOrder = $this->em->getRepository('FaUserBundle:UserSiteImage')->getMaxOrder($userSiteId, false);
            $webPath = $this->container->get('kernel')->getRootDir().'/../web';
            $hash = CommonManager::generateHash();
            $image->setHash($hash);
            $image->setPath($imagePath);
            $image->setOrd($maxOrder);
            $orgImageName = $uploadedFile->getClientOriginalName();
            $orgImageName = str_replace(array('"', "'"), '', $orgImageName);
            $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imagePath;
            $orgImageName = escapeshellarg($orgImageName);
            //upload original image.
            $uploadedFile->move($orgImagePath, $orgImageName);

            $usersiteImageManager = new UserSiteImageManager($this->container, $userSiteId, $hash, $orgImagePath);
            //save original jpg image.$userSiteId
            $usersiteImageManager->saveOriginalJpgImage($orgImageName);
            //create thumbnails
            $usersiteImageManager->createThumbnail();
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserSiteImage',
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
        return 'fa_user_user_site_image';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_site_image';
    }
}
