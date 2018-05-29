<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\LexikTranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * TransUnit form type.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitType extends AbstractType
{
    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param Array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'key',
            TextType::class,
            array(
                'label'    => 'Key',
                'required' => true,
            )
        );

        $builder->add(
            'domain',
            ChoiceType::class,
            array(
                'label'    => 'Domain',
                'choices'  => array_combine($options['domains'], $options['domains']),
                'required' => true,
            )
        );

        $builder->add(
            'translations',
            CollectionType::class,
            array(
                'type'     => 'lxk_translation',
                'label'    => 'Translations',
                'required' => false,
                'options'  =>
                array(
                    'data_class' => $options['translation_class'],
                )
            )
        );

        $builder->add(
            'save',
            SubmitType::class
        );

        $builder->add(
            'save_add',
            SubmitType::class,
            array(
                'label' => 'Save and new',
            )
        );
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
                'data_class'         => null,
                'domains'            => array('messages'),
                'translation_class'  => null,
                'translation_domain' => 'LexikTranslationBundle'
            )
        );
    }

    /**
     * Get name.
     */
    public function getName()
    {
        return 'lxk_trans_unit';
    }
    
    public function getBlockPrefix()
    {
        return 'lxk_trans_unit';
    }
}
