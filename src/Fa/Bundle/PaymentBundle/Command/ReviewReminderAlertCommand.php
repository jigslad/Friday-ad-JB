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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send renew your ad alert to users for before given time
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ReviewReminderAlertCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:review-reminder')
        ->setDescription("Send ad renewal alert")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('order_id', null, InputOption::VALUE_OPTIONAL, 'Order ID just for testing', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send review reminder email to buyer

Command:
 - php app/console fa:review-reminder
EOF
        );
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

        $identifier  = 'you_need_to_leave_a_review_buyer';
        $parameters  = $this->em->getRepository('FaEmailBundle:EmailTemplate')->getSchedualParameterArray($identifier, CommonManager::getCurrentCulture($this->getContainer()));
        $hours    = isset($parameters['x_hours_after_order_closed']) && $parameters['x_hours_after_order_closed'] > 0 ? $parameters['x_hours_after_order_closed'] : 96;

        if ($hours) {
            $closed_at = strtotime('-'.$hours.' hours') ;
        }

        $QUERY_BATCH_SIZE = 100;
        $done             = false;
        $last_id          = 0;

        while (!$done) {
        	if ($input->getOption('order_id') > 0) {
        		$orders = $this->getTestOrders($input->getOption('order_id'), $QUERY_BATCH_SIZE);
        		$done = true;
        	} else {
        		$orders = $this->getClosedOrders($last_id, $QUERY_BATCH_SIZE, $closed_at);
        	}
            
            if ($orders) {
                foreach ($orders as $order) {
                    $paymentTransactions = $this->em->getRepository('FaPaymentBundle:PaymentTransaction')->findOneBy(array('payment' => $order->getId()));

                    if ($paymentTransactions) {
                        $ad = $paymentTransactions->getAd();

                        if ($ad) {
                            $check = $this->em->getRepository('FaUserBundle:UserReview')->isAdReviewable($ad->getId(), $order->getUser()->getId(), $order->getSellerUserId());
                            if (!$check) {
                                $this->em->getRepository('FaUserBundle:UserReview')->sendReviewForSellerEmail($order, $this->getContainer());
                                echo 'Review reminder sent for -> '.$order->getCartCode()."\n";
                            }
                        }
                        $order->setIsReviewReminderSent(1);
                        $this->em->persist($order);
                    }
                }
                $last_id = $order->getId();
            } else {
                $done = true;
            }
            $this->em->flush();
            $this->em->clear();
        }
    }

    /**
     * Get images.
     *
     * @param integer $last_id           Last id.
     * @param integer $QUERY_BATCH_SIZE  Size of query batch.
     */
    public function getClosedOrders($last_id, $QUERY_BATCH_SIZE, $closed_at)
    {
        $q = $this->em->getRepository('FaPaymentBundle:Payment')->createQueryBuilder(PaymentRepository::ALIAS);
        $q->andWhere(PaymentRepository::ALIAS.'.payment_method = :payment_method');
        $q->andWhere(PaymentRepository::ALIAS.'.buy_now_status_id = :buy_now_status');
        $q->andWhere(PaymentRepository::ALIAS.'.is_review_reminder_sent = :is_review_reminder_sent');
        $q->andWhere(PaymentRepository::ALIAS.'.id > :id');
        $q->setParameter('is_review_reminder_sent', 0);
        $q->setParameter('payment_method', PaymentRepository::PAYMENT_METHOD_PAYPAL_ADAPTIVE);
        $q->setParameter('buy_now_status', PaymentRepository::BN_CLOSED_ID);
        $q->setParameter('id', $last_id);
        $q->addOrderBy(PaymentRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);
        return $q->getQuery()->getResult();
    }
    
    /**
     * Get images.
     *
     * @param integer $order_id          Order id
     * @param integer $QUERY_BATCH_SIZE  Size of query batch.
     */
    public function getTestOrders($order_id, $QUERY_BATCH_SIZE)
    {
        $q = $this->em->getRepository('FaPaymentBundle:Payment')->createQueryBuilder(PaymentRepository::ALIAS);
        $q->andWhere(PaymentRepository::ALIAS.'.id = :id');
        $q->setParameter('payment_method', PaymentRepository::PAYMENT_METHOD_PAYPAL_ADAPTIVE);
        $q->andWhere(PaymentRepository::ALIAS.'.payment_method = :payment_method');
        $q->setParameter('id', $order_id);
        return $q->getQuery()->getResult();
    }
}
