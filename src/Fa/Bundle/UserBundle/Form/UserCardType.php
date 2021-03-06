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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * This form is used to add new card.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class UserCardType extends AbstractType
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
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
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
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter house name/number.', array(), 'validators'))))
                )
            )
            ->add(
                'street_address_2',
                TextType::class,
                array(
                    'label' => 'Address line 2',
                    'attr'=>array('maxlength'=>'100'),
                    //'constraints' => array(new NotBlank(array('message' => 'Please enter address line 2.')))
                )
            )
            ->add(
                'zip',
                TextType::class,
                array(
                    'label' => 'Postcode',
                    'attr'=>array('maxlength'=>'15'),
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter postcode.', array(), 'validators'))))
                )
            )
            ->add(
                'town',
                TextType::class,
                array(
                    'label' => 'Town/city',
                    'attr'=>array('maxlength'=>'50'),
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter town/city.', array(), 'validators'))))
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
                'card_type',
                HiddenType::class
            )
            ->add(
                'card_holder_name',
                TextType::class,
                array(
                    'label' => 'Cardholder name (as it appears on your card)',
                    'attr'=>array('maxlength'=>'150'),
                    'constraints' => array(new Regex(array('pattern' => '/^[a-z0-9 _-]+$/i', 'message' => $this->translator->trans('Cardholder name cannot have special characters other than hyphen and underscore', array(), 'validators'))),new NotBlank(array('message' => $this->translator->trans('Please enter cardholder name.', array(), 'validators'))))
                )
            )
            ->add(
                'card_number',
                TelType::class,
                array(
                    'label' => 'Card number',
                    'attr'=>array('maxlength'=>'20'),
                    'attr' => array('pattern' => '[0-9]*'),
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter card number.', array(), 'validators'))))
                )
            )
            ->add(
                'card_security_code',
                TelType::class,
                array(
                    'label' => 'Security code (3 digits on the back of the card)',
                    'attr'=>array('maxlength'=>'3'),
                    'attr' => array('pattern' => '[0-9]*'),
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter security code.', array(), 'validators'))))
                )
            )
            ->add(
                'card_expity_month',
                ChoiceType::class,
                array(
                    'label' => 'Expiry date',
                    'placeholder'  => 'Month',
                    'choices'  => array_flip(CommonManager::getMonthChoices()),
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please select expiry month.', array(), 'validators'))))
                )
            )
            ->add(
                'card_expity_year',
                ChoiceType::class,
                array(
                    'placeholder'  => 'Year',
                    'choices'  => array_flip($this->getYearRangeOptions()),
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please select expiry year.', array(), 'validators'))))
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Save this card'))
            ->add('cancel', ButtonType::class, array('label' => 'Cancel'));

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'preSubmit'));
        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $form       = $event->getForm();
        $postCode   = trim($form->get('zip')->getData());
        $cardNumber = trim($form->get('card_number')->getData());

        //removed post code validation
        /*if ($postCode) {
            $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
            if (!$postCodeObj) {
                $event->getForm()->get('zip')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid post code.', array(), 'validators')));
            }
        }*/

        if ($cardNumber) {
            if (!$this->em->getRepository('FaPaymentBundle:PaymentTokenization')->validateCreditCardTypes($cardNumber)) {
                $event->getForm()->get('card_number')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Unknown card types, allowed card types are Visa, MasterCard, Maestro (UK Domestic) and Visa Electron.', array(), 'validators')));
            }
        }
    }

    /**
     * This function is called on pre submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (isset($data['card_number'])) {
            $cardNumber = trim($data['card_number']);
            if ($cardNumber) {
                $cardType = $this->em->getRepository('FaPaymentBundle:PaymentTokenization')->getCreditCardType($cardNumber);
                $data['card_type'] = $cardType;
                $event->setData($data);
            }
        }
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
                'translation_domain' => 'frontend-new-card',
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
        return 'fa_user_card';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_card';
    }

    /**
     * Get year range options.
     *
     * @return array
     */
    private function getYearRangeOptions()
    {
        $yearArray = array();

        for ($i=Date('Y'); $i<= date('Y')+20; $i++) {
            $yearArray[$i] = $i;
        }

        return $yearArray;
    }
}
