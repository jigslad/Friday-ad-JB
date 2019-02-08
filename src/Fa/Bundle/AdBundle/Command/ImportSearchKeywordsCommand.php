<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Entity\SearchKeyword;
use Fa\Bundle\AdBundle\Repository\SearchKeywordRepository;

/**
 * This command is used import search keywords from uploaded csv
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ImportSearchKeywordsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:import:search-keywords')
        ->setDescription("Import search keywords")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Import search keywords.

Command:
 - php app/console fa:import:search-keywords
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
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $file = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/import/search_keywords.csv';

        if (!file_exists($file)) {
            echo 'No file exist for import.'."\n";
            exit;
        }

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->importSearchKeywordsOffset($input, $output);
        } else {
            $this->importSearchKeywords($input, $output);
        }
    }

    /**
     * Update dimension with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function importSearchKeywordsOffset($input, $output)
    {
        $offset = $input->getOption('offset');

        $reader = new \EasyCSV\Reader($this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/import/search_keywords.csv');
        $reader->setDelimiter(',');
        $batchSize = 1000;
        $row = 0;
        $ad_id = array();
        $category = array();
        $row = $reader->getRow();

        if ($offset > 0) {
            $reader->advanceTo($offset-1);
        } else {
            $reader->advanceTo(1);
        }

        while (($row = $reader->getRow()) && $reader->getLineNumber() != $offset + $batchSize) {
            $searchKeyword = $this->em->getRepository('FaAdBundle:SearchKeyword')->findOneBy(array('keyword' => $row['Search Term']));

            $isNew       = false;
            $searchTerm  = null;
            $searchCount = 0;
            if ($row['Search Term'] && $row['Total Unique Searches']) {
                if ($searchKeyword) {
                    $searchTerm  = $searchKeyword->getKeyword();

                    if ($searchKeyword->getIsUpdated() == 1) {
                        $searchCount = ($searchKeyword->getSearchCount() + $row['Total Unique Searches']);
                    } else {
                        $searchCount = $row['Total Unique Searches'];
                    }
                } else {
                    $searchKeyword = new SearchKeyword();
                    $isNew         = true;
                    $searchTerm    = $row['Search Term'];
                    $searchCount   = $row['Total Unique Searches'];
                }

                $searchKeyword->setKeyword($searchTerm);
                $searchKeyword->setSearchCount($searchCount);
                $searchKeyword->setIsUpdated(1);
                $this->em->persist($searchKeyword);
                $this->em->flush($searchKeyword);

                $output->writeln('Keyword is '.($isNew ? 'added' : 'updated').' : '.$row['Search Term'], true);
            }
        }

        $this->em->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }


    /**
     * Update dimension.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function importSearchKeywords($input, $output)
    {
        $reader = new \EasyCSV\Reader($this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/import/search_keywords.csv');
        $reader->setDelimiter(',');
        $count     = $reader->getLastLineNumber();
        $step      = 1000;
        $stat_time = time();
        $returnVar = null;

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'="'.$value.'"';
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:import:search-keywords '.$commandOptions.' ';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        // Reset is_updated=0 after imported all keywords.
        $updateQuery = $this->em->getRepository('FaAdBundle:SearchKeyword')->createQueryBuilder(SearchKeywordRepository::ALIAS)
        ->update()
        ->set(SearchKeywordRepository::ALIAS.'.is_updated', 0)
        ->andWhere(SearchKeywordRepository::ALIAS.'.is_updated = :is_updated')
        ->setParameter('is_updated', 1);

        $updateQuery->getQuery()->execute();

        // move file to import directory
        if (file_exists($this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/import/search_keywords.csv')) {
            rename($this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/import/search_keywords.csv', $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/process/search_keywords.csv');
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
