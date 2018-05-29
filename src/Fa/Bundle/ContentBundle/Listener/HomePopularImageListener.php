<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Entity\HomePopularImage;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class HomePopularImageListener
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
     * @param object $event          LifecycleEventArgs.
     * @param object $objHomePopularImage HomePopularImage object.
     */
    public function preRemove(HomePopularImage $objHomePopularImage, LifecycleEventArgs $event)
    {
        $this->removeHomePopularImageCache($objHomePopularImage);
    }

    /**
     * Post persist.
     *
     * @param object $event          LifecycleEventArgs.
     * @param object $objHomePopularImage HomePopularImage object.
     */
    public function postPersist(HomePopularImage $objHomePopularImage, LifecycleEventArgs $event)
    {
        $this->removeHomePopularImageCache($objHomePopularImage);
    }

    /**
     * Post update.
     *
     * @param object $event          LifecycleEventArgs.
     * @param object $objHomePopularImage HomePopularImage object.
     */
    public function postUpdate(HomePopularImage $objHomePopularImage, LifecycleEventArgs $event)
    {
        $this->removeHomePopularImageCache($objHomePopularImage);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:HomePopularImage')->getTableName();
    }

    /**
     * Remove home popular image cache.
     *
     * @param object $objHomePopularImage HomePopularImage object.
     *
     * @return string
     */
    private function removeHomePopularImageCache($objHomePopularImage)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        CommonManager::removeCache($this->container, $this->getTableName().'|getHomePopularImageArray|'.$culture);
    }
}
