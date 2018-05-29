<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\AdBundle\Entity\PaaFieldRule;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PaaFieldRuleListener
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
     * @param object $paaFieldRule PaaFieldRule object.
     * @param object $event        LifecycleEventArgs.
     */
    public function preRemove(PaaFieldRule $paaFieldRule, LifecycleEventArgs $event)
    {
        $this->removePaaFieldRuleCache($paaFieldRule);
    }

    /**
     * Post persist.
     *
     * @param object $paaFieldRule PaaFieldRule object.
     * @param object $event        LifecycleEventArgs.
     */
    public function postPersist(PaaFieldRule $paaFieldRule, LifecycleEventArgs $event)
    {
        $this->removePaaFieldRuleCache($paaFieldRule);
    }

    /**
     * Post update.
     *
     * @param object $paaFieldRule PaaFieldRule object.
     * @param object $event        LifecycleEventArgs.
     */
    public function postUpdate(PaaFieldRule $paaFieldRule, LifecycleEventArgs $event)
    {
        $this->removePaaFieldRuleCache($paaFieldRule);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaAdBundle:PaaFieldRule')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getAdTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaAdBundle:Ad')->getTableName();
    }

    /**
     * Remove cache.
     *
     * @param string $paaFieldRule
     */
    private function removePaaFieldRuleCache($paaFieldRule)
    {
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getPaaFieldRulesArrayByCategoryId|*');
        CommonManager::removeCachePattern($this->container, $this->getAdTableName().'|getAdDetailFields|*');
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getRegNoFieldCategoryIds|*');
    }
}
