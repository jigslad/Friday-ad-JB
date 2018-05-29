<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\EntityBundle\Repository\FaEntityLogRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Entity search type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class FaEntityLogSearchAdminType extends AbstractType
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
        $this->em         = $this->container->get('doctrine')->getManager();
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
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('fa_entity_log__loggedAt'))
        ->add('fa_entity_log__objectId', TextType::class, array('required' => false))
        ->add('fa_entity_log__objectClass', ChoiceType::class, array(
            'empty_data'  => null,
            'placeholder' => "Select Entity",
            'choices' => array_flip($this->em->getRepository('FaEntityBundle:FaEntityLog')->getObjectClassOptionArray())
        ))
        ->add('fa_entity_log__username', TextType::class, array('required' => false))
        ->add('search', SubmitType::class, array('label' => 'Search'));
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
                'data_class' => null
            )
        )
        ->setRequired(
            array(
                'em',
            )
        )
        ->setAllowedTypes('em', array('null', 'string', 'Doctrine\Common\Persistence\ObjectManager'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_entity_log_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_entity_log_search_admin';
    }
}
