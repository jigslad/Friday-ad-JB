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

use Fa\Bundle\CoreBundle\Form\Type\JsChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * This form is used for search adult from landing page.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdultHomePageSearchType extends AbstractType
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
        $builder
        ->add(
            'item__distance',
            ChoiceType::class,
            array(
                'choices' => array_flip($this->em->getRepository('FaEntityBundle:Location')->getDistanceOptionsArray($this->container)),
                'data'    => 15,
                'attr'    => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'item__category_id',
            JsChoiceType::class,
            array(
                'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryArraySimpleById(CategoryRepository::ADULT_ID)),
                'label'       => 'Choose a category',
            )
        )
        ->add(
            'item_adult__ethnicity_id',
            JsChoiceType::class,
            array(
                'choices'     => array(),
                'data'        =>'Ethnicity'
            )
        )
        ->add(
            'item_adult__services_id',
            JsChoiceType::class,
            array(
                'choices'     => array(),
                'data'        =>'Service'
            )
        )
        ->add(
            'item_adult__independent_or_agency_id',
            JsChoiceType::class,
            array(
                'choices'     => array_flip(array(7317 => 'Agency',7318 => 'Independent')),
                'expanded' => true
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
        $defDistance = ($getDefaultRadius)?$getDefaultRadius:'';

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
        return 'fa_adult_home_page_search';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_adult_home_page_search';
    }
}
