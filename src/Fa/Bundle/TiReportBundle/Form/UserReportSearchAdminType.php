<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


/**
 * This is user admin form.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserReportSearchAdminType extends AbstractType
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
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventSubscriber(new AddDatePickerFieldSubscriber('rus_from_date'))
            ->addEventSubscriber(new AddDatePickerFieldSubscriber('rus_to_date'))
            ->add(
                'rus_date_filter_type',
                ChoiceType::class,
                array(
                    'choices'  => array(
                        'Signup date' => 'signup_date',
                        'Date of last action' => 'created_at',
                    ),
                )
            )
            ->add('rus_name', TextType::class, array('required' => false))
            ->add('rus_email', TextType::class, array('required' => false))
            ->add(
                'rus_user_type',
                EntityType::class,
                array(
                    'required' => false,
                    'class' => 'FaUserBundle:Role',
                    'choice_label' => 'name',
                    'placeholder' => 'Select user type',
                    'label' => 'User type',
                    'query_builder' => function (RoleRepository $er) {
                        return $er->createQueryBuilder(RoleRepository::ALIAS)
                        ->where(RoleRepository::ALIAS.'.type = :roleType')
                        ->setParameter('roleType', 'C');
                    }
                )
            )
            ->add(
                'rus_report_type',
                ChoiceType::class,
                array(
                    'choices'  => array('Individual' => 'user_wise', 'Group' => 'all'),
                    'expanded' => true,
                    'multiple' => false,
                    'data'     => 'user_wise',
                ))
            ->add(
                'rus_report_columns',
                ChoiceType::class,
                array(
                    'choices'  => array_flip(CommonManager::getUserReportFieldsChoices()),
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'   => false
                ))
            ->add(
                'rus_csv_name',
                TextType::class,
                array(
                    'constraints' => new Regex(array('pattern' => "/^[a-z0-9_ -]+$/i", 'message' => 'Please enter valid alpha numeric name ([a-z0-9_ -]).'))
                )
            )
            ->add(
                'rus_csv_email',
                TextType::class,
                array(
                    'constraints' => new CustomEmail(array('message' => 'Please enter valid email address.'))
                )
            )
            ->add('search', SubmitType::class)
            ->add(
                'download_csv',
                ButtonType::class,
                array(
                    'label' => 'Download generated csv',
                )
            )
            ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
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

        $this->postValidation($event);
    }

    /**
     * Add location field validation.
     *
     * @param object $form Form instance.
     */
    protected function postValidation(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $fromDate     = CommonManager::getTimeStampFromStartDate($form->get('rus_from_date')->getData());
        $toDate       = CommonManager::getTimeStampFromStartDate($form->get('rus_to_date')->getData());
        $reportColums = $form->get('rus_report_columns')->getData();
        $isValid  = true;

        if (empty($fromDate)) {
            $isValid = false;
            $form->get('rus_from_date')->addError(new FormError('From date must be selected.'));
        }

        if (empty($toDate)) {
            $isValid = false;
            $form->get('rus_to_date')->addError(new FormError('To date must be selected.'));
        }


        if ($fromDate && $toDate && $fromDate > $toDate) {
            $isValid = false;
            $form->get('rus_from_date')->addError(new FormError('From date must be smaller than to date.'));
        }

        if (!$reportColums || count($reportColums) == 0) {
            $isValid = false;
            $form->get('rus_report_columns')->addError(new FormError('Please select report columns.'));
        }

        return $isValid;
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_ti_report_user_report_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ti_report_user_report_search_admin';
    }
}
