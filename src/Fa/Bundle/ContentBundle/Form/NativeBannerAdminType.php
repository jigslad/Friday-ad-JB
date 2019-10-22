<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\ContentBundle\Entity\NativeBanner;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * Native Banner admin type form.
 *
 * @author Jigar lad <jigar.lad@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class NativeBannerAdminType extends AbstractType
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
        ->add('title', TextType::class,array(
            'attr' => array(
                'placeholder'=>'Advertisement',
                'value'=>'Advertisement',
                'readonly'=>true,
            ),
        ))
            ->add('createdAt',DateType::class)
            ->add('updatedAt',DateType::class)
        ->add('device', ChoiceType::class, array(
            'choices' => array(
                'Desktop'=>0,
                'Mobile'=>1,
                'Both'=>2,
            ),
            'multiple' => true,
            'expanded' => true,
            'required' => true
        ));

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
        $builder
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);
        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'))
            ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'))
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
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

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $this->postValidation($event);
        if ($form->isValid()) {
            $categoryId = $this->getCategoryId($form);
            if ($categoryId > 0) {
                $data->setCategory($this->em->getReference('FaEntityBundle:Category', $categoryId));
            } else {
                $data->setCategory(null);
            }
        }
        echo '<pre>';
        var_dump($event->getData());
        exit();
    }

    /**
     * Post submit.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPostSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
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
                'data_class' => 'Fa\Bundle\ContentBundle\Entity\NativeBanner',
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
        return 'fa_content_native_banner_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_native_banner_admin';
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

    /**
     * Add category fields.
     *
     * @param object $form    Form object.
     * @param object $native_banner  NativeBanner object.
     */
    private function addCategoryField($form, $native_banner)
    {
        $categoryId = null;
        if ($native_banner instanceof NativeBanner) {
            if ($native_banner->getCategory()) {
                $categoryId = $native_banner->getCategory()->getId();
            }
        } else {
            $categoryId = $this->getCategoryId($native_banner, true);
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
     * Add location field validation.
     *
     * @param object $form Form instance.
     */
    protected function postValidation(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $categoryId = $this->getCategoryId($form);
        $isValid       = true;
        return $isValid;
    }
}
