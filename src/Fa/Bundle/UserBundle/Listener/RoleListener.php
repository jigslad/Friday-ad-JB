<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This user listener allows various business rule to perform after or before role save
 * such as solr update, remove etc...
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class RoleListener
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
     * @param ContainerInterface $container object.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * This method is used to get table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:Role')->getTableName();
    }

    /**
     * Pre remove.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Role object.
     */
    public function preRemove(Role $entity, LifecycleEventArgs $event)
    {
        $this->removeRoleCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Role object.
     */
    public function postPersist(Role $entity, LifecycleEventArgs $event)
    {
        $this->removeRoleCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Role object.
     */
    public function postUpdate(Role $entity, LifecycleEventArgs $event)
    {
        $this->removeRoleCache($entity);
    }

    /**
     * Returns table name.
     *
     * @param removeRoleCache $entity
     *
     * @return string
     */
    private function removeRoleCache($entity)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        CommonManager::removeCache($this->container, $this->getTableName().'|getRoleArrayByType|'.$entity->getType().'_'.$culture);
    }
}
