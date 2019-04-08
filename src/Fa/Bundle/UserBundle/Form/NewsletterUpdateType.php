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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\DotMailerBundle\Entity\Dotmailer;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
/**
 * This is newsletter form
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class NewsletterUpdateType extends AbstractType
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
     * Secutiry encoder.
     *
     * @var object
     */
    private $security_encoder;

    /**
     * Constructor.
     *
     * @param Doctrine                $doctrine         Doctrine object.
     * @param EncoderFactoryInterface $security_encoder Object.
     * @param ContainerInterface      $container        Object.
     */
    public function __construct(Doctrine $doctrine, EncoderFactoryInterface $security_encoder, ContainerInterface $container)
    {
        $this->em                = $doctrine->getManager();
        $this->security_encoder  = $security_encoder;
        $this->container         = $container;
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
        ->add('firstname', TextType::class, ['label' => 'First name', 'attr' => ['class' => 'textcounter white-bg']])
        ->add('lastname', TextType::class, ['label' => 'Last name', 'attr' => ['class' => 'textcounter white-bg']])
        ->add('gender', ChoiceType::class, ['label' => 'Gender', 'attr' => ['class' => 'fa-select error form-input-box white-bg'], 'choices'=>['Please select ..' => '', 'Male' => 'M', 'Female' => 'F', 'Prefer not to say' => 'ND']])
        ->add('postcode', TextType::class, ['label'=>'Postcode', 'attr' => ['class' => 'textcounter white-bg']])
        ->add('update_newsletter_preferences', SubmitType::class, array('label' => 'Update'))
        //->add('stop_third_party_emails', 'submit', array('label' => 'Stop third party emails'))
        ->add('unsubscribe_from_all_emails', SubmitType::class, array('label' => 'Unsubscribe'))
        ->add('clickedElementValue', HiddenType::class, array('mapped'=>false))
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitData'))
        ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $dotmailer = $event->getData();
        $form      = $event->getForm();

        $birthDateVal = [];
        $getBirthDate = $dotmailer->getDateOfBirth();
        if( !empty($getBirthDate) ) {
            $birthDateVal = explode("-", $getBirthDate);
        }

        $fieldOptions = array(
            /** @Ignore */
            'label'    => false,
            'expanded' => true,
            'multiple' => true,
            'choices'  => array_flip($this->em->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getKeyValueArray($this->container)),
        );

        if ($dotmailer && $dotmailer->getDotmailerNewsletterTypeId() && !$dotmailer->getDotmailerNewsletterUnsubscribe()) {
            $fieldOptions['data'] = $dotmailer->getDotmailerNewsletterTypeId();
        }

        $form->add('dotmailer_newsletter_type_id', ChoiceType::class, $fieldOptions);
        $form->add('email', TextType::class, ['label' => 'Email', 'data' => $dotmailer->getEmail(), 'attr' => ['class' => 'textcounter', 'readonly' => true]]);
        $form->add('email_dis', TextType::class, ['label' => false, 'mapped' => false,'data' => $dotmailer->getEmail(), 'attr' => ['class' => 'textcounter', 'readonly' => true]]);

        $form->add(
            'day',
            ChoiceType::class,
            array(
                'label' => false,
                'mapped' => false,
                'choices' => array_flip($this->getDayChoices()),
                'attr' => array('class' => 'fa-select'),
                'placeholder' => 'day',
                'attr' => ['class' => 'fa-select error form-input-box white-bg'],
                'data' => (isset($birthDateVal[0]) && $birthDateVal[0] != '')?$birthDateVal[0]:'day'
            )
            )
            ->add(
                'month',
                ChoiceType::class,
                array(
                    'label' => false,
                    'mapped' => false,
                    'choices' => array_flip(CommonManager::getMonthChoices()),
                    'attr' => array('class' => 'fa-select'),
                    'placeholder' => 'month',
                    'attr' => ['class' => 'fa-select error form-input-box white-bg'],
                    'data' => (isset($birthDateVal[1]) && $birthDateVal[1] != '')?$birthDateVal[1]:'month'
                )
                )
                ->add(
                    'year',
                    ChoiceType::class,
                    array(
                        'label' => false,
                        'mapped' => false,
                        'choices' => array_flip($this->getYearChoices()),
                        'attr' => array('class' => 'fa-select'),
                        'placeholder' => 'year',
                        'attr' => ['class' => 'fa-select error form-input-box white-bg'],
                        'data' => (isset($birthDateVal[2]) && $birthDateVal[2] != '')?$birthDateVal[2]:'year'
                    )
                    );

    }

    /**
     * Callbak method for SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onSubmit(FormEvent $event)
    {
        $ad   = $event->getData();
        $form = $event->getForm();
        $getClickVal = $form->get('clickedElementValue')->getData();
        if($getClickVal == 'update') {
            $this->validatePostCode($form);
        }
        //$this->validateGender($form);
    }

    /**
     * On post submit data.
     *
     * @param FormEvent $event
     */
    public function postSubmitData(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isValid()) {
            $this->save($event);
        }
    }



    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'user_newsletterupdate';
    }

    public function getBlockPrefix()
    {
        return 'user_newsletterupdate';
    }

    /**
     * Save user site.
     *
     * @param object $event Event object.
     */
    public function save($event)
    {
        $isNewToDotmailer = false;
        $form      = $event->getForm();
        $user      = $this->container->get('security.token_storage')->getToken()->getUser();
        $dotmailer = $event->getData();

        // refresh dotmailer object
        if ($dotmailer->getId()) {
            $this->em->refresh($dotmailer);
        } else {
            $isNewToDotmailer = true;
        }

        $dotmailerNewsletterTypeId       = $dotmailer->getDotmailerNewsletterTypeId();
        $dotmailerNewsletterTypeOptoutId = $dotmailer->getDotmailerNewsletterTypeOptoutId();

        if ($dotmailer && $dotmailer->getEmail() && $this->container->get('request_stack')->getCurrentRequest()->query->get('guid')) {
            $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $dotmailer->getEmail()));
        } else {
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
        }

        if (is_object($user)) {
            $getClickVal = $form->get('clickedElementValue')->getData();
            if($getClickVal == 'update') {
                // Save business details
                $dotmailer->setGuid(CommonManager::generateGuid($user->getEmail()));
                //$dotmailer->setOptIn();
                //$dotmailer->setOptInType();
                $dotmailer->setFirstName($form->get('firstname')->getData());
                $dotmailer->setLastName($form->get('lastname')->getData());
                $dotmailer->setGender($form->get('gender')->getData());
                $dotmailer->setDateOfBirth($form->get('day')->getData().'-'.$form->get('month')->getData()."-".$form->get('year')->getData());
                $dotmailer->setBusinessName($user->getBusinessName());
                if ($user->getRole()) {
                    $dotmailer->setRoleId($user->getRole()->getId());
                }
                $dotmailer->setPhone($user->getPhone());

                //get post code, town and county
                if($dotmailer->getPostcode() != $form->get('postcode')->getData()) {
                    $postCode = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($form->get('postcode')->getData());
                    $dotmailer->setPostCode($form->get('postcode')->getData());
                    if( !empty($postCode && $postCode->getTownId()) != null ) {
                        $dotmailer->setTownId($postCode->getTownId());
                        $dotmailer->setCountyId($postCode->getCountyId());
                        $dotmailer->setTownText($this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $postCode->getTownId()));
                        $dotmailer->setCountyText($this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $postCode->getCountyId()));
                    } else {
                        $dotmailer->setTownId(null);
                        $dotmailer->setCountyId(null);
                        $dotmailer->setTownText(null);
                        $dotmailer->setCountyText(null);
                    }
                }
            }

            // only process on n-values if unsubscribe doesn't selected
            if ($getClickVal == 'update' && ($dotmailer->getOptIn() !== false || $form->get('dotmailer_newsletter_type_id')->getData())) {
                $dotmailer->setDotmailerNewsletterTypeId($form->get('dotmailer_newsletter_type_id')->getData());
                $dotmailer->setDotmailerNewsletterTypeOptoutId($this->getOptoutNewsletterTypeId($dotmailerNewsletterTypeId, $dotmailerNewsletterTypeOptoutId, $form));
                $dotmailer->setOptIn(1);
                $dotmailer->setDotmailerNewsletterUnsubscribe(0);
            }

            // update last_paid_at
            if (!$dotmailer->getLastPaidAt()) {
                $lastPaidAt = $this->em->getRepository('FaPaymentBundle:Payment')->getLastPaidAt($user->getId());
                if ($lastPaidAt && isset($lastPaidAt['created_at'])) {
                    $dotmailer->setLastPaidAt($lastPaidAt['created_at']);
                }
            }

            if ($getClickVal == 'unsubscribe') {
                $dotmailer->setOptIn(0);
                $dotmailer->setDotmailerNewsletterUnsubscribe(1);
                $dotmailer->setDotmailerNewsletterTypeOptoutId($this->getOptoutNewsletterTypeId($dotmailerNewsletterTypeId, $dotmailerNewsletterTypeOptoutId, $form));
                $dotmailer->setDotmailerNewsletterTypeId(null);
            }

            /*if ($form->get('stop_third_party_emails')->isClicked()) {
             $dotmailer->setOptIn(0);
             $preferences = array_diff($form->get('dotmailer_newsletter_type_id')->getData(), [48]);
             $dotmailer->setDotmailerNewsletterTypeId($preferences);
             }*/

            if ($dotmailer && $dotmailer->getIsSuppressed()) {
                $dotmailer->setDotmailerNewsletterUnsubscribe(1);
            }

            $this->em->persist($dotmailer);
            file_put_contents('/var/www/html/newfriday-ad/web/uploads/testing.txt', 'newsletter update type|', FILE_APPEND);
            $this->em->flush($dotmailer);

            if ($dotmailer->getOptIn() != 1) {

                // opt out user
                $user->setIsEmailAlertEnabled(0);
                $this->em->persist($user);
                $this->em->flush($user);
                $this->em->getRepository('FaDotMailerBundle:Dotmailer')->sendUnsubscribeUserFromDotmailerRequest($dotmailer, $this->container);
                //unsubscribe from dotmailer.
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:unsubscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
                // remove contact from dotmailer.
                //exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:delete-contact --email='.$dotmailer->getEmail().' >/dev/null &');
            }

            if ($dotmailer->getOptIn() == 1 && $user->getIsEmailAlertEnabled() != 1) {
                // opt in user
                $user->setIsEmailAlertEnabled(1);
                $this->em->persist($user);
                $this->em->flush($user);
            }

            //send to dotmailer instantly.
            if ($isNewToDotmailer) {
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:subscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
            } else if ($getClickVal == 'update' && $form->isValid()) {
                //get Contact Id for update
                $getConact = $this->container->get('fa.dotmailer.getcontactbyemail.resource');
                $getConact->setDataToSubmit(array(0 => $dotmailer->getEmail()));
                $dotmailerResponse = $getConact->getContact();
                if ($dotmailerResponse) {
                    $response = json_decode($dotmailerResponse);
                    if ($response->id) {
                        $updateDotmailerInfo =  $this->em->getRepository('FaDotMailerBundle:Dotmailer')->sendUpdateContactInfoToDotmailerRequest($dotmailer, $response->id, $this->container);
                    }
                }
            }
        }
    }

    /**
     * Get output newsletter.
     *
     * @param array  $dotmailerNewsletterTypeId
     * @param array  $dotmailerNewsletterTypeOptoutId
     * @param object $form
     */
    protected function getOptoutNewsletterTypeId($dotmailerNewsletterTypeId, $dotmailerNewsletterTypeOptoutId, $form)
    {
        $getClickVal = $form->get('clickedElementValue')->getData();

        if ($getClickVal =='update' && $form->get('dotmailer_newsletter_type_id')->getData() && $dotmailerNewsletterTypeId) {
            $optedOut = array_diff($dotmailerNewsletterTypeId, $form->get('dotmailer_newsletter_type_id')->getData());
            if (is_array($optedOut) && count($optedOut) > 0) {
                return $optedOut;
            } else if ($dotmailerNewsletterTypeOptoutId) {
                $optedOut = array_diff($dotmailerNewsletterTypeOptoutId, $form->get('dotmailer_newsletter_type_id')->getData());
                if (is_array($optedOut) && count($optedOut) > 0) {
                    return $optedOut;
                }
            }
        } else if ($getClickVal =='unsubscribe') {
            $newsletterType = $this->em->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getKeyValueArray($this->container);
            return array_keys($newsletterType);
        } else if ($getClickVal =='update' && $form->get('dotmailer_newsletter_type_id')->getData() && $dotmailerNewsletterTypeOptoutId) {
            $optedOut = array_diff($dotmailerNewsletterTypeOptoutId, $form->get('dotmailer_newsletter_type_id')->getData());
            if (is_array($optedOut) && count($optedOut) > 0) {
                return $optedOut;
            }
        } else {
            return $dotmailerNewsletterTypeId;
        }
    }

    /**
     * Set default options.
     *
     * @param OptionsResolver $resolver
     *
     * @see \Symfony\Component\Form\AbstractType::setDefaultOptions()
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fa\Bundle\DotMailerBundle\Entity\Dotmailer',
        ));
    }

    /**
     * Get year choices.
     *
     * @return array
     */
    public function getYearChoices()
    {
        $yearArray = array();

        for ($i = date('Y'); $i >= 1920; $i--) {
            $yearArray[$i] = $i;
        }

        return $yearArray;
    }

    /**
     * Get day choices.
     *
     * @return array
     */
    public function getDayChoices()
    {
        $dayArray = array();

        for ($i = 1; $i <= 31; $i++) {
            $dayArray[$i] = $i;
        }

        return $dayArray;
    }

    /**
     * Add postcode field validation.
     *
     * @param object $form
     * Form instance.
     */
    protected function validatePostCode($form)
    {
        $location = $form->get('postcode')->getData();
        if ($location) {
            $postCode = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($location);
            if (! $postCode || $postCode->getTownId() == null || $postCode->getTownId() == 0) {
                $form->get('postcode')->addError(new FormError('Postcode is invalid.'));
            }
        }
    }

    /**
     * Add gender field validation.
     *
     * @param object $form
     * Form instance.
     */
    protected function validateGender($form)
    {
        $gender = $form->get('gender')->getData();
        if ($gender == '') {
            $form->get('gender')->addError(new FormError('Gender is required.'));
        }
    }
}
