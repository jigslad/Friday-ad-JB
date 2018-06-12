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
use Fa\Bundle\AdBundle\Repository\AdViewCounterRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to update user report statistics.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAdEnquiryDailyReportCommand extends ContainerAwareCommand
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
        ->setName('fa:update:ad-enquiry-daily-report')
        ->setDescription("Update ad enquiry report statistics.")
        ->addArgument('action', InputArgument::OPTIONAL, 'beforeoneday')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', "512M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date (Y-m-d) for which need to add / update statistics', null)
        ->addOption('records_to_be_processed', null, InputOption::VALUE_OPTIONAL, 'Number of records to be inserted', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update ad enquiry report statistics.

Command:
 - php app/console fa:update:ad-enquiry-daily-report beforeoneday
 - php app/console fa:update:ad-enquiry-daily-report --date="2015-04-28"
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

        $adEnquiryReportTableName      = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdEnquiryReport')->getTableName();
        $adEnquiryReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdEnquiryReportDaily')->getTableName();

        //get arguments passed in command
        $offset = $input->getOption('offset');
        $date   = $input->getOption('date');
        $action = $input->getArgument('action');

        if ($action || $date) {
            if (!isset($offset)) {
                $start_time = time();
                $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
            }

            // update ad enquiry daily report.
            if (isset($offset)) {
                $this->updateAdEnquiryDailyWithOffset($input, $output);
            } else {
                $this->updateAdEnquiryDaily($input, $output);
            }

            if (!isset($offset)) {
                $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
                $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
            }
        } elseif (!$action && !$date) {
            $output->writeln('Please enter either action argument or date option.', true);
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
     * Update ad enquiry daily.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdEnquiryDaily($input, $output)
    {
        $action = $input->getArgument('action');
        $date   = $input->getOption('date');

        if ($action == 'beforeoneday' && $date == '') {
            $date = date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60));
        }

        $count  = $this->getAdViewAdIdsCount($action, $date);
        //$input->setOption("records_to_be_processed", $count);
        $output->writeln('##### NUMBER OF RECORDS TO BE PROCESSED IN THIS BATCH ARE: '.$count.' #####', true);

        if ($count > 0) {
            $created_at = ($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60));
            $adEnquiryReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdEnquiryReportDaily')->getTableName();
            $deleteSQL = "DELETE FROM ".$this->historyDbName.".".$adEnquiryReportDailyTableName." WHERE created_at = ".$created_at;
            $this->executeRawQuery($deleteSQL, $this->historyEntityManager);
            $output->writeln($count.' records deleted successfully....', true);
            $output->writeln('');
        }

        for ($i = 0; $i < $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-enquiry-daily-report '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update user total ad count with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdEnquiryDailyWithOffset($input, $output)
    {
        $action                        = $input->getArgument('action');
        $date                          = $input->getOption('date');
        $adEnquiryReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdEnquiryReportDaily')->getTableName();
        $offset                        = $input->getOption('offset');
        $adViewCounterArray            = array();
        $numberOfRecordsToBeProcessed  = $input->getOption('records_to_be_processed');

        //for ad_enquiry_daily_report table
        $created_at = ($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60));
        list($startDate, $endDate) = $this->getDateInTimeStamp($date);
        $adViewCounterArray = $this->entityManager->getRepository('FaAdBundle:AdViewCounter')->getAdViewCounterArrayByDate($date, $offset, $this->limit);
        if ($adViewCounterArray && count($adViewCounterArray)) {
            $adViewedIdsArray = array_keys($adViewCounterArray);
            $adIdArray = array();
            foreach ($adViewedIdsArray as $adId) {
                $adIdArray[] = $adId;
            }
            $adUserIds = $this->entityManager->getRepository('FaAdBundle:Ad')->getUserIdArrayByAdIds($adIdArray);
            $adUserPackageDetails = $this->entityManager->getRepository('FaUserBundle:UserPackage')->getShopPackageDetailByUserIdForAdReport($adUserIds);
            $adUserSiteViewCounters = $this->entityManager->getRepository('FaUserBundle:UserSiteViewCounter')->getUserSiteViewCounterArrayByDate($startDate, $endDate, $adUserIds);
            $adUserRoleDetails = $this->entityManager->getRepository('FaUserBundle:User')->getUserRoleIdArrayByUserIds($adUserIds);
            if ($offset == 0) {
                $batch = 1;
            } else {
                $batch = ($offset / $this->limit) + 1;
            }
            $insertSQL = "INSERT INTO ".$this->historyDbName.".".$adEnquiryReportDailyTableName.
            "(ad_id, view, contact_seller_click, call_click, email_send_link, social_share, web_link_click, created_at, updated_at, package_name, package_price, user_site_view_counter, role_id)
            VALUES ";
            $valuesSTR = '';
            $output->writeln('Batch '.$batch.' records insertion started ('.date("d-m-Y H:i:s").')...', true);
            foreach ($adViewedIdsArray as $adId) {
                $view = (isset($adViewCounterArray[$adId]) ? $adViewCounterArray[$adId] : 0);
                $adUserId = (isset($adUserIds[$adId]) ? $adUserIds[$adId] : null);
                $adUserPackageDetail = (isset($adUserPackageDetails[$adUserId]) ? $adUserPackageDetails[$adUserId] : array());
                $adUserRoleId = (isset($adUserRoleDetails[$adUserId]) ? $adUserRoleDetails[$adUserId] : null);
                $adUserSiteViewCounter = (isset($adUserSiteViewCounters[$adUserId]) ? $adUserSiteViewCounters[$adUserId] : 0);
                $contact_seller_click = CommonManager::getCacheCounter($this->getContainer(), 'ad_enquiry_contact_seller_click_'.$created_at.'_'.$adId);
                $call_click = CommonManager::getCacheCounter($this->getContainer(), 'ad_enquiry_call_click_'.$created_at.'_'.$adId);
                $email_send_link = CommonManager::getCacheCounter($this->getContainer(), 'ad_enquiry_email_send_link_'.$created_at.'_'.$adId);
                $social_share = CommonManager::getCacheCounter($this->getContainer(), 'ad_enquiry_social_share_'.$created_at.'_'.$adId);
                $web_link_click = CommonManager::getCacheCounter($this->getContainer(), 'ad_enquiry_web_link_click_'.$created_at.'_'.$adId);
                $valuesSTR .= "('".$adId."', '".$view."', '".$contact_seller_click."', '".$call_click."', '".$email_send_link."', '".$social_share."', '".$web_link_click."', '".$created_at."', '".$created_at."', '".(count($adUserPackageDetail) ? $adUserPackageDetail['package_text'] : null)."', '".(count($adUserPackageDetail) ? $adUserPackageDetail['price'] : null)."', '".$adUserSiteViewCounter."', '".$adUserRoleId."'),";

                //Remove each click counter
                $this->removeClickCountCache($created_at, $adId);
            }
            $valuesSTR = trim($valuesSTR, ',');
            $insertSQL = $insertSQL . $valuesSTR;
            $this->executeRawQuery($insertSQL, $this->historyEntityManager);
            $output->writeln('Batch '.$batch.' records insertion completed ('.date("d-m-Y H:i:s").')...', true);
            $processedRecords = $offset + count($adViewCounterArray);
            $remainingRecords = $numberOfRecordsToBeProcessed - $processedRecords;
            $output->writeln('Total '.$processedRecords.' records inserted successfully.... '.$remainingRecords.' remain now.', true);
            $output->writeln('');
        }
    }

    /**
     * Get total updated or created user.
     *
     * @param string $action       Action name.
     * @param string $date         Date.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getAdViewAdIdsCount($action, $date, $seaUpdateUserAdStatisticsCommandrchParams = array())
    {
        $adRepository = $this->entityManager->getRepository('FaAdBundle:AdViewCounter');
        $adViewCounterTableName = $this->entityManager->getClassMetadata('FaAdBundle:AdViewCounter')->getTableName();

        $sqlQuery = "SELECT COUNT(*) As total_ads FROM (
                        SELECT av.ad_id as ad_id
                            FROM ".$this->mainDbName.".".$adViewCounterTableName." av";

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $sqlQuery .= " WHERE av.created_at BETWEEN ".$startDate." AND ".$endDate;
        }
        $sqlQuery .= " GROUP BY av.ad_id) As SubTable";

        $stmt = $this->executeRawQuery($sqlQuery, $this->entityManager);
        $resultArray = $stmt->fetch();

        if ($resultArray && is_array($resultArray)) {
            return $resultArray['total_ads'];
        } else {
            return 0;
        }
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

    /**
     * Get user ad count results.
     *
     * @param integer $date date
     * @param integer $adId ad id
     *
     */
    protected function removeClickCountCache($date, $adId)
    {
        $this->getContainer()->get('fa.cache.manager')->delete('ad_enquiry_contact_seller_click_'.$date.'_'.$adId);
        $this->getContainer()->get('fa.cache.manager')->delete('ad_enquiry_call_click_'.$date.'_'.$adId);
        $this->getContainer()->get('fa.cache.manager')->delete('ad_enquiry_email_send_link_'.$date.'_'.$adId);
        $this->getContainer()->get('fa.cache.manager')->delete('ad_enquiry_social_share_'.$date.'_'.$adId);
        $this->getContainer()->get('fa.cache.manager')->delete('ad_enquiry_web_link_click_'.$date.'_'.$adId);
    }
}
