<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\PaaFieldRepository;
use Doctrine\ORM\Query;
use Fa\Bundle\CoreBundle\Walker\SortableNullsWalker;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PaaFieldRuleRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'pfr';

    const MIN_MAX_TYPE_LENGTH = 'LENGTH';
    const MIN_MAX_TYPE_RANGE  = 'RANGE';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Get category wise PAA field rules query.
     *
     * @param integer $categoryId Category id.
     * @param string  $ordBy      Order by ord, step or both
     *
     * @return object
     */
    public function getPaaFieldRulesQueryBuilderByCategoryId($categoryId = null, $ordBy = 'ord')
    {
        $queryBuilder = $this->getBaseQueryBuilder()
                        ->select(self::ALIAS, PaaFieldRepository::ALIAS)
                        ->leftJoin(self::ALIAS.'.paa_field', PaaFieldRepository::ALIAS)
                        ->where(self::ALIAS.'.category = '.$categoryId);

        if ($ordBy == 'both' || $ordBy == 'bothWithNullLast') {
            $queryBuilder->addOrderBy(self::ALIAS.'.step', 'asc')
                         ->addOrderBy(self::ALIAS.'.ord', 'asc');
        } else {
            $queryBuilder->addOrderBy(self::ALIAS.'.'.$ordBy, 'asc');
        }
        
        return $queryBuilder;
    }

    /**
     * Get category wise PAA field rules.
     *
     * @param integer $categoryId Category id.
     *
     * @return array
     */
    public function getPaaFieldRulesByCategoryId($categoryId = null, $ordBy = 'ord')
    {
        $query = $this->getPaaFieldRulesQueryBuilderByCategoryId($categoryId, $ordBy)->getQuery();
        if ($ordBy == 'bothWithNullLast') {
            $query
            ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\CoreBundle\Walker\SortableNullsWalker')
            ->setHint('SortableNullsWalker.fields', array(
                'step' => SortableNullsWalker::NULLS_LAST,
            ));
        }

        return $query->getResult();
    }

    /**
     * Get category wise PAA field rules which needs to show in form.
     *
     * @param integer $categoryId Category id.
     *
     * @return array
     */
    public function getActivePaaFieldRulesByCategoryId($categoryId = null)
    {
        $queryBuilder = $this->getPaaFieldRulesQueryBuilderByCategoryId($categoryId);

        return $queryBuilder->andWhere(self::ALIAS.'.status = 1')->getQuery()->getResult();
    }

    /**
     * Get show hide array.
     *
     * @param Container $container Container identifier.
     * @param boolean   $addEmpty  Flag to show empty message.
     *
     * @return array
     */
    public static function getStatusArray($container, $addEmpty = true)
    {
        $translator  = CommonManager::getTranslator($container);
        $statusArray = array();

        if ($addEmpty) {
            $statusArray[''] = $translator->trans('Both (Show & Hide)');
        }

        $statusArray[1] = $translator->trans('Show');
        $statusArray[0] = $translator->trans('Hide');

        return $statusArray;
    }

    /**
     * Add status filter.
     *
     * @param mixed $status Entity type
     */
    protected function addStatusFilter($status = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.status = '.$status);
    }

    /**
     * Get PAA fields by category or category ancestor.
     *
     * @param mixed $categoryId Category id.
     *
     * @return array
     */
    public function getActivePaaFieldsRulesByCategoryAncestor($categoryId = null)
    {
        // If given category have not PAA field rules defined then find by their parent categories.
        $paaFieldRules = $this->getActivePaaFieldRulesByCategoryId($categoryId);

        if (!count($paaFieldRules)) {
            $category = $this->_em->getRepository('FaEntityBundle:Category')->find($categoryId);
            if ($category && $category->getLvl() > 1) {
                $parent = $category->getParent();
                return $this->getActivePaaFieldsRulesByCategoryAncestor($parent->getId());
            }
        }

        return $paaFieldRules;
    }

    /**
     * Get PAA fields by category or category ancestor.
     *
     * @param mixed $categoryId Category id.
     *
     * @return array
     */
    public function getPaaFieldRulesByCategoryAncestor($categoryId = null, $ordBy = 'ord')
    {
        $paaFieldRules = $this->getPaaFieldRulesByCategoryId($categoryId, $ordBy);

        if ($paaFieldRules && !empty($paaFieldRules)) {
            return $paaFieldRules;
        } else {
            $categoryObj = $this->_em->getRepository('FaEntityBundle:Category')->find($categoryId);
            if ($categoryObj && $categoryObj->getLvl() > 1) {
                $parentObj = $categoryObj->getParent();
                return $this->getPaaFieldRulesByCategoryAncestor($parentObj->getId());
            }
        }
    }

    /**
     * Get PAA fields by category or category ancestor.
     *
     * @param mixed   $categoryId Category id.
     * @param mixed   $container  Container identifier.
     * @param integer $step       Field Step.
     * @param string  $ordBy      Ordered by ord, step or both.
     *
     * @return array
     */
    public function getPaaFieldRulesArrayByCategoryAncestor($categoryId = null, $container = null, $step = null, $ordBy = 'ord')
    {
        $paaFieldRules = $this->getPaaFieldRulesArrayByCategoryId($categoryId, $container, $step, $ordBy);

        if (isset($paaFieldRules[$categoryId]) && count($paaFieldRules[$categoryId])) {
            return $paaFieldRules[$categoryId];
        } else {
            $categoryPath = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container);
            $categoryIds  = array_keys($categoryPath);
            if ($categoryIds && count($categoryIds) > 1) {
                $parentCategoryId = $categoryIds[(count($categoryIds) - 2)];
                return $this->getPaaFieldRulesArrayByCategoryAncestor($parentCategoryId, $container, $step, $ordBy);
            }
        }
    }

    /**
     * Get category wise PAA field rules.
     *
     * @param mixed   $categoryId Category id.
     * @param mixed   $container  Container identifier.
     * @param integer $step       Field Step.
     * @param string  $ordBy      Ordered by ord, step or both.
     *
     * @return array
     */
    public function getPaaFieldRulesArrayByCategoryId($categoryId = null, $container = null, $step = null, $ordBy = 'ord')
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $cacheKey    = $this->getTableName().'|'.__FUNCTION__.'|'.$categoryId.'_'.$step.'_'.$ordBy.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $paaFieldRulesArray = array();

        $queryBuilder = $this->getPaaFieldRulesQueryBuilderByCategoryId($categoryId, $ordBy);
        if ($step) {
            $queryBuilder->andWhere(self::ALIAS.'.step = :step')->setParameter('step', $step);
        }

        $paaFieldRules = $queryBuilder->getQuery()->getArrayResult();
        $paaFieldRulesArray[$categoryId] = $paaFieldRules;

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $paaFieldRulesArray);
        }

        return $paaFieldRulesArray;
    }

    /**
     * Get table name.
     */
    private function getTableName()
    {
        return $this->_em->getClassMetadata('FaAdBundle:PaaFieldRule')->getTableName();
    }

    /**
     * Get reg no fields category ids
     *
     * @param integer $categoryId Category id.
     * @param string  $ordBy      Order by ord, step or both
     *
     * @return object
     */
    public function getRegNoFieldCategoryIds($container)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $cacheKey    = $this->getTableName().'|'.__FUNCTION__.'|_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $regNoFieldCategoryIds = array();

        $queryBuilder = $this->getBaseQueryBuilder()
        ->select(self::ALIAS.'.id', 'IDENTITY('.self::ALIAS.'.category) as categoryId')
        ->andWhere(self::ALIAS.'.status = 1')
        ->andWhere(self::ALIAS.'.paa_field = 118');

        $paaFieldRules = $queryBuilder->getQuery()->getArrayResult();

        if (count($paaFieldRules)) {
            foreach ($paaFieldRules as $paaFieldRule) {
                $regNoFieldCategoryIds[] = $paaFieldRule['categoryId'];
            }
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $regNoFieldCategoryIds);
        }

        return $regNoFieldCategoryIds;
    }

    /**
     * Get category wise PAA field rules for one field.
     *
     * @param mixed   $categoryId Category id.
     * @param integer $paaFieldId Paa Field id.
     * @param mixed   $container  Container identifier.
     *
     * @return array
     */
    public function getPaaFieldRuleArrayByCategoryIdForField($categoryId, $paaFieldId, $container)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $cacheKey    = $this->getTableName().'|'.__FUNCTION__.'|'.$categoryId.'_'.$paaFieldId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $paaFieldRulesArray = array();

        $queryBuilder = $this->getPaaFieldRulesQueryBuilderByCategoryId($categoryId);
        $queryBuilder->andWhere(self::ALIAS.'.paa_field = :paa_field')->setParameter('paa_field', $paaFieldId);

        $paaFieldRules = $queryBuilder->getQuery()->getArrayResult();
        $paaFieldRulesArray[$categoryId] = $paaFieldRules;

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $paaFieldRulesArray);
        }

        return $paaFieldRulesArray;
    }

    /**
     * Get PAA fields by category or category ancestor for one field.
     *
     * @param mixed   $categoryId Category id.
     * @param integer $paaFieldId Paa Field id.
     * @param mixed   $container  Container identifier.
     *
     * @return array
     */
    public function getPaaFieldRuleArrayByCategoryAncestorForOneField($categoryId, $paaFieldId, $container)
    {
        $paaFieldRules = $this->getPaaFieldRuleArrayByCategoryIdForField($categoryId, $paaFieldId, $container);

        if (isset($paaFieldRules[$categoryId]) && count($paaFieldRules[$categoryId])) {
            return $paaFieldRules[$categoryId];
        } else {
            $categoryPath = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container);
            $categoryIds  = array_keys($categoryPath);
            if ($categoryIds && count($categoryIds) > 1) {
                $parentCategoryId = $categoryIds[(count($categoryIds) - 2)];
                $paaFieldRules = $this->getPaaFieldRuleArrayByCategoryIdForField($parentCategoryId, $paaFieldId, $container);
                if (isset($paaFieldRules[$parentCategoryId]) && count($paaFieldRules[$parentCategoryId])) {
                    return $paaFieldRules[$parentCategoryId];
                }
            }
        }
    }
}
