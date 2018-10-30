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

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\ContentBundle\Repository\SeoToolOverrideRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\ContentBundle\Entity\SeoToolOverride;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Seo tool admin type form.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolOverrideAdminType extends AbstractType
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
             ->add('h1_tag', TextType::class, array('label' => 'H1 Tag', 'required' => false))
            ->add('meta_description', TextType::class, array('label' => 'Meta Description', 'required' => false))
            ->add('page_title', TextType::class, array('label' => 'Page Title', 'required' => false))
            ->add('page_url', TextType::class, array('label' => 'Page Url', 'required' => false))
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container))
                )
            )
            ->add('canonical_url', TextType::class, array('label' => 'Canonical url', 'required' => false))
            ->add('no_index', CheckboxType::class, array('label' => 'No Index', 'required' => false))
            ->add('no_follow', CheckboxType::class, array('label' => 'No Follow', 'required' => false))
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * On submit.
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $form      = $event->getForm();
        $seoRule   = $event->getData();
        $seoRuleId = null;

        if ($seoRule && $seoRule->getId()) {
            $seoRuleId = $seoRule->getId();
        }

        $pageUrl = $form->get('page_url')->getData();
        $pageUrl = trim($pageUrl);

        if ($pageUrl != '') {
            $objSeoToolOverride = $this->em->getRepository('FaContentBundle:SeoToolOverride')->findSeoRuleByPageUrlOnly($pageUrl, $this->container);

            if ($objSeoToolOverride && $objSeoToolOverride->getId() != $seoRuleId) {
                $form->get('page_url')->addError(new FormError('Active seo data for this page already present.'));
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
                'data_class' => 'Fa\Bundle\ContentBundle\Entity\SeoToolOverride'
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
        return 'fa_content_seo_tool_override_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_seo_tool_override_admin';
    }
}
