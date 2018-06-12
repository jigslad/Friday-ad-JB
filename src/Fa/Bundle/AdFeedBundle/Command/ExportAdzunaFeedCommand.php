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
use Fa\Bundle\AdFeedBundle\lib\Adzuna;
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
class ExportAdzunaFeedCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:export-adzuna-feed')
        ->setDescription("Export adzuna feed")
        ->addOption('category', null, InputOption::VALUE_REQUIRED, 'Ad category')
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "512M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Export Adzuna feed data

Command:
 - php app/console fa:export-adzuna-feed --category='Vehicles'
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

        $offset   = $input->getOption('offset');
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
        $step         = 500;
        $offset       = $input->getOption('offset');
        $category     = $input->getOption('category');
        $searchParams = array();

        if ($category == 'Vehicles') {
            $ads = $this->getAds($step, $offset, $category);
            $objAdzuna = new Adzuna($this->getContainer());
            $objAdzuna->writeVehiclesData($ads);
        } else {
            echo 'Category not found';
            exit;
        }

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
    protected function getAds($step, $offset, $category, $count = false)
    {
        $entityCache = $this->getContainer()->get('fa.entity.cache.manager');
        $solrManager = $this->getContainer()->get('fa.solrsearch.manager');

        $data = array();
        if ($category == 'Vehicles') {
            $data['query_filters']['item']['category_id'] = array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID);
            $userIds = $this->em->getRepository('FaCoreBundle:ConfigRule')->getAdzunaMotorsFeedUserIds($this->getContainer());
            $userIds = array_filter($userIds);
            if (count($userIds)) {
                $userIds = array_map('trim', $userIds);
                $data['query_filters']['item']['user_id'] = $userIds;
            }
            //$data['static_filters'] = ' AND ('.AdSolrFieldMapping::IS_FEED_AD.': 0 OR ('.AdSolrFieldMapping::IS_FEED_AD.': 1 AND '.AdSolrFieldMapping::AD_REF.': SN*) ) ';
        } else {
            echo 'Invalid Category'."\n";
            exit;
        }


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

        $step     = 500;

        $category = $input->getOption('category');

        $objAdzuna = new Adzuna($this->getContainer());
        $objAdzuna->initFile($category);
        $count = $this->getAds($step, 0, $category, true);

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('TOTAL ADS TO WRITE: '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:export-adzuna-feed -v '.$commandOptions;

            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $objAdzuna->fixFile($category);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
