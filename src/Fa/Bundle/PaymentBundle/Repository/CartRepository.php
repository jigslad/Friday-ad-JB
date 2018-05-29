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
use Symfony\Component\DependencyInjection\Alias;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Entity\Transaction;
use Fa\Bundle\PaymentBundle\Entity\TransactionDetail;
use Fa\Bundle\PaymentBundle\Entity\Cart;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;

/**
 * This repository is used for cart.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class CartRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'ca';

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
     * Generate unique cart code across the table.
     *
     * @return string
     */
    public function generateCartCode()
    {
        $cart_code = 'CART'.strtoupper(substr(md5(md5(time().rand().microtime()).time()), 0, 10));
        if ($this->findOneBy(array('cart_code' => $cart_code)) == '') {
            return $cart_code;
        } else {
            self::generateCartCode();
        }
    }

    /**
     * Filter parameters.
     *
     * @param Doctrine_Query $query  Doctrine query object.
     * @param array          $params Parameter array.
     *
     * @return Doctrine_Query
     */
    public function filterParams($query, $params)
    {
        if (isset($params['user_id'])) {
            if (!is_array($params['user_id'])) {
                $params['user_id'] = array($params['user_id']);
            }
            $query->andWhere(self::ALIAS.'.user IN (:userIds)')
                ->setParameter('userIds', $params['user_id']);
        }

        if (isset($params['status'])) {
            if (!is_array($params['status'])) {
                $params['status'] = array($params['status']);
            }
            $query->andWhere(self::ALIAS.'.status IN (:statusIds)')
                ->setParameter('statusIds', $params['status']);
        }

        if (isset($params['is_buy_now'])) {
            if (!is_array($params['is_buy_now'])) {
                $params['is_buy_now'] = array($params['is_buy_now']);
            }
            $query->andWhere(self::ALIAS.'.is_buy_now = :isBuyNow')
            ->setParameter('isBuyNow', $params['is_buy_now']);
        }

        if (isset($params['is_shop_package_purchase'])) {
            if (!is_array($params['is_shop_package_purchase'])) {
                $params['is_shop_package_purchase'] = array($params['is_shop_package_purchase']);
            }
            $query->andWhere(self::ALIAS.'.is_shop_package_purchase = :isShopPackagePurchase')
            ->setParameter('isShopPackagePurchase', $params['is_shop_package_purchase']);
        }

        if (isset($params['cart_code'])) {
            if (!is_array($params['cart_code'])) {
                $params['cart_code'] = array($params['cart_code']);
            }
            $query->andWhere(self::ALIAS.'.status IN (:cartCodes)')
                ->setParameter('cartCodes', $params['cart_code']);
        }

        return $query;
    }

    /**
     * This method is used to get cart object based on given user id.
     *
     * @param integer $userId                User id.
     * @param object  $container             Container identifier.
     * @param boolean $isBuyNow              Buy now flag.
     * @param boolean $isShopPackagePurchase Shop package purchase flag.
     *
     * @return object
     */
    public function getUserCart($userId = null, $container = null, $isBuyNow = false, $isShopPackagePurchase = false)
    {
        $cart = null;
        if ($userId) {
            $params['user_id']    = $userId;
            $params['status']     = '1';
            $params['is_buy_now'] = ($isBuyNow)?$isBuyNow:0;
            $params['is_shop_package_purchase'] = ($isShopPackagePurchase)?$isShopPackagePurchase:0;
            $query = $this->getBaseQueryBuilder();
            $query = $this->filterParams($query, $params);
            $query->setMaxResults(1)
                ->orderBy(self::ALIAS.'.id', 'DESC');
            $cart  = $query->getQuery()->getOneOrNullResult();
        }
        

        if (!$cart) {
            $cart = new Cart();
            $cart->setStatus('1');
            $cart->setCartCode(self::generateCartCode());
            $cart->setCurrency(CommonManager::getCurrencyCode($container));
            $cart->setUpdatedAt(time());
            $cart->setIsBuyNow($isBuyNow);
            $cart->setIsShopPackagePurchase($isShopPackagePurchase);

            if ($userId) {
                $userObj = $this->_em->getRepository('FaUserBundle:User')->find($userId);
                $cart->setUser($userObj);
            }

            $this->_em->persist($cart);
            $this->_em->flush($cart);
        }
        return $cart;
    }

    /**
     * Add package into cart.
     *
     * @param integer $userId                    User id.
     * @param integer $adId                      Ad id.
     * @param integer $packageId                 Package id.
     * @param object  $container                 Continer indentifier.
     * @param boolean $isUserHasPurchasedPackage Flag for purchase package.
     * @param integer $adExpiryDays              Ad expiry days.
     * @param integer $selectedPackagePrintId    Print duration id.
     * @param string  $promoteRepostAdFlag       Promote or repost flag.
     * @param integer $activeAdUserPackageId     Active ad user packge id.
     * @param boolean $addAdToModeration         Need to send ad for moderation or not.
     * @param object  $cart                      Cart instance.
     * @param array   $printEditionValues        Print edition array.
     *
     */
    public function addPackageToCart($userId, $adId, $packageId, $container, $isUserHasPurchasedPackage = false, $adExpiryDays = 0, $selectedPackagePrintId = null, $promoteRepostAdFlag = null, $activeAdUserPackageId = null, $addAdToModeration = false, $cart = null, $printEditionValues = array(), $userCreditId = null, $totalCredit = null, $privateUserAdParams = array())
    {
        $adRepository                = $this->_em->getRepository('FaAdBundle:Ad');
        $userRepository              = $this->_em->getRepository('FaUserBundle:User');
        $transactionRepository       = $this->_em->getRepository('FaPaymentBundle:Transaction');
        $transactionDetailRepository = $this->_em->getRepository('FaPaymentBundle:TransactionDetail');
        $packageRepository           = $this->_em->getRepository('FaPromotionBundle:Package');
        $cartObj                     = ($cart ? $cart : $this->getUserCart($userId, $container));
        $transactions                = $transactionRepository->getTransactionsByCartIdAndAdId($cartObj->getId(), $adId);
        $adObj                       = $adRepository->find($adId);
        $packageObj                  = $packageRepository->find($packageId);
        $userObj                     = ($adObj->getUser() ? $adObj->getUser() : null);
        $isAdminLoggedIn             = CommonManager::isAdminLoggedIn($container);

        if (!$isAdminLoggedIn && $adObj && $adObj->getSource() == AdRepository::SOURCE_ADMIN) {
            $isAdminLoggedIn = true;
        }

        $value = $packageRepository->getPackageInfoForTransaction($packageObj, $adObj, $userObj, $isUserHasPurchasedPackage, $selectedPackagePrintId, $isAdminLoggedIn);
        $packagePrice = $packageRepository->getPackagePrice($packageObj, $selectedPackagePrintId, $isAdminLoggedIn);
        if ($userCreditId && $totalCredit) {
            $value['user_credit_id'] = $userCreditId;
            $value['user_credit'] = $totalCredit;
            if (isset($value['discount_values']) && count($value['discount_values'])) {
                unset($value['discount_values']);
            }
            $packagePrice = 0;
        }
        $value['package'][$packageId]['is_package_assigned_by_admin'] = false;
        $value['package'][$packageId]['is_purchased'] = true;
        if ($isAdminLoggedIn) {
            $value['package'][$packageId]['is_package_assigned_by_admin'] = true;
        }

        if (count($privateUserAdParams)) {
            $value['privateUserAdParams'] = $privateUserAdParams;
        }

        // for future advert post
        if ($adObj && $adObj->getFuturePublishAt() && CommonManager::isAdminLoggedIn($container)) {
            $value['futureAdPostFlag'] = true;
            $value['package'][$packageId]['futureAdPostFlag'] = true;
        }
        if ($promoteRepostAdFlag) {
            $value['promoteRepostAdFlag'] = $promoteRepostAdFlag;
        }

        if ($activeAdUserPackageId) {
            $value['active_ad_user_package_id'] = $activeAdUserPackageId;
        }

        if ($selectedPackagePrintId) {
            $packagePrint      = $this->_em->getRepository('FaPromotionBundle:PackagePrint')->findOneBy(array('id' => $selectedPackagePrintId, 'package' => $packageObj->getId()));
            $packagePrintPrice = 0;
            if ($isAdminLoggedIn) {
                $packagePrintPrice = $packagePrint->getAdminPrice() !== null ? $packagePrint->getAdminPrice() : $packagePrint->getPrice();
            } else {
                $packagePrintPrice = $packagePrint->getPrice();
            }

            $value['package'][$packageId]['packagePrint'] = array('id' => $packagePrint->getId(), 'price' => $packagePrintPrice, 'duration' => $packagePrint->getDuration());
        }

        if (count($printEditionValues)) {
            $value['package'][$packageId]['printEditions'] = $printEditionValues;
        }

        $isAdminPrice = false;
        if ($isAdminLoggedIn) {
            if ($selectedPackagePrintId) {
                $isAdminPrice = $packagePrint->getAdminPrice() !== null ? true : false;
            } else {
                $isAdminPrice = $packageObj->getAdminPrice() !== null ? true : false;
            }
        }

        $value['package'][$packageId]['is_admin_price'] = $isAdminPrice;

        //set ad expiry days.
        if ($adExpiryDays) {
            $value['package'][$packageId]['ad_expiry_days'] = $adExpiryDays;

            if ($promoteRepostAdFlag && $promoteRepostAdFlag == 'renew') {
                $value['ad_expiry_days_renew'] = $adExpiryDays;
            }
        }

        //set moderation flag
        $value['package'][$packageId]['addAdToModeration'] = $addAdToModeration;

        //set package for
        $value['package'][$packageId]['package_for'] = $packageObj->getPackageFor();

        if ($cartObj && $adObj && $packageObj) {
            if ($transactions) {
                foreach ($transactions as $transaction) {
                    $transactionDetailObj = $transactionDetailRepository->getTransactionDetailByPaymentFor($cartObj->getId(), $transaction->getId(), $adId, TransactionDetailRepository::PAYMENT_FOR_PACKAGE);
                    if ($transactionDetailObj) {
                        //update transaction detail value
                        $transactionDetailObj->setAmount($packagePrice);
                        $transactionDetailObj->setValue(serialize($value));
                        $this->_em->persist($transactionDetailObj);
                        $this->_em->flush($transactionDetailObj);
                        //update transaction value
                        $transactionValue = unserialize($transaction->getValue());
                        $transactionValue = serialize($transactionValue + $value);
                        $transaction->setValue(serialize($value));
                        $this->_em->persist($transaction);
                        $this->_em->flush($transaction);
                    } else {
                        $transactionDetail = new TransactionDetail();
                        $transactionDetail->setTransaction($transaction);
                        $transactionDetail->setPaymentFor(TransactionDetailRepository::PAYMENT_FOR_PACKAGE);
                        $transactionDetail->setAmount($packagePrice);
                        $transactionDetail->setValue(serialize($value));
                        $this->_em->persist($transactionDetail);
                        $this->_em->flush($transactionDetail);
                    }
                }
            } else {
                $transaction = new Transaction();
                $transaction->setTransactionId(CommonManager::generateHash());
                $transaction->setAd($adObj);
                $transaction->setUser($userObj);
                $transaction->setCart($cartObj);
                $transaction->setValue(serialize($value));
                $this->_em->persist($transaction);
                $this->_em->flush($transaction);

                $transactionDetail = new TransactionDetail();
                $transactionDetail->setTransaction($transaction);
                $transactionDetail->setPaymentFor(TransactionDetailRepository::PAYMENT_FOR_PACKAGE);
                $transactionDetail->setAmount($packagePrice);
                $transactionDetail->setValue(serialize($value));
                $this->_em->persist($transactionDetail);
                $this->_em->flush($transactionDetail);
            }
        }
    }

    /**
     * Add subscription package into cart.
     *
     * @param integer $userId                    User id.
     * @param integer $packageId                 Package id.
     * @param object  $container                 Continer indentifier.
     * @param boolean $isUserHasPurchasedPackage Flag for purchase package.
     * @param array   $value                     Package array.
     * @param integer $adExpiryDays              Ad expiry days.
     * @param booelan $allow_zero_amount         Allow zero amount in cart
     */
    public function addSubscriptionToCart($userId, $packageId, $container, $isUserHasPurchasedPackage = false, $value = array(), $adExpiryDays = 0, $allow_zero_amount = false)
    {
        $userRepository              = $this->_em->getRepository('FaUserBundle:User');
        $transactionRepository       = $this->_em->getRepository('FaPaymentBundle:Transaction');
        $transactionDetailRepository = $this->_em->getRepository('FaPaymentBundle:TransactionDetail');
        $packageRepository           = $this->_em->getRepository('FaPromotionBundle:Package');
        $cartObj                     = $this->getUserCart($userId, $container, false, true);
        $transactions                = $transactionRepository->getTransactionsByCartId($cartObj->getId());
        $packageObj                  = $packageRepository->find($packageId);
        $userObj                     = $userRepository->findOneBy(array('id' => $userId));

        if (!count($value)) {
            $value = $packageRepository->getPackageInfoForTransaction($packageObj, null, $userObj, $isUserHasPurchasedPackage);

            if ($packageObj->getTrail() && $allow_zero_amount === true) {
                $packagePrice = 0;
            } else {
                $packagePrice = $packageObj->getPrice();
            }

            $value['package'][$packageId]['is_purchased'] = true;
        } else {
            $packageValues = $value['package'][$packageId];
            $packagePrice = $packageObj->getPrice();
            $value['package'][$packageId]['price'] = $packagePrice;
            $value['package'][$packageId]['is_purchased'] = false;
        }

        if ($adExpiryDays) {
            $value['package'][$packageId]['expiry_days'] = $adExpiryDays;
        }

        //set package for
        $value['package'][$packageId]['package_for'] = $packageObj->getPackageFor();

        if ($userObj && $cartObj && $packageObj) {
            if ($transactions) {
                foreach ($transactions as $transaction) {
                    $transactionDetailObj = $transactionDetailRepository->getTransactionDetailByPaymentFor($cartObj->getId(), $transaction->getId(), null, TransactionDetailRepository::PAYMENT_FOR_SHOP);
                    if ($transactionDetailObj) {
                        //update transaction detail value
                        $transactionDetailObj->setAmount($packagePrice);
                        $transactionDetailObj->setValue(serialize($value));
                        $this->_em->persist($transactionDetailObj);
                        $this->_em->flush($transactionDetailObj);
                        //update transaction value
                        $transactionValue = unserialize($transaction->getValue());
                        $transactionValue = serialize($transactionValue + $value);
                        $transaction->setValue(serialize($value));
                        $this->_em->persist($transaction);
                        $this->_em->flush($transaction);
                    } else {
                        $transactionDetail = new TransactionDetail();
                        $transactionDetail->setTransaction($transaction);
                        $transactionDetail->setPaymentFor(TransactionDetailRepository::PAYMENT_FOR_SHOP);
                        $transactionDetail->setAmount($packagePrice);
                        $transactionDetail->setValue(serialize($value));
                        $this->_em->persist($transactionDetail);
                        $this->_em->flush($transactionDetail);
                    }
                }
            } else {
                $transaction = new Transaction();
                $transaction->setTransactionId(CommonManager::generateHash());
                $transaction->setUser($userObj);
                $transaction->setCart($cartObj);
                $transaction->setValue(serialize($value));
                $this->_em->persist($transaction);
                $this->_em->flush($transaction);

                $transactionDetail = new TransactionDetail();
                $transactionDetail->setTransaction($transaction);
                $transactionDetail->setPaymentFor(TransactionDetailRepository::PAYMENT_FOR_SHOP);
                $transactionDetail->setAmount($packagePrice);
                $transactionDetail->setValue(serialize($value));
                $this->_em->persist($transactionDetail);
                $this->_em->flush($transactionDetail);
            }
        }
    }

    /**
     * Update total by cart id.
     *
     * @param object $cartObj         Cart object.
     * @param integer $total          Total.
     * @param integer $discountAmount Discount amount.
     * @param string  $value          Serialized string.
     *
     * @return boolean
     */
    public function updateTotalByCartId($cartObj, $total, $discountAmount = 0, $value = null)
    {
        $cartId = $cartObj->getId();
        $value = unserialize($value);
        $cartValue = unserialize($cartObj->getValue());
        $hasDiscount = false;
        /*if (isset($value['discount_values']) && count($value['discount_values'])) {
            $cartValue['discount_values'] = $value['discount_values'];
        } else if (isset($cartValue['discount_values']) && count($cartValue['discount_values'])) {
            unset($cartValue['discount_values']);
        }*/
        $transactionObjs = $this->_em->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartId($cartId);
        foreach ($transactionObjs as $transactionObj) {
            $transactionValue = unserialize($transactionObj->getValue());
            if (!$hasDiscount && isset($transactionValue['discount_values']) && count($transactionValue['discount_values'])) {
                $hasDiscount = true;
            }
            if (isset($transactionValue['discount_values']) && count($transactionValue['discount_values'])) {
                $cartValue['discount_values'] = $transactionValue['discount_values'];
            }
        }
        if (!$hasDiscount && isset($cartValue['discount_values']) && count($cartValue['discount_values'])) {
            unset($cartValue['discount_values']);
        }
        if (!$total) {
            $total = 0;
        }

        return $this->getBaseQueryBuilder()
            ->update()
            ->set(self::ALIAS.'.amount', $total)
            ->set(self::ALIAS.'.discount_amount', $discountAmount)
            ->set(self::ALIAS.'.value', ':value')
            ->setParameter('value', serialize($cartValue))
            ->andWhere(self::ALIAS.'.id = '.$cartId)
            ->getQuery()
            ->execute();
    }

    /**
     * Add status filter.
     *
     * @param integer $status Status value.
     */
    protected function addStatusFilter($status = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.status = '.$status);
    }

    /**
     * Add cart code filter to existing query object.
     *
     * @param string $cartCode Cart code.
     */
    protected function addCartCodeFilter($cartCode = null)
    {
        if ($cartCode) {
            if (!is_array($cartCode)) {
                $cartCode = array($cartCode);
            }

            $cartCode = array_filter($cartCode);

            if (count($cartCode)) {
                $this->queryBuilder->andWhere($this->getRepositoryAlias().'.cart_code IN (:'.$this->getRepositoryAlias().'_cartCode'.')');
                $this->queryBuilder->setParameter($this->getRepositoryAlias().'_cartCode', $cartCode);
            }
        }
    }

    /**
     * Add buy now ad into cart.
     *
     * @param object  $userObj          User object.
     * @param object  $adObj            Ad object.
     * @param integer $paypalCommission Paypal commission.
     * @param object  $container        Continer indentifier.
     * @param integer $postagePrice     Postage price.
     */
    public function addBuyNowAdToCart($userObj, $adObj, $paypalCommission, $container, $postagePrice = 0)
    {
        $userId                      = $userObj->getId();
        $adId                        = $adObj->getId();
        $adRepository                = $this->_em->getRepository('FaAdBundle:Ad');
        $userRepository              = $this->_em->getRepository('FaUserBundle:User');
        $transactionRepository       = $this->_em->getRepository('FaPaymentBundle:Transaction');
        $transactionDetailRepository = $this->_em->getRepository('FaPaymentBundle:TransactionDetail');
        $cartObj                     = $this->getUserCart($userId, $container, true);
        $transactions                = $transactionRepository->getTransactionsByCartIdAndAdId($cartObj->getId(), $adId);

        $value['paypal']['commission'] = $paypalCommission;
        if ($userObj && $cartObj && $adObj) {
            $value['paypal']['ad_id'] = $adId;
            $cartObj->setAmount($adObj->getPrice() + $postagePrice);
            $cartObj->setValue(serialize($value));
            $this->_em->persist($cartObj);
            $this->_em->flush($cartObj);
            if ($transactions) {
                foreach ($transactions as $transaction) {
                    $transactionDetailObj = $transactionDetailRepository->getTransactionDetailByPaymentFor($cartObj->getId(), $transaction->getId(), $adId, TransactionDetailRepository::PAYMENT_FOR_BUY_NOW);
                    if ($transactionDetailObj) {
                        //update transaction detail value
                        $transactionDetailObj->setAmount($adObj->getPrice() + $postagePrice);
                        $transactionDetailObj->setValue(serialize($value));
                        $this->_em->persist($transactionDetailObj);
                        $this->_em->flush($transactionDetailObj);
                        //update transaction value
                        $transactionValue = unserialize($transaction->getValue());
                        $transactionValue = serialize($transactionValue + $value);
                        $transaction->setValue(serialize($value));
                        $this->_em->persist($transaction);
                        $this->_em->flush($transaction);
                    } else {
                        $transactionDetail = new TransactionDetail();
                        $transactionDetail->setTransaction($transaction);
                        $transactionDetail->setPaymentFor(TransactionDetailRepository::PAYMENT_FOR_BUY_NOW);
                        $transactionDetail->setAmount($adObj->getPrice() + $postagePrice);
                        $transactionDetail->setValue(serialize($value));
                        $this->_em->persist($transactionDetail);
                        $this->_em->flush($transactionDetail);
                    }
                }
            } else {
                $transaction = new Transaction();
                $transaction->setTransactionId(CommonManager::generateHash());
                $transaction->setAd($adObj);
                $transaction->setUser($userObj);
                $transaction->setCart($cartObj);
                $transaction->setValue(serialize($value));
                $this->_em->persist($transaction);
                $this->_em->flush($transaction);

                $transactionDetail = new TransactionDetail();
                $transactionDetail->setTransaction($transaction);
                $transactionDetail->setPaymentFor(TransactionDetailRepository::PAYMENT_FOR_BUY_NOW);
                $transactionDetail->setAmount($adObj->getPrice() + $postagePrice);
                $transactionDetail->setValue(serialize($value));
                $this->_em->persist($transactionDetail);
                $this->_em->flush($transactionDetail);
            }
        }
    }
}
