<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\EmailBundle\Repository\EmailTemplateScheduleRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Email template schedule admin type.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class EmailTemplateScheduleAdminType extends AbstractType
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
                'time',
                ChoiceType::class,
                array(
                    'choices' => array_flip($this->getTimeChoices()),
                    'placeholder'  => 'Select time',
                    'required'  => false,
                )
            )
            ->add(
                'frequency',
                ChoiceType::class,
                array(
                    'choices' => array_flip($this->getFrequencyChoices()),
                    'expanded' => true,
                )
            )
            ->add(
                'daily_recur_day',
                TextType::class,
                array('label' => 'Recur Every')
            )
            ->add(
                'weekly_recur_day',
                TextType::class,
                array('label' => 'Recur Every')
            )
            ->add(
                'weekly_days',
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->getWeeklyDayChoices()),
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'   => false,
                    'constraints' => array(new NotBlank(array('groups'   => array('weekly'), 'message' => $this->translator->trans('Please select weekly days to recur on.', array(), 'validators'))))
                )
            )
            ->add(
                'months',
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->getMonthChoices()),
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'   => false,
                    'constraints' => array(new NotBlank(array('groups'   => array('monthly'), 'message' => $this->translator->trans('Please select months to recur on.', array(), 'validators'))))
                )
            )
            ->add(
                'monthly_days',
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->getMonthlyDayChoices()),
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'   => false,
                    'constraints' => array(new NotBlank(array('groups'   => array('monthly'), 'message' => $this->translator->trans('Please select monthly days to recur on.', array(), 'validators'))))
                )
            )
            ->add(
                'after_given_time',
                TextType::class,
                array('label' => 'Run After')
            )
            ->add(
                'is_after_given_time_recurring',
                ChoiceType::class,
                array(
                    'choices'  => array('One Time' => '0', 'Recurring' => '1'),
                    'expanded' => true,
                    'multiple' => false,
                    'label' => 'Is Run After Time Recurring?'
                )
            )
            ->addEventSubscriber(new AddDatePickerFieldSubscriber('date', array('label' => 'Date', 'required' => true, 'constraints' => array(new NotBlank(array('groups'   => array('one_time'), 'message' => $this->translator->trans('Please select monthly days to recur on.', array(), 'validators')))))))
            ->add('save', SubmitType::class)
            ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'))
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
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
                'data_class' => 'Fa\Bundle\EmailBundle\Entity\EmailTemplateSchedule',
                'validation_groups' => function (FormInterface $form) {
                    $data = $form->getData();
                    $groups = array();
                    switch ($data->getFrequency()) {
                        case EmailTemplateScheduleRepository::FREQUENCY_ONE_TIME:
                            $groups = array('one_time');
                            break;
                        case EmailTemplateScheduleRepository::FREQUENCY_DAILY:
                            $groups = array('daily');
                            break;
                        case EmailTemplateScheduleRepository::FREQUENCY_WEEKLY:
                            $groups = array('weekly');
                            break;
                        case EmailTemplateScheduleRepository::FREQUENCY_MONTHLY:
                            $groups = array('monthly');
                            break;
                        case EmailTemplateScheduleRepository::FREQUENCY_AFTER_GIVEN_TIME:
                            $groups = array('after_given_time');
                            break;
                        default:
                            $groups = array('all');
                            break;
                    }
                    return $groups;
                },
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
        return 'fa_email_template_email_template_schedule_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_email_template_email_template_schedule_admin';
    }
    
    /**
     * Get frequency choices.
     *
     * @return array
     */
    private function getFrequencyChoices()
    {
        return array(
            EmailTemplateScheduleRepository::FREQUENCY_ONE_TIME => 'One Time',
            EmailTemplateScheduleRepository::FREQUENCY_DAILY    => 'Daily',
            EmailTemplateScheduleRepository::FREQUENCY_WEEKLY   => 'Weekly',
            EmailTemplateScheduleRepository::FREQUENCY_MONTHLY  => 'Monthly',
            EmailTemplateScheduleRepository::FREQUENCY_AFTER_GIVEN_TIME  => 'Run After Given Time',
        );
    }

    /**
     * Get time choices.
     *
     * @return array
     */
    private function getTimeChoices()
    {
        $timeArray = array();

        for ($i=0; $i<=23; $i++) {
            $timeArray[$i] = $i;
        }
        return $timeArray;
    }

    /**
     * Pre set data.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPreSetData(FormEvent $event)
    {
        $emailTemplateSchedule = $event->getData();
        $form = $event->getForm();
        if ($emailTemplateSchedule->getFrequency() == EmailTemplateScheduleRepository::FREQUENCY_WEEKLY) {
            $form->add(
                'weekly_days',
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->getWeeklyDayChoices()),
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'   => false,
                    'data'      => explode(',', $emailTemplateSchedule->getWeeklyDay()),
                    'constraints' => array(new NotBlank(array('groups'   => array('weekly'), 'message' => $this->translator->trans('Please select weekly days to recur on.', array(), 'validators'))))
                )
            );
        } elseif ($emailTemplateSchedule->getFrequency() == EmailTemplateScheduleRepository::FREQUENCY_MONTHLY) {
            $form->add(
                'months',
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->getMonthChoices()),
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'   => false,
                    'constraints' => array(new NotBlank(array('groups'   => array('monthly'), 'message' => $this->translator->trans('Please select monthhs to recur on.', array(), 'validators')))),
                    'data'      => explode(',', $emailTemplateSchedule->getMonth()),
                )
            );

            $form->add(
                'monthly_days',
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->getMonthlyDayChoices()),
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'   => false,
                    'constraints' => array(new NotBlank(array('groups'   => array('monthly'), 'message' => $this->translator->trans('Please select monthly days to recur on.', array(), 'validators')))),
                    'data'      => explode(',', $emailTemplateSchedule->getMonthlyDay()),
                )
            );
        }
    }

    /**
     * Post submit.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPostSubmit(FormEvent $event)
    {
        $emailTemplateSchedule = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            $date = $form->get('date')->getData();
            $weeklyDays = $form->get('weekly_days')->getData();
            $months = $form->get('months')->getData();
            $monthlyDays = $form->get('monthly_days')->getData();
            $emailTemplate = $this->em->getRepository('FaEmailBundle:EmailTemplate')->find($this->container->get('request_stack')->getCurrentRequest()->get('id'));
            $emailTemplateSchedule->setEmailTemplate($emailTemplate);

            if ($date) {
                $emailTemplateSchedule->setDate(CommonManager::getTimeStampFromStartDate($date));
            } else {
                $emailTemplateSchedule->setDate(null);
            }
            if (count($weeklyDays)) {
                $emailTemplateSchedule->setWeeklyDay(implode(',', $weeklyDays));
            } else {
                $emailTemplateSchedule->setWeeklyDay(null);
            }

            if (count($months)) {
                $emailTemplateSchedule->setMonth(implode(',', $months));
            } else {
                $emailTemplateSchedule->setMonth(null);
            }

            if (count($monthlyDays)) {
                $emailTemplateSchedule->setMonthlyDay(implode(',', $monthlyDays));
            } else {
                $emailTemplateSchedule->setMonthlyDay(null);
            }
        }
    }

    /**
     * Get weekly day choices.
     *
     * @return array
     */
    private function getWeeklyDayChoices()
    {
        $weeklyArray = array();

        $weeklyArray['0'] = 'Sunday';
        $weeklyArray['1'] = 'Monday';
        $weeklyArray['2'] = 'Tuesday';
        $weeklyArray['3'] = 'Wednesday';
        $weeklyArray['4'] = 'Thursday';
        $weeklyArray['5'] = 'Friday';
        $weeklyArray['6'] = 'Saturday';

        return $weeklyArray;
    }

    /**
     * Get month choices.
     *
     * @return array
     */
    private function getMonthChoices()
    {
        $monthArray = array();

        $monthArray['1'] = 'January';
        $monthArray['2'] = 'February';
        $monthArray['3'] = 'March';
        $monthArray['4'] = 'April';
        $monthArray['5'] = 'May';
        $monthArray['6'] = 'June';
        $monthArray['7'] = 'July';
        $monthArray['8'] = 'August';
        $monthArray['9'] = 'September';
        $monthArray['10'] = 'Octomber';
        $monthArray['11'] = 'November';
        $monthArray['12'] = 'December';

        return $monthArray;
    }

    /**
     * Get monthly day choices.
     *
     * @return array
     */
    private function getMonthlyDayChoices()
    {
        $monthDayArray = array();

        for ($i=1; $i<=31; $i++) {
            $monthDayArray[$i] = $i;
        }
        return $monthDayArray;
    }
}
