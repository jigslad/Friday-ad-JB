<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddDomicileChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddTownChoiceFieldSubscriber;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Symfony\Component\Form\FormError;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * This is user search form.
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSearchType extends AbstractType
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
     * @param ContainerInterface $container object.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('user__id', TextType::class, array('required' => false))
        ->add('user__first_name', TextType::class, array('required' => false))
        ->add('user__last_name', TextType::class, array('required' => false))
        ->add('user__business_name', TextType::class, array('required' => false))
        ->add('user__email', TextType::class, array('required' => false))
        ->add('ad__id', TextType::class, array('required' => false))
        ->add('ad__ti_ad_id', TextType::class, array('required' => false))
        ->add('ad__nested_category', TextType::class, array(
                                                 'mapped' => false,
                                                 'label' => 'Category',
                                                 'required' => true))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('ad__created_at_from'))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('ad__created_at_to'))
        ->add('user__phone', TextType::class, array('required' => false))
        ->add('user__paypal_email', TextType::class, array('required' => false))
        ->add(
            'role__id',
            EntityType::class,
            array(
                'class'       => 'FaUserBundle:Role',
                'choice_label'    => 'role',
                'placeholder' => 'Role',
            )
        )
        ->add('ad__nested_category_json', HiddenType::class)
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
            'user__credit',
            ChoiceType::class,
            array(
                'choices'  => array('Active' => 'A', 'Used' => 'U'),
                'placeholder' => 'User credit',
            )
        )
        ->addEventSubscriber(new AddDomicileChoiceFieldSubscriber($this->container, false, 'user__location_domicile'))
        ->addEventSubscriber(new AddTownChoiceFieldSubscriber($this->container, false, 'user__location_town', 'user__location_domicile', array('multiple' => true)))
        ->add(
            'user__user_package__package_id',
            EntityType::class,
            array(
                'class' => 'FaPromotionBundle:Package',
                'choice_label' => 'name',
                'placeholder' => 'Business Profile',
                'query_builder' => function (PackageRepository $er) {
                    $pkgFor = "shop";
                    $status = "1";
                    return $er->createQueryBuilder(PackageRepository::ALIAS)
                ->where(PackageRepository::ALIAS.'.package_for = :pkgFor')
                ->setParameter('pkgFor', 'shop')
                ->andWhere(PackageRepository::ALIAS.'.status = :pkgStatus')
                ->setParameter('pkgStatus', '1');
                }
                )
            )
        ->add('search', SubmitType::class);

        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $catText  = $event->getForm()->get('ad__nested_category')->getData();
        $categories = explode(',', $catText);
        $form = $event->getForm();
        $catJson  = array();
        $rule     = $event->getData();
        foreach ($categories as $cat_id) {
            if ($cat_id) {
                $category = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $cat_id));

                if ($category) {
                    $catJson[] = array('id'=> $category->getId(), 'text' => $category->getName()." (".$category->getParent()->getName().($category->getParent()->getParent()?', '.$category->getParent()->getParent()->getName():'').')');
                }
            }
        }
        $form->add('ad__nested_category_json', HiddenType::class, array('mapped' => false, 'data' => json_encode($catJson)));

        $this->validateAdPlacedDate($form);
    }

    /**
     * Validate date.
     *
     * @param object $form Form instance.
     */
    protected function validateAdPlacedDate($form)
    {
        $adDatePlacedFrom = $form->get('ad__created_at_from')->getData();
        $adDatePlacedTo   = $form->get('ad__created_at_to')->getData();

        if (!$adDatePlacedTo) {
            $adDatePlacedTo = date('d/m/Y');
        } elseif (!$adDatePlacedFrom) {
            $adDatePlacedFrom = date('d/m/Y', strtotime("-6 month"));
        }

        if ($adDatePlacedFrom != $adDatePlacedTo) {
            $adDatePlacedFrom = date_create(str_replace('/', '-', $adDatePlacedFrom));
            $adDatePlacedTo   = date_create(str_replace('/', '-', $adDatePlacedTo));

            $diff = date_diff($adDatePlacedTo, $adDatePlacedFrom);

            if ($diff->m > 5 || $diff->y > 0) {
                $form->get('ad__created_at_from')->addError(new FormError('Difference between ad date placed from and ad date placed to should not be more then 6 months.'));
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_search_admin';
    }
}
