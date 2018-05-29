<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\RoleRepository;

/**
 * Dotmailer repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Sagar Lotiya <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserReportCategoryDailyRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'urcd';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Remove user ad statistics.
     *
     * @param string $date Date.
     */
    public function removeUserReportCategoryDailyByDate($date)
    {
        if ($date) {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime($date)));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime($date)));
        } else {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime('-1 day')));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime('-1 day')));
        }
        $this->getBaseQueryBuilder()
            ->delete()
            ->andWhere('('.self::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.')')
            ->getQuery()
            ->execute();
    }

    /**
     * Get category in which maximum ad posted
     *
     * @param integer $userId    user id.
     * @param string  $startDate start date.
     * @param string  $endDate   end date.
     */
    public function getCategoryInWhichMaxAdPostedByUserIdAndDateRange($userId, $startDate, $endDate)
    {
        $finalStartDate = CommonManager::getTimeStampFromStartDate($startDate);
        $finalEndDate   = CommonManager::getTimeStampFromEndDate($endDate);

        $qb = $this->createQueryBuilder(self::ALIAS)
                ->select(self::ALIAS.'.user_id', self::ALIAS.'.category_id', 'COUNT('.self::ALIAS.'.category_id) As CategoryCount')
                ->where(self::ALIAS.'.user_id IN (:userId)')
                ->setParameter('userId', $userId)
                ->andWhere('('.self::ALIAS.'.created_at BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')')
                ->groupBy(self::ALIAS.'.user_id', self::ALIAS.'.category_id')
                ->orderBy('CategoryCount', 'DESC');
                //->setFirstResult(0)
                //->setMaxResults(1);

        $resultArray      = $qb->getQuery()->getResult();
        $finalResultArray = array();


        if ($resultArray && is_array($resultArray)) {
            foreach ($resultArray as $record) {
                if (!array_key_exists($record['user_id'], $finalResultArray)) {
                    $finalResultArray[$record['user_id']] = $record;
                }
            }
        }

        return $finalResultArray;
    }

    /**
     * Get category in which maximum ad posted
     *
     * @param integer $userId    user id.
     * @param string  $startDate start date.
     * @param string  $endDate   end date.
     */
    public function getCategoryInWhichMaxAdPostedByUserIdAndDateRange1($userId, $startDate, $endDate)
    {
        $finalStartDate = CommonManager::getTimeStampFromStartDate($startDate);
        $finalEndDate   = CommonManager::getTimeStampFromEndDate($endDate);

        $mainQb = $this->createQueryBuilder(self::ALIAS);
        $subQb  = $this->getCategoryInWhichMaxAdPostedByUserIdAndDateRangeSub($userId, $startDate, $endDate);

        //$mainQb->select(self::ALIAS.'.user_id', self::ALIAS.'.category_id', 'COUNT('.self::ALIAS.'.category_id) As CategoryCount')
        $qb = $mainQb->select('T1.user_id', 'T1.category_id', 'COUNT(T1.category_id) As CategoryCount');
        $qb = $qb->from($subQb->getDQL(), 'T1')->groupBy(self::ALIAS.'.user_id')->orderBy('CategoryCount', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get category in which maximum ad posted
     *
     * @param array $searchParams search parameters.
     */
    public function getCategoryInWhichMaxAdPostedByDateRange($searchParams)
    {
        $finalStartDate = '';
        $finalEndDate   = '';

        if ($searchParams && !empty($searchParams['rus_from_date'])) {
            $finalStartDate = CommonManager::getTimeStampFromStartDate($searchParams['rus_from_date']);
        }

        if ($searchParams && !empty($searchParams['rus_to_date'])) {
            $finalEndDate = CommonManager::getTimeStampFromEndDate($searchParams['rus_to_date']);
        }

        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.category_id', 'COUNT('.self::ALIAS.'.category_id) As CategoryCount')
        ->innerJoin('FaReportBundle:UserReport', UserReportRepository::ALIAS, 'WITH', UserReportRepository::ALIAS.'.user_id = '.self::ALIAS.'.user_id')
        ->orderBy('CategoryCount', 'DESC')
        ->setFirstResult(0)
        ->setMaxResults(1);

        if ($searchParams && !empty($searchParams['rus_date_filter_type'])) {
            if ($searchParams['rus_date_filter_type'] == 'signup_date') {
                $qb = $qb->where('('.UserReportRepository::ALIAS.'.signup_date BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
            } else {
                $qb = $qb->where('('.UserReportRepository::ALIAS.'.created_at BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
            }
        }

        if ($searchParams && !empty($searchParams['rus_name'])) {
            $qb = $qb->andWhere(UserReportRepository::ALIAS.'.name LIKE :name');
            $qb = $qb->setParameter('name', $searchParams['rus_name'].'%');
        }

        if ($searchParams && !empty($searchParams['rus_email'])) {
            $qb = $qb->andWhere(UserReportRepository::ALIAS.'.email LIKE :email');
            $qb = $qb->setParameter('email', $searchParams['rus_email'].'%');
        }

        if ($searchParams && !empty($searchParams['rus_user_type'])) {
            $qb = $qb->andWhere(UserReportRepository::ALIAS.'.role_id = '.$searchParams['rus_user_type']);
        } else {
            $roleIds = array(RoleRepository::ROLE_BUSINESS_SELLER_ID, RoleRepository::ROLE_SELLER_ID);
            $qb = $qb->andWhere(UserReportRepository::ALIAS.'.role_id IN (:roleIds)');
            $qb = $qb->setParameter('roleIds', $roleIds);
        }

        return $qb->getQuery()->getResult();
    }
}
