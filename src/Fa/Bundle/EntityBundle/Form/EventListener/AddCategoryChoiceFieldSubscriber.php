<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\ORM\EntityRepository;

use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Entity\Category;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Add category choice field subscriber event subscriber.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AddCategoryChoiceFieldSubscriber implements EventSubscriberInterface
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
     * Town field name in form.
     *
     * @var string
     */
    private $categoryFieldName;

    /**
     * Category level.
     *
     * @var string
     */
    private $level;

    /**
     * Field options.
     *
     * @var array
     */
    private $fieldOptions = array();

    /**
     * Parent category id.
     *
     * @var integer
     */
    private $parentId;

    /**
     * Total level.
     *
     * @var integer
     */
    private $totalLevel;

    /**
     * Is adult category append.
     *
     * @var boolean
     */
    private $isAdultAppend;

    /**
     * Construct.
     *
     * @param ContainerInterface $container
     * @param number             $level
     * @param string             $categoryFieldName
     * @param string             $fieldOptions
     * @param string             $parentId
     * @param number             $totalLevel
     * @param boolean            $isAdultAppend
     */
    public function __construct(ContainerInterface $container, $level = 1, $categoryFieldName = 'category', $fieldOptions = array(), $parentId = null, $totalLevel = 4, $isAdultAppend = false)
    {
        $this->container         = $container;
        $this->em                = $this->container->get('doctrine')->getManager();
        $this->categoryFieldName = $categoryFieldName;
        $this->level             = $level;
        $this->parentId          = $parentId;
        $this->totalLevel        = $totalLevel;
        $this->isAdultAppend     = $isAdultAppend;

        if ($fieldOptions && count($fieldOptions)) {
            $this->fieldOptions = $fieldOptions;
        }
    }

    /**
     * Bind form events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
        );
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $form     = $event->getForm();
        $parentId = ($this->parentId) ? $this->parentId : null;

        $this->addCategroyForm($form, $parentId);
    }

    /**
     * Callbak method for PRE_SUBMIT_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $parentId = null;
        if ($this->level > 1) {
            if ($this->parentId) {
                $parentId = $this->parentId;
            } else {
                $parentFieldName = $this->categoryFieldName.'_'.($this->level - 1);
                $parentId        = array_key_exists($parentFieldName, $data) ? $data[$parentFieldName] : null;
            }
        }

        $this->addCategroyForm($form, $parentId);
    }

    /**
     * Add town field to form.
     *
     * @param string $form
     * @param string $parentId
     * @param string $categoryId
     */
    private function addCategroyForm($form, $parentId = null, $categoryId = null)
    {
        $choices = array();

        if ($parentId !== null) {
            if ($this->level < $this->totalLevel) {
                $choices = $this->em->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($parentId);

                // Append adult category at end of chidren of community and services category.
                if ($this->isAdultAppend && ($parentId == CategoryRepository::COMMUNITY_ID || $parentId == CategoryRepository::SERVICES_ID)) {
                    $choices[CategoryRepository::ADULT_ID] = $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Entity', CategoryRepository::ADULT_ID);
                }
            } else {
                $choices = $this->em->getRepository('FaEntityBundle:Category')->getNestedChildrenKeyValueArrayByParentId($parentId);
                $choices = array_map('html_entity_decode', $choices);
            }
        } elseif ($this->level == 1) {
            $choices = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray($this->level, $this->container);
        }

        $choices = $this->em->getRepository('FaEntityBundle:Category')->showDuplicateCategoriesForSubscriber($choices);

        $fieldOptions = array(
                            'choices'     => array_flip($choices),
                            'required'    => false,
                            /** @Ignore */
                            'placeholder' => 'Select Category'.' '. $this->level,
                            /** @Ignore */
                            'label'       => 'Select Category'.' '. $this->level,
                            'mapped'      => false,
                            'data'        => $categoryId,
                            'choice_translation_domain' => false,
                        );

        $fieldOptions = array_merge($fieldOptions, $this->fieldOptions);

        $form->add($this->categoryFieldName.'_'.$this->level, ChoiceType::class, $fieldOptions);
    }
}
