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

use Fa\Bundle\AdBundle\Form\AdPostSecondStepType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * AdPostSecondStepType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostSecondStepPropertyType extends AdPostSecondStepType
{
    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $isRenderCategoryDimension = false;

    /**
     * Need to show field in which step in posting
     *
     * @var integer
     */
    protected $step = 2;

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

        $this->addCategroyPaaFieldsForm($form, $firstStepData['category_id'], $ad);

        $secondStepData = array_merge($this->orderedFields, $this->getSecondStepFields());

        $form->add('second_step_ordered_fields', HiddenType::class, array('data' => implode(',', $secondStepData)));
    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad   = $event->getData();
        $form = $event->getForm();

        $this->validatePrice($form);
        $this->validateDescription($form);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_second_step_property';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_second_step_property';
    }

    /**
     * Get step data to render on template.
     *
     * @return array
     */
    public function getSecondStepFields()
    {
        return array(
                   //'save',
               );
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

        if (in_array($paaField['field'], array('rent_per_id'))) {
            $fieldOptions['choices'] = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, false));
        }

        if (in_array($paaField['field'], array('ad_type_id'))) {
            $fieldOptions['choices'] = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, true, 'ord'));
        }

        if ($defaultData) {
            $fieldOptions['data'] = $defaultData;
        }

        return $fieldOptions;
    }

    /**
     * Validate price.
     *
     * @param object $form Form instance.
     */
    protected function validatePrice($form)
    {
        if ($form->has('price')) {
            if ($form->get('price')->getData() == '') {
                $form->get('price')->addError(new FormError('Value should not be blank.'));
            } else {
                if (!preg_match('/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $form->get('price')->getData(), $matches)) {
                    $form->get('price')->addError(new FormError('Price is invalid.'));
                }
            }
        }
    }
}
