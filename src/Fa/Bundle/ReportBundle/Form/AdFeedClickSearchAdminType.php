<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\ReportBundle\Repository\AdFeedClickReportDailyRepository;
use Symfony\Component\Validator\Constraints\Email;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is ad report admin search form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdFeedClickSearchAdminType extends AbstractType
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
                'ad_feed_site_id',
                ChoiceType::class,
                array(
                    'mapped'    => false,
                    'placeholder' => 'Select feed source',
                    'choices'   => array_flip($this->em->getRepository('FaAdFeedBundle:AdFeedSite')->getFeedSiteArray())
                )
            )
            ->add('ad_id', TextType::class, array('required' => false))
            ->add(
                'feed_report_type',
                ChoiceType::class,
                array(
                    'choices'  =>  array('Feed source' => 'all', 'Ad ref' => 'ad_ref'),
                    'expanded' => true,
                    'multiple' => false,
                    'data' => 'all',
                    'label' => 'Report type',
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
        $reportColums = AdFeedClickReportDailyRepository::getAdFeedClickReportFields();

        if (!$fromDate) {
            $form->get('from_date')->addError(new FormError('From date must be selected.'));
        }

        if (!$toDate) {
            $form->get('to_date')->addError(new FormError('To date must be selected.'));
        }

        if ($fromDate && $toDate && $fromDate > $toDate) {
            $form->get('from_date')->addError(new FormError('From date must be smaller than to date.'));
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
        return 'fa_report_ad_feed_click_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_report_ad_feed_click_search_admin';
    }
}
