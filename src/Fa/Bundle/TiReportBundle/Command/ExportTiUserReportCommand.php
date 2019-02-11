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
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExportTiUserReportCommand extends ContainerAwareCommand
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
        ->setName('fa:ti:export-user-report')
        ->setDescription("Export user report")
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
 - php app/console fa:ti:export-user-report --criteria="xxxx"
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
            if (is_file($container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName)) {
                unlink($container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName);
            }
        } else {
            $fileName = "TiUserReport_".date('d-m-Y H:i:s').".tmp";
        }

        if ($criteria['rus_report_type'] == 'user_wise') {
            if (isset($offset)) {
                $this->exportToCSVWithOffset($criteria, $input, $output);
            } else {
                $reportDefaultFiledsArray  = array("Name");
                $reportAllOtherFieldsArray = CommonManager::getUserReportFieldsChoices();
                $reportFieldsArray = $reportDefaultFiledsArray;
                foreach ($criteria['rus_report_columns'] as $key => $value) {
                    $reportFieldsArray[] = $reportAllOtherFieldsArray[$value];
                }

                $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName, "a+");
                fputcsv($file, $reportFieldsArray);
                fclose($file);
                $input->setOption('file_name', $fileName);
                $this->exportToCSV($criteria, $input, $output);
            }
        } else {
            $reportBasicFields    = array_keys(CommonManager::getUserReportBasicFieldsArray());
            $reportBooleanFields  = array_keys(CommonManager::getUserReportBooleanFieldsArray());
            $reportDateFields     = array_keys(CommonManager::getUserReportDateFieldsArray());
            $reportDailyFields    = array_keys(CommonManager::getUserReportDailyBasicFieldsArray());
            $reportCategoryFields = array_keys(CommonManager::getUserReportCategoryFieldsArray());
            $reportEditionFields  = array_keys(CommonManager::getUserReportEditionFieldsArray());
            $reportPackageFields  = array_keys(CommonManager::getUserReportProfilePackageFieldsArray());
            $reportAllowedFields  = array_merge($reportBasicFields, $reportBooleanFields, $reportDateFields, $reportDailyFields, $reportCategoryFields, $reportEditionFields, $reportPackageFields);
            $selectedColumns      = $criteria['rus_report_columns'];
            unset($criteria['rus_report_columns']);
            foreach ($selectedColumns as $key => $value) {
                if (in_array($value, $reportAllowedFields)) {
                    $criteria['rus_report_columns'][] = $value;
                }
            }

            if (CommonManager::inArrayMulti($reportDailyFields, $criteria['rus_report_columns'])) {
                $resultArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportDaily')->getUserReportDailyTotalSum($criteria);
            } else {
                $resultArray[0] = array();
            }

            $newlySignupArray            = array();
            $booleanAndOtherFieldsArray  = array();
            $categoryAndEditionDataArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getCategoryAndEditionDataArray($criteria, $container);

            if (CommonManager::inArrayMulti($reportBooleanFields, $criteria['rus_report_columns'])) {
                $booleanAndOtherFieldsArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getBooleanAndOtherFieldsSumQuery($criteria)->getResult();
            }

            if (CommonManager::inArrayMulti(array('is_new', 'signup_date'), $criteria['rus_report_columns'])) {
                $newlySignupArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getNewlySignupUsersQuery($criteria)->getResult();
            }

            if ($categoryAndEditionDataArray && count($categoryAndEditionDataArray) > 0) {
                $resultArray[0] = array_merge($resultArray[0], $categoryAndEditionDataArray);
            }

            if ($booleanAndOtherFieldsArray && count($booleanAndOtherFieldsArray) > 0) {
                $resultArray[0] = array_merge($resultArray[0], $booleanAndOtherFieldsArray[0]);
            }

            if ($newlySignupArray && count($newlySignupArray) > 0) {
                $resultArray[0]           = array_merge($resultArray[0], $newlySignupArray[0]);
                $resultArray[0]['is_new'] = $newlySignupArray[0]['signup_date'];
            }

            $reportFieldsArray = array();
            $reportAllOtherFieldsArray = CommonManager::getUserReportFieldsChoices();
            foreach ($criteria['rus_report_columns'] as $key => $value) {
                $reportFieldsArray[] = $reportAllOtherFieldsArray[$value];
            }

            if (isset($criteria['rus_csv_name']) && $criteria['rus_csv_name'] && !isset($offset)) {
                $fileName = $criteria['rus_csv_name'].'.csv';
                if (is_file($container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName)) {
                    unlink($container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName);
                }
            } else {
                $fileName = "TiUserReport_".date('d-m-Y H:i:s').".csv";
            }

            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName, "a+");
            fputcsv($file, $reportFieldsArray);
            fclose($file);

            if ($resultArray && is_array($resultArray)) {
                foreach ($criteria['rus_report_columns'] as $key => $fieldName) {
                    if (isset($resultArray[0][$fieldName])) {
                        $recordArray[$fieldName] = $resultArray[0][$fieldName];
                    } else {
                        $recordArray[$fieldName] = '';
                    }
                }
            }

            if ($recordArray && is_array($recordArray)) {
                $recordArray[] = ' ';
                $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName, "a+");
                fputcsv($file, $recordArray);
                fclose($file);
            }
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:ti:export-user-report '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);

        // send email of csv
        $reportPath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/user/";
        $oldFileName = $input->getOption("file_name");
        $newFileName = str_replace('.tmp', '.csv', $oldFileName);
        rename($reportPath.$oldFileName, $reportPath.$newFileName);
        $filePath = $reportPath.$newFileName;
        if (isset($searchParam['rus_csv_email']) && $searchParam['rus_csv_email']) {
            $message = \Swift_Message::newInstance()
            ->setSubject('User report csv generated')
            ->setFrom($this->getContainer()->getParameter('mailer_sender_email'))
            ->setTo($searchParam['rus_csv_email'])
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'FaTiReportBundle:UserReportAdmin:email.html.twig',
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
        $criteria  = $searchParam;
        $query     = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getUserReportQuery($criteria, $criteria['sorter']);
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

            $categoryDataArray = array();
            $editionDataArray  = array();
            $packageDataArray  = array();
            $categoryFields    = array_keys(CommonManager::getUserReportCategoryFieldsArray());
            $editionFields     = array_keys(CommonManager::getUserReportEditionFieldsArray());
            $packageFields     = array_keys(CommonManager::getUserReportProfilePackageFieldsArray());
            $isCategorySet     = false;
            if (CommonManager::inArrayMulti($categoryFields, $criteria['rus_report_columns']) || CommonManager::inArrayMulti($editionFields, $criteria['rus_report_columns']) || CommonManager::inArrayMulti($packageFields, $criteria['rus_report_columns'])) {
                if (CommonManager::inArrayMulti($categoryFields, $criteria['rus_report_columns'])) {
                    $categoryArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportCategoryDaily')->getCategoryInWhichMaxAdPostedByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                    if ($categoryArray && count($categoryArray) > 0) {
                        foreach ($resultArray as $keyUserId => $valueFields) {
                            $categoryDataArray[$keyUserId]['category'] = '';
                            $categoryDataArray[$keyUserId]['class'] = '';
                            $categoryDataArray[$keyUserId]['subclass'] = '';
                            $categoryDataArray[$keyUserId]['sub_sub_class'] = '';
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
                                            case 3:
                                                $categoryDataArray[$values['user_id']]['subclass'] = $value;
                                                break;
                                            case 4:
                                                $categoryDataArray[$values['user_id']]['sub_sub_class'] = $value;
                                                break;
                                        }
                                        $counter++;
                                    }
                                }
                            }
                        }
                    }
                }

                if (CommonManager::inArrayMulti($editionFields, $criteria['rus_report_columns'])) {
                    if ($userIdsArray && is_array($userIdsArray)) {
                        $editionArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportEditionDaily')->getEditionInWhichMaxAdPostedByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                        $entityCacheManager = $container->get('fa.entity.cache.manager');
                        foreach ($resultArray as $keyUserId => $valueFields) {
                            $resultArray[$keyUserId]['edition'] = '';
                            if ($editionArray && is_array($editionArray)) {
                                foreach ($editionArray as $key => $values) {
                                    if ($keyUserId == $values['user_id']) {
                                        $editionName = $entityCacheManager->getEntityNameById('FaAdBundle:PrintEdition', $values['edition_id']);
                                        $editionDataArray[$values['user_id']]['edition'] = $editionName;
                                    }
                                }
                            }
                        }
                    }
                }

                if (CommonManager::inArrayMulti($packageFields, $criteria['rus_report_columns'])) {
                    if ($userIdsArray && is_array($userIdsArray)) {
                        $packageArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getPackageDetailsByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                        if ($packageArray && is_array($packageArray)) {
                            foreach ($resultArray as $keyUserId => $valueFields) {
                                if (array_key_exists($keyUserId, $packageArray)) {
                                    $packageName = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getMostRecentPackageNameByUserIdAndDateRange($keyUserId, $criteria['rus_from_date'], $criteria['rus_to_date']);
                                    $packageDataArray[$keyUserId]['package_name'] = $packageName;
                                    if (isset($packageArray[$keyUserId]['package_revenue'])) {
                                        $packageDataArray[$keyUserId]['package_revenue'] = CommonManager::formatCurrency($packageArray[$keyUserId]['package_revenue'], $container);
                                    } else {
                                        $packageDataArray[$keyUserId]['package_revenue'] = CommonManager::formatCurrency(0, $container);
                                    }
                                    $packageDataArray[$keyUserId]['package_cancelled'] = 'No';
                                }
                            }
                        }

                        $packageCancelledArray = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReportProfilePackageDaily')->getPackageCancelledCountsByUserIdAndDateRange($userIdsArray, $criteria['rus_from_date'], $criteria['rus_to_date']);
                        if ($packageCancelledArray && is_array($packageCancelledArray)) {
                            foreach ($resultArray as $keyUserId => $valueFields) {
                                $resultArray[$keyUserId]['package_cancelled'] = 'No';
                                if (array_key_exists($keyUserId, $packageCancelledArray)) {
                                    $totalCancelled = $packageCancelledArray[$keyUserId]['total_cancelled_packages'];
                                    if ($totalCancelled > 0) {
                                        $packageDataArray[$keyUserId]['package_cancelled'] = 'Yes';
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName, "a+");
            foreach ($results as $record) {
                $processedRecord = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->processRecord($record, $criteria, $container);
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
                    } elseif (in_array($fieldName, $editionFields)) {
                        if ($currentUserId != '' && array_key_exists($currentUserId, $editionDataArray) && isset($editionDataArray[$currentUserId])) {
                            $recordValues[] = $editionDataArray[$currentUserId][$fieldName];
                        } else {
                            $recordValues[] = '';
                        }
                    } elseif (in_array($fieldName, $packageFields)) {
                        if ($currentUserId != '' && array_key_exists($currentUserId, $packageDataArray) && isset($packageDataArray[$currentUserId])) {
                            $recordValues[] = $packageDataArray[$currentUserId][$fieldName];
                        } else {
                            $recordValues[] = '';
                        }
                    } elseif (isset($processedRecord[$fieldName])) {
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
        $query     = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getUserReportQuery($searchParam);
        $results   = $query->execute();

        return count($results);
    }
}
