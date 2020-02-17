<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;

/**
 * Upsell search admin type form.
 *
 * @author Chaitra Bhat <chaitra.bhat@fridaymediagroup.com>
 * @copyright 2017 Friday Media Group Ltd
 * @version 1.0
 */
class CategoryUpsellSearchAdminType extends AbstractType
{

    /**
     * Entity manager class object.
     *
     * @var object
     */
    protected $entityManager;

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
        $this->entityManager = $this->container->get('doctrine')->getManager();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*$builder->add('category__id', EntityType::class, array(
            'class' => 'FaEntityBundle:Category',
            'choice_label' => 'name',
            'placeholder' => 'Category',
            'query_builder' => function (CategoryRepository $er) {
            return $er->createQueryBuilder(CategoryRepository::ALIAS)
            ->where(CategoryRepository::ALIAS . '.lvl = 1')
            ->andWhere(CategoryRepository::ALIAS . '.status = 1')
            ->orderBy(CategoryRepository::ALIAS . '.name', 'ASC');
            }
         ));
        */
        
        $builder->add('category__id', HiddenType::class, array('data' => ''))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 1))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4));
        
         $builder->add('upsell__id', EntityType::class, array(
             'class' => 'FaPromotionBundle:Upsell',
             'choice_label' => 'title',
             'placeholder' => 'All Upsells',
             'query_builder' => function (UpsellRepository $er) {
             return $er->createQueryBuilder(UpsellRepository::ALIAS)
             ->where(UpsellRepository::ALIAS . '.status = 1')
             ->andWhere(UpsellRepository::ALIAS . '.type = '.UpsellRepository::UPSELL_TYPE_TOP_ADVERT_ID)
             ->orderBy(UpsellRepository::ALIAS . '.title', 'ASC');
             }
         ))
         ->add('search', SubmitType::class, array(
            'label' => 'Search'
        ));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_promotion_category_upsell_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_category_upsell_search_admin';
    }
}
