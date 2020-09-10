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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\ContentBundle\Repository\StaticPageRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Static page admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class StaticPageAdminType extends AbstractType
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
            ->add('title', TextType::class, array('required' => true))
            ->add('description', TextareaType::class, array('required' => true))
            ->add('additional_info', TextareaType::class, array('required' => false, 'label' => 'Right side content'))
            ->add('h1_tag', TextType::class, array('label' => 'H1 Tag', 'required' => false))
            ->add('meta_description', TextType::class, array('label' => 'Meta Description', 'required' => false))
            ->add('meta_keywords', TextType::class, array('label' => 'Meta Keywords', 'required' => false))
            ->add('slug', TextType::class, array('label' => 'Slug', 'required' => false))
            ->add('page_title', TextType::class, array('label' => 'Page Title', 'required' => false))
            ->add('no_index', CheckboxType::class, array('label' => 'No Index', 'required' => false))
            ->add('no_follow', CheckboxType::class, array('label' => 'No Follow', 'required' => false))
            ->add('include_in_footer', CheckboxType::class, array('label' => 'Include link in footer', 'required' => false))
            ->add('include_in_mobile_footer', CheckboxType::class, array('label' => 'Include link in mobile footer', 'required' => false))
            ->add('canonical_url', TextType::class, array('required' => false))
            ->add('canonical_url_status', CheckboxType::class, array('label' => 'Enable canonical url?', 'required' => false))
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container))
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class)
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
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
        $staticpage = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            $staticpage->setType(StaticPageRepository::STATIC_PAGE_TYPE_ID);
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
                'data_class'        => 'Fa\Bundle\ContentBundle\Entity\StaticPage',
                'validation_groups' =>  array('default'),
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
        return 'fa_content_static_page_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_static_page_admin';
    }
}
