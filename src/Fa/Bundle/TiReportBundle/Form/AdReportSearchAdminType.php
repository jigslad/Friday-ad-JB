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
use Fa\Bundle\TiReportBundle\Repository\AdReportDailyRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddDomicileChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddTownChoiceFieldSubscriber;
use Fa\Bundle\AdBundle\Repository\PrintEditionRepository;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is ad report admin search form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdReportSearchAdminType extends AbstractType
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
            ->addEventSubscriber(new AddDatePickerFieldSubscriber('from_date'))
            ->addEventSubscriber(new AddDatePickerFieldSubscriber('to_date'))
            ->add(
                'date_filter_type',
                ChoiceType::class,
                array(
                    'choices'  => array(
                        'Date posted' => 'ad_created_at',
                        'Date of last action' => 'created_at',
                        'Date of print insertion' => 'print_insert_date',
                    ),
                )
            )
            ->add(
                'role_id',
                ChoiceType::class,
                array(
                    'mapped'    => false,
                    'placeholder' => 'User type',
                    'choices'   => array_flip(RoleRepository::getUserTypes())
                )
            )
            ->add('ad_id', TextType::class, array('required' => false))
            ->add(
                'admin_user_email',
                TextType::class,
                array(
                    'constraints' => new CustomEmail(array('message' => 'Please enter valid email address.'))
                )
            )
            ->add('category_id', HiddenType::class, array('data' => ''))
            ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 1))
            ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
            ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
            ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4))
            ->addEventSubscriber(new AddDomicileChoiceFieldSubscriber($this->container, false, 'county_id', null, array('placeholder' => 'Select County')))
            ->addEventSubscriber(new AddTownChoiceFieldSubscriber($this->container, false, 'town_id', 'county_id', array('multiple' => true)))
            ->add(
                'print_edition_id',
                ChoiceType::class,
                array(
                    'mapped'    => false,
                    'placeholder' => 'Print edition',
                    'choices'   => array_flip(CommonManager::getEntityRepository($this->container, 'FaAdBundle:PrintEdition')->getActivePrintEditionArray()),
                )
            )
            ->add(
                'report_columns',
                ChoiceType::class,
                array(
                    'choices'  => array_flip(AdReportDailyRepository::getAdReportFields()),
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'   => false
                )
            )
            ->add('search', SubmitType::class)
            ->add(
                'reset',
                ButtonType::class,
                array(
                    'label' => 'Begin new search',
                )
            )
            ->add(
                'download_csv',
                ButtonType::class,
                array(
                    'label' => 'Download generated csv',
                )
            )
            ->add(
                'csv_name',
                TextType::class,
                array(
                    'constraints' => new Regex(array('pattern' => "/^[a-z0-9_ -]+$/i", 'message' => 'Please enter valid alpha numeric name ([a-z0-9_ -]).'))
                )
            )
            ->add(
                'csv_email',
                TextType::class,
                array(
                    'constraints' => new CustomEmail(array('message' => 'Please enter valid email address.'))
                )
            )
            ->add(
                'paid_ads',
                CheckboxType::class,
                array(
                    'label' => 'Only paid ads'
                )
            )
            ->add(
                'admin_ads',
                CheckboxType::class,
                array(
                    'label' => 'Only ads booked through Admin'
                )
            )
            ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * Add location field validation.
     *
     * @param object $form Form instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $fromDate     = CommonManager::getTimeStampFromStartDate($form->get('from_date')->getData());
        $toDate       = CommonManager::getTimeStampFromStartDate($form->get('to_date')->getData());
        $reportColums = $form->get('report_columns')->getData();

        if (!$fromDate) {
            $form->get('from_date')->addError(new FormError('From date must be selected.'));
        }

        if (!$toDate) {
            $form->get('to_date')->addError(new FormError('To date must be selected.'));
        }

        if ($fromDate && $toDate && $fromDate > $toDate) {
            $form->get('from_date')->addError(new FormError('From date must be smaller than to date.'));
        }

        if (!$reportColums || count($reportColums) == 0) {
            $form->get('report_columns')->addError(new FormError('Please select report columns.'));
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     */
    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'allow_extra_fields' => true
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_ti_item_report';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ti_item_report';
    }
}
