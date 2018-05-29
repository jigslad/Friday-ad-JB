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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Static block admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class StaticBlockAdminType extends AbstractType
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
            ->add('name', TextType::class, array('required' => false))
            ->add('description', TextareaType::class, array('required' => true))
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'choices' => array_flip(array(StaticPageRepository::STATIC_BLOCK_GA_CODE_ID => 'Google code', StaticPageRepository::STATIC_BLOCK_TYPE_ID => 'Static Block'))
                 )
            )
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
                'validation_groups' =>  array('static_block'),
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
        return 'fa_content_static_block_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_static_block_admin';
    }
}
