<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\MessageBundle\Repository\MessageRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This form is used for report user.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ReportUserType extends AbstractType
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
     * Reply id.
     *
     * @var integer
     */
    private $replyId;

    /**
     * Ad Owner Id.
     *
     * @var integer
     */
    private $adOwnerId;

    /**
     * Reason option array.
     *
     * @var array
     */
    private $reasonOptions;

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
        $this->reasonOptions = $this->em->getRepository('FaMessageBundle:Message')->getUserReportReasons();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->replyId = $options['replyId'];
        $this->adOwnerId = $options['adOwnerId'];

        $builder
            ->add(
                'reason',
                ChoiceType::class,
                array(
                    'required'  => true,
                    'multiple'  => false,
                    'expanded'  => true,
                    'mapped'  => false,
                    'data' => 1,
                    'choices'   => $this->reasonOptions,
                    'constraints' => new NotBlank(array('message' => $this->translator->trans('Please select reason.', array(), 'validators')))
                )
            )
            ->add(
                'comment',
                TextareaType::class,
                array(
                    'attr' => array('rows' => 5),
                    'mapped' => false,
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Continue'));

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) {
                $form = $event->getForm();

                if ($form->get('reason')->getData() == MessageRepository::REPORT_USER_REASON_OTHER && !$form->get('comment')->getData()) {
                    $event->getForm()->get('comment')->addError(new \Symfony\Component\Form\FormError('Please enter your reason.'));
                }
            }
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $messageSpammer = $event->getData();
        $form           = $event->getForm();

        if ($form->isValid()) {
            $reasonId = $form->get('reason')->getData();
            $reason = null;
            if ($reasonId == MessageRepository::REPORT_USER_REASON_OTHER) {
                $reason = $form->get('comment')->getData();
            } elseif (isset($this->reasonOptions[$reasonId])) {
                $reason = $this->reasonOptions[$reasonId];
            }
            $loggedInUser = (CommonManager::isAuth($this->container) ? CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser() : null);
            $rootMessageObj = $this->em->getRepository('FaMessageBundle:Message')->getMainMessage($this->replyId);
            $messageSpammer->setMessage($rootMessageObj);
            $messageSpammer->setAdId(($rootMessageObj->getMessageAdId() ? $rootMessageObj->getMessageAdId() : null));

            $messageSpammer->setReporter($this->em->getReference('FaUserBundle:User', $loggedInUser->getId()));
            if ($loggedInUser && $loggedInUser->getId() != $this->adOwnerId) {
                $messageSpammer->setSpammer($this->em->getReference('FaUserBundle:User', $this->adOwnerId));
            } elseif ($loggedInUser && $loggedInUser->getId() == $this->adOwnerId) {
                $messageSpammer->setSpammer($this->em->getReference('FaUserBundle:User', ($rootMessageObj && $rootMessageObj->getSender() ? $rootMessageObj->getSender()->getId() : null)));
            }

            $messageSpammer->setStatus(MessageRepository::MODERATION_QUEUE_STATUS_SEND);
            $messageSpammer->setReason($reason);
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
                'data_class' => 'Fa\Bundle\MessageBundle\Entity\MessageSpammer',
                'translation_domain' => 'frontend-report-user',
            )
        )->setDefined(array(
                'replyId',
                'adOwnerId',
            ));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return 'fa_message_spammer_report_user';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_message_spammer_report_user';
    }
}
