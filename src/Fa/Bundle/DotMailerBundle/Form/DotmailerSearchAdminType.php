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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Dotmailer search type form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerSearchAdminType extends AbstractType
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
        ->add(
            'dotmailer__opt_in',
            CheckboxType::class,
            array(
                'label'    => 'Opted in',
                'data'     => true,
                'attr'     => array('disabled' => true),
                'required' => false
            )
        )
        ->add(
            'dotmailer__fad_user',
            CheckboxType::class,
            array(
                'label'    => 'FAD',
                'required' => false
            )
        )
        ->add(
            'dotmailer__ti_user',
            CheckboxType::class,
            array(
                'label'    => 'TI',
                'required' => false
            )
        )
        ->add(
            'dotmailer__dotmailer_newsletter_type_id',
            ChoiceType::class,
            array(
                /** @Ignore */
                'label'    => false,
                'expanded' => true,
                'multiple' => true,
                'choices'  => array_flip($this->em->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getKeyValueArray($this->container)),
            )
        )
        ->add(
            'dotmailer__role_id',
            ChoiceType::class,
            array(
                'label'       => 'User type',
                'placeholder' => 'Any',
                'required'    => false,
                'choices'     => (array_flip($this->em->getRepository('FaUserBundle:Role')->getUserTypes()) + array('Other' => '-1')),
            )
        )
        ->add(
            'dotmailer__print_edition_id',
            ChoiceType::class,
            array(
                'label'       => 'Print edition',
                'placeholder' => 'Select',
                'required'    => false,
                'choices'     => array('Any area' => 'any-area', 'Any print area' => 'all-print-area', 'Non print area' => 'non-print-area') + array_flip($this->em->getRepository('FaAdBundle:PrintEdition')->getActivePrintEditionArray())
            )
        )
        ->add(
            'dotmailer__business_category_id',
            ChoiceType::class,
            array(
                'required'    => false,
                'label' => 'Business category',
                'placeholder' => 'Select business category',
                'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1)),
                'choice_translation_domain' => false,
            )
        )
        ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'dotmailer_info__paa_category_id', 'dotmailer_info__paa_category_id_json', 'FaEntityBundle:Category'))
        ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'dotmailer_info__enquiry_category_id', 'dotmailer_info__enquiry_category_id_json', 'FaEntityBundle:Category'))
        ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'dotmailer__town_id', 'dotmailer__town_id_json', 'FaEntityBundle:Location'))
        ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'dotmailer__enquiry_town_id', 'dotmailer__enquiry_town_id_json', 'FaEntityBundle:Location'))
        ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'dotmailer__county_id', 'dotmailer__county_id_json', 'FaEntityBundle:Location'))
        ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'dotmailer__enquiry_county_id', 'dotmailer__enquiry_county_id_json', 'FaEntityBundle:Location'))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer_info__paa_created_at_from', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer_info__paa_created_at_to', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer_info__enquiry_created_at_from', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer_info__enquiry_created_at_to', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer__last_paid_at_from', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer__last_paid_at_to', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer__last_paa_at_from', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer__last_paa_at_to', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer__last_enquiry_at_from', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer__last_enquiry_at_to', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer__newsletter_signup_at_from', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('dotmailer__newsletter_signup_at_to', array(/** @Ignore */ 'label' => false, 'attr' => array('placeholder' => 'dd/mm/yyyy', 'class' => 'fdatepicker', 'autocomplete' => 'off'))))

        ->add('search', SubmitType::class, array('label' => 'Search for emails'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_dotmailer_dotmailer_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_dotmailer_dotmailer_search_admin';
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
