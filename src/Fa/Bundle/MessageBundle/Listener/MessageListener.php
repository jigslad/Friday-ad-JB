<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\MessageBundle\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class MessageListener
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
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Message object.
     */
    public function preRemove(Message $entity, LifecycleEventArgs $event)
    {
        $this->removeMessageCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Message object.
     *
     * @return void
     */
    public function postPersist(Message $entity, LifecycleEventArgs $event)
    {
        $this->removeMessageCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Message object.
     *
     * @return void
     */
    public function postUpdate(Message $entity, LifecycleEventArgs $event)
    {
        $this->removeMessageCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaMessageBundle:Message')->getTableName();
    }

    /**
     * Remove cache.
     *
     * @param $entity
     *
     * @return string
     */
    private function removeMessageCache($entity)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        if ($entity->getReceiver()) {
            CommonManager::removeCache($this->container, $this->getTableName().'|getMessageCount|'.$entity->getReceiver()->getId().'_all_'.$culture);
            CommonManager::removeCache($this->container, $this->getTableName().'|getMessageCount|'.$entity->getReceiver()->getId().'_receiver_'.$culture);
            CommonManager::removeCache($this->container, $this->getTableName().'|getMessageCount|'.$entity->getReceiver()->getId().'_sender_'.$culture);
        }
        if ($entity->getSender()) {
            CommonManager::removeCache($this->container, $this->getTableName().'|getMessageCount|'.$entity->getSender()->getId().'_all_'.$culture);
            CommonManager::removeCache($this->container, $this->getTableName().'|getMessageCount|'.$entity->getSender()->getId().'_receiver_'.$culture);
            CommonManager::removeCache($this->container, $this->getTableName().'|getMessageCount|'.$entity->getSender()->getId().'_sender_'.$culture);
        }
    }
}
