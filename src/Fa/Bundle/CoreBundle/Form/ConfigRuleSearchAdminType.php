<?php
namespace Fa\Bundle\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Fa\Bundle\CoreBundle\Form\ConfigRuleSearchAdminType
 *
 * EntitySearchType form
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 */
class ConfigRuleSearchAdminType extends AbstractType
{
    /**
     * Container service class object
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager
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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add(
            'config__id',
            ChoiceType::class,
            array(
                'choices' => array_flip($this->em->getRepository('FaCoreBundle:Config')->getRuleArray()),
                'placeholder' => 'Select Config Option'
            )
        )
        ->add('category__id', HiddenType::class, array('data' => ''))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 1))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
        ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4))
        ->add(
            'location_group__id',
            ChoiceType::class,
            array(
                'choices'     => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getLocationGroupsKeyValueArray()),
                'placeholder' => 'Select Location Group',
            )
        )
        ->add('search', SubmitType::class, array('label' => 'Search'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'fa_core_config_rule_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_core_config_rule_search_admin';
    }
}
