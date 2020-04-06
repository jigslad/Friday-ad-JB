<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Entity\UserCredit;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserCreditRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'uc';

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
     * Add package status filter to existing query object.
     *
     * @param integer $status
     */
    protected function addStatusFilter($status = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.status = '.$status);
    }

    /**
     * Add package type filter to existing query object.
     *
     * @param array $packageSrNo Role id.
     */
    protected function addPackageSrNoFilter($packageSrNos = null)
    {
        $sqlString = null;

        foreach ($packageSrNos as $packageSrNo) {
            $sqlString .= 'FIND_IN_SET('.$packageSrNo.', '.self::ALIAS.'.package_sr_no) > 0 OR ';
        }

        if ($sqlString) {
            $sqlString = rtrim($sqlString, 'OR ');
        }

        $this->queryBuilder->andWhere($sqlString);
    }

    /**
     * Add category id filter to existing query object
     *
     * @param integer $id category id.
     */
    protected function addCategoryIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            $id = array_filter($id);
            if (count($id)) {
                $categoryNestedArray = array();
                foreach ($id as $categoryId) {
                    $nestedChildren = $this->_em->getRepository('FaEntityBundle:Category')->getNestedChildrenIdsByCategoryId($categoryId);
                    if (count($nestedChildren)) {
                        $categoryNestedArray = $categoryNestedArray + $nestedChildren;
                    }
                }

                $id = $categoryNestedArray;
                $this->queryBuilder->andWhere(CategoryRepository::ALIAS.'.id IN (:category_id'.')');
                $this->queryBuilder->setParameter('category_id', $id);
            }
        }
    }

    /**
     * Add credit for user based on packge perchase
     *
     * @param object $user    User object.
     * @param object $package Package object.
     */
    public function addUserCredit($user, $package)
    {
        $shopPackageCredits = $this->_em->getRepository('FaPromotionBundle:ShopPackageCredit')->getPackageCreditsByPackageId($package->getId());

        if (count($shopPackageCredits)) {
            foreach ($shopPackageCredits as $shopPackageCredit) {
                if ($shopPackageCredit->getCredit() && $shopPackageCredit->getCategory() && $shopPackageCredit->getPackageSrNo()) {
                    $expiresAt = CommonManager::getTimeStampFromEndDate(date('Y-m-d', CommonManager::getTimeFromDuration($shopPackageCredit->getDuration())));                    
                    $shopCreditCnt = $shopPackageCredit->getCredit();                     
                    $userCredit = new UserCredit();
                    $userCredit->setUser($user);
                    $userCredit->setCategory($shopPackageCredit->getCategory());
                    $userCredit->setCredit($shopCreditCnt);
                    $userCredit->setPackageSrNo($shopPackageCredit->getPackageSrNo());
                    $userCredit->setPaidUserOnly($shopPackageCredit->getPaidUserOnly());
                    $userCredit->setStatus(1);
                    $userCredit->setExpiresAt($expiresAt);
                    $this->_em->persist($userCredit);
                }
            }
            $this->_em->flush();
        }
    }

    /**
     * Get active credit for user by category.
     *
     * @param integer $userId         User id.
     * @param integer $rootCategoryId Root category id.
     *
     * @return array
     */
    public function getActiveCreditForUserByCategory($userId, $rootCategoryId, $cartId = null, $adId = null)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.user = '.$userId)
            ->andWhere(self::ALIAS.'.category = '.$rootCategoryId)
            ->andWhere(self::ALIAS.'.status = 1')
            ->andWhere(self::ALIAS.'.credit > 0')
            ->andWhere(self::ALIAS.'.expires_at IS NULL OR '.self::ALIAS.'.expires_at > '.time());

        $activeUserCredits = $qb->getQuery()->getResult();
        $userCreditArr = array();
        if (count($activeUserCredits)) {
            foreach ($activeUserCredits as $activeUserCredit) {
                $userCreditArr[$activeUserCredit->getId()] = array('package_sr_no' => explode(',', $activeUserCredit->getPackageSrNo()), 'credit' => $activeUserCredit->getCredit(), 'paid_user_only' => $activeUserCredit->getPaidUserOnly());
            }
        }

        if ($cartId && $adId) {
            $transactions = $this->_em->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartId($cartId);
            foreach ($transactions as $transaction) {
                $transactionValue = unserialize($transaction->getValue());
                $adObj = $transaction->getAd();
                if ($adObj && $adId != $adObj->getId() && isset($transactionValue['user_credit_id']) && isset($transactionValue['user_credit']) && isset($userCreditArr[$transactionValue['user_credit_id']])) {
                    if (($userCreditArr[$transactionValue['user_credit_id']]['credit'] - $transactionValue['user_credit']) < 0) {
                        $userCreditArr[$transactionValue['user_credit_id']]['credit'] = 0;
                    } else {
                        $userCreditArr[$transactionValue['user_credit_id']]['credit'] = ($userCreditArr[$transactionValue['user_credit_id']]['credit'] - $transactionValue['user_credit']);
                    }
                }
            }
        }

        return $userCreditArr;
    }

    /**
     * Get package sr no wise credits.
     *
     * @param array $userCreditArr USer credits array.
     *
     * @return array
     */
    public function getPackageWiseActiveCreditForUser($userCreditArr)
    {
        $packageWiseCredits = array();

        if (count($userCreditArr)) {
            foreach ($userCreditArr as $userCredit) {
                $packageSrNos = $userCredit['package_sr_no'];
                foreach ($packageSrNos as $packageSrNo) {
                    if (isset($packageWiseCredits[$packageSrNo])) {
                        $packageWiseCredits[$packageSrNo] += (int) $userCredit['credit'];
                    } else {
                        $packageWiseCredits[$packageSrNo] = (int) $userCredit['credit'];
                    }
                }
            }
        }

        return $packageWiseCredits;
    }

    /**
     * Get active credit count for user.
     *
     * @param integer $userId         User id.
     *
     * @return array
     */
    public function getActiveCreditCountForUser($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
            ->select('SUM('.self::ALIAS.'.credit) as total_credit')
            ->andWhere(self::ALIAS.'.user = '.$userId)
            ->andWhere(self::ALIAS.'.status = 1')
            ->andWhere(self::ALIAS.'.credit > 0')
            ->andWhere(self::ALIAS.'.expires_at IS NULL OR '.self::ALIAS.'.expires_at > '.time())
            ->setMaxResults(1);

        $activeUserCredits = $qb->getQuery()->getOneOrNullResult();

        return ($activeUserCredits['total_credit'] ? $activeUserCredits['total_credit'] : 0);
    }
    
    /**
     * Get active credit count for user.
     *
     * @param integer $userId         User id.
     *
     * @return array
     */
    public function getActiveFeaturedCreditCountForUser($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.credit as featured_credit')
        ->andWhere(self::ALIAS.'.user = '.$userId)
        ->andWhere(self::ALIAS.'.status = 1')
        ->andWhere(self::ALIAS.'.credit > 0')
        ->andWhere('FIND_IN_SET(6, '.self::ALIAS.'.package_sr_no) > 0 or FIND_IN_SET(3, '.self::ALIAS.'.package_sr_no) > 0')
        ->orderBy(self::ALIAS.'.id','desc')
        ->setMaxResults(1);

        $activeUserCredits = $qb->getQuery()->getOneOrNullResult();        
        return ($activeUserCredits['featured_credit'] ? $activeUserCredits['featured_credit'] : 0);
    }
    
    /**
     * Get active credit count for user.
     *
     * @param integer $userId         User id.
     *
     * @return array
     */
    public function getActiveBasicCreditCountForUser($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.credit as basic_credit')
        ->andWhere(self::ALIAS.'.user = '.$userId)
        ->andWhere(self::ALIAS.'.status = 1')
        ->andWhere(self::ALIAS.'.credit > 0')
        ->andWhere('FIND_IN_SET(1, '.self::ALIAS.'.package_sr_no) > 0')
        ->andWhere(self::ALIAS.'.expires_at IS NULL OR '.self::ALIAS.'.expires_at > '.time())
        ->setMaxResults(1);
        
        $activeUserCredits = $qb->getQuery()->getOneOrNullResult();
        return ($activeUserCredits['basic_credit'] ? $activeUserCredits['basic_credit'] : 0);
    }
    
    public function getActiveFeaturedCreditForUser($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.user = '.$userId)
        ->andWhere(self::ALIAS.'.status = 1')
        ->andWhere(self::ALIAS.'.credit > 0')
        ->andWhere('FIND_IN_SET(6, '.self::ALIAS.'.package_sr_no) > 0 or FIND_IN_SET(3, '.self::ALIAS.'.package_sr_no) > 0')
        ->andWhere(self::ALIAS.'.expires_at IS NULL OR '.self::ALIAS.'.expires_at > '.time())
        ->setMaxResults(1);
        
        $activeUserCredits = $qb->getQuery()->getOneOrNullResult();
        
        return $activeUserCredits;
    }

    /**
     * Get active credit for user by category.
     *
     * @param integer $userId         User id.
     * @param integer $rootCategoryId Root category id.
     *
     * @return array
     */
    public function getActiveCreditDetailForUser($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.user = '.$userId)
        ->andWhere(self::ALIAS.'.status = 1')
        ->andWhere(self::ALIAS.'.credit > 0')
        ->andWhere(self::ALIAS.'.expires_at IS NULL OR '.self::ALIAS.'.expires_at > '.time());

        $activeUserCredits = $qb->getQuery()->getResult();
        $userCreditArr = array();
        if (count($activeUserCredits)) {
            foreach ($activeUserCredits as $activeUserCredit) {
                if (isset($userCreditArr[$activeUserCredit->getCategory()->getId()]) && isset($userCreditArr[$activeUserCredit->getCategory()->getId()]['credit'])) {
                    $userCreditArr[$activeUserCredit->getCategory()->getId()]['credit'] += $activeUserCredit->getCredit();
                } else {
                    $userCreditArr[$activeUserCredit->getCategory()->getId()]['credit'] = $activeUserCredit->getCredit();
                }

                if (isset($userCreditArr[$activeUserCredit->getCategory()->getId()][date('Y_m_d', $activeUserCredit->getExpiresAt()).'_'.$activeUserCredit->getPaidUserOnly().'_'.$activeUserCredit->getPackageSrNo()])) {
                    $userCreditArr[$activeUserCredit->getCategory()->getId()][date('Y_m_d', $activeUserCredit->getExpiresAt()).'_'.$activeUserCredit->getPaidUserOnly().'_'.$activeUserCredit->getPackageSrNo()]['credit'] += $activeUserCredit->getCredit();
                } else {
                    $userCreditArr[$activeUserCredit->getCategory()->getId()][date('Y_m_d', $activeUserCredit->getExpiresAt()).'_'.$activeUserCredit->getPaidUserOnly().'_'.$activeUserCredit->getPackageSrNo()] = array(
                        'id' => $activeUserCredit->getId(),
                        'package_sr_no' => explode(',', $activeUserCredit->getPackageSrNo()),
                        'credit' => $activeUserCredit->getCredit(),
                        'paid_user_only' => $activeUserCredit->getPaidUserOnly(),
                        'expires_at' => $activeUserCredit->getExpiresAt(),
                    );
                }
            }
        }

        return $userCreditArr;
    }

    /**
     * Get credit package name
     *
     * @param integer $packageSrNo Package sr no.
     *
     * @return string
     */
    public function getCreditPackageName($packageSrNo)
    {
        $packageName = array(
            '1' => 'Basic',
            '2' => 'Urgent',
            '3' => 'Featured',
            '4' => 'Spotlight',
            '5' => 'Urgent',
            '6' => 'Featured',
            '7' => 'Spotlight',
        );

        return (isset($packageName[$packageSrNo]) ? $packageName[$packageSrNo] : '-');
    }
}
