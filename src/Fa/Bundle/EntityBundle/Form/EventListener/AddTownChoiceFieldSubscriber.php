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
use Fa\Bundle\EntityBundle\Entity\Location;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Add town choice field subscriber.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AddTownChoiceFieldSubscriber implements EventSubscriberInterface
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
    private $townFieldName;

    /**
     * Domicile field name in form.
     *
     * @var string
     */
    private $domicileFieldName;

    /**
     * Field options.
     *
     * @var array
     */
    private $fieldOptions = array();

    /**
     * Need to save field on database or not.
     *
     * @var boolean
     */
    private $save = false;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param string $save
     * @param string $townFieldName
     * @param string $domicileFieldName
     * @param unknown $fieldOptions
     */
    public function __construct(ContainerInterface $container, $save = false, $townFieldName = 'location_town', $domicileFieldName = 'location_domicile', $fieldOptions = array())
    {
        $this->container         = $container;
        $this->em                = $this->container->get('doctrine')->getManager();
        $this->townFieldName     = $townFieldName;
        $this->domicileFieldName = $domicileFieldName;
        $this->save              = $save;

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
            FormEvents::POST_SUBMIT  => 'postSubmit',
        );
    }

    /**
     * Callbak method for PRE_SET_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            $domicile = null;
            $town     = null;
        } else {
            $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $this->townFieldName)));
            if (method_exists($data, $methodName)) {
                $town = call_user_func(array($data, $methodName));
            } else {
                $town = null;
            }

            $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $this->domicileFieldName)));
            if (method_exists($data, $methodName)) {
                $domicile = call_user_func(array($data, $methodName));
            } else {
                $domicile = null;
            }
        }

        $townId     = ($town) ? $town->getId() : null;
        $domicileId = ($domicile) ? $domicile->getId() : null;

        $this->addTownForm($form, $domicileId, $townId);
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

        $domicileId = array_key_exists($this->domicileFieldName, $data) ? $data[$this->domicileFieldName] : null;
        $this->addTownForm($form, $domicileId);
    }

    /**
     * Callbak method for POST_SUBMIT form event
     *
     * @param object $event Event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        if ($this->save) {
            $form = $event->getForm();
            $data = $event->getForm()->getData();

            if ($form->isValid()) {
                $locationTown   = null;
                $locationTownId = $form->get($this->townFieldName)->getData();
                if ($locationTownId) {
                    $locationTown = $this->em->getRepository('FaEntityBundle:Location')->find($locationTownId);
                }

                $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $this->townFieldName)));
                call_user_func(array($data, $methodName), $locationTown);
            }
        }
    }

    /**
     * Add town field to form.
     *
     * @param string $form
     * @param string $domicileId
     * @param string $townId
     */
    private function addTownForm($form, $domicileId = null, $townId = null)
    {
        $fieldOptions = array(
                            'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Location')->getChildrenKeyValueArrayByParentId($domicileId)),
                            'required'    => false,
                            'placeholder' => 'Select Town',
                            'label'       => 'Select Town',
                            'mapped'      => false,
                            'data'        => $townId
                        );

        $fieldOptions = array_merge($fieldOptions, $this->fieldOptions);
        $form->add($this->townFieldName, ChoiceType::class, $fieldOptions);
    }
}
