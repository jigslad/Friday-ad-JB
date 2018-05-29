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
use Fa\Bundle\ContentBundle\Entity\SeoTool;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolListener
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
     * @param object $seoTool Seo tool object.
     */
    public function preRemove(SeoTool $seoTool, LifecycleEventArgs $event)
    {
        $this->removeSeoToolCache($seoTool);
    }

    /**
     * Post persist.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $seoTool Seo tool object.
     */
    public function postPersist(SeoTool $seoTool, LifecycleEventArgs $event)
    {
        $this->removeSeoToolCache($seoTool);
    }

    /**
     * Post update.
     *
     * @param object $event   LifecycleEventArgs.
     * @param object $seoTool Seo tool object.
     */
    public function postUpdate(SeoTool $seoTool, LifecycleEventArgs $event)
    {
        $this->removeSeoToolCache($seoTool);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:SeoTool')->getTableName();
    }

    /**
     * Remove seo tool cache.
     *
     * @param object $seoTool Seo tool object.
     *
     * @return string
     */
    private function removeSeoToolCache($seoTool)
    {
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getCustomizedUrlData|*');
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getSeoRulesKeyValueArray|*');
        CommonManager::removeCachePattern($this->container, 'list|page|seo*');

        if (in_array($seoTool->getPage(), array(SeoToolRepository::ADVERT_DETAIL_PAGE, SeoToolRepository::ADVERT_IMG_ALT))) {
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getSeoPageRuleDetailForSolrResult|*');
        }
        if ($seoTool->getPage() == SeoToolRepository::ADVERT_LIST_PAGE) {
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getSeoPageRuleDetailForListResult|*');
        }
    }
}
