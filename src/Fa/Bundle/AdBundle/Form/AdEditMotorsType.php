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
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Form\AdEditType;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;

/**
 * AdEditMotorsType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class AdEditMotorsType extends AdEditType
{
    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad   = $this->ad;
        $form = $event->getForm();

        $this->validateRegNo($form);
        $this->validatePrice($form);
        $this->validateAdLocation($form);
        $this->validateDescription($form);
        $this->validateBusinessAdField($form);
        $this->validateYoutubeField($form);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_edit_motors';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_edit_motors';
    }

    /**
     * (non-PHPdoc)
     * @see \Fa\Bundle\AdBundle\Form\AdPostAdminType::getRootCategoryId()
     *
     * @return integer
     */
    protected function getRootCategoryId()
    {
        return CategoryRepository::MOTORS_ID;
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
            } elseif (in_array($paaField['field'], array('reg_year', 'year_built'))) {
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

        if (in_array($paaField['field'], array('tonnage_id', 'berth_id', 'condition_id', 'number_of_stalls_id'))) {
            $fieldOptions['choices']     = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, false));
            $fieldOptions['placeholder'] = 'Select '.$paaField['label'];
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
     * Validate reg no.
     *
     * @param object $form Form instance.
     */
    protected function validateRegNo($form)
    {
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
