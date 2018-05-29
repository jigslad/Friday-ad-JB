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
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;

/**
 * This command is used to update ad print report statistics.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAdPrintReportCommand extends ContainerAwareCommand
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
        ->setName('fa:update:ad-print-report')
        ->setDescription("Update ad print report statistics.")
        ->addArgument('action', InputArgument::OPTIONAL, 'all or beforeoneday')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date (Y-m-d) for which need to add / update statistics', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update ad report statistics.

Command:
 - php app/console fa:update:ad-print-report all
 - php app/console fa:update:ad-print-report beforeoneday
 - php app/console fa:update:ad-print-report --date="2015-04-28"
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
        $offset = $input->getOption('offset');
        $date   = $input->getOption('date');
        $action = $input->getArgument('action');

        if ($action == 'beforeoneday' || $action == 'all' || $date) {
            if (!isset($offset)) {
                $start_time = time();
                $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
                if ($action == 'all') {
                    $adPrintReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdPrintReportDaily')->getTableName();
                    $output->writeln('Truncating ad_print_report_daily records...', true);
                    $this->executeRawQuery('TRUNCATE TABLE '.$this->historyDbName.'.'.$adPrintReportDailyTableName.';', $this->historyEntityManager);
                }
            }

            // insert ads statistics.
            if (isset($offset)) {
                $this->updateAdPrintStatisticsWithOffset($input, $output);
            } else {
                $this->updateAdPrintStatistics($input, $output);
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
     * Update ad print statistics.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdPrintStatistics($input, $output)
    {
        $action = $input->getArgument('action');
        $date   = $input->getOption('date');
        $count  = $this->getAdPrintCount($action, $date);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:ad-print-report '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update ad print statistics with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdPrintStatisticsWithOffset($input, $output)
    {
        $action                      = $input->getArgument('action');
        $date                        = $input->getOption('date');
        $adPrintReportDailyTableName = $this->historyEntityManager->getClassMetadata('FaReportBundle:AdPrintReportDaily')->getTableName();
        $offset                      = $input->getOption('offset');

        list($startDate, $endDate) = $this->getDateInTimeStamp($date);
        $ads           = $this->getAdPrintResult($action, $date, $offset, $this->limit);
        $adIdArray     = array();
        $insertPrintAdIds = array();
        $noInsertPrintAdIds = array();
        foreach ($ads as $ad) {
            $adIdArray[] = $ad['id'];
        }
        if (count($adIdArray)) {
            $this->historyEntityManager->getRepository('FaReportBundle:AdPrintReportDaily')->removeAdPrintReportDailyAdsByIds($adIdArray, $date);
            $output->writeln('Inserting records in ad_print_report_daily....', true);
            $adUserPackages     = $this->entityManager->getRepository('FaAdBundle:AdUserPackage')->getAdPackageArrayByAdIdForAdReportDaily($adIdArray, 'ad_print');
            $adPrints           = $this->entityManager->getRepository('FaAdBundle:AdPrint')->getAdPrintDetailByAdIdForAdReport($adIdArray, $startDate, $endDate);
            $printPackageArray  = $this->entityManager->getRepository('FaPromotionBundle:Package')->getPrintPackagesArray();

            $insertSql = 'INSERT INTO '.$this->historyDbName.'.'.$adPrintReportDailyTableName.
            '(`ad_id`, `user_id`, `title`, `print_insert_date`, `published_at`, `expires_at`, `category_id`, `print_edition_ids`, `source`, `role_id`, `revenue_gross`, `revenue_net`, `package_id`, `package_name`, `package_sr_no`, `created_at`, `is_latest_entry`) VALUES';

            foreach ($ads as $ad) {
                $source    = $ad['source'];
                $adUserPackage = (isset($adUserPackages[$ad['id']]) ? $adUserPackages[$ad['id']] : null);
                $userRole  = $this->entityManager->getRepository('FaUserBundle:User')->getUserRole($ad['user_id'], $this->getContainer());
                $userRoleId = null;
                $adUserPackageValue = unserialize($adUserPackage['value']);
                $packageId = ($adUserPackage ? $adUserPackage['package_id'] : 0);
                $packageName = ($adUserPackage ? $adUserPackage['package_sr_no'].' ('.$adUserPackage['package_text'].') ('.(isset($adUserPackageValue['is_admin_price']) && $adUserPackageValue['is_admin_price'] ? 'Admin Price' : 'PAA Price').')' : null);
                $packageSrNo = ($adUserPackage ? $adUserPackage['package_sr_no'] : null);

                if (in_array($packageId, $printPackageArray) && isset($adPrints[$ad['id']]) && count($adPrints[$ad['id']])) {
                    $insertPrintAdIds[] = $ad['id'];
                    $printInsertDate = null;
                    $printEditionIds = array();

                    if (isset($adPrints[$ad['id']]) && count($adPrints[$ad['id']])) {
                        foreach ($adPrints[$ad['id']] as $printEditionId => $adPrint) {
                            if (!$printInsertDate || ($adPrint['insert_date'] > $printInsertDate)) {
                                $printInsertDate   = $adPrint['insert_date'];
                            }
                            $printEditionIds[] = $printEditionId;
                        }
                        $printEditionIds = array_unique($printEditionIds);
                        asort($printEditionIds);
                    }
                    //revenue
                    $grossRevenue = 0;
                    $netRevenue   = 0;

                    if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER) {
                        $userRoleId = RoleRepository::ROLE_BUSINESS_SELLER_ID;
                    } elseif ($userRole == RoleRepository::ROLE_SELLER) {
                        $userRoleId = RoleRepository::ROLE_SELLER_ID;
                    }

                    if ($adUserPackage) {
                        $grossRevenue = $adUserPackage['price'];
                        $netRevenue   = CommonManager::getNetAmountFromGrossAmount($grossRevenue, $this->getContainer());
                    }

                    $insertSql .= '("'.$ad['id'].'", "'.$ad['user_id'].'", "'.addslashes($ad['title']).'", "'.$printInsertDate.'", "'.$ad['published_at'].'", "'.$ad['expires_at'].'", "'.$ad['category_id'].'", "'.implode(',', $printEditionIds).'", "'.$source.'", "'.$userRoleId.'", "'.$grossRevenue.'", "'.$netRevenue.'", "'.$packageId.'", "'.$packageName.'", "'.$packageSrNo.'", '.($action == 'all' ? time() : ($date ? strtotime($date) : (strtotime(date('Y-m-d'))- 24*60*60))).', 1), ';
                } else {
                    $noInsertPrintAdIds[] = $ad['id'];
                }
            }

            if (count($insertPrintAdIds)) {
                $updateSql = 'UPDATE '.$this->historyDbName.'.'.$adPrintReportDailyTableName.' SET is_latest_entry = 0 WHERE is_latest_entry = 1 AND ad_id in ('.implode(',', $insertPrintAdIds).');';
                $this->executeRawQuery($updateSql, $this->historyEntityManager);
                $insertSql = trim($insertSql, ', ');
                $this->executeRawQuery($insertSql, $this->historyEntityManager);
            }
        }
    }

    /**
     * Get query builder for print ads.
     *
     * @param string $action       Action name.
     * @param string $date         Date.
     * @param array  $searchParams Search parameter array.
     *
     * @return count
     */
    protected function getAdPrintCount($action, $date, $searchParams = array())
    {
        $adPrintRepository = $this->entityManager->getRepository('FaAdBundle:AdPrint');

        $query = $adPrintRepository->getBaseQueryBuilder()
            ->select('COUNT( DISTINCT '.AdRepository::ALIAS.'.id)')
            ->innerJoin(AdPrintRepository::ALIAS.'.ad', AdRepository::ALIAS)
            ->andWhere(AdPrintRepository::ALIAS.'.ad_moderate_status = :ad_moderate_status')
            ->andWhere(AdPrintRepository::ALIAS.'.is_paid = :is_paid')
            ->setParameter('is_paid', '1')
            ->setParameter('ad_moderate_status', AdModerateRepository::MODERATION_QUEUE_STATUS_OKAY);

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.AdPrintRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdPrintRepository::ALIAS.'.updated_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        } elseif ($action == 'all') {
            $query->andWhere(AdRepository::ALIAS.'.status = '.EntityRepository::AD_STATUS_LIVE_ID);
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get print ad results.
     *
     * @param string  $action      Action name.
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     * @param array   $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdPrintResult($action, $date, $offset, $limit, $searchParam = array())
    {
        $adPrintRepository  = $this->entityManager->getRepository('FaAdBundle:AdPrint');

        $query = $adPrintRepository->getBaseQueryBuilder()
        ->addSelect(AdRepository::ALIAS.'.id', AdRepository::ALIAS.'.title', AdRepository::ALIAS.'.published_at', AdRepository::ALIAS.'.expires_at', 'IDENTITY('.AdRepository::ALIAS.'.category) as category_id', 'IDENTITY('.AdRepository::ALIAS.'.user) as user_id', AdRepository::ALIAS.'.source')
        ->innerJoin(AdPrintRepository::ALIAS.'.ad', AdRepository::ALIAS)
        ->andWhere(AdPrintRepository::ALIAS.'.ad_moderate_status = :ad_moderate_status')
        ->andWhere(AdPrintRepository::ALIAS.'.is_paid = :is_paid')
        ->setParameter('is_paid', '1')
        ->setParameter('ad_moderate_status', AdModerateRepository::MODERATION_QUEUE_STATUS_OKAY)
        ->setMaxResults($limit)
        ->setFirstResult($offset)
        ->groupBy(AdRepository::ALIAS.'.id');

        if ($action == 'beforeoneday' || $date) {
            list($startDate, $endDate) = $this->getDateInTimeStamp($date);
            $query->andWhere('('.AdPrintRepository::ALIAS.'.created_at BETWEEN '.$startDate.' AND  '.$endDate.') OR ('.AdPrintRepository::ALIAS.'.updated_at BETWEEN '.$startDate.' AND  '.$endDate.')');
        } elseif ($action == 'all') {
            $query->andWhere(AdRepository::ALIAS.'.status = '.EntityRepository::AD_STATUS_LIVE_ID);
        }

        return $query->getQuery()->getArrayResult();
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
