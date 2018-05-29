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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\Length;
use Fa\Bundle\CoreBundle\Form\Validator\FaPhone;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints\Regex;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Form\Validator\FaTinyMceLength;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is user business profile form.
 *
  * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserBusinessProfileType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager class object.
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
     * Has Free Package flag.
     *
     * @var boolean
     */
    private $hasFreePackage;

    /**
     * User object.
     *
     * @var object
     */
    private $loggedInUser;

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
        $this->loggedInUser = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->loggedInUser) {
            $activePackage = $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($this->loggedInUser);
            if ($activePackage && $activePackage->getPackage() && $activePackage->getPackage()->getPrice()) {
                $this->hasFreePackage = false;
            } else {
                $this->hasFreePackage = true;
            }
        }

        $builder
            ->add('user', new UserBusinessProfileUserDetailType($this->container))
            ->add(
                'company_welcome_message',
                TextareaType::class,
                array(
                    'label'       => 'Welcome message',
                    'constraints' => array(new Length(array('max' => 160))),
                    'attr'        => array('maxlength' => 160, 'rows' => '3')
                )
            )
            ->add(
                'about_us',
                TextareaType::class,
                array(
                    'label'       => 'About your company',
                    'constraints' => array(new FaTinyMceLength(array('max' => 50000))),
                    'attr'        => array('class' => 'tinymce textcounter', 'maxlength' => 50000)
                )
            )
            ->add(
                'company_address',
                TextType::class,
                array('label' => 'Company address')
            )
            ->add(
                'phone1',
                TelType::class,
                array(
                    'label'       => 'Telephone 1',
                    'constraints' => array(new Regex(array('pattern' => '/^\+?\d{7,11}$/', 'message' => $this->translator->trans('Please enter correct telephone 1. It should contain minimum 7 digit and maximum 11 digit.', array(), 'validators'))))
                )
            )
            ->add(
                'phone2',
                TelType::class,
                array(
                    'label'       => 'Telephone 2',
                    'constraints' => array(new Regex(array('pattern' => '/^\+?\d{7,11}$/', 'message' => $this->translator->trans('Please enter correct telephone 2. It should contain minimum 7 digit and maximum 11 digit.', array(), 'validators'))))
                )
            )
            ->add(
                'website_link',
                TextType::class,
                array(
                    'label' => 'Website link',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            );

        if ($this->hasFreePackage) {
            $location = null;
            if ($this->loggedInUser && $this->loggedInUser->getZip()) {
                $location = $this->loggedInUser->getZip();
            } elseif ($this->loggedInUser && !$this->loggedInUser->getZip() && $this->loggedInUser->getLocationTown()) {
                $location = $this->loggedInUser->getLocationTown()->getName();
            }

            $builder->add(
                'location_autocomplete',
                TextType::class,
                array(
                    'label' => 'Your location',
                    'mapped' => false,
                    'data' => $location
                )
            )
            ->addEventListener(FormEvents::SUBMIT, array($this, 'submit'));
        }

        $builder->add('save_profile_changes', SubmitType::class, array('label' => 'Save changes'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'))
        ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     *
     * @param OptionsResolver $resolver object.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserSite',
                'validation_groups' => array('user_business_profile'),
                'translation_domain' => 'frontend-my-profile',
            )
        );
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
            $user_site = $form->getData();
            if ($user_site->getAboutUs() != '') {
                $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('if_profile_incomplete', null, $user_site->getUser()->getId());
            }
       

        if ($this->hasFreePackage) {
            $user = $this->loggedInUser;
            $locationText = trim($form->get('location_autocomplete')->getData());
            $postCodeObj = null;
            $townId = null;

            if ($locationText) {
                $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($locationText);
                // if postcode then update zip, town, county
                if ($postCodeObj) {
                    $townObj = $this->em->getRepository('FaEntityBundle:Location')->find($postCodeObj->getTownId());
                    $user->setZip($locationText);
                    $user->setLocationTown($townObj);
                    $user->setLocationDomicile($townObj->getParent());
                    $user->setLocationCountry($this->em->getReference('FaEntityBundle:Location', LocationRepository::COUNTY_ID));
                }
                if (!$postCodeObj) {
                    $townObj = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $locationText, 'lvl' => 3));
                    if ($townObj) {
                        $townId = $townObj->getId();
                    }
                }

                // if town then update town, county
                if ($townId && $townObj) {
                    $user->setZip(null);
                    $user->setLocationTown($townObj);
                    $user->setLocationDomicile($townObj->getParent());
                    $user->setLocationCountry($this->em->getReference('FaEntityBundle:Location', LocationRepository::COUNTY_ID));
                }
            }

            // if not post code or town then reset location info
            if (!$postCodeObj && !$townId) {
                $user->setZip(null);
                $user->setLocationTown(null);
                $user->setLocationDomicile(null);
                $user->setLocationCountry(null);
            }

            $this->em->persist($user);
            $this->em->flush($user);
        }
      }
    }

    /**
     * This function is called on pre submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (isset($data['phone1'])) {
            $data['phone1'] = str_replace(' ', '', $data['phone1']);
        }

        if (isset($data['phone2'])) {
            $data['phone2'] = str_replace(' ', '', $data['phone2']);
        }

        $event->setData($data);
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_business_profile';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_business_profile';
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function submit(FormEvent $event)
    {
        $form     = $event->getForm();
        $locationText = trim($form->get('location_autocomplete')->getData());
        $postCodeObj = null;
        $townId = null;
        if ($locationText) {
            $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($locationText);
            if (!$postCodeObj) {
                $townObj = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $locationText, 'lvl' => 3));
                if ($townObj) {
                    $townId = $townObj->getId();
                }
            }

            if (!$postCodeObj && !$townId) {
                $event->getForm()->get('location_autocomplete')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid postcode or town.', array(), 'validators')));
            }
        }
    }
}
