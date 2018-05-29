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
use Fa\Bundle\ContentBundle\Entity\SeoToolOverride;
use Fa\Bundle\ContentBundle\Repository\SeoToolOverrideRepository;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolOverrideListener
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
     * @param object $seoTool Seo tool override object.
     */
    public function preRemove(SeoToolOverride $seoTool, LifecycleEventArgs $event)
    {
        $this->removeSeoToolOverrideCache($seoTool);
    }

    /**
     * Post persist.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $seoTool Seo tool override object.
     */
    public function postPersist(SeoToolOverride $seoTool, LifecycleEventArgs $event)
    {
        $this->removeSeoToolOverrideCache($seoTool);
    }

    /**
     * Post update.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $seoTool Seo tool override object.
     */
    public function postUpdate(SeoToolOverride $seoTool, LifecycleEventArgs $event)
    {
        $this->removeSeoToolOverrideCache($seoTool);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:SeoToolOverride')->getTableName();
    }

    /**
     * Remove seo tool cache.
     *
     * @param object $seoTool Seo tool object.
     *
     * @return string
     */
    private function removeSeoToolOverrideCache($seoTool)
    {
        $pageUrl = $seoTool->getPageUrl();
        if (substr($pageUrl, (strlen($pageUrl)-1), 1) == '/') {
            $pageUrlWithoutSlash = substr($pageUrl, 0, strlen($pageUrl));
            $pageUrlWithSlash    = $pageUrl;
        } else {
            $pageUrlWithSlash    = $pageUrl.'/';
            $pageUrlWithoutSlash = $pageUrl;
        }

        $pageUrlMD5 = md5($pageUrlWithSlash);
        $cacheKey   = $this->getTableName().'|findSeoRuleByPageUrl|'.$pageUrlMD5.'*';
        //remove cache here
        CommonManager::removeCachePattern($this->container, $cacheKey);

        $cacheKey   = $this->getTableName().'|findSeoRuleByPageUrlOnly|'.$pageUrlMD5.'*';
        //remove cache here
        CommonManager::removeCachePattern($this->container, $cacheKey);
    }
}

