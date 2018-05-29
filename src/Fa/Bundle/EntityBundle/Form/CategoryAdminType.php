<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Entity\CategoryRecommendedSlot;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Category admin type form.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CategoryAdminType extends AbstractType
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
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        $this->em         = $this->container->get('doctrine')->getManager();
        $this->translator = CommonManager::getTranslator($this->container);
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $recommendedSlotArray = array();
        if ($builder->getData()->getId()) {
            $recommendedSlotArray = $this->em->getRepository('FaEntityBundle:CategoryRecommendedSlot')->getCategoryRecommendedSlotArrayByCategoryId($builder->getData()->getId(), $this->container);
        }
        $builder
            ->add('name')
            ->add('slug')
            ->add('display_on_footer', CheckboxType::class, array('label' => 'Display on Footer', 'required' => false))
            ->add('synonyms_keywords', TextareaType::class, array('label' => 'Synonyms Keywords', 'required' => false))
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(EntityRepository::getStatusArray($this->container))
                )
            )
            ->add('is_paa_disabled', CheckboxType::class, array('label' => "Don't show in PAA category search?", 'required' => false))
            ->add('is_nimber_enabled', CheckboxType::class, array('label' => 'Enable Nimber', 'required' => false))
            ->add(
                'nimber_size',
                ChoiceType::class,
                array(
                    'choices' => array_flip($this->em->getRepository('FaEntityBundle:Category')->getNimberSizeOptions()),
                    'placeholder' => 'Select nimber size',
                    'required' => false,
                )
            )
            ->add('is_finance_enabled', CheckboxType::class, array('label' => 'Enable Third-party', 'required' => false))
            ->add(
                'finance_title',
                TextType::class,
                array(
                    'label' => 'Third-party button',
                )
            )
            ->add(
                'finance_url',
                TextType::class,
                array(
                    'label' => 'Third-party url',
                )
            )
            ->add('has_recommended_slot', CheckboxType::class, array('label' => 'Has recommended slots?', 'required' => false))
            ->add('is_oneclickenq_enabled', CheckboxType::class, array('label' => 'Enable One click enquire', 'required' => false))
            ->add('save', SubmitType::class)
            ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));

        //recommended slots
        for ($i = 1; $i <= 3; $i++) {
            $builder->add('recommended_slot_title_'.$i, TextType::class, array('mapped' => false, 'label' => 'Title', 'data' => (isset($recommendedSlotArray[$i-1]) ? $recommendedSlotArray[$i-1]['title'] : '') ));
            $builder->add('recommended_slot_sub_title_'.$i, TextareaType::class, array('attr' => array('rows' => 5), 'mapped' => false, 'label' => 'Sub title', 'data' => (isset($recommendedSlotArray[$i-1]) ? $recommendedSlotArray[$i-1]['sub_title'] : '') ));
            $builder->add('recommended_slot_user_id_'.$i, TextType::class, array('mapped' => false, 'label' => 'User id', 'data' => (isset($recommendedSlotArray[$i-1]) ? $recommendedSlotArray[$i-1]['user_id'] : '') ));
            $builder->add('recommended_slot_url_'.$i, TextType::class, array('constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.'))), 'mapped' => false, 'label' => 'Url', 'data' => (isset($recommendedSlotArray[$i-1]) ? $recommendedSlotArray[$i-1]['url'] : '') ));
        }
        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'))
        ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
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
                'data_class' => 'Fa\Bundle\EntityBundle\Entity\Category'
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
        return 'fa_entity_category_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_entity_category_admin';
    }

    /**
     * On pre submit.
     *
     * @param FormEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onPreSubmit(FormEvent $event)
    {
        $parentId = $this->container->get('request_stack')->getCurrentRequest()->get('parent_id', null);

        if ($parentId) {
            $data = $event->getData();
            $form = $event->getForm();
            $parent = $this->em->getRepository('FaEntityBundle:Category')->find($parentId);

            if (!$parent) {
                throw new NotFoundHttpException('Unable to find Category entity.');
            }
            $form->add('parent', HiddenType::class);
            $data['parent'] = $parent;
            $event->setData($data);
        }
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $form             = $event->getForm();
        $isNimberEnabled  = $form->get('is_nimber_enabled')->getData();
        $nimberSize       = $form->get('nimber_size')->getData();
        $isFinanceEnabled = $form->get('is_finance_enabled')->getData();
        $financeTitle     = $form->get('finance_title')->getData();
        $financeUrl       = $form->get('finance_url')->getData();
        $hasRecommendedSlot = $form->get('has_recommended_slot')->getData();

        //removed post code validation
        if ($isNimberEnabled && !$nimberSize) {
            $event->getForm()->get('nimber_size')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please select nimber size.', array(), 'validators')));
        }

        if ($isFinanceEnabled && !$financeTitle) {
            $event->getForm()->get('finance_title')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter third-party button text.', array(), 'validators')));
        }

        if ($isFinanceEnabled && !$financeUrl) {
            $event->getForm()->get('finance_url')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter third-party url.', array(), 'validators')));
        }

        if ($hasRecommendedSlot) {
            $oneSelectRecommendedSlotFlag = true;
            $recommendedSlotError = array();
            for ($i = 1; $i <=3; $i++) {
                if ($form->get('recommended_slot_title_'.$i)->getData() || $form->get('recommended_slot_sub_title_'.$i)->getData() || $form->get('recommended_slot_user_id_'.$i)->getData() || $form->get('recommended_slot_url_'.$i)->getData()) {
                    $oneSelectRecommendedSlotFlag = false;
                    $recommendedSlotError[] = $i;
                }
                if (!$oneSelectRecommendedSlotFlag && in_array($i, $recommendedSlotError)) {
                    if (!$form->get('recommended_slot_title_'.$i)->getData()) {
                        $form->get('recommended_slot_title_'.$i)->addError(new FormError('Please enter title.'));
                    }
                    if (!$form->get('recommended_slot_sub_title_'.$i)->getData()) {
                        $form->get('recommended_slot_sub_title_'.$i)->addError(new FormError('Please enter sub title.'));
                    }
                    if (!$form->get('recommended_slot_user_id_'.$i)->getData()) {
                        $form->get('recommended_slot_user_id_'.$i)->addError(new FormError('Please enter user id.'));
                    } elseif ($form->get('recommended_slot_user_id_'.$i)->getData()) {
                        $userObj = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('id' => $form->get('recommended_slot_user_id_'.$i)->getData(), 'status' => EntityRepository::USER_STATUS_ACTIVE_ID));
                        if (!$userObj) {
                            $form->get('recommended_slot_user_id_'.$i)->addError(new FormError('Please enter valid active user id.'));
                        }
                    }
                    if (!$form->get('recommended_slot_url_'.$i)->getData()) {
                        $form->get('recommended_slot_url_'.$i)->addError(new FormError('Please enter url.'));
                    }
                }
            }
            if ($oneSelectRecommendedSlotFlag) {
                $form->get('has_recommended_slot')->addError(new FormError('Please enter atleast one recommended slot.'));
            }
        }
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event event instance.
     */
    public function onPostSubmit(FormEvent $event)
    {
        $category = $event->getData();
        $form    = $event->getForm();
        $hasRecommendedSlot = $form->get('has_recommended_slot')->getData();

        if ($form->isValid()) {
            $insertRecommendedSlotFlag = true;
            //remove category recommended slots
            if ($category->getId()) {
                $recommendedSlotArray = $this->em->getRepository('FaEntityBundle:CategoryRecommendedSlot')->getCategoryRecommendedSlotArrayByCategoryId($category->getId(), $this->container);
                $recommendedSlotFromArray = array();
                for ($i = 1; $i <=3; $i++) {
                    if ($form->get('recommended_slot_title_'.$i)->getData() && $form->get('recommended_slot_sub_title_'.$i)->getData() && $form->get('recommended_slot_user_id_'.$i)->getData() && $form->get('recommended_slot_url_'.$i)->getData()) {
                        $recommendedSlotFromArray[] = array(
                            'title' => $form->get('recommended_slot_title_'.$i)->getData(),
                            'sub_title' => $form->get('recommended_slot_sub_title_'.$i)->getData(),
                            'user_id' => (int)$form->get('recommended_slot_user_id_'.$i)->getData(),
                            'url' => $form->get('recommended_slot_url_'.$i)->getData(),
                        );
                    }
                }

                if (md5(serialize($recommendedSlotArray)) == md5(serialize($recommendedSlotFromArray))) {
                    $insertRecommendedSlotFlag = false;
                }
                if ($insertRecommendedSlotFlag || !$hasRecommendedSlot) {
                    $this->em->getRepository('FaEntityBundle:CategoryRecommendedSlot')->removeRecordsByCategoryId($category->getId());
                    $culture = CommonManager::getCurrentCulture($this->container);
                    CommonManager::removeCachePattern($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryRecommendedSlot')->getTableName().'|getCategoryRecommendedSlotArrayByCategoryId|*');
                    CommonManager::removeCachePattern($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryRecommendedSlot')->getTableName().'|getAdDetailCategoryRecommendedSlotArrayByCategoryId|*');
                }
            }

            //save seo tool popular search
            if ($hasRecommendedSlot && $insertRecommendedSlotFlag) {
                for ($i = 1; $i <=3; $i++) {
                    if ($form->get('recommended_slot_title_'.$i)->getData() && $form->get('recommended_slot_sub_title_'.$i)->getData() && $form->get('recommended_slot_user_id_'.$i)->getData() && $form->get('recommended_slot_url_'.$i)->getData()) {
                        $recommendedSlot = new CategoryRecommendedSlot();
                        $recommendedSlot->setCategory($category);
                        $recommendedSlot->setTitle($form->get('recommended_slot_title_'.$i)->getData());
                        $recommendedSlot->setSubTitle($form->get('recommended_slot_sub_title_'.$i)->getData());
                        $recommendedSlot->setUserId($form->get('recommended_slot_user_id_'.$i)->getData());
                        $recommendedSlot->setUrl($form->get('recommended_slot_url_'.$i)->getData());
                        $this->em->persist($recommendedSlot);
                    }
                }
                $this->em->flush();
            }
        }
    }
}
