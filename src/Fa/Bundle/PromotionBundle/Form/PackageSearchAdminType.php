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
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Package search admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PackageSearchAdminType extends AbstractType
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
        ->add('package__title', TextType::class)
        ->add(
            'package__status',
            ChoiceType::class,
            array(
                'choices' => array_flip(EntityRepository::getStatusArray($this->container)),
            )
        )
        ->add('package_rule__category__id', HiddenType::class, array('data' => ''))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 1))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4))
        ->add(
            'package_rule__location_group__id',
            ChoiceType::class,
            array(
                'multiple' => true,
                'required' => false,
                'label'    => 'Location Group',
                'choices' => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getLocationGroupsKeyValueArray()),
            )
        )
        ->add(
            'package__role_id',
            EntityType::class,
            array(
                'required' => false,
                'class' => 'FaUserBundle:Role',
                'choice_label' => 'name',
                'placeholder' => 'Select user type',
                'label' => 'User type',
                'query_builder' => function (RoleRepository $er) {
                    return $er->createQueryBuilder(RoleRepository::ALIAS)
                    ->where(RoleRepository::ALIAS.'.type = :roleType')
                    ->setParameter('roleType', 'C');
                }
            )
        )
        ->add(
             'package__is_admin_package',
             ChoiceType::class,
             array(
                 'multiple'  => true,
                 'expanded'  => true,
                 'mapped'    => false,
                 'choices'   => array('Admin only package' => 1)
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
        return 'fa_promotion_package_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_package_search_admin';
    }
}
