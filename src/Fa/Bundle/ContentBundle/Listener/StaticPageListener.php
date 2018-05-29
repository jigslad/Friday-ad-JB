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
use Fa\Bundle\ContentBundle\Entity\StaticPage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Fa\Bundle\ContentBundle\Repository\StaticPageRepository;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class StaticPageListener
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
     * @param object $event      LifecycleEventArgs.
     * @param object $staticPage Static page object.
     */
    public function preRemove(StaticPage $staticPage, LifecycleEventArgs $event)
    {
        $this->removeStaticPageCache($staticPage);
    }

    /**
     * Post persist.
     *
     * @param object $event      LifecycleEventArgs.
     * @param object $staticPage Static page object.
     */
    public function postPersist(StaticPage $staticPage, LifecycleEventArgs $event)
    {
        $this->removeStaticPageCache($staticPage);
    }

    /**
     * Post update.
     *
     * @param object $event      LifecycleEventArgs.
     * @param object $staticPage Static page object.
     */
    public function postUpdate(StaticPage $staticPage, LifecycleEventArgs $event)
    {
        $this->removeStaticPageCache($staticPage);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:StaticPage')->getTableName();
    }

    /**
     * Remove static page cache.
     *
     * @param object $staticPage Static page object.
     *
     * @return string
     */
    private function removeStaticPageCache($staticPage)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        if ($staticPage->getType() == StaticPageRepository::STATIC_PAGE_TYPE_ID) {
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getStaticPageLinkArray|*');
            CommonManager::removeCache($this->container, $this->getTableName().'|getStaticPagesForFooter|'.$culture);
        } elseif ($staticPage->getType() == StaticPageRepository::STATIC_BLOCK_TYPE_ID || $staticPage->getType() == StaticPageRepository::STATIC_BLOCK_GA_CODE_ID) {
            CommonManager::removeCache($this->container, $this->getTableName().'|getStaticBlockDetailArray|'.$staticPage->getSlug().'_'.$culture);
        }
    }
}
