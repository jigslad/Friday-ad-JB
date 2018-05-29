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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * AdPostSecondStepType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostSecondStepAnimalsType extends AdPostSecondStepType
{
    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $isRenderCategoryDimension = false;

    /**
     * steps.
     *
     * @var integer
     */
    protected $step = 2;

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_second_step_animals';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_second_step_animals';
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
}
