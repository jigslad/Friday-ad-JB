<?php
namespace Fa\Bundle\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Fa\Bundle\CoreBundle\Form\EntitySearchType
 *
 * EntitySearchType form
 *
 * @author Piyusyh Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 */
class ConfigRuleSearchType extends AbstractType
{
    protected $entityManager;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->entityManager = $options['em'];

        $builder
        ->add('category__name', TextType::class, array('required' => false))
        ->add(
            'config__id',
            ChoiceType::class,
            array(
                'choices' => array_flip($this->entityManager->getRepository('FaCoreBundle:Config')->getRuleArray()),
                'placeholder' => 'Select name'
            )
        )
        ->add('search', SubmitType::class, array('label' => 'Search'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'search';
    }
    
    public function getBlockPrefix()
    {
        return 'search';
    }

    /**
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
        );
    }
}
