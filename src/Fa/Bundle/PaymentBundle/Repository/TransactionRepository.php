<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\AdBundle\Repository\AdImageRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class TransactionRepository extends EntityRepository
{
    const ALIAS = 't';
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
     * Update total by transaction id.
     *
     * @param object  $transactionObj Transaction object.
     * @param integer $total          Total.
     * @param integer $discountAmount Discount amount.
     * @param string  $value          Serialized string.
     * @param boolean $updateVatValue Update vat.
     *
     * @return boolean
     */
    public function updateTotalByTransactionId($transactionObj, $total, $discountAmount = 0, $value = null, $updateVatValue = true)
    {
        $value = unserialize($value);
        $transactionId = $transactionObj->getId();
        $transactionValue = unserialize($transactionObj->getValue());

        if (isset($value['discount_values']) && count($value['discount_values'])) {
            $transactionValue['discount_values'] = $value['discount_values'];
        } else if (isset($transactionValue['discount_values']) && count($transactionValue['discount_values'])) {
            unset($transactionValue['discount_values']);
        }

        if (isset($value['user_credit_id']) && isset($value['user_credit'])) {
            $transactionValue['user_credit_id'] = $value['user_credit_id'];
        } else if (isset($transactionValue['user_credit_id']) && isset($transactionValue['user_credit'])) {
            unset($transactionValue['user_credit_id']);
            unset($transactionValue['user_credit']);
        }

        $vatAmount = 0;
        $vat       = 0;
        //if flag is true then update vat else not.
        if ($updateVatValue) {
            $vat = $this->_em->getRepository('FaCoreBundle:Config')->getVatAmount();

            if (!$total) {
                $total = 0;
            }

            $vatAmount = (($total * $vat) / 100);
        }

        return $this->getBaseQueryBuilder()
            ->update()
            ->set(self::ALIAS.'.amount', $total)
            ->set(self::ALIAS.'.vat', $vat)
            ->set(self::ALIAS.'.vat_amount', $vatAmount)
            ->set(self::ALIAS.'.discount_amount', $discountAmount)
            ->set(self::ALIAS.'.value', ':value')
            ->setParameter('value', serialize($transactionValue))
            ->andWhere(self::ALIAS.'.id = '.$transactionId)
            ->getQuery()
            ->execute();
    }

    /**
     * Get transaction by cart id & ad id.
     *
     * @param integer $cartId Cart id.
     * @param integer $adId   Ad id.
     *
     * @return Doctrine_Collection
     */
    public function getTransactionsByCartIdAndAdId($cartId, $adId)
    {
        return $this->getBaseQueryBuilder()
            ->andWhere(self::ALIAS.'.cart = '.$cartId)
            ->andWhere(self::ALIAS.'.ad = '.$adId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get transactions by cart id.
     *
     * @param integer $cartId Cart id.
     *
     * @return Doctrine_Collection
     */
    public function getTransactionsByCartId($cartId)
    {
        return $this->getBaseQueryBuilder()
            ->andWhere(self::ALIAS.'.cart = '.$cartId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get transaction by cart id.
     *
     * @param integer $cartId Cart id.
     *
     * @return Object
     */
    public function getTransactionByCartId($cartId)
    {
        return $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.cart = '.$cartId)
        ->setMaxResults(1)
        ->orderBy(self::ALIAS.'.id', 'DESC')
        ->getQuery()
        ->getOneOrNullResult();
    }

    /**
     * Update total by transaction id.
     *
     * @param integer $cartId Cart id.
     *
     * @return Doctrine_Collection
     */
    public function getCartDetail($cartId)
    {
        return $this->getBaseQueryBuilder()
            ->select(self::ALIAS.'.id', self::ALIAS.'.value', self::ALIAS.'.amount', self::ALIAS.'.vat', self::ALIAS.'.vat_amount', AdRepository::ALIAS.'.id as ad_id', AdRepository::ALIAS.'.title', AdRepository::ALIAS.'.price', AdImageRepository::ALIAS.'.aws', AdImageRepository::ALIAS.'.path', AdImageRepository::ALIAS.'.hash', AdImageRepository::ALIAS.'.image_name', 'IDENTITY('.AdRepository::ALIAS.'.category) as category_id')
            ->leftJoin(self::ALIAS.'.ad', AdRepository::ALIAS)
            ->leftJoin('FaAdBundle:AdImage', AdImageRepository::ALIAS, 'WITH', AdImageRepository::ALIAS.'.ad = '.AdRepository::ALIAS.'.id AND '.AdImageRepository::ALIAS.'.ord = 1')
            ->andWhere(self::ALIAS.'.cart = '.$cartId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total by cart id.
     *
     * @param integer $cartId Cart id.
     *
     * @return integer
     */
    public function getTotalByCartId($cartId)
    {
        $query = $this->getBaseQueryBuilder()
            ->select('SUM('.self::ALIAS.'.amount) as total', 'SUM('.self::ALIAS.'.discount_amount) as discount_amount')
            ->andWhere(self::ALIAS.'.cart = '.$cartId);
        $result = $query->getQuery()->getOneOrNullResult();

        $query = $this->getBaseQueryBuilder()
        ->select(self::ALIAS.'.value')
        ->andWhere(self::ALIAS.'.discount_amount > 0')
        ->andWhere(self::ALIAS.'.cart = '.$cartId)
        ->setMaxResults(1);
        $resultVal = $query->getQuery()->getOneOrNullResult();
        if (isset($resultVal['value'])) {
            $result['value'] = $resultVal['value'];
        }

        return $result;
    }

    /**
     * Check user's transaction is in cart.
     *
     * @param integer $cartId Cart id.
     * @param integer $userId User id.
     *
     * @return boolean
     */
    public function checkTransactionsForUser($cartId, $userId)
    {
        $userTransactionFlag = true;
        $transactions = $this->getBaseQueryBuilder()
            ->select(UserRepository::ALIAS.'.id as user_id')
            ->innerJoin(self::ALIAS.'.user', UserRepository::ALIAS)
            ->andWhere(self::ALIAS.'.cart = '.$cartId)
            ->getQuery()
            ->getResult();

        foreach ($transactions as $transaction) {
            if ($transaction['user_id'] != $userId) {
                $userTransactionFlag = false;
                break;
            }
        }

        return $userTransactionFlag;
    }

    /**
     * Get user id by cart.
     *
     * @param integer $cartId Cart id.
     * @param integer $userId User id.
     *
     * @return mixed
     */
    public function getUserIdByCart($cartId)
    {
        $transaction = $this->getBaseQueryBuilder()
            ->select(UserRepository::ALIAS.'.id as user_id')
            ->innerJoin(self::ALIAS.'.user', UserRepository::ALIAS)
            ->andWhere(self::ALIAS.'.cart = '.$cartId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return isset($transaction['user_id']) ? $transaction['user_id'] : null;
    }

    /**
     * Get user id by cart.
     *
     * @param integer $cartId Cart id.
     *
     * @return mixed
     */
    public function getAdIdByCart($cartId)
    {
        $transaction = $this->getBaseQueryBuilder()
        ->select('IDENTITY('.self::ALIAS.'.ad) as ad_id')
        ->andWhere(self::ALIAS.'.cart = '.$cartId)
        ->getQuery()
        ->getOneOrNullResult();

        return isset($transaction['ad_id']) ? $transaction['ad_id'] : null;
    }

    /**
     * Remove transaction by id.
     *
     * @param integer $transactionId Transaction id.
     * @param object  $cartObj       Cart object.
     *
     * @return boolean
     */
    public function removeByTransactionId($transactionId, $cartObj = null)
    {
        $delete = $this->getBaseQueryBuilder()
        ->delete()
        ->andWhere(self::ALIAS.'.id = '.$transactionId)
        ->getQuery()
        ->execute();

        if ($delete && $cartObj) {
            $total = null;
            $discountAmount = 0;
            $value = null;
            $transactionRes = $this->getTotalByCartId($cartObj->getId());
            if (isset($transactionRes['total'])) {
                $total = $transactionRes['total'];
            }
            if (isset($transactionRes['discount_amount'])) {
                $discountAmount = $transactionRes['discount_amount'];
            }
            if (isset($transactionRes['value'])) {
                $value = $transactionRes['value'];
            }
            $this->_em->getRepository('FaPaymentBundle:Cart')->updateTotalByCartId($cartObj, $total, $discountAmount, $value);
        }
        return $delete;
    }

    /**
     * Get total by user id and ad categories.
     *
     * @param integer $cartId Cart id.
     *
     * @return integer
     */
    public function getTotalAdByUserIdAndCategoryId($userId, $categoryIds = array(), $adIds = array())
    {
        $query = $this->getBaseQueryBuilder()
        ->select('COUNT( DISTINCT '.AdRepository::ALIAS.'.id) as ad_cnt')
        ->innerJoin(self::ALIAS.'.cart', CartRepository::ALIAS)
        ->innerJoin(self::ALIAS.'.ad', AdRepository::ALIAS)
        ->andWhere(CartRepository::ALIAS.'.status = 1')
        ->andWhere(CartRepository::ALIAS.'.user = '.$userId)
        ->andWhere(CartRepository::ALIAS.'.is_buy_now = 0')
        ->andWhere(CartRepository::ALIAS.'.is_shop_package_purchase = 0')
        ->setMaxResults(1);

        if(count($categoryIds)) {
            $query->andWhere(AdRepository::ALIAS.'.category IN (:adCategories)')
            ->setParameter('adCategories', $categoryIds);
        }

        if(count($adIds)) {
            $query->andWhere(AdRepository::ALIAS.'.id NOT IN (:adIds)')
            ->setParameter('adIds', $adIds);
        }

        $ads = $query->getQuery()->getOneOrNullResult();

        return $ads['ad_cnt'];
    }
}
