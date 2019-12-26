<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\UserBundle\Repository\UserPackageRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * This is user boost overide form.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version v1.0
 */
class BoostOverideType extends AbstractType
{
    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Secutiry encoder.
     *
     * @var object
     */
    private $security_encoder;

    /**
     * Constructor.
     *
     * @param Doctrine                $doctrine         Doctrine object.
     * @param EncoderFactoryInterface $security_encoder Object.
     */
    public function __construct(Doctrine $doctrine, EncoderFactoryInterface $security_encoder)
    {
        $this->em                = $doctrine->getManager();
        $this->security_encoder  = $security_encoder;
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPresetData'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onSubmitData'));
        $builder->add('save', SubmitType::class);
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
            //'data_class' => 'Fa\Bundle\UserBundle\Entity\UserPackage',
            'validation_groups' => array('user-boost-overide'),
        ));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_boost_overide_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_boost_overide_admin';
    }

    /**
     * This function is called on preset data event of form.
     *
     * @param FormEvent $event object.
     *
     * @return void|Query object.
     */
    public function onPresetData(FormEvent $event)
    {
        $user = $event->getData();
        $form = $event->getForm();
        $userPackageBoostDetails = array();

        if ($user->getId()) {
            $userPackageBoostDetails = $this->getUserPackageBoostCount($user);
            $form->add('user_package_id', HiddenType::class, array('mapped'=>false,'data'=>($userPackageBoostDetails)?$userPackageBoostDetails['id']:''));
            $form->add(
                'boost_overide',
                TextType::class,
                array(
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Overide boost count', 
                    'data' => ($userPackageBoostDetails)?$userPackageBoostDetails['count']:0
                )
            );
            $form->add('is_reset_boost_count', CheckboxType::class, array('label' => 'Revert to default on renewal date?', 'required' => false));
        }
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmitData(FormEvent $event)
    {
        $user = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            //$user->setStatus($form->get('user_status')->getData());
        }
    }

    public function getUserPackageBoostCount($user)
    {
        $userPackageBoostDetail = array();

        $getUserPackageBoostDetail  = $this->em->getRepository('FaUserBundle:UserPackage')->checkUserHasBoostPackage($user->getId());

        if ($getUserPackageBoostDetail) {
            $userPackageBoostDetail['count'] = ($getUserPackageBoostDetail[0]['boost_overide'])?$getUserPackageBoostDetail[0]['boost_overide']:$getUserPackageBoostDetail[0]['monthly_boost_count'];
            $userPackageBoostDetail['id'] = $getUserPackageBoostDetail[0]['id'];
        }

        return $userPackageBoostDetail;
    }
}
