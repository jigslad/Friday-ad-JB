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

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\ContentBundle\Entity\SeoTool;
use Fa\Bundle\ContentBundle\Entity\SeoToolPopularSearch;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Entity\SeoToolTopLink;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Seo tool admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolAdminType extends AbstractType
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
        $popularSearchArray = array();
        $topLinkArray = array();
        if ($builder->getData()->getId()) {
            $popularSearchArray = $this->em->getRepository('FaContentBundle:SeoToolPopularSearch')->getPopularSearchArrayBySeoToolId($builder->getData()->getId(), $this->container);
            $topLinkArray = $this->em->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($builder->getData()->getId(), $this->container);
        }

        $builder
             ->add('h1_tag', TextType::class, array('label' => 'H1 Tag', 'required' => false))
            ->add('meta_description', TextType::class, array('label' => 'Meta Description', 'required' => false))
            ->add('image_alt', TextType::class, array('label' => 'Image alt 1', 'required' => false))
            ->add('image_alt_2', TextType::class, array('label' => 'Image alt 2', 'required' => false))
            ->add('image_alt_3', TextType::class, array('label' => 'Image alt 3', 'required' => false))
            ->add('image_alt_4', TextType::class, array('label' => 'Image alt 4', 'required' => false))
            ->add('image_alt_5', TextType::class, array('label' => 'Image alt 5', 'required' => false))
            ->add('image_alt_6', TextType::class, array('label' => 'Image alt 6', 'required' => false))
            ->add('image_alt_7', TextType::class, array('label' => 'Image alt 7', 'required' => false))
            ->add('image_alt_8', TextType::class, array('label' => 'Image alt 8', 'required' => false))
            ->add('meta_keywords', TextType::class, array('label' => 'Meta Keywords', 'required' => false))
            ->add('source_url', TextType::class, array('label' => 'Source URL', 'required' => false))
            ->add('target_url', TextType::class, array('label' => 'Target URL', 'required' => false))
            ->add('page_title', TextType::class, array('label' => 'Page Title', 'required' => false))
            ->add('canonical_url', TextType::class, array('label' => 'Canonical url', 'required' => false))
            ->add('no_index', CheckboxType::class, array('label' => 'No Index', 'required' => false))
            ->add('no_follow', CheckboxType::class, array('label' => 'No Follow', 'required' => false))
            ->add('popular_search', CheckboxType::class, array('label' => 'Popular search', 'required' => false))
            ->add('top_link', CheckboxType::class, array('label' => 'Top links', 'required' => false))
            ->add('list_content_title', TextType::class, array('label' => 'Content title', 'required' => false))
            ->add('list_content_detail', TextareaType::class, array('label' => 'Content detail', 'required' => false, 'attr' => array('rows' => 8, 'cols' => 20, 'class' => 'tinymce')))
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container))
                )
            )
            ->add(
                'page',
                ChoiceType::class,
                array(
                    'choices' => array_flip(SeoToolRepository::getPageArray($this->container, false))
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        //popular search fields
        for ($i = 1; $i <= 7; $i++) {
            $builder->add('popular_search_title_'.$i, TextType::class, array('attr' => array('class' => 'popular_search_inputs'),'required' => false, 'mapped' => false, 'data' => (isset($popularSearchArray[$i-1]) ? $popularSearchArray[$i-1]['title'] : '') ));
            $builder->add('popular_search_url_'.$i, TextType::class, array('required' => false, 'mapped' => false, 'data' => (isset($popularSearchArray[$i-1]) ? $popularSearchArray[$i-1]['url'] : '') ));
        }
        //top link fields
        for ($i = 1; $i <= 20; $i++) {
            $builder->add('top_link_title_'.$i, TextType::class, array('attr' => array('class' => 'top_link_inputs'), 'required' => false, 'mapped' => false, 'data' => (isset($topLinkArray[$i-1]) ? $topLinkArray[$i-1]['title'] : '') ));
            $builder->add('top_link_url_'.$i, TextType::class, array('required' => false, 'mapped' => false, 'data' => (isset($topLinkArray[$i-1]) ? $topLinkArray[$i-1]['url'] : '') ));
        }
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
                $seoRule = $event->getData();
                $form    = $event->getForm();
                if ($seoRule->getId()) {
                    $this->addCategoryField($form, $seoRule);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function ($event) {
                $seoRule = $event->getData();
                $form    = $event->getForm();
                $this->addCategoryField($form, $seoRule);
            }
        );

        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'))
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Add category fields.
     *
     * @param object $form    Form object.
     * @param object $seoRule Seo rule object.
     */
    private function addCategoryField($form, $seoRule)
    {
        $categoryId = null;
        if ($seoRule instanceof SeoTool) {
            if ($seoRule->getCategory()) {
                $categoryId = $seoRule->getCategory()->getId();
            }
        } else {
            $categoryId = $this->getCategoryId($seoRule, true);
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
     * On submit.
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $form    = $event->getForm();
        $seoRule = $event->getData();

        $page       = $form->get('page')->getData();
        $categoryId = $this->getCategoryId($form);
        $popularSearch = $form->get('popular_search')->getData();
        $targetUrl = $form->get('target_url')->getData();
        $topLink       = $form->get('top_link')->getData();

        if ($page) {
            $target_url = $categoryId > 0 ? false : $categoryId;
            $seo = $this->em->getRepository('FaContentBundle:SeoTool')->getSeoPageRule($page, 1, ($seoRule->getId() ? $seoRule->getId() : null), ($categoryId ? $categoryId : null), false, $targetUrl);
            if ($seo) {
                $form->get('page')->addError(new FormError('Active seo data for this page already present.'));
            }
        }
        if ($popularSearch) {
            $oneSelectPopularFlag = true;
            for ($i = 1; $i <=7; $i++) {
                if ($form->get('popular_search_title_'.$i)->getData() || $form->get('popular_search_url_'.$i)->getData()) {
                    $oneSelectPopularFlag = false;
                }
                if ($form->get('popular_search_title_'.$i)->getData() && !$form->get('popular_search_url_'.$i)->getData()) {
                    $form->get('popular_search_url_'.$i)->addError(new FormError('Please enter popular search keyword url.'));
                }
                if (!$form->get('popular_search_title_'.$i)->getData() && $form->get('popular_search_url_'.$i)->getData()) {
                    $form->get('popular_search_title_'.$i)->addError(new FormError('Please enter popular search keyword.'));
                }
            }
            if ($oneSelectPopularFlag) {
                $form->get('popular_search')->addError(new FormError('Please enter atleast one popular search keyword and url.'));
            }
        }

        if ($topLink) {
            $oneSelectTopLinkFlag = true;
            for ($i = 1; $i <=20; $i++) {
                if ($form->get('top_link_title_'.$i)->getData() || $form->get('top_link_url_'.$i)->getData()) {
                    $oneSelectTopLinkFlag = false;
                }
                if ($form->get('top_link_title_'.$i)->getData() && !$form->get('top_link_url_'.$i)->getData()) {
                    $form->get('top_link_url_'.$i)->addError(new FormError('Please enter top link keyword url.'));
                }
                if (!$form->get('top_link_title_'.$i)->getData() && $form->get('top_link_url_'.$i)->getData()) {
                    $form->get('top_link_title_'.$i)->addError(new FormError('Please enter top link keyword.'));
                }
                if ($form->get('top_link_url_'.$i)->getData() && !preg_match("~^(?:ht)tps?://~i", $form->get('top_link_url_'.$i)->getData())) {
                    $form->get('top_link_url_'.$i)->addError(new FormError('Please enter valid url with http or https.'));
                }
            }
            if ($oneSelectTopLinkFlag) {
                $form->get('top_link')->addError(new FormError('Please enter atleast one top link keyword and url.'));
            }
        }
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $seoRule = $event->getData();
        $form    = $event->getForm();
        $popularSearch = $form->get('popular_search')->getData();
        $topLink = $form->get('top_link')->getData();

        if ($form->isValid()) {
            $sourceUrl = $form->get('source_url')->getData();
            if ($sourceUrl) {
                $sourceUrl = str_replace('+', ' ', $sourceUrl);
                $seoRule->setSourceUrl($sourceUrl);
            }
            //remove soe tool popular search
            if ($seoRule->getId()) {
                $this->em->getRepository('FaContentBundle:SeoToolPopularSearch')->removeRecordsBySeoToolId($seoRule->getId());
                $this->em->getRepository('FaContentBundle:SeoToolTopLink')->removeRecordsBySeoToolId($seoRule->getId());
                $culture = CommonManager::getCurrentCulture($this->container);
                CommonManager::removeCache($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:SeoToolPopularSearch')->getTableName().'|getPopularSearchArrayBySeoToolId|'.$seoRule->getId().'_'.$culture);
                CommonManager::removeCache($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:SeoToolTopLink')->getTableName().'|getTopLinkArrayBySeoToolId|'.$seoRule->getId().'_'.$culture);
            }
            $categoryId = $this->getCategoryId($form);
            if ($categoryId > 0) {
                $seoRule->setCategory($this->em->getReference('FaEntityBundle:Category', $categoryId));
            } else {
                $seoRule->setCategory(null);
            }
            //save seo tool popular search
            if ($popularSearch) {
                for ($i = 1; $i <=7; $i++) {
                    if ($form->get('popular_search_title_'.$i)->getData() && $form->get('popular_search_url_'.$i)->getData()) {
                        $seoToolPopularSearch = new SeoToolPopularSearch();
                        $seoToolPopularSearch->setSeoTool($seoRule);
                        $seoToolPopularSearch->setTitle($form->get('popular_search_title_'.$i)->getData());
                        $seoToolPopularSearch->setUrl($form->get('popular_search_url_'.$i)->getData());
                        $this->em->persist($seoToolPopularSearch);
                    }
                }
                $this->em->flush();
            }

            //save seo tool top link
            if ($topLink) {
                for ($i = 1; $i <=20; $i++) {
                    if ($form->get('top_link_title_'.$i)->getData() && $form->get('top_link_url_'.$i)->getData()) {
                        $seoToolTopLink = new SeoToolTopLink();
                        $seoToolTopLink->setSeoTool($seoRule);
                        $seoToolTopLink->setTitle($form->get('top_link_title_'.$i)->getData());
                        $seoToolTopLink->setUrl($form->get('top_link_url_'.$i)->getData());
                        $this->em->persist($seoToolTopLink);
                    }
                }
                $this->em->flush();
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
                'data_class' => 'Fa\Bundle\ContentBundle\Entity\SeoTool'
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
        return 'fa_content_seo_tool_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_seo_tool_admin';
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
            $target_url = $form->get('target_url')->getData();
        } else {
            $category1  = isset($form['category_1']) ? $form['category_1'] : null;
            $category2  = isset($form['category_2']) ? $form['category_2'] : null;
            $category3  = isset($form['category_3']) ? $form['category_3'] : null;
            $category4  = isset($form['category_4']) ? $form['category_4'] : null;
            $target_url = isset($form['target_url']) ? $form['target_url'] : null;
        }

        if ($category4) {
            $categoryId = $category4;
        } elseif ($category3) {
            $categoryId = $category3;
        } elseif ($category2) {
            $categoryId = $category2;
        } elseif ($category1) {
            $categoryId = $category1;
        } elseif ($target_url) {
            $categoryId = $target_url;
        }


        return $categoryId;
    }
}
