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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\EntityBundle\Form\EventListener\AddDomicileChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddTownChoiceFieldSubscriber;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Header image admin search type form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class HeaderImageAdminSearchType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager.
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
        ->add(
            'header_image__status',
            ChoiceType::class,
            array(
                'choices' => array_flip(EntityRepository::getStatusArray($this->container))
            )
        )
        ->add(
            'category__id',
            ChoiceType::class,
            array(
                'placeholder' => 'Select category',
                'choices'   => array_flip($this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1)),
            )
        )
        ->add(
            'header_image__screen_type',
            ChoiceType::class,
            array(
                'placeholder' => 'Select screen type',
                'choices'   => array_flip($this->em->getRepository('FaContentBundle:HeaderImage')->getScreenType())
            )
        )
        ->addEventSubscriber(new AddDomicileChoiceFieldSubscriber($this->container, false, 'location_domicile__id'))
        ->addEventSubscriber(new AddTownChoiceFieldSubscriber($this->container, false, 'location_town__id', 'location_domicile__id'))
        ->add('search', SubmitType::class);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_content_header_image_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_content_header_image_search_admin';
    }
}
