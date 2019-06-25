<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
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
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

/* Upsell search type form.
 *
 * @author Chaitra Bhat <chaitra.bhat@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class CategoryUpsellAdminType extends AbstractType
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
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $upsellCategories = null;

        $currency = CommonManager::getCurrencyCode($this->container);
        $categoriesArray = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1);

        $builder->add('category', EntityType::class, array(
            'class' => 'FaEntityBundle:Category',
            'choice_label' => 'name',
            'placeholder' => 'Category',
            'query_builder' => function (CategoryRepository $er) {
                return $er->createQueryBuilder(CategoryRepository::ALIAS)
                ->where(CategoryRepository::ALIAS . '.lvl = 1')
                ->andWhere(CategoryRepository::ALIAS . '.status = 1')
                ->orderBy(CategoryRepository::ALIAS . '.name', 'ASC');
            },
            'required' => true,
            'constraints' => new NotBlank(array(
                'message' => $this->translator->trans('Please select a category.', array(), 'validators')
            ))
        ))
        ->add('upsell', EntityType::class, array(
            'class' => 'FaPromotionBundle:Upsell',
            'choice_label' => 'title',
            'placeholder' => 'Upsell',
            'query_builder' => function (UpsellRepository $er) {
                return $er->createQueryBuilder(UpsellRepository::ALIAS)
                ->where(UpsellRepository::ALIAS . '.status = 1')
                ->orderBy(UpsellRepository::ALIAS . '.title', 'ASC');
            },
            'required' => true,
            'constraints' => new NotBlank(array(
                'message' => $this->translator->trans('Please select an upsell.', array(), 'validators')
            ))
        ))
        ->add('show_in_filters')
        ->add('save', SubmitType::class)
        ->add('saveAndNew', SubmitType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function ($event) {
            $categoryUpsell = $event->getData();
            $form = $event->getForm();

            $form->add('price', NumberType::class, array(
                'required' => false,
                'data' => (empty($categoryUpsell->getPrice()) ? 0 : $categoryUpsell->getPrice()),
            ));
        });

        $builder->addEventListener(FormEvents::SUBMIT, array(
            $this,
            'onSubmit'
        ));
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fa\Bundle\PromotionBundle\Entity\CategoryUpsell'
        ));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_promotion_category_upsell_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_promotion_category_upsell_admin';
    }

    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $categoryUpsellObj = $this->em->getRepository('FaPromotionBundle:CategoryUpsell')->findBy(
            [
                'category' => $form['category']->getData(),
                'upsell' => $form['upsell']->getData()
            ]
        );

        if ((empty($data->getId()) && ! empty($categoryUpsellObj)) || (! empty($data->getId()) && ! empty($categoryUpsellObj) && $categoryUpsellObj[0]->getId() != $data->getId())) {
            $form->get('upsell')->addError(new FormError('The category and upsell is already mapped.'));
            return false;
        }

        return true;
    }
}
