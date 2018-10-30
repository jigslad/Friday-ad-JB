<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send renew your ad alert to users for before given time
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdSendRefreshAdEmailCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:ad-refresh-email')
        ->setDescription("Send ad refresh emails")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_published_days', null, InputOption::VALUE_REQUIRED, 'old days after ad published', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send ad refresh email for manual refresh

Command:
 - php app/console fa:send:ad-refresh-email --last_published_days=7
 - php app/console fa:send:ad-refresh-email --last_published_days=14
 - php app/console fa:send:ad-refresh-email --last_published_days=21
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

        $offset = $input->getOption('offset');
        $days   = $input->getOption('last_published_days');

        if (!in_array($days, array('7', '14', '21'))) {
            $output->writeln('Please enter last_published_days(7 or 14 or 21)', true);
            exit;
        }

        $searchParam                     = array();
        $searchParam['entity_ad_status'] = array('id' => array(\Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID));

        if ($days) {
            $date = date('d/m/Y', strtotime('-'.$days.' days'));
            $searchParam['ad']['published_at_from_to'] =  $date.'|'.$date;
        }

        // Skip detached ads and feed ads for sending fresh email.
        $searchParam['ad']['is_detached_ad'] = 0;
        $searchParam['ad']['is_feed_ad']     = 0;
        $searchParam['ad']['is_blocked_ad']  = 0;

        if (isset($offset)) {
            $this->sendAdRefreshEmailWithOffset($searchParam, $input, $output);
        } else {
            $this->sendAdRefreshEmail($searchParam, $input, $output);

            //send userwise email
            $emailTemplate = 'ad_refresh_upsell_'.$days.'_days';
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:process-email-queue --email_identifier="'.$emailTemplate.'"';
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
    protected function sendAdRefreshEmailWithOffset($searchParam, $input, $output)
    {
        $qb     = $this->getAdQueryBuilder($searchParam);
        $step   = 100;
        $offset = $input->getOption('offset');
        $days   = $input->getOption('last_published_days');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();

        $refreshedAds = array();
        foreach ($ads as $ad) {
            $emailTemplate = 'ad_refresh_upsell_'.$days.'_days';
            $duration      = CommonManager::encryptDecrypt('R', time());
            if (($this->em->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($ad->getId()) == false) || $ad->getWeeklyRefreshAt() == null) {
                //$this->em->getRepository('FaAdBundle:Ad')->sendRefreshAdEmail($ad, $emailTemplate, $duration, $this->getContainer());
                $user = ($ad->getUser() ? $ad->getUser() : null);
                $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue($emailTemplate, $user, $ad, $this->getContainer());
                $output->writeln('Ad refresh email has been sent to AD ID: '.$ad->getId().' User Id:'.($user ? $user->getId() : null), true);
            } else {
                $refreshedAds[] = $ad->getId();
            }
        }
        $this->em->clear();

        if (count($refreshedAds)) {
            $output->writeln('', true);
            $output->writeln('Already refreshed ad ids : '.implode(', ', $refreshedAds), true);
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
    protected function sendAdRefreshEmail($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:send:ad-refresh-email '.$commandOptions;
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
        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad' => array('id' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($this->em->getRepository('FaAdBundle:Ad'), $data);

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
