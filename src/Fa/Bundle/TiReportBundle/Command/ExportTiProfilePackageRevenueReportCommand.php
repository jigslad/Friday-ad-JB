<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerResponseRepository;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to update dotmailer data in bulk.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExportTiProfilePackageRevenueReportCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:ti:export-profile-package-revenue-report')
        ->setDescription("Export profile package revenue report")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('criteria', null, InputOption::VALUE_REQUIRED, 'Filter criteria', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('file_name', null, InputOption::VALUE_OPTIONAL, 'Name of csv file', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at 6am

Actions:
- Daily bulk upload the data to master address book.

Command:
 - php app/console fa:ti:export-profile-package-revenue-report --criteria="xxxx"
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
        //get options passed in command
        $offset    = $input->getOption('offset');
        $criteria  = unserialize($input->getOption('criteria'));
        $container = $this->getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

        if (isset($criteria['rus_csv_name']) && $criteria['rus_csv_name'] && !isset($offset)) {
            $fileName = $criteria['rus_csv_name'].'.tmp';
            if (is_file($container->get('kernel')->getRootDir()."/../data/reports/profile_package_revenue/".$fileName)) {
                unlink($container->get('kernel')->getRootDir()."/../data/reports/profile_package_revenue/".$fileName);
            }
        } else {
            $fileName = "TiProfilePackageRevenueReport_".date('d-m-Y H:i:s').".tmp";
        }

        if (isset($offset)) {
            $this->exportToCSVWithOffset($criteria, $input, $output);
        } else {
            $profilePackageRevenueReportRepository = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily');
            $file              = fopen($container->get('kernel')->getRootDir()."/../data/reports/profile_package_revenue/".$fileName, "a+");
            $reportFields      = $profilePackageRevenueReportRepository->getPPRReportFieldsChoices($container);
            $reportTextColumns = array();

            $reportTextColumns = array("Name");
            foreach ($criteria['rus_report_columns'] as $reportColumn) {
                $reportTextColumns[] = (isset($reportFields[$reportColumn]) ? $reportFields[$reportColumn] : '');
            }
            fputcsv($file, $reportTextColumns);
            fclose($file);
            $input->setOption('file_name', $fileName);

            $this->exportToCSV($criteria, $input, $output);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function exportToCSV($searchParam, $input, $output)
    {
        $count     = $this->getReportCount($searchParam);
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    if ($option == 'criteria') {
                        $commandOptions .= ' --'.$option.'=\''.$value.'\'';
                    } else {
                        $commandOptions .= ' --'.$option.'="'.$value.'"';
                    }
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:ti:export-profile-package-revenue-report '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);

        // send email of csv
        $reportPath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/profile_package_revenue/";
        $oldFileName = $input->getOption("file_name");
        $newFileName = str_replace('.tmp', '.csv', $oldFileName);
        rename($reportPath.$oldFileName, $reportPath.$newFileName);
        $filePath = $reportPath.$newFileName;
        if (isset($searchParam['rus_csv_email']) && $searchParam['rus_csv_email']) {
            $message = \Swift_Message::newInstance()
            ->setSubject('Profile package revenue report csv generated')
            ->setFrom($this->getContainer()->getParameter('mailer_sender_email'))
            ->setTo($searchParam['rus_csv_email'])
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'FaTiReportBundle:ProfilePackageRevenueReportAdmin:email.html.twig',
                    array('email' => $searchParam['rus_csv_email'])
                ),
                'text/html'
            );

            if (is_file($filePath)) {
                $message->attach(\Swift_Attachment::fromPath($filePath));
            }

            $this->getContainer()->get('mailer')->send($message);
        }
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function exportToCSVWithOffset($searchParam, $input, $output)
    {
        $container = $this->getContainer();
        $entityCacheManager = $container->get('fa.entity.cache.manager');
        $criteria  = $searchParam;
        $query     = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getPPRReportQuery($criteria, $criteria['sorter']);
        $offset    = $input->getOption('offset');
        $fileName  = $input->getOption('file_name');

        $query->setFirstResult($offset);
        $query->setMaxResults($this->limit);

        $results = $query->getArrayResult();

        if (count($results) > 0) {
            foreach ($results as $record) {
                $userIdsArray[]                  = $record['user_id'];
                $resultArray[$record['user_id']] = $record;
            }

            $categoryDataArray         = array();
            $adCountsDataArray         = array();
            $packageDataArray          = array();
            $packageRevenueDataArray   = array();
            $packageCancelledDataArray = array();

            $categoryFields = array_keys(CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getPPRReportCategoryFieldsArray());
            $isCategorySet  = false;
            if (CommonManager::inArrayMulti($categoryFields, $criteria['rus_report_columns']) && $isCategorySet == false) {
                $categoryArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportCategoryDaily')->getCategoryInWhichMaxAdPostedByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                if ($categoryArray && count($categoryArray) > 0) {
                    foreach ($resultArray as $keyUserId => $valueFields) {
                        $categoryDataArray[$keyUserId]['category'] = '';
                        $categoryDataArray[$keyUserId]['class'] = '';
                        foreach ($categoryArray as $key => $values) {
                            $categoryPath = CommonManager::getEntityRepository($container, 'FaEntityBundle:Category')->getCategoryPathArrayById($values['category_id'], false, $container);
                            if (is_array($categoryPath)) {
                                $counter       = 1;
                                $isCategorySet = true;
                                foreach ($categoryPath as $key => $value) {
                                    switch ($counter) {
                                    	case 1:
                                    	    $categoryDataArray[$values['user_id']]['category'] = $value;
                                    	    break;
                                    	case 2:
                                    	    $categoryDataArray[$values['user_id']]['class'] = $value;
                                    	    break;
                                    }
                                    $counter++;
                                }
                            }
                        }
                    }
                }
            }

            $adCountsFields = array_keys(CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getPPRReportDailyBasicFieldsArray());
            if (CommonManager::inArrayMulti($adCountsFields, $criteria['rus_report_columns'])) {
                $adCountsArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportDaily')->getDifferentSumByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                if ($adCountsArray && is_array($adCountsArray)) {
                    foreach ($resultArray as $keyUserId => $valueFields) {
                        if (array_key_exists($keyUserId, $adCountsArray)) {
                            foreach ($adCountsArray[$keyUserId] as $fieldName => $fieldValue) {
                                $adCountsDataArray[$keyUserId][$fieldName] = $fieldValue;
                            }
                        }
                    }
                }
            }

            $packageFields = array_keys(CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getPPRReportProfilePackageFieldsArray());
            if (CommonManager::inArrayMulti($packageFields, $criteria['rus_report_columns'])) {
                $packageBasicFieldsArray = array('package_name', 'package_value_gross', 'package_value_net', 'package_category_id');
                $packageRevenueFieldsArray = array('package_transaction_revenue_gross', 'package_transaction_revenue_net');
                $packageCancelledFieldsArray = array('package_cancelled');

                if ($userIdsArray && is_array($userIdsArray)) {
                    $packageArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getPackageDetailsByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                    if ($packageArray && is_array($packageArray)) {
                        foreach ($resultArray as $keyUserId => $valueFields) {
                            if (array_key_exists($keyUserId, $packageArray)) {
                                $packageName = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getMostRecentPackageNameByUserIdAndDateRange($keyUserId, $criteria['rus_from_date'], $criteria['rus_to_date']);
                                $packageDataArray[$keyUserId]['package_name'] = $packageName;
                                $packageDataArray[$keyUserId]['package_value_gross'] = CommonManager::formatCurrency($packageArray[$keyUserId]['package_value_gross'], $container);
                                $packageDataArray[$keyUserId]['package_value_net'] = CommonManager::formatCurrency(CommonManager::getNetAmountFromGrossAmount($packageArray[$keyUserId]['package_value_gross'], $container), $container);
                                $packageDataArray[$keyUserId]['package_category_id'] = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $packageArray[$keyUserId]['package_category_id']);
                            }
                        }
                    }

                    $packageRevenueArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getPackageRevenueDetailsByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                    if ($packageRevenueArray && is_array($packageRevenueArray)) {
                        foreach ($resultArray as $keyUserId => $valueFields) {
                            if (array_key_exists($keyUserId, $packageRevenueArray)) {
                                $packageRevenueDataArray[$keyUserId]['package_transaction_revenue_gross'] = CommonManager::formatCurrency($packageRevenueArray[$keyUserId]['package_transaction_revenue_gross'], $container);
                                $packageRevenueDataArray[$keyUserId]['package_transaction_revenue_net'] = CommonManager::formatCurrency(CommonManager::getNetAmountFromGrossAmount($packageRevenueArray[$keyUserId]['package_transaction_revenue_gross'], $container), $container);
                            }
                        }
                    }

                    $packageCancelledArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getPackageCancelledCountsByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                    if ($packageCancelledArray && is_array($packageCancelledArray)) {
                        foreach ($resultArray as $keyUserId => $valueFields) {
                            $packageCancelledDataArray[$keyUserId]['package_cancelled'] = 'No';
                            if (array_key_exists($keyUserId, $packageCancelledArray)) {
                                $totalCancelled = $packageCancelledArray[$keyUserId]['total_cancelled_packages'];
                                if ($totalCancelled > 0) {
                                    $packageCancelledDataArray[$keyUserId]['package_cancelled'] = 'Yes';
                                }
                            }
                        }
                    }
                }
            }

            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/profile_package_revenue/".$fileName,"a+");
            foreach ($results as $record) {
                $processedRecord = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->processRecordForPPR($record, $criteria, $container);
                $recordValues   = array();
                $recordValues[] = $processedRecord['name'];
                $currentUserId  = '';
                if (isset($processedRecord['user_id'])) {
                    $currentUserId = $processedRecord['user_id'];
                }
                foreach ($criteria['rus_report_columns'] as $key => $fieldName) {
                    if (in_array($fieldName, $categoryFields)) {
                        if ($currentUserId != '' && array_key_exists($currentUserId, $categoryDataArray) && isset($categoryDataArray[$currentUserId])) {
                            $recordValues[] = $categoryDataArray[$currentUserId][$fieldName];
                        } else {
                            $recordValues[] = '';
                        }
                    } else if (in_array($fieldName, $adCountsFields)) {
                        if ($currentUserId != '' && array_key_exists($currentUserId, $adCountsDataArray) && isset($adCountsDataArray[$currentUserId])) {
                            $recordValues[] = $adCountsDataArray[$currentUserId][$fieldName];
                        } else {
                            $recordValues[] = '';
                        }
                    } else if (in_array($fieldName, $packageFields)) {
                        if (in_array($fieldName, $packageBasicFieldsArray)) {
                            if ($currentUserId != '' && array_key_exists($currentUserId, $packageDataArray) && isset($packageDataArray[$currentUserId])) {
                                $recordValues[] = $packageDataArray[$currentUserId][$fieldName];
                            } else {
                                $recordValues[] = '';
                            }
                        } else if (in_array($fieldName, $packageRevenueFieldsArray)) {
                                if ($currentUserId != '' && array_key_exists($currentUserId, $packageRevenueDataArray) && isset($packageRevenueDataArray[$currentUserId])) {
                                $recordValues[] = $packageRevenueDataArray[$currentUserId][$fieldName];
                            } else {
                                $recordValues[] = '';
                            }
                        } else if (in_array($fieldName, $packageCancelledFieldsArray)) {
                            if ($currentUserId != '' && array_key_exists($currentUserId, $packageCancelledDataArray) && isset($packageCancelledDataArray[$currentUserId])) {
                                $recordValues[] = $packageCancelledDataArray[$currentUserId][$fieldName];
                            } else {
                                $recordValues[] = '';
                            }
                        } else {
                            $recordValues[] = '';
                        }
                    } else if (isset($processedRecord[$fieldName])) {
                        $recordValues[] = $processedRecord[$fieldName];
                    } else {
                        $recordValues[] = '';
                    }
                }
                $recordValues[] = ' ';
                fputcsv($file, $recordValues);
            }
            fclose($file);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Get query builder for ad report.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getReportCount($searchParam)
    {
        $container = $this->getContainer();
        $query     = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getPPRReportQuery($searchParam);
        $results   = $query->execute();

        return count($results);
    }
}
