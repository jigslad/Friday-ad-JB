<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2017, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send 7 days after an ad first expires if the user has not reposted the ad and it is still inactive.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2017 Friday Media Group Ltd
 * @version 1.0
 */
class RenewalReminderCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:renewal-reminder')
        ->setDescription("This email will send 7 days after an ad first expires if the user has not reposted the ad and it is still inactive")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad ids', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_days', null, InputOption::VALUE_REQUIRED, 'update for last few days only', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Update date for ad weekly or monthly refresh.

Command:
 - php app/console fa:renewal-reminder --ad_id="xxxx"
 - php app/console fa:renewal-reminder --last_days=7
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
        if (!$input->getOption('last_days')) {
            $output->writeln('Please enter last_days.', true);
            return false;
        }

        //get options passed in command
        $reqids      = $input->getOption('ad_id');
        $offset   = $input->getOption('offset');
        $lastDays = $input->getOption('last_days');
        $date = strtotime(date('Y-m-d', strtotime('-'.$lastDays.' day')));

        $searchParam       = array();
        $searchParam['ad'] = array(
                                 'status'         => \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_EXPIRED_ID,
                                 'is_feed_ad'     => 0,
                             );
        if($lastDays) {
            $date = date('Y-m-d', strtotime('-'.$lastDays.' days'));
            $searchParam['ad']['expires_at_from_to'] =  $date.'|'.$date;
        }
        
        if ($reqids) {
            $searchParam['ad']['id'] = array('id' => $reqids);
        }

        if (isset($offset)) {
            $this->renewalReminderAdsWithOffset($searchParam, $input, $output);
        } else {
            $this->renewalReminderAds($searchParam, $input, $output);

            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:process-email-queue --email_identifier="renewal_reminder"';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function renewalReminderAdsWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 100;
        $offset      = 0;//$input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

//echo '<pre>';print_r($searchParam);die;
//echo $qb->getQuery()->getSql();die;
        $ads = $qb->getQuery()->getResult();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        foreach ($ads as $ad) {
            $user = ($ad->getUser() ? $ad->getUser() : null);
            $entityManager->persist($ad);
            $entityManager->flush($ad);
            $user_id = $user ? $user : null;
            if (!$ad->getIsFeedAd()) {
                $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('renewal_reminder', $ad->getId(), $user_id);
            }

            //send email only if ad has user and status is active and not feed ad.
            if (!$ad->getIsFeedAd() && $user && CommonManager::checkSendEmailToUser($user_id, $this->getContainer())) {
                //$this->em->getRepository('FaAdBundle:Ad')->sendRefreshAdEmail($ad, 'confirmation_of_ad_refreshing', null, $this->getContainer());
                $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue('renewal_reminder', $user, $ad, $this->getContainer());
                $output->writeln('Renewal Reminder for ad id: '.$ad->getId().' User Id:'.($user ? $user->getId() : null), true);
            }
            
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function renewalReminderAds($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:renewal-reminder '.$commandOptions;
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
    protected function getAdQueryBuilder($searchParam)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adRepository  = $entityManager->getRepository('FaAdBundle:Ad');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad' => array ('id' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdCount($searchParam)
    {
        $qb = $this->getAdQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
