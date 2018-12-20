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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\Url;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * This is user business shop profile form.
 *
  * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserBusinessShopProfileType extends AbstractType
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager class object.
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
        $userBusinessCategoryId    = null;
        $profileExposureCategoryId = null;
        $loggedInUser = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
        if ($loggedInUser && $loggedInUser->getBusinessCategoryId()) {
            $userBusinessCategoryId    = $loggedInUser->getBusinessCategoryId();
            $profileExposureCategoryId = $loggedInUser->getBusinessCategoryId();
        }
        $userSite = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedInUser->getId()));
        if ($userSite && $userSite->getProfileExposureCategoryId()) {
            $profileExposureCategoryId = $userSite->getProfileExposureCategoryId();
        }
        $builder
            ->add(
                'youtube_video_url',
                TextType::class,
                array(
                    'label' => 'Video',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.'))),
                )
            )
            ->add(
                'facebook_url',
                TextType::class,
                array(
                    'label' => 'Facebook',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )
            ->add(
                'google_url',
                TextType::class,
                array(
                    'label' => 'Google +',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )
            ->add(
                'twitter_url',
                TextType::class,
                array(
                    'label' => 'Twitter',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )
            ->add(
                'pinterest_url',
                TextType::class,
                array(
                    'label' => 'Pinterest',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            )->add(
                'instagram_url',
                TextType::class,
                array(
                    'label' => 'Instagram',
                    //'constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.')))
                )
            );

        $builder->add('save_shop_profile_changes', SubmitType::class, array('label' => 'Save changes'));

        if (in_array($userBusinessCategoryId, array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
            $builder
            ->add(
                'zip',
                TextType::class,
                array(
                    'label' => 'Postcode',
                    'mapped' => false,
                    'data' => ($loggedInUser->getZip() ? $loggedInUser->getZip() : null),
                    'constraints' => array(
                        new NotBlank(array('message' => $this->translator->trans("You have to introduce postcode.", array(), 'validators'))),
                    )
                )
            )
            ->add('show_map', CheckboxType::class, array('label' => 'Show map on business profile'));
            $totalLevel = $this->em->getRepository('FaEntityBundle:Category')->getMaxLevel();
            $categoryPath = array();
            if ('POST' === $this->container->get('request_stack')->getCurrentRequest()->getMethod()) {
                $parameters = $this->container->get('request_stack')->getCurrentRequest()->get($this->getName());
                if (isset($parameters['category_last_level'])) {
                    for ($i = $parameters['category_last_level']; $i >= 1; $i--) {
                        if (isset($parameters['category_'.$i]) && $parameters['category_'.$i]) {
                            $categoryPath = array_keys($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($parameters['category_'.$i], false, $this->container));
                            break;
                        }
                    }
                }
            } else {
                $categoryPath = array_keys($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($profileExposureCategoryId, false, $this->container));
            }
            $builder->add('category_last_level', HiddenType::class, array('mapped' => false, 'data' => count($categoryPath)));
            if ($totalLevel) {
                for ($i = 1; $i <= $totalLevel; $i++) {
                    $choices = array();
                    $data    = null;
                    $constraints = array();
                    if ($i == 1) {
                        $topCategories = $this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId(1);
                        $firstLevelCatogories = array();
                        foreach ($topCategories as $topCategoryId => $topCategoryName) {
                            if (in_array($topCategoryId, array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                                $firstLevelCatogories[$topCategoryId] = $topCategoryName;
                            }
                        }
                        $choices = array('' => 'Please select category') + $firstLevelCatogories;
                        $constraints = array(
                            new NotBlank(array('message' => $this->translator->trans("You have to select a category.", array(), 'validators'))),
                        );
                        $optionArray = array(
                            'placeholder' => 'Please select category',
                            'attr'        => array('class' => 'fa-select-white category category_'.$i),
                            'label'       => 'Category',
                            'data'        => (count($categoryPath) ? $categoryPath[0] : $profileExposureCategoryId),
                            'required'    => true,
                            'choices'     => array_flip($choices),
                            'constraints' => $constraints,
                        );
                    } else {
                        if ($totalLevel > 1) {
                            if (!count($categoryPath) && $i == 2 && 'POST' !== $this->container->get('request_stack')->getCurrentRequest()->getMethod()) {
                                $choices = array('' => 'Please select subcategory') + $this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($userBusinessCategoryId);
                                $constraints = array(
                                    new NotBlank(array('message' => $this->translator->trans("You have to select a subcategory.", array(), 'validators'))),
                                );
                            } elseif (isset($categoryPath[$i-1]) && isset($categoryPath[$i-2])) {
                                $choices = array('' => 'Please select subcategory') + $this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($categoryPath[$i-2]);
                                $data    = isset($categoryPath[$i-1]) ? $categoryPath[$i-1] : null;
                                $constraints = array(
                                    new NotBlank(array('message' => $this->translator->trans("You have to select a subcategory.", array(), 'validators'))),
                                );
                            } elseif (isset($categoryPath[$i-2])) {
                                $choices = $this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($categoryPath[$i-2]);
                                if (count($choices)) {
                                    $choices = array('' => 'Please select subcategory') + $choices;
                                }
                            }
                        }
                        $optionArray = array(
                            'placeholder' => 'Please select subcategory',
                            'attr'        => array('class' => 'fa-select-white category category_'.$i),
                            'label'       => 'Sub-category',
                            'choices'     => array_flip($choices),
                            'data'        => $data,
                            'constraints' => $constraints,
                            'required'    => true,
                        );
                    }
                    $builder->addEventSubscriber(
                        new AddCategoryChoiceFieldSubscriber(
                            $this->container,
                            $i,
                            'category',
                            $optionArray,
                            null,
                            $totalLevel
                        )
                    );
                }
            }
        } else {
            $builder->add(
                'zip',
                TextType::class,
                array(
                    'label' => 'Postcode',
                    'mapped' => false,
                    'data' => ($loggedInUser->getZip() ? $loggedInUser->getZip() : null),
                )
            );
        }
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function ($event) {
                $form     = $event->getForm();
                $postCode = trim($form->get('zip')->getData());
                $youtubeVideoUrl = trim($form->get('youtube_video_url')->getData());
                if ($postCode) {
                    $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                    if (!$postCodeObj) {
                        $event->getForm()->get('zip')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid postcode.', array(), 'validators')));
                    }
                }

                // validate youtube video url.
                if ($youtubeVideoUrl) {
                    $youtubeVideoId = CommonManager::getYouTubeVideoId($youtubeVideoUrl);
                    if (!$youtubeVideoId) {
                        $event->getForm()->get('youtube_video_url')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter valid youtube video url.', array(), 'validators')));
                    }
                }

                if ($form->has('category_last_level') && $form->get('category_last_level')->getData()) {
                    for ($i = 1; $i <= $form->get('category_last_level')->getData(); $i++) {
                        if (!$form->get('category_'.$i)->getData() && !count($form->get('category_'.$i)->getConfig()->getOption('constraints'))) {
                            $event->getForm()->get('category_'.$i)->addError(new \Symfony\Component\Form\FormError($this->translator->trans('You have to select a subcategory.', array(), 'validators')));
                        }
                    }
                }
            }
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
    }

    /**
     * This function is called on post submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isValid()) {
            $userSite = $form->getData();
            $user = $userSite->getUser();
            $postCode = trim($form->get('zip')->getData());
            if ($form->has('category_last_level') && $form->get('category_last_level')->getData()) {
                $categoryLevel = $form->get('category_last_level')->getData();
                if ($form->get('category_'.$categoryLevel)->getData()) {
                    $userSite->setProfileExposureCategoryId($form->get('category_'.$categoryLevel)->getData());
                }
            }
            if ($user) {
                if ($postCode) {
                    $postCodeObj = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                    if ($postCodeObj->getTownId()) {
                        $townObj = $this->em->getRepository('FaEntityBundle:Location')->find($postCodeObj->getTownId());
                        $user->setZip($postCode);
                        $user->setLocationTown($townObj);
                        $user->setLocationDomicile($townObj->getParent());
                        $user->setLocationCountry($this->em->getReference('FaEntityBundle:Location', LocationRepository::COUNTY_ID));
                    }
                } else {
                    $user->setZip(null);
                    $user->setLocationTown(null);
                    $user->setLocationDomicile(null);
                    $user->setLocationCountry(null);
                }

                $this->em->persist($user);
                $this->em->flush($user);
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
                'data_class' => 'Fa\Bundle\UserBundle\Entity\UserSite',
                'translation_domain' => 'frontend-my-profile',
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'fa_user_user_business_shop_profile';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_user_user_business_shop_profile';
    }
}
