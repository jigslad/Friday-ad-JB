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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This form is used for header search.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdTopSearchType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;
    
    /**
     * The request instance.
     *
     * @var Request
     */
    private $request;

    /**
     * Entity manager class object.
     *
     * @var object
     */
    private $em;

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     */
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->request   = $requestStack->getCurrentRequest();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $leafLevelCategoryId = null;
        $searchParams = $this->container->get('request_stack')->getCurrentRequest()->attributes->get('searchParams');

        if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
            $leafLevelCategoryId = $searchParams['item__category_id'];
        }

        $mobileDetectManager = $this->container->get('fa.mobile.detect.manager');

        $builder
        ->add('keywords', TextType::class, array(/** @Ignore */'label' => false))
        ->add('keyword_category_id', HiddenType::class)
        ->add('item__price_from', HiddenType::class)
        ->add('item__price_to', HiddenType::class)
        ->add('tmpLeafLevelCategoryId', HiddenType::class, array('data' => $leafLevelCategoryId))
        ->add('leafLevelCategoryId', HiddenType::class)
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));

        if ($mobileDetectManager->isMobile()) {
            $builder->add(
                'item__category_id',
               ChoiceType::class,
                array(
                    'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container)),
                    'placeholder' => 'All',
                    /** @Ignore */
                    'label'       => false,
                    'choice_translation_domain' => false,
                )
            );
        } else {
            $builder->add(
                'item__category_id',
                ChoiceType::class,
                array(
                    'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container)),
                    'placeholder' => 'All categories',
                    /** @Ignore */
                    'label'       => false,
                    'choice_translation_domain' => false,
                )
            );
        }
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();  
        $categoryId   = '';$getLocLvl = 0;
        $defDistance = '';
        $getDefaultRadius = $cookieLocationDet = array(); 
        
        $searchParams = $this->request->get('searchParams');
        $qryItemDistance = $this->request->get('item__distance');
        $cookieLocation = $this->request->cookies->get('location');

        if(!empty($cookieLocation)) {
            $cookieLocationDet = json_decode($cookieLocation);
        }
        
        $searchLocation = isset($searchParams['item__location'])?$searchParams['item__location']:((!empty($cookieLocationDet) && isset($cookieLocationDet->town_id))?$cookieLocationDet->town_id:2);         
        
        /*if($searchLocation!=2) {
            $selLocationArray = $this->em->getRepository('FaEntityBundle:Location')->find($searchLocation);
            if(!empty($selLocationArray)) { $getLocLvl = $selLocationArray->getLvl(); }
        }*/

        $isLocality = 0;$getLocLvl = 0;
        if (strpos($searchLocation,',') !== false) {
            $isLocality = 1;
        }

        $selLocationArray = $this->em->getRepository('FaEntityBundle:Location')->getCookieValue($searchLocation, $this->container);

        if($isLocality) {
            $getLocLvl = 5;
        } else {
            if(!empty($selLocationArray)) { $getLocLvl = $selLocationArray['lvl']; }
        }
        
        if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
            $categoryId = $searchParams['item__category_id'];
        }

        unset($searchParams['item__distance']);
        if($qryItemDistance) {
            $searchParams['item__distance'] = $qryItemDistance;
        }

        if ($qryItemDistance) {
            $defDistance = $qryItemDistance;
        } else {
            $getDefaultRadius = $this->em->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParamsOnly($searchParams, $this->container);
            $defDistance = ($getDefaultRadius) ? $getDefaultRadius : CategoryRepository::MAX_DISTANCE;
        }
                    
        $form->add(
            'item__distance',
            HiddenType::class,
            array(
                'mapped' => false,
                'empty_data' => $defDistance,
                'data' => $defDistance,
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
        $this->addLocationAutoSuggestField($event->getForm(),$selLocationArray);
    }

    /**
     * Add location autosuggest field.
     *
     * @param object $form Form instance.
     */
    protected function addLocationAutoSuggestField($form,$selLocationArray)
    {
        $searchLocationId = $searchLocationText = '';
        if(!empty($selLocationArray)) {
            $searchLocationId = $selLocationArray['location'];
            $searchLocationText = $selLocationArray['location_text'];
        }

        $form->add('item__location', HiddenType::class, array('data'=>$searchLocationId,'empty_data'=>$searchLocationId));
        $form->add('item__location_autocomplete', TextType::class, array(/** @Ignore */'label' => false,'data'=>$searchLocationText,'empty_data'=>$searchLocationText));
        $form->add('item__area', HiddenType::class);
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
                'translation_domain' => 'frontend-header-search',
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
        return 'fa_top_search';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_top_search';
    }
}
