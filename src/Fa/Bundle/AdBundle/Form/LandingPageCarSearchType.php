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
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Fa\Bundle\CoreBundle\Form\Type\JsChoiceType;

/**
 * This form is used for search car from landing page.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LandingPageCarSearchType extends AbstractType
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
        $transmissionDimensionId = $this->em->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategory(CategoryRepository::CARS_ID, 'transmission', $this->container);
        $colourDimensionId       = $this->em->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategory(CategoryRepository::CARS_ID, 'colour', $this->container);
        $fuelTypeDimensionId     = $this->em->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategory(CategoryRepository::CARS_ID, 'fuel type', $this->container);

        $builder
        ->add('item__price_from', NumberType::class, array(/** @Ignore */'label' => false))
        ->add('item__price_to', NumberType::class, array(/** @Ignore */'label' => false))
        ->add(
            'item__make_id',
            ChoiceType::class,
            array(
                'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId(CategoryRepository::CARS_ID, $this->container)),
                'placeholder' => 'Any make',
                'label'       => 'Make',
                'attr' => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'item__model_id',
            JsChoiceType::class,
            array(
                'choices' => array(),
                'label'   => 'Model',
                'attr'    => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'item__category_id',
            HiddenType::class,
            array(
                'data' => CategoryRepository::CARS_ID
            )
        )
        ->add(
            'item_motors__body_type_id',
            ChoiceType::class,
            array(
                'label'       => 'Body Type',
                'placeholder' => 'Any',
                'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Entity')->getLandingPageBodyTypeArray($this->container)),
                'attr'        => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'item_motors__mileage_range',
            ChoiceType::class,
            array(
                'label'       => 'Mileage',
                'placeholder' => 'Any mileage',
                'choices'     => array_flip(CommonManager::getMileageChoices()),
                'attr'        => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'item_motors__transmission_id',
            ChoiceType::class,
            array(
                'label'       => 'Transmission',
                'placeholder' => 'Any transmission',
                'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($transmissionDimensionId, $this->container)),
                'attr'        => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'item_motors__colour_id',
            ChoiceType::class,
            array(
                'label'       => 'Colour',
                'placeholder' => 'Any colour',
                'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($colourDimensionId, $this->container)),
                'attr'        => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'item_motors__fuel_type_id',
            ChoiceType::class,
            array(
                'label'       => 'Fuel Type',
                'placeholder' => 'Any fuel',
                'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($fuelTypeDimensionId, $this->container)),
                'attr'        => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'keywords',
            TextType::class,
            array(
                'label' => 'Keyword',
                'attr'  => array('placeholder' => 'e.g. GTI', 'class' => 'white-field')
            )
        )
        ->add('item__location', HiddenType::class)
        ->add('item__location_autocomplete', TextType::class, array(/** @Ignore */'label' => false))
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
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
        $getDefaultRadius = $searchParams = array();

        $categoryId   = '';
        if ($this->request->get('category_id')) {
            $searchParams['item__category_id'] = $this->request->get('category_id');
        }
        if ($this->request->get('location')) {
            $searchParams['item__location'] = $this->request->get('location');
        }
        
        $getDefaultRadius = $this->em->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($searchParams, $this->container);
        $defDistance = ($getDefaultRadius)?$getDefaultRadius:CategoryRepository::MAX_DISTANCE;

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
                'translation_domain' => 'frontend-landing-page',
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
        return 'fa_landing_page_car_search';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_landing_page_car_search';
    }
}
