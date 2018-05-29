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
class UserReportDailyRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'urd';

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
     * @param array  $userIds User id array.
     * @param string $date    Date.
     */
    public function removeUserReportDailyUsersByIds($userIds, $date)
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
            ->andWhere(self::ALIAS.'.user_id IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->andWhere('('.self::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.')')
            ->getQuery()
            ->execute();
    }

    /**
     * Get user report query
     *
     * @param array $searchParams search parameters array.
     *
     * @return array
     */
    public function getUserReportDailyTotalSum($searchParams)
    {
        $qb                         = $this->createQueryBuilder(self::ALIAS);
        $userReportDailyTableFields = array('renewed_ads', 'expired_ads', 'cancelled_ads', 'number_of_ad_placed', 'number_of_ad_sold', 'number_of_ads_to_renew', 'saved_searches', 'total_spent');

        if ($searchParams && CommonManager::inArrayMulti($userReportDailyTableFields, $searchParams['rus_report_columns'])) {
            $qb = $qb->select(
                'SUM('.self::ALIAS.'.renewed_ads) As renewed_ads',
                'SUM('.self::ALIAS.'.expired_ads) As expired_ads',
                'SUM('.self::ALIAS.'.cancelled_ads) As cancelled_ads',
                'SUM('.self::ALIAS.'.number_of_ad_placed) As number_of_ad_placed',
                'SUM('.self::ALIAS.'.number_of_ad_sold) As number_of_ad_sold',
                'SUM('.self::ALIAS.'.number_of_ads_to_renew) As number_of_ads_to_renew',
                'SUM('.self::ALIAS.'.saved_searches) As saved_searches',
                'SUM('.self::ALIAS.'.total_spent) As total_spent',
                'SUM('.self::ALIAS.'.profile_page_view_count) As profile_page_view_count',
                'SUM('.self::ALIAS.'.profile_page_email_sent_count) As profile_page_email_sent_count',
                'SUM('.self::ALIAS.'.profile_page_website_url_click_count) As profile_page_website_url_click_count',
                'SUM('.self::ALIAS.'.profile_page_phone_click_count) As profile_page_phone_click_count',
                'SUM('.self::ALIAS.'.profile_page_social_links_click_count) As profile_page_social_links_click_count',
                'SUM('.self::ALIAS.'.profile_page_map_click_count) As profile_page_map_click_count'
            );
        }

        if ($searchParams && !empty($searchParams['rus_from_date'])) {
            $finalStartDate = CommonManager::getTimeStampFromStartDate($searchParams['rus_from_date']);
            if (isset($searchParams['rus_to_date']) && $searchParams['rus_to_date'] != '') {
                $finalEndDate = CommonManager::getTimeStampFromEndDate($searchParams['rus_to_date']);
            } else {
                $finalEndDate = CommonManager::getTimeStampFromEndDate(date('d/m/Y'));
            }

            if (isset($searchParams['rus_date_filter_type']) && $searchParams['rus_date_filter_type'] == 'signup_date') {
                $qb = $qb->innerJoin('FaReportBundle:UserReport', UserReportRepository::ALIAS, 'WITH', UserReportRepository::ALIAS.'.user_id = '.self::ALIAS.'.user_id');
                $qb = $qb->where('('.UserReportRepository::ALIAS.'.signup_date BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
            } else {
                $qb = $qb->where('('.self::ALIAS.'.created_at BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
            }
        }

        if ($searchParams && !empty($searchParams['rus_user_type'])) {
            $qb = $qb->andWhere(self::ALIAS.'.role_id = '.$searchParams['rus_user_type']);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Get user report sort fields
     */
    public static function getUserReportDailySortFields()
    {
        $userReportDailySortFields   = array();
        $userReportDailySortFields[] = 'renewed_ads';
        $userReportDailySortFields[] = 'expired_ads';
        $userReportDailySortFields[] = 'cancelled_ads';
        $userReportDailySortFields[] = 'number_of_ad_placed';
        $userReportDailySortFields[] = 'number_of_ad_sold';
        $userReportDailySortFields[] = 'number_of_ads_to_renew';
        $userReportDailySortFields[] = 'saved_searches';
        $userReportDailySortFields[] = 'total_spent';

        return $userReportDailySortFields;
    }

    /**
     * Get different sum.
     *
     * @param integer $userId    user id.
     * @param string  $startDate start date.
     * @param string  $endDate   end date.
     */
    public function getDifferentSumByUserIdAndDateRange($userId, $startDate, $endDate)
    {
        $finalStartDate = CommonManager::getTimeStampFromStartDate($startDate);
        $finalEndDate   = CommonManager::getTimeStampFromEndDate($endDate);

        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(
            self::ALIAS.'.user_id',
            'SUM('.self::ALIAS.'.renewed_ads) As renewed_ads',
            'SUM('.self::ALIAS.'.expired_ads) As expired_ads',
            'SUM('.self::ALIAS.'.cancelled_ads) As cancelled_ads',
            'SUM('.self::ALIAS.'.number_of_ad_placed) As number_of_ad_placed',
            'SUM('.self::ALIAS.'.number_of_ad_sold) As number_of_ad_sold',
            'SUM('.self::ALIAS.'.number_of_ads_to_renew) As number_of_ads_to_renew',
            'SUM('.self::ALIAS.'.saved_searches) As saved_searches',
            'SUM('.self::ALIAS.'.total_spent) As total_spent'
        )
        ->where(self::ALIAS.'.user_id IN (:userId)')
        ->setParameter('userId', $userId)
        ->andWhere('('.self::ALIAS.'.created_at BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')')
        ->groupBy(self::ALIAS.'.user_id')
        ->orderBy(self::ALIAS.'.id', 'DESC');

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
     * Get existing user ids in user report daily table
     *
     * @param array $userIds user id array.
     *
     * @return array
     */
    public function getUserDailyReportUsersByIdsAndDate($userIds, $createdDate)
    {
        $userDailyReportUserIds = array();
        $reportUsers = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.user_id IN (:userIds)')
        ->setParameter('userIds', $userIds)
        ->andWhere(self::ALIAS.'.created_at = :createdAt')
        ->setParameter('createdAt', $createdDate)
        ->getQuery()
        ->getResult();

        foreach ($reportUsers as $reportUser) {
            $userDailyReportUserIds[] = $reportUser->getUserId();
        }

        return $userDailyReportUserIds;
    }
}
