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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Header image admin search type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolSearchAdminType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            'seo_tool__status',
            ChoiceType::class,
            array(
                'choices' => array_flip(EntityRepository::getStatusArray($this->container))
            )
        )
        ->add(
            'seo_tool__page',
            ChoiceType::class,
            array(
                'placeholder' => 'Select page',
                'choices' => array_flip(SeoToolRepository::getPageArray($this->container, false))
            )
        )
        ->add(
            'seo_tool__no_index',
            ChoiceType::class,
            array(
                'placeholder' => 'Select no index',
                'choices' => array('Yes' => '1', 'No' => '0')
            )
        )
        ->add(
            'seo_tool__no_follow',
            ChoiceType::class,
            array(
                'placeholder' => 'Select no follow',
                'choices' => array('Yes' => '1', 'No' => '0')
            )
        )
        ->add(
            'seo_tool__popular_search',
            ChoiceType::class,
            array(
                'placeholder' => 'Select popular search',
                'choices' => array('Yes' => '1', 'No' => '0')
            )
        )
        ->add('seo_tool_popular_search__title', TextType::class, array('required' => false))
        ->add(
            'seo_tool__canonical_search',
            ChoiceType::class,
            array(
                'placeholder' => 'Select canonical search',
                'choices' => array('Yes' => '1', 'No' => '0')
            )
         )
        ->add('seo_tool__canonical_url', TextType::class, array('required' => false))
        ->add(
            'seo_tool__list_content_search',
            ChoiceType::class,
            array(
                'placeholder' => 'Select content search',
                'choices' => array('Yes' => '1', 'No' => '0')
            )
         )
        ->add('seo_tool__list_content_title_and_detail', TextType::class, array('required' => false))
        ->add(
          'seo_tool__top_link',
          ChoiceType::class,
          array(
            'placeholder' => 'Select top link',
            'choices' => array('Yes' => '1', 'No' => '0')
          )
          )
        ->add('seo_tool_top_link__title', TextType::class, array('required' => false))
        ->add('seo_tool__basic_fields_search', TextType::class, array('required' => false))
        ->add('seo_tool__category__id', HiddenType::class, array('data' => ''))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 1))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4))
        ->add('search', SubmitType::class);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_content_seo_tool_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_seo_tool_search_admin';
    }
}
