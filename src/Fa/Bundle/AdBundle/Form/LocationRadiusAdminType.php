<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\AdBundle\Repository\LocationRadiusRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Entity\LocationRadius;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Location radius admin type form.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class LocationRadiusAdminType extends AbstractType
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
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
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
        ->add('defaultRadius', ChoiceType::class, array(
                'data'=> (($builder->getData()->getDefaultRadius()!='' || $builder->getData()->getDefaultRadius()==0) && $builder->getData()->getId())?$builder->getData()->getDefaultRadius():5,
                'empty_value' => 'Select default radius',
                'choices' => array_flip(LocationRadiusRepository::getDefaultRadius())
            ))
            ->add('extendedRadius', ChoiceType::class, array(
                'data'=>($builder->getData()->getExtendedRadius())?$builder->getData()->getExtendedRadius():0,
                'empty_value' => 'Select extended radius',
                'choices' => array_flip(LocationRadiusRepository::getExtendedRadius())
            ))
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container))
                )
            )
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

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function ($event) {
                $locationRadius = $event->getData();
                $form    = $event->getForm();
                if ($locationRadius->getId()) {
                    $this->addCategoryField($form, $locationRadius);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function ($event) {
                $locationRadius = $event->getData();
                $form    = $event->getForm();
                $this->addCategoryField($form, $locationRadius);
            }
        );

        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'))
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Add category fields.
     *
     * @param object $form    Form object.
     * @param object $locationRadius Location Radius object.
     */
    private function addCategoryField($form, $locationRadius)
    {
        $categoryId = null;
        if ($locationRadius instanceof LocationRadius) {
            if ($locationRadius->getCategory()) {
                $categoryId = $locationRadius->getCategory()->getId();
            }
        } else {
            $categoryId = $this->getCategoryId($locationRadius, true);
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
     * On submit.
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $form    = $event->getForm();
        $locationRadius = $event->getData();
        $locationcategory = array();

        $categoryId = $this->getCategoryId($form);
        $getDefRadiusFromFrm = $form->get('defaultRadius')->getData();
        $getStatusFromFrm = $form->get('status')->getData();

        if ($categoryId) {
            $category = $this->em->getRepository('FaEntityBundle:Category')->find($categoryId);
            $locationcategory = $this->em->getRepository('FaAdBundle:LocationRadius')->getLocationRadiusByCategoryId($categoryId);
            $categorylvl = $category->getLvl();
        }

        if (!empty($locationcategory)) {
            if ($form->getData()->getId() && count($locationcategory)>0 && $locationRadius->getCategory()->getId()!=$categoryId) {
                $form->get('category_'.$categorylvl)->addError(new FormError('Cannot save, record already exists.'));
            } elseif (!$form->getData()->getId()) {
                $form->get('category_'.$categorylvl)->addError(new FormError('Cannot save, record already exists.'));
            }
        }

        if (false === $getDefRadiusFromFrm || (empty($getDefRadiusFromFrm) && '0' != $getDefRadiusFromFrm)) {
            $form->get('defaultRadius')->addError(new FormError('Please select Default radius.'));
        }

        if (false === $getStatusFromFrm || (empty($getStatusFromFrm) && '0' != $getStatusFromFrm)) {
            $form->get('status')->addError(new FormError('Please select status.'));
        }
        
        if ($form->get('extendedRadius')->getData()>0 && $form->get('extendedRadius')->getData() < $form->get('defaultRadius')->getData()) {
            $form->get('extendedRadius')->addError(new FormError('Extended radius must be larger than Default radius.'));
        }
    }


    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $locationRadius = $event->getData();
        $form    = $event->getForm();
        $locationRadiusId = $form->getData()->getId();

        if (empty($locationRadiusId)) {
            $locationRadius = new LocationRadius();
        }

        if ($form->isValid()) {
            $categoryId = $this->getCategoryId($form);
            if ($categoryId > 0) {
                $locationRadius->setCategory($this->em->getReference('FaEntityBundle:Category', $categoryId));
            } else {
                $locationRadius->setCategory(null);
            }
            $locationRadius->setDefaultRadius($form->get('defaultRadius')->getData());
            $locationRadius->setExtendedRadius($form->get('extendedRadius')->getData());
            $locationRadius->setStatus($form->get('status')->getData());

            $this->em->persist($locationRadius);
            $this->em->flush();
        }
    }

    /**
      * Set default options
      *
      * @param OptionsResolver $resolver
      */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\AdBundle\Entity\LocationRadius',
                'validation_groups' => function (FormInterface $form) {
                    $data = $form->getData();
                    if (!$data->getId()) {
                        return array('new');
                    } else {
                        return array('edit');
                    }
                },
            )
        );
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_ad_location_radius_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_location_radius_admin';
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
