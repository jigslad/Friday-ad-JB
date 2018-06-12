<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Doctrine\ORM\Mapping\Entity;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdFeedBundle\lib\IndeedJobs;
use Fa\Bundle\AdFeedBundle\lib\Fa\Bundle\AdFeedBundle\lib;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Entity\Category;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This command is use to generate CandP feed data
 *
 * @author Mohit Chauahn <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExportIndeedJobsFeedCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:export-indeed-jobs-feed')
        ->setDescription("Export Indeed Jobs Feed")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "512M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Export Indeed Jobs feed data

Command:
 - php app/console fa:export-indeed-jobs-feed'
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

        $offset = $input->getOption('offset');
        if (isset($offset)) {
            $this->searchResultWithOffset($input, $output);
        } else {
            $this->searchResult($input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function searchResultWithOffset($input, $output)
    {
        $step         = 1000;
        $offset       = $input->getOption('offset');
        $searchParams = array();

        $ads = $this->getAds($step, $offset);
        $objIJ = new IndeedJobs($this->getContainer());
        $objIJ->writeJobsData($ads, $offset);

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }



    /**
     * get ads from solr
     *
     * @param array   $searchParams search array
     * @param integer $step
     * @param integer $offset
     * @param string  $category
     *
     * @return multitype:
     */
    protected function getAds($step, $offset, $count = false)
    {
        $entityCache = $this->getContainer()->get('fa.entity.cache.manager');
        $solrManager = $this->getContainer()->get('fa.solrsearch.manager');

        $data = array();
        $data['query_filters']['item']['category_id'] = array(CategoryRepository::JOBS_ID);
        $data['query_filters']['item']['is_trade_ad'] = 1;
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;

        $solrManager->init('ad', null, $data, 1, $step, $offset);
        $solrResponse = $solrManager->getSolrResponse();

        // fetch result set from solr
        $result      = $solrManager->getSolrResponseDocs($solrResponse);
        $resultCount = $solrManager->getSolrResponseDocsCount($solrResponse);

        if ($count) {
            return $resultCount;
        }

        return $result;
    }

    /**
     * Update refresh date for ad.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function searchResult($input, $output)
    {
        $stat_time    = time();
        $searchParams = null;
        $step         = 1000;

        $objIJ = new IndeedJobs($this->getContainer());
        $objIJ->initFile();
        $count = $this->getAds($step, 0, true);

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('TOTAL ADS TO EXPORT: '.$count);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:export-indeed-jobs-feed -v '.$commandOptions;

            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $objIJ->fixFile();
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
