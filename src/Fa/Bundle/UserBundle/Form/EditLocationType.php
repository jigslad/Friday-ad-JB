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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * This form is used for edit location.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class EditLocationType extends AbstractType
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
     * @param object $container
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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userBusinessCategoryId    = null;
        $loggedInUser = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
        if ($loggedInUser && $loggedInUser->getBusinessCategoryId()) {
            $userBusinessCategoryId    = $loggedInUser->getBusinessCategoryId();
        }

        if (in_array($userBusinessCategoryId, array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
            $builder
                ->add(
                    'zip',
                    TextType::class,
                    array(
                        'label' => 'Postcode',
                        'mapped' => false,
                        'data' => ($loggedInUser->getZip() ? $loggedInUser->getZip() : null),
                        'constraints' => array(
                            new NotBlank(array('message' => $this->translator->trans("You have to introduce postcode.", array(), 'validators'))),
                        )
                    )
                )
                ->add('show_map', CheckboxType::class, array('label' => 'Show map on business profile'));
        } else {
            $builder->add(
                'zip',
                TextType::class,
                array(
                    'label' => 'Postcode',
                    'mapped' => false,
                    'data' => ($loggedInUser->getZip() ? $loggedInUser->getZip() : null),
                )
            );
        }

        $builder->add('save', SubmitType::class, array('label' => 'Save changes'));

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) {
                $form     = $event->getForm();
                $postCode = trim($form->get('zip')->getData());
                if ($postCode) {
                    $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                    if (!$postCodeObj) {
                        $event->getForm()->get('zip')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid postcode.', array(), 'validators')));
                    }
                }
            }
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
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
            $userSite = $form->getData();
            $user = $userSite->getUser();
            $postCode = trim($form->get('zip')->getData());
            if ($user) {
                if ($postCode) {
                    $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                    if ($postCodeObj->getTownId()) {
                        $townObj = $this->em->getRepository('FaEntityBundle:Location')->find($postCodeObj->getTownId());
                        $user->setZip($postCode);
                        $user->setLocationTown($townObj);
                        $user->setLocationDomicile($townObj->getParent());
                        $user->setLocationCountry($this->em->getReference('FaEntityBundle:Location', LocationRepository::COUNTY_ID));
                    }
                } else {
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
     * Set default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserSite',
                'translation_domain' => 'frontend-my-profile',
            )
        );
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_user_edit_location';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_edit_location';
    }
}
