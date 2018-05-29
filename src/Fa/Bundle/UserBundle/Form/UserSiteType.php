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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\CoreBundle\Form\Validator\FaPhone;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Form\Validator\FaTinyMceLength;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is rolr form.
 *
 * @author Samir Amrutya<samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSiteType extends AbstractType
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
        $builder
            ->add(
                'company_welcome_message',
                TextareaType::class,
                array(
                    'label'       => 'Company welcome message',
                    'constraints' => array(new Length(array('max' => 160))),
                    'attr'        => array('class' => 'textcounter', 'maxlength' => 160, 'rows' => '3')
                )
            )
            ->add(
                'about_us',
                TextareaType::class,
                array(
                    'label'       => 'About your business',
                    'constraints' => array(new FaTinyMceLength(array('max' => 2000))),
                    'attr'        => array('class' => 'tinymce textcounter', 'maxlength' => 2000)
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
                    'label'       => 'telephone 1',
                    'constraints' => array(new Regex(array('pattern' => '/^\+?\d{7,11}$/', 'message' => $this->translator->trans('Please enter correct telephone 1. It should contain minimum 7 digit and maximum 11 digit.', array(), 'validators'))))
                )
            )
            ->add(
                'phone2',
                TelType::class,
                array(
                    'label'       => 'telephone 2',
                    'constraints' => array(new Regex(array('pattern' => '/^\+?\d{7,11}$/', 'message' => $this->translator->trans('Please enter correct telephone 2. It should contain minimum 7 digit and maximum 11 digit.', array(), 'validators'))))
                )
            )
            ->add(
                'website_link',
                TextType::class,
                array(
                    'label' => 'Website link',
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Next step: add more details'));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitData'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $userSite = $event->getData();
        $form     = $event->getForm();

        $fieldOptions = array(
                            'label'       => 'Company name',
                            'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Company name should not be blank.', array(), 'validators')))),
                            'mapped'      => false,
                        );

        if ($userSite && $userSite->getUser()) {
            $fieldOptions['data'] = $userSite->getUser()->getBusinessName();
        }

        $form->add('company_name', TextType::class, $fieldOptions);
    }

    /**
     * On post submit data.
     *
     * @param FormEvent $event
     */
    public function postSubmitData(FormEvent $event)
    {
        $this->save($event->getForm());
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
     * Set default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserSite',
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
        return 'user_site';
    }
    
    public function getBlockPrefix()
    {
        return 'user_site';
    }

    /**
     * Save user site.
     *
     * @param object $form Form object.
     */
    public function save($form)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($user) {
            $userSite = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
            if (!$userSite) {
                $userSite = new UserSite();
            }

            $phone1 = str_replace(' ', '', $form->get('phone1')->getData());
            $phone2 = str_replace(' ', '', $form->get('phone2')->getData());

            // Save business details
            $userSite->setUser($user);
            $userSite->setAboutUs($form->get('about_us')->getData());
            $userSite->setCompanyWelcomeMessage($form->get('company_welcome_message')->getData());
            $userSite->setCompanyAddress($form->get('company_address')->getData());
            $userSite->setPhone1(!$phone1 ? null : $phone1);
            $userSite->setPhone2(!$phone2 ? null : $phone2);
            $userSite->setWebsiteLink($form->get('website_link')->getData());
            $this->em->persist($userSite);
            $this->em->flush($userSite);

            // Save company name to user table
            $user->setBusinessName($form->get('company_name')->getData());
            $this->em->persist($user);
            $this->em->flush($user);
        }
    }
}
