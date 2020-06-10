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
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdUserPackageUpsellRepository extends EntityRepository
{
    const ALIAS = 'aupu';

    const STATUS_EXPIRED  = 5;
    const STATUS_FRONTEND_DISABLED = 6;

    /**
     * Set ad user package upsells.
     *
     * @param array   $data              Package value array.
     * @param string  $adUserPackageId   Ad user package id.
     * @param boolean $addAdToModeration Send ad to moderate or not.
     * @param boolean $batchUpdate       if call from batch update
     * @param boolean $futureAdPostFlag  Future advert post flag.
     *
     * @return integer
     */
    public function setAdUserPackageUpsell($data = array(), $adUserPackageId = null, $addAdToModeration = true, $batchUpdate = false, $futureAdPostFlag = false)
    {
        if (count($data) > 0) {
            $ad = null;
            $adUserPackageUpsell = new AdUserPackageUpsell();

            // find & set upsell
            $upsell = $this->_em->getRepository('FaPromotionBundle:Upsell')->find($data['id']);
            $adUserPackageUpsell->setUpsell($upsell);

            // find & set ad user package id.
            if ($adUserPackageId) {
                $adUserPackage = $this->_em->getRepository('FaAdBundle:AdUserPackage')->find($adUserPackageId);
                $adUserPackageUpsell->setAdUserPackage($adUserPackage);
            }

            // find & set ad
            if (isset($data['ad_id'])) {
                $ad = $this->_em->getRepository('FaAdBundle:AdMain')->find($data['ad_id']);
                $adUserPackageUpsell->setAdMain($ad);
                $adUserPackageUpsell->setAdId($data['ad_id']);
            }

            $adUserPackageUpsell->setValue($data['value']);
            $adUserPackageUpsell->setValue1($data['value1']);
            $adUserPackageUpsell->setDuration($data['duration']);
            if ($addAdToModeration || $futureAdPostFlag) {
                $adUserPackageUpsell->setStatus(0);
            } else {
                $adUserPackageUpsell->setStatus(1);
                $adUserPackageUpsell->setStartedAt(time());
                if ($upsell->getDuration()) {
                    $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($upsell->getDuration()));
                } elseif (isset($data['ad_id'])) {
                    $adObj = $this->_em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $data['ad_id']));
                    $expirationDays = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($adObj->getCategory()->getId());
                    $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
                }
            }

            $this->_em->persist($adUserPackageUpsell);

            if ($batchUpdate == false) {
                $this->_em->flush();
            }

            return $adUserPackageUpsell->getId();
        }
    }
    
    
    /**
     * Set ad user package upsells.
     *
     * @param array   $data              Package value array.
     * @param string  $upsellId          Upsell id.
     * @param string  $ad                Advert.
     *
     * @return integer
     */
    public function setAdUserIndividualUpsell($data = array(), $ad = array())
    {
        if (!empty($data)) {
            
            $adUserPackageUpsell = new AdUserPackageUpsell();
            
            // find & set upsell
            $upsell = $this->_em->getRepository('FaPromotionBundle:Upsell')->find($data->getId());
            $adUserPackageUpsell->setUpsell($upsell);
            
            // find & set ad
            if (!empty($ad)) {
                //$ad = $this->_em->getRepository('FaAdBundle:AdMain')->find($data['ad_id']);
                $adUserPackageUpsell->setAdMain($ad->getAdMain());
                $adUserPackageUpsell->setAdId($ad->getId());
            }
            
            $adUserPackageUpsell->setValue($data->getValue());
            $adUserPackageUpsell->setDuration($data->getDuration());
            $adUserPackageUpsell->setStatus(1);
            $adUserPackageUpsell->setStartedAt(time());
            
            if ($upsell->getDuration()) {
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($upsell->getDuration()));
            } 
            
            $this->_em->persist($adUserPackageUpsell);
            $this->_em->flush();
            
            
            return true;
        }
    }
    

    /**
     * Enable ad user package upsell.
     *
     * @param integer $adUserPackageId Ad user package id.
     *
     * @return integer
     */
    public function enableAdUserPackageUpsell($adUserPackageId)
    {
        $adUserPackageUpsells = $this->findUpsellByPackage($adUserPackageId);

        if ($adUserPackageUpsells) {
            foreach ($adUserPackageUpsells as $adUserPackageUpsell) {
                $adUserPackageUpsell->setStatus(1);
                $adUserPackageUpsell->setStartedAt(time());
                if ($adUserPackageUpsell->getDuration()) {
                    $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($adUserPackageUpsell->getDuration()));
                } else {
                    $ad = $this->_em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adUserPackageUpsell->getAdId()));
                    $expirationDays = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                    $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
                }

                $this->_em->persist($adUserPackageUpsell);
            }

            $this->_em->flush();

            return true;
        }

        return false;
    }
    
    /**
     * Find ad package upsells.
     *
     * @param integer $adId            Ad id.
     * @param mixed   $adUserPackageId Ad user package id.
     *
     * @return array
     */
    public function getFeaturedUpsellById($adId, $upsellId)
    {
        $res = array();
        $query = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS)
        ->andWhere(self::ALIAS.'.ad_id = :adId')
        ->setParameter('adId', $adId)
        ->andWhere(self::ALIAS.'.upsell = :upsellId')
        ->setParameter('upsellId', $upsellId)
        ->andWhere(self::ALIAS.'.status = 1')
        ->orderBy(self::ALIAS.'.id', 'desc');

        $res = $query->getQuery()->getResult();
        return $res;
    }
    
    /**
     * Enable ad user package upsell.
     *
     * @param integer $adId Ad id.
     *
     * @return integer
     */
    public function disableFeaturedAdUpsell($adId,$upsellId)
    {
        $adUserPackageUpsells = array();
        
        $adUserPackageUpsells = $this->getFeaturedUpsellById($adId, $upsellId);

        if(!empty($adUserPackageUpsells)) {
            $adUserPackageUpsell = $adUserPackageUpsells[0];
            $adUserPackageUpsell->setStatus(self::STATUS_EXPIRED);
            $this->_em->persist($adUserPackageUpsell);
            $this->_em->flush();        
            return true; 
        } else { return false; }
    }

    public function forceExpireAdPackageUpsell($adId)
    {
        $adUserPackageUpsells = array();

        $adUserPackageUpsells = $this->_em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'status' => 1))

        if (!empty($adUserPackageUpsells)) {
            foreach ($adUserPackageUpsells as $adUserPackageUpsell) {
                $adUserPackageUpsell->setStatus(self::STATUS_EXPIRED);
                $this->_em->persist($adUserPackageUpsell);
                $this->_em->flush();
            }
            return true;
        } else { return false; }
    }

    /**
     * Find upsell by package.
     *
     * @param integer $adUserPackageId Ad user package id.
     *
     * @return array
     */
    public function findUpsellByPackage($adUserPackageId)
    {
        return $this->findBy(array('ad_user_package' => $adUserPackageId));
    }

    /**
     * Find ad package upsells.
     *
     * @param integer $adId            Ad id.
     * @param mixed   $adUserPackageId Ad user package id.
     *
     * @return array
     */
    public function getAdPackageUpsell($adId, $adUserPackageId = null)
    {
        $upsellArray = array();
        $query = $this->createQueryBuilder(self::ALIAS)
            ->select(self::ALIAS, UpsellRepository::ALIAS)
            ->innerJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
            ->andWhere(self::ALIAS.'.ad_id = :adId')
            ->setParameter('adId', $adId);

        if ($adUserPackageId) {
            $query->andWhere(self::ALIAS.'.ad_user_package = :adUserPackageId')
                ->setParameter('adUserPackageId', $adUserPackageId);
        }

        $adUpsells = $query->getQuery()->getResult();

        foreach ($adUpsells as $adUpsell) {
            $upsellArray[$adUpsell->getUpsell()->getId()] = $adUpsell->getUpsell()->getTitle();
        }

        return $upsellArray;
    }

    /**
     * Find latest ad package upsells.
     *
     * @param integer $adId Ad id.
     *
     * @return array
     */
    public function getLatestAdPackageUpsell($adId)
    {
        $upsellArray = array();
        $adUserPackageUpsellTableName = $this->_em->getClassMetadata('FaAdBundle:AdUserPackageUpsell')->getTableName();
        $adUserPackageTableName = $this->_em->getClassMetadata('FaAdBundle:AdUserPackage')->getTableName();
        $upsellTableName = $this->_em->getClassMetadata('FaPromotionBundle:Upsell')->getTableName();

        $query = 'SELECT '.UpsellRepository::ALIAS.'.title as upsell_name,'.UpsellRepository::ALIAS.'.id as upsell_id
                FROM  '.$adUserPackageUpsellTableName.' AS '.AdUserPackageUpsellRepository::ALIAS.'
                INNER JOIN '.$adUserPackageTableName.' AS '.AdUserPackageRepository::ALIAS.' ON ('.AdUserPackageUpsellRepository::ALIAS.'.ad_user_package_id = '.AdUserPackageRepository::ALIAS.'.id AND '.AdUserPackageRepository::ALIAS.'.id = (SELECT MAX(id) FROM '.$adUserPackageTableName.' WHERE ad_id = '.$adId.' ))
                INNER JOIN '.$upsellTableName.' AS '.UpsellRepository::ALIAS.' ON ('.AdUserPackageUpsellRepository::ALIAS.'.upsell_id = '.UpsellRepository::ALIAS.'.id)
                WHERE '.AdUserPackageUpsellRepository::ALIAS.'.ad_id = '.$adId;
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();
        $adUpsells = $stmt->fetchAll();
        
        foreach ($adUpsells as $adUpsell) {
            $upsellArray[$adUpsell['upsell_id']] = $adUpsell['upsell_name'];
        }
        
        return $upsellArray;
    }

    /**
     * Find ad package upsells values array.
     *
     * @param integer $adId       Ad id.
     * @param integer $categoryId Category id.
     * @param object  $container  Continer identifier.
     *
     * @return array
     */
    public function getAdPackageUpsellValueArray($adId, $categoryId, $container)
    {
        $adUserPackageObj = $this->_em->getRepository('FaAdBundle:AdUserPackage')->getLastAdPackage($adId);
        $upsellValueArray = array();
        $query = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS, UpsellRepository::ALIAS)
        ->innerJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
        ->andWhere(self::ALIAS.'.ad_id = :adId')
        ->setParameter('adId', $adId);

        if ($adUserPackageObj) {
            $query->andWhere(self::ALIAS.'.ad_user_package = :adUserPackageId')
                ->setParameter('adUserPackageId', $adUserPackageObj->getId());
        }

        $adUpsells = $query->getQuery()->getResult();

        foreach ($adUpsells as $adUpsell) {
            //get photo upsell value.
            if ($adUpsell->getUpsell()->getType() == UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_ID) {
                if (!isset($upsellValueArray[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE])) {
                    $upsellValueArray[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE] = $adUpsell->getValue();
                } elseif (isset($upsellValueArray[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE]) && $upsellValueArray[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE] < $adUpsell->getValue()) {
                    $upsellValueArray[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE] = $adUpsell->getValue();
                }
            }
        }

        //get default value by category root id
        if (!isset($upsellValueArray[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE])) {
            $categoryPath   = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container);
            $categoryIds    = array_keys($categoryPath);
            $photoLimitName = CommonManager::getCategoryClassNameById($categoryIds[0]);
            $upsellValueArray[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE] = $container->getParameter('fa.image.'.$photoLimitName.'_upload_limit');
        }

        return $upsellValueArray;
    }

    /**
     * Get Count ad package upsells by upsell type and ad id.
     *
     * @param integer $adId Ad id.
     * @param integer $type Type of upsell.
     *
     * @return array
     */
    public function getAdPackageUpsellCountByIdAndType($adId, $type = null)
    {
        $queryBuilder = $this->getAdPackageUpsellsByIdAndTypeQueryBuilder($adId, $type);

        return $queryBuilder
                ->select('COUNT('.$queryBuilder->getRootAlias().'.id)')
                ->andWhere(self::ALIAS.'.status = 1')
                ->getQuery()
                ->getSingleScalarResult();
    }

    /**
     * Prepare query builder ad package upsells by upsell type and ad id.
     *
     * @param integer $adId Ad id.
     * @param integer $type Type of upsell.
     *
     * @return object
     */
    public function getAdPackageUpsellsByIdAndTypeQueryBuilder($adId, $type = null)
    {
        $queryBuilder = $this->createQueryBuilder(self::ALIAS)
                      ->select(self::ALIAS, UpsellRepository::ALIAS)
                      ->innerJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
                      ->andWhere(self::ALIAS.'.ad_id = :adId')
                      ->setParameter('adId', $adId)
                      ->andWhere(self::ALIAS.'.status=1')
                      ->andWhere(UpsellRepository::ALIAS.'.type = :type')
                      ->setParameter('type', $type);

        return $queryBuilder;
    }

    /**
     * Prepare query builder ads by type and value.
     *
     * @param integer $type Type id.
     * @param integer $upsellvalue Value of upsell.
     * @param array $ids Value of upsell.
     *
     * @return object
     */
    public function getAdsByTypeValue($type = null, $upsellvalue=null, $ids=null)
    {
        $resultArr = array();
        $returnArr = array();
        $queryBuilder = $this->createQueryBuilder(self::ALIAS)
                      ->select(self::ALIAS.'.ad_id')
                      ->innerJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
                      ->andWhere(UpsellRepository::ALIAS.'.type = :type')
                      ->setParameter('type', $type)
                      ->andWhere(self::ALIAS.'.status=1')
                      ->andWhere(UpsellRepository::ALIAS.'.value = :upsellvalue')
                      ->setParameter('upsellvalue', $upsellvalue);
        if ($ids) {
            $queryBuilder->andWhere(self::ALIAS.'.ad_id in ('.$ids.')');
        }
        $resultArr = $queryBuilder->getQuery()->getResult();
        
        if (!empty($resultArr)) {
            $returnArr = array_column($resultArr, 'ad_id');
        }
        return $returnArr;
    }

    /**
     * Prepare query builder ad package upsells by upsell type.
     *
     * @param integer $type Type of upsell.
     *
     * @return object
     */
    public function getAdPackageUpsellsByTypeQueryBuilder($type)
    {
        return $this->createQueryBuilder(self::ALIAS)
                        ->select(self::ALIAS, UpsellRepository::ALIAS)
                        ->innerJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
                        ->andWhere(UpsellRepository::ALIAS.'.type = :type')
                        ->setParameter('type', $type);
    }

    /**
     * Prepare query builder active ad package upsells by upsell type.
     *
     * @param integer $type Type of upsell.
     *
     * @return object
     */
    public function getActiveAdPackageUpsellsByTypeQueryBuilder($type)
    {
        return $this->getAdPackageUpsellsByTypeQueryBuilder($type)->andWhere(self::ALIAS.'.status=1');
    }

    /**
     * Get active ad package upsells by upsell type.
     *
     * @param integer $type Type of upsell.
     *
     * @return array
     */
    public function getActiveAdPackageUpsellsByType($type)
    {
        return $this->getActiveAdPackageUpsellsByTypeQueryBuilder($type)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * Get count for active ad package upsells by upsell type.
     *
     * @param integer $type Type of upsell.
     *
     * @return array
     */
    public function getActiveAdPackageUpsellCountByType($type)
    {
        $queryBuilder = $this->getActiveAdPackageUpsellsByTypeQueryBuilder($type);

        return $queryBuilder
                ->select('COUNT('.$queryBuilder->getRootAlias().'.id)')
                ->getQuery()
                ->getSingleScalarResult();
    }

    /**
     * Prepare query builder active ad package upsells for expiration
     *
     * @param integer $type      Type of upsell.
     * @param integer $expiresAt Expire time.
     * @param mixed   $adId      Ad ids.
     *
     * @return object
     */
    public function getActiveAdPackageUpsellsForExpirationQueryBuilder($type, $expiresAt= null, $adId = array())
    {
        $queryBuilder = $this->createQueryBuilder(self::ALIAS)
                             ->select(self::ALIAS, UpsellRepository::ALIAS)
                             ->innerJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
                             ->andWhere(self::ALIAS.'.status=1')
                             ->andWhere(UpsellRepository::ALIAS.'.type In (:type)')
                             ->setParameter('type', $type)
                             ->orderBy(self::ALIAS.'.ad_id', 'asc');

        if (count($adId)) {
            $queryBuilder->andWhere(self::ALIAS.'.ad_id IN (:ad_id)')->setParameter('ad_id', $adId);
        }

        if ($expiresAt) {
            $queryBuilder->andWhere(self::ALIAS.'.expires_at < :expires_at')->setParameter('expires_at', $expiresAt);
        }

        return $queryBuilder;
    }

    /**
     *
     * @return array
     */
    public function getBoostAdUpsellData($ad_id, $user_id)
    {
        if ($user_id) {
            $queryBuilder = $this->createQueryBuilder(self::ALIAS)
            ->select(self::ALIAS, UpsellRepository::ALIAS)
            ->innerJoin(self::ALIAS . '.upsell', UpsellRepository::ALIAS)
            ->where(self::ALIAS . '.status=1')
            ->andWhere(self::ALIAS . '.ad_id = :adId')
            ->andWhere(UpsellRepository::ALIAS . '.type='.UpsellRepository::UPSELL_TYPE_BOOST_ADVERT_ID)
            ->setParameter('adId', $ad_id)
            ->orderBy(self::ALIAS . '.ad_id', 'asc');
            $getUserPackageUpsellResults = $queryBuilder->getQuery()->getResult();
            if (!empty($getUserPackageUpsellResults)) {
                return $getUserPackageUpsellResults;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     *
     * @return array
     */
    public function getAutoRenewUpsellByAdId($ad_id)
    {
        $queryBuilder = $this->createQueryBuilder(self::ALIAS)
            ->select(self::ALIAS, UpsellRepository::ALIAS)
            ->innerJoin(self::ALIAS . '.upsell', UpsellRepository::ALIAS)
            ->where(self::ALIAS . '.status=1')
            ->andWhere(self::ALIAS . '.ad_id = :adId')
            ->andWhere(UpsellRepository::ALIAS . '.type='.UpsellRepository::UPSELL_TYPE_AUTO_RENEW_ID)
            ->setParameter('adId', $ad_id)
            ->orderBy(self::ALIAS . '.ad_id', 'asc');
        $getAutoRenewUpsellResults = $queryBuilder->getQuery()->getResult();
        if (!empty($getAutoRenewUpsellResults)) {
            return $getAutoRenewUpsellResults;
        } else {
            return array();
        }
    }
    
    /**
     *
     * @return array
     */
    public function getFeaturedUpsellByAdId($ad_id)
    {
        $queryBuilder = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS, UpsellRepository::ALIAS)
        ->innerJoin(self::ALIAS . '.upsell', UpsellRepository::ALIAS)
        ->where(self::ALIAS . '.status=1')
        ->andWhere(self::ALIAS . '.ad_id = :adId')
        ->andWhere(UpsellRepository::ALIAS . '.type='.UpsellRepository::UPSELL_TYPE_TOP_ADVERT_ID)
        ->setParameter('adId', $ad_id)
        ->andWhere(self::ALIAS . '.ad_user_package IS NOT NULL')
        ->orderBy(self::ALIAS . '.ad_id', 'asc');
        $getFeaturedUpsellResults = $queryBuilder->getQuery()->getResult();
        if (!empty($getFeaturedUpsellResults)) {
            return $getFeaturedUpsellResults;
        } else {
            return array();
        }
    }
    
    /**
     * Get featured upsell for ad id.
     *
     * @param array   $adId              Ad id array.
     * @param boolean $getTiPackageTitle Get ti package name flag.
     *
     * @return array
     */
    public function getAdFeaturedUpsellArrayByAdId($adId = array())
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', UpsellRepository::ALIAS.'.id as upsell_id', UpsellRepository::ALIAS.'.duration as duration', UpsellRepository::ALIAS.'.title', UpsellRepository::ALIAS.'.price', self::ALIAS.'.ad_id')
        ->leftJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
        ->andWhere(UpsellRepository::ALIAS . '.type='.UpsellRepository::UPSELL_TYPE_TOP_ADVERT_ID)
        ->andWhere(self::ALIAS.'.status = 1'); 
        
        if (!is_array($adId)) {
            $adId = array($adId);
        }
        
        if (count($adId)) {
            $qb->andWhere(self::ALIAS.'.ad_id IN (:adId)');
            $qb->setParameter('adId', $adId);
        }
        
        $adUpsells   = $qb->getQuery()->getArrayResult();
        $adUpsellArr = array();
        if (count($adUpsells)) {
            foreach ($adUpsells as $adUpsell) {           
                $adUpsellArr[$adUpsell['ad_id']]['title'] = $adUpsell['title'];
                $adUpsellArr[$adUpsell['ad_id']]['price'] = $adUpsell['price'];
                $adUpsellArr[$adUpsell['ad_id']]['upsell_id'] = $adUpsell['upsell_id'];
                $adUpsellArr[$adUpsell['ad_id']]['duration'] = $adUpsell['duration'];
            }
        }
        
        return $adUpsellArr;
    }
    
    /**
     * Get featured upsell for user id.
     *
     * @param integer $userId  user id.
     *
     * @return integer
     */
    public function getAdFeaturedUpsellCountByUserId($userId)
    {
        $featuredCnt = 0;
        $adStatusIds = array(BaseEntityRepository::AD_STATUS_LIVE_ID);
        
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select('Count(distinct('.self::ALIAS.'.ad_id)) as featured_cnt')
        ->leftJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
        ->leftJoin('FaAdBundle:Ad', AdRepository::ALIAS, 'WITH', AdRepository::ALIAS.'.id = '.self::ALIAS.'.ad_id')
        ->andwhere(AdRepository::ALIAS.'.status IN (:ad_status)')
        ->setParameter('ad_status', $adStatusIds)
        ->andWhere(AdRepository::ALIAS . '.user = '.$userId)
        ->andWhere(UpsellRepository::ALIAS . '.type='.UpsellRepository::UPSELL_TYPE_TOP_ADVERT_ID)
        ->andWhere(self::ALIAS.'.status = 1');
        
        $featuredCnt   = $qb->getQuery()->getSingleScalarResult();        
        return $featuredCnt;
    }
    
    /**
     * Get featured upsell for ad id.
     *
     * @param array   $userId              Ad id array.
     * @param boolean $getTiPackageTitle Get ti package name flag.
     *
     * @return array
     */
    public function getAdFeaturedUpsellIdsByAdId($adId = array())
    {
        $adFeaturedUpsellIds = '';
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.ad_id')
        ->leftJoin(self::ALIAS.'.upsell', UpsellRepository::ALIAS)
        ->andWhere(UpsellRepository::ALIAS . '.type='.UpsellRepository::UPSELL_TYPE_TOP_ADVERT_ID)
        ->andWhere(self::ALIAS.'.status = 1');
        $qb->groupBy(self::ALIAS.'.ad_id');
        
               
        if (!is_array($adId)) {
            $adId = array($adId);
        }
        
        /*if (!empty($adId)) {
            $qb->andWhere(self::ALIAS.'.ad_id IN (:adId)');
            $qb->setParameter('adId', $adId);
        }*/
        
        $adFeaturedUpsellArr   = $qb->getQuery()->getArrayResult();
        
        if(!empty($adFeaturedUpsellArr)) {
           $adFeaturedUpsellIdArray = array_column($adFeaturedUpsellArr, 'ad_id');
           if(!empty($adId)) {
                $adFeaturedUpsellIds  = array_intersect($adFeaturedUpsellIdArray, $adId);
           } else {
                $adFeaturedUpsellIds  = $adFeaturedUpsellIdArray;
           }
        }

        return $adFeaturedUpsellIds;
    }
}
