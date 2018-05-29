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
use Fa\Bundle\EntityBundle\Entity\CategoryDimension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call lifecycle event of doctrine.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CategoryDimensionListener
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
     * @param object $entity CategoryDimension object.
     */
    public function preRemove(CategoryDimension $entity, LifecycleEventArgs $event)
    {
        $this->removeCategoryDimensionCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity CategoryDimension object.
     */
    public function postPersist(CategoryDimension $entity, LifecycleEventArgs $event)
    {
        $this->removeCategoryDimensionCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity CategoryDimension object.
     */
    public function postUpdate(CategoryDimension $entity, LifecycleEventArgs $event)
    {
        $this->removeCategoryDimensionCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryDimension')->getTableName();
    }

    /**
     * Remove category dimension cache.
     *
     * @param  string $entity
     *
     * @return string
     */
    private function removeCategoryDimensionCache($entity)
    {
        if ($entity->getCategory() && $entity->getCategory()->getId()) {
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getDimesionsByCategoryId|'.$entity->getCategory()->getId().'*');
        }
    }
}
