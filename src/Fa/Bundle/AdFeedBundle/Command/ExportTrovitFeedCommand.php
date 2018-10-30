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
use Fa\Bundle\AdFeedBundle\lib\Trovit;
// use Fa\Bundle\AdFeedBundle\lib\Fa\Bundle\AdFeedBundle\lib;
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
class ExportTrovitFeedCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:export-trovit-feed')
        ->setDescription("Export trovit-feed")
        ->addOption('category', null, InputOption::VALUE_REQUIRED, 'Ad category')
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "512M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Export trovit feed data

Command:
 - php app/console fa:export-trovit-feed'
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
        $step         = 1000;
        $offset       = $input->getOption('offset');
        $category     = $input->getOption('category');
        $searchParams = array();

        if ($category == 'Vehicles') {
            $ads = $this->getAds($step, $offset, $category);
            $trovite = new Trovit($this->getContainer());
            $trovite->writeVehiclesData($ads);
        } elseif ($category == 'Jobs') {
            $ads = $this->getAds($step, $offset, $category);
            $trovite = new Trovit($this->getContainer());
            $trovite->writeJobData($ads);
        } elseif ($category == 'Products') {
            $pcategories = $this->getTrovitProducts();
            $cat_a = array();
            foreach ($pcategories as $cat => $cat_ids) {
                $cat_a = array_merge($cat_a, $cat_ids);
            }
            $ads = $this->getAds($step, $offset, $category, false, $cat_a);
            $trovite = new Trovit($this->getContainer());
            $trovite->writeProductData($ads);
        } elseif ($category == 'Property') {
            $pcategories = $this->getTrovitPropertyTypes();
            $cat_a = array();
            foreach ($pcategories as $cat => $cat_ids) {
                $cat_a = array_merge($cat_a, $cat_ids);
            }
            $ads = $this->getAds($step, $offset, $category, false, $cat_a);
            $trovite = new Trovit($this->getContainer());
            $trovite->writePropertyData($ads);
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
     * @param array  $ids
     *
     * @return multitype:
     */
    protected function getAds($step, $offset, $category, $count = false, $ids = null)
    {
        $entityCache = $this->getContainer()->get('fa.entity.cache.manager');
        $solrManager = $this->getContainer()->get('fa.solrsearch.manager');

        $data = array();
        if ($category == 'Vehicles') {
            $data['query_filters']['item']['category_id'] = array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID);
            $data['static_filters'] = ' AND ('.AdSolrFieldMapping::IS_FEED_AD.': 0 OR ('.AdSolrFieldMapping::IS_FEED_AD.': 1 AND '.AdSolrFieldMapping::AD_REF.': SN*) ) ';
        } elseif ($category == 'Jobs') {
            $data['query_filters']['item']['category_id'] = CategoryRepository::JOBS_ID;
            $data['static_filters'] = ' AND ('.AdSolrFieldMapping::IS_FEED_AD.': 0 ) ';
        } elseif ($category == 'Products') {
            $data['query_filters']['item']['category_id'] = $ids;
            $data['static_filters'] = ' AND ('.AdSolrFieldMapping::IS_FEED_AD.': 0 ) ';
        } elseif ($category == 'Property') {
            $data['query_filters']['item']['category_id'] = $ids;
            $data['static_filters'] = ' AND ('.AdSolrFieldMapping::IS_FEED_AD.': 0 ) ';
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
     * get trovit product categories
     *
     * @param  $string
     *
     * @return array
     */
    protected function getTrovitProducts()
    {
        $cat = array();
        $cat['Furniture'] = array(159,269,343,351,300,327,329,330,331,332,336,340,341,342);
        $cat['Sports Equipment'] = array(408,422,425,431);
        $cat['Electronics and Technology'] = array(382,57,71,84,102);
        $cat['Pets'] = array(726,757);
        $cat['Phones'] = array(98);
        $cat['Musical Instruments'] = array(363);
        $cat['Kids'] = array(127,8,143,149,150,151);
        $cat['Photography'] = array(88);
        $cat['Art and Collectables'] = array(40,323);
        $cat['Fashion'] = array(117,104,132,137,147,148,155,156,157);
        $cat['Health and Beauty'] = array(152);
        $cat['Car parts and Accessories'] = array(95,489,490,494);
        return $cat;
    }


    protected function getTrovitPropertyTypes()
    {
        $cat = array();
        $cat['For Rent']        = array(680,681,682,683,684,685,695);
        $cat['For Sale']         = array(699,700,701,702,703);
        $cat['Roommate']         = array(718,720,719);
        $cat['Office For Rent']  = array(686);
        $cat['Parking for Rent'] = array(685,684);
        $cat['Land for sale']    = array(710);
        return $cat;
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

        $step     = 1000;

        $cat_a = array();
        $category = $input->getOption('category');

        if ($category == 'Property') {
            $pcategories = $this->getTrovitPropertyTypes();
            $cat_a = array();
            foreach ($pcategories as $cat => $cat_ids) {
                $cat_a = array_merge($cat_a, $cat_ids);
            }
        } elseif ($category == 'Products') {
            $pcategories = $this->getTrovitProducts();
            $cat_a = array();
            foreach ($pcategories as $cat => $cat_ids) {
                $cat_a = array_merge($cat_a, $cat_ids);
            }
        }

        $trovite = new Trovit($this->getContainer());
        $trovite->initFile($category);
        $count = $this->getAds($step, 0, $category, true, $cat_a);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:export-trovit-feed -v '.$commandOptions;

            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $trovite->fixFile($category);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
