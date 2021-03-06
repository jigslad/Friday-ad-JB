<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is user search form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPaymentSearchType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container object.
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
        ->add('payment__id', TextType::class, array('required' => false))
        ->add('payment__cart_code', TextType::class, array('required' => false))
        ->add(
            'payment__payment_method',
            ChoiceType::class,
            array(
                'multiple' => false,
                'label' => 'Payment method',
                'placeholder' => 'Please select payment method',
                'choices'   => array_flip($this->em->getRepository('FaPaymentBundle:Payment')->getPaymentMethods($this->container)),
              )
        )
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('payment__created_at_from'))
        ->addEventSubscriber(new AddDatePickerFieldSubscriber('payment__created_at_to'))
        ->add('search', SubmitType::class);
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_payment_search_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_payment_search_admin';
    }
}
