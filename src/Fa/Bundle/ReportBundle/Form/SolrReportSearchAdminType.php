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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddDomicileChoiceFieldSubscriber;
use Fa\Bundle\EntityBundle\Form\EventListener\AddTownChoiceFieldSubscriber;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Symfony\Component\Form\FormError;
use Fa\Bundle\AdBundle\Repository\InActiveUserSolrAdsRepository;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;


/**
 * SolrReportSearchAdminType form.
 *
 * @author Rohini Subburam <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class SolrReportSearchAdminType extends AbstractType
{
    /**
     * Entity manager class object.
     *
     * @var object
     */
    protected $entityManager;

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $this->container->get('doctrine')->getManager();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder 
        ->add('user__id', 'text', array('required' => false))
        ->add('ad__id', 'text', array('required' => false))
        ->add('user__email', 'text', array('required' => false)) 
        ->add('status', 'hidden')      
        ->add(
            'ad__status',
            'entity',
            array(
                'class' => 'FaEntityBundle:Entity',
                'choice_label' => 'name',
                'empty_value' => 'Ad status',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder(EntityRepository::ALIAS)
                    ->where(EntityRepository::ALIAS.'.category_dimension = '.EntityRepository::AD_STATUS_ID)
                    ->orderBy(EntityRepository::ALIAS.'.name', 'ASC');
                }
            )
        )
        ->add(
            'user__status',
            'entity',
            array(
                'class' => 'FaEntityBundle:Entity',
                'choice_label' => 'name',
                'empty_value' => 'User status',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder(EntityRepository::ALIAS)
                    ->where(EntityRepository::ALIAS.'.category_dimension = '.EntityRepository::USER_STATUS_ID)
                    ->orderBy(EntityRepository::ALIAS.'.name', 'ASC');
                }
            )
        )
        ->add('search', 'submit', array(
            'label' => 'Search'
        ));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_solr_report';
    }
}
