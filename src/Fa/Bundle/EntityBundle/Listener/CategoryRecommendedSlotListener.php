<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Entity\SeoToolPopularSearch;
use Fa\Bundle\EntityBundle\Entity\CategoryRecommendedSlot;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CategoryRecommendedSlotListener
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
     * @param object $event                   LifecycleEventArgs.
     * @param object $categoryRecommendedSlot Category recommended slot object.
     */
    public function preRemove(CategoryRecommendedSlot $categoryRecommendedSlot, LifecycleEventArgs $event)
    {
        $this->removeCategoryRecommendedSlotCache($categoryRecommendedSlot);
    }

    /**
     * Post persist.
     *
     * @param object $event                   LifecycleEventArgs.
     * @param object $categoryRecommendedSlot Category recommended slot object.
     */
    public function postPersist(CategoryRecommendedSlot $categoryRecommendedSlot, LifecycleEventArgs $event)
    {
        $this->removeCategoryRecommendedSlotCache($categoryRecommendedSlot);
    }

    /**
     * Post update.
     *
     * @param object $event                   LifecycleEventArgs.
     * @param object $categoryRecommendedSlot Category recommended slot object.
     */
    public function postUpdate(SeoToolPopularSearch $categoryRecommendedSlot, LifecycleEventArgs $event)
    {
        $this->removeCategoryRecommendedSlotCache($categoryRecommendedSlot);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryRecommendedSlot')->getTableName();
    }

    /**
     * Remove Category Recommended Slot cache.
     *
     * @param object $categoryRecommendedSlot Category recommended slot object.
     *
     * @return string
     */
    private function removeCategoryRecommendedSlotCache($categoryRecommendedSlot)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        CommonManager::removeCachePattern($this->container, '*getCategoryRecommendedSlotArrayByCategoryId*');
        sleep(1);
        CommonManager::removeCachePattern($this->container, '*getAdDetailCategoryRecommendedSlotArrayByCategoryId*');
    }
}
