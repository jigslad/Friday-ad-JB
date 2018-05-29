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

use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\AdBundle\Entity\AdUserPackage;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentTransactionRepository;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdUserPackageRepository extends BaseEntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'aup';

    const STATUS_INACTIVE = 2;
    const STATUS_ACTIVE   = 1;
    const STATUS_EXPIRED  = 5;
    const STATUS_FRONTEND_DISABLED = 6;

    /**
     * prepareQueryBuilder.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Set ad user package.
     *
     * @param array   $data              Package value array.
     * @param boolean $addAdToModeration Send ad to moderate or not.
     * @param boolean $batchUpdate       if call through batch update
     * @param boolean $futureAdPostFlag  Future advert post flag.
     *
     * @return integer
     */
    public function setAdUserPackage($data = array(), $addAdToModeration = true, $batchUpdate = false, $futureAdPostFlag = false)
    {
        if (count($data) > 0) {
            $adUserPackage = new AdUserPackage();

            // find & set package
            $package = $this->_em->getRepository('FaPromotionBundle:Package')->find($data['id']);
            $adUserPackage->setPackage($package);

            // find & set ad
            if (isset($data['ad_id'])) {
                $ad = $this->_em->getRepository('FaAdBundle:AdMain')->find($data['ad_id']);
                $adUserPackage->setAdMain($ad);
                $adUserPackage->setAdId($data['ad_id']);
                if ($addAdToModeration || $futureAdPostFlag) {
                    $adUserPackage->setStatus(self::STATUS_INACTIVE);
                } else {
                    $adUserPackage->setStatus(self::STATUS_ACTIVE);
                    $adUserPackage->setStartedAt(time());
                    if ($package->getDuration()) {
                        $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration()));
                    } elseif (isset($data['ad_id'])) {
                        $adObj = $this->_em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $data['ad_id']));
                        $expirationDays = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($adObj->getCategory()->getId());
                        $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
                    }
                }
            }

            // find & set user
            if (isset($data['user_id'])) {
                $packageValue['package'][$data['id']] = $data;
                $user = $this->_em->getRepository('FaUserBundle:User')->find($data['user_id']);
                if ($user) {
                    $adUserPackage->setUser($user);
                }
                /*$assignAdUserPackage = new AdUserPackage();
                $assignAdUserPackage->setPackage($package);
                $assignAdUserPackage->setUser($user);
                $assignAdUserPackage->setStatus(self::STATUS_ACTIVE);
                $assignAdUserPackage->setStartedAt(time());
                $assignAdUserPackage->setPrice(0);
                $assignAdUserPackage->setDuration($data['duration']);
                $assignAdUserPackage->setValue(serialize($packageValue));

                $this->_em->persist($assignAdUserPackage);
                $this->_em->flush();*/
            }

            $upsellValue = array();
            if (isset($data['ad_expiry_days'])) {
                $upsellValue['ad_expiry_days'] = $data['ad_expiry_days'];
            }
            if (isset($data['packagePrint'])) {
                $upsellValue['packagePrint'] = $data['packagePrint'];
            }

            if (isset($data['is_admin_price'])) {
                $upsellValue['is_admin_price'] = $data['is_admin_price'];
            }
            if (isset($data['printEditions'])) {
                $upsellValue['printEditions'] = $data['printEditions'];
            }


            $adUserPackage->setValue(serialize($upsellValue));
            $adUserPackage->setPrice($data['price']);
            $adUserPackage->setDuration($data['duration']);

            $this->_em->persist($adUserPackage);

            if ($batchUpdate == false) {
                $this->_em->flush();
            }

            return $adUserPackage->getId();

        }
    }

    /**
     * Enable ad user package.
     *
     * @param integer $adId Ad Id.
     *
     * @return integer
     */
    public function enableAdUserPackage($adId)
    {
        if ($this->getActiveAdPackage($adId)) {
            return null;
        }
        $adUserPackage = $this->findCurrentInactivePackage($adId);

        if ($adUserPackage) {
            $adUserPackage->setStatus(self::STATUS_ACTIVE);
            $adUserPackage->setStartedAt(time());
            if ($adUserPackage->getDuration()) {
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($adUserPackage->getDuration()));
            } else {
                $ad = $this->_em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId));
                $expirationDays = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            $this->_em->persist($adUserPackage);
            $this->_em->flush($adUserPackage);
            return $adUserPackage->getId();
        }

        return null;
    }

    /**
     * Find current inactive package.
     *
     * @param integer $adId Ad Id.
     *
     * @return integer
     */
    public function findCurrentInactivePackage($adId)
    {
        $adUserPackage = $this->findOneBy(array('ad_id' => $adId, 'status' => self::STATUS_INACTIVE), array('id' => 'DESC'));
        if ($adUserPackage) {
            return $adUserPackage;
        }

        $adUserPackage = $this->findOneBy(array('ad_id' => $adId, 'status' => self::STATUS_FRONTEND_DISABLED), array('id' => 'DESC'));
        if ($adUserPackage) {
            return $adUserPackage;
        }

        return null;
    }

    /**
     * Find current active/inactive package.
     *
     * @param integer $adId Ad Id.
     *
     * @return mixed
     */
    public function getPurchasedAdPackage($adId)
    {
        $query = $this->getBaseQueryBuilder()
            ->andWhere(self::ALIAS.'.ad_id = '.$adId)
            ->andWhere(self::ALIAS.'.status = '.self::STATUS_ACTIVE.' OR '.self::ALIAS.'.status = '.self::STATUS_INACTIVE)
            ->orderBy(self::ALIAS.'.id', 'desc')
            ->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Find current active package.
     *
     * @param integer $adId Ad Id.
     *
     * @return mixed
     */
    public function getActiveAdPackage($adId)
    {
        $query = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.ad_id = '.$adId)
        ->andWhere(self::ALIAS.'.status = '.self::STATUS_ACTIVE)
        ->orderBy(self::ALIAS.'.id', 'desc')
        ->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Get package for ad id.
     *
     * @param array   $adId              Ad id array.
     * @param boolean $getTiPackageTitle Get ti package name flag.
     *
     * @return array
     */
    public function getAdPackageArrayByAdId($adId = array(), $getTiPackageTitle = false)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', PackageRepository::ALIAS.'.id as package_id', PackageRepository::ALIAS.'.title', self::ALIAS.'.price', self::ALIAS.'.ad_id', self::ALIAS.'.ti_package', PackageRepository::ALIAS.'.package_text')
        ->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
        ->andWhere(self::ALIAS.'.status = '.self::STATUS_ACTIVE);

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $qb->andWhere(self::ALIAS.'.ad_id IN (:adId)');
            $qb->setParameter('adId', $adId);
        }

        $adPackages   = $qb->getQuery()->getArrayResult();
        $adPackageArr = array();
        if (count($adPackages)) {
            foreach ($adPackages as $adPackage) {
                if ($getTiPackageTitle) {
                    $adPackageArr[$adPackage['ad_id']]['title'] = ($adPackage['ti_package'] ? $adPackage['ti_package'] : $adPackage['title']);
                } else {
                    $adPackageArr[$adPackage['ad_id']]['title'] = $adPackage['title'];
                }

                $adPackageArr[$adPackage['ad_id']]['price'] = $adPackage['price'];
                $adPackageArr[$adPackage['ad_id']]['package_id'] = $adPackage['package_id'];
                $adPackageArr[$adPackage['ad_id']]['package_text'] = $adPackage['package_text'];
            }
        }

        return $adPackageArr;
    }

    /**
     * Get email template name by id.
     *
     * @param integer $id Ad user package id.
     *
     * @return mixed
     */
    public function getEmailTemplateIdByAdUserPackageId($id)
    {
        $adUserPackage = $this->find($id);

        if ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getEmailTemplate()) {
            return $adUserPackage->getPackage()->getEmailTemplate()->getIdentifier();
        }

        return null;
    }

    /**
     * Get email template name by id.
     *
     * @param integer $id Ad user package id.
     *
     * @return mixed
     */
    public function getPackageIdByAdUserPackageId($id)
    {
        $adUserPackage = $this->find($id);

        if ($adUserPackage && $adUserPackage->getPackage()) {
            return $adUserPackage->getPackage()->getId();
        }

        return null;
    }

    /**
     * Get package for ad id for ad report daily.
     *
     * @param array  $adId       Ad id array.
     * @param string $reportType Report type.
     *
     * @return array
     */
    public function getAdPackageArrayByAdIdForAdReportDaily($adId = array(), $reportType = "ad")
    {
        /*
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select('IDENTITY('.self::ALIAS.'.package) as package_id', self::ALIAS.'.ad_id', self::ALIAS.'.started_at', self::ALIAS.'.value', PackageRepository::ALIAS.'.package_sr_no', self::ALIAS.'.price', PackageRepository::ALIAS.'.package_text')
        ->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS);
        if ($reportType != 'ad') {
            $qb->andWhere(self::ALIAS.'.status = '.self::STATUS_ACTIVE);
        }

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $qb->andWhere(self::ALIAS.'.ad_id IN (:adId)');
            $qb->setParameter('adId', $adId);
        }*/

        $adUserPackageTableName = $this->_em->getClassMetadata('FaAdBundle:AdUserPackage')->getTableName();
        $packageTableName = $this->_em->getClassMetadata('FaPromotionBundle:Package')->getTableName();

        $sql ='SELECT '.self::ALIAS.'.package_id,'.self::ALIAS.'.ad_id,'.self::ALIAS.'.started_at,'.self::ALIAS.'.value,'.self::ALIAS.'.price,'.PackageRepository::ALIAS.'.package_sr_no, '.PackageRepository::ALIAS.'.package_text FROM '.$adUserPackageTableName.' as '.self::ALIAS.'
            JOIN (SELECT ad_id, MAX(id) max_id FROM '.$adUserPackageTableName.' GROUP BY ad_id) '.self::ALIAS.'1 ON ('.self::ALIAS.'.id = '.self::ALIAS.'1.max_id)
            LEFT JOIN '.$packageTableName.' as '.PackageRepository::ALIAS.' ON ('.self::ALIAS.'.package_id = '.PackageRepository::ALIAS.'.id)
            Where 1=1';

        if ($reportType != 'ad') {
            $sql .= ' AND '.self::ALIAS.'.status = '.self::STATUS_ACTIVE;
        }

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $sql .= ' AND '.self::ALIAS.'.ad_id IN ('.implode(',', $adId).')';
        }
        $sql .= ';';

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();
        $adPackages = $stmt->fetchAll();

        $adPackageArr = array();
        if (count($adPackages)) {
            foreach ($adPackages as $adPackage) {
                $adPackageArr[$adPackage['ad_id']] = array(
                    'package_id' => $adPackage['package_id'],
                    'package_sr_no' => $adPackage['package_sr_no'],
                    'package_text' => $adPackage['package_text'],
                    'price' => $adPackage['price'],
                    'value' => $adPackage['value'],
                    'started_at' => $adPackage['started_at'],
                );
            }
        }

        return $adPackageArr;
    }

    /**
     * Add ad package filter to existing query object.
     *
     * @param integer $packageId Package id.
     */
    protected function addPackageFilter($packageId = null)
    {
        if ($packageId) {
            if (!is_array($packageId)) {
                $packageId = array($packageId);
            }

            $packageId = array_filter($packageId);

            if (count($packageId)) {
                $this->queryBuilder->andWhere($this->getRepositoryAlias().'.package IN (:'.$this->getRepositoryAlias().'_package'.')');
                $this->queryBuilder->setParameter($this->getRepositoryAlias().'_package', $packageId);
            }
        }
    }

    /**
     * Get last print packages for ads
     *
     * @param unknown $adIds
     *
     * @return array
     */
    public function getLastPrintPackagesForAdIds($adIds)
    {
        $adIds = array_unique($adIds);
        $adUserPackageTableName   = $this->_em->getClassMetadata('FaAdBundle:AdUserPackage')->getTableName();
        $adUserPackageUpsellTableName   = $this->_em->getClassMetadata('FaAdBundle:AdUserPackageUpsell')->getTableName();
        $upsellTableName   = $this->_em->getClassMetadata('FaPromotionBundle:Upsell')->getTableName();
        $packageTableName   = $this->_em->getClassMetadata('FaPromotionBundle:Package')->getTableName();

        $sql ='SELECT MAX( '.AdUserPackageRepository::ALIAS.'.id ), '.AdUserPackageRepository::ALIAS.'.ad_id ,
                (SELECT value FROM '.$adUserPackageTableName.' AS '.AdUserPackageRepository::ALIAS.'1
                    WHERE  id = MAX( '.AdUserPackageRepository::ALIAS.'.id ) LIMIT 1
                    ) as ad_user_package_value,
                (SELECT '.AdUserPackageUpsellRepository::ALIAS.'.value FROM '.$adUserPackageUpsellTableName.' as '.AdUserPackageUpsellRepository::ALIAS.'
                    WHERE  '.AdUserPackageUpsellRepository::ALIAS.'.upsell_id
                        IN (
                            SELECT id
                            FROM '.$upsellTableName.'
                                WHERE TYPE = '.UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID.'
                           ) AND '.AdUserPackageUpsellRepository::ALIAS.'.ad_user_package_id = MAX( '.AdUserPackageRepository::ALIAS.'.id )  LIMIT 1
                    ) AS total_print_editions
        FROM '.$adUserPackageTableName.' AS '.AdUserPackageRepository::ALIAS.'
            WHERE '.AdUserPackageRepository::ALIAS.'.package_id
            IN (
                SELECT id
                FROM '.$packageTableName.' as '.PackageRepository::ALIAS.'
                INNER JOIN package_upsell as pu ON ( '.PackageRepository::ALIAS.'.id = pu.package_id )
                AND pu.upsell_id
                IN (
                    SELECT id
                    FROM '.$upsellTableName.'
                    WHERE TYPE = '.UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID.'
                )
            )
            AND '.AdUserPackageRepository::ALIAS.'.ad_id
            IN ('.implode(',', $adIds).')
            GROUP BY '.AdUserPackageRepository::ALIAS.'.ad_id';

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();
        $printPackages = $stmt->fetchAll();

        $adPrintdurations = array();
        foreach ($printPackages as $printPackage) {
            $packageValues = unserialize($printPackage['ad_user_package_value']);

            $adPrintdurations[$printPackage['ad_id']] = array(
                                                            'total_print_editions' => $printPackage['total_print_editions'],
                                                            'print_durations' => ((isset($packageValues['packagePrint']) && isset($packageValues['packagePrint']['duration'])) ? (int) $packageValues['packagePrint']['duration'] : 0)
                                                        );
        }

        return $adPrintdurations;
    }

    /**
     * Find last package.
     *
     * @param integer $adId Ad Id.
     *
     * @return mixed
     */
    public function getLastAdPackage($adId)
    {
        $query = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.ad_id = '.$adId)
        ->orderBy(self::ALIAS.'.id', 'desc')
        ->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Get package for ad id.
     *
     * @param array $adId Ad id array.
     *
     * @return array
     */
    public function getAdActivePackageArrayByAdId($adId = array())
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', 'IDENTITY('.self::ALIAS.'.package) as package_id', self::ALIAS.'.ad_id', self::ALIAS.'.price')
        ->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
        ->andWhere(self::ALIAS.'.status = '.self::STATUS_ACTIVE);

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $qb->andWhere(self::ALIAS.'.ad_id IN (:adId)');
            $qb->setParameter('adId', $adId);
        }

        $adPackages   = $qb->getQuery()->getArrayResult();
        $adPackageArr = array();
        if (count($adPackages)) {
            foreach ($adPackages as $adPackage) {
                $packageAarray = array('package_id' => $adPackage['package_id'], 'package_price' => $adPackage['price']);
                $adPackageArr[$adPackage['ad_id']] = $packageAarray;
            }
        }

        return $adPackageArr;
    }

    /**
     * Get package for ad id.
     *
     * @param array   $adId              Ad id array.
     *
     * @return array
     */
    public function getAdPackagesAndPriceSum($adId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', PackageRepository::ALIAS.'.id as package_id', PackageRepository::ALIAS.'.title', self::ALIAS.'.price', self::ALIAS.'.ad_id', PackageRepository::ALIAS.'.package_text')
        ->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
        ->andWhere(self::ALIAS.'.ad_id IN (:adId)')
        ->setParameter('adId', $adId);

        $adPackages   = $qb->getQuery()->getArrayResult();
        $adPackageArr = array('package_text' => '', 'price_sum' => '');
        if (count($adPackages)) {
            foreach ($adPackages as $adPackage) {
                if (isset($adPackage['package_text'])) {
                    $adPackageArr['package_text'] = $adPackageArr['package_text'].', '.$adPackage['package_text'];
                } else {
                    $adPackageArr['package_text'] = $adPackageArr['package_text'].', '.$adPackage['title'];
                }
                $adPackageArr['price_sum'] = $adPackageArr['price_sum'] + $adPackage['price'];
            }
            $adPackageArr['package_text'] = trim($adPackageArr['package_text'], ', ');
        }

        return $adPackageArr;
    }

    /**
     * Get advert package purchases for police report.
     *
     * @param integer $adId ad id integer.
     *
     * @return array
     */
    public function getAdvertPackagePurchases($adId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', PackageRepository::ALIAS.'.title', PaymentRepository::ALIAS.'1.cart_code', PaymentTransactionRepository::ALIAS.'.amount', self::ALIAS.'.created_at')
        ->innerJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
        ->innerJoin('FaPaymentBundle:PaymentTransaction', PaymentTransactionRepository::ALIAS, 'WITH', self::ALIAS.'.ad_id = '.PaymentTransactionRepository::ALIAS.'.ad')
        ->innerJoin(PaymentTransactionRepository::ALIAS.'.payment', PaymentRepository::ALIAS.'1')
        ->where(self::ALIAS.'.ad_id = :adId')
        ->setParameter('adId', $adId)
        ->groupBy(self::ALIAS.'.id')
        ->orderBy(self::ALIAS.'.created_at');

        $result = $qb->getQuery()->getResult();

        return $result;
    }
    
}
