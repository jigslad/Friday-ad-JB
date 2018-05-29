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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityRepository;
use Fa\Bundle\EntityBundle\Entity\Location;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Add country field subscriber.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AddCountryFieldSubscriber implements EventSubscriberInterface
{
    /**
     * Country field name in form.
     *
     * @var string
     */
    private $countryFieldName;

    /**
     * Constructor.
     *
     * @param string $countryFieldName Country field name in form
     *
     */
    public function __construct($countryFieldName = 'location_country')
    {
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
            $country = null;
        } else {
            $accessor = PropertyAccess::createPropertyAccessor();
            $country  = $accessor->getValue($data, $this->countryFieldName);
            $country  = ($country) ? $country: null;
        }

        $this->addCountryForm($form, $country);
    }

    /**
     * Callbak method for PRE_SUBMIT_DATA form event.
     *
     * @param object $event Event instance.
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $this->addCountryForm($form);
    }

    /**
     * Add domicile field to form.
     *
     * @param string $form
     * @param string $country
     *
     * @return object
     */
    private function addCountryForm($form, $country = null)
    {
        $formOptions = array(
            'class'         => 'FaEntityBundle:Location',
            'label'         => 'Select Country',
            'empty_value'   => 'Select Country',
            'query_builder' => function (LocationRepository $repository) {
                $qb = $repository->createQueryBuilder($repository::ALIAS)
                ->where($repository::ALIAS.'.lvl = 1')
                ->orderBy($repository::ALIAS.'.name', 'asc');

                return $qb;
            },
            'required' => false
        );

        if ($country) {
            $formOptions['data'] = $country;
        }

        $form->add($this->countryFieldName, EntityType::class, $formOptions);
    }
}
