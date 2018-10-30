<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\AdBundle\Repository\CampaignsRepository;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * CampaignAdminSearchType form.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class CampaignsAdminSearchType extends AbstractType
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
        $builder
        ->add('campaigns__campaignName', TextType::class, array(
            'required' => false
        ))
        ->add('campaigns__pageTitle', TextType::class, array(
            'required' => false
        ))
        /*->add('campaigns__category__id', 'entity', array(
                'class' => 'FaEntityBundle:Category',
                'empty_value' => "Select Category",
                'choice_label' => 'name',
                'query_builder' => function (CategoryRepository $cr) {
                    return $cr->createQueryBuilder(CategoryRepository::ALIAS)
                    ->where(CategoryRepository::ALIAS.'.status = 1')
                    ->andWhere(CategoryRepository::ALIAS.'.lvl in (1,2,3,4)')
                    ->orderBy(CategoryRepository::ALIAS.'.lvl', 'ASC');
                }
        ))*/
        ->add('category__id', HiddenType::class, array('data' => ''))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 1))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4))
        ->add('campaigns__campaignStatus', ChoiceType::class, array(
            'required' => false,
            'choices' => array(
                'Active' => 1,
                'In-Active' => 2),
            'placeholder'=>'Search By Status'))
        
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
        return 'fa_ad_campaigns_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_campaigns_search_admin';
    }
}
