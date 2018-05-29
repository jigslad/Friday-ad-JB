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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Fa\Bundle\ContentBundle\Entity\LandingPagePopularSearch;
use Fa\Bundle\ContentBundle\Repository\LandingPagePopularSearchRepository;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LandingPagePopularSearchListener
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
     * @param object $event   LifecycleEventArgs.
     * @param object $landingPagePopularSearch Landing page object.
     */
    public function preRemove(LandingPagePopularSearch $landingPagePopularSearch, LifecycleEventArgs $event)
    {
        $this->removeLandingPagePopularSearchCache($landingPagePopularSearch);
    }

    /**
     * Post persist.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $landingPagePopularSearch Landing page object.
     */
    public function postPersist(LandingPagePopularSearch $landingPagePopularSearch, LifecycleEventArgs $event)
    {
        $this->removeLandingPagePopularSearchCache($landingPagePopularSearch);
    }

    /**
     * Post update.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $landingPagePopularSearch Landing page object.
     */
    public function postUpdate(LandingPagePopularSearch $landingPagePopularSearch, LifecycleEventArgs $event)
    {
        $this->removeLandingPagePopularSearchCache($landingPagePopularSearch);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:LandingPagePopularSearch')->getTableName();
    }

    /**
     * Remove Landing page cache.
     *
     * @param object $landingPagePopularSearch Landing page object.
     *
     * @return string
     */
    private function removeLandingPagePopularSearchCache($landingPagePopularSearch)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        if ($landingPagePopularSearch->getLandingPage()) {
            CommonManager::removeCache($this->container, $this->getTableName().'|getPopularSearchArrayByLandingPageId|'.$landingPagePopularSearch->getLandingPage()->getId().'_'.$culture);
        }
    }
}
