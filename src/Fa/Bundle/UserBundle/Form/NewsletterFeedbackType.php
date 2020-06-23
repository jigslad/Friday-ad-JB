<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
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
use Fa\Bundle\UserBundle\Entity\NewsletterFeedback;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
/**
 * This is newsletter form
 *
 * @author GauravAggarwal <gaurav.aggarwal@fridaymediagroup.com>
 * @copyright  2018 Friday Media Group Ltd
 * @version v1.0
 */
class NewsletterFeedbackType extends AbstractType
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
	 * @param Doctrine                $doctrine         Doctrine object.
	 * @param EncoderFactoryInterface $security_encoder Object.
	 * @param ContainerInterface      $container        Object.
	 */
	public function __construct(Doctrine $doctrine, EncoderFactoryInterface $security_encoder, ContainerInterface $container)
	{
		$this->em                = $doctrine->getManager();
		$this->translator        = CommonManager::getTranslator($container);
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
		->add('reason', ChoiceType::class, [
		        'choices' => array_flip($this->em->getRepository('FaUserBundle:NewsletterFeedback')->getFeedbackOptions()),
				'required'  => true,
				'multiple'  => false,
				'expanded'  => true,
				'mapped'    => false,
		])
		->add('otherReason', TextareaType::class, ['label' => 'Others', 'attr' => ['class' => 'textcounter white-bg', 'maxlength' => '1000']])
		->add('feedback', SubmitType::class, array('label' => 'Give your feedback'))
		->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'))
		;
	}
	
	/**
	 * This function is called on submit data event of form.
	 *
	 * @param FormEvent $event object.
	 */
	public function onSubmit(FormEvent $event)
	{
		$form = $event->getForm();
		
		if (!$form->get('reason')->getData()) {
			$form->get('reason')->addError(new FormError($this->translator->trans('Please choose the option.', array(), 'validators')));
		}
		
		if ($form->get('reason')->getData() && $form->get('reason')->getData() == '6' && !$form->get('otherReason')->getData()) {
			$form->get('otherReason')->addError(new FormError($this->translator->trans('Please enter the message.', array(), 'validators')));
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Form\FormTypeInterface::getName()
	 */
	public function getName()
	{
		return 'newsletter_feedback';
	}
	
}
?>
