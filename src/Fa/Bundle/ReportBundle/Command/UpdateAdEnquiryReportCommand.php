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
class UpdateAdEnquiryReportCommand extends ContainerAwareCommand
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
        ->setName('fa:update:ad-enquiry-report')
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
 - php app/console fa:update:ad-enquiry-report all
 - php app/console fa:update:ad-enquiry-report beforeoneday
 - php app/console fa:update:ad-enquiry-report --date="2015-04-28"
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

            if ($action == 'all') {
                if (!isset($offset)) {
                    $adEnquiryTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdEnquiryReport')->getTableName();
                    $adEnquiryDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdEnquiryReportDaily')->getTableName();
                    $output->writeln('Truncating ad_enquiry_report records...', true);
                    $this->executeRawQuery('TRUNCATE TABLE '.$this->historyDbName.'.'.$adEnquiryTableName.';', $this->historyEntityManager);
                    $output->writeln('Truncating ad_enquiry_daily_report records...', true);
                    $this->executeRawQuery('TRUNCATE TABLE '.$this->historyDbName.'.'.$adEnquiryDailyTableName.';', $this->historyEntityManager);
                    $this->updateAdEnquiry($input, $output);
                } else {
                    $this->updateAdEnquiryWithOffset($input, $output);
                }
            } else {
                if (isset($offset)) {
                    $this->updateAdEnquiryWithOffset($input, $output);
                } else {
                    $this->updateAdEnquiry($input, $output);
                }
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
     * Update ad enquiry.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdEnquiry($input, $output)
    {
        $action = $input->getArgument('action');
        $date   = $input->getOption('date');

        if ($action == 'beforeoneday' && $date == '') {
            $date = date('Y-m-d', (strtotime(date('Y-m-d'))- 24*60*60));
        }

        if ($action == 'all') {
            $count = $this->getAdIdsCountForAll($action, $date);
        } else {
            $count = $this->getAdIdsCount($action, $date);
        }

        $input->setOption("records_to_be_processed", $count);
        $output->writeln('##### NUMBER OF RECORDS TO BE PROCESSED IN THIS BATCH ARE: '.$count.' #####', true);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:ad-enquiry-report '.$commandOptions.' '.$input->getArgument('action');
            //$output->writeln($command, true);
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
    protected function updateAdEnquiryWithOffset($input, $output)
    {
        $action                        = $input->getArgument('action');
        $date                          = $input->getOption('date');
        $adTableName                   = $this->entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName();
        $adLocationTableName           = $this->entityManager->getClassMetadata('FaAdBundle:AdLocation')->getTableName();
        $adFavoriteTableName           = $this->entityManager->getClassMetadata('FaAdBundle:AdFavorite')->getTableName();
        $userTableName                 = $this->entityManager->getClassMetadata('FaUserBundle:User')->getTableName();
        $adEnquiryReportTableName      = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdEnquiryReport')->getTableName();
        $adEnquiryReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdEnquiryReportDaily')->getTableName();
        $offset                        = $input->getOption('offset');
        $numberOfRecordsToBeInserted   = $input->getOption('records_to_be_processed');
        $adIdsArray                    = array();
        $adEnquiryReportAdIds          = array();
        $nonAdEnquiryReportAdIds       = array();

        if ($action == 'all') {
            $adIdsResults = $this->getAdIdsResultsForAll($action, $date, $offset, $this->limit);
        } else {
            $adIdsResults = $this->getAdIdsResults($action, $date, $offset, $this->limit);
        }

        foreach ($adIdsResults as $adIdsResult) {
            $adIdsArray[] = $adIdsResult['ad_id'];
        }

        $totalRecords = count($adIdsArray);
        if ($totalRecords) {
            array_unique($adIdsArray);

            if ($action == 'all') {
                $nonAdEnquiryReportAdIds = $adIdsArray;
            } else {
                $adEnquiryReportAdIds    = $this->historyEntityManager->getRepository('FaReportBundle:AdEnquiryReport')->getAdEnquiryReportAdsByIds($adIdsArray);
                $nonAdEnquiryReportAdIds = array_diff($adIdsArray, $adEnquiryReportAdIds);
            }

            $alredyExistRecords = count($adEnquiryReportAdIds);
            $notExistRecords    = count($nonAdEnquiryReportAdIds);
            $output->writeln('##### AMONG OF '.$totalRecords.' RECORDS '.$alredyExistRecords.' ARE ALREADY EXISTS SO NOW '.$notExistRecords.' WOULD BE INSERTED #####', true);
            // inserting new user to ad_enquiry_report.
            if ($notExistRecords) {
                $created_at = ($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60));
                $this->executeRawQuery('INSERT INTO '.$this->historyDbName.'.'.$adEnquiryReportTableName.
                    ' (ad_id, title, description, category_id, postcode, county_id, town_id, saved_ads, title_word_count, title_character_count, description_word_count, description_character_count, use_privacy_number, created_at, user_id, username)
                    SELECT a.id, a.title, a.description, a.category_id, al.postcode, al.domicile_id, al.town_id,
                    COUNT(af.id) As saved_ads,
                    (LENGTH(a.title) - LENGTH(REPLACE(a.title, " ", ""))+1) As title_word_count,
                    LENGTH(a.title) As title_character_count,
                    (LENGTH(a.description) - LENGTH(REPLACE(a.description, " ", ""))+1) As description_word_count,
                    LENGTH(a.description) As description_character_count,
                    use_privacy_number, a.created_at, u.id, u.username
                    FROM '.$this->mainDbName.'.'.$adTableName.' a
                    LEFT JOIN '.$this->mainDbName.'.'.$adLocationTableName.' al ON al.ad_id = a.id
                    LEFT JOIN '.$this->mainDbName.'.'.$adFavoriteTableName.' af ON af.ad_id = a.id
                    LEFT JOIN '.$this->mainDbName.'.'.$userTableName.' u ON u.id = a.user_id
                    WHERE a.id IN ('.implode(',', $nonAdEnquiryReportAdIds).')
                    GROUP BY a.id ORDER BY a.id', $this->historyEntityManager);

                $insertedRecords  = $offset + count($nonAdEnquiryReportAdIds);
                $remainingRecords = $numberOfRecordsToBeInserted - $insertedRecords;
                $output->writeln('Total '.$insertedRecords.' records processed successfully.... '.$remainingRecords.' remain now.', true);
                $output->writeln('');
            }

            /*
            // updating new ads to ad_enquiry_report.
            if (count($adEnquiryReportAdIds) && $action != 'all') {
                $output->writeln('Updating ad_enquiry_report....', true);
                $adDetails = $this->entityManager->getRepository('FaAdBundle:Ad')->getAdDetailsByAdIdsForAdEnquiryReport($adEnquiryReportAdIds);

                if ($adDetails && count($adDetails)) {
                    $adDetailsArray = array();
                    foreach ($adDetails as $adDetail) {
                        $adDetailsArray[$adDetail['ad_id']] = $adDetail;
                    }
                }

                foreach ($adEnquiryReportAdIds as $adId) {
                    $updatedAt = ($date ? strtotime($date) : strtotime(date('Y-m-d', strtotime('-1 day'))));
                    $updateSQL = "UPDATE ".$this->historyDbName.".".$adEnquiryReportTableName."
                    SET title = '".addslashes($adDetailsArray[$adId]['title'])."', description = '".addslashes($adDetailsArray[$adId]['description'])."', category_id = '".$adDetailsArray[$adId]['category_id']."',
                    postcode = '".$adDetailsArray[$adId]['postcode']."', county_id = '".$adDetailsArray[$adId]['domicile_id']."', town_id = '".$adDetailsArray[$adId]['town_id']."',
                    saved_ads = '".$adDetailsArray[$adId]['saved_ads']."', title_word_count = '".$adDetailsArray[$adId]['title_word_count']."', title_character_count = '".$adDetailsArray[$adId]['title_character_count']."'
                    , description_word_count = '".$adDetailsArray[$adId]['description_word_count']."', description_character_count = '".$adDetailsArray[$adId]['description_character_count']."', updated_at = '".$updatedAt."' WHERE ad_id = '".$adId."'";

                    $this->executeRawQuery($updateSQL, $this->historyEntityManager);
                }
            }*/
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
    protected function getAdIdsCount($action, $date, $searchParams = array())
    {
        $adViewCounterRepository = $this->entityManager->getRepository('FaAdBundle:AdViewCounter');

        $query = $adViewCounterRepository->getBaseQueryBuilder()
        ->select('COUNT('.AdViewCounterRepository::ALIAS.'.id)');

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.AdViewCounterRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get user ad count results.
     *
     * @param string  $action      Action name.
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdIdsResults($action, $date, $offset, $limit, $searchParam = array())
    {
        $adIds = array();

        $adViewCounterRepository = $this->entityManager->getRepository('FaAdBundle:AdViewCounter');

        $query = $adViewCounterRepository->getBaseQueryBuilder()
        ->select('IDENTITY('.AdViewCounterRepository::ALIAS.'.ad) as ad_id')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.AdViewCounterRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        }

        $adIds = $query->getQuery()->getArrayResult();

        return $adIds;
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
     * @param string  $action      Action name.
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdIdsResultsForAll($action, $date, $offset, $limit, $searchParam = array())
    {
        $adIds = array();

        $adRepository = $this->entityManager->getRepository('FaAdBundle:Ad');

        $query = $adRepository->getBaseQueryBuilder()
        ->select(AdRepository::ALIAS.'.id as ad_id')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        $query->andWhere(AdRepository::ALIAS.'.status = '.EntityRepository::AD_STATUS_LIVE_ID);
        $adIdsFromAd = $query->getQuery()->getArrayResult();

        return $adIdsFromAd;
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
    protected function getAdIdsCountForAll($action, $date, $searchParams = array())
    {
        $adRepository = $this->entityManager->getRepository('FaAdBundle:Ad');

        $query = $adRepository->getBaseQueryBuilder()
        ->select('COUNT('.AdRepository::ALIAS.'.id)');

        $query->andWhere(AdRepository::ALIAS.'.status = '.EntityRepository::AD_STATUS_LIVE_ID);

        return $query->getQuery()->getSingleScalarResult();
    }
}
