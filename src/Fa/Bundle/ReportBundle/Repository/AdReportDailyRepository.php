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
use Fa\Bundle\ReportBundle\Repository\AdPrintInsertDateReportDailyRepository;
use Doctrine\ORM\QueryBuilder;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;

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
class AdReportDailyRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'ard';

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
     * Remove ad statistics.
     *
     * @param array  $adIds Ad id array.
     * @param string $date  Date.
     */
    public function removeAdReportDailyAdsByIds($adIds, $date)
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
    public function getAdReportQuery($searchParams, $sorter, $container, $isCountQuery = false)
    {
        $adReportFields = array_keys(self::getAdReportFields());
        $qb             = $this->createQueryBuilder(self::ALIAS);

        if ($isCountQuery) {
            $qb->select('COUNT(DISTINCT '.self::ALIAS.'.id) as total_ads');
        } else {
            $qb->select(self::ALIAS.'.id', self::ALIAS.'.ad_id', self::ALIAS.'.print_revenue_gross', self::ALIAS.'.print_edition_ids', self::ALIAS.'.duration_print', self::ALIAS.'.skip_payment_reason')
            ->distinct(self::ALIAS.'.id');
        }

        if (!$isCountQuery && isset($searchParams['report_columns']) && count($searchParams['report_columns'])) {
            foreach ($searchParams['report_columns'] as $field) {
                if (!in_array($field, array('published_print_revenue_gross', 'published_print_revenue_net', 'print_insert_date')) && in_array($field, $adReportFields)) {
                    if (strstr($field, 'category_')) {
                        $field = 'category_id';
                    }
                    $qb->addSelect(self::ALIAS.'.'.$field);
                }
            }
        }

        $isPrintPublishedFlag = false;
        if (in_array('print_insert_date', $searchParams['report_columns']) || (isset($searchParams['date_filter_type']) && $searchParams['date_filter_type'] == 'print_insert_date')) {
            $isPrintPublishedFlag = true;
        }
        $qb = $this->addFilter($qb, $searchParams, $sorter, $container, $isPrintPublishedFlag, $isCountQuery, true);

        return $qb->getQuery();
    }

    /**
     * Get ad report query
     *
     * @param array   $adIds        Ad ids array.
     * @param array   $searchParams Search parameters array.
     * @param array   $sorter       Sort parameters array.
     * @param object  $container    Container object.
     *
     * @return array
     */
    public function getAdPrintInsertDatesByAdIds($adIds, $searchParams, $sorter, $container)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
            ->select(self::ALIAS.'.id', self::ALIAS.'.ad_id', AdPrintInsertDateReportDailyRepository::ALIAS.'.print_insert_date', AdPrintInsertDateReportDailyRepository::ALIAS.'.print_edition_id')
            ->andWhere(self::ALIAS.'.ad_id IN (:adIds)')
            ->orderBy(AdPrintInsertDateReportDailyRepository::ALIAS.'.print_insert_date', 'ASC')
            ->setParameter('adIds', $adIds);

        $qb = $this->addFilter($qb, $searchParams, $sorter, $container, true);

        $adPrintDatesArr = array();
        $adPrintEditionArr = array();
        $adPrintDates    = $qb->getQuery()->getArrayResult();

        foreach ($adPrintDates as $adPrintDate) {
            if (!isset($adPrintDatesArr[$adPrintDate['ad_id']][$adPrintDate['id']]) || !in_array($adPrintDate['print_insert_date'], $adPrintDatesArr[$adPrintDate['ad_id']][$adPrintDate['id']])) {
                $adPrintDatesArr[$adPrintDate['ad_id']][$adPrintDate['id']][] = $adPrintDate['print_insert_date'];
            }

            if (!isset($adPrintEditionArr[$adPrintDate['ad_id']][$adPrintDate['id']]) || !in_array($adPrintDate['print_edition_id'], $adPrintEditionArr[$adPrintDate['ad_id']][$adPrintDate['id']])) {
                $adPrintEditionArr[$adPrintDate['ad_id']][$adPrintDate['id']][$adPrintDate['print_insert_date']][] = $adPrintDate['print_edition_id'];
            }
        }

        return array($adPrintDatesArr, $adPrintEditionArr);
    }

    /**
     *
     * @param object  $qb                            QueryBuilder object.
     * @param array   $searchParams                  Search parameters array.
     * @param array   $sorter                        Sort parameters array.
     * @param object  $container                     Container object.
     * @param boolean $isPrintPublishedFlag          Is print published flag.
     * @param boolean $isCountQuery                  Is count query.
     * @param boolean $isPrintPublishedGroupByAdFlag Group by ad id.
     *
     * @return QueryBuilder
     */
    private function addFilter($qb, $searchParams, $sorter, $container, $isPrintPublishedFlag = false, $isCountQuery = false, $isPrintPublishedGroupByAdFlag = false)
    {
        $qb->leftJoin('FaReportBundle:AdPrintInsertDateReportDaily', AdPrintInsertDateReportDailyRepository::ALIAS, 'WITH', AdPrintInsertDateReportDailyRepository::ALIAS.'.ad_report_daily_id = '.self::ALIAS.'.id');

        // filter for admin user email.
        if (isset($searchParams['admin_user_email']) && $searchParams['admin_user_email']) {
            $qb->andWhere(self::ALIAS.'.admin_user_email = :admin_user_email')
                ->setParameter('admin_user_email', $searchParams['admin_user_email']);
        }
        // filter for paid ads only.
        if (isset($searchParams['paid_ads']) && $searchParams['paid_ads']) {
            $qb->andWhere(self::ALIAS.'.total_revenue_gross > 0');
        }
        // filter for admin ads only.
        if (isset($searchParams['admin_ads']) && $searchParams['admin_ads']) {
            $qb->andWhere(self::ALIAS.'.source_latest = :admin_source')
            ->setParameter('admin_source', AdRepository::SOURCE_ADMIN);
        }

        // filter for paa-lite ads only.
        if (isset($searchParams['is_paa_lite']) && $searchParams['is_paa_lite']) {
            $qb->andWhere(self::ALIAS.".source = 'paa_lite'");
        }

        $adReportSortFields = self::getAdReportSortFields();

        if (isset($searchParams['from_date'])) {
            $finalStartDate = CommonManager::getTimeStampFromStartDate($searchParams['from_date']);
        }

        if (isset($searchParams['to_date'])) {
            $finalEndDate = CommonManager::getTimeStampFromEndDate($searchParams['to_date']);
        }

        if ($isPrintPublishedFlag) {
            $qb->andWhere('('.AdPrintInsertDateReportDailyRepository::ALIAS.'.print_insert_date BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
            $printPackageArray = CommonManager::getEntityRepository($container, 'FaPromotionBundle:Package')->getPrintPackagesArray();
            $qb->andWhere(self::ALIAS.'.package_id in ('.implode(',', $printPackageArray).')');
        } elseif (isset($searchParams['date_filter_type'])) {
            $qb->andWhere('('.self::ALIAS.'.'.$searchParams['date_filter_type'].' BETWEEN '.$finalStartDate.' AND  '.$finalEndDate.')');
        }

        // category filter.
        if (isset($searchParams['category_id']) && $searchParams['category_id']) {
            $nestedChildren = CommonManager::getEntityRepository($container, 'FaEntityBundle:Category')->getNestedChildrenIdsByCategoryId($searchParams['category_id']);
            $qb->andWhere(self::ALIAS.'.category_id IN (:categoryId)')
            ->setParameter('categoryId', $nestedChildren);
        }

        // role filter
        if (isset($searchParams['role_id']) && $searchParams['role_id']) {
            $qb->andWhere(self::ALIAS.'.role_id = (:roleId)')
            ->setParameter('roleId', $searchParams['role_id']);
        }

        // ad ref filter.
        if (isset($searchParams['ad_id']) && $searchParams['ad_id']) {
            $qb->andWhere(self::ALIAS.'.ad_id = :adId')
            ->setParameter('adId', $searchParams['ad_id']);
        }

        // ti ad ref filter.
        if (isset($searchParams['ti_ad_id']) && $searchParams['ti_ad_id']) {
            $qb->andWhere(self::ALIAS.'.ti_ad_id = :ti_ad_id')
            ->setParameter('ti_ad_id', $searchParams['ti_ad_id']);
        }

        // location filter.
        if (isset($searchParams['town_id']) && $searchParams['town_id']) {
            $qb->andWhere(self::ALIAS.'.town_id IN (:townId)')
            ->setParameter('townId', $searchParams['town_id']);
        } elseif (isset($searchParams['county_id']) && $searchParams['county_id']) {
            $qb->andWhere(self::ALIAS.'.county_id = (:countyId)')
            ->setParameter('countyId', $searchParams['county_id']);
        }

        // print edition filter.
        if (isset($searchParams['print_edition_id']) && $searchParams['print_edition_id']) {
            /*$pattern = '^('.$searchParams['print_edition_id'].')$|(^'.$searchParams['print_edition_id'].',)|(,'.$searchParams['print_edition_id'].'$)|(,'.$searchParams['print_edition_id'].',)';
            $qb->andWhere("regexp(".self::ALIAS.".print_edition_ids, '".$pattern."') != false");*/
            $qb->andWhere(AdPrintInsertDateReportDailyRepository::ALIAS.'.print_edition_id = '.$searchParams['print_edition_id']);
        }

        // sorting.
        if ($sorter['sort_field'] == 'ad_report_daily__id') {
            $sorter['sort_field'] = 'id';
        }
        if (in_array($sorter['sort_field'], $adReportSortFields) && isset($sorter['sort_field']) && $sorter['sort_field'] && isset($sorter['sort_ord']) && $sorter['sort_ord']) {
            $qb->orderBy(self::ALIAS.'.'.$sorter['sort_field'], $sorter['sort_ord']);
        }

        return $qb;
    }

    /**
     * Get ad report fields
     */
    public static function getAdReportFields()
    {
        $adReportFields = array();
        //$adReportFields['id'] = 'Unique bit';
        //$adReportFields['total_ads'] = 'Total ads';
        $adReportFields['is_paa_lite'] = 'Is Paa Lite';
        $adReportFields['ad_id'] = 'Original adref';
        $adReportFields['ti_ad_id'] = 'Old Trade-It adref';
        $adReportFields['ad_created_at'] = 'DateTime stamp ad placed';
        $adReportFields['print_insert_date'] = 'Published print date';
        $adReportFields['published_at'] = 'Published online date';
        $adReportFields['is_edit'] = 'Edit';
        $adReportFields['is_renewed'] = 'Renewal';
        $adReportFields['is_expired'] = 'Expired';
        $adReportFields['expires_at'] = 'Expected expiry date';
        $adReportFields['expired_at'] = 'Expired date';
        $adReportFields['status_id'] = 'Ad Status';
        $adReportFields['category_1'] = 'Category';
        $adReportFields['category_2'] = 'Class';
        $adReportFields['category_3'] = 'Subclass';
        $adReportFields['category_4'] = 'Subsubclass';
        $adReportFields['postcode'] = 'Postcode';
        $adReportFields['town_id'] = 'Town';
        $adReportFields['county_id'] = 'County';
        $adReportFields['print_edition_ids'] = 'Edition';
        $adReportFields['source'] = 'Source original';
        $adReportFields['source_latest'] = 'Source latest';
        $adReportFields['role_id'] = 'User type';
        $adReportFields['no_of_photos'] = 'Number of photos per advert';
        $adReportFields['total_revenue_gross'] = 'Total revenue gross';
        $adReportFields['print_revenue_gross'] = 'Print revenue gross';
        $adReportFields['online_revenue_gross'] = 'Online revenue gross';
        $adReportFields['total_revenue_net'] = 'Total revenue net';
        $adReportFields['print_revenue_net'] = 'Print revenue net';
        $adReportFields['online_revenue_net'] = 'Online revenue net';
        $adReportFields['package_name'] = 'Packages';
        $adReportFields['duration_print'] = 'Duration print';
        $adReportFields['duration_online'] = 'Duration online';
        $adReportFields['shop_package_name'] = 'Shop package ';
        $adReportFields['shop_package_revenue'] = 'Shop package revenue';
        $adReportFields['published_print_revenue_gross'] = 'Published print revenue gross';
        $adReportFields['published_print_revenue_net'] = 'Published print revenue net';
        $adReportFields['renewed_at'] = 'Renewal date';
        $adReportFields['edited_at'] = 'Edit date';
        $adReportFields['admin_user_email'] = 'Admin user';
        $adReportFields['payment_method'] = 'Payment source';
        $adReportFields['ad_price'] = 'Ad price';
        $adReportFields['is_discount_code_used'] = 'Discount code used?';
        $adReportFields['phones'] = 'Phones';
        $adReportFields['is_credit_used'] = 'Credit used?';
        $adReportFields['ip_addresses'] = 'Ip Address';
        asort($adReportFields);

        return $adReportFields;
    }

    /**
     * Get ad report sort fields
     */
    public static function getAdReportSortFields()
    {
        $adReportSortFields   = array();
        $adReportSortFields[] = 'id';
        $adReportSortFields[] = 'ad_id';
        $adReportSortFields[] = 'ad_created_at';
        $adReportSortFields[] = 'print_insert_date';
        $adReportSortFields[] = 'published_at';
        $adReportSortFields[] = 'no_of_photos';
        $adReportSortFields[] = 'total_revenue_gross';
        $adReportSortFields[] = 'print_revenue_gross';
        $adReportSortFields[] = 'online_revenue_gross';
        $adReportSortFields[] = 'total_revenue_net';
        $adReportSortFields[] = 'print_revenue_net';
        $adReportSortFields[] = 'online_revenue_net';
        $adReportSortFields[] = 'shop_package_revenue';

        return $adReportSortFields;
    }

    /**
     * Format ad report fields.
     *
     * @param array   $adReportDetailArray Ad report detail array.
     * @param object  $container           Container object.
     * @param integer $uniqueBit           Unique bit.
     */
    public function formatAdReportRaw($adReportDetailArray, $container, $uniqueBit = null)
    {
        $fieldValueArray = array();
        $dateFields      = array('ad_created_at', 'published_at', 'expires_at', 'expired_at', 'renewed_at', 'edited_at');
        $booleanFields   = array('is_edit', 'is_renewed', 'is_expired');
        $yesNoFields   = array('is_discount_code_used', 'is_credit_used');
        $entityFields    = array('status_id', 'category_id', 'town_id', 'county_id', 'print_edition_ids', 'role_id');
        $priceFields     = array('total_revenue_gross', 'total_revenue_net', 'print_revenue_gross', 'print_revenue_net', 'online_revenue_gross', 'online_revenue_net', 'shop_package_revenue', 'ad_price');
        $ucFirstFields   = array('payment_method');

        foreach ($adReportDetailArray as $key => $value) {
            if ($key == 'id') {
                $fieldValueArray[$key] = $uniqueBit;
            } else {
                if (is_bool($value) && in_array($key, $booleanFields)) {
                    if ($value) {
                        $fieldValueArray[$key] = 1;
                    } else {
                        $fieldValueArray[$key] = 0;
                    }
                } elseif (in_array($key, $yesNoFields)) {
                    if ($value) {
                        $fieldValueArray[$key] = 'Yes';
                    } else {
                        $fieldValueArray[$key] = 'No';
                    }
                } elseif (in_array($key, $priceFields)) {
                    $fieldValueArray[$key] = CommonManager::formatCurrency($value, $container);
                } elseif (in_array($key, $ucFirstFields)) {
                    $fieldValueArray[$key] = ucfirst($value);
                    if ($value == PaymentRepository::PAYMENT_METHOD_FREE && $adReportDetailArray['skip_payment_reason']) {
                        $fieldValueArray[$key] = $adReportDetailArray['skip_payment_reason'];
                    }
                } elseif (in_array($key, $entityFields)) {
                    $entityCacheManager = $container->get('fa.entity.cache.manager');
                    switch ($key) {
                        case 'category_id':
                            $categoryPath = CommonManager::getEntityRepository($container, 'FaEntityBundle:Category')->getCategoryPathArrayById($value, false, $container);
                            if (is_array($categoryPath)) {
                                $catCounter       = 1;
                                foreach ($categoryPath as $catKey => $catValue) {
                                    $fieldValueArray['category_'.$catCounter] = $catValue;
                                    $catCounter++;
                                }
                            }
                            break;
                        case 'status_id':
                            $fieldValueArray[$key] = $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $value);
                            break;
                        case 'county_id':
                        case 'town_id':
                            $fieldValueArray[$key] = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $value);
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
        }

        return $fieldValueArray;
    }

    /**
     * Get ad report id by ad ids
     *
     * @param array $adId Ad id array.
     *
     * @return array
     */
    public function getAdReportIdsByAdIds($adId = array())
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', self::ALIAS.'.ad_id');

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $qb->andWhere(self::ALIAS.'.ad_id IN (:adId)')
            ->setParameter('adId', $adId);
        }

        $adReportIds    = $qb->getQuery()->getArrayResult();
        $adReportIdsArr = array();
        if (count($adReportIds)) {
            foreach ($adReportIds as $adReportId) {
                $adReportIdsArr[$adReportId['ad_id']] = $adReportId['id'];
            }
        }

        return $adReportIdsArr;
    }
}
