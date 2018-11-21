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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * This form is used for search jobs from landing page.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LandingPageJobsSearchType extends AbstractType
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
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $jobCategoryIds = $this->em->getRepository('FaEntityBundle:Category')->getNestedChildrenIdsByCategoryId(CategoryRepository::JOBS_ID, $this->container);
        $jobCategoryArray = array();
        foreach ($jobCategoryIds as $jobCategoryId) {
            if ($jobCategoryId != CategoryRepository::JOBS_ID) {
                $jobCategoryArray[$jobCategoryId] = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $jobCategoryId);
            }
        }
        asort($jobCategoryArray);
        $jobCategoryArray = array(CategoryRepository::JOBS_ID => 'All categories') + $jobCategoryArray;

        //$contractTypeDimensionId = $this->em->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategoryHierarchy(CategoryRepository::JOBS_ID, 'Contract Type', $this->container);
        $builder
        ->add(
            'item_jobs__contract_type_id',
            ChoiceType::class,
            array(
                'label'       => 'Contract Type',
                'placeholder' => 'Any',
                'choices'     => array_flip($this->getContractTypes()),//$this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($contractTypeDimensionId, $this->container),
                'attr'        => array('class' => 'fa-select-white')
            )
        )
        ->add(
            'item__category_id',
            ChoiceType::class,
            array(
                'choices'     => array_flip($jobCategoryArray),
                'label'       => 'Job categories',
                'attr' => array('class' => 'fa-select-white')
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
        $defDistance = '';$getDefaultRadius = $searchParams = array();

        $categoryId   = '';
        if($this->request->get('category_id')) {
            $searchParams['item__category_id'] = $this->request->get('category_id');
        }
        if($this->request->get('location')) {
            $searchParams['item__location'] = $this->request->get('location');
        }
        
        $getDefaultRadius = $this->em->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($searchParams, $this->container);
        $defDistance = ($getDefaultRadius)?$getDefaultRadius:'';

        $form->add(
            'item__distance',
            ChoiceType::class,
            array(
                'choices' => array_flip($this->em->getRepository('FaEntityBundle:Location')->getDistanceOptionsArray($this->container)),
                'empty_data' => $defDistance,
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
        return 'fa_landing_page_jobs_search';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_landing_page_jobs_search';
    }

    /**
     * Get contract types.
     */
    private function getContractTypes()
    {
        return array(
            '2445' => 'Full time',
            '2444' => 'Part time',
            '2448' => 'Contract',
            '2447' => 'Weekend',
            '2446' => 'Evenings',
            '2451' => 'Home Working',
            '2450' => 'Freelance',
            '2452' => 'Wanted',
            '2449' => 'Temporary',
        );
    }
}
