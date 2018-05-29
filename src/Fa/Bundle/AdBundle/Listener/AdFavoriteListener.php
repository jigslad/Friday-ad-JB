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
use Fa\Bundle\AdBundle\Entity\AdFavorite;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdFavoriteListener
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
     * Pre remove callback.
     *
     * @param object $adFavorite AdFavorite object.
     * @param object $event      Lifecycle event args.
     */
    public function preRemove(AdFavorite $adFavorite, LifecycleEventArgs $event)
    {
        $this->removeAdFavoriteCache($adFavorite);
    }

    /**
     * Post persist callback.
     *
     * @param object $adFavorite AdFavorite object.
     * @param object $event      Lifecycle event args.
     */
    public function postPersist(AdFavorite $adFavorite, LifecycleEventArgs $event)
    {
        $this->removeAdFavoriteCache($adFavorite);
    }

    /**
     * Post update callback.
     *
     * @param object $adFavorite AdFavorite object.
     * @param object $event      Lifecycle event args.
     */
    public function postUpdate(AdFavorite $adFavorite, LifecycleEventArgs $event)
    {
        $this->removeAdFavoriteCache($adFavorite);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaAdBundle:AdFavorite')->getTableName();
    }

    /**
     * Remove cache.
     *
     * @param object $adFavorite AdFavorite object.
     */
    private function removeAdFavoriteCache($adFavorite)
    {
        $user = $adFavorite->getUser();
        $culture = CommonManager::getCurrentCulture($this->container);

        //remove user ad favorite cache
        if ($user) {
            CommonManager::removeCache($this->container, $this->getTableName().'|getFavoriteAdByUserId|'.$user->getId().'_'.$culture);
        } else if ($adFavorite->getSessionId()) {
            //remove session ad favorite cache
            CommonManager::removeCache($this->container, $this->getTableName().'|getFavoriteAdByUserId|'.$adFavorite->getSessionId().'_'.$culture);
        }
    }
}
