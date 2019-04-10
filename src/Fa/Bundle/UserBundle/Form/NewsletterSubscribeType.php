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
use Fa\Bundle\DotMailerBundle\Entity\Dotmailer;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This is user half account form.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class NewsletterSubscribeType extends AbstractType
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
     * The request instance.
     *
     * @var Request
     */
    private $request;

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
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->request    = $requestStack->getCurrentRequest();
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

        $loggedInUser = (CommonManager::isAuth($this->container) ? CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser() : null);

        $emailAlertLabel = 'Sign me up to the Friday-Ad newsletter';
        $thirdPartyEmailAlertLabel = 'Send me relevant offers and promotions from third parties';

        $builder
        	->add(
        		'email',
        	    EmailType::class,
        		array(
        		    'data'=>($loggedInUser)?$loggedInUser->getEmail():'',
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
		        		'data' => ($loggedInUser ? $loggedInUser->getIsEmailAlertEnabled() : false),
        		        'value' => ($loggedInUser ? $loggedInUser->getIsEmailAlertEnabled() : false),
        		)
        	)
	        ->add(
	        	'third_party_email_alert',
	            CheckboxType::class,
	        	array(
	        			'label' => $thirdPartyEmailAlertLabel,
	        			'mapped' => false,
	        			'data' => ($loggedInUser ? $loggedInUser->getIsThirdPartyEmailAlertEnabled() : false),
	        	        'value' => ($loggedInUser ? $loggedInUser->getIsThirdPartyEmailAlertEnabled() : false)
	        	)
	        )
	        ->add('save', SubmitType::class, array('label' => 'Create'));
	        $builder->add('save', SubmitType::class, array('label' => 'Subscribe', 'attr' => ['class' => 'footer-newsletter-btn']));
        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
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
    	$newsletterTypeIds = [];
    	if ($form->isValid()) {
    		$user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData()));
    		if (!$user) {
    			$user = new User();
    			$user->setIsHalfAccount(1);

    			$user->setUserName($form->get('email')->getData());
    			$user->setEmail($form->get('email')->getData());

    			//set user status
    			$userActiveStatus = $this->em->getRepository('FaEntityBundle:Entity')->find(EntityRepository::USER_STATUS_ACTIVE_ID);
    			$user->setStatus($userActiveStatus);

    			// set guid
    			$user->setGuid(CommonManager::generateGuid($form->get('email')->getData()));

    			// manually added third party email alert
    			if ($form->get('email_alert')->getData() == 1) {
    				$user->setIsEmailAlertEnabled(1);
    			}

    			// manually added third party email alert
    			if ($form->get('third_party_email_alert')->getData() == 1) {
    				$user->setIsThirdPartyEmailAlertEnabled(1);
    			}

    			$user->setCreatedAt(time());
    			$user->setUpdatedAt(time());

    			$this->em->persist($user);
    			$this->em->flush($user);
    		}

            file_put_contents('/var/www/html/newfriday-ad/web/uploads/testing.txt', 'line '.__LINE__.$this->_em->getRepository('FaDotMailerBundle:Dotmailer')->dotmailerFindByEmail($user->getEmail()).'|', FILE_APPEND);
    		$dotMailer = $this->em->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $form->get('email')->getData()));
    		if (!$dotMailer) {
    			$dotmailer = new Dotmailer();
    			$dotmailer->setOptIn(1);
    			$dotmailer->setFadUser(1);
    			$dotmailer->setDotmailerNewsletterUnsubscribe(0);
    			$dotmailer->setEmail($form->get('email')->getData());
    			$dotmailer->setGuid(CommonManager::generateGuid($form->get('email')->getData()));
    			$dotmailer->setIsSuppressed(0);
    			$dotmailer->setNewsletterSignupAt(time());
    			$dotmailer->setIsHalfAccount(1);
    			$dotmailer->setCreatedAt(time());
    			$dotmailer->setUpdatedAt(time());
    			$dotmailer->setOptInType(DotmailerRepository::OPTINTYPE);
    			$dotmailer->setFirstTouchPoint(DotmailerRepository::TOUCH_POINT_NEWSLETTER);
				

    			if($form->get('email_alert')->getData() == 1) {
    				$newsletterTypeIds = $this->em->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getAllNewsletterTypeByOrd($this->container, 47);
    			}
    			// manually added third party email alert
    			if ($form->get('third_party_email_alert')->getData() == 1) {
    				$newsletterTypeIds[] = 48;
    			}

    			if (is_array($newsletterTypeIds) && count($newsletterTypeIds) > 0) {
    				$dotmailer->setDotmailerNewsletterTypeId($newsletterTypeIds);
    			}

    			$this->em->persist($dotmailer);
                file_put_contents('/var/www/html/newfriday-ad/web/uploads/testing.txt', 'newsletter subscribe type|', FILE_APPEND);
    			$this->em->flush($dotmailer);

    			//send to dotmailer instantly.
    			$this->em->getRepository('FaDotMailerBundle:Dotmailer')->sendContactInfoToConsentDotmailerRequest($dotmailer,$this->container);
    			exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:subscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
    		}
    	}
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function onSubmit(FormEvent $event)
    {
    	$paaFieldRule = $event->getData();
    	$form         = $event->getForm();
    	if (!$form->get('email_alert')->getData()&& !$form->get('third_party_email_alert')->getData()) {
    		$form->get('email_alert')->addError(new FormError("Please choose atleast one below preferences."));
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
        return 'fa_user_newsletter';
    }

    public function getBlockPrefix()
    {
        return 'fa_user_newsletter';
    }
}
