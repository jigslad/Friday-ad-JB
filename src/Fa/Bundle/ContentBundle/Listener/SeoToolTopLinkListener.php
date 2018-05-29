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
use Fa\Bundle\ContentBundle\Entity\SeoToolTopLink;
use Fa\Bundle\ContentBundle\Repository\SeoToolTopLinkRepository;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolTopLinkListener
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
     * @param object $seoToolTopLink Seo tool object.
     */
    public function preRemove(SeoToolTopLink $seoToolTopLink, LifecycleEventArgs $event)
    {
        $this->removeSeoToolTopLinkCache($seoToolTopLink);
    }

    /**
     * Post persist.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $seoToolTopLink Seo tool object.
     */
    public function postPersist(SeoToolTopLink $seoToolTopLink, LifecycleEventArgs $event)
    {
        $this->removeSeoToolTopLinkCache($seoToolTopLink);
    }

    /**
     * Post update.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $seoToolTopLink Seo tool object.
     */
    public function postUpdate(SeoToolTopLink $seoToolTopLink, LifecycleEventArgs $event)
    {
        $this->removeSeoToolTopLinkCache($seoToolTopLink);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:SeoToolTopLink')->getTableName();
    }

    /**
     * Remove seo tool cache.
     *
     * @param object $seoToolTopLink Seo tool object.
     *
     * @return string
     */
    private function removeSeoToolTopLinkCache($seoToolTopLink)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        CommonManager::removeCache($this->container, $this->getTableName().'|getTopLinkArrayBySeoToolId|'.$seoToolTopLink->getSeoTool()->getId().'_'.$culture);
        CommonManager::removeCachePattern($this->container, '*list|page|seo*');
        CommonManager::removeCachePattern($this->container, '*addetail|page|seo*');
    }
}
