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
use Fa\Bundle\UserBundle\Manager\UserImageManager;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * UserImageType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserImageType extends AbstractType
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
            ->add('user_id', HiddenType::class)
            ->add('is_company', HiddenType::class)
            ->add('profileImage', HiddenType::class)
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
        $form         = $event->getForm();
        $uploadedFile = $form->get('fileData')->getData();

        if ($form->isValid()) {
            $userId    = $form->get('user_id')->getData();
            $isCompany = $form->get('is_company')->getData();
            $profileImage = $form->get('profileImage')->getData();
            $user      = $this->em->getRepository('FaUserBundle:User')->find($userId);

            $imageObj = null;
            if ($isCompany) {
                $imagePath = $this->container->getParameter('fa.company.image.dir').'/'.CommonManager::getGroupDirNameById($userId, 5000);
                $imageObj  = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userId));
            } else {
                $imagePath = $this->container->getParameter('fa.user.image.dir').'/'.CommonManager::getGroupDirNameById($userId, 5000);
                $imageObj  = $user;
            }

            // Check if user site entry not found then create first
            if (!$imageObj && $isCompany) {
                $imageObj = new UserSite();
                $imageObj->setUser($user);
            }

            if ($isCompany) {
                $imageObj->setPath($imagePath);
            } else {
                if ($imageObj) {
                    $imageObj->setImage($imagePath);
                }
            }
            //$imageObj->setAws(0);
            if ($imageObj) {
                $this->em->persist($imageObj);
                $this->em->flush($imageObj);
            }

            $webPath      = $this->container->get('kernel')->getRootDir().'/../web';
            
            if($flagDirectS3Upload){
                $userImageManager = new UserImageManager($this->container, $userId, $orgImagePath, $isCompany);
                $imageFileName = "";
                
                if (!empty($image->getImageName())) {
                    $imageFileName = $image->getImageName();
                } else {
                    $imageFileName = CommonManager::generateImageFileName($adTitle, $adId, $maxOrder);
                    $image->setImageName($imageFileName);
                }
                
                $userImageS3Name = $userImageManager->uploadImageDirectlyToS3($uploadedFile, $imageFileName);
                
            } else {
                $orgImageName = $uploadedFile->getClientOriginalName();
                $orgImageName = str_replace(array('"', "'"), '', $orgImageName);
                $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imagePath;
                $orgImageName = escapeshellarg($orgImageName);
    
                //upload original image.
                $uploadedFile->move($orgImagePath, $orgImageName);
    
                $userImageManager = new UserImageManager($this->container, $userId, $orgImagePath, $isCompany);
    
                // remove image if its from profile page.
                if ($profileImage) {
                    $userImageManager->removeImage();
                }
                //save original jpg image.
                //$userImageManager->saveOriginalJpgImage($orgImageName);
    
                //create thumbnails
                $userImageManager->createThumbnail();
    
                //$userImageManager->uploadImagesToS3($image);
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
                'data_class' => null,
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
        return 'fa_user_image';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_image';
    }
}
