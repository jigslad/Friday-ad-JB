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
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * AdReportDailyRepository repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdPrintReportDailyRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'aprd';

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
     * Remove ad print statistics.
     *
     * @param array  $adIds Ad id array.
     * @param string $date  Date.
     */
    public function removeAdPrintReportDailyAdsByIds($adIds, $date)
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
            ->andWhere(self::ALIAS.'.ad_id IN (:adIds)')
            ->setParameter('adIds', $adIds)
            ->andWhere('('.self::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.')')
            ->getQuery()
            ->execute();
    }

    /**
     * Get ad report query
     *
     * @param array   $searchParams Search parameters array.
     * @param array   $sorter       Sort parameters array.
     * @param object  $container    Container object.
     * @param boolean $isCountQuery Is count query.
     *
     * @return array
     */
    public function getAdPrintReportQuery($searchParams, $sorter, $container, $isCountQuery = false)
    {
        $adPrintReportFields = array_keys(self::getAdPrintReportFields());
        $qb                  = $this->createQueryBuilder(self::ALIAS);

        if ($isCountQuery) {
            $qb->select('COUNT('.self::ALIAS.'.id) as total_ads');
        } else {
            $qb->select(self::ALIAS.'.id', self::ALIAS.'.user_id');
            //->leftJoin('FaTiReportBundle:UserReport', UserReportRepository::ALIAS, 'WITH', UserReportRepository::ALIAS.'.user_id = '.self::ALIAS.'.user_id');
        }

        $qb->leftJoin('FaTiReportBundle:AdPrintReportDaily', self::ALIAS.'1', 'WITH', self::ALIAS.'.id < '.self::ALIAS.'1.id AND '.self::ALIAS.'.ad_id = '.self::ALIAS.'1.ad_id')
            ->andWhere(self::ALIAS.'1.id IS NULL');
        if (!$isCountQuery) {
            foreach ($adPrintReportFields as $field) {
                if (strstr($field, 'category_')) {
                    $field = 'category_id';
                }
                if (!in_array($field, array('name', 'business_name', 'phone', 'email'))) {
                    $qb->addSelect(self::ALIAS.'.'.$field);
                }
            }
        }

        $qb = $this->addFilter($qb, $searchParams, $sorter, $container);

        return $qb->getQuery();
    }

    /**
     *
     * @param object $qb           QueryBuilder object.
     * @param array  $searchParams Search parameters array.
     * @param array  $sorter       Sort parameters array.
     * @param object $container    Container object.
     *
     * @return QueryBuilder
     */
    private function addFilter($qb, $searchParams, $sorter, $container)
    {
        $adPrintReportSortFields = self::getAdPrintReportSortFields();

        // print expiry filter
        if (isset($searchParams['print_insert_date']) && $searchParams['print_insert_date']) {
            $finalStartDate = CommonManager::getTimeStampFromStartDate($searchParams['print_insert_date']);
            $finalEndDate = CommonManager::getTimeStampFromEndDate($searchParams['print_insert_date']);
            $qb = $qb->andWhere('('.self::ALIAS.'.print_insert_date BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
        }

        // role filter
        if (isset($searchParams['role_id']) && $searchParams['role_id']) {
            $qb->andWhere(self::ALIAS.'.role_id = (:roleId)')
            ->setParameter('roleId', $searchParams['role_id']);
        }

        // ad ref filter.
        if (isset($searchParams['ad_id']) && $searchParams['ad_id']) {
            $qb->andWhere(self::ALIAS.'.ad_id = (:adId)')
            ->setParameter('adId', $searchParams['ad_id']);
        }

        // source filter.
        if (isset($searchParams['source']) && $searchParams['source']) {
            $qb->andWhere(self::ALIAS.'.source = (:source)')
            ->setParameter('source', $searchParams['source']);
        }

        // print edition filter.
        if (isset($searchParams['print_edition_id']) && $searchParams['print_edition_id']) {
            $pattern = '^('.$searchParams['print_edition_id'].')$|(^'.$searchParams['print_edition_id'].',)|(,'.$searchParams['print_edition_id'].'$)|(,'.$searchParams['print_edition_id'].',)';

            $qb->andWhere("regexp(".self::ALIAS.".print_edition_ids, '".$pattern."') != false");
        }

        // sorting.
        if ($sorter['sort_field'] == 'ad_print_report_daily__id') {
            $sorter['sort_field'] = 'id';
        }
        if (in_array($sorter['sort_field'], $adPrintReportSortFields) && isset($sorter['sort_field']) && $sorter['sort_field'] && isset($sorter['sort_ord']) && $sorter['sort_ord']) {
            $qb->orderBy(self::ALIAS.'.'.$sorter['sort_field'], $sorter['sort_ord']);
        }

        return $qb;
    }

    /**
     * Get ad print report fields
     */
    public static function getAdPrintReportFields()
    {
        $adPrintReportFields = array();
        $adPrintReportFields['name'] = 'Customer name';
        $adPrintReportFields['business_name'] = 'Company name';
        $adPrintReportFields['role_id'] = 'User type';
        $adPrintReportFields['phone'] = 'Phone number';
        $adPrintReportFields['source'] = 'Source';
        $adPrintReportFields['email'] = 'Email';
        $adPrintReportFields['ad_id'] = 'Adref';
        $adPrintReportFields['title'] = 'Ad title';
        $adPrintReportFields['category_top'] = 'Category';
        $adPrintReportFields['category_leaf'] = 'Class';
        $adPrintReportFields['print_edition_ids'] = 'Print edition';
        $adPrintReportFields['package_name'] = 'Package';
        $adPrintReportFields['revenue_gross'] = 'Revenue gross';
        $adPrintReportFields['revenue_net'] = 'Revenue net';
        $adPrintReportFields['published_at'] = 'Published online';
        $adPrintReportFields['expires_at'] = 'Expiring online';

        return $adPrintReportFields;
    }

    /**
     * Get ad print report sort fields
     */
    public static function getAdPrintReportSortFields()
    {
        $adPrintReportSortFields   = array();
        $adPrintReportSortFields[] = 'id';
        $adPrintReportSortFields[] = 'ad_id';
        $adPrintReportSortFields[] = 'print_insert_date';
        $adPrintReportSortFields[] = 'published_at';
        $adPrintReportSortFields[] = 'revenue_gross';
        $adPrintReportSortFields[] = 'revenue_net';
        $adPrintReportSortFields[] = 'published_at';
        $adPrintReportSortFields[] = 'expires_at';
        $adPrintReportSortFields[] = 'source';

        return $adPrintReportSortFields;
    }

    /**
     * Format ad report fields.
     *
     * @param array   $adReportDetailArray Ad report detail array.
     * @param object  $container           Container object.
     */
    public function formatAdPrintReportRaw($adReportDetailArray, $container)
    {
        $fieldValueArray = array();
        $dateFields      = array('print_insert_date', 'published_at', 'expires_at');
        $entityFields    = array('category_id', 'print_edition_ids', 'role_id');
        $priceFields     = array('revenue_gross', 'revenue_net');

        foreach ($adReportDetailArray as $key => $value) {
            if (in_array($key, $priceFields)) {
                $fieldValueArray[$key] = CommonManager::formatCurrency($value, $container);
            } elseif (in_array($key, $entityFields)) {
                $entityCacheManager = $container->get('fa.entity.cache.manager');
                switch ($key) {
                    case 'category_id':
                        $categoryPath = array_values(CommonManager::getEntityRepository($container, 'FaEntityBundle:Category')->getCategoryPathArrayById($value, false, $container));
                        if (is_array($categoryPath)) {
                            $fieldValueArray['category_top'] = $categoryPath[0];
                            $fieldValueArray['category_leaf'] = end($categoryPath);
                        }
                        break;
                    case 'print_edition_ids':
                        $printEditionIds = explode(',', $value);
                        $fieldValueArray[$key] = '';
                        foreach ($printEditionIds as $printEditionId) {
                            $fieldValueArray[$key] .= $entityCacheManager->getEntityNameById('FaAdBundle:PrintEdition', $printEditionId).', ';
                        }
                        $fieldValueArray[$key] = trim($fieldValueArray[$key], ', ');
                        break;
                    case 'role_id':
                        if ($value == RoleRepository::ROLE_SELLER_ID) {
                            $fieldValueArray[$key] = 'Private advertiser';
                        } elseif ($value == RoleRepository::ROLE_BUSINESS_SELLER_ID) {
                            $fieldValueArray[$key] = 'Business advertiser';
                        }
                        break;
                }
            } else {
                if ($value) {
                    if (in_array($key, $dateFields)) {
                        $fieldValueArray[$key] = CommonManager::formatDate($value, $container, \IntlDateFormatter::SHORT);
                    } else {
                        $fieldValueArray[$key] = $value;
                    }
                } else {
                    $fieldValueArray[$key] = '-';
                }
            }
        }

        return $fieldValueArray;
    }
}
