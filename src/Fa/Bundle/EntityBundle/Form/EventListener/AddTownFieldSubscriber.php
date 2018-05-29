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
 * Add town field subscriber.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AddTownFieldSubscriber implements EventSubscriberInterface
{
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
     * Constructor.
     *
     * @param string $townFieldName     Town field name in form
     * @param string $domicileFieldName Domicile field name in form
     *
     */
    public function __construct($townFieldName = 'location_town', $domicileFieldName = 'location_domicile')
    {
        $this->townFieldName    = $townFieldName;
        $this->domcileFieldName = $domicileFieldName;
    }

    /**
     * Bind form events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA  => 'preSetData',
            FormEvents::PRE_SUBMIT    => 'preSubmit'
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
            $domicileId = null;
            $town       = null;
        } else {
            $accessor   = PropertyAccess::createPropertyAccessor();
            $domicile   = $accessor->getValue($data, $this->domcileFieldName);
            $domicileId = ($domicile) ? $domicile->getId() : null;
            $town       = $accessor->getValue($data, $this->townFieldName);
        }

        $this->addTownForm($form, $domicileId, $town);
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

        $domicileId = array_key_exists($this->domcileFieldName, $data) ? $data[$this->domcileFieldName] : null;
        $this->addTownForm($form, $domicileId);
    }

    /**
     * Add town field to form.
     *
     * @param string $form
     * @param string $domicileId
     * @param string $town
     *
     * @return object
     */
    private function addTownForm($form, $domicileId = null, $town = null)
    {
        $formOptions = array(
            'class'         => 'FaEntityBundle:Location',
            'empty_value'   => 'Select Town',
            'label'         => 'Select Town',
            'query_builder' => function (LocationRepository $repository) use ($domicileId) {
                $qb = $repository->createQueryBuilder($repository::ALIAS)
                ->where($repository::ALIAS.'.lvl = 3')
                ->andWhere($repository::ALIAS.'.parent = :location_domicile')
                ->setParameter(':location_domicile', $domicileId)
                ->orderBy($repository::ALIAS.'.name', 'asc');

                return $qb;
            },
            'required' => false
        );

        if ($town) {
            $formOptions['data'] = $town;
        }

        $form->add($this->townFieldName, EntityType::class, $formOptions);
    }
}
