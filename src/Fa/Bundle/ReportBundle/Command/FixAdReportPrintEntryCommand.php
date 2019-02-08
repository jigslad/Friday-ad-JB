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
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;

/**
 * This command is used to fix ad report print entries.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FixAdReportPrintEntryCommand extends ContainerAwareCommand
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
        ->setName('fa:fix:ad-report-print-entry')
        ->setDescription("Fix ad report print entries.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to fix ad report print entries.

Command:
 - php app/console fa:fix:ad-report-print-entry
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
            $this->fixAdPrintEntryWithOffset($input, $output);
        } else {
            $output->writeln('Total entries to process: '.$this->getAdCount(), true);
            $this->fixAdPrintEntry($input, $output);
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
    protected function fixAdPrintEntry($input, $output)
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:fix:ad-report-print-entry '.$commandOptions.' --verbose';
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
    protected function fixAdPrintEntryWithOffset($input, $output)
    {
        $adPrintReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdPrintReportDaily')->getTableName();
        $offset                      = 0;

        $ads           = $this->getAdResult($offset, $this->limit);
        $adIdArray     = array();
        foreach ($ads as $ad) {
            $adIdArray[] = $ad['ad_id'];
        }
        if (count($adIdArray)) {
            $adUserPackages     = $this->getAdPackageArrayByAdIdForAdReportDaily($adIdArray);
            foreach ($ads as $ad) {
                $grossRevenue = 0;
                $netRevenue   = 0;
                $adPrints           = $this->getAdPrintDetailByAdIdForAdReport($ad['ad_id'], false, false);
                $adUserPackage = (isset($adUserPackages[$ad['ad_id']]) ? $adUserPackages[$ad['ad_id']] : null);
                if ($adUserPackage) {
                    $grossRevenue = $adUserPackage['price'];
                    $netRevenue   = CommonManager::getNetAmountFromGrossAmount($grossRevenue, $this->getContainer());
                }
                $packageId = ($adUserPackage ? $adUserPackage['package_id'] : 0);
                $packageSrNo = ($adUserPackage ? $adUserPackage['package_sr_no'] : null);
                if (!$packageSrNo) {
                    $packageSrNo = 8;
                }
                $packageName = ($adUserPackage ? $packageSrNo.' ('.($adUserPackage['package_text'] ? $adUserPackage['package_text'] : 'Other Package').')' : null);
                if (!$packageId) {
                    $packageId = '-1';
                    $packageSrNo = 8;
                    $packageName = '8 (Migrated print)';
                }
                if (isset($adPrints[$ad['ad_id']]) && count($adPrints[$ad['ad_id']])) {
                    $printInsertDate = null;
                    $printEditionIds = array();

                    if (isset($adPrints[$ad['ad_id']]) && count($adPrints[$ad['ad_id']])) {
                        foreach ($adPrints[$ad['ad_id']] as $printEditionId => $adPrint) {
                            if (!$printInsertDate || ($adPrint['insert_date'] > $printInsertDate)) {
                                $printInsertDate   = $adPrint['insert_date'];
                            }
                            $printEditionIds[] = $printEditionId;
                        }
                        $printEditionIds = array_unique($printEditionIds);
                        asort($printEditionIds);
                    }
                    $updateSetSql = '';
                    if (!$ad['print_insert_date']) {
                        $updateSetSql .= ', print_insert_date = '.$printInsertDate;
                    }
                    if (!$ad['print_edition_ids']) {
                        $updateSetSql .= ', print_edition_ids = "'.implode(',', $printEditionIds).'"';
                    }
                    if (!$ad['package_name']) {
                        $updateSetSql .= ', package_name = "'.$packageName.'"';
                    }
                    if (!$ad['package_sr_no']) {
                        $updateSetSql .= ', package_sr_no = "'.$packageSrNo.'"';
                    }
                    if (!$ad['revenue_gross']) {
                        $updateSetSql .= ', revenue_gross = "'.$grossRevenue.'"';
                    }
                    if (!$ad['revenue_net']) {
                        $updateSetSql .= ', revenue_net = "'.$netRevenue.'"';
                    }

                    $output->writeln('Updating records in ad_print_report_daily id: '.$ad['id'].'('.$ad['ad_id'].')', true);
                    $updateSql = 'UPDATE '.$this->historyDbName.'.'.$adPrintReportDailyTableName.' SET package_id = "'.$packageId.'"'.$updateSetSql.' WHERE id = '.$ad['id'].';';
                    $this->executeRawQuery($updateSql, $this->historyEntityManager);
                } else {
                    $output->writeln('Deleting records from  ad_print_report_daily id: '.$ad['id'].'('.$ad['ad_id'].')', true);
                    $deleteSql = 'DELETE from '.$this->historyDbName.'.'.$adPrintReportDailyTableName.' WHERE id = '.$ad['id'].';';
                    $this->executeRawQuery($deleteSql, $this->historyEntityManager);
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
        $adPrintReportDailyRepository  = $this->historyEntityManager->getRepository('FaReportBundle:AdPrintReportDaily');

        $query = $adPrintReportDailyRepository->getBaseQueryBuilder()
            ->select(AdPrintReportDailyRepository::ALIAS.'.id')
            ->andWhere(AdPrintReportDailyRepository::ALIAS.'.is_latest_entry = 1')
            ->andWhere(AdPrintReportDailyRepository::ALIAS.'.package_id = 0');

        return count($query->getQuery()->getArrayResult());
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
        $adPrintReportDailyRepository  = $this->historyEntityManager->getRepository('FaReportBundle:AdPrintReportDaily');

        $query = $adPrintReportDailyRepository->getBaseQueryBuilder()
        ->andWhere(AdPrintReportDailyRepository::ALIAS.'.is_latest_entry = 1')
        ->andWhere(AdPrintReportDailyRepository::ALIAS.'.package_id = 0')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        return $query->getQuery()->getArrayResult();
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
    public function getAdPrintDetailByAdIdForAdReport($adId, $getAllInsertDateFlag = false, $getFirstInsertDateFlag = false)
    {
        $adPrintRepository  = $this->entityManager->getRepository('FaAdBundle:AdPrint');
        $qb = $adPrintRepository->createQueryBuilder(AdPrintRepository::ALIAS)
        ->select('IDENTITY('.AdPrintRepository::ALIAS.'.ad) as ad_id', 'IDENTITY('.AdPrintRepository::ALIAS.'.print_edition) as print_edition_id', AdPrintRepository::ALIAS.'.insert_date', AdPrintRepository::ALIAS.'.duration')
        ->innerJoin(AdPrintRepository::ALIAS.'.ad', AdRepository::ALIAS)
        ->andwhere(AdPrintRepository::ALIAS.'.is_paid = 1');

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
                if ($getAllInsertDateFlag) {
                    $adPrintArr[$adPrint['ad_id']][] = array(
                        'insert_date' => $adPrint['insert_date'],
                        'print_edition_id' => $adPrint['print_edition_id'],
                    );
                } elseif ($getFirstInsertDateFlag) {
                    if (!isset($adPrintArr[$adPrint['ad_id']][$adPrint['print_edition_id']])) {
                        $adPrintArr[$adPrint['ad_id']][$adPrint['print_edition_id']] = array(
                            'insert_date' => $adPrint['insert_date'],
                            'duration' => $adPrint['duration'],
                        );
                    }
                } else {
                    if (!isset($adPrintArr[$adPrint['ad_id']][$adPrint['print_edition_id']]) || (isset($adPrintArr[$adPrint['ad_id']][$adPrint['print_edition_id']]['insert_date']) && $adPrint['insert_date'] > $adPrintArr[$adPrint['ad_id']][$adPrint['print_edition_id']]['insert_date'])) {
                        $adPrintArr[$adPrint['ad_id']][$adPrint['print_edition_id']] = array(
                            'insert_date' => $adPrint['insert_date'],
                            'duration' => $adPrint['duration'],
                        );
                    }
                }
            }
        }

        return $adPrintArr;
    }

    /**
     * Get package for ad id for ad report daily.
     *
     * @param array  $adId       Ad id array.
     *
     * @return array
     */
    public function getAdPackageArrayByAdIdForAdReportDaily($adId = array())
    {
        $adUserPackageTableName = $this->entityManager->getClassMetadata('FaAdBundle:AdUserPackage')->getTableName();
        $packageTableName = $this->entityManager->getClassMetadata('FaPromotionBundle:Package')->getTableName();

        $sql ='SELECT '.AdUserPackageRepository::ALIAS.'.package_id,'.AdUserPackageRepository::ALIAS.'.ad_id,'.AdUserPackageRepository::ALIAS.'.started_at,'.AdUserPackageRepository::ALIAS.'.value,'.AdUserPackageRepository::ALIAS.'.price,'.PackageRepository::ALIAS.'.package_sr_no, '.PackageRepository::ALIAS.'.package_text FROM '.$adUserPackageTableName.' as '.AdUserPackageRepository::ALIAS.'
            JOIN (SELECT ad_id, MAX(id) max_id FROM '.$adUserPackageTableName.' GROUP BY ad_id) '.AdUserPackageRepository::ALIAS.'1 ON ('.AdUserPackageRepository::ALIAS.'.id = '.AdUserPackageRepository::ALIAS.'1.max_id)
            LEFT JOIN '.$packageTableName.' as '.PackageRepository::ALIAS.' ON ('.AdUserPackageRepository::ALIAS.'.package_id = '.PackageRepository::ALIAS.'.id)
            Where 1=1';

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($this->printPackageArray)) {
            $sql .= ' AND '.AdUserPackageRepository::ALIAS.'.package_id IN ('.implode(',', $this->printPackageArray).')';
        }

        if (count($adId)) {
            $sql .= ' AND '.AdUserPackageRepository::ALIAS.'.ad_id IN ('.implode(',', $adId).')';
        }
        $sql .= ';';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $adPackages = $stmt->fetchAll();

        $adPackageArr = array();
        if (count($adPackages)) {
            foreach ($adPackages as $adPackage) {
                $adPackageArr[$adPackage['ad_id']] = array(
                    'package_id' => $adPackage['package_id'],
                    'package_sr_no' => $adPackage['package_sr_no'],
                    'package_text' => $adPackage['package_text'],
                    'price' => $adPackage['price'],
                    'value' => $adPackage['value'],
                    'started_at' => $adPackage['started_at'],
                );
            }
        }

        return $adPackageArr;
    }
}
