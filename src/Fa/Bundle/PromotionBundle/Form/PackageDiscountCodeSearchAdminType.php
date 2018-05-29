<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Package discount code search admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PackageDiscountCodeSearchAdminType extends AbstractType
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
        ->add('package_discount_code__code', TextType::class)
        ->add(
            'package_discount_code__status',
            ChoiceType::class,
            array(
                'choices' => array_flip(EntityRepository::getStatusArray($this->container)),
            )
        )
        ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'package_discount_code__category_id', 'package_discount_code__category_id_json', 'FaEntityBundle:Category'))
        ->add(
            'package_discount_code__package_sr_no',
            ChoiceType::class,
            array(
                'label' => 'Package type',
                'choices' => array_flip($this->em->getRepository('FaPromotionBundle:Package')->getPackageTypeArray()),
                'multiple'  => true,
                'expanded'  => true,
                'required'  => false,
            )
        )
        ->add(
            'package_discount_code__role_ids',
            ChoiceType::class,
            array(
                'required'  => false,
                'multiple'  => true,
                'expanded'  => true,
                'mapped'    => false,
                'label'     => 'User role',
                'choices'   => array_flip(RoleRepository::getUserTypes()),
            )
        )
        ->add('search', SubmitType::class, array('label' => 'Search'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_promotion_package_discount_code_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_package_discount_code_search_admin';
    }
}
