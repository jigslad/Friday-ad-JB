<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used to show cyber source api fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class DeliveryMethodOptionSearchAdminType extends AbstractType
{
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
        $this->container = $container;
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('delivery_method_option__name')
            ->add(
                'delivery_method_option__status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container)),
                )
            )
            ->add('search', SubmitType::class, array('label' => 'Search'))
        ;
    }

    /**
     * Get name.
     * @return string
     */
    public function getName()
    {
        return 'fa_payment_delivery_method_option_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_payment_delivery_method_option_search_admin';
    }
}
