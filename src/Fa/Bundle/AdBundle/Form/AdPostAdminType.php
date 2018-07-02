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
use Symfony\Component\Validator\Constraints\Range;

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\PaaFieldRuleRepository;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Fa\Bundle\CoreBundle\Form\Type\JsChoiceType;

/**
 * AdPostAdminType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

abstract class AdPostAdminType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    protected $container;

    /**
     * Entity manager.
     *
     * @var object
     */
    protected $em;

    /**
     * The request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * Ordered fields to display in template in order.
     *
     * @var array
     */
    protected $orderedFields = array();

    /**
     * Disabled PAA fields by PAA rule.
     *
     * @var array
     */
    protected $disabledFields = array();

    /**
     * Need to render category wise dimension in any step.
     *
     * @var boolean
     */
    protected $isRenderCategoryDimension = true;

    /**
     * This contains array of data which is in moderation.
     *
     * @var boolean
     */
    protected $moderationValue = array();

    /**
     * Translator.
     *
     * @var object
     */
    protected $translator;

    /**
     * Constructor.
     *
     * @param object       $container    Container instance.
     * @param RequestStack $requestStack RequestStack instance.
     */
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->request    = $requestStack->getCurrentRequest();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_paa_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_paa_admin';
    }

    /**
     * Add category wise paa fields
     *
     * @param object  $form       Form instance.
     * @param integer $categoryId Selected category id.
     * @param string  $ad
     */
    protected function addCategroyPaaFieldsForm($form, $categoryId = null, $ad = null)
    {
        if ($categoryId) {
            // Edit ad
            $verticalObj   = $this->getVerticalObject($categoryId, $ad);

            if ($verticalObj && count($this->moderationValue) > 0 && isset($this->moderationValue['dimensions'][0])) {
                $verticalObj = $this->getVerticalRepository()->setObjectFromModerationData($this->moderationValue['dimensions'][0]);
            }

            $paaFieldRules = $this->em->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryAncestor($categoryId, $this->container, null, 'both');

            if (count($paaFieldRules)) {
                // First: if field is defined in PAA field rules of parent category.
                foreach ($paaFieldRules as $paaFieldRule) {
                    $paaField = $paaFieldRule['paa_field'];
                    // show only active fields from rule
                    if ($paaFieldRule['status']) {
                        if ($paaField['field'] == 'location') {
                            $this->addLocationAutoSuggestField($form, $ad, $paaFieldRule);
                        } else if ($this->getPaaFieldType($paaField) == 'autosuggest') {
                            $this->addAutoSuggestField($form, $paaField['label'], $paaField['category_dimension_id'], $this->getPaaFieldOptions($paaFieldRule, $categoryId, $ad, $verticalObj), $verticalObj);
                        } elseif ($this->getPaaFieldType($paaField) == 'datepicker') {
                            $this->addDatePickerField($form, $paaField['category_dimension_id'], $paaField['label'], $verticalObj, $paaFieldRule['label'], $paaFieldRule);
                        } else if ($paaField['field'] == 'model_id') {
                            $form->add($paaField['field'], JsChoiceType::class, $this->getPaaFieldOptions($paaFieldRule, $categoryId, $ad, $verticalObj));
                            $this->addOrderedField($paaField['field']);
                        } else {
                            if( $paaField['field'] != 'rates_id') {
                                $form->add($paaField['field'], $this->getFormFieldType($paaField, TRUE), $this->getPaaFieldOptions($paaFieldRule, $categoryId, $ad, $verticalObj));
                                $this->addOrderedField($paaField['field']);
                            } else {
                                $form->add($paaField['field'], HiddenType::class, array('mapped' => false, 'data' => true, 'label'=>'Rates'));
                                $this->addRatesFields($form, $paaField, $verticalObj);
                                $this->addOrderedField($paaField['field']);
                            }
                        }
                    } else {
                        // Store disbaled fields to check for adding fields from remaining fields from category dimensions
                        $this->addDisabledField($paaField['field']);
                    }
                }

                // Second : Add Remaining category wise dimension fields which are not in rule by default
                if ($this->isRenderCategoryDimension) {
                    $this->addCategroyDimensionFieldsForm($form, $categoryId, $ad, $verticalObj);
                }
            }
        }

        $form->add('admin_ad_counter', HiddenType::class, array('mapped' => false, 'data' => $this->request->get('admin_ad_counter')));
    }
    
    /**
     * Get form Rates fields.
     *
     * @param object $form
     */
    protected function addRatesFields($form, $paaField, $verticalObj)
    {
        $entitySortBy = 'id';
        $data = [];
        $category_dimension_id = $paaField['category_dimension_id'];
        $metaData = $this->getField('meta_data', $verticalObj) ? unserialize($this->getField('meta_data', $verticalObj)) : null;
        $ratesData = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($category_dimension_id, $this->container, true, $entitySortBy, 'textCollection');
        
        if( !empty ($ratesData) ) {
            foreach ($ratesData as $rate=>$val) {
                $label = explode('_', $val);
                
                if ( isset($metaData[$paaField['field']][$label[1]][$rate]) ) {
                    $data = trim($metaData[$paaField['field']][$label[1]][$rate]);
                } else {
                    $data = [];
                }
                
                $fieldConstraints = new Regex(array(
                    'pattern' => '/^[\d,\.]+$/',
                    'message' => $label[0]. ' is invalid.'
                ));
                
                
                $form->add(str_replace(' ', '', $val), TextType::class, array('mapped' => false, 'label' => $label[0], 'data'=>$data, 'constraints' => $fieldConstraints, 'required'=>false));
            }
        }
        return true;
    }

    /**
     * Get form field type.
     *
     * @param array $paaField PAA field array.
     */
    protected function getFormFieldType($paaField = array(), $classFlag = FALSE)
    {
        if ($paaField['field_type'] == 'text_int' || $paaField['field_type'] == 'text_float') {
            return NumberType::class;
        }

        $fieldTypeArray = explode('_', $paaField['field_type']);
        
        if ($classFlag) {
            $formTypeArray = ['choice' => ChoiceType::class, 'text' => TextType::class, 'textarea' => TextareaType::class, 'integer' => NumberType::class];
            $formTypeName = isset($formTypeArray[$fieldTypeArray[0]]) ? $formTypeArray[$fieldTypeArray[0]] : $fieldTypeArray[0];
        }else {
            $formTypeName = $fieldTypeArray[0];
        }
        
        return $formTypeName;
    }

    /**
     * Get form field type.
     *
     * @param array $paaField PAA field array.
     */
    protected function getPaaFieldType($paaField = array())
    {
        $fieldTypeArray = explode('_', $paaField['field_type']);

        return (isset($fieldTypeArray[1]) && $fieldTypeArray[1]) ? $fieldTypeArray[1] : null;
    }

    /**
     * Get form field options.
     *
     * @param array   $paaFieldRule PAA field rule array.
     * @param integer $categoryId   Category Id.
     * @param object  $ad           Ad instance.
     * @param object  $verticalObj  Vertical instance.
     */
    protected function getPaaFieldOptions($paaFieldRule, $categoryId, $ad = null, $verticalObj = null)
    {
        $paaField         = $paaFieldRule['paa_field'];
        $fieldType        = $this->getFormFieldType($paaField);
        $fieldOptions     = array();
        $fieldConstraints = array();

        $fieldOptions['mapped'] = false;

        if ($paaFieldRule['label']) {
            $fieldOptions['label'] = $paaFieldRule['label'];
        }

        if ($paaFieldRule['help_text']) {
            $fieldOptions['attr']['field-help'] = $paaFieldRule['help_text'];
        }

        if ($paaFieldRule['placeholder_text']) {
            $fieldOptions['attr']['placeholder'] = $paaFieldRule['placeholder_text'];
        }

        if ($paaFieldRule['is_required']) {
            if ($paaField['field'] == 'price' || $paaField['field'] == 'price_text') {
                $fieldOptions['required'] = false;
            } else {
                $fieldOptions['required'] = true;
                $fieldConstraints[]       = new NotBlank(array('message' => $paaFieldRule['error_text'] ? $paaFieldRule['error_text'] : $this->translator->trans('Value should not be blank.', array(), 'validators')));
            }
        } else {
            $fieldOptions['required'] = false;
        }

        if ($paaFieldRule['min_value'] || $paaFieldRule['max_value']) {
            $lengthOptions = array();

            if ($paaFieldRule['min_value']) {
                $lengthOptions['min'] = $paaFieldRule['min_value'];
            }
            if ($paaFieldRule['max_value']) {
                $lengthOptions['max']              = $paaFieldRule['max_value'];

                if ($paaFieldRule['min_max_type'] == PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH) {
                    $fieldOptions['attr']['maxlength'] = $paaFieldRule['max_value'];
                    $fieldOptions['attr']['class'] = isset($fieldOptions['attr']['class']) ? $fieldOptions['attr']['class'].' textcounter' : 'textcounter';
                }
            }

            if ($paaFieldRule['min_max_type'] == PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH) {
                if ($this->getPaaFieldType($paaField) != 'tinymce') {
                    $fieldConstraints[] = new Length($lengthOptions);
                }
            } else if ($paaFieldRule['min_max_type'] == PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE) {
                if ($paaFieldRule['error_text'] && $paaField['field'] == 'photo_error') {
                    $lengthOptions['minMessage'] = $paaFieldRule['error_text'];
                    $lengthOptions['maxMessage'] = $paaFieldRule['error_text'];
                }

                $fieldConstraints[] = new Range($lengthOptions);
            }
        }

        if ($this->getPaaFieldType($paaField) == 'int') {
            $fieldConstraints[] = new Regex(array('pattern' => '/^[\d,]+$/', 'message' => $this->translator->trans('%paa_field% is invalid.', array('%paa_field%' => $paaField['label']), 'validators')));
        }

        if ($this->getPaaFieldType($paaField) == 'float') {
            if ($paaField['field'] != 'price') {
                $fieldConstraints[] = new Regex(array('pattern' => '/^[\d,\.]+$/', 'message' => $this->translator->trans('%paa_field% is invalid.', array('%paa_field%' => $paaField['label']), 'validators')));
            }
        }

        if (count($fieldConstraints)) {
            $fieldOptions['constraints'] = $fieldConstraints;
        }

        if ($this->getPaaFieldType($paaField) == 'tinymce') {
            $fieldOptions['attr']['class'] = isset($fieldOptions['attr']['class']) ? $fieldOptions['attr']['class'].' tinymce' : 'tinymce';
            $fieldOptions['attr']['rows']  = '5';
        }

        // category dimension
        if ($paaField['category_dimension_id'] && $fieldType == 'choice') {
            if ($this->getPaaFieldType($paaField) != 'range') {
                $entitySortBy = 'name';
                if (in_array($paaField['field'], array('salary_band_id', 'travel_arrangements_id', 'independent_or_agency_id'))) {
                    $entitySortBy = 'id';
                }
                $fieldOptions['choices'] = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($paaField['category_dimension_id'], $this->container, true, $entitySortBy));
                if ($paaField['field'] == 'travel_arrangements_id') {
                    $fieldOptions['choices'] = array_flip($this->em->getRepository('FaEntityBundle:Entity')->customFormatOptions($fieldOptions['choices'], 'paa'));
                }
                $fieldOptions['placeholder'] = 'Select '.$paaField['label'];
            }
        }

        if ($this->getPaaFieldType($paaField) == 'radio' || $this->getPaaFieldType($paaField) == 'boolean') {
            $fieldOptions['expanded']    = true;
            $fieldOptions['multiple']    = false;
            $fieldOptions['placeholder'] = false;
        } elseif ($this->getPaaFieldType($paaField) == 'checkbox') {
            $fieldOptions['expanded'] = true;
            $fieldOptions['multiple'] = true;
        } elseif ($this->getPaaFieldType($paaField) == 'single' || $this->getPaaFieldType($paaField) == 'range') {
            $fieldOptions['expanded'] = false;
            $fieldOptions['multiple'] = false;
        } elseif ($this->getPaaFieldType($paaField) == 'multiple') {
            $fieldOptions['expanded'] = false;
            $fieldOptions['multiple'] = true;
        }

        if ($paaField['field'] == 'is_new') {
            $fieldOptions['choices']  = array_flip($this->em->getRepository('FaEntityBundle:Entity')->getIsNewOptionsArray($this->container));
            $fieldOptions['data']     = '0';
        } elseif ($this->getPaaFieldType($paaField) == 'boolean') {
            $fieldOptions['choices']     = array('Yes' => '1', 'No' => '0');
            $fieldOptions['data']        = '0';
            $fieldOptions['placeholder'] = false;
        } elseif ($paaField['field'] == 'ad_type_id') {
            $fieldOptions['data'] = EntityRepository::AD_TYPE_FORSALE_ID;
        } elseif ($paaField['field'] == 'delivery_method_option_id') {
            $fieldOptions['choices'] = array_flip($this->em->getRepository('FaPaymentBundle:DeliveryMethodOption')->getDeliveryMethodOptionArray($this->container));
            $fieldOptions['data']    = DeliveryMethodOptionRepository::COLLECTION_ONLY_ID;
        } elseif ($paaField['field'] == 'payment_method_id') {
            $fieldOptions['choices'] = array_flip($this->em->getRepository('FaPaymentBundle:Payment')->getPaymentMethodOptionsArray($this->container));
            $fieldOptions['data']    = PaymentRepository::PAYMENT_METHOD_CASH_ON_COLLECTION_ID;
        }

        if ($paaFieldRule['default_value']) {
            $fieldOptions['data'] = $paaFieldRule['default_value'];
        }

        // Edit ad
        if ($ad && $ad->getId()) {
            if (in_array($paaField['field'], $this->getVerticalFields())) {
                if (in_array($paaField['field'], $this->getNotIndexedVerticalFields())) {
                    $metaData = $this->getField('meta_data', $verticalObj) ? unserialize($this->getField('meta_data', $verticalObj)) : null;
                    if ($metaData && isset($metaData[$paaField['field']])) {
                        $fieldData = explode(',', $metaData[$paaField['field']]);
                        if ($this->getPaaFieldType($paaField) == 'checkbox' || $this->getPaaFieldType($paaField) == 'multiple') {
                            $fieldOptions['data'] = $fieldData;
                        } else {
                            if ($fieldType == 'number') {
                                if ($metaData[$paaField['field']] != null) {
                                    $fieldOptions['data'] = trim($metaData[$paaField['field']]);
                                }
                            } else {
                                $fieldOptions['data'] = $metaData[$paaField['field']];
                            }
                        }
                    }
                } else {
                    $fieldData = explode(',', $this->getField($paaField['field'], $verticalObj));
                    if ($this->getPaaFieldType($paaField) == 'checkbox' || $this->getPaaFieldType($paaField) == 'multiple') {
                        $fieldOptions['data'] = $fieldData;
                    } else {
                        if ($fieldType == 'number') {
                            if ($this->getField($paaField['field'], $verticalObj) != null) {
                                $fieldOptions['data'] = trim($this->getField($paaField['field'], $verticalObj));
                            }
                        } else {
                            $fieldOptions['data'] = $this->getField($paaField['field'], $verticalObj);
                        }
                    }
                }
            } else {
                if ($paaField['field'] == 'ad_type_id') {
                    $fieldOptions['data'] = $ad->getType() ? $ad->getType()->getId(): null;
                } else {
                    if ($fieldType == 'number') {
                        if ($this->getField($paaField['field'], $ad) != null) {
                            $fieldOptions['data'] = trim($this->getField($paaField['field'], $ad));
                        }
                    } else {
                        $fieldOptions['data'] = $this->getField($paaField['field'], $ad);
                    }
                }
            }
        }

        // Set trim
        if ($this->getFormFieldType($paaField) == 'text' || $this->getFormFieldType($paaField) == 'textarea') {
            $fieldOptions['trim'] = true;
        }

        //for image uploaded count.
        if ($paaField['field'] == 'photo_error') {
            if ($ad && $ad->getId()) {
                $adId = $ad->getId();
            } else {
                $adId = $this->container->get('session')->get('ad_id');
            }

            $adImgCount = $this->em->getRepository('FaAdBundle:AdImage')->getAdImageCount($adId);
            $fieldOptions['data'] = $adImgCount;
            $fieldOptions['attr']['max_value'] = $paaFieldRule['max_value'];
            $fieldOptions['attr']['min_value'] = $paaFieldRule['min_value'];
        }

        return $fieldOptions;
    }

    /**
     * Add auto-suggest dimension fields.
     *
     * @param object  $form            Form instance.
     * @param string  $fieldName       Field name.
     * @param integer $dimensionId     Dimension id.
     * @param array   $paaFieldOptions Paa field options.
     * @param object  $verticalObj     Vertical instance.
     *
     */
    protected function addAutoSuggestField($form, $fieldName, $dimensionId = null, $paaFieldOptions = array(), $verticalObj = null)
    {
        $freeTextfield = str_replace(' ', '_', strtolower($fieldName));
        if ($freeTextfield == 'event_start_time' || $freeTextfield == 'event_end_time') {
            $field = str_replace(' ', '_', strtolower($fieldName));
        } else {
            $field = str_replace(' ', '_', strtolower($fieldName)).'_id';
        }
        if (!in_array($field, $this->orderedFields) && !in_array($field, $this->disabledFields) && !in_array($field.'_autocomplete', $this->orderedFields) && !in_array($field.'_autocomplete', $this->disabledFields)) {
            // Edit ad
            $selectedData = null;
            if ($verticalObj) {
                $metaData = $this->getField('meta_data', $verticalObj) ? unserialize($this->getField('meta_data', $verticalObj)) : null;
                if (in_array($freeTextfield, $this->getNotIndexedVerticalFields()) && ($metaData && isset($metaData[$freeTextfield]))) {
                    $selectedData = $metaData[$freeTextfield];
                } elseif (in_array($field, $this->getNotIndexedVerticalFields()) && ($metaData && isset($metaData[$field]))) {
                    $selectedData = $metaData[$field];
                } else {
                    $selectedData = $this->getField($field, $verticalObj);
                }
            }

            // autocomplete hidden field for value
            $form->add($field, HiddenType::class, array('mapped' => false, 'data' => $selectedData));

            // autocomplete text field
            $fieldOptions = array(
                                'required' => false,
                                'mapped'   => false,
                                /** @Ignore */
                                'label'    => $fieldName,
                            );

            $fieldOptions = array_merge($fieldOptions, $paaFieldOptions);

            if ($selectedData) {
                $entity = $this->em->getRepository('FaEntityBundle:Entity')->findOneBy(array('id' => $selectedData));

                if ($entity && !in_array($field, array('event_start_time', 'event_end_time'))) {
                    $fieldOptions['data'] = $entity->getName();
                } else {
                    $fieldOptions['data'] = $selectedData;
                }
            }

            $form->add($field.'_autocomplete', TextType::class, $fieldOptions);
            $this->addOrderedField($field.'_autocomplete');

            // To store category dimension for auto-suggest field to fetch options from entity table
            if ($dimensionId) {
                $form->add($field.'_dimension_id', HiddenType::class, array('data' => $dimensionId, 'mapped' => false));
            }
        }
    }

    /**
     * Add category dimension fields.
     *
     * @param object  $form          Form instance.
     * @param integer $dimensionId   Dimension id.
     * @param string  $dimensionName Dimension name.
     * @param object  $verticalObj   Vertical instance.
     * @param string  $fieldLabel    Field label.
     */
    protected function addDatePickerField($form, $dimensionId, $dimensionName, $verticalObj = null, $fieldLabel = null, $paaFieldRule = null)
    {
        $dimensionField = str_replace(' ', '_', strtolower($dimensionName));
        if (!in_array($dimensionField, $this->orderedFields) && !in_array($dimensionField, $this->disabledFields)) {
            $fieldOptions   = array(
                                  'required' => false,
                                   /** @Ignore */
                                  'label'    => $fieldLabel,
                                  'mapped'   => false,
                                  'attr'     => array(
                                                    'class' => 'fdatepicker',
                                                    'autocomplete' => 'off',
                                                ),
            );

            if (isset($paaFieldRule['placeholder_text']) && $paaFieldRule['placeholder_text']) {
                $fieldOptions['attr']['placeholder'] = $paaFieldRule['placeholder_text'];
            }

            // Edit ad
            if ($verticalObj) {
                if (in_array($dimensionField, $this->getNotIndexedVerticalFields())) {
                    $metaData = $this->getField('meta_data', $verticalObj) ? unserialize($this->getField('meta_data', $verticalObj)) : null;
                    if ($metaData && isset($metaData[$dimensionField])) {
                        $fieldOptions['data'] = (is_numeric($metaData[$dimensionField]) ? (date('d/m/Y', $metaData[$dimensionField])) : $metaData[$dimensionField]);
                    }
                } else {
                    $date = $this->getField($dimensionField, $verticalObj);
                    $fieldOptions['data'] = (is_numeric($date) ? (date('d/m/Y', $date)) : $date);
                }
            }

            $form->add($dimensionField, TextType::class, $fieldOptions);
            $this->addOrderedField($dimensionField);
        }
    }

    /**
     * Add category dimension fields.
     *
     * @param object  $form           Form instance.
     * @param integer $dimensionId    Dimension id.
     * @param string  $dimensionName  Dimension name.
     * @param object  $verticalObj    Vertical instance.
     */
    protected function addCategroyDimensionTextNumberField($form, $dimensionField, $dimensionLabel, $verticalObj = null)
    {
        if (!in_array($dimensionField, $this->orderedFields) && !in_array($dimensionField, $this->disabledFields)) {
            $fieldOptions = array(
                                'required'    => false,
                                'mapped'      => false,
                                /** @Ignore */
                                'label'       => $dimensionLabel,
                                'constraints' => array(new Regex(array('pattern' => '/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', 'message' => $this->translator->trans('%dimensionLabel% is invalid.', array('dimensionLabel' => $dimensionLabel), 'validators')))),
                            );

            // Edit ad
            if ($verticalObj) {
                if (in_array($dimensionField, $this->getNotIndexedVerticalFields())) {
                    $metaData = $this->getField('meta_data', $verticalObj) ? unserialize($this->getField('meta_data', $verticalObj)) : null;
                    if ($metaData && isset($metaData[$dimensionField])) {
                        $fieldOptions['data'] = $metaData[$dimensionField];
                    }
                } else {
                    $fieldOptions['data'] = $this->getField($dimensionField, $verticalObj);
                }
            }

            $form->add($dimensionField, TextType::class, $fieldOptions);
            $this->addOrderedField($dimensionField);
        }
    }

    /**
     * Add category dimension fields.
     *
     * @param object  $form           Form instance.
     * @param integer $dimensionId    Dimension id.
     * @param string  $dimensionName  Dimension name.
     * @param object  $verticalObj    Vertical instance.
     */
    protected function addCategroyDimensionChoiceListField($form, $dimensionId, $dimensionName, $verticalObj = null)
    {
        $dimensionField = str_replace(array('(', ')'), '', strtolower($dimensionName));
        $dimensionField = str_replace(' ', '_', strtolower($dimensionField)).'_id';

        if (!in_array($dimensionField, $this->orderedFields) && !in_array($dimensionField, $this->disabledFields)) {
            $withSort = true;
            if (in_array($dimensionField, array('leg_id', 'waist_id', 'neck_id', 'size_id', 'age_range_id', 'age_id'))) {
                $withSort = false;
            }

            $fieldChoices = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($dimensionId, $this->container, $withSort);
            $fieldOptions = array(
                                'required'    => false,
                                'mapped'      => false,
                                'expanded'    => false,
                                'multiple'    => false,
                                /** @Ignore */
                                'placeholder' => $this->container->get('translator')->trans('Select %dimensionName%', array('%dimensionName%' => $dimensionName)),
                                /** @Ignore */
                                'label'       => $dimensionName,
                                'choices'     => $fieldChoices,
                            );

            // Edit ad
            if ($verticalObj) {
                if (in_array($dimensionField, $this->getNotIndexedVerticalFields())) {
                    $metaData = $this->getField('meta_data', $verticalObj) ? unserialize($this->getField('meta_data', $verticalObj)) : null;
                    if ($metaData && isset($metaData[$dimensionField])) {
                        $fieldOptions['data'] = $metaData[$dimensionField];
                    }
                } else {
                    $fieldOptions['data'] = $this->getField($dimensionField, $verticalObj);
                }
            }

            if (count($fieldChoices)) {
                $form->add($dimensionField, ChoiceType::class, $fieldOptions);
                $this->addOrderedField($dimensionField);
            }
        }
    }

    /**
     * Set field data.
     *
     * @param string $field    Field name.
     * @param string $fieldVal Field value.
     * @param object $object   Instance.
     */
    protected function setField($field, $fieldVal, $object)
    {
        $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        if (method_exists($object, $methodName) === true) {
            call_user_func(array($object, $methodName), $fieldVal);
        }
    }

    /**
     * Set field data.
     *
     * @param string $field  Field name.
     * @param object $object Instance.
     *
     * @return array
     */
    protected function getField($field, $object)
    {
        $fieldVal   = null;
        $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        if (method_exists($object, $methodName) === true) {
            $fieldVal = call_user_func(array($object, $methodName));
        }

        return $fieldVal;
    }

    /**
     *  Add location field.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instacne.
     */
    protected function addLocationField($form, $ad = null)
    {
        $fieldOptions = array(
                            'mapped'      => false,
                            'label'       => 'Location',
                            'attr'        =>  array('field-help' => 'Postcode or Town'),
                            'constraints' => new NotBlank(array('message' => $this->translator->trans('Location should not be blank.', array(), 'validators'))),
        );

        // Edit
        if ($ad && $ad->getId()) {
            if ($ad->getAdLocations() && count($ad->getAdLocations())) {
                foreach ($ad->getAdLocations() as $adLocation) {
                    if ($adLocation->getPostCode()) {
                        $fieldOptions['data'] = $adLocation->getPostCode();
                    } elseif ($adLocation->getLocationTown()) {
                        $fieldOptions['data'] = $adLocation->getLocationTown()->getName();
                    }
                }
            }
        } else {
            // Add: user postcode or town as default selection
            $user = $this->em->getRepository('FaUserBundle:User')->find($this->request->get('user_id', null));
            $defaultLocation = null;
            if ($user) {
                if ($user->getZip()) {
                    $defaultLocation = $user->getZip();
                } elseif ($user->getLocationTown()) {
                    $defaultLocation = $user->getLocationTown()->getName();
                } elseif ($user->getTown()) {
                    $defaultLocation = $user->getTown();
                }

                $fieldOptions['data'] = $defaultLocation;
            }
        }

        $form->add('location', TextType::class, $fieldOptions);
    }

    /**
     *  Add auto-suggest location.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instacne.
     */
    protected function addLocationAutoSuggestField($form, $ad = null, $paaFieldRule = null)
    {
        $locationId   = null;
        $locationText = null;
        $areaText = null;
        $areaId = null;

        // Edit ad
        if ($ad && $ad->getId()) {
            if ($ad->getAdLocations() && count($ad->getAdLocations())) {
                foreach ($ad->getAdLocations() as $key => $adLocation) {
                    if (count($this->moderationValue) > 0 && isset($this->moderationValue['locations'][$key])) {
                        $adLocation = $this->em->getRepository('FaAdBundle:AdLocation')->setObjectFromModerationData($this->moderationValue['locations'][$key], $ad->getId());
                    }
                    
                    if($adLocation->getLocationArea()) {
                        $getArea = $this->em->getRepository('FaEntityBundle:Location')->find($adLocation->getLocationArea()->getId());
                        $areaId = $getArea->getId();
                        $areaText =  $getArea->getName().', '.$getArea->getParent()->getName();
                    }

                    if ($adLocation->getPostCode()) {
                        $locationId   = $adLocation->getPostCode();
                        $locationText = $adLocation->getPostCode();
                    } elseif ($adLocation->getLocality()) {
                        $locationId   = $adLocation->getLocality()->getId().','.$adLocation->getLocationTown()->getId();
                        $locationText = $adLocation->getLocality()->getName().', '.$adLocation->getLocationTown()->getName();
                    } elseif ($adLocation->getLocationTown()) {
                        $locationId   = $adLocation->getLocationTown()->getId();
                        $locationText = $adLocation->getLocationTown()->getName();
                        if ($adLocation->getLocationTown()->getParent() && $adLocation->getLocationTown()->getParent()->getName()) {
                            $locationText .=', '.$adLocation->getLocationTown()->getParent()->getName();
                        }
                    }
                }
            }
        } else {
            // Add: user postcode or town as default selection
            $user = $this->em->getRepository('FaUserBundle:User')->find($this->request->get('user_id', null));
            if ($user) {
                if ($user->getZip()) {
                    $locationId   = $user->getZip();
                    $locationText = $user->getZip();
                } elseif ($user->getLocationTown()) {
                    $locationId   = $user->getLocationTown()->getId();
                    $locationText = $user->getLocationTown()->getName();
                    if ($user->getLocationTown()->getParent() && $user->getLocationTown()->getParent()->getName()) {
                        $locationText .=', '.$user->getLocationTown()->getParent()->getName();
                    }
                }
            }
        }

        // autocomplete hidden field for value
        $form->add('location', HiddenType::class, array('mapped' => false, 'data' => $locationId));

        // autocomplete text field
        $fieldOptions = array(
                            'mapped'      => false,
                            'label'       => 'Location',
                            'constraints' => new NotBlank(array('message' => $this->translator->trans('Location should not be blank.', array(), 'validators'))),
                            'data'        => $locationText,
                        );

        if (isset($paaFieldRule['placeholder_text']) && $paaFieldRule['placeholder_text']) {
            $fieldOptions['attr']['placeholder'] = $paaFieldRule['placeholder_text'];
        }

        $form->add('location_autocomplete', TextType::class, $fieldOptions);
        $this->addOrderedField('location_autocomplete');
        //Add Location Area
        // autocomplete hidden field for value
        $form->add('area', HiddenType::class, array(
            'mapped' => false,
            'data' => $areaId
        ));
        
        // autocomplete text field for location Area
        $fieldOptionsForArea = array(
            'mapped' => false,
            'label' => 'Location Area',
            'data' => $areaText,
            'attr' => array(
                'class' => 'white-field'
            )
        );
        
        $form->add('area_autocomplete', TextType::class, $fieldOptionsForArea);
    }

    /**
     * Add location field validation.
     *
     * @param object $form Form instance.
     */
    protected function validateAdLocation($form)
    {
        $location = $form->get('location')->getData();
        if ($location) {
            $postCode = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($location);
            $town     = null;
            $locality = null;

            if (!$postCode || $postCode->getTownId() == null || $postCode->getTownId() == 0) {
                if (preg_match('/^\d+$/', $location)) {
                    $town = $this->em->getRepository('FaEntityBundle:Location')->getTownAndAreaById($location, $this->container);
                } else if (preg_match('/^([\d]+,[\d]+)$/', $location)) {
                    $localityTown = explode(',', $location);
                    $localityId = $localityTown[0];
                    $townId     = $localityTown[1];
                    if ($localityId && $townId) {
                        $town = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $townId, 'lvl' => '3'));
                    }
                } else {
                    $town = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $location, 'lvl' => '3'));
                    if (!$town) {
                        $locality = $this->em->getRepository('FaEntityBundle:Locality')->findOneBy(array('name' => $location));
                    }
                }
            }

            if (!$postCode && !$town && !$locality) {
                $form->get('location_autocomplete')->addError(new FormError($this->translator->trans('Location is invalid.', array(), 'validators')));
            }
            //validate Area for London Location
            if($postCode && $postCode->getId() != null) {
                $town      = $this->em->getRepository('FaEntityBundle:Location')->find($postCode->getTownId());
            }
            
            if($town) {
                //check area is based on London Location
                if($town && $town->getId() == LocationRepository::LONDON_TOWN_ID){
                    $locationArea = $form->get('area')->getData();
                    
                    if($locationArea == null) {
                        $form->get('area_autocomplete')->addError(new FormError($this->translator->trans('Area should not be blank.', array(), 'validators')));
                    } else {
                        $area = null;
                        if (preg_match('/^\d+$/', $locationArea)) {
                            $area = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id'=>$locationArea, 'lvl'=>'4'));
                        }
                        
                        if ( !$area ) {
                            $form->get('area_autocomplete')->addError(new FormError($this->translator->trans('Area is invalid.', array(), 'validators')));
                        }
                    }
                }
            }
        }
    }

    /**
     * Validate price based on ad type selected.
     *
     * @param object $form Form instance.
     */
    protected function validatePrice($form)
    {
        if ($form->has('price')) {
            if ($form->get('price')->getData() == '') {
                $form->get('price')->addError(new FormError($this->translator->trans('Value should not be blank.', array(), 'validators')));
            } else {
                if (!preg_match('/^[0-9]{1,3}(?:\,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $form->get('price')->getData() , $matches)) {
                    $form->get('price')->addError(new FormError($this->translator->trans('Price is invalid.', array(), 'validators')));
                }
            }
        }
    }

    /**
     * Validate ad image limits.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance.
     */
    protected function validateAdImageLimit($form, $ad = null)
    {
        return true;

        // Image validation : required one photo uploaded
        if ($ad && $ad->getId()) {
            $adId = $ad->getId();
        } else {
            $adId = $this->container->get('session')->get('admin_ad_id_'.$form->get('admin_ad_counter')->getData());
        }

        $adImgCount = $this->em->getRepository('FaAdBundle:AdImage')->getAdImageCount($adId);
        if ($adImgCount < 1) {
            $form->get('photo_error')->addError(new FormError($this->translator->trans('Minimum 1 photo is required.', array(), 'validators')));
        }
    }

    /**
     * Add field to order array to render on template.
     *
     * @param string $field Field.
     */
    protected function addOrderedField($field)
    {
        $this->orderedFields[] = $field;
    }

    /**
     * Add field to disable fields array to NOT render on template.
     *
     * @param string $field Field.
     */
    protected function addDisabledField($field)
    {
        $this->disabledFields[] = $field;
    }

    /**
     * Get vertical object.
     *
     * @param integer $categoryId Category Id.
     * @param object  $ad         Ad instance.
     *
     * @return object
     */
    protected function getVerticalObject($categoryId, $ad = null)
    {
        if ($ad && $ad->getId()) {
            return $this->getVerticalRepository()->findOneBy(array('ad' => $ad->getId()));
        }

        return null;
    }

    /**
     * Get ad vertical fields.
     *
     * @param integer $categoryId Category Id.
     *
     * @return array
     */
    protected function getVerticalFields()
    {
        return $this->getVerticalRepository()->getAllFields();
    }

    /**
     * Get ad not-inexed vertical fields.
     *
     * @return array
     */
    protected function getNotIndexedVerticalFields()
    {
        return $this->getVerticalRepository()->getNotIndexedFields();

    }

    /**
     * Get ad vertical repository.
     *
     * @param integer $categoryId Category Id.
     *
     * @return object
     */
    protected function getVerticalRepository()
    {
        $repoName = 'FaAdBundle:Ad'.CommonManager::getCategoryClassNameById($this->getRootCategoryId(), true);
        return $this->em->getRepository($repoName);
    }

    /**
     * Get ad post step wise data from session.
     *
     * @param string $step Ad post step.
     *
     * return array
     */
    protected function getAdPostStepData($step)
    {
        $stepData = array();

        if ($this->container->get('session')->has('paa_'.$step.'_step_data')) {
            $stepData = unserialize($this->container->get('session')->get('paa_'.$step.'_step_data'));
        }

        return $stepData;
    }

    /**
     * Get ad object with set moderated data.
     *
     * @param object $ad Ad object.
     *
     * @return object
     */
    protected function getAdObjectWithModeratedData($ad)
    {
        // check whether to fill data from moderation or not.
        $adModerate = $this->em->getRepository('FaAdBundle:AdModerate')->findByAdIdAndModerationQueueFilter($ad->getId(), array(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT, AdModerateRepository::MODERATION_QUEUE_STATUS_MANUAL_MODERATION, AdModerateRepository::MODERATION_QUEUE_STATUS_SEND));
        if ($ad->getStatus()->getId() == EntityRepository::AD_STATUS_IN_MODERATION_ID || ($adModerate && $adModerate->getStatus()->getId() == EntityRepository::AD_STATUS_IN_MODERATION_ID) || ($adModerate && $adModerate->getModerationQueue() == 0)) {
            if ($adModerate && $adModerate->getValue()) {
                $this->setModerationValue(unserialize($adModerate->getValue()));
            }
        }

        // fill ad object from moderation
        if (count($this->moderationValue) > 0 && isset($this->moderationValue['ad'][0])) {
            $ad = $this->em->getRepository('FaAdBundle:Ad')->setObjectFromModerationData($this->moderationValue['ad'][0]);
        }

        return $ad;
    }

    /**
     * Add detached ad fields.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance
     */
    protected function addDetachedAdFields($form, $ad = null)
    {
        if ($this->isDetachedAd($ad)) {
            $form
                ->add(
                    'phone',
                    TelType::class,
                    array(
                        'label'  => 'Telephone number',
                        'mapped' => false,
                        'data'   => ($ad && $ad->getPhone() ? $ad->getPhone() : null),
                    )
                )
                ->add(
                    'use_privacy_number',
                    CheckboxType::class,
                    array(
                        'label'  => 'Keep telephone number private',
                        'mapped' => false,
                        'data'   => ($ad && $ad->getUsePrivacyNumber() ? $ad->getUsePrivacyNumber() : null),
                    )
                );

            if ($ad && $ad->getId()) {
                $form
                ->add(
                    'email',
                    TextType::class,
                    array(
                        'label'    => 'Associate ad with existing user (email)',
                        'mapped'   => false,
                        'required' => false,
                        'data'   => ($ad && $ad->getEmail() ? $ad->getEmail() : null)
                    )
                );
            }
        }
    }

    /**
     * Add future post ad fields.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance
     */
    protected function addFutureAdPostFields($form, $ad = null)
    {
        if ($this->isAllowedFutureAdPostDate($ad)) {
            if ($ad && $ad->getId()) {
                $form
                ->add(
                    'future_publish_at',
                    TextType::class,
                    array(
                        'label'    => 'Post advert at a later date',
                        'mapped'   => false,
                        'required' => false,
                        'data'   => ($ad && $ad->getFuturePublishAt() ? date('d/m/Y', $ad->getFuturePublishAt()) : null),
                        'attr'     => array(
                            'class' => 'fdatepicker',
                            'autocomplete' => 'off',
                            'placeholder' => 'dd/mm/yyyy'
                        )
                    )
                );
            } else {
                $form
                ->add(
                    'future_publish_at',
                    TextType::class,
                    array(
                        'label'    => 'Post advert at a later date',
                        'mapped'   => false,
                        'required' => false,
                        'attr'     => array(
                            'class' => 'fdatepicker',
                            'autocomplete' => 'off',
                            'placeholder' => 'dd/mm/yyyy'
                        )
                    )
                );
            }
        }
    }

    /**
     * Validate detached ad fields.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance.
     */
    protected function validateDetachedAdFields($form, $ad = null)
    {
        if ($this->isDetachedAd($ad)) {
            if ($ad && $ad->getId()) {
                if ($form->get('email')->getData() == '') {
                    if ($form->get('phone')->getData() == '') {
                        $form->get('phone')->addError(new FormError($this->translator->trans('Telephone number is required.', array(), 'validators')));
                    } else {
                        if (!preg_match('/^\+?\d{7,11}$/', str_replace(' ', '', $form->get('phone')->getData()))) {
                            $form->get('phone')->addError(new FormError($this->translator->trans('Please enter correct telephone number. It should contain minimum 7 digit and maximum 11 digit.', array(), 'validators')));
                        }
                    }
                } else {
                    $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData(), 'is_half_account' => '0'));
                    if (!$user) {
                        $form->get('email')->addError(new FormError($this->translator->trans('Please enter existing user\'s email.', array(), 'validators')));
                    }
                }
            } else {
                if ($form->get('phone')->getData() == '') {
                    $form->get('phone')->addError(new FormError($this->translator->trans('Telephone number is required.', array(), 'validators')));
                } else {
                    if (!preg_match('/^\+?\d{7,11}$/', str_replace(' ', '', $form->get('phone')->getData()))) {
                        $form->get('phone')->addError(new FormError($this->translator->trans('Please enter correct telephone number. It should contain minimum 7 digit and maximum 11 digit.', array(), 'validators')));
                    }
                }
            }
        }
    }

    /**
     * Validate future post ad fields.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance.
     */
    protected function validateFutureAdPostFields($form, $ad = null)
    {
        if ($this->isAllowedFutureAdPostDate($ad)) {
            $futurePublishDate = CommonManager::getTimeStampFromStartDate($form->get('future_publish_at')->getData());
            $tomorrowDate      = CommonManager::getTimeStampFromStartDate(date('d/m/Y', strtotime('tomorrow')));
            if ($futurePublishDate && $tomorrowDate && $futurePublishDate < $tomorrowDate) {
                $form->get('future_publish_at')->addError(new FormError('Please select future date only.'));
            }
        }
    }

    /**
     * Validate detached ad fields.
     *
     * @param object $ad Ad instance.
     *
     * @return boolean
     */
    protected function isDetachedAd($ad = null)
    {
        $isDetachedAd = false;
        if ($ad && $ad->getId()) {
            if (!$ad->getUser()) {
                $isDetachedAd = true;
            }
        } else {
            if ($this->request->get('user_id') == 'no_user') {
                $isDetachedAd = true;
            }
        }

        return $isDetachedAd;
    }

    /**
     * Check is future date post allowed.
     *
     * @param object $ad Ad instance.
     *
     * @return boolean
     */
    protected function isAllowedFutureAdPostDate($ad = null)
    {
        $isAllowedFutureAdPostDate = false;
        if (!$this->isDetachedAd($ad)) {
            //for edit
            $allowedEditStatusIdArray = array(
                EntityRepository::AD_STATUS_DRAFT_ID,
                EntityRepository::AD_STATUS_EXPIRED_ID,
                EntityRepository::AD_STATUS_SOLD_ID,
                EntityRepository::AD_STATUS_INACTIVE_ID,
            );
            if ($ad && $ad->getId() && $ad->getStatus() && in_array($ad->getStatus()->getId(), $allowedEditStatusIdArray)) {
                $isAllowedFutureAdPostDate = true;
            } elseif ($ad && !$ad->getId()) {
                $isAllowedFutureAdPostDate = true;
            }
        }


        return $isAllowedFutureAdPostDate;
    }

    /**
     * Validate description.
     *
     * @param object $form Form instance.
     */
    protected function validateDescription($form)
    {
        if ($form->has('description')) {
            $fieldOptions = $form->get('description')->getConfig()->getOptions();
            if (isset($fieldOptions['attr']) && isset($fieldOptions['attr']['maxlength']) && $fieldOptions['attr']['maxlength']) {
                $description = $form->get('description')->getData();
                $description = preg_replace('/<p>&nbsp;<\/p>/', '', $description);
                $description = preg_replace('/<[^>]*>/i', '', $description);
                $description = preg_replace('/\r|\n|\r\n/i', '', $description);
                if ($form->get('description')->getData() && mb_strlen(html_entity_decode($description), 'UTF-8') > $fieldOptions['attr']['maxlength']) {
                    $form->get('description')->addError(new FormError($this->translator->trans('This value is too long. It should have %maxlength% characters or less.', array('%maxlength%' => $fieldOptions['attr']['maxlength']), 'validators')));
                }
            }
        }
    }

    /**
     * Add business phone field.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance
     */
    protected function addBusinessAdField($form, $ad = null)
    {
        if ($this->isBusinessUser($ad)) {
            $form
            ->add(
                'business_phone',
                TelType::class,
                array(
                    'label'    => 'Ad specific phone number',
                    'mapped'   => false,
                    'required' => false,
                    'data'     => ($ad && $ad->getBusinessPhone() ? $ad->getBusinessPhone() : null)
                )
            );
        }
    }

    /**
     * Is business user.
     *
     * @param object $ad Ad instance
     *
     * @return boolean
     */
    protected function isBusinessUser($ad = null)
    {
        $user = null;
        if ($ad && $ad->getId()) {
            $user = $ad->getUser();
        } else {
            $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('id' => $this->request->get('user_id', null)));
        }

        if ($user && ($user->getRole() && $user->getRole()->getId() == RoleRepository::ROLE_BUSINESS_SELLER_ID)) {
            return true;
        }

        return false;
    }

    /**
     * Validate business ad field.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance
     *
     */
    protected function validateBusinessAdField($form, $ad = null)
    {
        if ($this->isBusinessUser($ad)) {
            if ($form->has('business_phone') && $form->get('business_phone')->getData()) {
                if (!preg_match('/^\+?\d{7,11}$/', str_replace(' ', '', $form->get('business_phone')->getData()))) {
                    $form->get('business_phone')->addError(new FormError($this->translator->trans('Please enter correct ad specific phone number. It should contain minimum 7 digit and maximum 11 digit.', array(), 'validators')));
                }
            }
        }
    }

    /**
     * Validate youtube ad field.
     *
     * @param object $form Form instance.
     * @param object $ad   Ad instance
     *
     */
    protected function validateYoutubeField($form, $ad = null)
    {
        $youtubeVideoUrl = trim($form->get('youtube_video_url')->getData());

        // validate youtube video url.
        if ($youtubeVideoUrl) {
            $youtubeVideoId = CommonManager::getYouTubeVideoId($youtubeVideoUrl);
            if (!$youtubeVideoId) {
                $form->get('youtube_video_url')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid youtube video url.', array(), 'validators')));
            }
        }
    }
    
    /**
     * Validate Adult Rates ad field.
     *
     * @param object $form
     *            Form instance.
     *
     */
    protected function validateAdultRates($form, $ad = null)
    {
        if($ad && $ad->getId()) {
            $categoryId = $ad->getCategory()->getId();
        } else{
            $categoryId = $form->get('category_id')->getData();
        }
        if($form->has('travel_arrangements_id') && $form->get('travel_arrangements_id')->getData() != '') {
            $checkRateIsRequired = $this->em->getRepository('FaAdBundle:PaaField')->checkRateDimensionIsRequired($categoryId);
            $getTravelArrangement = $this->em->getRepository('FaEntityBundle:Entity')->find((int) $form->get('travel_arrangements_id')->getData());
            if(!empty($getTravelArrangement) && ($getTravelArrangement->getName() == 'In-call' || $getTravelArrangement->getName() == 'Either')) {
                if ($checkRateIsRequired && $form->has('1hour_incall') && $form->get('1hour_incall')->getData() == '' && $form->get('1hour_incall')->getData() <= '0') {
                    $form->get('1hour_incall')->addError(new FormError($this->translator->trans('1 hr In-call rate is required', array(), 'validators')));
                }
            }
            
            if(!empty($getTravelArrangement) && ($getTravelArrangement->getName() == 'Out-call' || $getTravelArrangement->getName() == 'Either')) {
                if ($checkRateIsRequired && $form->has('1hour_outcall') && $form->get('1hour_outcall')->getData() == '' && $form->get('1hour_outcall')->getData() <= '0') { var_dump($checkRateIsRequired); die;
                $form->get('1hour_outcall')->addError(new FormError($this->translator->trans('1 hr Out-call rate is required', array(), 'validators')));
                }
            }
        }
    }

    /**
     * This method is used to get root category id.
     */
    abstract protected function getRootCategoryId();

    /**
     * This method is used to set values from ad moderate table.
     *
     * @param array $value
     */
    abstract protected function setModerationValue($value = array());
}
