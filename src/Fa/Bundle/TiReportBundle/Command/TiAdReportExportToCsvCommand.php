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
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to export ad report data in csv.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class TiAdReportExportToCsvCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 1000;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:ti:ad:report:export-to-csv')
        ->setDescription("Ad report export to csv")
        ->addOption('criteria', null, InputOption::VALUE_REQUIRED, 'Serialize string of ad report criteria')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('file_name', null, InputOption::VALUE_OPTIONAL, 'Name of csv file', null)
        ->setHelp(
            <<<EOF
Cron: Will be execute at run time in bg process

Actions:
-  Will be execute at run time in bg process whne user clicks export from ad report.

Command:
 - php app/console fa:ti:ad:report:export-to-csv --criteria='a:2:{s:6:"search";a:6:{s:9:"from_date";s:10:"17/08/2015";s:7:"to_date";s:10:"21/08/2015";s:16:"date_filter_type";s:13:"ad_created_at";s:7:"role_id";s:1:"6";s:8:"paid_ads";s:1:"1";s:14:"report_columns";a:7:{i:0;s:14:"duration_print";i:1;s:17:"print_edition_ids";i:2;s:19:"print_revenue_gross";i:3;s:17:"print_revenue_net";i:4;s:29:"published_print_revenue_gross";i:5;s:27:"published_print_revenue_net";i:6;s:19:"total_revenue_gross";}}s:4:"sort";a:2:{s:10:"sort_field";s:19:"ad_report_daily__id";s:8:"sort_ord";s:4:"desc";}}'
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
        $searchParam = array();

        //get options passed in command
        $searchParam = unserialize($input->getOption('criteria'));
        $offset      = $input->getOption('offset');
        if (isset($offset)) {
            $this->adReportExportToCsvWithOffset($searchParam, $input, $output);
        } else {
            $container               = $this->getContainer();
            $adReportDailyRepository = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:AdReportDaily');

            if (isset($searchParam['search']['csv_name']) && $searchParam['search']['csv_name']) {
                $fileName = $searchParam['search']['csv_name'].'.tmp';
                if (is_file($container->get('kernel')->getRootDir()."/../data/reports/ad/".$fileName)) {
                    unlink($container->get('kernel')->getRootDir()."/../data/reports/ad/".$fileName);
                }
            } else {
                $fileName = "TiAdReport_".date('d-m-Y H:i:s').'.tmp';
            }

            $file                    = fopen($container->get('kernel')->getRootDir()."/../data/reports/ad/".$fileName, "a+");
            $adReportFields          = $adReportDailyRepository->getAdReportFields();
            $adReportTextColumns     = array();

            $count = $this->getAdReportCount($searchParam);
            fputcsv($file, array('Total ads', $count));

            foreach ($searchParam['search']['report_columns'] as $reportColumn) {
                $adReportTextColumns[] = (isset($adReportFields[$reportColumn]) ? $adReportFields[$reportColumn] : '');
            }
            fputcsv($file, $adReportTextColumns);
            fclose($file);
            $input->setOption('file_name', $fileName);

            $this->adReportExportToCsv($searchParam, $input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array   $searchParam Search parameters.
     * @param integer $masterId    Id of master address book.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function adReportExportToCsvWithOffset($searchParam, $input, $output)
    {
        $isCountQuery = false;
        if (in_array('total_ads', $searchParam['search']['report_columns'])) {
            $isCountQuery = true;
        }

        $container               = $this->getContainer();
        $adReportDailyRepository = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:AdReportDaily');
        $qb                      = $adReportDailyRepository->getAdReportQuery($searchParam['search'], $searchParam['sort'], $this->getContainer(), $isCountQuery);
        $offset                  = $input->getOption('offset');
        $fileName                = $input->getOption('file_name');
        $entityCacheManager      = $this->getContainer()->get('fa.entity.cache.manager');

        if ($isCountQuery) {
            $qb->setMaxResults(1);
        } else {
            $qb->setFirstResult($offset);
            $qb->setMaxResults($this->limit);
        }

        $adReports = $qb->getArrayResult();
        if (count($adReports) > 0) {
            $adIdArray = array();
            if (in_array('published_print_revenue_gross', $searchParam['search']['report_columns']) || in_array('published_print_revenue_net', $searchParam['search']['report_columns'])) {
                foreach ($adReports as $adReport) {
                    $adIdArray[] = $adReport['ad_id'];
                }
                if (in_array('published_print_revenue_gross', $searchParam['search']['report_columns']) || in_array('published_print_revenue_net', $searchParam['search']['report_columns']) || in_array('print_insert_date', $searchParam['search']['report_columns']) || in_array('print_edition_ids', $searchParam['search']['report_columns'])) {
                    $adIdArray = array_unique($adIdArray);
                    $adPrintDatesEditionArray = $adReportDailyRepository->getAdPrintInsertDatesByAdIds($adIdArray, $searchParam['search'], $searchParam['sort'], $this->getContainer());
                    $adPrintDates = $adPrintDatesEditionArray[0];
                    $adPrintEditions = $adPrintDatesEditionArray[1];
                }
            }

            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/ad/".$fileName, "a+");
            $uniqueBit = $offset + 1;
            foreach ($adReports as $adReport) {
                $adReportDetail  = $adReportDailyRepository->formatAdReportRaw($adReport, $container, $uniqueBit);
                $adReportColumns = array();

                foreach ($searchParam['search']['report_columns'] as $reportColumn) {
                    if ($reportColumn == 'print_edition_ids' && isset($adPrintEditions[$adReport['ad_id']]) && isset($adPrintEditions[$adReport['ad_id']][$adReport['id']])) {
                        $printEditions = $adPrintEditions[$adReport['ad_id']][$adReport['id']];
                        $printEditionString = '';
                        foreach ($printEditions as $printEditionId) {
                            $printEditionString .= $entityCacheManager->getEntityNameById('FaAdBundle:PrintEdition', $printEditionId).',';
                        }
                        $adReportColumns[] = trim($printEditionString, ',');
                    } elseif (isset($adReportDetail[$reportColumn])) {
                        $adReportColumns[] = $adReportDetail[$reportColumn];
                    } elseif ($reportColumn == 'published_print_revenue_gross' or $reportColumn == 'published_print_revenue_net') {
                        $publishedPrintRevenueGross = 0;
                        if (!$adReport['skip_payment_reason']) {
                            $printRevenuePerEdition = 0;
                            $totalPrintEditions = explode(',', $adReport['print_edition_ids']);
                            if ($adReport['duration_print'] > 0 && count($totalPrintEditions) > 0 && isset($adPrintDates[$adReport['ad_id']]) && isset($adPrintDates[$adReport['ad_id']][$adReport['id']])) {
                                $printRevenuePerEdition = $adReport['print_revenue_gross']/(count($totalPrintEditions) * $adReport['duration_print']);
                                $weekDuration = (count($adPrintDates[$adReport['ad_id']][$adReport['id']]) > $adReport['duration_print'] ? $adReport['duration_print'] : count($adPrintDates[$adReport['ad_id']][$adReport['id']]));
                                if (count($adPrintDates[$adReport['ad_id']][$adReport['id']]) > $adReport['duration_print']) {
                                    if (in_array('print_edition_id', array_keys($searchParam['search']))) {
                                        $publishedPrintRevenueGross = ($printRevenuePerEdition * $weekDuration);
                                    } elseif (!in_array('print_edition_id', array_keys($searchParam['search']))) {
                                        $publishedPrintRevenueGross = ($printRevenuePerEdition * count($printEditions) * $weekDuration);
                                    }
                                } else {
                                    foreach ($adPrintDates[$adReport['ad_id']][$adReport['id']] as $adPrintInsertDate) {
                                        $printEditions = (isset($adPrintEditions[$adReport['ad_id']]) && isset($adPrintEditions[$adReport['ad_id']][$adReport['id']]) && isset($adPrintEditions[$adReport['ad_id']][$adReport['id']][$adPrintInsertDate]) ? $adPrintEditions[$adReport['ad_id']][$adReport['id']][$adPrintInsertDate] : array());
                                        if (in_array('print_edition_id', array_keys($searchParam['search']))) {
                                            $publishedPrintRevenueGross = $publishedPrintRevenueGross + $printRevenuePerEdition;
                                        } elseif (!in_array('print_edition_id', array_keys($searchParam['search']))) {
                                            $publishedPrintRevenueGross = $publishedPrintRevenueGross + ($printRevenuePerEdition * count($printEditions));
                                        }
                                    }
                                }
                            }
                        }
                        if ($reportColumn == 'published_print_revenue_gross') {
                            $adReportColumns[] = CommonManager::formatCurrency($publishedPrintRevenueGross, $this->getContainer());
                        } elseif ($reportColumn == 'published_print_revenue_net') {
                            $publishedPrintRevenueNet = CommonManager::getNetAmountFromGrossAmount($publishedPrintRevenueGross, $this->getContainer());
                            $adReportColumns[]        = CommonManager::formatCurrency($publishedPrintRevenueNet, $this->getContainer());
                        }
                    } elseif ($reportColumn == 'print_insert_date' && isset($adPrintDates[$adReport['ad_id']]) && isset($adPrintDates[$adReport['ad_id']][$adReport['id']])) {
                        $adReportColumns[] = CommonManager::formatDate(end($adPrintDates[$adReport['ad_id']][$adReport['id']]), $this->getContainer());
                    } else {
                        $adReportColumns[] = '-';
                    }
                }
                $adReportColumns[] = ' ';
                fputcsv($file, $adReportColumns);
                $uniqueBit++;
            }
            fclose($file);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array   $searchParam Search parameters.
     * @param integer $masterId    Id of master address book.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function adReportExportToCsv($searchParam, $input, $output)
    {
        $count                   = $this->getAdReportCount($searchParam);
        $stat_time               = time();

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
                    if ($option == 'verbose') {
                        $commandOptions .= ' --verbose';
                    } elseif ($option == 'criteria') {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:ti:ad:report:export-to-csv '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
        $reportPath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/ad/";
        $oldFileName = $input->getOption("file_name");
        $newFileName = str_replace('.tmp', '.csv', $oldFileName);
        rename($reportPath.$oldFileName, $reportPath.$newFileName);
        $filePath = $reportPath.$newFileName;
        // send email of csv
        if (isset($searchParam['search']['csv_email']) && $searchParam['search']['csv_email']) {
            $message = \Swift_Message::newInstance()
            ->setSubject('Ad report csv generated')
            ->setFrom($this->getContainer()->getParameter('mailer_sender_email'))
            ->setTo($searchParam['search']['csv_email'])
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'FaTiReportBundle:AdReportAdmin:email.html.twig',
                    array('email' => $searchParam['search']['csv_email'])
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
     * Get query builder for ad report.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdReportCount($searchParam)
    {
        if (in_array('total_ads', $searchParam['search']['report_columns'])) {
            return 1;
        } else {
            $qb = CommonManager::getTiHistoryRepository($this->getContainer(), 'FaTiReportBundle:AdReportDaily')->getAdReportQuery($searchParam['search'], $searchParam['sort'], $this->getContainer(), true);

            return $qb->getSingleScalarResult();
        }
    }
}
