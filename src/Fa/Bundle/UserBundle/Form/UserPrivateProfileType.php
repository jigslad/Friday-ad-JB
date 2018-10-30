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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is user profile form.
 *
  * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPrivateProfileType extends AbstractType
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
     * Constructor.
     *
     * @param object $container Container instance.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Object.
     * @param array                $options Array of various options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $location = null;
        $loggedInUser = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
        if ($loggedInUser && $loggedInUser->getZip()) {
            $location = $loggedInUser->getZip();
        } elseif ($loggedInUser && !$loggedInUser->getZip() && $loggedInUser->getLocationTown()) {
            $location = $loggedInUser->getLocationTown()->getName();
        }

        $builder
            ->add(
                'first_name',
                TextType::class,
                array(
                    'label' => 'First name',
                    'attr'=>array('maxlength'=>'100'),
                    'constraints' => array(new Regex(array('pattern' => '/^[a-z0-9 _-]+$/i', 'groups' => array('user_private_profile'), 'message' => $this->translator->trans('First name cannot have special characters other than hyphen and underscore', array(), 'validators'))),new NotBlank(array('groups' => array('user_private_profile'), 'message' => $this->translator->trans('Please enter first name.', array(), 'validators'))))
                )
            )
            ->add(
                'last_name',
                TextType::class,
                array(
                    'label' => 'Last name',
                    'attr'=>array('maxlength'=>'100'),
                    'constraints' => array(new Regex(array('pattern' => '/^[a-z0-9 _-]+$/i', 'groups' => array('user_private_profile'), 'message' => $this->translator->trans('Last name cannot have special characters other than hyphen and underscore', array(), 'validators'))),new NotBlank(array('groups' => array('user_private_profile'), 'message' => $this->translator->trans('Please enter last name.', array(), 'validators'))))
        )
            )
            ->add(
                'about_you',
                TextareaType::class,
                array(
                    'label' => 'About you',
                    'attr'=>array('maxlength'=>'2000'),
                    'constraints' => array(
                        new Length(array('groups' => array('user_private_profile'), 'max' => 2000, 'maxMessage' => $this->translator->trans("About you can have maximum 2000 characters.", array(), 'validators')))
                    )
                )
            )
            ->add(
                'location_autocomplete',
                TextType::class,
                array(
                    'label' => 'Your location',
                    'mapped' => false,
                    'data' => $location
                )
            );

        $builder->add('save_profile_changes', SubmitType::class, array('label' => 'Save changes'))
            ->addEventListener(FormEvents::SUBMIT, array($this, 'submit'))
            ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\User',
                'validation_groups' => array('user_private_profile'),
                'translation_domain' => 'frontend-my-profile',
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_private_profile';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_private_profile';
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
            $user = $form->getData();
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

            if (($user->getFirstName() != '' || $user->getLastName() != '') && $user->getAboutYou() != '') {
                $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('if_profile_incomplete', null, $user->getId());
            }
        }
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
