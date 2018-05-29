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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormInterface;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * This form is used to show create nimber task.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

class NimberCreateTaskType extends AbstractType
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
     * Ad Owner Id.
     *
     * @var integer
     */
    private $adId;

    /**
     * Logged in user.
     *
     * @var object
     */
    private $loggedInUser;

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
        $this->loggedInUser = (CommonManager::isAuth($this->container) ? CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser() : null);
    }

    /**
     * Build form for nimber.
     *
     * @param FormBuilderInterface $builder Form builder.
     * @param array                $options Array of options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->adId = $options['adId'];
        $builder
            ->add(
                'first_name',
                TextType::class,
                array(
                    'max_length' => 50,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter first name.', array(), 'validators')))),
                    'data' => ($this->loggedInUser && $this->loggedInUser->getFirstName() ? $this->loggedInUser->getFirstName() : null),
                )
            )
            ->add(
                'last_name',
                TextType::class,
                array(
                    'max_length' => 50,
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please enter last name.', array(), 'validators')))),
                    'data' => ($this->loggedInUser && $this->loggedInUser->getLastName() ? $this->loggedInUser->getLastName() : null),
                )
            )
            ->add(
                'email',
                TextType::class,
                array(
                    'max_length' => 50,
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please enter email.', array(), 'validators'))),
                        new CustomEmail(array('message' => $this->translator->trans('Please enter valid email.', array(), 'validators')))
                    ),
                    'data' => ($this->loggedInUser && $this->loggedInUser->getEmail() ? $this->loggedInUser->getEmail() : null),
                )
            )
            ->add(
                'phone',
                TextType::class,
                array(
                    'max_length' => 15,
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Please enter phone.', array(), 'validators'))),
                        new Regex(array('pattern' => "/^\+\d{7,12}$/", 'message' => $this->translator->trans('Please enter valid phone (ex. +441234567890).', array(), 'validators'))),
                    ),
                    'data' => '+44',
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Submit your delivery request'));

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     *
     * @return void
     */
    public function onPostSubmit(FormEvent $event)
    {
        $adNimber = $event->getData();
        $form  = $event->getForm();

        if ($form->isValid()) {
            $adNimber->setAd($this->em->getReference('FaAdBundle:Ad', $this->adId));
            if ($this->loggedInUser) {
                $adNimber->setBuyerUserId($this->loggedInUser->getId());
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
                'data_class' => 'Fa\Bundle\AdBundle\Entity\AdNimber',
                'translation_domain' => 'frontend-nimber',
            )
        )->setDefined(
            array(
                'adId',
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
        return 'fa_nimber_create_task';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_nimber_create_task';
    }
}
