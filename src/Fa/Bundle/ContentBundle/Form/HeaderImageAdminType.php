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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

use Fa\Bundle\EntityBundle\Form\EventListener\AddDomicileChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddTownChoiceFieldSubscriber;
use Fa\Bundle\ContentBundle\Entity\HeaderImage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Header image admin type.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class HeaderImageAdminType extends AbstractType
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
        if (!$builder->getForm()->getData()->getId()) {
            $builder
                ->add('file', FileType::class, array('label' => 'Main Image'))
                ->add(
                    'main_image_url',
                    TextType::class,
                    array('label' => 'Main Image url')
                    )
                ->add('phone_file', FileType::class, array('label' => 'Right-Hand-Side Image'))
                ->add(
                    'right_hand_image_url',
                    TextType::class,
                     array('label' => 'Right-Hand-Side Image url')
                 )
                ->add(
                    'status',
                    ChoiceType::class,
                    array(
                        'choices' => array_flip(EntityRepository::getStatusArray($this->container))
                    )
                )
                ->addEventSubscriber(new AddDomicileChoiceFieldSubscriber($this->container, false, 'location_domicile', 'location_country', array('multiple' => true, 'label' => "Location domicile (Don't select domicile for national image)")))
                ->addEventSubscriber(new AddTownChoiceFieldSubscriber($this->container, false, 'location_town', 'location_domicile', array('multiple' => true)))
                ->add(
                    'category',
                    ChoiceType::class,
                    array(
                        'multiple' => true,
                        'mapped' => false,
                        'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1)),
                    )
                )
                ->add(
                    'override_image',
                    CheckboxType::class,
                    array(
                            'label' => 'Override Image',
                            'required' => false
                    )
                )
                ->add('save', SubmitType::class)
                ->add('saveAndNew', SubmitType::class);
        } else {
            $domicileObj = $builder->getForm()->getData()->getLocationDomicile();
            $townObj = $builder->getForm()->getData()->getLocationTown();
            $categoryObj = $builder->getForm()->getData()->getCategory();
            if ($domicileObj) {
                $editData['domicile_id'] = $domicileObj->getId();
            } else {
                $editData['domicile_id'] = 0;
            }
            if ($townObj) {
                $editData['town_id'] = $townObj->getId();
            } else {
                $editData['town_id'] = 0;
            }
            if ($categoryObj) {
                $editData['category_id'] = $categoryObj->getId();
            } else {
                $editData['category_id'] = 0;
            }
            
            
            $builder
            ->add('file', FileType::class, array('label' => 'Main Image'))
            ->add(
                'main_image_url',
                TextType::class,
                array('label' => 'Main Image url')
                )
            ->add('phone_file', FileType::class, array('label' => 'Right-Hand-Side Image'))
            ->add(
                'right_hand_image_url',
                TextType::class,
                array('label' => 'Right-Hand-Side Image url')
                )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container))
                    )
                )
            ->add(
                    'location_domicile',
                    ChoiceType::class,
                    array(
                        'multiple' => true,
                        'mapped' => false,
                        'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Location')->getLocationByLevelArray(2)),
                        'data' => array($editData['domicile_id']),
                        'required' => false,
                        'label' => "Location domicile (Don't select domicile for national image)"
                    )
                )
            ->add(
                    'location_town',
                    ChoiceType::class,
                    array(
                        'multiple' => true,
                        'mapped' => false,
                        'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Location')->getTownByIdArray($editData['domicile_id'])),
                        'data' => array($editData['town_id'])
                    )
                )
            ->add(
                    'category',
                    ChoiceType::class,
                    array(
                        'multiple' => true,
                        'mapped' => false,
                        'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1)),
                        'data' => array($editData['category_id'])
                    )
                )
            ->add(
                      'override_image',
                       CheckboxType::class,
                       array(
                               'label' => 'Override Image',
                               'required' => false
                       )
                )
            ->add('save', SubmitType::class);

            // Below is used to load the car selectbox when brand is submitted
            $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($editData) {
                $form = $event->getForm();
     
                if ($editData['town_id']!='') {
                    $form->add(
                        'location_town',
                        ChoiceType::class,
                        array(
                            'multiple' => true,
                            'mapped' => false,
                            'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Location')->getTownByIdArray($editData['domicile_id'])),
                            'data' => array($editData['town_id'])
                        )
                    );
                }
            });

            // Below is used to load the car selectbox when brand is submitted
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
     
                if (array_key_exists('location_town', $data)) {
                    $form->add(
                        'location_town',
                        ChoiceType::class,
                        array(
                            'multiple' => true,
                            'mapped' => false,
                            'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Location')->getTownByIdArray($data['location_domicile'][0])),
                            'data' => $data['location_town']
                        )
                    );
                }
            });
        }

        $builder->add(
            'screen_type',
            ChoiceType::class,
            array(
                'placeholder' => 'Select screen type',
                'choices'   => array_flip($this->em->getRepository('FaContentBundle:HeaderImage')->getScreenType()),
            )
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) use ($builder) {
                $form = $event->getForm();
                
                $categories = $form->get('category')->getData();
                if (!count($categories)) {
                    $event->getForm()->get('category')->addError(new \Symfony\Component\Form\FormError('Please select category.'));
                }
               

                $screenType = $form->get('screen_type')->getData();
                if (!$screenType) {
                    $event->getForm()->get('screen_type')->addError(new \Symfony\Component\Form\FormError('Please select screen type.'));
                }
            }
        );

        
        

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
        
        if ($form->isValid()) {
            if (!$form->getData()->getId()) {
                // New
                $domicileIds = $form->get('location_domicile')->getData();
                $townIds     = $form->get('location_town')->getData();
                $categories = $form->get('category')->getData();

                if (!empty($townIds)) {
                    $this->save($form, $townIds, true, $categories);
                } elseif (!empty($domicileIds)) {
                    $this->save($form, $domicileIds, false, $categories);
                } else {
                    $this->save($form, null, null, $categories);
                }
            } else {
                // Edit
                $oldFile     = $headerImage->getAbsolutePath();
                $oldFileName = $headerImage->getFileName();

                $oldPhoneFile     = $headerImage->getPhoneFileAbsolutePath();
                $oldPhoneFileName = $headerImage->getPhoneFileName();

                $headerImage->setStatus($form->get('status')->getData());

                $file     = $form->get('file')->getData();
                $fileName = null;
                if ($file !== null) {
                    $fileName = uniqid().'.'.$file->guessExtension();
                    $headerImage->setFile($file);
                    $headerImage->setFileName($fileName);
                }

                // phone image
                $phoneFile     = $form->get('phone_file')->getData();
                $phoneFileName = null;
                if ($phoneFile !== null) {
                    $phoneFileName = uniqid().'.'.$phoneFile->guessExtension();
                    $headerImage->setPhoneFile($phoneFile);
                    $headerImage->setPhoneFileName($phoneFileName);
                }
 
                $domicileIds = $form->get('location_domicile')->getData();
                $townIds     = $form->get('location_town')->getData();
                $categories = $form->get('category')->getData();

                if (!empty($townIds)) {
                    $this->save($form, $townIds, true, $categories);
                } elseif (!empty($domicileIds)) {
                    $this->save($form, $domicileIds, false, $categories);
                } else {
                    $this->save($form, null, null, $categories);
                }

                $this->em->persist($headerImage);
                $this->em->flush();

                if ($file !== null && $fileName) {
                    $this->removeImage($headerImage, $oldFile, $oldFileName);
                    $this->uploadImage($headerImage, $fileName);
                }

                if ($phoneFile !== null && $phoneFileName) {
                    $this->removeImage($headerImage, $oldPhoneFile, $oldPhoneFileName);
                    $this->uploadPhoneImage($headerImage, $phoneFileName);
                }
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
                'data_class' => 'Fa\Bundle\ContentBundle\Entity\HeaderImage',
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
        return 'fa_content_header_image_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_header_image_admin';
    }

    /**
     * Save header image.
     *
     * @param object  $form     Form object.
     * @param array   $location Town or domicile ids array.
     * @param boolean $isTown   Locations are town or domiciles.
     */
    public function save($form, $locations = array(), $isTown = false, $categories = array())
    {
        $headerImage      = $form->getData();
        $locationTown     = null;
        $locationDomicile = null;
        $locationCountry  = $this->em->getRepository('FaEntityBundle:Location')->find(LocationRepository::COUNTY_ID);
        $status           = $form->get('status')->getData();
        $file             = $form->get('file')->getData();
        $phoneFile        = $form->get('phone_file')->getData();
        $override_image   = $form->get('override_image')->getData();
        $headerImageId    = $form->getData()->getId();

        $fileName = null;
        if ($file !== null) {
            $fileName = uniqid().'.'.$file->guessExtension();
        }

        $phoneFileName = null;
        if ($phoneFile !== null) {
            $phoneFileName = uniqid().'.'.$phoneFile->guessExtension();
        }
        
        if (!empty($locations)) {
            foreach ($locations as $index => $locationId) {
                if ($isTown) {
                    $locationTown     = $this->em->getRepository('FaEntityBundle:Location')->find($locationId);
                    $locationDomicile = $locationTown->getParent();
                    $locationTownId     = $locationTown->getId();
                } else {
                    $locationDomicile = $this->em->getRepository('FaEntityBundle:Location')->find($locationId);
                }
                
                if (count($categories)) {
                    foreach ($categories as $category) {
                        if ($headerImageId=='') {
                            $headerImage = new HeaderImage();
                        }
                        $headerImage->setCategory($this->em->getReference('FaEntityBundle:Category', $category));
                        $headerImage->setScreenType($form->get('screen_type')->getData());
                        if ($headerImageId == '') {
                            $headerImage->setFile($file);
                            $headerImage->setFileName($fileName);
                            $headerImage->setPhoneFile($phoneFile);
                            $headerImage->setPhoneFileName($phoneFileName);
                        }
                        $headerImage->setLocationCountry($locationCountry);
                        $headerImage->setLocationDomicile($locationDomicile);
                        $headerImage->setLocationTown($locationTown);
                        $headerImage->setStatus($status);
                        $headerImage->setOverrideImage($override_image);
                        $this->em->persist($headerImage);
                    }
                } else {
                    if ($headerImageId=='') {
                        $headerImage = new HeaderImage();
                    }
                    
                    $headerImage->setScreenType($form->get('screen_type')->getData());
                    if ($headerImageId == '') {
                        $headerImage->setFile($file);
                        $headerImage->setFileName($fileName);
                        $headerImage->setPhoneFile($phoneFile);
                        $headerImage->setPhoneFileName($phoneFileName);
                    }
                    $headerImage->setLocationCountry($locationCountry);
                    $headerImage->setLocationDomicile($locationDomicile);
                    $headerImage->setLocationTown($locationTown);
                    $headerImage->setStatus($status);
                    $headerImage->setOverrideImage($override_image);
                    $this->em->persist($headerImage);
                }
            }
            if ($headerImageId == '') {
                $this->uploadImage($headerImage, $fileName);
                $this->uploadPhoneImage($headerImage, $phoneFileName);
            }
            $this->em->flush();
        } else {
            if (count($categories)) {
                foreach ($categories as $category) {
                    if ($headerImageId=='') {
                        $headerImage = new HeaderImage();
                    }
                    $headerImage->setCategory($this->em->getReference('FaEntityBundle:Category', $category));
                    $headerImage->setScreenType($form->get('screen_type')->getData());
                    if ($headerImageId == '') {
                        $headerImage->setFile($file);
                        $headerImage->setFileName($fileName);
                        $headerImage->setPhoneFile($phoneFile);
                        $headerImage->setPhoneFileName($phoneFileName);
                    }
                    $headerImage->setStatus($status);
                    $headerImage->setOverrideImage($override_image);
                    $this->em->persist($headerImage);
                }
            } else {
                if ($headerImageId=='') {
                    $headerImage = new HeaderImage();
                }
                $headerImage->setScreenType($form->get('screen_type')->getData());
                if ($headerImageId == '') {
                    $headerImage->setFile($file);
                    $headerImage->setFileName($fileName);
                    $headerImage->setPhoneFile($phoneFile);
                    $headerImage->setPhoneFileName($phoneFileName);
                }
                $headerImage->setStatus($status);
                $headerImage->setOverrideImage($override_image);
                $this->em->persist($headerImage);
            }

            $this->em->flush();

            if ($headerImageId == '') {
                $this->uploadImage($headerImage, $fileName);
                $this->uploadPhoneImage($headerImage, $phoneFileName);
            }
        }
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
            $headerImage->getFile()->move($headerImage->getUploadRootDir(), $fileName);
            $headerImage->setFile(null);
        }
    }

    /**
     * Upload phone image.
     *
     * @param object $headerImage Header image object.
     * @param string $fileName    File name.
     *
     * @return void
     */
    public function uploadPhoneImage($headerImage, $fileName)
    {
        if ($fileName) {
            $headerImage->getPhoneFile()->move($headerImage->getUploadRootDir(), $fileName);
            $headerImage->setPhoneFile(null);
        }
    }

    /**
     * Remove image if image is not assign to any other rule.
     *
     * @param object $headerImage Header image object.
     * @param string $file        Image file path.
     * @param string $file        Image file name.
     */
    public function removeImage($headerImage, $file, $fileName)
    {
        // Count how many rules found with same image, delete image if only one rule found
        $data['query_filters'] = array('header_image' => array('file_name' => $fileName));
        $this->container->get('fa.sqlsearch.manager')->init($this->em->getRepository('FaContentBundle:HeaderImage'), $data);
        $imageCount = $this->container->get('fa.sqlsearch.manager')->getResultCount();

        // Delete image from directory
        if ($imageCount < 1) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
