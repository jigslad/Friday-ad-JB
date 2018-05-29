<?php

namespace Fa\Bundle\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Validator\Constraints\NotBlank;
// use Symfony\Component\Validator\Constraints\True;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Fa\Bundle\CoreBundle\Repository\ConfigRuleRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Entity\ConfigRule;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Form\EventListener\AddDatePickerFieldSubscriber;
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;



class ConfigRuleAdminType extends AbstractType
{
    /**
     * Container service class object
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager
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
        $this->translator = CommonManager::getTranslator($container);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$builder->getForm()->getData()->getId()) {
            $builder
                    ->add(
                        'config',
                        EntityType::class,
                        array(
                            'class'       => 'FaCoreBundle:Config',
                            'choice_label'    => 'name',
                            'placeholder' => 'Select Config Option',
                            'constraints' => array(new NotBlank(array('message' => $this->translator->trans('Please select config option', array(), 'validators')))),
                            'attr'        => array('field-help' => 'Select or change config option to load category / location-group fields.'),
                        )
                    )
                    ->add('value', TextType::class)
                    ->add(
                        'location_group',
                        ChoiceType::class,
                        array(
                            'choices'     => array_flip($this->em->getRepository('FaEntityBundle:LocationGroup')->getLocationGroupsKeyValueArray()),
                            'placeholder' => 'Select Location Group',
                            'label'       => 'Location Group',
                            'multiple'    => true,
                            'mapped'      => false,
                            'required'    => false,
                            'attr'        => array('field-help' => 'Use "CTRL" key for select / deselect multiple options.'),
                            )
                    )
                    ->addEventSubscriber(new AddDatePickerFieldSubscriber('period_from'))
                    ->addEventSubscriber(new AddDatePickerFieldSubscriber('period_to'))
                    ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 1))
                    ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 2))
                    ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 3))
                    ->addEventSubscriber(new AddCategoryChoiceFieldSubscriber($this->container, 4))
                    ->add('save', SubmitType::class)
                    ->add('saveAndNew', SubmitType::class);
        } else {
            $builder
                    ->addEventSubscriber(new AddDatePickerFieldSubscriber('period_from'))
                    ->addEventSubscriber(new AddDatePickerFieldSubscriber('period_to'))
                    ->add('save', SubmitType::class);
            if ($builder->getForm()->getData()->getConfig()->getId() == ConfigRepository::ADZUNA_MOTORS_FEED_USER_IDS) {
                $builder->add('value', TextareaType::class, array('attr' => array('rows' => 5)));
            } else {
                $builder->add('value', TextType::class);
            }
        }

        $builder
                ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'))
                ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    public function onPostSubmit(FormEvent $event)
    {
        $configRule = $event->getData();
        $form       = $event->getForm();

        if (!$configRule->getId() && $form->isValid()) {
            $category = null;
            $categoryId = $this->getCategoryId($form);

            if ($categoryId) {
                $category = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $categoryId));
            }

            // Add rule with location groups if selected otherwise add one blank rule
            $locationGroupIds = $form->get('location_group')->getData();
            if (!empty($locationGroupIds)) {
                foreach ($locationGroupIds as $locationGroupId) {
                    $locationGroup = $this->em->getRepository('FaEntityBundle:LocationGroup')->find($locationGroupId);
                    if ($locationGroup) {
                        $configRule = new ConfigRule();
                        $configRule->setCategory($category);
                        $configRule->setLocationGroup($locationGroup);
                        $configRule->setStatus(1);
                        $configRule->setValue($form->get('value')->getData());
                        $configRule->setConfig($form->get('config')->getData());
                        $configRule->setPeriodFrom(CommonManager::getTimeStampFromStartDate($form->get('period_from')->getData()));
                        $configRule->setPeriodTo(CommonManager::getTimeStampFromEndDate($form->get('period_to')->getData()));
                        $this->em->persist($configRule);
                        $this->em->flush();
                    }
                }
            } else {
                $configRule = new ConfigRule();
                $configRule->setCategory($category);
                $configRule->setStatus(1);
                $configRule->setValue($form->get('value')->getData());
                $configRule->setConfig($form->get('config')->getData());
                $configRule->setPeriodFrom(CommonManager::getTimeStampFromStartDate($form->get('period_from')->getData()));
                $configRule->setPeriodTo(CommonManager::getTimeStampFromEndDate($form->get('period_to')->getData()));
                $this->em->persist($configRule);
                $this->em->flush();
            }
        } else {
            $configRule->setPeriodFrom(CommonManager::getTimeStampFromStartDate($form->get('period_from')->getData()));
            $configRule->setPeriodTo(CommonManager::getTimeStampFromEndDate($form->get('period_to')->getData()));
        }
    }

    public function onSubmit(FormEvent $event)
    {
        $configRule = $event->getData();
        $form       = $event->getForm();

        if (!$configRule->getId()) {
            $config           = $form->get('config')->getData();
            $categoryId       = $this->getCategoryId($form);
            $locationGroupIds = $form->get('location_group')->getData();

            if ($config && $categoryId && !count($locationGroupIds)) {
                $category   = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $categoryId));
                if ($this->checkRuleExist($config->getId(), $categoryId)) {
                    $form->get('category_'.$category->getLvl())->addError(new FormError($this->translator->trans('Rule is already set for selected category : %categoryName%', array('%categoryName%' => $category->getName()), 'validators')));
                }
            } elseif ($config && count($locationGroupIds) && !$categoryId) {
                foreach ($locationGroupIds as $locationGroupId) {
                    $locationGroup = $this->em->getRepository('FaEntityBundle:LocationGroup')->find($locationGroupId);
                    if ($locationGroup) {
                        if ($this->checkRuleExist($config->getId(), null, $locationGroupId)) {
                            $form->get('location_group')->addError(new FormError($this->translator->trans('Rule is already set for selected location group : %locationGroup%', array('%locationGroup%' => $locationGroup->getName()), 'validators')));
                        }
                    }
                }
            } elseif ($config && $categoryId && count($locationGroupIds)) {
                foreach ($locationGroupIds as $locationGroupId) {
                    $category      = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $categoryId));
                    $locationGroup = $this->em->getRepository('FaEntityBundle:LocationGroup')->find($locationGroupId);
                    if ($category && $locationGroup) {
                        if ($this->checkRuleExist($config->getId(), $categoryId, $locationGroupId)) {
                            $form->get('location_group')->addError(new FormError($this->translator->trans('Rule is already set for selected category and location group : (%categoryName%, %locationGroup%)', array('%categoryName%' => $category->getName(), '%locationGroup%' => $locationGroup->getName()), 'validators')));
                        }
                    }
                }
            } elseif ($config) {
                if ($this->checkRuleExist($config->getId())) {
                    $form->get('config')->addError(new FormError($this->translator->trans('Global rule is already added for this config option.', array(), 'validators')));
                }
            }
        }

        // Period dates validation
        if ($form->get('period_from')->getData() && $form->get('period_to')->getData()) {
            $periodFrom = CommonManager::getTimeStampFromStartDate($form->get('period_from')->getData());
            $periodTo   = CommonManager::getTimeStampFromEndDate($form->get('period_to')->getData());

            if ($periodTo < $periodFrom) {
                $form->get('period_to')->addError(new FormError($this->translator->trans('Perido To date should be greater than Period From date.', array(), 'validators')));
            }
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Fa\Bundle\CoreBundle\Entity\ConfigRule'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'fa_core_config_rule_admin';
    }
    
    public function getBlockPrefix()
    {
        return 'fa_core_config_rule_admin';
    }

    private function checkRuleExist($configId, $categoryId = null, $locationGroupId = null)
    {
        $configRule = $this->em->getRepository('FaCoreBundle:ConfigRule')->findOneBy(array('config' => $configId, 'category' => $categoryId, 'location_group' => $locationGroupId));
        if ($configRule) {
            return true;
        } else {
            return false;
        }
    }

    private function getCategoryId($form)
    {
        $categoryId = null;
        $category1  = $form->get('category_1')->getData();
        $category2  = $form->get('category_2')->getData();
        $category3  = $form->get('category_3')->getData();
        $category4  = $form->get('category_4')->getData();

        if ($category4) {
            $categoryId = $category4;
        } elseif ($category3) {
            $categoryId = $category3;
        } elseif ($category2) {
            $categoryId = $category2;
        } elseif ($category1) {
            $categoryId = $category1;
        }

        return $categoryId;
    }
}
