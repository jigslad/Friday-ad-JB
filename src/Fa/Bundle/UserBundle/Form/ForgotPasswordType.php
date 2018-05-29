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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is forget password form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class ForgotPasswordType extends AbstractType
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
        $builder
        ->add(
            'email',
            EmailType::class,
            array(
                'label' => 'Email address',
                'constraints' => array(
                    new Assert\NotBlank(array('message' => $this->translator->trans('Please enter email address.', array(), 'validators'))),
                    new Assert\Email(array('message' => $this->translator->trans('The email "{{ value }}" is not a valid email.', array(), 'validators')))
                )
            )
        )
        ->add('save', SubmitType::class, array('label' => 'Reset Password'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($form->isValid()) {
            $user = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('username' => $data['email'], 'is_half_account' => 0));
            if (!$user) {
                $form->get('email')->addError(new FormError($this->translator->trans('This email is not registered in Friday-Ad.', array(), 'validators')));
            } else if ($user && $user->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                $form->get('email')->addError(new FormError($this->translator->trans('Your status is not active.', array(), 'validators')));
            }
        }
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
                'data_class' => null,
                'translation_domain' => 'frontend-forgot-password',
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_forgot_password';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_forgot_password';
    }
}
