<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\LocationGroupRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * Package rule repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PackageRuleRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'pr';

    /**
     * Remove packages based on package id.
     *
     * @param integer $packageId
     */
    public function removeRecordsByPackageId($packageId)
    {
        $records = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(sprintf('%s.package = %d', self::ALIAS, $packageId))
        ->getQuery()
        ->getResult();

        if ($records) {
            foreach ($records as $record) {
                $this->_em->remove($record);
            }
            $this->_em->flush();
        }
    }

    /**
     * Get category and location groups based on package ids.
     *
     * @param array $packageIds
     */
    public function getCategoryLocationGroupByPackageIds($packageIds)
    {
        $categoryLocationArray = array();
        $packageRules = $this->createQueryBuilder(self::ALIAS)
            ->addSelect(LocationGroupRepository::ALIAS.'.name as location_group_name')
            ->addSelect(LocationGroupRepository::ALIAS.'.id as location_group_id')
            ->addSelect(CategoryRepository::ALIAS.'.id as category_id')
            ->addSelect(PackageRepository::ALIAS.'.id as package_id')
            ->leftJoin(self::ALIAS.'.location_group', LocationGroupRepository::ALIAS)
            ->leftJoin(self::ALIAS.'.category', CategoryRepository::ALIAS)
            ->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
            ->andWhere(self::ALIAS.'.package IN (:packageIds)')
            ->setParameter('packageIds', $packageIds)
            ->getQuery()
            ->getArrayResult();

        foreach ($packageRules as $packageRule) {
            if (isset($packageRule['location_group_id']) && !isset($categoryLocationArray[$packageRule['package_id']]) && !isset($categoryLocationArray[$packageRule['package_id']]['location'])) {
                $categoryLocationArray[$packageRule['package_id']]['location'] = array($packageRule['location_group_id'] => $packageRule['location_group_name']);
            } elseif (isset($packageRule['location_group_id'])) {
                $categoryLocationArray[$packageRule['package_id']]['location'] = $categoryLocationArray[$packageRule['package_id']]['location'] + array($packageRule['location_group_id'] => $packageRule['location_group_name']);
            }

            if (isset($packageRule['category_id'])) {
                $categoryLocationArray[$packageRule['package_id']]['category_id'] = $packageRule['category_id'];
            }
        }

        return $categoryLocationArray;
    }

    /**
     * Get active packages by category id, location group & user type.
     *
     * @param integer $categoryId
     * @param array   $locationGroupIdArray
     * @param array   $roleIdArray
     * @param array   $currentActivePackageIds
     * @param object  $container
     * @param boolean $skipAdminPackages
     * @param boolean $showOnlyCurrentActivePackage
     * @param boolean $fetchOnlyAdminPackages
     *
     * @return Doctrine_Object
     */
    public function getActivePackagesByCategoryId($categoryId, $locationGroupIdArray, $roleIdArray, $currentActivePackageIds = array(), $container = null, $skipAdminPackages = true, $showOnlyCurrentActivePackage = false, $fetchOnlyAdminPackages = false)
    {
        $query = $this->createQueryBuilder(self::ALIAS)
            ->select(self::ALIAS, PackageRepository::ALIAS)
            ->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
            ->andWhere(PackageRepository::ALIAS.'.status = 1')
            ->andWhere(PackageRepository::ALIAS.'.package_for = :package_for')
            ->setParameter('package_for', 'ad')
            ->andWhere(self::ALIAS.'.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            //->addOrderBy(PackageRepository::ALIAS.'.price', 'ASC')
            ->addGroupBy(PackageRepository::ALIAS.'.id');

        // remove assigned packages.
        if (count($currentActivePackageIds)) {
            if ($showOnlyCurrentActivePackage) {
                $query->andWhere(PackageRepository::ALIAS.'.id IN (:currentActivePackageIds)')
                ->setParameter(':currentActivePackageIds', $currentActivePackageIds);
            } else {
                $query->andWhere(PackageRepository::ALIAS.'.id NOT IN (:currentActivePackageIds)')
                ->setParameter(':currentActivePackageIds', $currentActivePackageIds);
            }
        }

        //add location group.
        if (count($locationGroupIdArray)) {
            $query->andWhere(self::ALIAS.'.location_group IN (:locationGroupId) OR '.self::ALIAS.'.location_group IS NULL')
                ->setParameter('locationGroupId', $locationGroupIdArray);
        } else {
            $query->andWhere(self::ALIAS.'.location_group IS NULL');
        }

        //add user type.
        if (count($roleIdArray)) {
            $query->andWhere(PackageRepository::ALIAS.'.role IN (:roleIds) OR '.PackageRepository::ALIAS.'.role IS NULL')
            ->setParameter('roleIds', $roleIdArray);
        }

        if ($skipAdminPackages) {
            $query->andWhere(PackageRepository::ALIAS.'.is_admin_package = 0');
        }

        if ($fetchOnlyAdminPackages) {
            $query->andWhere(PackageRepository::ALIAS.'.is_admin_package = 1');
        }

        $packages =  $query->getQuery()->getResult();
		
        if (!count($packages) && $categoryId) { 
            $parentCategoryIds = array_keys($this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container));
            array_pop($parentCategoryIds);
            $parentCategoryIds = array_reverse($parentCategoryIds);
            if (count($parentCategoryIds)) {
                foreach ($parentCategoryIds as $parentCategoryId) {
                    return $this->getActivePackagesByCategoryId($parentCategoryId, $locationGroupIdArray, $roleIdArray, $currentActivePackageIds, $container, $skipAdminPackages, $showOnlyCurrentActivePackage, $fetchOnlyAdminPackages);
                }
            }
        }

        return $packages;
    }
    
    /**
     * Get package rules based on package id.
     *
     * @param integer $packageId
     *
     * @return array
     */
    public function getPackageRuleArrayByPackageId($packageId)
    {
        $packageRuleArray = array();

        $records = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(sprintf('%s.package = %d', self::ALIAS, $packageId))
        ->getQuery()
        ->getResult();

        if ($records) {
            foreach ($records as $record) {
                $packageRuleArray[] = array(
                    'location_group_id' => ($record->getLocationGroup() ? $record->getLocationGroup()->getId() : null),
                    'category_id' => ($record->getCategory() ? $record->getCategory()->getId() : null),
                );
            }
        }

        return $packageRuleArray;
    }
    
    public function getPackageByCategoryId($packagesId = '') {
    	if($packagesId != '') {
    		$query = $this->createQueryBuilder(self::ALIAS)
    		->select(self::ALIAS, PackageRepository::ALIAS)
    		->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
    		->andWhere(PackageRepository::ALIAS.'.status = 1')
    		->andWhere(PackageRepository::ALIAS.'.package_for = :package_for')
    		->setParameter('package_for', 'ad')
    		->andWhere(PackageRepository::ALIAS.'.id = :packageId')
    		->setParameter('packageId', $packagesId)
    		->addGroupBy(PackageRepository::ALIAS.'.id');
    		
    		$packages =  $query->getQuery()->getResult();
    		return $packages;
    	}
    }
    
}
