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
use Fa\Bundle\EntityBundle\Entity\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class EntityListener
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
     * @param object $entity Entity object
     * @param object $event  LifecycleEventArgs
     */
    public function preRemove(Entity $entity, LifecycleEventArgs $event)
    {
        $this->removeEntityCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $entity Entity object
     * @param object $event  LifecycleEventArgs
     */
    public function postPersist(Entity $entity, LifecycleEventArgs $event)
    {
        $this->removeEntityCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $entity Entity object
     * @param object $event  LifecycleEventArgs
     */
    public function postUpdate(Entity $entity, LifecycleEventArgs $event)
    {
        $this->removeEntityCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:Entity')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @param removeEntityCache $entity
     *
     * @return string
     */
    private function removeEntityCache($entity)
    {
        if ($entity->getCategoryDimension()) {
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getEntityArrayByType|'.$entity->getCategoryDimension()->getId().'*');
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getEntitySlugArrayByType|'.$entity->getCategoryDimension()->getId().'*');
        }

        //remove getEntityNameById cache for entity cache manager
        CommonManager::removeCachePattern($this->container, 'EntityCacheManager|getEntityNameById|FaEntityBundle:Entity_'.$entity->getId().'*');
        CommonManager::removeCachePattern($this->container, 'EntityCacheManager|getEntitySlugById|FaEntityBundle:Entity_'.$entity->getId().'*');
        CommonManager::removeCachePattern($this->container, 'EntityCacheManager|getSeoValueById|FaEntityBundle:Entity_'.$entity->getId().'*');
        CommonManager::removeCachePattern($this->container, 'EntityCacheManager|getEntityIdsByFieldIdAndName|*');
    }
}
