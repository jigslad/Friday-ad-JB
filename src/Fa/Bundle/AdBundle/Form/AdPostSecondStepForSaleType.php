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
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * AdPostSecondStepForSaleType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdPostSecondStepForSaleType extends AdPostSecondStepType
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
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_second_step_for_sale';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_second_step_for_sale';
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
                'translation_domain' => 'frontend-paa-second-step',
            )
        );
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
     * Validate price.
     *
     * @param object $form Form instance.
     */
    protected function validatePrice($form)
    {
        if ($form->has('ad_type_id') && $form->get('ad_type_id')->getData()) {
            if ($form->get('ad_type_id')->getData() == EntityRepository::AD_TYPE_SWAPPING_ID) {
                if ($form->has('price_text') && $form->get('price_text')->getData() == '') {
                    $form->get('price_text')->addError(new FormError('Value should not be blank.'));
                }
            } else {
                if ($form->has('price') && $form->get('ad_type_id')->getData() != EntityRepository::AD_TYPE_FREETOCOLLECTOR_ID) {
                    if ($form->get('price')->getData() == '') {
                        if ($form->get('ad_type_id')->getData() != EntityRepository::AD_TYPE_WANTED_ID) {
                            $form->get('price')->addError(new FormError('Value should not be blank.'));
                        }
                    } else {
                        if (!preg_match('/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $form->get('price')->getData(), $matches)) {
                            $form->get('price')->addError(new FormError('Price is invalid.'));
                        }
                    }
                }
            }
        }
    }
}
