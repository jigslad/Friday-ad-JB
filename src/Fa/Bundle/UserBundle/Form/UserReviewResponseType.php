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
use Fa\Bundle\UserBundle\Entity\UserReview;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\UserReviewRepository;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * This is rolr form.
 *
 * @author Samir Amrutya<samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserReviewResponseType extends AbstractType
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
        if ($this->request->get('is_mobile')) {
            $messageRows = 1;
            $builder->add('save', SubmitType::class, array('label' => 'Reply'));
        } else {
            $messageRows = 5;
            $builder->add('save', SubmitType::class, array('label' => 'Respond'));
        }

        $builder
            ->add('review_id', HiddenType::class, array('mapped' => false))
            ->add('responder_id', HiddenType::class, array('mapped' => false))
            ->add(
                'message',
                TextareaType::class,
                array(
                    /** @Ignore */
                    'label'       => false,
                    'constraints' => array(
                                        new NotBlank(array('message' => $this->translator->trans('Message should not be blank.', array(), 'validators'))),
                                        new Length(array('max' => 1000))),
                    'attr'        => array('class' => 'textcounter ', 'maxlength' => 1000, 'rows' => $messageRows, 'placeholder' => 'Write your reply message here')
                )
            );

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitData'));
    }

    /**
     * On post submit data.
     *
     * @param FormEvent $event
     */
    public function postSubmitData(FormEvent $event)
    {
        if ($event->getForm()->isValid()) {
            $this->save($event->getForm());
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
                'data_class'         => 'Fa\Bundle\UserBundle\Entity\UserReview',
                'translation_domain' => 'frontend-review',
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
        return 'user_review_response';
    }
    
    public function getBlockPrefix()
    {
        return 'user_review_response';
    }

    /**
     * Save user site.
     *
     * @param object $form Form object.
     */
    public function save($form)
    {
        $userReview   = $form->getData();
        $parentReview = $this->em->getReference('FaUserBundle:UserReview', $form->get('review_id')->getData());
        $responder    = $this->em->getReference('FaUserBundle:User', $form->get('responder_id')->getData());

        if ($parentReview && $responder) {
            $ad   = $parentReview->getAd();
            $user = $parentReview->getReviewer();

            $isSeller = 0;
            if ($parentReview->getUserReviewAdId() > 0) {
                // If responder and ad owner is same then responder is seller otherwise buyer.
                if ($ad->getUser()->getId() == $responder->getId()) {
                    $isSeller = 1;
                } else {
                    $isSeller = 0;
                }
            }

            // Add review response
            if (!$userReview->getId()) {
                $userReview->setParent($parentReview);
                $userReview->setUser($user);
                $userReview->setReviewer($responder);
                if ($ad && $parentReview->getUserReviewAdId() > 0) {
                    $userReview->setAd($ad);
                } else {
                    $userReview->setSubject($parentReview->getSubject());
                }
                $userReview->setUserReviewAdId($parentReview->getUserReviewAdId());
                $userReview->setMessage($form->get('message')->getData());
                $userReview->setStatus(UserReviewRepository::MODERATION_QUEUE_STATUS_SEND);
                $userReview->setIsSeller($isSeller);
                $userReview->setIpAddress($this->request->getClientIp());
                $this->em->persist($userReview);
                $this->em->flush($userReview);

                // send ad for moderation
                $this->em->getRepository('FaUserBundle:UserReview')->sendReviewForModeration($userReview->getId(), $this->container);
            }
        }
    }
}
