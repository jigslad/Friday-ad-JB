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
class ExportTiUserProfileReportCommand extends ContainerAwareCommand
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
        ->setName('fa:ti:export-user-profile-report')
        ->setDescription("Export user profile report")
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
 - php app/console fa:ti:export-user-profile-report --criteria="xxxx"
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
            if (is_file($container->get('kernel')->getRootDir()."/../data/reports/user_profile/".$fileName)) {
                unlink($container->get('kernel')->getRootDir()."/../data/reports/user_profile/".$fileName);
            }
        } else {
            $fileName = "TiUserProfileReport_".date('d-m-Y H:i:s').".tmp";
        }

        if (isset($offset)) {
            $this->exportToCSVWithOffset($criteria, $input, $output);
        } else {
            $reportDefaultFiledsArray  = array("Name");
            $reportAllOtherFieldsArray = CommonManager::getUserProfileReportFieldsChoices();
            $reportFieldsArray = $reportDefaultFiledsArray;
            foreach ($criteria['rus_report_columns'] as $key => $value) {
                $reportFieldsArray[] = $reportAllOtherFieldsArray[$value];
            }

            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/user_profile/".$fileName, "a+");
            fputcsv($file, $reportFieldsArray);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:ti:export-user-profile-report '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);

        // send email of csv
        $reportPath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/user_profile/";
        $oldFileName = $input->getOption("file_name");
        $newFileName = str_replace('.tmp', '.csv', $oldFileName);
        rename($reportPath.$oldFileName, $reportPath.$newFileName);
        $filePath = $reportPath.$newFileName;
        if (isset($searchParam['rus_csv_email']) && $searchParam['rus_csv_email']) {
            $message = \Swift_Message::newInstance()
            ->setSubject('User profile report csv generated')
            ->setFrom($this->getContainer()->getParameter('mailer_sender_email'))
            ->setTo($searchParam['rus_csv_email'])
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'FaTiReportBundle:UserProfileReportAdmin:email.html.twig',
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
        $query     = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getUserProfileReportQuery($criteria, $criteria['sorter']);
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

            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/user_profile/".$fileName, "a+");
            foreach ($results as $record) {
                $processedRecord = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->processUserProfileRecord($record, $criteria, $container);
                $recordValues   = array();
                $recordValues[] = $processedRecord['name'];
                $currentUserId  = '';
                if (isset($processedRecord['user_id'])) {
                    $currentUserId = $processedRecord['user_id'];
                }
                foreach ($criteria['rus_report_columns'] as $key => $fieldName) {
                    if (isset($processedRecord[$fieldName])) {
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
        $query     = CommonManager::getTiHistoryRepository($container, 'FaTiReportBundle:UserReport')->getUserProfileReportQuery($searchParam);
        $results   = $query->execute();

        return count($results);
    }
}
