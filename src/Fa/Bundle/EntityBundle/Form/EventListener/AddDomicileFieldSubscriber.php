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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\ORM\EntityRepository;
use Fa\Bundle\EntityBundle\Entity\Location;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Add domicile field subscriber.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AddDomicileFieldSubscriber implements EventSubscriberInterface
{
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
     * Construct.
     *
     * @param string $domicileFieldName Domicile field name in form.
     * @param string $countryFieldName  Country field name in form.
     */
    public function __construct($domicileFieldName = 'location_domicile', $countryFieldName = 'location_country')
    {
        $this->domcileFieldName = $domicileFieldName;
        $this->countryFieldName = $countryFieldName;
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
            FormEvents::PRE_SUBMIT   => 'preSubmit'
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
            $accessor = PropertyAccess::createPropertyAccessor();
            $domicile = $accessor->getValue($data, $this->domcileFieldName);
            $country  = $accessor->getValue($data, $this->countryFieldName);
        }

        $countryId = ($country) ? $country->getId() : LocationRepository::COUNTY_ID;

        $this->addDomicileForm($form, $countryId, $domicile);
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

        $countryId = array_key_exists($this->countryFieldName, $data) ? $data[$this->countryFieldName] : LocationRepository::COUNTY_ID;

        $this->addDomicileForm($form, $countryId);
    }

    /**
     * Add domicile field to form.
     *
     * @param string $form
     * @param string $countryId
     * @param string $domicile
     *
     * @return object
     */
    private function addDomicileForm($form, $countryId, $domicile = null)
    {
        $formOptions = array(
            'class'         => 'FaEntityBundle:Location',
            'placeholder'   => 'Select Domicile',
            'label'         => 'Select Domicile',
            'query_builder' => function (LocationRepository $repository) use ($countryId) {
                $qb = $repository->createQueryBuilder($repository::ALIAS)
                ->where($repository::ALIAS.'.lvl = 2')
                ->where($repository::ALIAS.'.parent = :location_country')
                ->setParameter('location_country', $countryId)
                ->orderBy($repository::ALIAS.'.name', 'asc');

                return $qb;
            },
            'required' => false
        );

        if ($domicile) {
            $formOptions['data'] = $domicile;
        }

        $form->add($this->domcileFieldName, EntityType::class, $formOptions);
    }
}
