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

/**
 * This command is used to add/update/delete solr index for ads.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class OptimizeAdSolrIndexCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:optimize:ad-solr-index')
        ->setDescription("Optimize solr index for ads.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', "256M")
        ->addOption('retry_counter', null, InputOption::VALUE_OPTIONAL, 'Retry counter', "0")
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Optimize ad solr index

Command:
 - php app/console fa:optimize:ad-solr-index
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

        $retryCounter = $input->getOption('retry_counter');
        $stat_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);

        try {
            $solrClient = $this->getContainer()->get('fa.solr.client.ad');
            if ($solrClient->ping()) {
                $solr = $solrClient->connect();
                $solr->optimize();
            }

            $solrClientNew = $this->getContainer()->get('fa.solr.client.ad.new');
            if ($solrClientNew->ping()) {
                $solrNew = $solrClientNew->connect();
                $solrNew->optimize();
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            $output->writeln('Error occurred during subtask', true);
            $retryCounter = $retryCounter+1;
            $input->setOption('retry_counter', $retryCounter);
            if ($retryCounter <= 4) {
                $commandOptions = null;
                foreach ($input->getOptions() as $option => $value) {
                    if ($value) {
                        $commandOptions .= ' --'.$option.'="'.$value.'"';
                    }
                }

                $memoryLimit = '';
                if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                    $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
                }
                $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:optimize:ad-solr-index '.$commandOptions;
                $output->writeln($command, true);
                passthru($command, $returnVar);

                if ($returnVar !== 0) {
                    $output->writeln('Error occurred during subtask', true);
                }
            }
        }
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
