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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Fa\Bundle\EntityBundle\Repository\LocationGroupRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Entity\PrintEditionRule;
use Fa\Bundle\AdBundle\Entity\PrintEdition;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * PrintEditionAdminType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class PrintEditionAdminType extends AbstractType
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
            ->add('name', TextType::class)
            ->add('code', TextType::class)
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container)),
                )
            )
            ->add(
                'deadline_day_of_week',
                ChoiceType::class,
                array(
                    'choices' => array_flip(CommonManager::getDaysOfWeekArray($this->container)),
                    'placeholder' => 'Select day of week',
                    'label'       => 'Deadline day of week',
                )
            )
            ->add(
                'deadline_time_of_day',
                ChoiceType::class,
                array(
                    'choices' => array_flip(CommonManager::getTimeWithIntervalArray(15)),
                    'placeholder' => 'Select time',
                    'label'       => 'Deadline time',
                )
            )
            ->add(
                'insert_date_day_of_week',
                ChoiceType::class,
                array(
                    'choices' => array_flip(CommonManager::getDaysOfWeekArray($this->container)),
                    'placeholder' => 'Select day of week',
                    'label'       => 'Insert date day of week',
                )
            )
            ->add(
                'insert_date_time_of_day',
                ChoiceType::class,
                array(
                    'choices' => array_flip(CommonManager::getTimeWithIntervalArray(15)),
                    'placeholder' => 'Select time',
                    'label'       => 'Insert date time',
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
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
                'data_class' => 'Fa\Bundle\AdBundle\Entity\PrintEdition'
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
        return 'fa_ad_print_edition_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_print_edition_admin';
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $selectedLocationGroupIds  = array();
        $printEditionRules         = $data->getPrintEditionRules();
        foreach ($printEditionRules as $printEditionRule) {
            if ($locationGroup = $printEditionRule->getLocationGroup()) {
                $selectedLocationGroupIds[] = $locationGroup->getId();
            }
        }

        $this->addLocationGroupField($form, $selectedLocationGroupIds);
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->isValid()) {
            $this->beforeSave($form);
        }
    }

    /**
     * Save header image.
     *
     * @param object $form Form object.
     */
    public function beforeSave($form)
    {
        $printEdition     = $form->getData();
        $locationGroupIds = $form->get('location_group')->getData();
        $status           = $form->get('status')->getData();
        $name             = $form->get('name')->getData();

        // Remove print edition rules before add new rules each time
        $printEditionRules = $printEdition->getPrintEditionRules();
        foreach ($printEditionRules as $printEditionRule) {
            $this->em->remove($printEditionRule);
            $this->em->flush();
        }

        // Add rule with location groups if selected otherwise add one blank rule
        if (!empty($locationGroupIds)) {
            foreach ($locationGroupIds as $locationGroupId) {
                $locationGroup = $this->em->getRepository('FaEntityBundle:LocationGroup')->find($locationGroupId);
                if ($locationGroup) {
                    $printEditionRule = new PrintEditionRule();
                    $printEditionRule->setPrintEdition($printEdition);
                    $printEditionRule->setLocationGroup($locationGroup);
                    $printEdition->addPrintEditionRule($printEditionRule);
                }
            }
        } else {
            $printEditionRule = new PrintEditionRule();
            $printEditionRule->setPrintEdition($printEdition);
            $printEdition->addPrintEditionRule($printEditionRule);
        }
    }

    /**
     * Add location group field to form.
     *
     * @param object $form                     Form object.
     * @param mixed  $selectedLocationGroupIds Selected location group ids.
     */
    private function addLocationGroupField($form, $selectedLocationGroupIds = null)
    {
        $fieldOptions = array(
            'choices'     => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getLocationGroupsKeyValueArray()),
            'placeholder' => 'Select Location Group',
            'label'       => 'Location Group',
            'multiple'    => true,
            'mapped'      => false,
            'required'    => false,
            'data'        => $selectedLocationGroupIds,
            'attr'        => array('field-help' => 'Use "CTRL" key for select / deselect multiple options.'),
        );

        $form->add('location_group', ChoiceType::class, $fieldOptions);
    }
}
