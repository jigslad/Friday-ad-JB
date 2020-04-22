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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Fa\Bundle\EntityBundle\Repository\LocationGroupRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * PaaSearchKeywordSearchAdminType form
 *
 * @author Jigar Lad <jigar.lad@fridaymediagroup.com>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class PaaSearchKeywordSearchAdminType extends AbstractType
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
        /*$builder
            ->add('category__synonyms_keywords', TextType::class)
            ->add('category__name', TextType::class)
            ->add('search', SubmitType::class);*/
        $builder
        ->add('paa_search_keyword__keyword', TextType::class)
        ->add('category__name', TextType::class)
        ->add('search', SubmitType::class);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_ad_paa_search_keyword_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_paa_search_keyword_search_admin';
    }
}