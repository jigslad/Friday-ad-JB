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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\CoreBundle\Manager\ThumbnailManager;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * AdImageType form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdImageType extends AbstractType
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
            ->add('ad', HiddenType::class)
            ->add('session_id', HiddenType::class)
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
                                'image/png',
                            ),
                        'mimeTypesMessage' => $this->translator->trans('Please upload a valid jpg, gif, png.', array(), 'validators'),
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
            if ($form->get('session_id')->getData()) {
                $adId = $form->get('session_id')->getData();
                $imagePath = $this->container->getParameter('fa.ad.image.tmp.dir');
                $maxOrder = $this->em->getRepository('FaAdBundle:AdImage')->getMaxOrder($form->get('session_id')->getData(), true);
            } else {
                $adId = $form->get('ad')->getData()->getId();
                $imagePath = $this->container->getParameter('fa.ad.image.dir').'/'.CommonManager::getGroupDirNameById($adId);
                $maxOrder = $this->em->getRepository('FaAdBundle:AdImage')->getMaxOrder($form->get('ad')->getData()->getId(), false);
            }
            $webPath = $this->container->get('kernel')->getRootDir().'/../web';
            $hash = CommonManager::generateHash();
            $image->setHash($hash);
            $image->setPath($imagePath);
            $image->setOrd($maxOrder);

            $ad = $this->em->getRepository('FaAdBundle:Ad')->find($adId);
            if ($ad) {
                $image->setImageName(Urlizer::urlize($ad->getTitle().'-'.$ad->getId().'-'.$maxOrder));
            }
            $image->setStatus('1');
            $image->setAws(0);
            $orgImageName = $uploadedFile->getClientOriginalName();
            $orgImageName = str_replace(array('"', "'"), '', $orgImageName);
            $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imagePath;
            $orgImageName = escapeshellarg($orgImageName);

            //upload original image.
            $uploadedFile->move($orgImagePath, $orgImageName);

            $adImageManager = new AdImageManager($this->container, $adId, $hash, $orgImagePath);
            //save original jpg image.
            $adImageManager->saveOriginalJpgImage($orgImageName);
            //create thumbnails
            $adImageManager->createThumbnail();
            //create cope thumbnails
            $adImageManager->createCropedThumbnail();

            //$adImageManager->uploadImagesToS3($image);
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
                'data_class' => 'Fa\Bundle\AdBundle\Entity\AdImage',
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
        return 'fa_paa_image';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_image';
    }
}
