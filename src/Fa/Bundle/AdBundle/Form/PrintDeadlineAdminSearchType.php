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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Fa\Bundle\EntityBundle\Repository\LocationGroupRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * PrintDeadlineAdminSearchType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class PrintDeadlineAdminSearchType extends AbstractType
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
                'print_deadline_rule__location_group__id',
                ChoiceType::class,
                array(
                    'choices'     => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getLocationGroupsKeyValueArray()),
                    'placeholder' => 'Select Location Group',
                )
            )
            ->add('search', SubmitType::class);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_ad_print_deadline_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_print_deadline_search_admin';
    }
}
