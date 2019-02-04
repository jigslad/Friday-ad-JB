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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

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
            $recommendedSlotSearchArray = $this->em->getRepository('FaEntityBundle:CategoryRecommendedSlot')->getCategoryRecommendedSlotSearchlistArrayByCategoryId($builder->getData()->getId(), $this->container);
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
            ->add('is_oneclickenq_enabled', CheckboxType::class, array('label' => 'Enable One click enquire', 'required' => false));
            
        /* Upgreade option enabled for root category */
        if (($builder->getData()->getLvl() == '1' && $builder->getData()->getRoot() == '1') || $this->container->get('request_stack')->getCurrentRequest()->get('parent_id', null) == '1') {
            $builder->add('is_featured_upgrade_enabled', CheckboxType::class, array('label' => 'Featured upgrade', 'required' => false))
                    ->add(
                        'featured_upgrade_info',
                        TextType::class,
                        array(
                            'label' => 'Category stats/info',
                            'required' => false
                        )
                    )
                    ->add(
                        'featured_upgrade_btn_txt',
                        TextType::class,
                        array(
                            'label' => 'CTA text',
                            'required' => false
                        )
                    );
        }
        $builder
            ->add('has_recommended_slot_searchlist', CheckboxType::class, array('label' => 'Has recommended slots for search list page?', 'required' => false))
            ->add('is_oneclickenq_enabled', CheckboxType::class, array('label' => 'Enable One click enquire', 'required' => false))
            ->add('save', SubmitType::class)
            ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));

        //recommended slots
        $recommendedSlotUrl = array();
        for ($i = 1; $i <= 3; $i++) {
            $builder->add('recommended_slot_title_'.$i, TextType::class, array('mapped' => false, 'label' => 'Title', 'data' => (isset($recommendedSlotArray[$i-1]) ? $recommendedSlotArray[$i-1]['title'] : '') ));
            $builder->add('recommended_slot_sub_title_'.$i, TextareaType::class, array('attr' => array('rows' => 5), 'mapped' => false, 'label' => 'Sub title', 'data' => (isset($recommendedSlotArray[$i-1]) ? $recommendedSlotArray[$i-1]['sub_title'] : '') ));
            $builder->add('recommended_slot_user_id_'.$i, TextType::class, array('mapped' => false, 'label' => 'User id', 'data' => (isset($recommendedSlotArray[$i-1]) ? $recommendedSlotArray[$i-1]['user_id'] : '') ));
            if (isset($recommendedSlotArray[$i-1]) && $recommendedSlotArray[$i-1]['url']!='') {
                $recommendedSlotUrl[$i-1] = $recommendedSlotArray[$i-1]['url'];
                $recommendedSlotUrl[$i-1]  = str_replace('{', '%7B', $recommendedSlotUrl[$i-1]);
                $recommendedSlotUrl[$i-1]  = str_replace('}', '%7D', $recommendedSlotUrl[$i-1]);
            } else {
                $recommendedSlotUrl[$i-1] = '';
            }
            $builder->add('recommended_slot_url_'.$i, TextType::class, array('constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.'))), 'mapped' => false, 'label' => 'Url', 'data' => $recommendedSlotUrl[$i-1] ));
        }

        //recommended slots
        $st = 0;
        $i=1;
        $recommendedSearchSlotUrl = array();
        for ($k = 1; $k <= 6; $k++) {
            for ($j = 1; $j <= 3; $j++) {
                if (!empty($recommendedSlotSearchArray)) {
                    if ($recommendedSlotSearchArray[$st]['creative_group']== $k && $recommendedSlotSearchArray[$st]['creative_ord']==$j && count($recommendedSlotSearchArray)>$st) {
                        $builder->add('recommended_slot_searchlist_title_'.$i, TextType::class, array('mapped' => false, 'label' => 'Title', 'data' => (isset($recommendedSlotSearchArray[$st]) ? $recommendedSlotSearchArray[$st]['title'] : '') ));
                        $builder->add('recommended_slot_searchlist_sub_title_'.$i, TextareaType::class, array('attr' => array('rows' => 5), 'mapped' => false, 'label' => 'Sub title', 'data' => (isset($recommendedSlotSearchArray[$st]) ? $recommendedSlotSearchArray[$st]['sub_title'] : '') ));
                        $builder->add('recommended_slot_searchlist_slot_file_'.$i, FileType::class, array('mapped' => false, 'label' => 'Image'));
                        $builder->add('recommended_slot_searchlist_slot_filename_'.$i, HiddenType::class, array('mapped' => false, 'data' => (isset($recommendedSlotSearchArray[$st]) ? $recommendedSlotSearchArray[$st]['slot_filename'] : '')));
                        if (isset($recommendedSlotSearchArray[$st]) && $recommendedSlotSearchArray[$st]['url']!='') {
                            $recommendedSearchSlotUrl[$st] = $recommendedSlotSearchArray[$st]['url'];
                            $recommendedSearchSlotUrl[$st] = str_replace('{', '%7B', $recommendedSearchSlotUrl[$st]);
                            $recommendedSearchSlotUrl[$st]  = str_replace('}', '%7D', $recommendedSearchSlotUrl[$st]);
                        } else {
                            $recommendedSearchSlotUrl[$st] = '';
                        }
                        $builder->add('recommended_slot_searchlist_url_'.$i, TextType::class, array('constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.'))), 'mapped' => false, 'label' => 'Url', 'data' => $recommendedSearchSlotUrl[$st] ));
                        $builder->add('recommended_slot_searchlist_creative_group_'.$i, HiddenType::class, array('mapped' => false,'data' => (isset($recommendedSlotSearchArray[$st]) ? $recommendedSlotSearchArray[$st]['creative_group'] : '')));
                        $builder->add('recommended_slot_searchlist_creative_ord_'.$i, HiddenType::class, array('mapped' => false,'data' => (isset($recommendedSlotSearchArray[$st]) ? $recommendedSlotSearchArray[$st]['creative_ord'] : '')));
                        if (count($recommendedSlotSearchArray)-1 > $st) {
                            $st++;
                        }
                    } else {
                        $builder->add('recommended_slot_searchlist_title_'.$i, TextType::class, array('mapped' => false, 'label' => 'Title' ));
                        $builder->add('recommended_slot_searchlist_sub_title_'.$i, TextareaType::class, array('attr' => array('rows' => 5), 'mapped' => false, 'label' => 'Sub title' ));
                        $builder->add('recommended_slot_searchlist_slot_file_'.$i, FileType::class, array('mapped' => false, 'label' => 'Image'));
                        $builder->add('recommended_slot_searchlist_slot_filename_'.$i, HiddenType::class, array('mapped' => false, ));
                        $builder->add('recommended_slot_searchlist_url_'.$i, TextType::class, array('constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.'))), 'mapped' => false, 'label' => 'Url'));
                        $builder->add('recommended_slot_searchlist_creative_group_'.$i, HiddenType::class, array('mapped' => false));
                        $builder->add('recommended_slot_searchlist_creative_ord_'.$i, HiddenType::class, array('mapped' => false));
                    }
                } else {
                    $builder->add('recommended_slot_searchlist_title_'.$i, TextType::class, array('mapped' => false, 'label' => 'Title' ));
                    $builder->add('recommended_slot_searchlist_sub_title_'.$i, TextareaType::class, array('attr' => array('rows' => 5), 'mapped' => false, 'label' => 'Sub title' ));
                    $builder->add('recommended_slot_searchlist_slot_file_'.$i, FileType::class, array('mapped' => false, 'label' => 'Image'));
                    $builder->add('recommended_slot_searchlist_slot_filename_'.$i, HiddenType::class, array('mapped' => false, ));
                    $builder->add('recommended_slot_searchlist_url_'.$i, TextType::class, array('constraints' => array(new Url(array('message' => 'Please enter valid url with http or https.'))), 'mapped' => false, 'label' => 'Url'));
                    $builder->add('recommended_slot_searchlist_creative_group_'.$i, HiddenType::class, array('mapped' => false));
                    $builder->add('recommended_slot_searchlist_creative_ord_'.$i, HiddenType::class, array('mapped' => false));
                }
                $i++;
            }
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
        
        $data = $event->getData();
        $form = $event->getForm();
        $finance_url = $data['finance_url'];
        $hasRecommendedSlot = $form->get('has_recommended_slot')->getData();
        $hasRecommendedSlotSearchlist = $form->get('has_recommended_slot_searchlist')->getData();
        
        if ($parentId) {
            $parent = $this->em->getRepository('FaEntityBundle:Category')->find($parentId);

            if (!$parent) {
                throw new NotFoundHttpException('Unable to find Category entity.');
            }
            $form->add('parent', HiddenType::class);
            $data['parent'] = $parent;
        }
        if ($finance_url!='') {
            // In URL if we give {} brances symfony throughing validation error to avoid that we just replaced with { %7B and }  %7D
            $finance_url  = str_replace('{', '%7B', $finance_url);
            $finance_url  = str_replace('}', '%7D', $finance_url);
            $data['finance_url'] = $finance_url;
        }
        if ($hasRecommendedSlot) {
            $recomSlotUrl = array();
            for ($i = 1; $i <=3; $i++) {
                $recomSlotUrl[$i] =  $data['recommended_slot_url_'.$i];
                if ($recomSlotUrl[$i] !='') {
                    $recomSlotUrl[$i]  = str_replace('{', '%7B', $recomSlotUrl[$i]);
                    $recomSlotUrl[$i]  = str_replace('}', '%7D', $recomSlotUrl[$i]);
                    $data['recommended_slot_url_'.$i] = $recomSlotUrl[$i];
                }
            }
        }
        if ($hasRecommendedSlotSearchlist) {
            $recomSlotSrchUrl = array();
            for ($i = 1; $i <=18; $i++) {
                $recomSlotSrchUrl[$i] = $data['recommended_slot_searchlist_url_'.$i];
                if ($recomSlotSrchUrl[$i] !='') {
                    $recomSlotSrchUrl[$i]  = str_replace('{', '%7B', $recomSlotSrchUrl[$i]);
                    $recomSlotSrchUrl[$i]  = str_replace('}', '%7D', $recomSlotSrchUrl[$i]);
                    $data['recommended_slot_searchlist_url_'.$i] = $recomSlotSrchUrl[$i];
                }
            }
        }
        $event->setData($data);
    }

    /**
     * This function is called on submit data event of form.
     *
     * @param FormEvent $event object.
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form             = $event->getForm();
        $isNimberEnabled  = $form->get('is_nimber_enabled')->getData();
        $nimberSize       = $form->get('nimber_size')->getData();
        $isFinanceEnabled = $form->get('is_finance_enabled')->getData();
        $financeTitle     = $form->get('finance_title')->getData();
        $financeUrl       = $form->get('finance_url')->getData();
        $hasRecommendedSlot = $form->get('has_recommended_slot')->getData();
        if ($form->has('is_featured_upgrade_enabled') && $form->get('is_featured_upgrade_enabled')->getData()) {
            if ($form->get('featured_upgrade_info')->getData() == '') {
                $event->getForm()->get('featured_upgrade_info')->addError(new \Symfony\Component\Form\FormError($this->translator->trans('Please enter category stats/info.', array(), 'validators')));
            }
        }
        $hasRecommendedSlotSearchlist = $form->get('has_recommended_slot_searchlist')->getData();

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

        if ($hasRecommendedSlotSearchlist) {
            $oneSelectRecommendedSlotSearchlistFlag = true;
            $recommendedSlotSearchlistError = array();
            for ($i = 1; $i <=18; $i++) {
                if ($form->get('recommended_slot_searchlist_title_'.$i)->getData() || $form->get('recommended_slot_searchlist_sub_title_'.$i)->getData() || $form->get('recommended_slot_searchlist_slot_file_'.$i)->getData() || $form->get('recommended_slot_searchlist_url_'.$i)->getData()) {
                    $oneSelectRecommendedSlotSearchlistFlag = false;
                    $recommendedSlotSearchlistError[] = $i;
                }
                if (!$oneSelectRecommendedSlotSearchlistFlag && in_array($i, $recommendedSlotSearchlistError)) {
                    if (!$form->get('recommended_slot_searchlist_title_'.$i)->getData()) {
                        $form->get('recommended_slot_searchlist_title_'.$i)->addError(new FormError('Please enter title.'));
                    }
                    if (!$form->get('recommended_slot_searchlist_sub_title_'.$i)->getData()) {
                        $form->get('recommended_slot_searchlist_sub_title_'.$i)->addError(new FormError('Please enter sub title.'));
                    }
                    if ((!$form->get('recommended_slot_searchlist_slot_file_'.$i)->getData()) && (!$form->get('recommended_slot_searchlist_slot_filename_'.$i)->getData())) {
                        $form->get('recommended_slot_searchlist_slot_file_'.$i)->addError(new FormError('Please upload slot image.'));
                    }
                    if (!$form->get('recommended_slot_searchlist_url_'.$i)->getData()) {
                        $form->get('recommended_slot_searchlist_url_'.$i)->addError(new FormError('Please enter url.'));
                    }
                }
            }
            if ($oneSelectRecommendedSlotSearchlistFlag) {
                $form->get('has_recommended_slot_searchlist')->addError(new FormError('Please enter atleast one recommended slot for search list page.'));
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
        $hasRecommendedSlotSearchlist = $form->get('has_recommended_slot_searchlist')->getData();

        if ($form->isValid()) {
            $insertRecommendedSlotFlag = true;
            $insertRecommendedSlotSearchlistFlag = true;
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
                    $this->em->getRepository('FaEntityBundle:CategoryRecommendedSlot')->removeSlotsByCategoryId($category->getId());
                    $culture = CommonManager::getCurrentCulture($this->container);
                    CommonManager::removeCachePattern($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryRecommendedSlot')->getTableName().'|getCategoryRecommendedSlotArrayByCategoryId|*');
                    CommonManager::removeCachePattern($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryRecommendedSlot')->getTableName().'|getAdDetailCategoryRecommendedSlotArrayByCategoryId|*');
                }

                $recommendedSlotSearchlistArray = $this->em->getRepository('FaEntityBundle:CategoryRecommendedSlot')->getCategoryRecommendedSlotSearchlistArrayByCategoryId($category->getId(), $this->container);
                $recommendedSlotSearchlistFromArray = array();
                for ($i = 1; $i <=18; $i++) {
                    if ($form->get('recommended_slot_searchlist_title_'.$i)->getData() && $form->get('recommended_slot_searchlist_sub_title_'.$i)->getData() && ($form->get('recommended_slot_searchlist_slot_file_'.$i)->getData() || $form->get('recommended_slot_searchlist_slot_filename_'.$i)->getData()) && $form->get('recommended_slot_searchlist_url_'.$i)->getData()) {
                        $recommendedSlotSearchlistFromArray[] = array(
                            'title' => $form->get('recommended_slot_searchlist_title_'.$i)->getData(),
                            'sub_title' => $form->get('recommended_slot_searchlist_sub_title_'.$i)->getData(),
                            'slot_filename' => $form->get('recommended_slot_searchlist_slot_filename_'.$i)->getData(),
                            'url' => $form->get('recommended_slot_searchlist_url_'.$i)->getData(),
                            'creative_group' => $form->get('recommended_slot_searchlist_creative_group_'.$i)->getData(),
                            'creative_ord' => $form->get('recommended_slot_searchlist_creative_ord_'.$i)->getData(),
                        );
                    }
                }
                

                if (md5(serialize($recommendedSlotSearchlistArray)) == md5(serialize($recommendedSlotSearchlistFromArray))) {
                    $insertRecommendedSlotSearchlistFlag = false;
                }
                if ($insertRecommendedSlotSearchlistFlag || !$hasRecommendedSlotSearchlist) {
                    $this->em->getRepository('FaEntityBundle:CategoryRecommendedSlot')->removeSlotsSearchlistByCategoryId($category->getId());
                    $culture = CommonManager::getCurrentCulture($this->container);
                    CommonManager::removeCachePattern($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryRecommendedSlot')->getTableName().'|getCategoryRecommendedSlotSearchlistArrayByCategoryId|*');
                    CommonManager::removeCachePattern($this->container, $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryRecommendedSlot')->getTableName().'|getAdDetailCategoryRecommendedSlotSearchlistArrayByCategoryId|*');
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
                        $recommendedSlot->setUrl(urldecode($form->get('recommended_slot_url_'.$i)->getData()));
                        $this->em->persist($recommendedSlot);
                    }
                }
                $this->em->flush();
            }

            if ($hasRecommendedSlotSearchlist && $insertRecommendedSlotSearchlistFlag) {
                for ($i = 1; $i <=18; $i++) {
                    if ($form->get('recommended_slot_searchlist_title_'.$i)->getData() && $form->get('recommended_slot_searchlist_sub_title_'.$i)->getData() && ($form->get('recommended_slot_searchlist_slot_file_'.$i)->getData() || $form->get('recommended_slot_searchlist_slot_filename_'.$i)->getData()) && $form->get('recommended_slot_searchlist_url_'.$i)->getData()) {
                        $recommendedSlot = new CategoryRecommendedSlot();
                        $recommendedSlot->setCategory($category);
                        $recommendedSlot->setTitle($form->get('recommended_slot_searchlist_title_'.$i)->getData());
                        $recommendedSlot->setSubTitle($form->get('recommended_slot_searchlist_sub_title_'.$i)->getData());
                        $recommendedSlot->setIsSearchlist(1);
                        $slotFile     = $form->get('recommended_slot_searchlist_slot_file_'.$i)->getData();
                        $slotFileName = null;
                        if ($slotFile !== null) {
                            $slotFileName = uniqid().'.'.$slotFile->guessExtension();
                            $recommendedSlot->setSlotFile($slotFile);
                            $recommendedSlot->getSlotFile()->move($recommendedSlot->getUploadRootDir(), $slotFileName);
                            $recommendedSlot->setSlotFilename($slotFileName);
                        } else {
                            $existslotFileName = null;
                            $existslotFileName     = $form->get('recommended_slot_searchlist_slot_filename_'.$i)->getData();
                            if ($existslotFileName !== null) {
                                $recommendedSlot->setSlotFilename($existslotFileName);
                            }
                        }

                        $recommendedSlot->setUrl(urldecode($form->get('recommended_slot_searchlist_url_'.$i)->getData()));
                        $recommendedSlot->setCreativeGroup($form->get('recommended_slot_searchlist_creative_group_'.$i)->getData());
                        $recommendedSlot->setCreativeOrd($form->get('recommended_slot_searchlist_creative_ord_'.$i)->getData());
                        $this->em->persist($recommendedSlot);
                    }
                }
                $this->em->flush();
            }
        }
    }
}
