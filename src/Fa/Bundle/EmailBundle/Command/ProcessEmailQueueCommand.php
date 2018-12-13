<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\RoleRepository;

/**
 * This command is used to send queued email to users.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ProcessEmailQueueCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:process-email-queue')
        ->setDescription("Send queued emails to user.")
        ->addOption('email_identifier', null, InputOption::VALUE_REQUIRED, 'Email template identifier', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send queued email to users.

Command:
 - php app/console fa:process-email-queue --email_identifier="ad_expires_tomorrow"
 - php app/console fa:process-email-queue --email_identifier="ad_is_expired"
 - php app/console fa:process-email-queue --email_identifier="renewal_reminder"
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

        $offset   = $input->getOption('offset');
        $emailIdentifier = $input->getOption('email_identifier');

        $searchParam                              = array();
        $searchParam['email_queue']['identifier'] =  $emailIdentifier;

        if (isset($offset)) {
            $this->processEmailQueueWithOffset($searchParam, $input, $output);
        } else {
            $this->processEmailQueue($searchParam, $input, $output);
        }
    }

    /**
     * Send ad expiration alert before one day with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function processEmailQueueWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getEmailQueueQueryBuilder($searchParam);
        $step        = 5;
        $offset      = 0;

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step)
            ->groupBy($qb->getRootAlias().'.user');

        $emailQueues = $qb->getQuery()->getResult();

        foreach ($emailQueues as $emailQueue) {
            try {
                $user = $emailQueue->getUser();
                $userRoleId = ($user ? $user->getRole()->getId() : 0);
                //send email only if ad has user and status is active.
                if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer()) && $userRoleId!=RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID) {
                    switch ($searchParam['email_queue']['identifier']) {
                        case 'ad_expires_tomorrow':
                            $this->em->getRepository('FaAdBundle:Ad')->sendExpireTomorrowAlertEmailByUser($user, $this->getContainer());
                            $output->writeln('Renewal email sent for User ID: '.$user->getId(), true);
                            break;
                        case 'ad_is_expired':
                            $this->em->getRepository('FaAdBundle:Ad')->sendExpirationEmailByUser($user, $this->getContainer());
                            $output->writeln('Expired ads email sent for User ID: '.$user->getId(), true);
                            break;
                        case 'ad_needs_renewing_4_days_left':
                            $this->em->getRepository('FaAdBundle:Ad')->sendRenewalEmailByUser($user, $this->getContainer());
                            $output->writeln('Renewal email sent to for User ID: '.$user->getId(), true);
                            break;
                        case 'print_your_ad_upsell':
                            $this->em->getRepository('FaAdBundle:Ad')->sendEmailForPrintAdPackageByUser($user, $this->getContainer());
                            $output->writeln('Print your ad upsell email sent to for User ID: '.$user->getId(), true);
                            break;
                        case 'confirmation_of_ad_refreshing':
                            $this->em->getRepository('FaAdBundle:Ad')->sendRefreshAdEmailByUser($user, 'confirmation_of_ad_refreshing', null, $this->getContainer());
                            $output->writeln('Refresh date is updated email sent to for User ID: '.$user->getId(), true);
                            break;
                        case 'ad_refresh_upsell_7_days':
                            $duration      = CommonManager::encryptDecrypt('R', time());
                            $this->em->getRepository('FaAdBundle:Ad')->sendRefreshAdEmailByUser($user, 'ad_refresh_upsell_7_days', $duration, $this->getContainer());
                            $output->writeln('Ad refresh email has sent to for User ID: '.$user->getId(), true);
                            break;
                        case 'ad_refresh_upsell_14_days':
                            $duration      = CommonManager::encryptDecrypt('R', time());
                            $this->em->getRepository('FaAdBundle:Ad')->sendRefreshAdEmailByUser($user, 'ad_refresh_upsell_14_days', $duration, $this->getContainer());
                            $output->writeln('Ad refresh email has sent to for User ID: '.$user->getId(), true);
                            break;
                        case 'ad_refresh_upsell_21_days':
                            $duration      = CommonManager::encryptDecrypt('R', time());
                            $this->em->getRepository('FaAdBundle:Ad')->sendRefreshAdEmailByUser($user, 'ad_refresh_upsell_21_days', $duration, $this->getContainer());
                            $output->writeln('Ad refresh email has sent to for User ID: '.$user->getId(), true);
                            break;
                        case 'renewal_reminder':
                            $this->em->getRepository('FaAdBundle:Ad')->sendRenewalReminderEmailByUser($user, 'renewal_reminder', $this->getContainer());
                            $output->writeln('Renewal Reminder email has sent to the User ID: '.$user->getId(), true);
                            break;
                            
                        case 'ad_is_received_live_paid_print':
                        case 'ad_is_received_live_paid_online_only':
                        case 'ad_is_received_live_free_ad':
                        case 'ad_is_received_live_adult_private':
                            $this->em->getRepository('FaAdBundle:Ad')->sendLiveAdPackageEmailByUser($user, $searchParam['email_queue']['identifier'], $this->getContainer());
                            $output->writeln('Ad package email sent to for User ID: '.$user->getId(), true);
                            break;
                    }
                } else {
                    $this->em->getRepository('FaEmailBundle:EmailQueue')->removeFromEmailQueue($searchParam['email_queue']['identifier'], $user, $emailQueue->getId());
                    $output->writeln('Email Queue removed for user & email template: '.$user->getId().' '.$emailQueue->getIdentifier(), true);
                }
            } catch (\Exception $e) {
                $this->em->getRepository('FaEmailBundle:EmailQueue')->removeFromEmailQueue($searchParam['email_queue']['identifier'], $user, $emailQueue->getId());
                $output->writeln('Error occurred during subtask:'.$e->getMessage(), true);
            }
        }
        $this->em->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send ad expiration alert before one day.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function processEmailQueue($searchParam, $input, $output)
    {
        $count     = $this->getEmailQueueCount($searchParam);
        $step      = 5;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('total users : '.$count, true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'="'.$value.'"';
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:process-email-queue '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getEmailQueueQueryBuilder($searchParam)
    {
        $emailQueueRepository  = $this->em->getRepository('FaEmailBundle:EmailQueue');

        $data                  = array();
        $data['query_filters'] = $searchParam;

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($emailQueueRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getEmailQueueCount($searchParam)
    {
        $qb = $this->getEmailQueueQueryBuilder($searchParam);
        $qb->select('COUNT( DISTINCT '.$qb->getRootAlias().'.user)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
