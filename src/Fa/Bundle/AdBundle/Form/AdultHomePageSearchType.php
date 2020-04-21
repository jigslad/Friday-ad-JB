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
            'item__category_id',
            JsChoiceType::class,
            array(
                'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryArraySimpleById(CategoryRepository::ADULT_ID)),
                'label'       => 'Choose by category',
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
        
        $dimensionIdsArray = $indOrAgencyArray = $ethinicityArray = $servicesArray = array();
        $ethinicityArray[''] = 'Any Ethinicity';
        $servicesArray[''] = 'Any Service';
        $dimensionIdsArray = $this->em->getRepository('FaEntityBundle:CategoryDimension')->getDimesionIdsByCategoryIdAndName(CategoryRepository::ESCORT_SERVICES_ID, array('Ethnicity', 'Services','Independent or Agency'), $this->container);
        if (!empty($dimensionIdsArray)) {
            foreach ($dimensionIdsArray as $dimensionId => $dimensionName) {
                if ($dimensionName == 'Ethnicity') {
                    $dimensionsList = $this->em->getRepository('FaEntityBundle:Entity')->findby(array('category_dimension'=>$dimensionId));
                    foreach ($dimensionsList as $dimension){
                        $ethinicityArray[$dimension->getId()] = $dimension->getName();
                    }
                } elseif ($dimensionName == 'Services') {
                    $dimensionsList = $this->em->getRepository('FaEntityBundle:Entity')->findby(array('category_dimension'=>$dimensionId));
                    foreach ($dimensionsList as $dimension){
                        $servicesArray[$dimension->getId()] = $dimension->getName();
                    }
                } elseif ($dimensionName == 'Independent or Agency') {
                    $dimensionsList = $this->em->getRepository('FaEntityBundle:Entity')->findby(array('category_dimension'=>$dimensionId));
                    foreach ($dimensionsList as $dimension){
                        $indOrAgencyArray[$dimension->getId()] = $dimension->getName();
                    }
                }
            }
        }
        
        $form->add(
            'item_adult__independent_or_agency_id',
            HiddenType::class
            /*array(
                'choices'     => array_flip($indOrAgencyArray),
                'expanded' => true
            )*/
        );
        $form->add(
            'item_adult__ethnicity_id',
            JsChoiceType::class,
            array(
                'choices'     => array_flip($ethinicityArray),
                'placeholder' => 'Ethnicity',
            )
        );
        $form->add(
            'item_adult__services_id',
            JsChoiceType::class,
            array(
                'choices'     => array_flip($servicesArray),
                'placeholder' => 'Any Service',
                'multiple' => true,
            )
        );
        
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
