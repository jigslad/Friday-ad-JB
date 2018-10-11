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
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * This is user change status form.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ChangeStatusType extends AbstractType
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
        /*$builder->add(
            'user_status',
            EntityType::class,
            array(
                'class' => 'FaEntityBundle:Entity',
                'choice_label' => 'name',
                'placeholder' => 'User status',
                'mapped' => false,
                'constraints' => array(new NotBlank(array('message' => 'Please select status'))),
            )
        );*/
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPresetData'));
        //$builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmitData'));
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
            'data_class' => 'Fa\Bundle\UserBundle\Entity\User',
            'validation_groups' => array('user-change-status'),
        ));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_change_status_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_change_status_admin';
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

        if ($user->getId()) {
            $form->add(
                'user_status',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'multiple' => false,
                    'label' => 'Select status',
                    'placeholder' => 'Please select status',
                    'choices'   => array_flip($this->getUserStatuses($user)),
                )
            );
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

    public function getUserStatuses($user)
    {
        $userStatuses  = $this->em->getRepository('FaEntityBundle:Entity')->geUserStatusesForCombo($user);

        if ($userStatuses) {
            foreach ($userStatuses as $key => $valueArray) {
                $userStatusesArray[$valueArray['id']] = $valueArray['name'];
            }
        }

        return $userStatusesArray;
    }
}
