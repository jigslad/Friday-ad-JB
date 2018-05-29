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
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Fa\Bundle\EntityBundle\Entity\Entity;
use Symfony\Component\Form\FormError;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Dimension admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DimensionAdminType extends AbstractType
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
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container     = $container;
        $this->entityManager = $this->container->get('doctrine')->getManager();
        $this->translator    = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$builder->getForm()->getData()->getId()) {
            $builder->addEventSubscriber(new AddCategoryChoiceFieldSubscriber(
                $this->container,
                1,
                'category',
                array(
                    'required' => true,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please select atleast one category', array(), 'validators'))))
                )
            ))
            ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
            ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
            ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4));
        }

        $builder->add('name', TextType::class, array('required' => false, 'label' => 'Value', 'attr'=> array('field-help' => 'Value can be anything based on selected category dimension for e.g. "Samsung" If dimension is "Brands", "XL" if dimension is "Size"')))
                ->add('min', TextType::class, array('required' => false, 'attr' => array('field-help' => 'Used if you want to define range for any dimension for e.g. "Net Profit"')))
                ->add('max', TextType::class, array('required' => false, 'attr' => array('field-help' => 'Used if you want to define range for any dimension for e.g. "Turnover"')))
                ->add('seo_value', TextType::class, array('required' => false, 'label' => 'Seo Value',))
                ->add('save', SubmitType::class)
                ->add('saveAndNew', SubmitType::class);

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));

        if (!$builder->getForm()->getData()->getId()) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
            $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        }
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

        $this->setCategoryDimension($form, $data['category_dimension'], $categoryId);
    }

    /**
     * Set category dimension.
     *
     * @param string $form
     * @param string $data
     * @param string $categoryId
     */
    private function setCategoryDimension($form, $data = null, $categoryId = null)
    {
        $choices = $this->entityManager->getRepository('FaEntityBundle:CategoryDimension')->getDimesionByCategoryHierarchyArray(($categoryId ? $categoryId : '-1'));

        $form->add(
            'category_dimension',
            ChoiceType::class,
            array(
                'multiple'    => false,
                'choices'     => array_flip($choices),
                'placeholder' => 'Select Category Dimension',
                'required'    => true,
                'mapped'      => false,
                'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please select category dimension', array(), 'validators')))),
                'data'        => $data
            )
        );
    }

    /**
     * On post submit.
     *
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $entity = $event->getData();

        if (!$entity->getId() && $event->getForm()->isValid()) {
            $categoryDimension = $this->entityManager->getRepository('FaEntityBundle:CategoryDimension')->findOneBy(array('id' => $event->getForm()->get('category_dimension')->getData()));

            $entity = new Entity();
            $entity->setCategoryDimension($categoryDimension);
            $entity->setStatus(1);
            $entity->setName($event->getForm()->get('name')->getData());
            $entity->setMin($event->getForm()->get('min')->getData());
            $entity->setMax($event->getForm()->get('max')->getData());
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }
    }

    /**
     * On submit.
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $entity = $event->getData();
        $form   = $event->getForm();

        if ($entity->getId()) {
            $categoryDimensionId = $entity->getCategoryDimension()->getId();
        } else {
            $categoryDimensionId = $form->get('category_dimension')->getData();
        }

        if (!$form->get('name')->getData() && !$form->get('min')->getData() && !$form->get('max')->getData()) {
            $form->get('name')->addError(new FormError($this->translator->trans('For category dimension either value or min or max is required.', array(), 'validators')));
            return;
        }

        /* Commented for FFR-1269
        if ($form->get('name')->getData()) {
            if ($this->entityManager->getRepository('FaEntityBundle:Entity')->getEntityByTypeAndName($categoryDimensionId, $form->get('name')->getData(), $entity->getId())) {
                $form->get('name')->addError(new FormError($this->translator->trans('For selected category dimension this value already added.', array(), 'validators')));
            }
        }*/

        if ($form->get('min')->getData()) {
            if ($this->entityManager->getRepository('FaEntityBundle:Entity')->getEntityByTypeAndMin($categoryDimensionId, $form->get('min')->getData(), $entity->getId())) {
                $form->get('min')->addError(new FormError($this->translator->trans('For selected category dimension this minimum value already added.', array(), 'validators')));
            }
        }

        if ($form->get('max')->getData()) {
            if ($this->entityManager->getRepository('FaEntityBundle:Entity')->getEntityByTypeAndMax($categoryDimensionId, $form->get('max')->getData(), $entity->getId())) {
                $form->get('max')->addError(new FormError($this->translator->trans('For selected category dimension this maximum value already added.', array(), 'validators')));
            }
        }
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\EntityBundle\Entity\Entity'
            )
        );
    }

    /**
     * Get category id.
     *
     * @param string $form
     * @param string $data
     *
     * @return object
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
        return 'fa_entity_dimension_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_entity_dimension_admin';
    }
}
