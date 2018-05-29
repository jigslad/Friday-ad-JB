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
use Fa\Bundle\PaymentBundle\Entity\Payment;
use Assetic\Exception\Exception;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PaymentRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'p';

    const PAYMENT_METHOD_PAYPAL = 'paypal';
    const PAYMENT_METHOD_PAYPAL_ADAPTIVE = 'paypal-adaptive';
    const PAYMENT_METHOD_CYBERSOURCE = 'cybersource';
    const PAYMENT_METHOD_CYBERSOURCE_RECURRING = 'cybersource-recurring';
    const PAYMENT_METHOD_APPLEPAY = 'applepay';
    const PAYMENT_METHOD_APPLEPAY_RECURRING = 'applepay-recurring';
    const PAYMENT_METHOD_AMAZONPAY = 'amazonpay';
    const PAYMENT_METHOD_AMAZONPAY_RECURRING = 'amazonpay-recurring';

    const PAYMENT_METHOD_FREE = 'free';
    const PAYMENT_METHOD_OFFLINE_PAYMENT = 'offline-payment';

    const PAYMENT_METHOD_CASH_ON_COLLECTION_ID = 1;
    const PAYMENT_METHOD_PAYPAL_ID             = 2;
    const PAYMENT_METHOD_PAYPAL_OR_CASH_ID     = 3;
    const VAT = 20;
    const CURRENCY = 'GBP';

    const BN_NEW_ORDER_ID              = 1;
    const BN_PREPARING_FOR_DISPATCH_ID = 2;
    const BN_DISPATCHED_ID             = 3;
    const BN_DELIVERED_ID              = 4;
    const BN_CLOSED_ID                 = 5;

    /**
     * User admin obj.
     *
     * @var
     */
    protected $userAdminObj = null;

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
     * Update payment from cart.
     *
     * @param object  $cart         Cart object.
     * @param object  $userAdminObj Admin user object.
     *
     * @return integer
     */
    public function setPayment($cart, $userAdminObj)
    {
        $payment = new Payment();
        $payment->setAmount($cart->getAmount());
        $payment->setCartCode($cart->getCartCode());
        $payment->setCurrency($cart->getCurrency());
        $payment->setDeliveryMethodOption($cart->getDeliveryMethodOption());
        $payment->setPaymentMethod($cart->getPaymentMethod());
        $payment->setDiscountAmount($cart->getDiscountAmount());
        $payment->setStatus(1);
        $payment->setValue($cart->getValue());
        $payment->setUser($cart->getUser());
        if ($userAdminObj) {
            $payment->setIsActionByAdmin(1);
            $payment->setActionByUserId($userAdminObj->getId());
        }

        $this->_em->persist($payment);
        $this->_em->flush();
        $this->updatePaymentMethod($payment);

        $cartValue = unserialize($cart->getValue());
        if ($payment->getDiscountAmount() > 0 && isset($cartValue['discount_values']) && count($cartValue['discount_values']) && isset($cartValue['discount_values']['id'])) {
            $this->_em->getRepository('FaPromotionBundle:UserPackageDiscountCode')->addUserPackageDiscountCode($cartValue, $cart->getUser(), $payment, $cart);
        }

        return $payment->getId();
    }

    /**
     * Process payment using cart code.
     *
     * @param string  $cartCode     Code of cart.
     * @param object  $userAdminObj Admin user object.
     * @param object  $container    Container object.
     *
     * @throws \Exception
     * @return integer
     */
    public function processPaymentSuccess($cartCode, $userAdminObj = null, $container = null)
    {
        try {
            //check if cart is already processed
            $paymentObject = $this->_em->getRepository('FaPaymentBundle:Payment')->findOneBy(array('cart_code' => $cartCode));

            if (!$paymentObject) {
                $this->userAdminObj = $userAdminObj;

                // find cart from cart code.
                $cart = $this->_em->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode));

                if ($cart) {
                    // set payment
                    $paymentId = $this->setPayment($cart, $userAdminObj);

                    // fetch all transactions of cart.
                    $transactions = $this->_em->getRepository('FaPaymentBundle:Transaction')->findBy(array('cart' => $cart->getId()));

                    foreach ($transactions as $transaction) {
                        $this->handleTransaction($transaction, $paymentId, $container);
                    }
                    $cart->setStatus(0);
                    $this->_em->persist($cart);
                    $this->_em->flush($cart);

                    try {
                        // update dotmailer last paid at
                        if ($cart->getUser() && $cart->getUser()->getEmail()) {
                            $this->_em->getRepository('FaPaymentBundle:Cart')->updateFieldByEmail($cart->getUser()->getEmail(), 'last_paid_at');
                        }
                    } catch (\Exception $e) {
                    }

                    return $paymentId;
                } else {
                    throw new \Exception('Cart not found with code: '.$cartCode);
                }
            } elseif ($paymentObject) {
                $cart = $this->_em->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode, 'status' => 1));
                if ($cart) {
                    $cart->setStatus(0);
                    $this->_em->persist($cart);
                    $this->_em->flush($cart);
                }
                return $paymentObject->getId();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Process payment using cart code.
     *
     * @param string  $cartCode  Code of cart.
     * @param object  $container Container object.
     *
     * @throws \Exception
     * @return integer
     */
    public function processBuyNowPaymentSuccess($cartCode, $container)
    {
        try {
            //check if cart is already processed
            $paymentObject = $this->_em->getRepository('FaPaymentBundle:Payment')->findOneBy(array('cart_code' => $cartCode));
            if (!$paymentObject) {
                $cart = $this->_em->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode));

                if ($cart) {
                    // set payment
                    $paymentId = $this->setPayment($cart, null);

                    // fetch all transactions of cart.
                    $transactions = $this->_em->getRepository('FaPaymentBundle:Transaction')->findBy(array('cart' => $cart->getId()));

                    foreach ($transactions as $transaction) {
                        // handle payment transaction detail.
                        $adObj = $transaction->getAd();
                        $adObj->setQty($adObj->getQty() - 1);
                        $adObj->setQtySold($adObj->getQtySold() + 1);
                        $adObj->setQtySoldTotal(($adObj->getQtySoldTotal() + 1));
                        $this->_em->persist($adObj);
                        $this->_em->flush($adObj);

                        //if qty is zero then expire ad.
                        if ($adObj->getQty() <= 0) {
                            $expiredAt = time();
                            $adObj->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_EXPIRED_ID));
                            $adObj->setExpiresAt($expiredAt);
                            $this->_em->persist($adObj);
                            $this->_em->flush($adObj);

                            // insert expire stat
                            $this->_em->getRepository('FaAdBundle:AdStatistics')->insertExpiredStat($adObj, $expiredAt);

                            // inactivate the package
                            $this->_em->getRepository('FaAdBundle:Ad')->doAfterAdCloseProcess($adObj->getId(), $container);
                        }
                        $paymentTransactionId = $this->handlePaymentTransaction($transaction, $paymentId);

                        // handle payment transaction detail.
                        $this->handlePaymentTransactionDetail($transaction, $paymentTransactionId, false);
                    }
                    $cart->setStatus(0);
                    $this->_em->persist($cart);
                    $this->_em->flush($cart);

                    try {
                        // update dotmailer last paid at
                        if ($cart->getUser() && $cart->getUser()->getEmail()) {
                            $this->_em->getRepository('FaPaymentBundle:Cart')->updateFieldByEmail($cart->getUser()->getEmail(), 'last_paid_at');
                        }
                    } catch (\Exception $e) {
                    }

                    return $paymentId;
                } else {
                    throw new \Exception('Cart not found with code: '.$cartCode);
                }
            } elseif ($paymentObject) {
                $cart = $this->_em->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode, 'status' => 1));
                if ($cart) {
                    $cart->setStatus(0);
                    $this->_em->persist($cart);
                    $this->_em->flush($cart);
                }
                return $paymentObject->getId();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Handle each transaction of cart.
     *
     * @param object  $transaction Transaction object.
     * @param integer $paymentId   Payment id.
     * @param object  $container    Container object.
     */
    public function handleTransaction($transaction, $paymentId, $container = null)
    {
        $addAdToModeration = null;
        $futureAdPostFlag  = false;
        $printPkg = null;
        $value = unserialize($transaction->getValue());

        // set expires at
        if ($transaction->getAd()) {
            $ad = $transaction->getAd();

            if ($ad->getStatus()->getId() == BaseEntityRepository::AD_STATUS_DRAFT_ID
                || $ad->getStatus()->getId() == BaseEntityRepository::AD_STATUS_EXPIRED_ID || $ad->getStatus()->getId() == BaseEntityRepository::AD_STATUS_SOLD_ID) {
                $expirationDays = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId(), $container);
                $ad->setExpiresAt($this->_em->getRepository('FaAdBundle:Ad')->getAdPrintExpiry($ad->getId(), CommonManager::getTimeFromDuration($expirationDays.'d')));
                $this->_em->persist($ad);
                $this->_em->flush($ad);
            }

            // handle is_paid_ad and is_paid_before
            if ($transaction->getAmount() > 0 && $ad->getIsPaidAd() != 1) {
                $ad->setIsPaidAd(1);
                $this->_em->persist($ad);
                $this->_em->flush($ad);
            }

            if ($transaction->getAmount() > 0 && $ad->getUser() && $ad->getUser()->getIsPaidBefore() != 1) {
                $user = $ad->getUser();
                $user->setIsPaidBefore(1);
                $this->_em->persist($user);
                $this->_em->flush($user);
            }

            // handle renewed and quantity
            $this->_em->getRepository('FaAdBundle:Ad')->handleRenewAndQty($ad, $container);

            // handle credits
            if (isset($value['user_credit_id']) && isset($value['user_credit'])) {
                $this->_em->getRepository('FaUserBundle:UserCreditUsed')->addUserCreditUsed($transaction, $paymentId);
            }
        }

        // handle package.
        if (isset($value['package'])) {
            $adObj = $transaction->getAd();
            if ($adObj) {
                $addAdToModeration = $this->handlePackage($value['package'], $adObj);
                $futureAdPostFlag  = (isset($value['futureAdPostFlag']) ? $value['futureAdPostFlag'] : false);
            }

            foreach ($value['package'] as $p) {
                if ((isset($p['package_for']) && $p['package_for'] == 'shop')) {
                    $this->handleSubscriptionPackage($transaction->getUser()->getId(), $p['id'], $paymentId, $container);
                }

                if (isset($p['packagePrint']) && $adObj) {
                    $printPkg = true;
                    if ($adObj->getUser()) {
                        $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_upgraded_print', $adObj->getId(), $adObj->getUser()->getId());
                    }
                } elseif ($p['price'] > 0 && $adObj && $adObj->getUser()) {
                    $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_upgraded', $adObj->getId(), $adObj->getUser()->getId());
                }
            }
        }

        // handle notification
        if (isset($value['promoteRepostAdFlag']) && $value['promoteRepostAdFlag'] == 'promote') {
            $ad = $transaction->getAd();

            if (($this->_em->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($ad->getId()))) {
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_7_days', $ad->getId());
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_14_days', $ad->getId());
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_21_days', $ad->getId());
            }

        } elseif (isset($value['promoteRepostAdFlag']) && $value['promoteRepostAdFlag'] == 'repost' && $addAdToModeration == false) {
            $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live', $ad->getId(), $ad->getUser()->getId());
            $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('share_on_facebook_twitter', $ad->getId(), $ad->getUser()->getId());

            if (($this->_em->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($ad->getId()) == false) || $ad->getWeeklyRefreshAt() == null) {
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_7_days', $ad->getId(), $ad->getUser()->getId(), '7d');
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_14_days', $ad->getId(), $ad->getUser()->getId(), '14d');
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_21_days', $ad->getId(), $ad->getUser()->getId(), '21d');
            }

            $adImgCount = $this->_em->getRepository('FaAdBundle:AdImage')->getAdImageCount($ad->getId());

            if (!$adImgCount) {
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('no_photos', $ad->getId(), $ad->getUser()->getId());
            } else {
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('no_photos', $ad->getId());
            }

            $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_incomplete', $adObj->getId());
        } elseif (isset($value['promoteRepostAdFlag']) && $value['promoteRepostAdFlag'] == 'renew' && $addAdToModeration == false) {
            $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live', $ad->getId(), $ad->getUser()->getId());
            $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('share_on_facebook_twitter', $ad->getId(), $ad->getUser()->getId());

            if (($this->_em->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($ad->getId()) == false) || $ad->getWeeklyRefreshAt() == null) {
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_7_days', $ad->getId(), $ad->getUser()->getId(), '7d');
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_14_days', $ad->getId(), $ad->getUser()->getId(), '14d');
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_21_days', $ad->getId(), $ad->getUser()->getId(), '21d');
            }

            $adImgCount = $this->_em->getRepository('FaAdBundle:AdImage')->getAdImageCount($ad->getId());

            if (!$adImgCount) {
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('no_photos', $ad->getId(), $ad->getUser()->getId());
            } else {
                $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('no_photos', $ad->getId());
            }
        }

        // handle payment transaction detail.
        $paymentTransactionId = $this->handlePaymentTransaction($transaction, $paymentId);

        // handle payment transaction detail.
        $this->handlePaymentTransactionDetail($transaction, $paymentTransactionId, $addAdToModeration, $futureAdPostFlag);
    }


    /**
     * handle subscription package after payment
     *
     * @param integer $user_id    User id
     * @param integer $package_id Package id
     * @param integer $paymentId  Payment id.
     * @param object  $container  Container object.
     */
    private function handleSubscriptionPackage($user_id, $package_id, $paymentId, $container = null)
    {
        $user       = $this->_em->getRepository('FaUserBundle:User')->findOneBy(array('id' => $user_id));
        $package    = $this->_em->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $package_id));
        $userPackage = $this->_em->getRepository('FaUserBundle:UserPackage')->assignPackageToUser($user, $package, 'choose-package-frontend', $paymentId, false, $container);
    }

    /**
     * Handle packages of transaction.
     *
     * @param array   $packages Packages array.
     * @param object  $adObj    Ad object.
     */
    public function handlePackage(array $packages, $adObj)
    {
        foreach ($packages as $package) {
            //get ad moderation flag.
            $addAdToModeration = false;
            $futureAdPostFlag  = false;
            $packagePrint      = null;
            if (isset($package['addAdToModeration'])) {
                $addAdToModeration = $package['addAdToModeration'];
            }
            if (isset($package['futureAdPostFlag'])) {
                $futureAdPostFlag = $package['futureAdPostFlag'];
            }
            if (isset($package['packagePrint'])) {
                $packagePrint = $package['packagePrint'];
            }
            //send ad for moderation
            if ($adObj && $addAdToModeration) {
                $this->handleAdModerate($adObj);
            }
            // make entry into ad user package
            $adUserPackageId = $this->_em->getRepository('FaAdBundle:AdUserPackage')->setAdUserPackage($package, $addAdToModeration, false, $futureAdPostFlag);

            // handle upsells
            if (isset($package['upsell'])) {
                $this->handleUpsell($package['upsell'], $adUserPackageId, $adObj, $addAdToModeration, $packagePrint, $futureAdPostFlag);
            }

            return $addAdToModeration;
        }
    }

    /**
     * Handle upsell of transaction.
     *
     * @param array   $upsells           Upsells array.
     * @param integer $adUserPackageId   Ad user package id.
     * @param object  $adObj             Ad object.
     * @param boolean $addAdToModeration Send ad to moderate or not.
     * @param array   $packagePrint      Package print array.
     * @param boolean $futureAdPostFlag  Future advert post flag.
     */
    public function handleUpsell(array $upsells, $adUserPackageId, $adObj = null, $addAdToModeration = true, $packagePrint = null, $futureAdPostFlag = false)
    {
        $printUpsellFlag = false;
        foreach ($upsells as $upsell) {
            // make entry into ad user package upsell
            $adUserPackageUpsellId = $this->_em->getRepository('FaAdBundle:AdUserPackageUpsell')->setAdUserPackageUpsell($upsell, $adUserPackageId, $addAdToModeration, false, $futureAdPostFlag);
            //add ad to print.
            if ($adObj) {
                //check user has purchased print upsell else add free print ad.
                if (isset($upsell['type']) && $upsell['type'] == UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID) {
                    $printUpsellFlag = true;
                    $duration        = null;

                    if (is_array($packagePrint) && count($packagePrint)) {
                        $duration = $packagePrint['duration'];
                    }

                    $this->_em->getRepository('FaAdBundle:AdPrint')->addPrintAd($upsell['value'], $duration, $adObj, $addAdToModeration, true, false, null, $futureAdPostFlag, $adUserPackageId);
                }
            }
        }
        //if no print upsell then add free print upsell.
        if (!$printUpsellFlag) {
            $this->_em->getRepository('FaAdBundle:AdPrint')->addPrintAd(1, '', $adObj, $addAdToModeration, false, false, null, $futureAdPostFlag);
        }
    }

    /**
     * Handle payment.
     *
     * @param object $cart Cart object.
     */
    public function handlePayment($cart)
    {
        return $this->setPayment($cart);
    }

    /**
     * Handle payment transaction.
     *
     * @param object  $transaction Transaction object.
     * @param integer $paymentId   Payment id.
     */
    public function handlePaymentTransaction($transaction, $paymentId)
    {
        return $this->_em->getRepository('FaPaymentBundle:PaymentTransaction')->setPaymentTransaction($transaction, $paymentId);
    }

    /**
     * Handle payment transaction detail.
     *
     * @param object  $transaction          Transaction object.
     * @param integer $paymentTransactionId Payment transaction id.
     * @param boolean $addAdToModeration    Send ad to moderate or not.
     * @param boolean $futureAdPostFlag     Future advert post flag.
     */
    public function handlePaymentTransactionDetail($transaction, $paymentTransactionId, $addAdToModeration, $futureAdPostFlag = false)
    {
        $transactionDetails = $this->_em->getRepository('FaPaymentBundle:TransactionDetail')->findBy(array('transaction' => $transaction->getId()));
        foreach ($transactionDetails as $transactionDetail) {
            $this->_em->getRepository('FaPaymentBundle:PaymentTransactionDetail')->setPaymentTransactionDetail($transactionDetail, $paymentTransactionId, $addAdToModeration, $futureAdPostFlag);
        }
    }

    /**
     * Handle ad moderate.
     *
     * @param Ad     $adId     Ad object
     * @param string $modifyIp Modify ip
     */
    public function handleAdModerate(Ad $ad, $modifyIp=null)
    {
        // Don't perform addAdToModerate when status is expired and sold because we already did that from advert editing.
        if ($ad && $ad->getStatus() && in_array($ad->getStatus()->getId(), array(BaseEntityRepository::AD_STATUS_EXPIRED_ID, BaseEntityRepository::AD_STATUS_SOLD_ID))) {
            return;
        }

        if ($this->userAdminObj == null) {
            $adId            = $ad->getId();
            $adModerateArray = array();

            $adModerateArray['ad']         = $this->_em->getRepository('FaAdBundle:Ad')->findByAdId($adId, true, $modifyIp);
            $adModerateArray['images']     = $this->_em->getRepository('FaAdBundle:AdImage')->findByAdId($adId);
            $adModerateArray['locations']  = $this->_em->getRepository('FaAdBundle:AdLocation')->findByAdId($adId, true);
            $root                          = $this->_em->getRepository('FaEntityBundle:Category')->getRootNodeByCategory($ad->getCategory()->getId());
            $repository                    = $this->_em->getRepository('FaAdBundle:'.'Ad'.str_replace(' ', '', $root->getName()));
            $adModerateArray['dimensions'] = $repository->findByAdId($adId);
            $this->_em->getRepository('FaAdBundle:AdModerate')->addAdToModerate($ad, $adModerateArray);
        }
    }

    /**
     * Get payment method options.
     *
     * @param object $container Container identifier.
     *
     * @return array
     */
    public function getPaymentMethodOptionsArray($container)
    {
        $translator  = CommonManager::getTranslator($container);
        $optionArray = array(
            self::PAYMENT_METHOD_CASH_ON_COLLECTION_ID  => $translator->trans('Cash on collection'),
            self::PAYMENT_METHOD_PAYPAL_ID              => $translator->trans('Paypal'),
            self::PAYMENT_METHOD_PAYPAL_OR_CASH_ID      => $translator->trans('Paypal or cash')
        );

        return $optionArray;
    }

    /**
     * Get payment method name by id.
     *
     * @param integer $id        Payment method id.
     * @param object  $container Container identifier.
     *
     * @return mixed
     */
    public function getPaymentMethodNameById($id, $container)
    {
        $options = $this->getPaymentMethodOptionsArray($container);
        if (isset($options[$id])) {
            return $options[$id];
        }

        return null;
    }

    /**
     * Get payment method route name.
     *
     * @param string $paymentMethod Payment method name.
     *
     * @return string
     */
    public function getPaymentMethodRoute($paymentMethod)
    {
        $route = '';
        switch ($paymentMethod) {
            case self::PAYMENT_METHOD_CYBERSOURCE:
                $route = 'cybersource_checkout';
                break;
            case self::PAYMENT_METHOD_PAYPAL:
                $route = 'paypal_checkout';
                break;
            case self::PAYMENT_METHOD_APPLEPAY:
                $route = 'applepay_checkout';
                break;
            case self::PAYMENT_METHOD_AMAZONPAY:
                $route = 'amazonpay_checkout';
                break;
        }

        return $route;
    }

    /**
     * Method used to update payment method record.
     *
     * @param object $paymentObj Payment object.
     */
    public function updatePaymentMethod($paymentObj)
    {
        switch ($paymentObj->getPaymentMethod())
        {
            case self::PAYMENT_METHOD_CYBERSOURCE:
                $this->_em->getRepository('FaPaymentBundle:PaymentCyberSource')->addPaymentRecord($paymentObj);
                break;
            case self::PAYMENT_METHOD_AMAZONPAY:
                $this->_em->getRepository('FaPaymentBundle:PaymentAmazon')->addPaymentRecord($paymentObj);
                break;
            case self::PAYMENT_METHOD_PAYPAL_ADAPTIVE:
            case self::PAYMENT_METHOD_PAYPAL:
                $this->_em->getRepository('FaPaymentBundle:PaymentPaypal')->addPaymentRecord($paymentObj);
                break;
        }
    }

    /**
     * Get payment method options.
     *
     * @param object $container Container identifier.
     *
     * @return array
     */
    public function getPaymentMethods($container)
    {
        $translator  = CommonManager::getTranslator($container);
        $optionArray = array(
            self::PAYMENT_METHOD_FREE        => $translator->trans('Free'),
            self::PAYMENT_METHOD_PAYPAL      => $translator->trans('Paypal'),
            self::PAYMENT_METHOD_CYBERSOURCE => $translator->trans('Cybersource')
        );

        return $optionArray;
    }

    /**
     * Add cart code filter to existing query object.
     *
     * @param string $cartCode Cart code.
     */
    protected function addCartCodeFilter($cartCode = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.cart_code = (:cart_code)');
        $this->queryBuilder->setParameter('cart_code', $cartCode);
    }

    /**
     * Add payment method filter to existing query object.
     *
     * @param string $paymentMethod Payment method.
     */
    protected function addPaymentMethodFilter($paymentMethod = array())
    {
        if ($paymentMethod) {
            if (!is_array($paymentMethod)) {
                $paymentMethod = array($paymentMethod);
            }

            $paymentMethod = array_filter($paymentMethod);

            if (count($paymentMethod)) {
                $this->queryBuilder->andWhere($this->getRepositoryAlias().'.payment_method IN (:payment_method)');
                $this->queryBuilder->setParameter('payment_method', $paymentMethod);
            }
        }
    }

    /**
     * get transcation details for google analytics
     *
     * @param string $cartCode
     * @param User $user
     *
     * @return array
     */
    public function getTranscationDetailsForGA($cartCode, $user)
    {
        $payment = $this->findOneBy(array('cart_code' => $cartCode, 'status' => 1, 'ga_status' => 0, 'user' => $user->getId()));
        $data    = array();

        if ($payment) {
            $data['PAYMENT_ID'] = $payment->getId();
            $data['ID'] = $payment->getCartCode();
            $data['Affiliation'] = '';
            $data['Revenue'] = $payment->getAmount();
            $data['Shipping']= 0;
            $data['Tax']     = ($payment->getAmount()-($payment->getAmount()/(1 + self::VAT/100)));
            $data['Currency']= self::CURRENCY;
            $items = $this->_em->getRepository('FaPaymentBundle:PaymentTransactionDetail')->getTransactonDataForGoogleAnalytics($payment->getId());
            foreach ($items as $item) {
                $data['items'] = $items;
                $data['ID'] = $item['ID'];
            }
        }

        return $data;
    }

    /**
     * Get last paid at for user.
     *
     * @param integer $userId
     *
     * @return integer
     */
    public function getLastPaidAt($userId)
    {
        $qb = $this->getBaseQueryBuilder()
            ->select(self::ALIAS.'.created_at as created_at')
            ->andWhere(self::ALIAS.'.user = :userId')
            ->setParameter('userId', $userId)
            ->addOrderBy(self::ALIAS.'.created_at', 'desc')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get delivery status options.
     *
     * @param object $container Container identifier.
     *
     * @return array
     */
    public function getDeliveryStatusOptionsArray($container)
    {
        $translator  = CommonManager::getTranslator($container);
        $optionArray = array(
            self::BN_NEW_ORDER_ID  => $translator->trans('New order'),
            self::BN_PREPARING_FOR_DISPATCH_ID  => $translator->trans('Preparing for dispatch'),
            self::BN_DISPATCHED_ID => $translator->trans('Dispatched'),
            self::BN_DELIVERED_ID => $translator->trans('Delivered'),
            self::BN_CLOSED_ID => $translator->trans('Closed'),
        );

        return $optionArray;
    }

    /**
     * Get delivery status by status id.
     *
     * @param object $container Container identifier.
     *
     * @return array
     */
    public function getDeliveryStatusByStatusId($statusId, $container)
    {
        $optionArray = $this->getDeliveryStatusOptionsArray($container);
        if (isset($optionArray[$statusId])) {
            return $optionArray[$statusId];
        }

        return null;
    }

    /**
     * Get page number based on cart code.
     *
     * @param integer $userId    User id.
     * @param string  $cartCode  Cart code.
     * @param object  $container Container identifier.
     *
     * @return integer
     */
    public function getPageNumberByCartCodeForPurchaseOrder($userId, $cartCode, $container, $type = 'purchases')
    {
        if ($type == 'purchases') {
            $userFieldName = 'user_id';
        } elseif ($type == 'orders') {
            $userFieldName = 'seller_user_id';
        }
        $entityManager    = $container->get('doctrine')->getManager();
        $paymentTableName = $entityManager->getClassMetadata('FaPaymentBundle:Payment')->getTableName();
        $sql = 'SELECT x.id, x.position, x.'.$userFieldName.', x.payment_method, x.cart_code
            FROM (
                SELECT '.self::ALIAS.'.id, @rownum := @rownum +1 AS position, '.self::ALIAS.'.'.$userFieldName.', '.self::ALIAS.'.payment_method, '.self::ALIAS.'.cart_code, '.self::ALIAS.'.created_at
                    FROM '.$paymentTableName.' '.self::ALIAS.'
                JOIN (SELECT @rownum :=0) r
                WHERE '.self::ALIAS.'.'.$userFieldName.' ="'.$userId.'" and '.self::ALIAS.'.payment_method="'.self::PAYMENT_METHOD_PAYPAL_ADAPTIVE.'" ORDER BY '.self::ALIAS.'.created_at DESC
            ) x
            WHERE x.'.$userFieldName.' ="'.$userId.'" and x.payment_method="'.self::PAYMENT_METHOD_PAYPAL_ADAPTIVE.'" and x.cart_code = "'.$cartCode.'"';

        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Get invoice months.
     *
     * @param integer $userId User id.
     *
     * @return array
     */
    public function getInvoiceMonths($userId)
    {
        $invoiceMonthArray = array();
        $paymentTableName  = $this->_em->getClassMetadata('FaPaymentBundle:Payment')->getTableName();

        $sql = 'SELECT DISTINCT DATE_FORMAT(FROM_UNIXTIME('.self::ALIAS.'.created_at), "%c %Y") as payment_date
            FROM '.$paymentTableName.' as '.self::ALIAS.
            ' WHERE '.self::ALIAS.'.user_id = '.$userId.' AND '.self::ALIAS.'.amount > 0 AND '.self::ALIAS.'.payment_method IN ("'.self::PAYMENT_METHOD_PAYPAL.'", "'.self::PAYMENT_METHOD_CYBERSOURCE.'", "'.self::PAYMENT_METHOD_CYBERSOURCE_RECURRING.'", "'.self::PAYMENT_METHOD_AMAZONPAY.'") ORDER BY '.self::ALIAS.'.created_at DESC';
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();
        $invoiceMonthsResult = $stmt->fetchAll();

        foreach ($invoiceMonthsResult as $invoiceMonth) {
            list($month, $year) = explode(' ', $invoiceMonth['payment_date']);
            $invoiceMonthArray[$month.'_'.$year] = CommonManager::getMonthName($month).' '.$year;
        }

        return $invoiceMonthArray;
    }
}
