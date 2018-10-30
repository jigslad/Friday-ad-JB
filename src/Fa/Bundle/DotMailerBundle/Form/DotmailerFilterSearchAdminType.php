<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerFilterRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Dotmailer search type form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerFilterSearchAdminType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager class object.
     *
     * @var object
     */
    protected $em;

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
        ->add('dotmailer_filter__name', TextType::class, array('required' => false))
        ->add(
          'dotmailer_filter__status',
          ChoiceType::class,
           array(
             'placeholder' => 'Dotmailer filter status',
             'choices'     => array_flip(DotmailerFilterRepository::getStatusArray($this->container)),
           )
          )
        ->add(
          'dotmailer_filter__is_24h_loop',
          ChoiceType::class,
           array(
             'multiple' => true,
             'expanded' => true,
             'mapped'   => false,
             'choices'  => array('Normal filter' => '0', 'Repeat every 24h filter' => '1')
           )
          )
        ->add(
          'dotmailer_filter__created_by',
          EntityType::class,
          array(
            'class' => 'FaDotMailerBundle:DotmailerFilter',
            'choice_label' => 'created_by',
            'choice_value' => 'created_by',
            'placeholder'  => 'Created by',
            'query_builder' => function (DotmailerFilterRepository $er) {
                return $er->createQueryBuilder(DotmailerFilterRepository::ALIAS)
                   ->orderBy(DotmailerFilterRepository::ALIAS.'.created_by', 'ASC')
                   ->groupBy(DotmailerFilterRepository::ALIAS.'.created_by');
            }
            )
          )
        ->add('search', SubmitType::class, array('label' => 'Search'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_dotmailer_dotmailer_filter_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_dotmailer_dotmailer_filter_search_admin';
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
            'csrf_protection' => false,
            'csrf_field_name' => '_token',
        ));
    }
}
