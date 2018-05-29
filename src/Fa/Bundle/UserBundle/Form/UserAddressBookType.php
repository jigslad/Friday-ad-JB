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
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used to show cyber source api fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class UserAddressBookType extends AbstractType
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
        $loggedinUser = null;
        if (!$builder->getForm()->getData()->getId()) {
            $loggedinUser = CommonManager::getLoggedInUser($this->container);
        }
        $builder
            ->add(
                'first_name',
                TextType::class,
                array(
                    'label' => 'Full name',
                    'max_length' => 100,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter full name.', array(), 'validators')))),
                    'data' => ($loggedinUser ? $loggedinUser->getFullName() : $builder->getForm()->getData()->getFirstName()),
                )
            )
            ->add(
                'street_address',
                TextType::class,
                array(
                    'label' => 'House name/number',
                    'max_length' => 100,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter house name/number.', array(), 'validators'))))
                )
            )
            ->add(
                'street_address_2',
                TextType::class,
                array(
                    'label' => 'Address line 2',
                    'max_length' => 100,
                    //'constraints' => array(new NotBlank(array('message' => 'Please enter address line 2.')))
                )
            )
            ->add(
                'zip',
                TextType::class,
                array(
                    'label' => 'Postcode',
                    'max_length' => 15,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter postcode.', array(), 'validators'))))
                )
            )
            ->add(
                'town',
                TextType::class,
                array(
                    'label' => 'Town/city',
                    'max_length' => 50,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter town/city.', array(), 'validators')))),
                    'data' => (($builder->getForm()->getData()->getId() && $builder->getForm()->getData()->getTown()) ? $builder->getForm()->getData()->getTown() : null),
                )
            )
            ->add(
                'county',
                TextType::class,
                array(
                    'required' => false,
                    'label' => 'State/county',
                    'max_length' => 50,
                    'data' => (($builder->getForm()->getData()->getId() && $builder->getForm()->getData()->getCounty()) ? $builder->getForm()->getData()->getCounty() : null),
                )
            );

        if ($builder->getForm()->getData()->getId()) {
            $builder->add('save', SubmitType::class, array('label' => 'Save changes'));
        } else {
            $builder->add('save', SubmitType::class, array('label' => 'Add this address'));
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));

        //removed post code validation
        /*
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) {
                $form       = $event->getForm();
                $postCode   = trim($form->get('zip')->getData());
                if ($postCode) {
                    $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                    if (!$postCodeObj) {
                        $event->getForm()->get('zip')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid post code.', array(), 'validators')));
                    }
                }
            }
        );*/
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isValid()) {
            $entityCacheManager = $this->container->get('fa.entity.cache.manager');
            $loggedInUser  = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
            $userAddresses = $this->em->getRepository('FaUserBundle:UserAddressBook')->findOneBy(array('user' => $loggedInUser->getId()));
            $userAddress   = $form->getData();
            $stateName     = trim($form->get('county')->getData());
            $townName      = trim($form->get('town')->getData());
            $countryName   = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', LocationRepository::COUNTY_ID);

            $userAddress->setUser($loggedInUser);
            $userAddress->setCountry($countryName);
            $userAddress->setTown($townName);
            $userAddress->setCounty($stateName);
            if (!$userAddresses) {
                $userAddress->setIsInvoiceAddress(1);
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserAddressBook',
                'translation_domain' => 'frontend-user-address-book',
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
        return 'fa_user_user_address_book';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_address_book';
    }
}
