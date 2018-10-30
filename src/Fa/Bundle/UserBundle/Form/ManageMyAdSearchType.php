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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * This form is used to search user's own ads.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ManageMyAdSearchType extends AbstractType
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
        $builder
             ->add('ad__id', TextType::class, array('required' => false))
             ->add('ad__title', TextType::class, array('required' => false))
             ->add(
                 'entity_ad_type__id',
                 EntityType::class,
                 array(
                    'class' => 'FaEntityBundle:Entity',
                    'choice_label' => 'name',
                    'placeholder' => 'Ad type',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder(EntityRepository::ALIAS)
                        ->where(EntityRepository::ALIAS.'.category_dimension = '.EntityRepository::AD_TYPE_ID)
                        ->orderBy(EntityRepository::ALIAS.'.name', 'ASC');
                    }
                 )
             )
             ->add(
                 'entity_ad_status__id',
                 EntityType::class,
                 array(
                    'class' => 'FaEntityBundle:Entity',
                    'choice_label' => 'name',
                    'placeholder' => 'Ad status',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder(EntityRepository::ALIAS)
                        ->where(EntityRepository::ALIAS.'.category_dimension = '.EntityRepository::AD_STATUS_ID)
                        ->orderBy(EntityRepository::ALIAS.'.name', 'ASC');
                    }
                 )
             )
             ->add('search', SubmitType::class);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_my_item_search';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_my_item_search';
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     *
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false
        ));
    }
}
