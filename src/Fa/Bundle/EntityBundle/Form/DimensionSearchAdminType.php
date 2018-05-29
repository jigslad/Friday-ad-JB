<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Entity search type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DimensionSearchAdminType extends AbstractType
{
    /**
     * Entity manager class object.
     *
     * @var object
     */
    protected $entityManager;

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container     = $container;
        $this->entityManager = $this->container->get('doctrine')->getManager();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('entity__name', TextType::class, array('required' => false))
        ->add('category_dimension__category__id', HiddenType::class, array('data' => ''))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 1))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4))
        ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'))
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'))
        ->add('search', SubmitType::class, array('label' => 'Search'));
    }

    /**
     * On pre set data.
     *
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $this->setCategoryDimension($form, null, -1);
    }

    /**
     * On pre submit.
     *
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $categoryId = $this->getCategoryId(null, $data);

        $this->setCategoryDimension($form, (isset($data['category_dimension__id']) ? $data['category_dimension__id']: null), $categoryId);
    }

    /**
     * Set category dimension.
     *
     * @param object $form
     * @param string $data
     * @param string $categoryId
     */
    private function setCategoryDimension($form, $data = null, $categoryId = null)
    {
        $choices = $this->entityManager->getRepository('FaEntityBundle:CategoryDimension')->getDimesionByCategoryHierarchyArray(($categoryId ? $categoryId : '-1'));

        $form->add(
            'category_dimension__id',
            ChoiceType::class,
            array(
                'multiple'    => true,
                'choices'     => array_flip($choices),
                'placeholder' => 'Select Category Dimension',
                'data'        => $data
            )
        );
    }

    /**
     * Get category id.
     *
     * @param string $form
     * @param string $data
     *
     * @return string
     */
    private function getCategoryId($form = null, $data = null)
    {
        $categoryId = null;
        $category4  = null;
        $category3  = null;
        $category2  = null;
        $category1  = null;

        if ($form) {
            $category1  = $form->get('category_1')->getData();
            $category2  = $form->get('category_2')->getData();
            $category3  = $form->get('category_3')->getData();
            $category4  = $form->get('category_4')->getData();
        } else if ($data) {
            $category1  = isset($data['category_1']) ? $data['category_1'] : null;
            $category2  = isset($data['category_2']) ? $data['category_2'] : null;
            $category3  = isset($data['category_3']) ? $data['category_3'] : null;
            $category4  = isset($data['category_4']) ? $data['category_4'] : null;
        }

        if ($category4) {
            $categoryId = $category4;
        } elseif ($category3) {
            $categoryId = $category3;
        } elseif ($category2) {
            $categoryId = $category2;
        } elseif ($category1) {
            $categoryId = $category1;
        }

        return $categoryId;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_entity_dimension_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_entity_dimension_search_admin';
    }
}
