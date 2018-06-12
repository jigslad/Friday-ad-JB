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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Doctrine\ORM\Query;

/**
 * This command is used to export ad report data in csv.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExportAdFeedClickReportCommand extends ContainerAwareCommand
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
        ->setName('fa:export-ad-feed-click-report')
        ->setDescription("Ad feed click report export to csv")
        ->addOption('criteria', null, InputOption::VALUE_REQUIRED, 'Serialize string of ad report criteria')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('file_name', null, InputOption::VALUE_OPTIONAL, 'Name of csv file', null)
        ->setHelp(
            <<<EOF
Cron: Will be execute at run time in bg process

Actions:
-  Will be execute at run time in bg process whne user clicks export from ad feed click report.

Command:
 - php app/console fa:export-ad-feed-click-report --criteria='a:2:{s:6:"search";a:3:{s:9:"from_date";s:10:"01/06/2015";s:7:"to_date";s:10:"15/06/2015";s:14:"report_columns";a:2:{i:0;s:2:"id";i:1;s:5:"ad_id";}}s:4:"sort";a:2:{s:10:"sort_field";s:19:"ad_report_daily__id";s:8:"sort_ord";s:4:"desc";}}'
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
            $this->adFeedClickReportExportToCsvWithOffset($searchParam, $input, $output);
        } else {
            $container               = $this->getContainer();
            $adFeedClickReportRepository = CommonManager::getHistoryRepository($container, 'FaReportBundle:AdFeedClickReportDaily');

            if (isset($searchParam['search']['csv_name']) && $searchParam['search']['csv_name']) {
                $fileName = $searchParam['search']['csv_name'].'.tmp';
                if (is_file($container->get('kernel')->getRootDir()."/../data/reports/adFeedClick/".$fileName)) {
                    unlink($container->get('kernel')->getRootDir()."/../data/reports/adFeedClick/".$fileName);
                }
            } else {
                $fileName = "AdFeedClickReport_".date('d-m-Y H:i:s').'.tmp';
            }

            $file              = fopen($container->get('kernel')->getRootDir()."/../data/reports/adFeedClick/".$fileName, "a+");
            $reportFields      = $adFeedClickReportRepository->getAdFeedClickReportFields($container);
            $reportTextColumns = array();

            foreach ($searchParam['search']['report_columns'] as $reportColumn) {
                $reportTextColumns[] = (isset($reportFields[$reportColumn]) ? $reportFields[$reportColumn] : '');
            }
            fputcsv($file, $reportTextColumns);
            fclose($file);
            $input->setOption('file_name', $fileName);

            $this->adFeedClickReportExportToCsv($searchParam, $input, $output);
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
    protected function adFeedClickReportExportToCsvWithOffset($searchParam, $input, $output)
    {
        $container                 = $this->getContainer();
        $this->entityManager       = $container->get('doctrine')->getManager();
        $feedSiteArray = $this->entityManager->getRepository('FaAdFeedBundle:AdFeedSite')->getFeedSiteArray();
        
        $adFeedClickReportRepository = CommonManager::getHistoryRepository($container, 'FaReportBundle:AdFeedClickReportDaily');
        $qb                        = $adFeedClickReportRepository->getAdFeedClickReportDailyQuery($searchParam['search'], $searchParam['sort'], $this->getContainer());
        if(!empty($searchParam['search']['ad_feed_site_id']) || isset($searchParam['search']['feed_report_type'])) {
            $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\ReportBundle\Walker\AdFeedClickReportSqlWalker');
            $qb->setHint("adFeedClickReportRemoveId.ad_feed_site_id", true);
        } else {
            $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\ReportBundle\Walker\AdFeedClickReportSqlWalker');
            $qb->setHint("adFeedClickReportRemoveId.ad_id", true);
        }
        $offset                    = $input->getOption('offset');
        $fileName                  = $input->getOption('file_name');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($this->limit);

        $adReports = $qb->getArrayResult();
        if (count($adReports) > 0) {
            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/adFeedClick/".$fileName, "a+");
            $uniqueBit = $offset + 1;
            foreach ($adReports as $adReport) {
                $adReportDetail  = $adFeedClickReportRepository->formatAdFeedClickReportRaw($adReport, $container, $uniqueBit);
                $adReportColumns = array();

                foreach ($searchParam['search']['report_columns'] as $reportColumn) {
                    if($reportColumn == 'ad_feed_site_id') {
                      $adReportDetail[$reportColumn] = $feedSiteArray[$adReportDetail[$reportColumn]];
                    }
                    $adReportColumns[] = (isset($adReportDetail[$reportColumn]) ? $adReportDetail[$reportColumn] : '-');
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
    protected function adFeedClickReportExportToCsv($searchParam, $input, $output)
    {
        $count     = $this->getAdFeedClickReportCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:export-ad-feed-click-report '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
        $reportPath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/adFeedClick/";
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
                    'FaReportBundle:AdFeedClickReportAdmin:email.html.twig',
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
    protected function getAdFeedClickReportCount($searchParam)
    {
        $qb = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:AdFeedClickReportDaily')->getAdFeedClickReportDailyQuery($searchParam['search'], $searchParam['sort'], $this->getContainer(), true);
        if(!empty($searchParam['search']['ad_feed_site_id']) || isset($searchParam['search']['feed_report_type'])) {
          $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\ReportBundle\Walker\AdFeedClickReportSqlWalker');
          $qb->setHint("adFeedClickReportRemoveId.ad_feed_site_id", true);
        } else {
          $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\ReportBundle\Walker\AdFeedClickReportSqlWalker');
          $qb->setHint("adFeedClickReportRemoveId.ad_id", true);
        }
        
        return $qb->getSingleScalarResult();
    }
}