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
 * Add domicile choice field subscriber.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AddDomicileChoiceFieldSubscriber implements EventSubscriberInterface
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
     * Domicile field name in form.
     *
     * @var string
     */
    private $domicileFieldName;

    /**
     * Country field name in form.
     *
     * @var string
     */
    private $countryFieldName;

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
     * @param string $domicileFieldName Domicile field name in form.
     * @param string $countryFieldName  Country field name in form.
     * @param string $fieldOptions     Field options.
     */
    public function __construct(ContainerInterface $container, $save = false, $domicileFieldName = 'location_domicile', $countryFieldName = 'location_country', $fieldOptions = array())
    {
        $this->container         = $container;
        $this->em                = $this->container->get('doctrine')->getManager();
        $this->domicileFieldName = $domicileFieldName;
        $this->countryFieldName  = $countryFieldName;
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
            $country  = null;
        } else {
            $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $this->domicileFieldName)));
            if (method_exists($data, $methodName)) {
                $domicile = call_user_func(array($data, $methodName));
            } else {
                $domicile = null;
            }

            $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $this->countryFieldName)));
            if (method_exists($data, $methodName)) {
                $country = call_user_func(array($data, $methodName));
            } else {
                $country = null;
            }
        }

        $countryId  = ($country) ? $country->getId() : LocationRepository::COUNTY_ID;
        $domicileId = ($domicile) ? $domicile->getId() : null;

        $this->addDomicileForm($form, $countryId, $domicileId);
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

        $countryId = array_key_exists($this->countryFieldName, $data) ? $data[$this->countryFieldName] : LocationRepository::COUNTY_ID;
        $this->addDomicileForm($form, $countryId);
    }

    /**
     * Callbak method for POST_SUBMIT form event.
     *
     * @param object $event Event instance.
     */
    public function postSubmit(FormEvent $event)
    {
        if ($this->save) {
            $form   = $event->getForm();
            $data   = $event->getForm()->getData();

            if ($form->isValid()) {
                $locationDomicile   = null;
                $locationDomicileId = $event->getForm()->get($this->domicileFieldName)->getData();
                if ($locationDomicileId) {
                    $locationDomicile = $this->em->getRepository('FaEntityBundle:Location')->find($locationDomicileId);
                }
                $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $this->domicileFieldName)));
                call_user_func(array($data, $methodName), $locationDomicile);
            }
        }
    }

    /**
     * Add domicile field to form.
     *
     * @param string $form
     * @param string $countryId
     * @param string $domicileId
     */
    private function addDomicileForm($form, $countryId, $domicileId = null)
    {
        $fieldOptions = array(
                            'choices'     => array_flip($this->em->getRepository('FaEntityBundle:Location')->getChildrenKeyValueArrayByParentId($countryId)),
                            'required'    => false,
                            'placeholder' => 'Select Domicile',
                            'label'       => 'Select Domicile',
                            'mapped'      => false,
                            'data'        => $domicileId
                        );

        $fieldOptions = array_merge($fieldOptions, $this->fieldOptions);
        $form->add($this->domicileFieldName, ChoiceType::class, $fieldOptions);
    }
}
