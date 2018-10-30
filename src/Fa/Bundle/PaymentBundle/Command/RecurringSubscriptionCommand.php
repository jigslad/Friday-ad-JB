<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\UserBundle\Repository\UserPackageRepository;
use Fa\Bundle\PaymentBundle\Entity\Payment;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Repository\PaymentCyberSourceRepository;
use Fa\Bundle\PaymentBundle\Entity\PaymentCyberSource;
use Fa\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Fa\Bundle\PaymentBundle\Entity\PaymentTransactionDetail;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentTransactionDetailRepository;
use Fa\Bundle\PaymentBundle\Repository\TransactionDetailRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to renew subscription packages through recurring payment
 *
 * php app/console fa:recurring-subscription
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class RecurringSubscriptionCommand extends ContainerAwareCommand
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:recurring-subscription')
        ->setDescription('Recurring subscription')
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'user id', null);
    }


    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $QUERY_BATCH_SIZE = 1000;
        $done             = false;
        $last_id          = 0;
        $user_id          = $input->getOption('user_id');

        while (!$done) {
            $userPackages = $this->getUserSubscriptions($last_id, $QUERY_BATCH_SIZE, $user_id);
            if ($userPackages) {
                foreach ($userPackages as $userPackage) {
                    $user = $userPackage->getUser();
                    $userStatus = $this->em->getRepository('FaUserBundle:User')->getUserStatus($user->getId(), $this->getContainer());
                    if ($userPackage->getPayment() && $userStatus === EntityRepository::USER_STATUS_ACTIVE_ID) {
                        $today = date('d', time());

                        $dat  = date('d', $userPackage->getExpiresAt());

                        if ($today < 27 && (($dat - $today) != 1 && ($dat - $today) > 0) && !$user_id) {
                            continue;
                        }

                        $payment = $userPackage->getPayment();
                        $package = $userPackage->getPackage();
                        $values =  unserialize($payment->getValue());
                        $subscriptionId = null;
                        $cyberSourceManager  = $this->getContainer()->get('fa.cyber.source.manager');
                        $cyberSourceManager->setMerchantReferenceCodeForSubscription();

                        $billTo              = $this->getBillToArray($user, $values);
                        $userAddressBookInfo = $this->getBillToArray($user, $values, true);

                        if (isset($values['cyber_source_response']->paySubscriptionCreateReply) && isset($values['cyber_source_response']->paySubscriptionCreateReply->subscriptionID) && $values['cyber_source_response']->paySubscriptionCreateReply->subscriptionID != '') {
                            $subscriptionId = $values['cyber_source_response']->paySubscriptionCreateReply->subscriptionID;
                        } elseif (isset($values['subscriptionID']) && $values['subscriptionID'] != '') {
                            $subscriptionId = $values['subscriptionID'];
                        }

                        if ($subscriptionId == null) {
                            echo "Subscription failed for user".$user->getId()."\n";
                            $this->em->getRepository('FaUserBundle:UserPackage')->sendUserPackageEmail($user, $package, 'payment_not_received', $this->getContainer(), $subscriptionId);
                            continue;
                        }

                        $recurringSubscriptionInfo = array('subscriptionID' => $subscriptionId);
                        $cyberSourceReply = $cyberSourceManager->getCyberSourceReplyForSubscriptionRecurring($subscriptionId, $package);

                        if ($cyberSourceReply && $cyberSourceReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE) {
                            echo "Subsciption package renewed successully ".$user->getId().'-'.$user->getEmail()."\n";
                            $cartCode = $this->updatePaymentRecords($user, $package, $payment, $cyberSourceReply, $subscriptionId);
                            $userPackage = $this->em->getRepository('FaUserBundle:UserPackage')->assignPackageToUser($user, $package, 'package-renew-thourgh-recurring', null, false, $this->getContainer());
                            $this->em->getRepository('FaUserBundle:UserPackage')->sendUserPackageBillingEmail($user, $package, $userPackage, $cartCode, $subscriptionId, 'subscription_billing_receipt', $this->getContainer());
                        } else {
                            echo "Subscription failed for user".$payment->getId()."\n";
                            $this->em->getRepository('FaUserBundle:UserPackage')->sendUserPackageEmail($user, $package, 'payment_not_received', $this->getContainer(), $subscriptionId);
                            $this->em->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($user, 'downgraded-to-free-package-on-fail-payment', $this->getContainer());
                        }
                    }
                    $this->getContainer()->get('doctrine')->getManager()->flush();
                }

                $last_id = $userPackage->getId();
            } else {
                $done = true;
            }

            $this->em->flush();
            $this->em->clear();
        }
    }

    private function updatePaymentRecords($user, $package, $payment, $cyberSourceReply, $subscriptionId)
    {
        $cartCode = $this->em->getRepository('FaPaymentBundle:Cart')->generateCartCode();
        $currency = CommonManager::getCurrencyCode($this->getContainer());

        $paymentValue = array('cyberSourceReply' => $cyberSourceReply, 'subscriptionID' => $subscriptionId);
        $payment = new Payment();
        $payment->setUser($user);
        $payment->setCartCode($cartCode);
        $payment->setStatus(1);
        $payment->setCurrency($currency);
        $payment->setAmount($cyberSourceReply->ccCaptureReply->amount);
        $payment->setPaymentMethod(PaymentRepository::PAYMENT_METHOD_CYBERSOURCE_RECURRING);
        $payment->setValue(serialize($paymentValue));
        $payment->setIsActionByAdmin(1);
        $this->em->persist($payment);

        $paymentCyberSource = new PaymentCyberSource();
        $paymentCyberSource->setPayment($payment);
        $paymentCyberSource->setIp('127.0.0.1');
        $paymentCyberSource->setRequestId($cyberSourceReply->requestID);
        $paymentCyberSource->setRequestToken($cyberSourceReply->requestToken);
        $paymentCyberSource->setValue(serialize($cyberSourceReply));
        $this->em->persist($paymentCyberSource);

        $vat = $this->em->getRepository('FaCoreBundle:Config')->getVatAmount();
        $payementTranscation = new PaymentTransaction();
        $payementTranscation->setUser($user);
        $payementTranscation->setAmount($cyberSourceReply->ccCaptureReply->amount);
        $payementTranscation->setTransactionId(CommonManager::generateHash());
        $payementTranscation->setPayment($payment);
        $payementTranscation->setVat($vat);
        $payementTranscation->setVatAmount(($cyberSourceReply->ccCaptureReply->amount * $vat) / 100);
        $this->em->persist($payementTranscation);

        $value = $this->em->getRepository('FaPromotionBundle:Package')->getPackageInfoForTransaction($package, null, $user, false);
        $paymentTransactionDetail = new PaymentTransactionDetail();
        $paymentTransactionDetail->setPaymentFor(TransactionDetailRepository::PAYMENT_FOR_SHOP);
        $paymentTransactionDetail->setPaymentTransaction($payementTranscation);
        $paymentTransactionDetail->setAmount($cyberSourceReply->ccCaptureReply->amount);
        $paymentTransactionDetail->setValue(serialize($value));
        $this->em->persist($paymentTransactionDetail);
        $this->em->flush();
        return $cartCode;
    }


    /**
     * Get billing array.
     *
     * @param object  $loggedinUser   Logged in user object.
     * @param object  $values  array
     * @param boolean $forAddressBook Flag for user address book.
     *
     * @return array
     */
    private function getBillToArray($user, $values, $forAddressBook = false)
    {
        $billTo    = array();
        $firstName = $user->getFirstName() ? $user->getFirstName() : $user->getUserName();
        $lastName  = $user->getLastName() ? $user->getLastName() : $user->getUserName();

        if (isset($values['user_address_info']['street_address'])) {
            $street1 = trim($values['user_address_info']['street_address'].', '.$values['user_address_info']['street_address_2'], ', ');
        } elseif (isset($values['user_address_info']['street1'])) {
            $street1 = $values['user_address_info']['street1'];
        }

        $billTo['firstName'] = $firstName;
        $billTo['lastName']  = $lastName;
        if (!$forAddressBook) {
            $billTo['street1'] = $street1;
        } else {
            if (isset($values['user_address_info']['street_address'])) {
                $billTo['street_address']   = $values['user_address_info']['street_address'];
                $billTo['street_address_2'] = $values['user_address_info']['street_address'];
            } elseif (isset($values['user_address_info']['street_address'])) {
                $billTo['street_address'] = $street1;
            }
        }
        $billTo['city'] = $values['user_address_info']['city'];
        if ($values['user_address_info']['state']) {
            $billTo['state'] = $values['user_address_info']['state'];
        }
        $billTo['postalCode'] = $values['user_address_info']['postalCode'];
        $billTo['country']    = 'UK';
        $billTo['email']      = $user->getEmail();
        $billTo['ipAddress']  = '127.0.0.1';

        return array_map('trim', $billTo);
    }


    /**
     * Get images.
     *
     * @param integer $last_id           Last id.
     * @param integer $QUERY_BATCH_SIZE  Size of query batch.
     */
    public function getUserSubscriptions($last_id, $QUERY_BATCH_SIZE, $user_id)
    {
        $q = $this->em->getRepository('FaUserBundle:UserPackage')->createQueryBuilder(UserPackageRepository::ALIAS);
        $q->andWhere(UserPackageRepository::ALIAS.'.status = :status');
        $q->setParameter('status', 'A');
        $q->andWhere(UserPackageRepository::ALIAS.'.id > :id');
        $q->setParameter('id', $last_id);
        if (!$user_id) {
            $q->andWhere(UserPackageRepository::ALIAS.'.expires_at < :expires_at_from OR '.UserPackageRepository::ALIAS.'.expires_at < :expires_at_to');
            $q->setParameter('expires_at_from', strtotime('+1 day'));
            $q->setParameter('expires_at_to', strtotime('+3 day'));
        }
        $q->addOrderBy(UserPackageRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);

        if ($user_id != '') {
            $q->andWhere(UserPackageRepository::ALIAS.'.user = :user');
            $q->setParameter('user', $user_id);
        }

        return $q->getQuery()->getResult();
    }
}
