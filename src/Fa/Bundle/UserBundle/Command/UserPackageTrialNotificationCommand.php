<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

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

/**
 * This command is used to renew subscription packages through recurring payment
 *
 * php app/console fa:user-package-trial-notification
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPackageTrialNotificationCommand extends ContainerAwareCommand
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:user-package-trial-notification')
        ->setDescription('Subscription package expiration')
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

        $identifier    = 'free_trial_ending_5_x_days_have_payment_source';
        $parameters  = $this->em->getRepository('FaEmailBundle:EmailTemplate')->getSchedualParameterArray($identifier, CommonManager::getCurrentCulture($this->getContainer()));
        $ending_days = isset($parameters['x_days_before_end_of_trail_period']) && $parameters['x_days_before_end_of_trail_period'] > 0 ? $parameters['x_days_before_end_of_trail_period'] : 5;

        while (!$done) {
            $userPackages = $this->getUserSubscriptions($last_id, $QUERY_BATCH_SIZE, $user_id, $ending_days);
            if ($userPackages) {
                foreach ($userPackages as $userPackage) {
                    $user    = $userPackage->getUser();
                    $package = $userPackage->getPackage();

                    $payment = $userPackage->getPayment();
                    if ($payment) {
                        $values =  unserialize($payment->getValue());
                        $subscriptionId = null;

                        if (isset($values['cyber_source_response']->paySubscriptionCreateReply) && isset($values['cyber_source_response']->paySubscriptionCreateReply->subscriptionID) && $values['cyber_source_response']->paySubscriptionCreateReply->subscriptionID != '') {
                            $subscriptionId = $values['cyber_source_response']->paySubscriptionCreateReply->subscriptionID;
                        } elseif (isset($values['subscriptionID']) && $values['subscriptionID'] != '') {
                            $subscriptionId = $values['subscriptionID'];
                        }

                        echo 'Trial is going to end notification sent to user -> '.$user->getId();
                        $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('free_trial_ends_in_5_days', null, $user->getId());
                        //send email only if ad has user and status is active.
                        if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer())) {
                            $this->em->getRepository('FaUserBundle:UserPackage')->sendUserPackageEmail($userPackage->getUser(), $userPackage->getPackage(), $identifier, $this->getContainer(), $subscriptionId);
                        }
                    }
                }
                $last_id = $userPackage->getId();
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
    public function getUserSubscriptions($last_id, $QUERY_BATCH_SIZE, $user_id, $ending_days = 5)
    {
        $q = $this->em->getRepository('FaUserBundle:UserPackage')->createQueryBuilder(UserPackageRepository::ALIAS);
        $q->andWhere(UserPackageRepository::ALIAS.'.status = :status');
        $q->setParameter('status', 'A');
        $q->andWhere(UserPackageRepository::ALIAS.'.id > :id');
        $q->andWhere(UserPackageRepository::ALIAS.'.trial = :trial');
        $q->andWhere(UserPackageRepository::ALIAS.'.expires_at < :expires_at');
        $q->setParameter('id', $last_id);
        $q->setParameter('trial', 1);
        $q->setParameter('expires_at', strtotime('+'.$ending_days.' days'));
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
