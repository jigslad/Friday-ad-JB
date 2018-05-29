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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Entity\UserConfigRule;

/**
 * This class is used to call LifecycleEvent of doctrine
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserConfigRuleListener
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
     * @param object $entity UserConfigRule object.
     */
    public function preRemove(UserConfigRule $entity, LifecycleEventArgs $event)
    {
        $this->removeUserConfigRuleCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserConfigRule object.
     */
    public function postPersist(UserConfigRule $entity, LifecycleEventArgs $event)
    {
        $this->removeUserConfigRuleCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserConfigRule object.
     */
    public function postUpdate(UserConfigRule $entity, LifecycleEventArgs $event)
    {
        $this->removeUserConfigRuleCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:UserConfigRule')->getTableName();
    }

    /**
     * Returns user config rule table name.
     *
     * @return string
     */
    private function getUserConfigRuleDimensionTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:UserConfigRule')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @param $entity
     *
     * @return string
     */
    private function removeUserConfigRuleCache($entity)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        CommonManager::removeCache($this->container, $this->getTableName().'|getActivePaypalCommission|'.$entity->getUser()->getId().'_'.$culture);
    }
}
