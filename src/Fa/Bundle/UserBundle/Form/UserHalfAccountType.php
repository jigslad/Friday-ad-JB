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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Fa\Bundle\UserBundle\Entity\User;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * This is user half account form.
 *
 * @author Samir Amrutya<samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserHalfAccountType extends AbstractType
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
     * Request instance.
     *
     * @var object
     */
    protected $request;

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
        $this->request    = $this->container->get('request_stack')->getCurrentRequest();
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
        $emailAlertLabel = 'I\'d like to receive news, offers and promotions by email from Friday-Ad';
        $thirdPartyEmailAlertLabel = 'I\'d like to receive offers and promotions by email on behalf of carefully chosen partners';
        
        $builder
            ->add(
                'first_name',
                TextType::class,
                array(
                    'label'       => 'Name',
                    'attr'=>array('maxlength'=>'100'),
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Name is required.', array(), 'validators')))),
                )
            )
            ->add(
                'email',
                EmailType::class,
                array(
                    'attr'=>array('maxlength'=>'255'),
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans('Email is required.', array(), 'validators'))),
                        new CustomEmail(array('message' => 'Please enter valid email address.')),
                    ),
                )
            )
            ->add(
                'email_alert',
                CheckboxType::class,
                array(
                    'label' => $emailAlertLabel,
                    'mapped' => false,
                )
            )
            ->add(
                'third_party_email_alert',
                CheckboxType::class,
                array(
                    'label' => $thirdPartyEmailAlertLabel,
                    'mapped' => false,
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Create'));

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitData'));
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function postSubmitData(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isValid()) {
            $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData()));

            if (!$user) {
                $user = new User();
                $user->setIsHalfAccount(1);

                if ($form->has('first_name')) {
                    $user->setFirstName($form->get('first_name')->getData());
                }

                $user->setUserName($form->get('email')->getData());
                $user->setEmail($form->get('email')->getData());
                
                //update email alerts
                if ($form->get('email_alert')->getData()) {
                    $user->setIsEmailAlertEnabled(1);
                } else {
                    $user->setIsEmailAlertEnabled(0);
                }
                
                //update third party email alerts
                if ($form->get('third_party_email_alert')->getData()) {
                    $user->setIsThirdPartyEmailAlertEnabled(1);
                } else {
                    $user->setIsThirdPartyEmailAlertEnabled(0);
                }

                //set user status
                $userActiveStatus = $this->em->getRepository('FaEntityBundle:Entity')->find(EntityRepository::USER_STATUS_ACTIVE_ID);
                $user->setStatus($userActiveStatus);

                // set guid
                $user->setGuid(CommonManager::generateGuid($form->get('email')->getData()));

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
                'data_class'         => null,
                'translation_domain' => 'frontend-half-account',
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
        return 'fa_user_half_account';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_half_account';
    }
}
