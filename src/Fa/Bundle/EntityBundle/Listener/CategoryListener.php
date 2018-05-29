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
use Fa\Bundle\EntityBundle\Entity\Category;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CategoryListener
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
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Category object.
     */
    public function preRemove(Category $entity, LifecycleEventArgs $event)
    {
        $this->removeCategoryCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Category object.
     */
    public function postPersist(Category $entity, LifecycleEventArgs $event)
    {
        $this->removeCategoryCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity Category object.
     */
    public function postUpdate(Category $entity, LifecycleEventArgs $event)
    {
        $this->removeCategoryCache($entity);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:Category')->getTableName();
    }

    /**
     * Returns category dimension table name.
     *
     * @return string
     */
    private function getCategoryDimensionTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:CategoryDimension')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @param removeCategoryCache $entity
     *
     * @return string
     */
    private function removeCategoryCache($entity)
    {
        $categoryPathArray = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById1($entity->getId());

        // Remove cache for all parents of this category.
        foreach (array_keys($categoryPathArray) as $categoryId) {
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getCategoryPathArrayById|'.$categoryId.'*');

            // Remove cache for leaf children categories.
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getNestedLeafChildrenIdsByCategoryId|'.$categoryId.'*');

            // Remove cache for nested categories used in auto complete for posting
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getNestedChildrenArrayByParentId|'.$categoryId.'*');

            // Remove nested children ids array cache
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getNestedChildrenIdsByCategoryId|'.$categoryId.'*');
        }

        // Remove children category cache of given category and also parent categoy of given category
        if ($entity->getParent()) {
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getChildrenKeyValueArrayByParentId|'.$entity->getParent()->getId().'*');
            CommonManager::removeCachePattern($this->container, $this->getTableName().'|getChildrenKeyValueArrayByParentId|'.$entity->getId().'*');
        }

        // Remove category dimension of given category
        CommonManager::removeCachePattern($this->container, $this->getCategoryDimensionTableName().'|getDimesionsByCategoryId|'.$entity->getId().'*');

        //remove full slug by id cache
        //CommonManager::removeCachePattern($this->container, $this->getTableName().'|getFullSlugById_'.$entity->getId().'*');
        CommonManager::removeCachePattern($this->container, 'EntityCacheManager|getEntitySlugById|FaEntityBundle:Category_'.$entity->getId().'*');

        //remove id by full slug by cache
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getCategoryByFullSlug|'.$entity->getFullSlug().'*');

        //remove slug by id cache
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getSlugById|'.$entity->getId().'*');

        //remove id by slug cache
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getIdBySlug|'.$entity->getId().'*');

        //remove getEntityNameById cache for entity cache manager
        CommonManager::removeCachePattern($this->container, 'EntityCacheManager|getEntityNameById|FaEntityBundle:Category_'.$entity->getId().'*');

        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getMaxLevel|*');

        //Remove category footer cache
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getFooterCategories|*');

        //Remove category header cache
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getHeaderCategories|*');

        //Remove templace cache for header category menu
        CommonManager::removeCachePattern($this->container, 'header|menu|*');

        // Remove level wise category cache
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getCategoryByLevelArray|*');

        //Remove cache for nimber
        CommonManager::removeCache($this->container, $this->getTableName().'|getNimberDetailForCategoryId|'.$entity->getId());

        //Remove cache for paa hide categories
        CommonManager::removeCache($this->container, $this->getTableName().'|getPaaDisabledCategoryIds');

        //Remove cache for finance
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getFinanceDetailForCategoryId|*');

        //Remove cache for one click enquire
        CommonManager::removeCachePattern($this->container, $this->getTableName().'|getOneclickenqDetailForCategoryId|*');

        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:generate:category-slug-path --category_id='.$entity->getId().' >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:generate:entity-url-keys --category_id='.$entity->getId().' >/dev/null &');
    }
}
