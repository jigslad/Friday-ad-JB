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
use Fa\Bundle\UserBundle\Entity\UserSiteViewCounter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This user listener allows various business rule to perform after or before user save
 * such as solr update, remove etc...
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserSiteViewCounterListener
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
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:UserSiteViewCounter')->getTableName();
    }

    /**
     * Pre remove.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserSiteViewCounter object.
     */
    public function preRemove(UserSiteViewCounter $entity, LifecycleEventArgs $event)
    {
        $this->removeUserSiteViewCounterCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserSiteViewCounter object.
     */
    public function postPersist(UserSiteViewCounter $entity, LifecycleEventArgs $event)
    {
        $this->removeUserSiteViewCounterCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserSiteViewCounter object.
     */
    public function postUpdate(UserSiteViewCounter $entity, LifecycleEventArgs $event)
    {
        $this->removeUserSiteViewCounterCache($entity);
    }

    /**
     * Returns table name.
     *
     * @param removeUserSiteViewCounterCache $entity
     *
     * @return string
     */
    private function removeUserSiteViewCounterCache($entity)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        CommonManager::removeCache($this->container, $this->getTableName().'|getUserSiteViewCounter|'.$entity->getUser()->getId().'_'.$culture);
    }
}
