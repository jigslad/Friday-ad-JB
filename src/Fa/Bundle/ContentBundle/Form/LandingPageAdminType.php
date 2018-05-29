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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\ContentBundle\Repository\LandingPageRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Fa\Bundle\ContentBundle\Entity\LandingPage;
use Symfony\Component\Validator\Constraints\File;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\ContentBundle\Entity\LandingPageInfo;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormError;
use Fa\Bundle\ContentBundle\Entity\LandingPagePopularSearch;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


/**
 * Landing page admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LandingPageAdminType extends AbstractType
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
     * Category id.
     *
     * @var integer
     */
    private $categoryId;

    /**
     * Category image array.
     *
     * @var array
     */
    private $imagesArray = array();

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
        $popularSearchArray = array();
        if ($builder->getData()->getId()) {
            $popularSearchArray = $this->em->getRepository('FaContentBundle:LandingPagePopularSearch')->getPopularSearchArrayByLandingPageId($builder->getData()->getId(), $this->container);
        }

        if (!$builder->getForm()->getData()->getId()) {
            $params = $this->container->get('request_stack')->getCurrentRequest()->get($this->getName());
            if (isset($params['category'])) {
                $this->categoryId = $params['category'];
            }
            $builder
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'choices' => array_flip(LandingPageRepository::getLandingPageTypeArray($this->container))
                )
            )
            ->add(
                'category',
                EntityType::class,
                array(
                    'placeholder' => 'Select category',
                    'class' => 'FaEntityBundle:Category',
                    'choice_label' => 'name',
                    'query_builder' => function (CategoryRepository $er) {
                        $qb = $er->createQueryBuilder(CategoryRepository::ALIAS)
                        ->where(CategoryRepository::ALIAS.'.id IN (:id)')
                        ->setParameter('id', array(CategoryRepository::FOR_SALE_ID, CategoryRepository::PROPERTY_ID, CategoryRepository::MOTORS_ID, CategoryRepository::ANIMALS_ID, CategoryRepository::JOBS_ID, CategoryRepository::ADULT_ID));

                        return $qb;
                    }
                )
            );
            $builder->addEventListener(
                FormEvents::SUBMIT,
                function ($event) {
                    $form        = $event->getForm();
                    $categoryId  = $form->get('category')->getData();
                    $landingPage = $this->em->getRepository('FaContentBundle:LandingPage')->findOneBy(array('category' => $categoryId));
                    if ($landingPage) {
                        $event->getForm()->get('category')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Landing page already exist for selected category.', array(), 'validators')));
                    }
                }
            );
        } else {
            $this->categoryId = $builder->getForm()->getData()->getCategory()->getId();
        }

        $landingPageImageArray = $this->em->getRepository('FaContentBundle:LandingPageInfo')->getLandingPageImageArray($this->container);
        if (isset($landingPageImageArray[$this->categoryId]) && count($landingPageImageArray[$this->categoryId])) {
            $this->imagesArray = $landingPageImageArray[$this->categoryId];
        }

        $builder
            ->add(
                'description',
                TextareaType::class,
                array(
                    'label' => 'Intro text',
                )
            )
            ->add(
                'file',
                FileType::class,
                array(
                    'label' => 'Hero image',
                    'constraints' => new File(array('groups'   => array('new', 'edit'), 'mimeTypes' => array("image/jpeg", "image/png", "image/gif", "image/svg+xml")))
                )
            )
            ->add('h1_tag', TextType::class, array('label' => 'H1 Tag', 'required' => false))
            ->add('meta_description', TextType::class, array('label' => 'Meta Description', 'required' => false))
            ->add('meta_keywords', TextType::class, array('label' => 'Meta Keywords', 'required' => false))
            ->add('page_title', TextType::class, array('label' => 'Page Title', 'required' => false))
            ->add('popular_search', CheckboxType::class, array('label' => 'Popular search', 'required' => false))
            ->add('no_index', CheckboxType::class, array('label' => 'No Index', 'required' => false))
            ->add('no_follow', CheckboxType::class, array('label' => 'No Follow', 'required' => false))
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        //popular search fields
        for ($i = 1; $i <= 12; $i++) {
            $builder->add('popular_search_title_'.$i, TextType::class, array('required' => false, 'mapped' => false, 'data' => (isset($popularSearchArray[$i-1]) ? $popularSearchArray[$i-1]['title'] : '') ));
            $builder->add('popular_search_url_'.$i, TextType::class, array('required' => false, 'mapped' => false, 'data' => (isset($popularSearchArray[$i-1]) ? $popularSearchArray[$i-1]['url'] : '') ));
        }

        if ($this->imagesArray) {
            foreach ($this->imagesArray as $sectionId => $images) {
                foreach ($images as $image) {
                    $builder->add(
                        $image['field_name'],
                        FileType::class,
                        array(
                            /** @Ignore */
                            'label' => $image['name'],
                            'mapped' => false,
                            'constraints' => array(
                                new File(array('groups'   => array('new', 'edit'), 'mimeTypes' => array("image/jpeg", "image/png", "image/gif", "image/svg+xml"))),
                                new NotBlank(array('groups'   => array('new'), 'message' => $this->translator->trans('File is required', array(), 'validators'))),
                            )
                        )
                    );

                    if (isset($image['overlay']) && $image['overlay']) {
                        $builder->add(
                            $image['field_name'].'_overlay',
                            FileType::class,
                            array(
                                /** @Ignore */
                                'label' => $image['name'],
                                'mapped' => false,
                                'constraints' => array(
                                    new File(array('groups'   => array('new', 'edit'), 'mimeTypes' => array("image/jpeg", "image/png", "image/gif", "image/svg+xml"))),
                                    new NotBlank(array('groups'   => array('new'), 'message' => $this->translator->trans('File is required', array(), 'validators'))),
                                )
                            )
                        );
                    }
                }
            }

        }
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'))
            ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
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
                'data_class' => 'Fa\Bundle\ContentBundle\Entity\LandingPage',
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
        return 'fa_content_landing_page_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_landing_page_admin';
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     *
     * @return void
     */
    public function onPostSubmit(FormEvent $event)
    {
        $landingPage = $event->getData();
        $form        = $event->getForm();
        $isNew       = ($form->getData()->getId() ? false : true);
        $popularSearch = $form->get('popular_search')->getData();

        if ($form->isValid()) {
            //remove landing page popular search
            if ($landingPage->getId()) {
                $this->em->getRepository('FaContentBundle:LandingPagePopularSearch')->removeRecordsByLandingPageId($landingPage->getId());
                $culture = CommonManager::getCurrentCulture($this->container);
                CommonManager::removeCache($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:LandingPagePopularSearch')->getTableName().'|getPopularSearchArrayByLandingPageId|'.$landingPage->getId().'_'.$culture);
            }
            $landingPage->setStatus(1);
            //save landing page popular search
            if ($popularSearch) {
                for ($i = 1; $i <=12; $i++) {
                    if ($form->get('popular_search_title_'.$i)->getData() && $form->get('popular_search_url_'.$i)->getData()) {
                        $landingPagePopularSearch = new LandingPagePopularSearch();
                        $landingPagePopularSearch->setLandingPage($landingPage);
                        $landingPagePopularSearch->setTitle($form->get('popular_search_title_'.$i)->getData());
                        $landingPagePopularSearch->setUrl($form->get('popular_search_url_'.$i)->getData());
                        $this->em->persist($landingPagePopularSearch);
                    }
                }
                $this->em->flush();
            }

            if (count($this->imagesArray)) {
                foreach ($this->imagesArray as $sectionId => $images) {
                    foreach ($images as $image) {
                        $file           = null;
                        $overlayFile     = null;
                        $oldFile         = null;
                        $oldOverLayFile  = null;
                        $fileName        = null;
                        $landingPageInfo = null;
                        // Edit
                        if (!$isNew) {
                            if ($image['type'] == 'category') {
                                $landingPageInfo = $this->em->getRepository('FaContentBundle:LandingPageInfo')->findOneBy(array('landing_page' => $landingPage->getId(), 'category_id' => $image['id'], 'section_type' => $sectionId));
                            } elseif ($image['type'] == 'adtype') {
                                $landingPageInfo = $this->em->getRepository('FaContentBundle:LandingPageInfo')->findOneBy(array('landing_page' => $landingPage->getId(), 'ad_type_id' => $image['id'], 'section_type' => $sectionId));
                            }
                            // get old file names.
                            if ($landingPageInfo) {
                                if ($landingPageInfo->getFileName()) {
                                    $oldFile = $landingPageInfo->getUploadRootDir().'/'.$landingPageInfo->getFileName();
                                }
                                if ($landingPageInfo->getOverLayFileName()) {
                                    $oldOverLayFile = $landingPageInfo->getUploadRootDir().'/'.$landingPageInfo->getOverLayFileName();
                                }
                            }
                        }

                        if (!isset($landingPageInfo)) {
                            $landingPageInfo = new LandingPageInfo();
                            $landingPageInfo->setLandingPage($landingPage);
                            $landingPageInfo->setSectionType($sectionId);
                            if (isset($image['type'])) {
                                if ($image['type'] == 'category') {
                                    $landingPageInfo->setCategoryId($image['id']);
                                } elseif ($image['type'] == 'adtype') {
                                    $landingPageInfo->setAdTypeId($image['id']);
                                }
                            }
                        }

                        $file = $form->get($image['field_name'])->getData();
                        if ($image['overlay']) {
                            $overlayFile = $form->get($image['field_name'].'_overlay')->getData();
                        }
                        if ($file !== null) {
                            if ($oldFile) {
                                $this->removeImage($oldFile);
                            }
                            $fileName = uniqid().'.'.$file->guessExtension();
                            $landingPageInfo->setFile($file);
                            $landingPageInfo->setFileName($fileName);
                            $this->uploadImage($landingPageInfo, $fileName);
                        }

                        if ($overlayFile !== null) {
                            if ($oldOverLayFile) {
                                $this->removeImage($oldOverLayFile);
                            }
                            $fileName = uniqid().'.'.$overlayFile->guessExtension();
                            $landingPageInfo->setOverLayFile($overlayFile);
                            $landingPageInfo->setOverlayFileName($fileName);
                            $this->uploadImage($landingPageInfo, $fileName, true);
                        }

                        $this->em->persist($landingPageInfo);
                    }
                }
                $this->em->flush($landingPageInfo);
            }
        }
    }

    /**
     * Upload image.
     *
     * @param object $landingPageInfo Landing page info image object.
     * @param string $fileName        File name.
     *
     * @return void
     */
    public function uploadImage($landingPageInfo, $fileName, $isOverlay = false)
    {
        if ($fileName) {
            if ($isOverlay) {
                $landingPageInfo->getOverlayFile()->move($landingPageInfo->getUploadRootDir(), $fileName);
                $landingPageInfo->setOverlayFile(null);
            } else {
                $landingPageInfo->getFile()->move($landingPageInfo->getUploadRootDir(), $fileName);
                $landingPageInfo->setFile(null);
            }
        }
    }

    /**
     * Remove image if image is not assign to any other rule.
     *
     * @param string $file Image file path.
     */
    public function removeImage($file)
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * On submit.
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $form    = $event->getForm();
        $popularSearch = $form->get('popular_search')->getData();

        if ($popularSearch) {
            $oneSelectPopularFlag = true;
            for ($i = 1; $i <=12; $i++) {
                if ($form->get('popular_search_title_'.$i)->getData() || $form->get('popular_search_url_'.$i)->getData()) {
                    $oneSelectPopularFlag = false;
                }
                if ($form->get('popular_search_title_'.$i)->getData() && !$form->get('popular_search_url_'.$i)->getData()) {
                    $form->get('popular_search_url_'.$i)->addError(new FormError('Please enter popular search keyword url.'));
                }
                if (!$form->get('popular_search_title_'.$i)->getData() && $form->get('popular_search_url_'.$i)->getData()) {
                    $form->get('popular_search_title_'.$i)->addError(new FormError('Please enter popular search keyword.'));
                }
                if ($form->get('popular_search_url_'.$i)->getData() && !preg_match("~^(?:ht)tps?://~i", $form->get('popular_search_url_'.$i)->getData())) {
                    $form->get('popular_search_url_'.$i)->addError(new FormError('Please enter valid url with http or https.'));
                }
            }
            if ($oneSelectPopularFlag) {
                $form->get('popular_search')->addError(new FormError('Please enter atleast one popular search keyword and url.'));
            }
        }
    }
}
