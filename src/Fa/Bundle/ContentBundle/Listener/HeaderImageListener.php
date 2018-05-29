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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Entity\HeaderImage;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class HeaderImageListener
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
     * @param object $objHeaderImage HeaderImage object.
     */
    public function preRemove(HeaderImage $objHeaderImage, LifecycleEventArgs $event)
    {
        $this->removeHeaderImageCache($objHeaderImage);
    }

    /**
     * Post persist.
     *
     * @param object $event          LifecycleEventArgs.
     * @param object $objHeaderImage HeaderImage object.
     */
    public function postPersist(HeaderImage $objHeaderImage, LifecycleEventArgs $event)
    {
        $this->removeHeaderImageCache($objHeaderImage);
    }

    /**
     * Post update.
     *
     * @param object $event          LifecycleEventArgs.
     * @param object $objHeaderImage HeaderImage object.
     */
    public function postUpdate(HeaderImage $objHeaderImage, LifecycleEventArgs $event)
    {
        $this->removeHeaderImageCache($objHeaderImage);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:HeaderImage')->getTableName();
    }

    /**
     * Remove header image cache.
     *
     * @param object $objHeaderImage HeaderImage object.
     *
     * @return string
     */
    private function removeHeaderImageCache($objHeaderImage)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        CommonManager::removeCache($this->container, $this->getTableName().'|getHeaderImageArray|'.$culture);
    }
}
