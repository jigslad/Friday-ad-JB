<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DeliveryMethodOptionListener
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Pre remove.
     *
     * @param object $event                LifecycleEventArgs.
     * @param object $deliveryMethodOption DeliveryMethodOption object.
     */
    public function preRemove(DeliveryMethodOption $deliveryMethodOption, LifecycleEventArgs $event)
    {
        $this->removeCache($deliveryMethodOption);
    }

    /**
     * Post persist.
     *
     * @param object $event                LifecycleEventArgs.
     * @param object $deliveryMethodOption DeliveryMethodOption Object.
     */
    public function postPersist(DeliveryMethodOption $deliveryMethodOption, LifecycleEventArgs $event)
    {
        $this->removeCache($deliveryMethodOption);
    }

    /**
     * Post update.
     *
     * @param object $event                LifecycleEventArgs.
     * @param object $deliveryMethodOption DeliveryMethodOption Object.
     */
    public function postUpdate(DeliveryMethodOption $deliveryMethodOption, LifecycleEventArgs $event)
    {
        $this->removeCache($deliveryMethodOption);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaPaymentBundle:DeliveryMethodOption')->getTableName();
    }

    /**
     * Remove cache.
     *
     * @param object $event LifecycleEventArgs.
     * @return string
     */
    private function removeCache($deliveryMethodOption)
    {
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getDeliveryMethodOptionArray|'.'*');
    }
}
