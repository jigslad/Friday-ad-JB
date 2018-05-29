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
use Fa\Bundle\EntityBundle\Entity\FaEntityLog;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FaEntityLogListener
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
     * @param object $entity FaEntityLog object.
     */
    public function preRemove(FaEntityLog $entity, LifecycleEventArgs $event)
    {

    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity FaEntityLog object.
     */
    public function postPersist(FaEntityLog $entity, LifecycleEventArgs $event)
    {

    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity FaEntityLog object.
     */
    public function postUpdate(FaEntityLog $entity, LifecycleEventArgs $event)
    {

    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:FaEntityLog')->getTableName();
    }
}
