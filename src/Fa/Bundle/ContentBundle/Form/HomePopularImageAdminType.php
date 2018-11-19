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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Entity\HomePopularImage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * Home popular image admin type.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class HomePopularImageAdminType extends AbstractType
{
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
        $builder
        ->add(
            'file',
            FileType::class,
            array(
                'label' => 'Background image',
                'constraints' => new File(array('groups'   => array('new', 'edit'), 'mimeTypes' => array("image/jpeg", "image/png", "image/gif", "image/svg+xml")))
            )
        )
        ->add(
            'overlay_file',
            FileType::class,
            array('label' => 'Overlay image')
        )
        ->add(
            'url',
            TextType::class
        )
        ->add(
            'status',
            ChoiceType::class,
            array(
                'choices' => array_flip(EntityRepository::getStatusArray($this->container))
            )
        )
        ->add('save', SubmitType::class)
        ->add('saveAndNew', SubmitType::class);
        
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $url  = $data['url'];
            // In URL if we give {} brances symfony throughing validation error to avoid that we just replaced with { %7B and }  %7D
            $url  = str_replace('{', '%7B', $url);
            $url  = str_replace('}', '%7D', $url);
            $data['url'] = $url;
            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmitData'));
    }

    /**
     * On post submit data.
     *
     * @param FormEvent $event
     */
    public function onPostSubmitData(FormEvent $event)
    {
        $form        = $event->getForm();
        $headerImage = $event->getForm()->getData();
        $oldFile     = null;

        if ($form->isValid()) {
            // Edit
            if ($form->getData()->getId()) {
                $oldFile = $headerImage->getAbsolutePathForOverlay();
            }

            $file     = $form->get('overlay_file')->getData();
            $fileName = null;
            if ($file !== null) {
                $fileName = uniqid().'.'.$file->guessExtension();
                $headerImage->setOverLayFile($file);
                $headerImage->setOverlayFileName($fileName);
            }
            $url = $form->getData()->getUrl();
            $headerImage->setUrl(urldecode($url));
            $this->em->persist($headerImage);
            $this->em->flush();

            if ($file !== null && $fileName) {
                if ($oldFile) {
                    $this->removeImage($oldFile);
                }
                $this->uploadImage($headerImage, $fileName);
            }
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
                'data_class' => 'Fa\Bundle\ContentBundle\Entity\HomePopularImage',
                'validation_groups' => function (FormInterface $form) {
                    $data = $form->getData();
                    if (!$data->getId()) {
                        return array('new');
                    } else {
                        return array('edit');
                    }
                },
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
        return 'fa_content_home_popular_image_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_home_popular_image_admin';
    }

    /**
     * Upload image.
     *
     * @param object $headerImage Header image object.
     * @param string $fileName    File name.
     *
     * @return void
     */
    public function uploadImage($headerImage, $fileName)
    {
        if ($fileName) {
            $headerImage->getOverlayFile()->move($headerImage->getUploadRootDir(), $fileName);
            $headerImage->setOverlayFile(null);
        }
    }

    /**
     * Remove image if image is not assign to any other rule.
     *
     * @param string $file Image file path.
     * @param string $file Image file name.
     */
    public function removeImage($file)
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
