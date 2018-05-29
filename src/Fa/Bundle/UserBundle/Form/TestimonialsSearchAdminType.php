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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is testimonials search form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class TestimonialsSearchAdminType extends AbstractType
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
     * @param ContainerInterface $container object.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('testimonials__user', TextType::class, array('required' => false))
        ->add('testimonials__user_name', TextType::class, array('required' => false))
        ->add('testimonials__user_email', TextType::class, array('required' => false))
        ->add(
            'testimonials__status',
            ChoiceType::class,
            array(
                'choices' => array_flip(EntityRepository::getStatusArray1($this->container))
            )
        )
        ->add('search', SubmitType::class);
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_testimonials_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_testimonials_search_admin';
    }
}
