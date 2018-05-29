<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;

/**
 * This command is used to update ad report statistics.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAdReportSourceLatestCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 1000;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * History entity manager
     *
     * @var object
     */
    private $historyEntityManager;

    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * History db name
     *
     * @var object
     */
    private $historyDbName;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-report-latest-source-latest-source')
        ->setDescription("Update ad report latest source.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Date (Y-m-d) for which need to add / update statistics', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update ad report statistics.

Command:
 - php app/console fa:update:ad-report-latest-source --date="2015-04-28"
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
        // set entity manager.
        $this->entityManager        = $this->getContainer()->get('doctrine')->getManager();
        $this->historyEntityManager = $this->getContainer()->get('doctrine')->getManager('history');
        $this->historyDbName        = $this->getContainer()->getParameter('database_name_history');
        $this->mainDbName           = $this->getContainer()->getParameter('database_name');


        //get arguments passed in command
        $date = $input->getOption('date');
        list($startDate, $endDate) = $this->getDateInTimeStamp($date);

        $historyCountSql = "select id, ad_id  from ".$this->historyDbName.".ad_report_daily where created_at  BETWEEN ".$startDate."
            AND ".$endDate." and is_renewed = 1 and source = 'migrated' and source_latest = 'migrated';";
        $stmt = $this->executeRawQuery($historyCountSql, $this->historyEntityManager);
        $historyCountRes = $stmt->fetchAll();
        $output->writeln('Date : '.$date, true);
        $output->writeln('Total history count: '.count($historyCountRes), true);
        $adReportDailyArray = array();
        foreach ($historyCountRes as $historyDetail) {
            $adReportDailyArray[$historyDetail['ad_id']] = $historyDetail['id'];
        }

        $paymentSql = "select MAX(pt.id), pt.ad_id, p.is_action_by_admin from ".$this->mainDbName.".payment_transaction as pt
            inner join ".$this->mainDbName.".payment as p on (pt.payment_id = p.id)
                where p.created_at  BETWEEN ".$startDate."
            AND ".$endDate." and pt.ad_id in (select ad_id from ".$this->historyDbName.".ad_report_daily where created_at  BETWEEN ".$startDate."
            AND ".$endDate." and is_renewed = 1 and source = 'migrated' and source_latest = 'migrated') group by pt.ad_id;";
        $stmt = $this->executeRawQuery($paymentSql, $this->historyEntityManager);
        $paymentRes = $stmt->fetchAll();
        $output->writeln('Total history count: '.count($paymentRes), true);
        if (count($paymentRes)) {
            $updateCnt = 1;
            foreach ($paymentRes as $paymentDetail) {
                $latestSource = AdRepository::SOURCE_PAA;
                if ($paymentDetail['is_action_by_admin'] == 1) {
                    $latestSource = AdRepository::SOURCE_ADMIN;
                }
                if (isset($adReportDailyArray[$paymentDetail['ad_id']])) {
                    $historyUpdateSql = "update ".$this->historyDbName.".ad_report_daily set source_latest = '".$latestSource."' where id = ".$adReportDailyArray[$paymentDetail['ad_id']];
                    $this->executeRawQuery($historyUpdateSql, $this->historyEntityManager);
                    $output->writeln($updateCnt.')Latest source updated for ad id: '.$paymentDetail['ad_id'], true);
                    $updateCnt++;
                }
            }
        }
    }

    /**
     * Execute raw query.
     *
     * @param string  $sql           Sql query to run.
     * @param object  $entityManager Entity manager.
     *
     * @return object
     */
    private function executeRawQuery($sql, $entityManager)
    {
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Get date in time stamp
     *
     * @param string $date Date.
     *
     * @return array
     */
    private function getDateInTimeStamp($date)
    {
        if ($date) {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime($date)));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime($date)));
        } else {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60)));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60)));
        }

        return array($startDate, $endDate);
    }
}
