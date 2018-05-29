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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\ContentBundle\Repository\BannerRepository;
use Fa\Bundle\ContentBundle\Repository\BannerZoneRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\ContentBundle\Entity\Banner;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


/**
 * Banner admin type form.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class BannerAdminType extends AbstractType
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
        ->add(
            'banner_zone',
            EntityType::class,
            array(
                'class'         => 'FaContentBundle:BannerZone',
                'choice_label'      => 'name',
                'label'         => 'Zone',
                'placeholder'   => 'Select zone',
            )
        )
        ->add('banner_pages', ChoiceType::class, array('placeholder' => 'Select pages', 'multiple' => true, 'mapped' => false))
        ->add('code', TextareaType::class, array('required' => true, 'attr' => array('cols' => '15', 'rows' => '8')))
        ->add('save', SubmitType::class)
        ->add('saveAndNew', SubmitType::class);

        //category fields
        $builder->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                1,
                'category'
            )
        )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                2,
                'category'
            )
        )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                3,
                'category'
            )
        )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                4,
                'category'
            )
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function ($event) {
                $banner = $event->getData();
                $form    = $event->getForm();
                if ($banner->getId()) {
                    $this->addCategoryField($form, $banner);
                }
            }
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'))
        ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Add category fields.
     *
     * @param object $form    Form object.
     * @param object $banner  Banner object.
     */
    private function addCategoryField($form, $banner)
    {
        $categoryId = null;
        if ($banner instanceof Banner) {
            if ($banner->getCategory()) {
                $categoryId = $banner->getCategory()->getId();
            }
        } else {
            $categoryId = $this->getCategoryId($banner, true);
        }
        //for category
        if ($categoryId > 0) {
            $categoryPath     = array_keys($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, true, $this->container));
            $categoryPathTemp = $categoryPath;

            if (count($categoryPath) > 5) {
                end($categoryPath);
                $categoryPath[4] = $categoryPath[key($categoryPath)];
            } elseif (count($categoryPath) < 5) {
                $categoryPath[count($categoryPath)+1] = $categoryPath[count($categoryPath)-1];
            }
            $categoryPath = array_slice($categoryPath, 0, 5);

            for ($i=1; $i < count($categoryPath); $i++) {
                $choices = array('' => 'Select Category '.$i) + ($i == 4 ? $this->em->getRepository('FaEntityBundle:Category')->getNestedChildrenKeyValueArrayByParentId($categoryPathTemp[$i-1]) :$this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($categoryPathTemp[$i-1]));
                $choices = $this->em->getRepository('FaEntityBundle:Category')->showDuplicateCategoriesForSubscriber($choices);
                $form->add(
                    'category_'.$i,
                    ChoiceType::class,
                    array(
                        'required' => false,
                        'mapped'   => false,
                        'choices'  => array_flip($choices),
                        'data'     => isset($categoryPath[$i]) ? $categoryPath[$i] : null,
                    )
                );
            }
        }
    }

    /**
     * This function is called on pre submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $bannerPagesArray = array();

        if ($data['banner_zone']) {
            $zoneId           = (int) trim($data['banner_zone']);
            $objBannerZone    = $this->em->getRepository('FaContentBundle:BannerZone')->find($zoneId);
            $objBannerPages   = $objBannerZone->getBannerPages();

            foreach ($objBannerPages as $objBannerPage) {
                $bannerPagesArray[$objBannerPage->getId()] = $objBannerPage->getName();
            }
        }
        $this->addCategoryField($form, $data);

        $form->add('banner_pages', ChoiceType::class, array('placeholder' => 'Select pages', 'choices' => array_flip($bannerPagesArray), 'multiple' => true, 'mapped' => false));
        $event->setData($data);
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $this->postValidation($event);
    }

    /**
     * Post submit.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPostSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        //Remove existing banner pages while updating
        if ($data->getId()) {
            $objBannerPages = $data->getBannerPages();
            if ($objBannerPages) {
                foreach ($objBannerPages as $objBannerPage) {
                    $data->removeBannerPage($objBannerPage);
                }
            }
        }

        //Add banner pages while adding or updating.
        $bannerPages = $form->get('banner_pages')->getData();
        if ($form->isValid()) {
            foreach ($bannerPages as $key => $pageId) {
                $data->addBannerPage($this->em->getReference('FaContentBundle:BannerPage', $pageId));
            }
            $categoryId = $this->getCategoryId($form);
            if ($categoryId > 0) {
                $data->setCategory($this->em->getReference('FaEntityBundle:Category', $categoryId));
            } else {
                $data->setCategory(null);
            }
        }
    }

    /**
     * Add location field validation.
     *
     * @param object $form Form instance.
     */
    protected function postValidation(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $categoryId = $this->getCategoryId($form);
        $objBannerZone = $form->get('banner_zone')->getData();
        $banner_pages  = $form->get('banner_pages')->getData();
        $isValid       = true;

        if (count($banner_pages) <= 0) {
            $isValid = false;
            $form->get('banner_pages')->addError(new FormError('Banner pages are required..'));
        } else {
            $objBanners = $this->em->getRepository('FaContentBundle:Banner')->findBy(array('banner_zone' => $objBannerZone->getId(), 'category' => $categoryId));
            if ($objBanners) {
                $notAvaliablePageIdsArray   = array();
                $notAvaliablePageNamesArray = array();
                foreach ($objBanners as $objBanner) {
                    if ($objBanner->getId() != $data->getId()) {
                        $objBannerPages = $objBanner->getBannerPages();
                        if ($objBannerPages) {
                            $existingPageIds = array();
                            foreach ($objBannerPages as $objBannerPage) {
                                $existingPageIds[] = $objBannerPage->getId();
                            }
                            foreach ($banner_pages as $key => $pageId) {
                                if (in_array($pageId, $existingPageIds)) {
                                    if (!in_array($pageId, $notAvaliablePageIdsArray)) {
                                        $notAvaliablePageIdsArray[] = $pageId;
                                    }
                                }
                            }
                        }
                    }
                }
                if (count($notAvaliablePageIdsArray) > 0) {
                    foreach ($notAvaliablePageIdsArray as $pageId) {
                        $notAvaliablePageNamesArray[] = $this->em->getRepository('FaContentBundle:BannerPage')->findOneById($pageId);
                    }
                    $pageNames = implode(', ', $notAvaliablePageNamesArray);
                    $form->get('banner_pages')->addError(new FormError('Sorry few banner page(s) are already define with other banner ('.$pageNames.').'));
                }
            }
        }

        return $isValid;
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
                'data_class' => 'Fa\Bundle\ContentBundle\Entity\Banner',
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
        return 'fa_content_banner_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_banner_admin';
    }

    /**
     * Get category id based on posted form data.
     *
     * @param object  $form        Form instance.
     * @param boolean $isArrayFlag Array flag.
     *
     * @return integer
     */
    private function getCategoryId($form, $isArrayFlag = false)
    {
        $categoryId = null;
        if (!$isArrayFlag) {
            $category1  = $form->get('category_1')->getData();
            $category2  = $form->get('category_2')->getData();
            $category3  = $form->get('category_3')->getData();
            $category4  = $form->get('category_4')->getData();
        } else {
            $category1  = isset($form['category_1']) ? $form['category_1'] : null;
            $category2  = isset($form['category_2']) ? $form['category_2'] : null;
            $category3  = isset($form['category_3']) ? $form['category_3'] : null;
            $category4  = isset($form['category_4']) ? $form['category_4'] : null;
        }

        if ($category4) {
            $categoryId = $category4;
        } elseif ($category3) {
            $categoryId = $category3;
        } elseif ($category2) {
            $categoryId = $category2;
        } elseif ($category1) {
            $categoryId = $category1;
        }

        return $categoryId;
    }
}
