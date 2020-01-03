<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\PromotionBundle\Entity\CategoryUpsell;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType; 
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/* Upsell search type form.
 *
 * @author Chaitra Bhat <chaitra.bhat@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class CategoryUpsellAdminType extends AbstractType
{

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager object.
     *
     * @var object
     */
    private $em;

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
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('category', HiddenType::class, array('data' => ''));
        $builder->add('upsell', EntityType::class, array(
            'class' => 'FaPromotionBundle:Upsell',
            'choice_label' => 'title',
            'placeholder' => 'Upsell',
            'query_builder' => function (UpsellRepository $er) {
                return $er->createQueryBuilder(UpsellRepository::ALIAS)
                ->where(UpsellRepository::ALIAS . '.status = 1')
                ->orderBy(UpsellRepository::ALIAS . '.title', 'ASC');
            },
            'required' => true,
            'constraints' => new NotBlank(array(
                'message' => $this->translator->trans('Please select an upsell.', array(), 'validators')
            ))
        ))
        ->add('show_in_filters', CheckboxType::class,
            array(
                'label'    => 'Status',
            ))
        ->add('save', SubmitType::class)
        ->add('saveAndNew', SubmitType::class);
        
        //category fields
        $builder->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                1,
                'category'
                )
            )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                2,
                'category'
                )
            )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                3,
                'category'
                )
            )
        ->addEventSubscriber(
            new AddCategoryChoiceFieldSubscriber(
                $this->container,
                4,
                'category'
                )
            );
        
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function ($event) {
            $categoryUpsell = $event->getData();
            $form = $event->getForm();

            $form->add('price', NumberType::class, array(
                'required' => false,
                'data' => (empty($categoryUpsell->getPrice()) ? 0 : $categoryUpsell->getPrice()),
            ));
            if ($categoryUpsell->getId()) {
                $this->addCategoryField($form, $categoryUpsell);
            }
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
        $builder->addEventListener(FormEvents::SUBMIT, array(
            $this,
            'onSubmit'
        ));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fa\Bundle\PromotionBundle\Entity\CategoryUpsell'
        ));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_promotion_category_upsell_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_category_upsell_admin';
    }
    
    /**
     * This function is called on pre submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $this->addCategoryField($form, $data);
        $event->setData($data);
    }
    
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        
        $categoryId = $this->getCategoryId($form);
        
        $categoryUpsellObj = $this->em->getRepository('FaPromotionBundle:CategoryUpsell')->findBy(
            [
                'category' => $categoryId,
            ]
        );

        if ((empty($data->getId()) && ! empty($categoryUpsellObj)) || (! empty($data->getId()) && ! empty($categoryUpsellObj) && $categoryUpsellObj[0]->getId() != $data->getId())) {
            $form->get('upsell')->addError(new FormError('An upsell has already been created for this category.'));
            return false;
        }

        return true;
    }
    
    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $categoryUpsell = $event->getData();
        $form    = $event->getForm();
        $categoryUpsellId = $form->getData()->getId();
        
        if (empty($categoryUpsellId)) {
            $categoryUpsell = new CategoryUpsell();
        }
        
        if ($form->isValid()) {
            $categoryId = $this->getCategoryId($form);
            if ($categoryId > 0) {
                $categoryUpsell->setCategory($this->em->getReference('FaEntityBundle:Category', $categoryId));
            } else {
                $categoryUpsell->setCategory(null);
            }
            
            $rootCategoryId = $form->get('category_1')->getData();
            if ($rootCategoryId > 0) {
                $categoryUpsell->setRootCategory($this->em->getReference('FaEntityBundle:Category', $rootCategoryId));
            } else {
                $categoryUpsell->setRootCategory(null);
            }
            
            if($form->get('upsell')->getData()!='') {
                $categoryUpsell->setUpsell($this->em->getRepository('FaPromotionBundle:Upsell')->find($form->get('upsell')->getData()));
            } else { $categoryUpsell->setUpsell(null); }
            
            
            $categoryUpsell->setPrice($form->get('price')->getData());
            $categoryUpsell->setShowInFilters($form->get('show_in_filters')->getData());
            
            $this->em->persist($categoryUpsell);
            $this->em->flush();
        }
    }
    
    /**
     * Add category fields.
     *
     * @param object $form    Form object.
     * @param object $categoryUpsell Category Upsell object.
     */
    private function addCategoryField($form, $categoryUpsell)
    {
        $categoryId = null;
        if ($categoryUpsell instanceof CategoryUpsell) {
            if ($categoryUpsell->getCategory()) {
                $categoryId = $categoryUpsell->getCategory()->getId();
            }
        } else {
            $categoryId = $this->getCategoryId($categoryUpsell, true);
        }
        //for category
        if ($categoryId > 0) {
            $categoryPath     = array_keys($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, true, $this->container));
            $categoryPathTemp = $categoryPath;
            
            if (count($categoryPath) > 5) {
                end($categoryPath);
                $categoryPath[4] = $categoryPath[key($categoryPath)];
            } elseif (count($categoryPath) < 5) {
                $categoryPath[count($categoryPath)+1] = $categoryPath[count($categoryPath)-1];
            }
            $categoryPath = array_slice($categoryPath, 0, 5);
            
            for ($i=1; $i < count($categoryPath); $i++) {
                $choices = array('' => 'Select Category '.$i) + ($i == 4 ? $this->em->getRepository('FaEntityBundle:Category')->getNestedChildrenKeyValueArrayByParentId($categoryPathTemp[$i-1]) :$this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($categoryPathTemp[$i-1]));
                $choices = $this->em->getRepository('FaEntityBundle:Category')->showDuplicateCategoriesForSubscriber($choices);
                $form->add(
                    'category_'.$i,
                    ChoiceType::class,
                    array(
                        'required' => false,
                        'mapped'   => false,
                        'choices'  => array_flip($choices),
                        'data'     => isset($categoryPath[$i]) ? $categoryPath[$i] : null,
                    )
                    );
            }
        }
    }
    
    /**
     * Get category id based on posted form data.
     *
     * @param object  $form        Form instance.
     * @param boolean $isArrayFlag Array flag.
     *
     * @return integer
     */
    private function getCategoryId($form, $isArrayFlag = false)
    {
        $categoryId = null;
        if (!$isArrayFlag) {
            $category1  = $form->get('category_1')->getData();
            $category2  = $form->get('category_2')->getData();
            $category3  = $form->get('category_3')->getData();
            $category4  = $form->get('category_4')->getData();
        } else {
            $category1  = isset($form['category_1']) ? $form['category_1'] : null;
            $category2  = isset($form['category_2']) ? $form['category_2'] : null;
            $category3  = isset($form['category_3']) ? $form['category_3'] : null;
            $category4  = isset($form['category_4']) ? $form['category_4'] : null;
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
}
