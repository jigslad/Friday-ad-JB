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
use Fa\Bundle\AdFeedBundle\lib\NewsNow;
use Fa\Bundle\AdFeedBundle\lib\Fa\Bundle\AdFeedBundle\lib;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Entity\Category;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This command is use to generate trovit feed data
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExportNewsNowFeedCommand extends ContainerAwareCommand
{
    protected $categoryMapping = array();

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:export-news-now-feed')
        ->setDescription("Export news-now-feed")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "512M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Export trovit feed data

Command:
 - php app/console fa:export-news-now-feed
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

        $this->setCategoryMapping();

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
        $step   = 1000;
        $offset = $input->getOption('offset');
        $page   = ($offset ? ($offset / $step) : 1);

        $ads     = $this->getAds($page, $step);
        $newsNow = new NewsNow($this->getContainer());
        $newsNow->writeAdsData($ads, $this->getCategoryMapping());

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }


    /**
     * get ads from solr
     *
     * @param integer $page
     * @param integer $step
     * @param boolean $count
     *
     * @return multitype:
     */
    protected function getAds($page, $step, $count = false)
    {
        $entityCache = $this->getContainer()->get('fa.entity.cache.manager');
        $solrManager = $this->getContainer()->get('fa.solrsearch.manager');

        $data               = array();
        $categoryMapping    = $this->getCategoryMapping();
        $newsNowCategoryIds = array_keys($categoryMapping);

        $newCategoryIds = $this->em->getRepository('FaEntityBundle:MappingCategory')->getNewCategoryIds($newsNowCategoryIds);
        $categoryIds    = array_merge($newCategoryIds, array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID));

        //$categoryIds    =  array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID);
        $data['query_filters']['item']['category_id'] = $categoryIds;
        $data['query_filters']['item']['status_id']   = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['is_feed_ad']  = 0;

        $solrManager->init('ad', null, $data, $page, $step);
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
        $stat_time = time();
        $step      = 1000;

        $newsNow = new NewsNow($this->getContainer());
        $newsNow->initFile();

        $count = $this->getAds(1, $step, true);

        $output->writeln('Total ads : '.$count, true);

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = $step; $i <= ($count+$step);) {
            $low = $i;
            $i   = ($i + $step);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:export-news-now-feed -v '.$commandOptions;

            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $newsNow->fixFile();
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Set category mapping
     */
    public function setCategoryMapping()
    {
        $this->categoryMapping = array();
        $file_handle = fopen(__DIR__."/newsNowFeedCategoryMapping.csv", "r");
        $i = 1;
        while (!feof($file_handle)) {
            $row = fgetcsv($file_handle, 1024);
            if ($row[0]) {
                $this->categoryMapping[$row[0]]['category'] = $row[1];
                $this->categoryMapping[$row[0]]['main_category'] = $row[2];
            }
            $i++;
        }
    }

    /**
     * Get category mapping
     */
    public function getCategoryMapping()
    {
        return $this->categoryMapping;
    }
}
