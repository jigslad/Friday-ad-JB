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
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Entity\Category;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Upsell search type form.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ProfileUpsellAdminType extends AbstractType
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
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $upsellCategories = null;
        $em               = $this->container->get('doctrine')->getManager();
        $currency         = CommonManager::getCurrencyCode($this->container);
        $categoriesArray  = $em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1);

        if ($builder->getData()->getId()) {
            $upsellCategories = $em->getRepository('FaEntityBundle:Category')->getCategoryByUpsellIds(array($builder->getData()->getId()));
        }
        $builder
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'choices' => array_flip(UpsellRepository::getProfileUpsellTypeArray($this->container))
                )
            )
            ->add('title')
            ->add('description')
            ->add('value')
            ->add('value1')
            ->add('upsell_for', HiddenType::class, array('data' => 'shop'))
            ->add(
                'duration',
                TextType::class,
                array(
                    'label'    => 'Duration (eg. Days = 2d, Week = 3w, Month = 1m)',
                    'required' => false
                )
            )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices'  => array_flip(EntityRepository::getStatusArray($this->container))
                )
            )
            ->add('save', SubmitType::class)
            ->add('saveAndNew', SubmitType::class);

        $container = $this->container;
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
            'data_class' => 'Fa\Bundle\PromotionBundle\Entity\Upsell'
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
        return 'fa_promotion_profile_upsell_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_profile_upsell_admin';
    }
}
