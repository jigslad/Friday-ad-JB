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

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This form is used for left search.
 *
 * @author Chaitra Bhat <chaitra.bhat@fridaymediagroup.com>
 * @copyright 2020 Friday Media Group Ltd
 * @version v1.0
 */
class AdLeftSearchNewType extends AbstractType
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

    protected $searchParams;

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
        $this->searchParams = $options['data']['searchParams'];

        $builder
        ->add('item__price_from', TextType::class, array(/** @Ignore */'label' => false, 'data' => empty($this->searchParams['item__price_from']) ? '' : $this->searchParams['item__price_from']))
        ->add('item__price_to', TextType::class, array(/** @Ignore */'label' => false, 'data' => empty($this->searchParams['item__price_to']) ? '' : $this->searchParams['item__price_to']))
        ->add('item__category_id', HiddenType::class)
        ->add('map', HiddenType::class)
        ->add('sort_field', HiddenType::class)
        ->add('sort_ord', HiddenType::class)
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));

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
        $getDefaultRadius = $cookieLocation = $cookieLocationDet = array();
        $rootCategoryId = null;

        $categoryId   = '';$getLocLvl = 0;
        $searchParams = $this->searchParams;
        
        $cookieLocation = $this->request->cookies->get('location');
        if(!empty($cookieLocation)) {
            $cookieLocationDet = json_decode($cookieLocation);
        }

        $searchLocation = isset($searchParams['item__location'])?$searchParams['item__location']:((!empty($cookieLocationDet) && isset($cookieLocationDet->town_id))?$cookieLocationDet->town_id:2);

        $isLocality = 0;$getLocLvl = 0;
        if (strpos($searchLocation,',') !== false) {
            $isLocality = 1;
        }
        if($isLocality) {
            $getLocLvl = 5;
        } else {
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
                $defDistance = CategoryRepository::MAX_DISTANCE;
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
        $form->add(
            'default_distance',
            HiddenType::class,
            array(
                'mapped' => false,
                'empty_data' => $defDistance,
                'data' => $defDistance,
            )
        );
        $this->addLocationAutoSuggestField($form,$searchLocation);
    }
    
    /**
     * Add location autosuggest field.
     *
     * @param object $form Form instance.
     */
    protected function addLocationAutoSuggestField($form,$searchLocation)
    {
        /*$searchLocationId = $searchLocationText = '';
        if(!empty($selLocationArray)) {
            $searchLocationId = $selLocationArray->getId();
            $searchLocationText = $selLocationArray->getName();
        } */
        
        $form->add('item__location', HiddenType::class, array('data'=>$searchLocationId,'empty_data'=>$searchLocation));
        $form->add('item__location_autocomplete', TextType::class, array(/** @Ignore */'label' => false,'data'=>$searchLocationText,'empty_data'=>$searchLocationText));
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
        return 'fa_left_search_new';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_left_search_new';
    }
}
