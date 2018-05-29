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
use Fa\Bundle\ContentBundle\Entity\SeoToolPopularSearch;
use Fa\Bundle\ContentBundle\Repository\SeoToolPopularSearchRepository;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolPopularSearchListener
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
     * @param object $seoToolPopularSearch Seo tool object.
     */
    public function preRemove(SeoToolPopularSearch $seoToolPopularSearch, LifecycleEventArgs $event)
    {
        $this->removeSeoToolPopularSearchCache($seoToolPopularSearch);
    }

    /**
     * Post persist.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $seoToolPopularSearch Seo tool object.
     */
    public function postPersist(SeoToolPopularSearch $seoToolPopularSearch, LifecycleEventArgs $event)
    {
        $this->removeSeoToolPopularSearchCache($seoToolPopularSearch);
    }

    /**
     * Post update.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $seoToolPopularSearch Seo tool object.
     */
    public function postUpdate(SeoToolPopularSearch $seoToolPopularSearch, LifecycleEventArgs $event)
    {
        $this->removeSeoToolPopularSearchCache($seoToolPopularSearch);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:SeoToolPopularSearch')->getTableName();
    }

    /**
     * Remove seo tool cache.
     *
     * @param object $seoToolPopularSearch Seo tool object.
     *
     * @return string
     */
    private function removeSeoToolPopularSearchCache($seoToolPopularSearch)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        if ($seoToolPopularSearch->getSeoTool()) {
            CommonManager::removeCache($this->container, $this->getTableName().'|getPopularSearchArrayBySeoToolId|'.$seoToolPopularSearch->getSeoTool()->getId().'_'.$culture);
        }
    }
}
