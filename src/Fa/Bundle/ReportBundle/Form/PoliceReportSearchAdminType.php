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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ReportBundle\Repository\AdReportDailyRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddDomicileChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddTownChoiceFieldSubscriber;
use Fa\Bundle\AdBundle\Repository\PrintEditionRepository;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is police report admin search form.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class PoliceReportSearchAdminType extends AbstractType
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
            ->add('ad_id', TextType::class, array('required' => false))
            ->add(
                'email',
                TextType::class,
                array(
                    //'constraints' => new CustomEmail(array('message' => 'Please enter valid email address.'))
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
                'include_messages',
                CheckboxType::class,
                array(
                    'label' => 'Include messages'
                )
            )
            ->add(
                'include_ads',
                CheckboxType::class,
                array(
                    'label' => 'Include available ads'
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

        $adId  = $form->get('ad_id')->getData();
        $email = $form->get('email')->getData();

        if (!$adId && !$email) {
            $form->get('email')->addError(new FormError('Either email address or advert ref is required.'));
        } elseif ($adId && $email) {
            $form->get('email')->addError(new FormError('Either email address or advert ref any one should be entered.'));
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
        return 'fa_police_report';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_police_report';
    }
}
