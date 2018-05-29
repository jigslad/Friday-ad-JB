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
use Fa\Bundle\EntityBundle\Entity\Location;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LocationListener
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
     * @param object $entity Location object.
     *
     * @return void
     */
    public function preRemove(Location $entity, LifecycleEventArgs $event)
    {
        $this->removeLocationCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Location object.
     *
     * @return void
     */
    public function postPersist(Location $entity, LifecycleEventArgs $event)
    {
        $this->removeLocationCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs
     * @param object $entity Location object
     *
     * @return void
     */
    public function postUpdate(Location $entity, LifecycleEventArgs $event)
    {
        $this->removeLocationCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:Location')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @param removeLocationCache $entity
     *
     * @return string
     */
    private function removeLocationCache($entity)
    {
        //remove getEntityNameById cache for entity cache manager
        CommonManager::removeCachePattern($this->container, 'EntityCacheManager|getEntityNameById|FaEntityBundle:Location_'.$entity->getId().'*');

        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getLocationNameWithParentNameById|'.$entity->getId().'*');

        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getTownInfoArrayById|'.$entity->getId().'*');
    }
}
