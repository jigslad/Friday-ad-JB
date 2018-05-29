<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PackageListener
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
     * @param object $entity Package object.
     */
    public function preRemove(Package $entity, LifecycleEventArgs $event)
    {
        $this->removeCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Package object.
     */
    public function postPersist(Package $entity, LifecycleEventArgs $event)
    {
        $this->removeCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Package object.
     */
    public function postUpdate(Package $entity, LifecycleEventArgs $event)
    {
        $this->removeCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaPromotionBundle:Package')->getTableName();
    }

    /**
     * Remove cache.
     *
     * @param object $entity Package object.
     */
    private function removeCache($entity)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        if ($entity->getPackageFor() == 'shop' && $entity->getShopCategory()) {
            CommonManager::removeCache($this->container, $this->getTableName().'|getPaidShopPackagesByCategory|'.$entity->getShopCategory()->getId().'_'.$culture);
        }
    }
}
