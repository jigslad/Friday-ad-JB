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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Form\EventListener\AddDomicileChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddTownChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


/**
 * AdAdminType form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdSearchAdminType extends AbstractType
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
        ->add('user__first_name', TextType::class, array('required' => false))
        ->add('user__last_name', TextType::class, array('required' => false))
        ->add('user__email', TextType::class, array('required' => false))
        ->add('user__phone', TextType::class, array('required' => false))
        ->add('user__paypal_email', TextType::class, array('required' => false))
        ->add(
            'user__role',
            ChoiceType::class,
            array(
                'multiple'  => false,
                'expanded'  => false,
                'mapped'    => false,
                'placeholder' => 'User type',
                'choices'   => array_flip(RoleRepository::getUserTypes())
            )
        )
         ->add('ad__id', TextType::class, array('required' => false))
         ->add('ad__ti_ad_id', TextType::class, array('required' => false))
         ->add('ad__title', TextType::class, array('required' => false))
         ->addEventSubscriber(new AddDatePickerFieldSubscriber('ad__created_at_from'))
         ->addEventSubscriber(new AddDatePickerFieldSubscriber('ad__created_at_to'))
         ->addEventSubscriber(new AddAutoSuggestFieldSubscriber($this->container, 'ad__category', 'ad__category_json', 'FaEntityBundle:Category'))
         ->add(
             'ad__status',
             EntityType::class,
             array(
                'class' => 'FaEntityBundle:Entity',
                'choice_label' => 'name',
                'placeholder' => 'Ad status',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder(EntityRepository::ALIAS)
                    ->where(EntityRepository::ALIAS.'.category_dimension = '.EntityRepository::AD_STATUS_ID)
                    ->orderBy(EntityRepository::ALIAS.'.name', 'ASC');
                }
             )
         )
         ->add(
             'user__status',
             EntityType::class,
             array(
                 'class' => 'FaEntityBundle:Entity',
                 'choice_label' => 'name',
                 'placeholder' => 'User status',
                 'query_builder' => function (EntityRepository $er) {
                     return $er->createQueryBuilder(EntityRepository::ALIAS)
                     ->where(EntityRepository::ALIAS.'.category_dimension = '.EntityRepository::USER_STATUS_ID)
                     ->orderBy(EntityRepository::ALIAS.'.name', 'ASC');
                 }
            )
         )
         ->add(
             'ad_user_package__package',
             EntityType::class,
             array(
                 'multiple' => true,
                 'class' => 'FaPromotionBundle:Package',
                 'choice_label' => 'filter_title',
                 'query_builder' => function (PackageRepository $er) {
                     return $er->createQueryBuilder(PackageRepository::ALIAS)
                     ->where(PackageRepository::ALIAS.'.status = 1')
                     ->andWhere(PackageRepository::ALIAS.'.package_for = :package_for')
                     ->setParameter('package_for', 'ad')
                     ->orderBy(PackageRepository::ALIAS.'.category_name', 'ASC');
                 }
            )
         )
         ->addEventSubscriber(new AddDomicileChoiceFieldSubscriber($this->container, false, 'ad_locations__location_domicile__id'))
         ->addEventSubscriber(new AddTownChoiceFieldSubscriber($this->container, false, 'ad_locations__location_town__id', 'ad_locations__location_domicile__id', array('multiple' => true)))
         ->add(
             'ad__is_feed_ad',
             ChoiceType::class,
             array(
                'multiple'  => true,
                'expanded'  => true,
                'mapped'    => false,
                'choices'   => array('Feed Ad' => 1)
             )
         )
         ->add(
             'ad__is_detached_ad',
             ChoiceType::class,
             array(
                 'multiple'  => true,
                 'expanded'  => true,
                 'mapped'    => false,
                 'choices'   => array('Detached Ad' => 1)
             )
         )
         ->add('payment_transaction__payment__cart_code', TextType::class, array('required' => false))
         ->add('search', SubmitType::class);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_ad_ad_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_ad_search_admin';
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     *
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'csrf_field_name' => '_token',
        ));
    }
}
