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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * PaaFieldAdminType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class PaaFieldAdminType extends AbstractType
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
     * Paa field rule object.
     *
     * @var object
     */
    private $paaFieldRule;

    /**
     * Paa field object.
     *
     * @var object
     */
    private $paaField;

    /**
     * Default ord in case of new rule.
     *
     * @var integer
     */
    private $defaultOrd;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param string             $paaFieldRule
     * @param string             $paaField
     * @param string             $defaultOrd
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container    = $container;
        $this->em           = $this->container->get('doctrine')->getManager();
        /* $this->paaFieldRule = $paaFieldRule;
        $this->paaField     = ($paaField ? $paaField : $this->paaFieldRule->getPaaField());
        $this->defaultOrd   = $defaultOrd; */
        $this->translator   = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->paaFieldRule = $options['paaFieldRule'];
        $this->paaField = $options['paaField'] ? $options['paaField'] : $this->paaFieldRule->getPaaField();
        $this->defaultOrd = $options['defaultOrd'];
        
        $builder
            ->add(
                'label',
                TextType::class,
                array(
                    'data'        => ($this->paaFieldRule && $this->paaFieldRule->getLabel()) ? $this->paaFieldRule->getLabel() : $this->paaField->getLabel(),
                    'required'    => true,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Label can not be empty.', array(), 'validators')))
                    )
                )
            )
            ->add(
                'placeholder_text',
                TextType::class,
                array(
                    'data'        => (($this->paaFieldRule && $this->paaFieldRule->getPlaceholderText()) ? $this->paaFieldRule->getPlaceholderText() : null),
                    'required'    => false,
                )
            )
            ->add('status', CheckboxType::class, array('data' => ($this->paaFieldRule && $this->paaFieldRule->getStatus()) ? true : false))
            ->add('is_required', CheckboxType::class, array('data' => ($this->paaFieldRule && $this->paaFieldRule->getIsRequired()) ? true : false))
            ->add('is_recommended', CheckboxType::class, array('data' => ($this->paaFieldRule && $this->paaFieldRule->getIsRecommended()) ? true : false))
            ->add('help_text', TextareaType::class, array('data' => ($this->paaFieldRule && $this->paaFieldRule->getHelpText()) ? $this->paaFieldRule->getHelpText() : ''))
            ->add('error_text', TextareaType::class, array('data' => ($this->paaFieldRule && $this->paaFieldRule->getErrorText()) ? $this->paaFieldRule->getErrorText() : ''))
            ->add(
                'ord',
                TextType::class,
                array(
                    'data' => ($this->paaFieldRule && $this->paaFieldRule->getOrd()) ? $this->paaFieldRule->getOrd() : $this->defaultOrd,
                    'required' => true,
                    'constraints' => array(
                                         new Regex(array('pattern' => '/^[0-9]+$/i', 'message' => $this->translator->trans('Display order is not a valid integer.', array(), 'validators'))),
                                         new NotBlank(array('message' => $this->translator->trans('Display order can not be empty.', array(), 'validators')))
                                     )
                )
            )
            ->add(
                'min_value',
                TextType::class,
                array(
                    'data' => ($this->paaFieldRule && $this->paaFieldRule->getMinValue()) ? $this->paaFieldRule->getMinValue() : '',
                    'constraints' => array(new Regex(array('pattern' => '/^[0-9]+(?:\.[0-9]{2})?$/', 'message' => $this->translator->trans('Minimum value is not a valid number.', array(), 'validators'))))
                )
            )
            ->add(
                'max_value',
                TextType::class,
                array(
                    'data' => ($this->paaFieldRule && $this->paaFieldRule->getMaxValue()) ? $this->paaFieldRule->getMaxValue() : '',
                    'constraints' => array(new Regex(array('pattern' => '/^[0-9]+(?:\.[0-9]{2})?$/', 'message' => $this->translator->trans('Maximum value is not a valid number.', array(), 'validators'))))
                )
            )
            ->add(
                'step',
                TextType::class,
                array(
                    'data' => ($this->paaFieldRule && $this->paaFieldRule->getStep()) ? $this->paaFieldRule->getStep() : '',
                    'constraints' => array(new Regex(array('pattern' => '/^[0-9]+$/i', 'message' => $this->translator->trans('Stage should be integer.', array(), 'validators'))))
                )
            )
            ->add('is_added', HiddenType::class);

//        if(($this->paaField->getField() != 'photo_error') && ($this->paaField->getField() != 'youtube_video_url') && ($this->paaField->getField() != 'location')){
//            $builder->add('hide_field', CheckboxType::class, array('label' => 'Collapse field by default', 'data' => ($this->paaFieldRule && $this->paaFieldRule->getHideField()) ? true : false));
//        }else{
//            $builder->add('hide_field', HiddenType::class, array('label' => false, 'data' => ($this->paaFieldRule && $this->paaFieldRule->getHideField()) ? true : false));
//        }

        // allow admin to set default value
        if ($this->paaField->getCategoryDimensionId() && ($this->paaField->getFieldType() == 'choice_radio' || $this->paaField->getFieldType() == 'choice_single')) {
            $fieldOptions['choices']     = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($this->paaField->getCategoryDimensionId(), $this->container));
            $fieldOptions['placeholder'] = 'Select';
            $fieldOptions['data']        = ($this->paaFieldRule && $this->paaFieldRule->getDefaultValue()) ? $this->paaFieldRule->getDefaultValue() : '';
            $builder->add('default_value', ChoiceType::class, $fieldOptions);
        } elseif ($this->paaField->getFieldType() == 'choice_boolean') {
            $fieldOptions['choices']     = array_flip(array(1 => 'Yes', 0 => 'No'));
            $fieldOptions['placeholder'] = 'Select';
            $fieldOptions['data']        = ($this->paaFieldRule && $this->paaFieldRule->getDefaultValue() != null) ? $this->paaFieldRule->getDefaultValue() : '';
            $builder->add('default_value', ChoiceType::class, $fieldOptions);
        } else {
            $builder->add('default_value', TextType::class, array('data' => ($this->paaFieldRule && $this->paaFieldRule->getDefaultValue()) ? $this->paaFieldRule->getDefaultValue() : ''));
        }
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
                'paaFieldRule' => null,
                'paaField' => null,
                'defaultOrd' => null
            )
            );
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_ad_paa_field_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_paa_field_admin';
    }
}
