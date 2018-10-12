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
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * This is rolr form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class RoleType extends AbstractType
{
    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'constraints' => array(
                        new NotBlank(array('message' => 'Please enter name.')),
                     )
                    )
            )
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'placeholder' => 'Select Role Type',
                    'choices'  => array_flip(array('A' => 'Admin', 'C' => 'Customer', 'P' => 'Internal Use')),
                    'constraints' => array(
                        new NotBlank(array('message' => 'Please select role type.')),
                    )
                )
            )
            ->add('created_at')
            ->add('updated_at');
        ;
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\UserBundle\Entity\Role'
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_bundle_userbundle_role';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_bundle_userbundle_role';
    }
}
