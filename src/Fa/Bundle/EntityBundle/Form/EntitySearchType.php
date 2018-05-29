<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Entity search type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class EntitySearchType extends AbstractType
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
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->entityManager = $options['em'];

        $builder
        ->add('entity__name', TextType::class, array('required' => false))
        ->add(
            'category_dimension__id',
            ChoiceType::class,
            array(
                'choices' => array_flip($this->entityManager->getRepository('FaEntityBundle:CategoryDimension')->getDimesionByCategoryArray()),
            )
        )
        ->add('search', SubmitType::class, array('label' => 'Search'));
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
                'data_class' => null
            )
        )
        ->setRequired(
            array(
                'em'
            )
        )
        ->setAllowedTypes('em', array('null', 'string', 'Doctrine\Common\Persistence\ObjectManager'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'entity_admin_search';
    }
    
    public function getBlockPrefix()
    {
        return 'entity_admin_search';
    }
}
