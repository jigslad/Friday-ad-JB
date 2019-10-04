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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;

/**
 * This is newsletter form
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class NewsletterType extends AbstractType
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
        ->add('update_newsletter_preferences', SubmitType::class, array('label' => 'Update preferences'))
        ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitData'));
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
        
        if ($dotmailer && $dotmailer->getDotmailerNewsletterUnsubscribe()) {
            $form->add('dotmailer_newsletter_unsubscribe', CheckboxType::class, array('label' => '<b>Unsubscribe from all emails</b>', 'data' => $dotmailer->getDotmailerNewsletterUnsubscribe()));
        } else {
            $form->add('dotmailer_newsletter_unsubscribe', CheckboxType::class, array('label' => '<b>Unsubscribe from all emails</b>', 'data' => false));
        }
        
        $form->add('dotmailer_newsletter_type_id', ChoiceType::class, $fieldOptions);
    }
    
    /**
     * On post submit data.
     *
     * @param FormEvent $event
     */
    public function postSubmitData(FormEvent $event)
    {
        $this->save($event);
    }
    
    
    
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'user_newsletter';
    }
    
    public function getBlockPrefix()
    {
        return 'user_newsletter';
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
        $insertFirstPoint = 0;
        
        // refresh dotmailer object
        if ($dotmailer->getId()) {
            $this->em->refresh($dotmailer);
            if ($dotmailer->getIsContactSent()!=1)  {
                $insertFirstPoint = 1;
            }
        } else {
            $isNewToDotmailer = true;
            $dotmailer = new Dotmailer();
            $dotmailer->setFadUser(1);
            $dotmailer->setFirstTouchPoint(DotmailerRepository::TOUCH_POINT_ACCOUNT_PREFS); 
        }
        
        $dotmailerNewsletterTypeId       = $dotmailer->getDotmailerNewsletterTypeId();
        $dotmailerNewsletterTypeOptoutId = $dotmailer->getDotmailerNewsletterTypeOptoutId();
        
        if ($dotmailer && $dotmailer->getEmail() && $this->container->get('request_stack')->getCurrentRequest()->query->get('guid')) {
            $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $dotmailer->getEmail()));
        } else {
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
        }
        
        if (is_object($user)) {
            // Save business details
            $dotmailer->setEmail($user->getEmail());
            $dotmailer->setGuid(CommonManager::generateGuid($user->getEmail()));
            //$dotmailer->setOptIn();
            //$dotmailer->setOptInType();
            $dotmailer->setFirstName($user->getFirstName());
            $dotmailer->setLastName($user->getLastName());
            $dotmailer->setBusinessName($user->getBusinessName());
            if ($user->getRole()) {
                $dotmailer->setRoleId($user->getRole()->getId());
            }
            $dotmailer->setPhone($user->getPhone());
            
            // only process on n-values if unsubscribe doesn't selected
            if ($form->get('dotmailer_newsletter_unsubscribe')->getData() != 1 && ($dotmailer->getOptIn() !== false || $form->get('dotmailer_newsletter_type_id')->getData())) {
                if ($form->get('dotmailer_newsletter_type_id')->getData()) {
                    $dotmailer->setDotmailerNewsletterTypeId($form->get('dotmailer_newsletter_type_id')->getData());
                    $dotmailer->setDotmailerNewsletterTypeOptoutId($this->getOptoutNewsletterTypeId($dotmailerNewsletterTypeId, $dotmailerNewsletterTypeOptoutId, $form));
                } else {
                    $dotmailer->setDotmailerNewsletterTypeId(null);
                    $dotmailer->setDotmailerNewsletterTypeOptoutId($this->getOptoutNewsletterTypeId($dotmailerNewsletterTypeId, $dotmailerNewsletterTypeOptoutId, $form));
                }
            }
            
            // update last_paid_at
            if (!$dotmailer->getLastPaidAt()) {
                $lastPaidAt = $this->em->getRepository('FaPaymentBundle:Payment')->getLastPaidAt($user->getId());
                if ($lastPaidAt && isset($lastPaidAt['created_at'])) {
                    $dotmailer->setLastPaidAt($lastPaidAt['created_at']);
                }
            }
            
            if ($form->get('dotmailer_newsletter_unsubscribe')->getData() == 1 || ($dotmailer && $dotmailer->getIsSuppressed())) {
                $dotmailer->setOptIn(0);
            } else {
                $dotmailer->setOptIn(1);
            }
            
            $dotmailer->setDotmailerNewsletterUnsubscribe($form->get('dotmailer_newsletter_unsubscribe')->getData());
            
            if ($dotmailer && $dotmailer->getIsSuppressed()) {
                $dotmailer->setDotmailerNewsletterUnsubscribe(1);
            }
            
            $this->em->persist($dotmailer);
            $this->em->flush($dotmailer);
            
            if(in_array('48',$dotmailer->getDotmailerNewsletterTypeId())) {
                $user->setIsThirdPartyEmailAlertEnabled(1);
            }            
            
            if ($dotmailer->getOptIn() != 1) {
                
                // opt out user
                $user->setIsEmailAlertEnabled(0);
                $this->em->persist($user);
                $this->em->flush($user);
                
                //unsubscribe from dotmailer.
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:unsubscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
                // remove contact from dotmailer.
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:delete-contact --email='.$dotmailer->getEmail().' >/dev/null &');
            } if ($dotmailer->getOptIn() == 1 && $user->getIsEmailAlertEnabled() != 1) {
                // opt in user
                $user->setIsEmailAlertEnabled(1);
                $this->em->persist($user);
                $this->em->flush($user);
            }
            if ($insertFirstPoint== 1 && ($user->getIsEmailAlertEnabled()==1 || $user->getIsThirdPartyEmailAlertEnabled()==1)) {
                $dotmailer->setFirstTouchPoint(DotmailerRepository::TOUCH_POINT_ACCOUNT_PREFS);
                $dotmailer->setIsContactSent(1);
                $this->em->persist($dotmailer);
                $this->em->flush($dotmailer);
            }

            
            //send to dotmailer instantly.
            if ($isNewToDotmailer || $user->getIsEmailAlertEnabled() ==1) {
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:subscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
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
        if ($form->get('dotmailer_newsletter_type_id')->getData() && $dotmailerNewsletterTypeId) {
            $optedOut = array_diff($dotmailerNewsletterTypeId, $form->get('dotmailer_newsletter_type_id')->getData());
            if (is_array($optedOut) && count($optedOut) > 0) {
                return $optedOut;
            } else if ($dotmailerNewsletterTypeOptoutId) {
                $optedOut = array_diff($dotmailerNewsletterTypeOptoutId, $form->get('dotmailer_newsletter_type_id')->getData());
                if (is_array($optedOut) && count($optedOut) > 0) {
                    return $optedOut;
                }
            }
        } else if ($form->get('dotmailer_newsletter_unsubscribe')->getData()) {
            $newsletterType = $this->em->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getKeyValueArray($this->container);
            return array_keys($newsletterType);
        } else if ($form->get('dotmailer_newsletter_type_id')->getData() && $dotmailerNewsletterTypeOptoutId) {
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
}
