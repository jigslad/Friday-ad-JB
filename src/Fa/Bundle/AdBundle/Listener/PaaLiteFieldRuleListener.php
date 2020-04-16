<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\AdBundle\Entity\PaaLiteFieldRule;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class PaaLiteFieldRuleListener
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
     * @param object $PaaLiteFieldRule PaaLiteFieldRule object.
     * @param object $event        LifecycleEventArgs.
     */
    public function preRemove(PaaLiteFieldRule $PaaLiteFieldRule, LifecycleEventArgs $event)
    {
        $this->removePaaLiteFieldRuleCache($PaaLiteFieldRule);
    }

    /**
     * Post persist.
     *
     * @param object $PaaLiteFieldRule PaaLiteFieldRule object.
     * @param object $event        LifecycleEventArgs.
     */
    public function postPersist(PaaLiteFieldRule $PaaLiteFieldRule, LifecycleEventArgs $event)
    {
        $this->removePaaLiteFieldRuleCache($PaaLiteFieldRule);
    }

    /**
     * Post update.
     *
     * @param object $PaaLiteFieldRule PaaLiteFieldRule object.
     * @param object $event        LifecycleEventArgs.
     */
    public function postUpdate(PaaLiteFieldRule $PaaLiteFieldRule, LifecycleEventArgs $event)
    {
        $this->removePaaLiteFieldRuleCache($PaaLiteFieldRule);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaAdBundle:PaaLiteFieldRule')->getTableName();
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
     * @param string $PaaLiteFieldRule
     */
    private function removePaaLiteFieldRuleCache($PaaLiteFieldRule)
    {
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getPaaLiteFieldRulesArrayByCategoryId|*');
        CommonManager::removeCachePattern($this->container, $this->getAdTableName().'|getAdDetailFields|*');
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getRegNoFieldCategoryIds|*');
    }
}
