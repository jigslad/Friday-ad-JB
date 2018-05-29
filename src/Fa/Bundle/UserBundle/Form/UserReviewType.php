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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This is rolr form.
 *
 * @author Samir Amrutya<samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserReviewType extends AbstractType
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
     * Request instance.
     *
     * @var object
     */
    protected $request;

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
        $builder
            ->add('user_id', HiddenType::class, array('mapped' => false))
            ->add('reviewer_id', HiddenType::class, array('mapped' => false))
            ->add('ad_id', HiddenType::class, array('mapped' => false))
            ->add(
                'rating',
                ChoiceType::class,
                array(
                    'mapped'      => false,
                    'choices'     => array_flip(CommonManager::getStraRatingLabels($this->container)),
                    'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Rating is required.', array(), 'validators')))),
                    'placeholder' => 'Any'
                )
            )
            ->add(
                'message',
                TextareaType::class,
                array(
                    'label'       => 'Tell us more',
                    'constraints' => array(new Length(array('max' => 1000))),
                    'attr'        => array('class' => 'textcounter ', 'maxlength' => 1000, 'rows' => 5)
                )
            )
            ->add('save', SubmitType::class, array('label' => 'Send'));

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
        return 'user_review';
    }
    
    public function getBlockPrefix()
    {
        return 'user_review';
    }

    /**
     * Save user site.
     *
     * @param object $form Form object.
     */
    public function save($form)
    {
        $userReview     = $form->getData();
        $rootUserReview = $this->em->getReference('FaUserBundle:UserReview', 1);
        $user           = $this->em->getReference('FaUserBundle:User', $form->get('user_id')->getData());
        $reviewer       = $this->em->getReference('FaUserBundle:User', $form->get('reviewer_id')->getData());
        $ad             = $this->em->getReference('FaAdBundle:Ad', $form->get('ad_id')->getData());

        if ($user && $reviewer && $ad) {
            $isSeller = 0;
            if ($form->get('ad_id')->getData() > 0) {
                // If reviewer and ad owner is same then reviewer is seller otherwise buyer.
                if ($ad->getUser()->getId() == $reviewer->getId()) {
                    $isSeller = 1;
                } else {
                    $isSeller = 0;
                }
            }

            // Add review
            if (!$userReview->getId()) {
                $userReview->setParent($rootUserReview);
                $userReview->setUser($user);
                $userReview->setReviewer($reviewer);
                if ($ad && $form->get('ad_id')->getData() > 0) {
                    $userReview->setAd($ad);
                } else {
                    $adMessageObject = $this->em->getRepository('FaMessageBundle:Message')->findOneBy(array('message_ad_id' => $form->get('ad_id')->getData()));
                    if ($adMessageObject) {
                        $userReview->setSubject($adMessageObject->getSubject());
                    }
                }
                $userReview->setMessage($form->get('message')->getData());
                $userReview->setUserReviewAdId($form->get('ad_id')->getData());
                $userReview->setRating($form->get('rating')->getData());
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
