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
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is user configuration rule form.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPackageAdminType extends AbstractType
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
     * @param object $container Container instance.
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
        $userBusinessCategoryId = $options['data']['category_id'];
        $userRoleId = $user->getRole()->getId();
        $builder->add('user_id', HiddenType::class, array('mapped' => false, 'data' => $options['data']['user_id']))
        ->add('is_auto_renew', HiddenType::class, array('mapped' => false))
        ->add('category_id', HiddenType::class, array('mapped' => false, 'data' => $options['data']['category_id']))
        ->add('profile_exposure_category_id', HiddenType::class, array('mapped' => false))
        ->add('zip', HiddenType::class, array('mapped' => false))
        ->add('isValidCatForm', HiddenType::class, array('mapped' => false))
        ->add(
            'package',
            ChoiceType::class,
            array(
                        'choices'  => array_flip($this->getPackages($userBusinessCategoryId, $userRoleId)),
                        'mapped'   => false,
                        'multiple' => false,
                        'expanded' => true,
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
        
        if ($form->isValid() && $form->get('isValidCatForm')->getData()) {
            $package_id = $form->get('package')->getData();
            $user_id    = $form->get('user_id')->getData();
            $user_id    = $form->get('user_id')->getData();
            $postCode   = $form->get('zip')->getData();
            $profileExposureCategoryId = $form->get('profile_exposure_category_id')->getData();
            $businessCategoryId = $form->get('category_id')->getData();
            $is_auto_renew = $form->get('is_auto_renew')->getData();
            $user       = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('id' => $user_id));
            $package    = $this->em->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $package_id));
            
            // update zip or business category id.
            if ($user && ($postCode || $businessCategoryId)) {
                $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                if ($postCodeObj && $postCodeObj->getTownId()) {
                    $townObj = $this->em->getRepository('FaEntityBundle:Location')->find($postCodeObj->getTownId());
                    $user->setZip($postCode);
                    $user->setLocationTown($townObj);
                    $user->setLocationDomicile($townObj->getParent());
                    $user->setLocationCountry($this->em->getReference('FaEntityBundle:Location', LocationRepository::COUNTY_ID));
                }
                if ($businessCategoryId) {
                    $user->setBusinessCategoryId($businessCategoryId);
                }
                $this->em->persist($user);
                $this->em->flush($user);
            }
            // update profile exposure category id.
            if ($user && $profileExposureCategoryId) {
                $userSite = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
                if ($userSite) {
                    $userSite->setProfileExposureCategoryId($profileExposureCategoryId);
                    $this->em->persist($userSite);
                    $this->em->flush($userSite);
                }
            }
            // assign user package.
            if ($user && $package) {
                $this->em->getRepository('FaUserBundle:UserPackage')->assignPackageToUser($user, $package, 'choose-package-backend', null, $is_auto_renew, $this->container);
            }
        }
    }

    /**
     * Get packages.
     *
     * @param object $userBusinessCategoryId
     * @param object $userRoleId
     */
    public function getPackages($userBusinessCategoryId, $userRoleId)
    {
        return $this->em->getRepository('FaPromotionBundle:Package')->getShopPackageArrayByCategory($userBusinessCategoryId, $userRoleId, false);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_user_package_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_package_admin';
    }
}
