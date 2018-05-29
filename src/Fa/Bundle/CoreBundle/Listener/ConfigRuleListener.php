<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Entity\ConfigRule;
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;

/**
 * Fa\Bundle\EntityBundle\Listener\ConfigRuleListener
 *
 * This class is used to call LifecycleEvent of doctrine
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 * @version 1.0
 */
class ConfigRuleListener
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
     * preRemove
     *
     * @param object $event  LifecycleEventArgs
     * @param object $entity ConfigRule object
     *
     * @return void
     */
    public function preRemove(ConfigRule $entity, LifecycleEventArgs $event)
    {
        $this->removeConfigRuleCache($entity);
    }

    /**
     * postPersist
     *
     * @param object $event  LifecycleEventArgs
     * @param object $entity ConfigRule object
     *
     * @return void
     */
    public function postPersist(ConfigRule $entity, LifecycleEventArgs $event)
    {
        $this->removeConfigRuleCache($entity);
    }

    /**
     * postUpdate
     *
     * @param object $event  LifecycleEventArgs
     * @param object $entity ConfigRule object
     *
     * @return void
     */
    public function postUpdate(ConfigRule $entity, LifecycleEventArgs $event)
    {
        $this->removeConfigRuleCache($entity);
    }

    /**
     * returns table name
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaCoreBundle:ConfigRule')->getTableName();
    }

    /**
     * Returns user config rule table name.
     *
     * @return string
     */
    private function getConfigRuleDimensionTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaCoreBundle:ConfigRule')->getTableName();
    }

    /**
     * returns table name
     *
     * @return string
     */
    private function removeConfigRuleCache($entity)
    {
        if ($entity->getConfig()) {
            if ($entity->getConfig()->getId() == ConfigRepository::PAYPAL_COMMISION) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getActiveHighestPaypalCommission|*');
            }
            if ($entity->getConfig()->getId() == ConfigRepository::NUMBER_OF_BUSINESSPAGE_SLOTS) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getBusinessPageSlots|*');
            }
            if ($entity->getConfig()->getId() == ConfigRepository::TOP_BUSINESSPAGE) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getTopBusiness|*');
            }

            if ($entity->getConfig()->getId() == ConfigRepository::LISTING_TOPAD_SLOTS) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getListingTopAdSlots|*');
            }

            if ($entity->getConfig()->getId() == ConfigRepository::NUMBER_OF_ORGANIC_RESULTS) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getNumberOfOrganicResult|*');
            }

            if ($entity->getConfig()->getId() == ConfigRepository::AD_EXPIRATION_DAYS) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getExpirationDays|*');
            }

            if ($entity->getConfig()->getId() == ConfigRepository::CLICKEDITVEHICLEADVERTS_PACKAGE_ID) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getClickEditVehicleAdvertsPackageId|*');
            }

            if ($entity->getConfig()->getId() == ConfigRepository::PRIVATE_USER_AD_POST_LIMIT) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getPrivateUserAdPostLimit|*');
            }

            if ($entity->getConfig()->getId() == ConfigRepository::ADZUNA_MOTORS_FEED_USER_IDS) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getAdzunaMotorsFeedUserIds|*');
            }
        }
    }
}
