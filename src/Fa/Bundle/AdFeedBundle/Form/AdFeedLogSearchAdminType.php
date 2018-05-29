<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used to show cyber source api fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class AdFeedLogSearchAdminType extends AbstractType
{
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
            ->add('ad_feed__ad__id')
            ->add('ad_feed__user__email')
            ->add('ad_feed__trans_id')
            ->add('ad_feed__unique_id')
            ->add('ad_feed__status')
            ->add('ad_feed__ref_site_id', ChoiceType::class, array('choices' => array('Select' => '') + array_flip($this->entityManager->getRepository('FaAdFeedBundle:AdFeedSite')->getFeedSiteArray())))
            ->add('search', SubmitType::class, array('label' => 'Search'))
        ;
    }

    /**
     * Get name.
     * @return string
     */
    public function getName()
    {
        return 'fa_ad_feed_ad_feed_log_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_feed_ad_feed_log_search_admin';
    }
}
