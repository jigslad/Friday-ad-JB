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
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType as EntType;

/**
 * Entity type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class EntityType extends AbstractType
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
            ->add(
                'category_dimension',
                EntType::class,
                array(
                    'class' => 'FaEntityBundle:CategoryDimension',
                    'choice_label' => 'name',
                    'query_builder' => function (CategoryDimensionRepository $er) {
                        $qb = $er->createQueryBuilder(CategoryDimensionRepository::ALIAS);
                        $qb->where($qb->expr()->isNull(CategoryDimensionRepository::ALIAS.'.category'));
                        return $qb;
                    }
                )
            )
            ->add('name');
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
                'data_class' => 'Fa\Bundle\EntityBundle\Entity\Entity',
                'validation_groups' => array('shared_entity'),
            )
        )
        ->setRequired(
            array(
                'em',
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
        return 'entity_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'entity_admin';
    }
}
