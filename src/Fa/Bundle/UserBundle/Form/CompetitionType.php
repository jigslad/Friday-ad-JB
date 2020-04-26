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
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used for entering competition.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CompetitionType extends AbstractType
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
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
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
            ->add(
                'day',
                ChoiceType::class,
                array(
                    'label' => 'Date of birth',
                    'mapped' => false,
                    'choices' => array_flip($this->getDayChoices()),
                    'attr' => array('class' => 'select-control'),
                    'placeholder' => 'DD',
                )
            )
            ->add(
                'month',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'choices' => array_flip(CommonManager::getMonthChoices()),
                    'attr' => array('class' => 'select-control'),
                    'placeholder' => 'MM',
                )
            )
            ->add(
                'year',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'choices' => array_flip($this->getYearChoices()),
                    'attr' => array('class' => 'select-control'),
                    'placeholder' => 'YY',
                )
            )
            ->add(
                'interest',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => array_flip($this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType('260', $this->container)),
                    'constraints' => new NotBlank(array('message' => $this->translator->trans('Please select at least one interest.', array(), 'validators'))),
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Enter'));

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'))
            ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $competition = $event->getData();
        $form        = $event->getForm();
        $interest    = $form->get('interest')->getData();
        $day         = $form->get('day')->getData();
        $month       = $form->get('month')->getData();
        $year        = $form->get('year')->getData();

        if ($form->isValid()) {
            asort($interest);
            $date = $year.'-'.$month.'-'.$day;
            $interest = implode(',', $interest);
            $loggedInUser = (CommonManager::isAuth($this->container) ? CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser() : null);
            $competition->setUser($this->em->getReference('FaUserBundle:User', $loggedInUser->getId()));
            $competition->setStatus(1);
            $competition->setInterest($interest);
            $competition->setBirthDate($date);
        }
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\Competition',
                'translation_domain' => 'frontend-competition',
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
        return 'fa_user_competition';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_competition';
    }

    /**
     * Get day choices.
     *
     * @return array
     */
    public function getDayChoices()
    {
        $dayArray = array();

        for ($i = 1; $i <= 31; $i++) {
            $dayArray[$i] = $i;
        }

        return $dayArray;
    }

    /**
     * Get year choices.
     *
     * @return array
     */
    public function getYearChoices()
    {
        $yearArray = array();

        for ($i = date('Y'); $i >= 1920; $i--) {
            $yearArray[$i] = $i;
        }

        return $yearArray;
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $day = $form->get('day')->getData();
        $month = $form->get('month')->getData();
        $year = $form->get('year')->getData();

        if (!$day) {
            $form->get('day')->addError(new FormError('Please select date of birth.'));
        }
        if (!$month) {
            $form->get('month')->addError(new FormError('Please select date of birth.'));
        }
        if (!$year) {
            $form->get('year')->addError(new FormError('Please select date of birth.'));
        }
    }
}
