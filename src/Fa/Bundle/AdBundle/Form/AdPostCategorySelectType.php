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
use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * AdPostCategorySelectType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostCategorySelectType extends AdPostSecondStepType
{
    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $categoryId = null;

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (isset($options['data']['categoryId']) && $options['data']['categoryId']) {
            $this->categoryId = $options['data']['categoryId'];
        }
        $builder
        ->add('category_id', HiddenType::class)
        ->add('category_id_autocomplete', TextType::class, array(/** @Ignore */ 'label' => false))
        ->add('save', SubmitType::class, array('label' => 'Next step: describe your item'))
        ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'))
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $this->addCategoryChoiceFields($builder);
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $ad            = $event->getData();
        $form          = $event->getForm();
        $firstStepData = $this->getAdPostStepData('first');
        $categoryId    = ($this->categoryId ? $this->categoryId : (($this->request->get('is_edit') && isset($firstStepData['category_id']) && $firstStepData['category_id']) ? $firstStepData['category_id'] : null));
        $categoryIds   = array_keys($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container));
        $rootCategoryId = (isset($categoryIds[0]) ? $categoryIds[0] : null);
        $secondLevelCategoryId = (isset($categoryIds[1]) ? $categoryIds[1] : null);
        $regNoFieldCategoryIds = $this->em->getRepository('FaAdBundle:PaaFieldRule')->getRegNoFieldCategoryIds($this->container);


        if ($rootCategoryId == CategoryRepository::MOTORS_ID && $categoryId && in_array($secondLevelCategoryId, $regNoFieldCategoryIds)) {
            $this->addCategroyPaaFieldsForm($form, $categoryId, null);

            foreach ($form->all() as $field) {
                if (!in_array($field->getName(), $this->getMotorRegNoFields())) {
                    $form->remove($field->getName());
                }
            }

            $firstStepData = array_merge($this->orderedFields, $this->getMotorRegNoFields());
            $firstStepData = array_unique($firstStepData);

            $form->add('first_step_ordered_fields', HiddenType::class, array('data' => implode(',', $firstStepData)));
        } else {
            $form->add('first_step_ordered_fields', HiddenType::class, array('data' => null));
        }
    }

    /**
     * Get form field options.
     *
     * @param array  $paaFieldRule PAA field rule array
     * @param object $ad           Ad instance
     * @param object $verticalObj  Vertical instance
     */
    protected function getPaaFieldOptions($paaFieldRule, $ad = null, $verticalObj = null)
    {
        $paaField     = $paaFieldRule['paa_field'];
        $fieldOptions = parent::getPaaFieldOptions($paaFieldRule, $ad, $verticalObj);

        $defaultData = null;
        if (isset($fieldOptions['data']) && $fieldOptions['data']) {
            $defaultData = $fieldOptions['data'];
        }

        if ($this->getPaaFieldType($paaField) == 'range') {
            if (in_array($paaField['field'], array('mot_expiry_year', 'road_tax_expiry_year'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getYearChoices());
                $fieldOptions['placeholder'] = 'Year';
            } elseif (in_array($paaField['field'], array('reg_year'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getRegYearChoices());
                $fieldOptions['placeholder'] = 'Year';
            } elseif (in_array($paaField['field'], array('mot_expiry_month', 'road_tax_expiry_month'))) {
                $fieldOptions['choices']     = array_flip(CommonManager::getMonthChoices());
                $fieldOptions['placeholder'] = 'Month';
            }
        }

        if (in_array($paaField['field'], array('no_of_doors', 'no_of_seats'))) {
            $fieldOptions['attr']['class'] = isset($fieldOptions['attr']['class']) ? $fieldOptions['attr']['class'].' door-no' : 'door-no';
        }

        if (in_array($paaField['field'], array('reg_year'))) {
            $fieldOptions['attr']['class'] = isset($fieldOptions['attr']['class']) ? $fieldOptions['attr']['class'].' reg-year' : 'reg-year';
        }

        if (in_array($paaField['field'], array('fuel_type_id'))) {
            $fuelTypeChoices = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, true);
            $petrolKey       = array_search('Petrol', $fuelTypeChoices);

            if ($petrolKey !== false) {
                unset($fuelTypeChoices[$petrolKey]);
                $fuelTypeChoices = array($petrolKey => 'Petrol') + $fuelTypeChoices;
            }

            $fieldOptions['choices'] = array_flip($fuelTypeChoices);
        }

        if ($defaultData) {
            $fieldOptions['data'] = $defaultData;
        }

        return $fieldOptions;
    }

    /**
     * Add category choice field.
     *
     * @param object $builder
     */
    private function addCategoryChoiceFields($builder)
    {
        $totalLevel = $this->em->getRepository('FaEntityBundle:Category')->getMaxLevel($this->container);

        if ($totalLevel) {
            for ($i = 1; $i <= $totalLevel; $i++) {
                if ($i == 1) {
                    $optionArray = array(
                        'placeholder' =>  'Please select category',
                        'attr'        => array('class' => 'select-control category category_'.$i),
                    );
                } else {
                    $optionArray = array(
                        'placeholder' => 'Please select subcategory',
                        'attr'        => array('class' => 'select-control category category_'.$i),
                    );
                }
                $builder->addEventSubscriber(
                    new AddCategoryChoiceFieldSubscriber(
                        $this->container,
                        $i,
                        'category',
                        $optionArray,
                        null,
                        $totalLevel,
                        true
                    )
                );
            }
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
                'data_class' => null,
                'translation_domain' => 'frontend-paa-first-step',
            )
        );
    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $form        = $event->getForm();
        $categoryId  = $form->get('category_id')->getData() ? $form->get('category_id')->getData() : null;
        $category    = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $categoryId));

        if ($category) {
            $hasChildren = $this->em->getRepository('FaEntityBundle:Category')->hasChildren($category->getId(), $this->container);
            if ($hasChildren === true) {
                $form->get('category_id_autocomplete')->addError(new FormError('Please search advert category or choose from all categories.'));
            }
        } else {
            $form->get('category_id_autocomplete')->addError(new FormError('Please search advert category or choose from all categories.'));
        }

        $this->validateRegNo($form);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_category_select';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_category_select';
    }

    /**
     * Get step data to render on template.
     *
     * @return array
     */
    public function getMotorRegNoFields()
    {
        return array(
            'category_id',
            'category_id_autocomplete',
            'has_reg_no',
            'reg_no',
            'colour_id',
            'body_type_id',
            'reg_year',
            'fuel_type_id',
            'transmission_id',
            'engine_size',
            'no_of_doors',
            'no_of_seats',
            'fuel_economy',
            '062mph',
            'top_speed',
            'ncap_rating',
            'co2_emissions',
            'colour_id_autocomplete',
            'colour_id_dimension_id',
            'save',
            'first_step_ordered_fields',
        );
    }

    /**
     * Validate reg no.
     *
     * @param object $form Form instance.
     */
    protected function validateRegNo($form)
    {
        /*if ($form->has('has_reg_no') && $form->get('has_reg_no')->getData() === null) {
            $form->get('has_reg_no')->addError(new FormError('Please select has registration number.'));
        }*/
        if ($form->has('has_reg_no') && $form->get('has_reg_no')->getData() == 1) {
            if ($form->get('reg_no')->getData()) {
                $carWebData = $this->container->get('fa.webcar.manager')->findByVRM($form->get('reg_no')->getData());
                if (isset($carWebData['error'])) {
                    $form->get('reg_no')->addError(new FormError('Please enter correct registration number.'));
                }
            } else {
                $form->get('reg_no')->addError(new FormError('Please enter registration number.'));
            }
        }
    }
}
