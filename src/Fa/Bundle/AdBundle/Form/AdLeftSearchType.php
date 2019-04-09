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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * This form is used for left search.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdLeftSearchType extends AbstractType
{
    /**
     * Category Dimension filters to rendered on template.
     *
     * @var array
     */
    protected $dimensionFilters = array();

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
    private $em;

    /**
     * The request instance.
     *
     * @var Request
     */
    private $request;

    /**
     * Array of field parent id.
     *
     * @var array
     */
    protected $parentIdArray;

    /**
     * Constructor.
     *
     * @param object       $container    Container instance.
     * @param RequestStack $requestStack RequestStack instance.
     */
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->request   = $requestStack->getCurrentRequest();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Form builder.
     * @param array                $options Form options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('item__price_from', TextType::class, array(/** @Ignore */'label' => false))
        ->add('item__price_to', TextType::class, array(/** @Ignore */'label' => false))
        ->add('item__category_id', HiddenType::class)
        ->add('map', HiddenType::class)
        ->add('sort_field', HiddenType::class)
        ->add('sort_ord', HiddenType::class)
        ->add(
            'expired_ads',
            CheckboxType::class,
            array(
                'required' => false,
                'label'    => 'Expired ads',
            )
        )
        ->add(
            'items_with_photo',
            CheckboxType::class,
            array(
                'required' => false,
                'label'    => 'Only ads with photos',
            )
        )
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));

        if (isset($options['data']) && isset($options['data']['isShopPage']) && $options['data']['isShopPage']) {
            $builder->add('keywords', TextType::class)
            ->add('item__user_id', HiddenType::class);
        } else {
            $builder->add('keywords', HiddenType::class);
        }

        if (isset($options['data']['parentIdArray']) && $options['data']['parentIdArray']) {
            $this->parentIdArray = $options['data']['parentIdArray'];
        }
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();        
        $defDistance = '';
        $getDefaultRadius = $cookieLocation = array();
        $rootCategoryId = null;

        $categoryId   = '';$getLocLvl = 0;
        $searchParams = $this->request->get('searchParams');
        
        $searchLocation = isset($searchParams['item__location'])?$searchParams['item__location']:2;
        if($searchLocation!=2) {
            $selLocationArray = $this->em->getRepository('FaEntityBundle:Location')->find($searchLocation);
            if(!empty($selLocationArray)) { $getLocLvl = $selLocationArray->getLvl(); }
        }
        
        if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
            $categoryId = $searchParams['item__category_id'];
        }
        if (isset($searchParams['item__distance']) && $searchParams['item__distance']) {
            $defDistance = $searchParams['item__distance'];
        } else {
            $getDefaultRadius = $this->em->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($searchParams, $this->container);
            $defDistance = ($getDefaultRadius)?$getDefaultRadius:'';
        }       
        if($defDistance=='') {
            if($categoryId!='') {
                $rootCategoryId = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
                $defDistance = ($rootCategoryId==CategoryRepository::MOTORS_ID)?CategoryRepository::MOTORS_DISTANCE:CategoryRepository::OTHERS_DISTANCE;
            } else {
                $defDistance = 200;
            }
       }  
       
       if($searchLocation == 2 || $getLocLvl==2) {                      
           $form->add('hide_distance_block', HiddenType::class,array('mapped' => false,'empty_data' => 1,'data'=>1));
        } else {            
           $form->add('hide_distance_block', HiddenType::class,array('mapped' => false,'empty_data' => 0,'data'=>0));
        }
        
        $form->add(
            'item__distance',
            ChoiceType::class,
            array(
                'choices' => array_flip($this->em->getRepository('FaEntityBundle:Location')->getDistanceOptionsArray($this->container)),
                'placeholder' => $defDistance,
                'data' => $defDistance,
                'attr'    => array('class' => 'fa-select-white')
            )
        );
        $this->addLocationAutoSuggestField($form);
        $this->addCategroyDimensionFilters($form, $categoryId);
        $this->addIsTradeAdField($form);
    }

    /**
     * Add location autosuggest field.
     *
     * @param object $form Form instance.
     */
    protected function addLocationAutoSuggestField($form)
    {
        $searchLocation = isset($searchParams['item__location'])?$searchParams['item__location']:2;
        $form->add('item__location', HiddenType::class, array('data'=>$searchLocation,'empty_data'=>$searchLocation));
        $form->add('item__location_autocomplete', TextType::class, array(/** @Ignore */'label' => false));
        $form->add('item__area', HiddenType::class);
    }

    /**
     * Add is trade ad field.
     *
     * @param object $form Form instance.
     */
    protected function addIsTradeAdField($form)
    {
        $fieldOptions['expanded'] = false;
        $fieldOptions['multiple'] = false;
        $form->add(
            'item__is_trade_ad',
            ChoiceType::class,
            array(
                'expanded' => false,
                'multiple' => false,
                /** @Ignore */
                'label'    => false,
                'choices'  => array('Select' => '', 'Private Seller' => '0', 'Business Seller' => '1')
            )
        );
    }

    /**
     * Add category dimension fields.
     *
     * @param object  $form       Form instance.
     * @param integer $categoryId Selected category id.
     */
    protected function addCategroyDimensionFilters($form, $categoryId = null)
    {
        if ($categoryId) {
            $dimensions = $this->em->getRepository('FaEntityBundle:CategoryDimension')->getLeftSearchableDimensionFieldsArrayByCategoryId($categoryId, $this->container);
            $dimensionFieldPrefix = 'item';
            $rootCategoryName  = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryName($categoryId, $this->container);

            if ($rootCategoryName) {
                $dimensionFieldPrefix = $dimensionFieldPrefix.'_'.$rootCategoryName;
            }

            foreach ($dimensions as $dimensionId => $dimension) {
                $dimensionName  = $dimension['name'];
                $dimensionLabel = ucfirst(strtolower($dimension['name']));
                $dimensionField = str_replace(array('(', ')', ',', '?', '|', '.', '/', '\\', '*', '+', '-', '"', "'"), '', $dimensionName);
                $dimensionField = str_replace(' ', '_', strtolower($dimensionField));

                $searchTypeArray = explode('_', $dimension['search_type']);
                if ($searchTypeArray[0] == 'choice') {
                    $dimensionField = $dimensionField.'_id';

                    $parentId = null;
                    if (isset($this->parentIdArray[$dimensionField])) {
                        $parentId = $this->parentIdArray[$dimensionField];
                    }

                    $fieldChoices = array();
                    $withSort = true;
                    if (in_array($dimensionField, array('size_id', 'age_range_id', 'tonnage_id', 'berth_id', 'number_of_stalls_id', 'number_of_bathrooms_id', 'number_of_bedrooms_id', 'number_of_rooms_available_id', 'rent_per_id'))) {
                        $withSort = false;
                    }

                    if ($dimensionField == 'reg_year_id') {
                        $dimensionField = str_replace('_id', '', $dimensionField);
                        $fieldChoices   = CommonManager::getRegYearChoices();
                    } elseif ($dimensionField == 'mileage_id') {
                        $dimensionField = str_replace('_id', '', $dimensionField).'_range';
                        $fieldChoices   = CommonManager::getMileageChoices();
                    } elseif ($dimensionField == 'engine_size_id') {
                        $dimensionField = str_replace('_id', '', $dimensionField).'_range';
                        $fieldChoices   = CommonManager::getEngineSizeChoices();
                    } else {
                        if ($parentId) {
                            $fieldChoices = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByParent($parentId, $this->container);
                        } else {
                            $fieldChoices = $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($dimensionId, $this->container, $withSort);
                        }

                        // Merge options of clothes brand with baby and kids brand options
                        if ($categoryId == CategoryRepository::BABY_AND_KIDS_ID && $dimensionField == 'brand_id') {
                            $brandDimensionId = $this->em->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategory(CategoryRepository::CLOTHES_ID, 'brand', $this->container);
                            if ($brandDimensionId) {
                                $fieldChoices = $fieldChoices + $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($brandDimensionId, $this->container, $withSort);
                            }
                        }
                    }

                    if ($dimensionField == 'travel_arrangements_id') {
                        $fieldChoices = $this->em->getRepository('FaEntityBundle:Entity')->customFormatOptions($fieldChoices, 'for-endusers');
                    }

                    $fieldOptions = array();
                    if (isset($searchTypeArray[1]) && $searchTypeArray[1] == 'radio') {
                        $fieldOptions['expanded'] = true;
                        $fieldOptions['multiple'] = false;
                    } elseif (isset($searchTypeArray[1]) && $searchTypeArray[1] == 'checkbox') {
                        $fieldOptions['expanded'] = true;
                        $fieldOptions['multiple'] = true;
                    } elseif (isset($searchTypeArray[1]) && ($searchTypeArray[1] == 'single' || $searchTypeArray[1] == 'link')) {
                        $fieldOptions['expanded'] = false;
                        $fieldOptions['multiple'] = false;
                        $fieldChoices = array('' => $dimensionLabel) + $fieldChoices;
                    } elseif (isset($searchTypeArray[1]) && $searchTypeArray[1] == 'multiple') {
                        $fieldOptions['expanded'] = false;
                        $fieldOptions['multiple'] = true;
                    }

                    $fieldOptions = $fieldOptions + array(/** @Ignore */'label' => $dimensionLabel, 'choices' => array_flip($fieldChoices));

                    if ($dimensionField == 'ad_type_id') {
                        $dimensionField = 'item__'.$dimensionField;
                    } else {
                        $dimensionField = $dimensionFieldPrefix.'__'.$dimensionField;
                    }

                    $form->add($dimensionField, ChoiceType::class, $fieldOptions);
                    $this->addDimensionFilter($dimensionField);
                } elseif ($dimension['search_type'] == 'range_date') {
                    if ($rootCategoryName == 'community') {
                        $dimensionLabel = 'Event date';
                    }

                    $fieldOptions = array(/** @Ignore */'label' => $dimensionLabel, 'attr' => array('class' => 'fdatepicker_search white-field', 'autocomplete' => 'off', 'placeholder' => 'From'));
                    $form->add($dimensionFieldPrefix.'__'.$dimensionField.'_from', TextType::class, $fieldOptions);

                    $fieldOptions = array(/** @Ignore */'label' => $dimensionLabel, 'attr' => array('class' => 'fdatepicker_search white-field', 'autocomplete' => 'off', 'placeholder' => 'To'));
                    $form->add($dimensionFieldPrefix.'__'.$dimensionField.'_to', TextType::class, $fieldOptions);

                    // add date period field
                    if ($rootCategoryName == 'property') {
                        $fieldOptions = array(/** @Ignore */'label' => false, 'choices' => array_flip(CommonManager::getTimePeriodChoices('property', $this->container)), 'attr' => array('class' => 'fa-select-white'));
                        $form->add($dimensionFieldPrefix.'__'.$dimensionField.'_period', ChoiceType::class, $fieldOptions);
                    } elseif ($rootCategoryName == 'community') {
                        $fieldOptions = array(/** @Ignore */'label' => false, 'choices' => array_flip(CommonManager::getTimePeriodChoices('community', $this->container)), 'attr' => array('class' => 'fa-select-white'));
                        $form->add($dimensionFieldPrefix.'__'.$dimensionField.'_period', ChoiceType::class, $fieldOptions);
                    }
                } elseif ($dimension['search_type'] == 'range_text') {
                    $fieldOptions = array(/** @Ignore */'label' => $dimensionLabel, 'attr' => array('class' => 'white-field', 'placeholder' => 'Minimum'));
                    $form->add($dimensionFieldPrefix.'__'.$dimensionField.'_from', TextType::class, $fieldOptions);

                    $fieldOptions = array(/** @Ignore */'label' => $dimensionLabel, 'attr' => array('class' => 'white-field', 'placeholder' => 'Maximum'));
                    $form->add($dimensionFieldPrefix.'__'.$dimensionField.'_to', TextType::class, $fieldOptions);
                } elseif ($dimension['search_type'] == 'date') {
                    $fieldOptions = array(/** @Ignore */'label' => $dimensionLabel, 'attr' => array('class' => 'fdatepicker_search white-field', 'autocomplete' => 'off', 'placeholder' => 'Please select date'));
                    $form->add($dimensionFieldPrefix.'__'.$dimensionField, TextType::class, $fieldOptions);
                } elseif ($dimension['search_type'] == 'text') {
                    $fieldOptions = array(/** @Ignore */'label' => $dimensionLabel,'attr' => array('class' => 'white-field'));
                    $form->add($dimensionFieldPrefix.'__'.$dimensionField, TextType::class, $fieldOptions);
                }
            }
        }
    }

    /**
     * Add dimension filters to array to render on template.
     *
     * @param string $field Field.
     */
    protected function addDimensionFilter($field)
    {
        $this->dimensionFilters[] = $field;
    }

    /**
     * Set default form options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => null,
                'translation_domain' => 'frontend-left-search',
                'csrf_protection'    => false,
            )
        );
    }

    /**
     * Get form name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_left_search';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_left_search';
    }
}
