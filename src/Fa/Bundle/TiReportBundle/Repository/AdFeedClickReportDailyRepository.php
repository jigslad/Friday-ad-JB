<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedSite;

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
class AdFeedClickReportDailyRepository extends EntityRepository
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
     * Get ad report fields
     */
    public static function getAdFeedClickReportFields()
    {
        $adReportFields = array();
        $adReportFields['ad_feed_site_id'] = 'Feed source';
        $adReportFields['ad_id'] = 'Adref';
        $adReportFields['view'] = 'Clicks';

        asort($adReportFields);

        return $adReportFields;
    }


    /**
     * Get ad feed click report query
     *
     * @param array $searchParams search parameters array.
     *
     * @return array
     */
    public function getAdFeedClickReportDailyQuery($searchParams, $sorter = null, $container, $isCountQuery = false)
    {
        $adFeedClickReportDailyTableFields = array('view');

        $qb = $this->createQueryBuilder(self::ALIAS);

        if ($isCountQuery) {
            $qb->select('COUNT('.self::ALIAS.'.id)');
        } else {
            $qb->select(
                self::ALIAS.'.ad_id',
                self::ALIAS.'.ad_feed_site_id',
                self::ALIAS.'.created_at'
                );

            $qb = $qb->addSelect(
                'SUM('.AdFeedClickReportDailyRepository::ALIAS.'.view) As view'
            );

            if (is_array($sorter) && array_key_exists('sort_field', $sorter)) {
                if (in_array($sorter['sort_field'], AdFeedClickReportDailyRepository::getReportSortFields())) {
                    $qb = $qb->addSelect('SUM('.AdFeedClickReportDailyRepository::ALIAS.'.'.$sorter['sort_field'].') As '.$sorter['sort_field'].'_sum');
                }
            }
        }

        if ($searchParams && !empty($searchParams['from_date'])) {
            $finalStartDate = CommonManager::getTimeStampFromStartDate($searchParams['from_date']);
            if (isset($searchParams['to_date']) && $searchParams['to_date'] != '') {
                $finalEndDate = CommonManager::getTimeStampFromEndDate($searchParams['to_date']);
            } else {
                $finalEndDate = CommonManager::getTimeStampFromEndDate(date('d/m/Y'));
            }
            $qb = $qb->where('('.self::ALIAS.'.created_at BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
            $qb = $qb->orWhere('('.AdFeedClickReportDailyRepository::ALIAS.'.created_at BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
        }

        if (!$isCountQuery) {
            if($searchParams['feed_report_type'] == 'ad_ref' && isset($searchParams['ad_feed_site_id']) && $searchParams['ad_feed_site_id']) {
                $qb->addGroupBy(self::ALIAS.'.ad_feed_site_id');
                $qb->addGroupBy(self::ALIAS.'.ad_id');
            } elseif($searchParams['feed_report_type'] == 'all' || (isset($searchParams['ad_feed_site_id']) && $searchParams['ad_feed_site_id'])) {
                $qb = $qb->addGroupBy(self::ALIAS.'.ad_feed_site_id');
            } else {
                $qb = $qb->addGroupBy(self::ALIAS.'.ad_id');
            }

            $qb = $this->addSorter($qb, $sorter, $container);
        }

        $qb = $this->addFilter($qb, $searchParams, $container);

        return $qb->getQuery();
    }


    /**
     *
     * @param object $qb           QueryBuilder object.
     * @param array  $searchParams Search parameters array.
     * @param object $container    Container object.
     *
     * @return QueryBuilder
     */
    private function addFilter($qb, $searchParams, $container)
    {
        // ad ref filter.
        if (isset($searchParams['ad_id']) && $searchParams['ad_id']) {
            $qb->andWhere(self::ALIAS.'.ad_id = (:adId)')
            ->setParameter('adId', $searchParams['ad_id']);
        }

        if (isset($searchParams['ad_feed_site_id']) && $searchParams['ad_feed_site_id']) {
          $qb->andWhere(self::ALIAS.'.ad_feed_site_id = (:adFeedSiteId)')
          ->setParameter('adFeedSiteId', $searchParams['ad_feed_site_id']);
        }


        return $qb;
    }


    /**
     * Get ad report sort fields
     */
    public static function getReportSortFields()
    {
        $adReportSortFields   = array();
        $adReportSortFields[] = 'ad_id';

        return $adReportSortFields;
    }

    /**
     *
     * @param object $qb           QueryBuilder object.
     * @param array  $sorter       Sort parameters array.
     * @param object $container    Container object.
     *
     * @return QueryBuilder
     */
    private function addSorter($qb, $sorter, $container)
    {
        // sorting.
        $sortFields = self::getAdFeedClickReportSortFields();
        if (in_array($sorter['sort_field'], $sortFields) && isset($sorter['sort_field']) && $sorter['sort_field'] && isset($sorter['sort_ord']) && $sorter['sort_ord']) {
            if (in_array($sorter['sort_field'], self::getReportSortFields())) {
                $qb->orderBy(self::ALIAS.'.'.$sorter['sort_field'], $sorter['sort_ord']);
            } else if (in_array($sorter['sort_field'], AdFeedClickReportDailyRepository::getReportSortFields())) {
                $qb->orderBy($sorter['sort_field'].'_sum', $sorter['sort_ord']);
            }
        }

        return $qb;
    }


    /**
     * Get ad feed click report sort fields
     */
    public static function getAdFeedClickReportSortFields()
    {
        return array_merge(self::getReportSortFields(), AdFeedClickReportDailyRepository::getReportSortFields());
    }


    /**
     * Format ad report fields.
     *
     * @param array   $adReportDetailArray Ad report detail array.
     * @param object  $container           Container object.
     * @param integer $uniqueBit           Unique bit.
     */
    public function formatAdFeedClickReportRaw($adReportDetailArray, $container, $uniqueBit = null)
    {
        $fieldValueArray = array();

        foreach ($adReportDetailArray as $key => $value) {
            if ($value) {
              $fieldValueArray[$key] = $value;
            } else {
              $fieldValueArray[$key] = '-';
            }
        }

        return $fieldValueArray;
    }
}
