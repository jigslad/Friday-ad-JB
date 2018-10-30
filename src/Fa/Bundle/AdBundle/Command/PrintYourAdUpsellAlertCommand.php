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
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\adPrintRepository;

/**
 * This command is used Send email at Monday 9am to anyone with a live advert which has not already been booked into a print product and is within the print location group.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PrintYourAdUpsellAlertCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:print-your-ad-upsell-alert')
        ->setDescription("Send email at Monday 9am to anyone with a live advert which has not already been booked into a print product and is within the print location group.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Send email at Monday 9am to anyone with a live advert which has not already been booked into a print product and is within the print location group.

Command:
 - php app/console fa:update:print-your-ad-upsell-alert
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
        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        //get options passed in command
        $offset = $input->getOption('offset');

        // Last week ads
        $fromDate = date('d/m/Y', strtotime('-1 week'));
        $toDate   = date('d/m/Y', strtotime('-1 days'));

        $searchParam  = array();

        $searchParam['ad_print'] = array('is_paid' => 0);
        $searchParam['ad_print__ad'] = array(
                                           'status'             => \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID,
                                           'is_detached_ad'     => 0,
                                           'is_feed_ad'         => 0,
                                           'is_blocked_ad'      => 0,
                                           'created_at_from_to' => $fromDate.'|'.$toDate
                                       );

        if (isset($offset)) {
            $this->sendEmailWithOffset($searchParam, $input, $output);
        } else {
            $this->sendEmail($searchParam, $input, $output);
            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:process-email-queue --email_identifier="print_your_ad_upsell"';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Send email.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendEmailWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getAdQueryBuilder($searchParam);
        $step          = 100;
        $offset        = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $printAds = $qb->getQuery()->getResult();
        foreach ($printAds as $printAd) {
            $ad     = $printAd->getAd();
            $adUser = $ad->getUser();
            //send email only if ad has user and status is active.
            if ($adUser && CommonManager::checkSendEmailToUser($adUser->getId(), $this->getContainer())) {
                //$entityManager->getRepository('FaAdBundle:Ad')->sendEmailForPrintAdPackage($ad, $this->getContainer());
                $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue('print_your_ad_upsell', $adUser, $ad, $this->getContainer());
                $output->writeln('Email has been sent for ad id: '.$ad->getId().' User Id:'.($adUser ? $adUser->getId() : null), true);
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send email.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendEmail($searchParam, $input, $output)
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:print-your-ad-upsell-alert '.$commandOptions;
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
        $entityManager     = $this->getContainer()->get('doctrine')->getManager();
        $adPrintRepository = $entityManager->getRepository('FaAdBundle:AdPrint');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad_print' => array('ad' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adPrintRepository, $data);

        $queryBuilder = $searchManager->getQueryBuilder();
        $queryBuilder->leftJoin('FaAdBundle:AdUserPackage', AdUserPackageRepository::ALIAS, 'WITH', AdUserPackageRepository::ALIAS.'.ad_id = '.AdRepository::ALIAS.'.id')
                     ->andWhere(AdUserPackageRepository::ALIAS.'.status = :ad_user_package_status')
                     ->setParameter('ad_user_package_status', AdUserPackageRepository::STATUS_ACTIVE)
                     ->andWhere(AdUserPackageRepository::ALIAS.'.price = 0')
                     ->addGroupBy(adPrintRepository::ALIAS.'.ad');

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

        return count($qb->getQuery()->getResult());
    }
}
