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
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is user configuration rule form.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPackageType extends AbstractType
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
     * @param object       $container    Container instance.
     *
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
        $user    = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('id' => $options['data']['user_id']));
        $package = $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);
        $userBusinessCategoryId = $user->getBusinessCategoryId() > 0 ? $user->getBusinessCategoryId() : CategoryRepository::FOR_SALE_ID;
        $builder->add('user_id', HiddenType::class, array('mapped' => false, 'data' => $options['data']['user_id']))
        ->add('trail_enable', HiddenType::class, array('mapped' => false, 'data' => $user->getFreeTrialEnable()))
        ->add(
            'package',
            ChoiceType::class,
            array(
                        'choices'  => array_flip($this->getPackages($userBusinessCategoryId)),
                        'mapped'   => false,
                        'multiple' => false,
                        'expanded' => true,
                        'data'     => $package ? $package->getPackage()->getId() : null,
                        'attr'     => array('data-size'=>'auto')
                )
        )
        ->add('save', SubmitType::class);

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isValid()) {
            $package_id        = $form->get('package')->getData();
            $user_id           = $form->get('user_id')->getData();
            $trail_enable      = $form->get('trail_enable')->getData();
            $user              = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('id' => $user_id));
            $package           = $this->em->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $package_id));
            $allow_zero_amount = $trail_enable ? true : false;

            if ($user && $package) {
                $this->em->getRepository('FaPaymentBundle:Cart')->addSubscriptionToCart($user_id, $package_id, $this->container, true, array(), null, $allow_zero_amount);
            }
        }
    }

    /**
     * Get packages.
     *
     * @param object $userBusinessCategoryId
     */
    public function getPackages($userBusinessCategoryId)
    {
        return $this->em->getRepository('FaPromotionBundle:Package')->getShopPackageArrayByCategory($userBusinessCategoryId, true);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_user_package_choose';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_package_choose';
    }
}
