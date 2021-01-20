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
use Fa\Bundle\ReportBundle\Repository\AdReportDailyRepository;
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;
use Fa\Bundle\ReportBundle\Repository\AdPrintReportDailyRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;

/**
 * This command is used to fix ad report entries.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FixAdReportEntryCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

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
     * Print package ids
     *
     * @var array
     */
    private $printPackageArray;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:fix:ad-report-entry')
        ->setDescription("Fix ad report print entries.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to fix ad report print entries.

Command:
 - php app/console fa:fix:ad-report-entry
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
        $this->printPackageArray    = $this->entityManager->getRepository('FaPromotionBundle:Package')->getPrintPackagesArray();

        //get arguments passed in command
        $offset = $input->getOption('offset');

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        }

        // insert ads statistics.
        if (isset($offset)) {
            $this->fixAdEntryWithOffset($input, $output);
        } else {
            $output->writeln('Total entries to process: '.$this->getAdCount(), true);
            $this->fixAdEntry($input, $output);
        }

        if (!isset($offset)) {
            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
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
     * Update user total ad count.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function fixAdEntry($input, $output)
    {
        $count  = $this->getAdCount();

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:fix:ad-report-entry '.$commandOptions.' --verbose';
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
    protected function fixAdEntryWithOffset($input, $output)
    {
        $adReportDailyTableName   = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdReportDaily')->getTableName();
        $adPrintInsertDateReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdPrintInsertDateReportDaily')->getTableName();
        $offset = 0;
        $ads = $this->getAdResult($offset, $this->limit);
        foreach ($ads as $ad) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($ad['created_at']);
            $adPrints = $this->getAdPrintDetailByAdIdForAdReport(array($ad['ad_id']), $startDate, $endDate, true);
            $printInsertDate = null;
            $printEditionIds = array();
            $durationPrint   = null;
            $printDateInsertFlag = false;
            if (isset($adPrints[$ad['ad_id']]) && count($adPrints[$ad['ad_id']])) {
                $insertSql = 'INSERT INTO '.$this->historyDbName.'.'.$adPrintInsertDateReportDailyTableName.
                '(`ad_id`, `ad_report_daily_id`, `print_insert_date`, `print_edition_id`, `created_at`) VALUES';
                foreach ($adPrints[$ad['ad_id']] as $adPrint) {
                    if (!$printInsertDate || ($adPrint['insert_date'] > $printInsertDate)) {
                        $printInsertDate   = $adPrint['insert_date'];
                    }
                    $printEditionIds[] = $adPrint['print_edition_id'];
                    $durationPrint     = $adPrint['duration'];
                    $adPrintInsertDateReportDailyObj = $this->historyEntityManager->getRepository('FaReportBundle:AdPrintInsertDateReportDaily')->findOneBy(array('ad_id' => $ad['ad_id'], 'ad_report_daily_id' => $ad['id'], 'print_insert_date' => $adPrint['insert_date'], 'print_edition_id' => $adPrint['print_edition_id']));
                    if (!$adPrintInsertDateReportDailyObj) {
                        $printDateInsertFlag = true;
                        $insertSql .= '("'.$ad['ad_id'].'", "'.$ad['id'].'", "'.$adPrint['insert_date'].'", "'.$adPrint['print_edition_id'].'", '.time().'), ';
                    }
                }
                $printEditionIds = array_unique($printEditionIds);
                asort($printEditionIds);
                $output->writeln('Updating records in ad_report_daily for ad: '.$ad['ad_id'].'('.$ad['id'].')', true);
                $updateSql = 'UPDATE '.$this->historyDbName.'.'.$adReportDailyTableName.' SET print_insert_date = '.$printInsertDate.', duration_print = "'.$durationPrint.'", print_edition_ids = "'.implode(',', $printEditionIds).'" WHERE id = '.$ad['id'].';';
                $this->executeRawQuery($updateSql, $this->historyEntityManager);

                if ($printDateInsertFlag) {
                    $output->writeln('Inserting records in ad_print_insert_date_report_daily....', true);
                    $insertSql = trim($insertSql, ', ');
                    $this->executeRawQuery($insertSql, $this->historyEntityManager);
                }
            }
        }
    }

    /**
     * Get query builder for ads.
     *
     * @return count
     */
    protected function getAdCount()
    {
        //$sql = 'select count(id) as total from '.$this->historyDbName.'.ad_report_daily where print_revenue_gross > 0 AND duration_print = 0;';
        $sql = 'select count(id) as total from '.$this->historyDbName.'.ad_report_daily where duration_print = 1;';
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $countRes = $stmt->fetch();

        return $countRes['total'];
    }

    /**
     * Get user ad count results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdResult($offset, $limit)
    {
        //$sql = 'select ard.id, ard.ad_id, ard.created_at from '.$this->historyDbName.'.ad_report_daily as ard where print_revenue_gross > 0 AND duration_print = 0;';
        $sql = 'select ard.id, ard.ad_id, ard.created_at from '.$this->historyDbName.'.ad_report_daily as ard where duration_print = 1;';
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get ad print latest entry.
     *
     * @param array   $adId                   Ad id array.
     * @param integer $startDate              Start date.
     * @param integer $endDate                End date.
     * @param boolean $getAllInsertDateFlag   Get all insert dates.
     * @param boolean $getFirstInsertDateFlag Get first insert dates by print editions.
     *
     * @return array
     */
    public function getAdPrintDetailByAdIdForAdReport($adId, $startDate, $endDate, $getAllInsertDateFlag = false)
    {
        $adPrintRepository  = $this->entityManager->getRepository('FaAdBundle:AdPrint');
        $qb = $adPrintRepository->getBaseQueryBuilder()
        ->select('IDENTITY('.AdPrintRepository::ALIAS.'.ad) as ad_id', 'IDENTITY('.AdPrintRepository::ALIAS.'.print_edition) as print_edition_id', AdPrintRepository::ALIAS.'.insert_date', AdPrintRepository::ALIAS.'.duration')
        ->innerJoin(AdPrintRepository::ALIAS.'.ad', AdRepository::ALIAS)
        ->andwhere(AdPrintRepository::ALIAS.'.is_paid = 1')
        ->andWhere(AdPrintRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND '.$endDate);

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $qb->andWhere(AdPrintRepository::ALIAS.'.ad IN (:adId)')
            ->setParameter('adId', $adId);
        }

        $adPrints   = $qb->getQuery()->getArrayResult();
        $adPrintArr = array();
        if (count($adPrints)) {
            foreach ($adPrints as $adPrint) {
                $adPrintArr[$adPrint['ad_id']][] = array(
                    'insert_date' => $adPrint['insert_date'],
                    'print_edition_id' => $adPrint['print_edition_id'],
                    'duration' => $adPrint['duration'],
                );
            }
        }

        return $adPrintArr;
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
        $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', $date));
        $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', $date));

        return array($startDate, $endDate);
    }
}
