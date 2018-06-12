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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ReportBundle\Repository\AdPrintReportDailyRepository;

/**
 * This command is used to export ad print report data in csv.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdPrintReportExportToCsvCommand extends ContainerAwareCommand
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
        ->setName('fa:ad:print:report:export-to-csv')
        ->setDescription("Ad print report export to csv")
        ->addOption('criteria', null, InputOption::VALUE_REQUIRED, 'Serialize string of ad report criteria')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('file_name', null, InputOption::VALUE_OPTIONAL, 'Name of csv file', null)
        ->setHelp(
            <<<EOF
Cron: Will be execute at run time in bg process

Actions:
-  Will be execute at run time in bg process whne user clicks export from ad print report.

Command:
 - php app/console fa:ad:print:report:export-to-csv --criteria='a:2:{s:6:"search";a:1:{s:6:"source";s:5:"admin";}s:4:"sort";a:2:{s:10:"sort_field";s:25:"ad_print_report_daily__id";s:8:"sort_ord";s:4:"desc";}}'
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
            $this->adPrintReportExportToCsvWithOffset($searchParam, $input, $output);
        } else {
            $container                    = $this->getContainer();
            $adPrintReportDailyRepository = CommonManager::getHistoryRepository($container, 'FaReportBundle:AdPrintReportDaily');

            if (isset($searchParam['search']['csv_name']) && $searchParam['search']['csv_name']) {
                $fileName = $searchParam['search']['csv_name'].'.tmp';
                if (is_file($container->get('kernel')->getRootDir()."/../data/reports/ad_print/".$fileName)) {
                    unlink($container->get('kernel')->getRootDir()."/../data/reports/ad_print/".$fileName);
                }
            } else {
                $fileName = "AdPrintReport_".date('d-m-Y H:i:s').'.tmp';
            }

            $file                = fopen($container->get('kernel')->getRootDir()."/../data/reports/ad_print/".$fileName, "a+");
            $adPrintReportFields = $adPrintReportDailyRepository->getAdPrintReportFields();
            $adReportTextColumns = array();

            $count = $this->getAdPrintReportCount($searchParam);
            fputcsv($file, array('Total print ads', $count));

            foreach ($adPrintReportFields as $reportColumn) {
                $adReportTextColumns[] = $reportColumn;
            }
            fputcsv($file, $adReportTextColumns);
            fclose($file);
            $input->setOption('file_name', $fileName);

            $this->adPrintReportExportToCsv($searchParam, $input, $output);
        }
    }

    /**
     * Export ad print report data to csv with offset.
     *
     * @param array   $searchParam Search parameters.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function adPrintReportExportToCsvWithOffset($searchParam, $input, $output)
    {
        $container               = $this->getContainer();
        $adPrintReportDailyRepository = CommonManager::getHistoryRepository($container, 'FaReportBundle:AdPrintReportDaily');
        $userReportRepository    = CommonManager::getHistoryRepository($container, 'FaReportBundle:UserReport');
        $qb                      = $adPrintReportDailyRepository->getAdPrintReportQuery($searchParam['search'], $searchParam['sort'], $this->getContainer());
        $offset                  = $input->getOption('offset');
        $fileName                = $input->getOption('file_name');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($this->limit);
        $adPrintReports = $qb->getArrayResult();
        $adPrintReportFields = $adPrintReportDailyRepository->getAdPrintReportFields();
        if (count($adPrintReports) > 0) {
            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/ad_print/".$fileName, "a+");
            $userIdArray = array();
            foreach ($adPrintReports as $adPrintReport) {
                $userIdArray[] = $adPrintReport['user_id'];
            }
            $userIdArray = array_unique($userIdArray);
            $userDataArray = $userReportRepository->getUserDetailByUserIds($userIdArray);

            foreach ($adPrintReports as $adPrintReport) {
                $adPrintReportDetail  = $adPrintReportDailyRepository->formatAdPrintReportRaw($adPrintReport, $container);
                $adPrintReportColumns = array();

                foreach ($adPrintReportFields as $reportKey => $reportColumn) {
                    $adPrintReportColumns[] = (isset($adPrintReportDetail[$reportKey]) ? $adPrintReportDetail[$reportKey] : ((isset($userDataArray[$adPrintReportDetail['user_id']]) && isset($userDataArray[$adPrintReportDetail['user_id']][$reportKey])) ? $userDataArray[$adPrintReportDetail['user_id']][$reportKey] : '-'));
                }
                $adPrintReportColumns[] = ' ';
                fputcsv($file, $adPrintReportColumns);
            }
            fclose($file);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Export ad print report data to csv.
     *
     * @param array   $searchParam Search parameters.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function adPrintReportExportToCsv($searchParam, $input, $output)
    {
        $count     = $this->getAdPrintReportCount($searchParam);
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:ad:print:report:export-to-csv '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
        $reportPath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/ad_print/";
        $oldFileName = $input->getOption("file_name");
        $newFileName = str_replace('.tmp', '.csv', $oldFileName);
        rename($reportPath.$oldFileName, $reportPath.$newFileName);
        $filePath = $reportPath.$newFileName;
        // send email of csv
        if (isset($searchParam['search']['csv_email']) && $searchParam['search']['csv_email']) {
            $message = \Swift_Message::newInstance()
            ->setSubject('Ad print report csv generated')
            ->setFrom($this->getContainer()->getParameter('mailer_sender_email'))
            ->setTo($searchParam['search']['csv_email'])
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'FaReportBundle:AdPrintReportAdmin:email.html.twig',
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
     * Get query builder for ad print report.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdPrintReportCount($searchParam)
    {
        $qb = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:AdPrintReportDaily')->getAdPrintReportQuery($searchParam['search'], $searchParam['sort'], $this->getContainer(), true);

        return $qb->getSingleScalarResult();
    }
}
