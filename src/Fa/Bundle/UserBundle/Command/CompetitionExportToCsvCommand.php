<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to export competition report data in csv.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CompetitionExportToCsvCommand extends ContainerAwareCommand
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
        ->setName('fa:user:competition:export-to-csv')
        ->setDescription("Ad report export to csv")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('file_name', null, InputOption::VALUE_OPTIONAL, 'Name of csv file', null)
        ->setHelp(
            <<<EOF
Cron: Will be execute as needed

Actions:
-  Will be execute as needed.

Command:
 - php app/console fa:user:competition:export-to-csv
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
        $offset      = $input->getOption('offset');
        if (isset($offset)) {
            $this->userCompetitionExportToCsvWithOffset($searchParam, $input, $output);
        } else {
            $container               = $this->getContainer();
            $competitionRepository = CommonManager::getEntityRepository($container, 'FaUserBundle:Competition');

            $fileName = "CompetitionReport_".date('d-m-Y H:i:s').'.tmp';
            $file              = fopen($container->get('kernel')->getRootDir()."/../data/reports/competition/".$fileName, "a+");
            $competitionFields = $competitionRepository->getCompetitionFields();
            $count = $this->getCompetitionReportCount($searchParam);
            fputcsv($file, array('Total users', $count));

            fputcsv($file, $competitionFields);
            fclose($file);
            $input->setOption('file_name', $fileName);

            $this->userCompetitionExportToCsv($searchParam, $input, $output);
        }
    }

    /**
     * User competition report with given offset.
     *
     * @param array   $searchParam Search parameters.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function userCompetitionExportToCsvWithOffset($searchParam, $input, $output)
    {
        $container               = $this->getContainer();
        $competitionRepository   = CommonManager::getEntityRepository($container, 'FaUserBundle:Competition');
        $qb                      = $competitionRepository->getCompetitionReportQuery(array(), array(), $this->getContainer(), false);
        $offset                  = $input->getOption('offset');
        $fileName                = $input->getOption('file_name');
        $entityCacheManager      = $this->getContainer()->get('fa.entity.cache.manager');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($this->limit);

        $competitionReports = $qb->getArrayResult();
        if (count($competitionReports) > 0) {
            $competitionFields = array_keys($competitionRepository->getCompetitionFields());
            $file = fopen($container->get('kernel')->getRootDir()."/../data/reports/competition/".$fileName, "a+");
            foreach ($competitionReports as $competitionReport) {
                $competitionReportDetail  = $competitionRepository->formatReportRaw($competitionReport, $container);
                $competitionReportColumns = array();

                foreach ($competitionFields as $reportColumn) {
                    if (isset($competitionReportDetail[$reportColumn])) {
                        $competitionReportColumns[] = $competitionReportDetail[$reportColumn];
                    } else {
                        $competitionReportColumns[] = '-';
                    }
                }
                $competitionReportColumns[] = ' ';
                fputcsv($file, $competitionReportColumns);
            }
            fclose($file);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * User competition report.
     *
     * @param array   $searchParam Search parameters.
     * @param object  $input       Input object.
     * @param object  $output      Output object.
     */
    protected function userCompetitionExportToCsv($searchParam, $input, $output)
    {
        $count      = $this->getCompetitionReportCount($searchParam);
        $stat_time  = time();

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:user:competition:export-to-csv '.$commandOptions.' -v';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);

        $reportPath  = $this->getContainer()->get('kernel')->getRootDir()."/../data/reports/competition/";
        $oldFileName = $input->getOption("file_name");
        $newFileName = str_replace('.tmp', '.csv', $oldFileName);
        rename($reportPath.$oldFileName, $reportPath.$newFileName);
    }

    /**
     * Get query builder for ad report.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getCompetitionReportCount($searchParam)
    {
        $qb = CommonManager::getEntityRepository($this->getContainer(), 'FaUserBundle:Competition')->getCompetitionReportQuery(array(), array(), $this->getContainer(), true);

        return $qb->getSingleScalarResult();
    }
}
