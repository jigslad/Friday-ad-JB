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

/**
 * This command is used to export automated email report data in csv.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AutomatedEmailReportExportToCsvCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 20;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:automated-email:report:export-to-csv')
        ->setDescription("Automated email report export to csv")
        ->addOption('criteria', null, InputOption::VALUE_REQUIRED, 'Serialize string of automated email report criteria')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('file_name', null, InputOption::VALUE_OPTIONAL, 'Name of csv file', null)
        ->setHelp(
            <<<EOF
Cron: Will be execute at run time in bg process

Actions:
-  Will be execute at run time in bg process whne user clicks export from automated email report.

Command:
 - php app/console fa:automated-email:report:export-to-csv --criteria='a:2:{s:6:"search";a:3:{s:9:"from_date";s:10:"21/12/2015";s:7:"to_date";s:10:"21/12/2015";s:6:"parsed";s:1:"1";}s:4:"sort";a:2:{s:10:"sort_field";s:32:"automated_email_report_daily__id";s:8:"sort_ord";s:4:"desc";}}'
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
            $this->automatedEmailReportExportToCsvWithOffset($searchParam, $input, $output);
        } else {
            $container = $this->getContainer();
            $automatedEmailReportDailyRepository = CommonManager::getHistoryRepository($container, 'FaReportBundle:AutomatedEmailReportDaily');

            if (isset($searchParam['search']['csv_name']) && $searchParam['search']['csv_name']) {
                $fileName = $searchParam['search']['csv_name'].'.tmp';
                if (is_file($container->get('kernel')->getRootDir()."/../data/reports/automated_email/".$fileName)) {
                    unlink($container->get('kernel')->getRootDir()."/../data/reports/automated_email/".$fileName);
                }
            } else {
                $fileName = "AutomatedEmailReport_".date('d-m-Y H:i:s').'.tmp';
            }

            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/automated_email/".$fileName, "a+");
            $automatedEmailReportFields = $automatedEmailReportDailyRepository->getAdReportFields();
            $automatedEmailReportTextColumns = array();

            $count = $this->getAutomatedEmailReportCount($searchParam);
            fputcsv($file, array('Total templates', $count));

            foreach ($automatedEmailReportFields as $reportColumn) {
                $automatedEmailReportTextColumns[] = $reportColumn;
            }
            fputcsv($file, $automatedEmailReportTextColumns);
            fclose($file);
            $input->setOption('file_name', $fileName);

            $this->automatedEmailReportExportToCsv($searchParam, $input, $output);
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
    protected function automatedEmailReportExportToCsvWithOffset($searchParam, $input, $output)
    {
        $container               = $this->getContainer();
        $automatedEmailReportDailyRepository = CommonManager::getHistoryRepository($container, 'FaReportBundle:AutomatedEmailReportDaily');
        $emailTemplateRepository = CommonManager::getEntityRepository($container, 'FaEmailBundle:EmailTemplate');
        $qb                      = $automatedEmailReportDailyRepository->getAutomatedEmailReportQuery($searchParam['search'], $searchParam['sort'], $this->getContainer());
        $offset                  = $input->getOption('offset');
        $fileName                = $input->getOption('file_name');
        $automatedEmailReportFields = $automatedEmailReportDailyRepository->getAdReportFields();
        $allEmailTemplates = $emailTemplateRepository->getAllEmailTemplateIdentifierArray();

        $qb->setFirstResult($offset);
        $qb->setMaxResults($this->limit);

        $automatedEmailReports = $qb->getArrayResult();
        if (count($automatedEmailReports) > 0) {
            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/automated_email/".$fileName, "a+");
            foreach ($automatedEmailReports as $automatedEmailReport) {
                $automatedEmailReportColumns = array();
                foreach ($automatedEmailReportFields as $filedId => $fieldName) {
                    if ($filedId == 'identifier') {
                        if (isset($allEmailTemplates[$automatedEmailReport[$filedId]])) {
                            $automatedEmailReportColumns[] = $allEmailTemplates[$automatedEmailReport[$filedId]];
                        } else {
                            $automatedEmailReportColumns[] = $automatedEmailReport[$filedId];
                        }
                    } elseif (isset($automatedEmailReport[$filedId])) {
                        $automatedEmailReportColumns[] = $automatedEmailReport[$filedId];
                    } else {
                        $automatedEmailReportColumns[] = '-';
                    }
                }
                $automatedEmailReportColumns[] = ' ';
                fputcsv($file, $automatedEmailReportColumns);
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
    protected function automatedEmailReportExportToCsv($searchParam, $input, $output)
    {
        $count     = $this->getAutomatedEmailReportCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:automated-email:report:export-to-csv '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
        $reportPath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/automated_email/";
        $oldFileName = $input->getOption("file_name");
        $newFileName = str_replace('.tmp', '.csv', $oldFileName);
        rename($reportPath.$oldFileName, $reportPath.$newFileName);
        $filePath = $reportPath.$newFileName;
        // send email of csv
        if (isset($searchParam['search']['csv_email']) && $searchParam['search']['csv_email']) {
            $message = \Swift_Message::newInstance()
            ->setSubject('Automated email report csv generated')
            ->setFrom($this->getContainer()->getParameter('mailer_sender_email'))
            ->setTo($searchParam['search']['csv_email'])
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'FaReportBundle:AutomatedEmailReportAdmin:email.html.twig',
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
     * Get query builder for automated email report.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAutomatedEmailReportCount($searchParam)
    {
        $qb = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:AutomatedEmailReportDaily')->getAutomatedEmailReportQuery($searchParam['search'], $searchParam['sort'], $this->getContainer(), true);

        return $qb->getSingleScalarResult();
    }
}
