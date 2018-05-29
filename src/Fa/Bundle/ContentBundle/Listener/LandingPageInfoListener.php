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
use Fa\Bundle\ContentBundle\Entity\LandingPageInfo;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LandingPageInfoListener
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
     * @param object $objLandingPageInfo LandingPageInfo object.
     */
    public function preRemove(LandingPageInfo $objLandingPageInfo, LifecycleEventArgs $event)
    {
        $this->removeLandingPageInfoCache($objLandingPageInfo);
    }

    /**
     * Post persist.
     *
     * @param object $event          LifecycleEventArgs.
     * @param object $objLandingPageInfo LandingPageInfo object.
     */
    public function postPersist(LandingPageInfo $objLandingPageInfo, LifecycleEventArgs $event)
    {
        $this->removeLandingPageInfoCache($objLandingPageInfo);
    }

    /**
     * Post update.
     *
     * @param object $event          LifecycleEventArgs.
     * @param object $objLandingPageInfo LandingPageInfo object.
     */
    public function postUpdate(LandingPageInfo $objLandingPageInfo, LifecycleEventArgs $event)
    {
        $this->removeLandingPageInfoCache($objLandingPageInfo);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:LandingPageInfo')->getTableName();
    }

    /**
     * Remove header image cache.
     *
     * @param object $objLandingPageInfo LandingPageInfo object.
     *
     * @return string
     */
    private function removeLandingPageInfoCache($objLandingPageInfo)
    {
        $landingPageId = $objLandingPageInfo->getLandingPage()->getId();
        CommonManager::removeCache($this->container, $this->getTableName().'|getLandingPageImagesBySecton|'.$landingPageId);
    }
}
