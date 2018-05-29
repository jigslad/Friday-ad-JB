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
use Fa\Bundle\EntityBundle\Entity\Postcode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PostcodeListener
{
    /**
     * Container service class object
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
     * @param object $entity Postcode object.
     * @param object $event  LifecycleEventArgs.
     */
    public function preRemove(Postcode $entity, LifecycleEventArgs $event)
    {
        $this->removePostcodeCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $entity Postcode object.
     * @param object $event  LifecycleEventArgs.
     */
    public function postPersist(Postcode $entity, LifecycleEventArgs $event)
    {
        $this->removePostcodeCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $entity Postcode object.
     * @param object $event  LifecycleEventArgs.
     */
    public function postUpdate(Postcode $entity, LifecycleEventArgs $event)
    {
        $this->removePostcodeCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:Postcode')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @param removePostcodeCache $entity
     *
     * @return string
     */
    private function removePostcodeCache($entity)
    {
        //remove getEntityNameById cache for entity cache manager
        CommonManager::removeCachePattern($this->container, 'EntityCacheManager|getEntityNameById|FaEntityBundle:Postcode_'.$entity->getId().'*');

        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getPostCodTextByLocation|'.$entity->getPostCodeC().'*');

        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getPostCodInfoArrayByLocation|'.$entity->getPostCodeC().'*');
    }
}
