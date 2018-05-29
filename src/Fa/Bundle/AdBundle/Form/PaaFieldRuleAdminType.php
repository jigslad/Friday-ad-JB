<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;

use Fa\Bundle\AdBundle\Form\PaaFieldAdminType;
use Fa\Bundle\CoreBundle\Form\EventListener\AddAutoSuggestFieldSubscriber;
use Fa\Bundle\AdBundle\Entity\PaaFieldRule;
use Fa\Bundle\AdBundle\Repository\PaaFieldRuleRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * PaaFieldRuleAdminType form.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 */

class PaaFieldRuleAdminType extends AbstractType
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
     * Constructor.
     *
     * @param object       $container    Container instance
     * @param RequestStack $requestStack RequestStack instance
     *
     */
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->request   = $requestStack->getCurrentRequest();
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$builder->getForm()->getData()->getId()) {
            $builder
            ->addEventSubscriber(
                new AddAutoSuggestFieldSubscriber(
                    $this->container,
                    'category_id',
                    'category_id_json',
                    'FaEntityBundle:Category',
                    $this->request->get('category_id'),
                    array(
                        /** @Ignore */
                        'label' => false,
                        'attr'  => array('field-help' => 'Select or change category for load category-wise PAA fields.')
                    )
                )
            )
            ->add('saveAndNew', SubmitType::class);
        }

        $builder->add('save', SubmitType::class);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'preSubmit'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $paaFieldRule = $event->getData();
        $form         = $event->getForm();

        // New form
        if (!$paaFieldRule->getId()) {
            $categoryId = $this->request->get('category_id', null);
        } else {
            $categoryId = $paaFieldRule->getCategory()->getId();
        }

        $this->addCategroyPaaFieldsForm($form, $categoryId, $paaFieldRule);
    }

    /**
     * Callbak method for PRE_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!$form->getData()->getId()) {
            $categoryId = array_key_exists('category_id', $data) ? $data['category_id'] : null;
        } else {
            $categoryId = $form->getData()->getCategory()->getId();
        }

        $this->addCategroyPaaFieldsForm($form, $categoryId, $form->getData());
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        $form         = $event->getForm();
        $paaFieldRule = $event->getData();

        if ($form->isValid()) {
            // New form
            if (!$paaFieldRule->getId()) {
                $categoryId = $form->get('category_id')->getData();
                if ($categoryId) {
                    $category = $this->em->getRepository('FaEntityBundle:Category')->find($categoryId);

                    // Check if rule is already added for this category
                    if ($this->checkRuleExist($categoryId)) {
                        $form->get('category_id')->addError(new FormError('PAA fields rules are already added for category : '.$category->getName()));
                    }

                    // Check if display same order is used many times for different fields
                    $this->validateDisplayOrder($form, $categoryId, $paaFieldRule);

                    if ($form->isValid()) {
                        $this->savePaaFieldRules($form, $category);
                    }
                }
            } else {
                // Check if display same order is used many times for different fields
                $categoryId = $form->getData()->getCategory()->getId();
                $this->validateDisplayOrder($form, $categoryId, $paaFieldRule);
                $this->saveEditPaaFieldRules($form, $categoryId);
            }
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
                'data_class' => 'Fa\Bundle\AdBundle\Entity\PaaFieldRule'
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
        return 'fa_ad_paa_field_rule_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_ad_paa_field_rule_admin';
    }

    /**
     * Save paa field rule in create mode.
     *
     * @param object  $form     Form instance.
     * @param object  $category Select category instance.
     */
    private function savePaaFieldRules($form, $category)
    {
        $paaFields = $this->em->getRepository('FaAdBundle:PaaField')->getAllPaaFields($category->getId());
        foreach ($paaFields as $paaField) {
            $field = $paaField->getField();
            if ($form->has($field)) {
                $fieldRule    = $form->get($field)->getData();
                $paaFieldRule = new PaaFieldRule();
                $paaFieldRule->setLabel($fieldRule['label']);
                $paaFieldRule->setPlaceholderText($fieldRule['placeholder_text']);
                $paaFieldRule->setStatus($fieldRule['status']);
                $paaFieldRule->setIsRequired($fieldRule['is_required']);
                $paaFieldRule->setIsRecommended($fieldRule['is_recommended']);
                $paaFieldRule->setHelpText($fieldRule['help_text']);
                $paaFieldRule->setErrorText($fieldRule['error_text']);
                $paaFieldRule->setOrd($fieldRule['ord']);
                $paaFieldRule->setDefaultValue($fieldRule['default_value']);
                $paaFieldRule->setMinValue($fieldRule['min_value']);
                $paaFieldRule->setMaxValue($fieldRule['max_value']);
                $paaFieldRule->setStep($fieldRule['step']);
                $paaFieldRule->setCategory($category);
                $paaFieldRule->setPaaField($paaField);
                if ($field == 'photo_error') {
                    $paaFieldRule->setMinMaxType(PaaFieldRuleRepository::MIN_MAX_TYPE_RANGE);
                }
                $this->em->persist($paaFieldRule);
                $this->em->flush();
            }
        }
    }

    /**
     * Save paa field rule in edit mode.
     *
     * @param object  $form       Form instance.
     * @param integer $categoryId Selected category id.
     */
    private function saveEditPaaFieldRules($form, $categoryId)
    {
        $paaFieldRules = $this->em->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesByCategoryId($categoryId);
        foreach ($paaFieldRules as $paaFieldRule) {
            $paaField = $paaFieldRule->getPaaField();
            $field    = $paaField->getField();
            if ($form->has($field)) {
                $fieldRule = $form->get($field)->getData();
                $paaFieldRule->setLabel($fieldRule['label']);
                $paaFieldRule->setPlaceholderText($fieldRule['placeholder_text']);
                $paaFieldRule->setStatus($fieldRule['status']);
                $paaFieldRule->setIsRequired($fieldRule['is_required']);
                $paaFieldRule->setIsRecommended($fieldRule['is_recommended']);
                $paaFieldRule->setHelpText($fieldRule['help_text']);
                $paaFieldRule->setErrorText($fieldRule['error_text']);
                $paaFieldRule->setOrd($fieldRule['ord']);
                $paaFieldRule->setDefaultValue($fieldRule['default_value']);
                $paaFieldRule->setMinValue($fieldRule['min_value']);
                $paaFieldRule->setMaxValue($fieldRule['max_value']);
                $paaFieldRule->setStep($fieldRule['step']);
                $this->em->persist($paaFieldRule);
                $this->em->flush();
            }
        }
    }

    /**
     * Check for duplicate categorywise rule.
     *
     * @param integer $categoryId Selected category id.
     *
     * @return boolean
     */
    private function checkRuleExist($categoryId)
    {
        $paaFieldRule = $this->em->getRepository('FaAdBundle:PaaFieldRule')->findOneBy(array('category' => $categoryId));
        if ($paaFieldRule) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  Add category wise paa fields.
     *
     * @param object  $form         Form instance.
     * @param integer $categoryId   Selected category id.
     * @param object  $paaFieldRule Paa field rule instance.
     */
    private function addCategroyPaaFieldsForm($form, $categoryId = null, $paaFieldRule = null)
    {
        if ($categoryId) {
            //Edit
            if ($paaFieldRule && $paaFieldRule->getId()) {
                $fieldRules = $this->em->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesByCategoryId($categoryId);
                foreach ($fieldRules as $fieldRule) {
                    $fieldId = $fieldRule->getPaaField()->getId();
                    $field   = $fieldRule->getPaaField()->getField();
                    $label   = $fieldRule->getPaaField()->getLabel();

                    $form->add($field, new PaaFieldAdminType($this->container, $fieldRule), array('mapped' => false, 'label' => /** @Ignore */$label, 'required' => false));
                }
            } else {
                $ord           = 1;
                $paaFieldsData = $this->em->getRepository('FaAdBundle:PaaField')->getPaaFieldsByCategoryAncestor($categoryId, true);
                foreach ($paaFieldsData as $fieldId => $paaFieldData) {
                    if ($paaFieldData['is_rule']) {
                        $fieldRule = $paaFieldData['data'];
                        $field = $fieldRule->getPaaField()->getField();
                        $label = $fieldRule->getPaaField()->getLabel();
                        $form->add($field, new PaaFieldAdminType($this->container, $fieldRule), array('mapped' => false, 'label' => /** @Ignore */$label, 'required' => false));
                    } else {
                        $paaField = $paaFieldData['data'];
                        $form->add($paaField->getField(), new PaaFieldAdminType($this->container, null, $paaField, $ord), array('mapped' => false, 'label' => /** @Ignore */$paaField->getLabel(), 'required' => false));
                    }

                    $ord++;
                }

                // Overwrite default values based on parent categories PAA field rules
                /*
                $lastOrd         = '';
                $parentPaaFields = array();
                $fieldRules      = $this->em->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesByCategoryAncestor($categoryId);
                foreach ($fieldRules as $fieldRule) {
                    $fieldId = $fieldRule->getPaaField()->getId();
                    $field   = $fieldRule->getPaaField()->getField();
                    $label   = $fieldRule->getPaaField()->getLabel();

                    $form->add($field, new PaaFieldAdminType($this->container, $fieldRule), array('mapped' => false, 'label' => $label, 'required' => false));
                    $lastOrd = $fieldRule->getOrd();
                    $parentPaaFields[] = $fieldId;
                }

                $ord       = $lastOrd;
                $paaFields = $this->em->getRepository('FaAdBundle:PaaField')->getAllPaaFields($categoryId);
                foreach ($paaFields as $field => $paaField) {
                    if (!in_array($paaField->getId(), $parentPaaFields)) {
                        $form->add($paaField->getField(), new PaaFieldAdminType($this->container, null, $paaField, $ord++), array('mapped' => false, 'label' => $paaField->getLabel(), 'required' => false));
                    }
                }*/
            }
        }
    }

    /**
     * Check for duplicate categorywise rule.
     *
     * @param object  $form         Form instance.
     * @param integer $categoryId   Selected category id.
     * @param object  $paaFieldRule Paa field rule instance.
     */
    private function validateDisplayOrder($form, $categoryId, $paaFieldRule = null)
    {
        //Edit
        if ($paaFieldRule && $paaFieldRule->getId()) {
            $displayOrder  = array();
            $paaFieldRules = $this->em->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesByCategoryId($categoryId);
            foreach ($paaFieldRules as $paaFieldRule) {
                $paaField = $paaFieldRule->getPaaField();
                $field    = $paaField->getField();
                if ($form->has($field)) {
                    $fieldRule = $form->get($field)->getData();
                    if (in_array($fieldRule['ord'], $displayOrder)) {
                        $form->get($field)->addError(new FormError("' ".$fieldRule['ord']." ' display order is given to more than one fields."));
                    } else {
                        $displayOrder[] = $fieldRule['ord'];
                    }
                }
            }
        } else {
            $displayOrder  = array();
            $paaFields     = $this->em->getRepository('FaAdBundle:PaaField')->getAllPaaFields($categoryId);
            foreach ($paaFields as $paaField) {
                $field = $paaField->getField();
                if ($form->has($field)) {
                    $fieldRule = $form->get($field)->getData();
                    if (in_array($fieldRule['ord'], $displayOrder)) {
                        $form->get($field)->addError(new FormError("' ".$fieldRule['ord']." ' display order is given to more than one fields."));
                    } else {
                        $displayOrder[] = $fieldRule['ord'];
                    }
                }
            }
        }
    }
}
