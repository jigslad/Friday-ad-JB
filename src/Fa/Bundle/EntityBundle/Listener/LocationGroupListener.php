<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\EntityBundle\Entity\LocationGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LocationGroupListener
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
     * @param object $event  LifecycleEventArgs.
     * @param object $entity LocationGroup object.
     *
     * @return void
     */
    public function preRemove(LocationGroup $entity, LifecycleEventArgs $event)
    {
        $this->removeLocationGroupCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity LocationGroup object.
     *
     * @return void
     */
    public function postPersist(LocationGroup $entity, LifecycleEventArgs $event)
    {
        $this->removeLocationGroupCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs
     * @param object $entity LocationGroup object
     *
     * @return void
     */
    public function postUpdate(LocationGroup $entity, LifecycleEventArgs $event)
    {
        $this->removeLocationGroupCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:LocationGroup')->getTableName();
    }

    /**
     * Returns print edition table name.
     *
     * @return string
     */
    private function getPrintEditionTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaAdBundle:PrintEdition')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @param removeLocationGroupCache $entity
     *
     * @return string
     */
    private function removeLocationGroupCache($entity)
    {
        CommonManager::removeCachePattern($this->container, $this->getPrintEditionTableName().'|getPrintEditionColumnByTownId|*');
    }
}
