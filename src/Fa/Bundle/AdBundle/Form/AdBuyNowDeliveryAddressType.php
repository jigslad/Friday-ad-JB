<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used to show cyber source api fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class AdBuyNowDeliveryAddressType extends AbstractType
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
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param object $container Container identifier.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Build form for cyber source.
     *
     * @param FormBuilderInterface $builder Form builder.
     * @param array                $options Array of options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'street_address',
                TextType::class,
                array(
                    'label' => 'House name/number',
                    'attr'=>array('maxlength'=>'100'),
                    'constraints' => array(new NotBlank(array('groups' => array('new_delivery_addr'), 'message' => $this->translator->trans('Please enter house name/number.', array(), 'validators'))))
                )
            )
            ->add(
                'street_address_2',
                TextType::class,
                array(
                    'label' => 'Address line 2',
                    'attr'=>array('maxlength'=>'100'),
                    'constraints' => array(new NotBlank(array('groups' => array('new_delivery_addr'), 'message' => $this->translator->trans('Please enter address line 2.', array(), 'validators'))))
                )
            )
            ->add(
                'zip',
                TextType::class,
                array(
                    'label' => 'Postcode',
                    'attr'=>array('maxlength'=>'15'),
                    'constraints' => array(new NotBlank(array('groups' => array('new_delivery_addr'), 'message' => $this->translator->trans('Please enter postcode.', array(), 'validators'))))
                )
            )
            ->add(
                'town',
                TextType::class,
                array(
                    'label' => 'Town/city',
                    'attr'=>array('maxlength'=>'50'),
                    'constraints' => array(new NotBlank(array('groups' => array('new_delivery_addr'), 'message' => $this->translator->trans('Please enter town/city.', array(), 'validators'))))
                )
            )
            ->add(
                'county',
                TextType::class,
                array(
                    'required' => false,
                    'label' => 'State/county',
                    'attr'=>array('maxlength'=>'50'),
                )
            )
            ->add(
                'delivery_address',
                ChoiceType::class,
                array(
                    'choices'  => array_flip($this->em->getRepository('FaUserBundle:UserAddressBook')->getUserAddressOptions($options['data']['userId'], (isset($options['data']['deliveryMethodId']) ? $options['data']['deliveryMethodId'] : null), $this->container)),
                    'constraints' => array(new NotBlank(array('groups' => array('delivery_addr', 'new_delivery_addr'), 'message' => $this->translator->trans('Please select delivery address.', array(), 'validators')))),
                    'multiple' => false,
                    'expanded' => true,
                    'mapped'   => false,
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Continue'));
        /*
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) {
                $form       = $event->getForm();
                $townName   = trim($form->get('town')->getData());
                $countyName = trim($form->get('county')->getData());
                $postCode   = trim($form->get('zip')->getData());
                if ($postCode) {
                    $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                    if (!$postCodeObj) {
                        $event->getForm()->get('zip')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid post code.', array(), 'validators')));
                    }
                }

                if ($townName) {
                    $town = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $townName, 'lvl' => 3));
                    if (!$town) {
                        $event->getForm()->get('town')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid town/city name.', array(), 'validators')));
                    }
                }

                if ($countyName) {
                    $county = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $countyName, 'lvl' => 2));
                    if (!$county) {
                        $event->getForm()->get('county')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid state/county name.', array(), 'validators')));
                    }
                }
            }
        );
        */
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver Option resolver.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => null,
                'translation_domain' => 'frontend-buy-now',
                'validation_groups' => function (FormInterface $form) {
                    $groups = array('delivery_addr');
                    switch ($form->get('delivery_address')->getData()) {
                        case 0:
                            $groups = array('new_delivery_addr');
                            break;
                    }

                    return $groups;
                },
            )
        );
    }

    /**
     * Get form name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_buy_now_delivery_address';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_buy_now_delivery_address';
    }
}
