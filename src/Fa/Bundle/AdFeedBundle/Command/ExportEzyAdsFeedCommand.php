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
use Fa\Bundle\AdFeedBundle\lib\EzyAds;
// use Fa\Bundle\AdFeedBundle\lib\Fa\Bundle\AdFeedBundle\lib;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Entity\Category;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\AdFeedBundle\lib\Export;

/**
 * This command is use to generate trovit feed data
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExportEzyAdsFeedCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:export-ezy-ads-feed')
        ->setDescription("Export ezy-ads-feed")
        ->addOption('category', null, InputOption::VALUE_REQUIRED, 'Category', null)
        ->addOption('last_days', null, InputOption::VALUE_REQUIRED, 'Result of last modified days', 7)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "512M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Export ezy ads feeds

Command:
 - php app/console fa:export-ezy-ads-feed
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

        $this->em->getConnection()
        ->getConfiguration()
        ->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger())
        ;
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        //get options passed in command
        $offset   = $input->getOption('offset');

        $searchParam = array();

        $searchParam['category']  = $input->getOption('category');
        $searchParam['last_days'] = $input->getOption('last_days');
        $ezyAds = new EzyAds($this->getContainer());

        if (isset($offset)) {
            $this->updateDimensionWithOffset($searchParam, $input, $output, $ezyAds);
        } else {
            $ezyAds->initFile($searchParam['category']);
            $this->updateDimension($searchParam, $input, $output);
            $ezyAds->fixFile($searchParam['category']);
        }
    }

    /**
     * Update dimension with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimensionWithOffset($searchParam, $input, $output, EzyAds $ezyAds)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $step        = 1000;
        $offset      = $input->getOption('offset');
        $ads         = $this->getAdQueryBuilder($searchParam, $offset, $step);

        $ids = array();

        foreach ($ads as $ad) {
            $ids[] = $ad['id'];
        }

        $id_string = implode(',', $ids)."\n";

        $solrAds =  $this->getSolrAds($ids);

        if ($searchParam['category'] == 'Cars') {
            $ezyAds->writeVehiclesData($solrAds, EzyAds::CAR_FILE);
        } elseif ($searchParam['category'] == 'OnlyCars') {
            $ezyAds->writeVehiclesData($solrAds, EzyAds::ONLY_CAR_FILE);
        } elseif ($searchParam['category'] == 'Forsale') {
            $ezyAds->writeProductData($solrAds);
        } elseif ($searchParam['category'] == 'Gardening') {
            $ezyAds->writeProductData($solrAds, $searchParam['category']);
        } elseif ($searchParam['category'] == 'Pets') {
            $ezyAds->writePetsData($solrAds);
        } elseif ($searchParam['category'] == 'Property') {
            $ezyAds->writePropertyData($solrAds);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update dimension.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimension($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:export-ezy-ads-feed '.$commandOptions.' -v';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    protected function getAdQueryBuilder($searchParam, $offset, $step)
    {
        if ($searchParam['category'] == 'Cars') {
            $query = 'SELECT a.id FROM ad a
                     LEFT JOIN ad_location al ON a.id = al.ad_id
                     LEFT JOIN ad_motors am ON a.id = am.ad_id
                     LEFT JOIN user u ON a.user_id = u.id
                     WHERE a.status_id = 25
                     AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                     AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                     AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (456) AND node.rgt - node.lft =1)
                     AND (a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                     AND a.price > 0
                     AND al.town_id > 0
                     AND am.fuel_type_id > 0
                     AND am.transmission_id > 0
                     AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                     ORDER BY a.id ASC LIMIT '.$offset.', '.$step;
        } elseif ($searchParam['category'] == 'OnlyCars') {
            $query = 'SELECT a.id FROM ad a
                     LEFT JOIN ad_location al ON a.id = al.ad_id
                     LEFT JOIN ad_motors am ON a.id = am.ad_id
                     LEFT JOIN user u ON a.user_id = u.id
                     WHERE a.status_id = 25
                     AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (456) AND node.rgt - node.lft =1)
                     AND (a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                     ORDER BY a.id ASC LIMIT '.$offset.', '.$step;
        }elseif ($searchParam['category'] == 'Forsale') {

            $query = 'SELECT a.id FROM ad a
                     LEFT JOIN ad_location al ON a.id = al.ad_id
                     LEFT JOIN user u ON a.user_id = u.id
                     WHERE a.status_id = 25
                     AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                     AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                     AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (2) AND node.rgt - node.lft =1)
                     AND a.category_id NOT IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (266) AND node.rgt - node.lft =1)
                     AND ( a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                     AND a.price > 0
                     AND al.town_id > 0
                     AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                     ORDER BY a.id ASC LIMIT '.$offset.', '.$step;

        } elseif ($searchParam['category'] == 'Gardening') {

            $query = 'SELECT a.id FROM ad a
                     LEFT JOIN ad_location al ON a.id = al.ad_id
                     LEFT JOIN user u ON a.user_id = u.id
                     WHERE a.status_id = 25
                     AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                     AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                     AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (266) AND node.rgt - node.lft =1)
                     AND ( a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                     AND a.price > 0
                     AND al.town_id > 0
                     AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                     ORDER BY a.id ASC LIMIT '.$offset.', '.$step;

        } elseif ($searchParam['category'] == 'Pets') {

            $query = 'SELECT a.id FROM ad a
                     LEFT JOIN ad_location al ON a.id = al.ad_id
                     LEFT JOIN user u ON a.user_id = u.id
                     WHERE a.status_id = 25
                     AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                     AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                     AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (758, 726, 766) AND node.rgt - node.lft =1)
                     AND ( a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                     AND a.price > 0
                     AND al.town_id > 0
                     AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                     ORDER BY a.id ASC LIMIT '.$offset.', '.$step;

        } elseif ($searchParam['category'] == 'Property') {

            $query = 'SELECT a.id FROM ad a
                     LEFT JOIN ad_location al ON a.id = al.ad_id
                     LEFT JOIN user u ON a.user_id = u.id
                     WHERE a.status_id = 25
                     AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                     AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                     AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (678) AND node.rgt - node.lft =1)
                     AND ( a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                     AND a.price > 0
                     AND al.town_id > 0
                     AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                     ORDER BY a.id ASC LIMIT '.$offset.', '.$step;

        }

        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $ads = $stmt->fetchAll();
        return $ads;

    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdCount($searchParam)
    {
        if ($searchParam['category'] == 'Cars') {

            $query = 'SELECT count(a.id) AS count FROM ad a
                     LEFT JOIN ad_location al ON a.id = al.ad_id
                     LEFT JOIN ad_motors am ON a.id = am.ad_id
                     LEFT JOIN user u ON a.user_id = u.id
                     WHERE a.status_id = 25
                     AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                     AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                     AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (456) AND node.rgt - node.lft =1)
                     AND (a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                     AND a.price > 0
                     AND al.town_id > 0
                     AND am.fuel_type_id > 0
                     AND am.transmission_id > 0
                     AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                     ORDER BY a.id';

        } elseif ($searchParam['category'] == 'OnlyCars') {

            $query = 'SELECT count(a.id) AS count FROM ad a
                     LEFT JOIN ad_location al ON a.id = al.ad_id
                     LEFT JOIN ad_motors am ON a.id = am.ad_id
                     LEFT JOIN user u ON a.user_id = u.id
                     WHERE a.status_id = 25
                     AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (456) AND node.rgt - node.lft =1)
                     AND (a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                     ORDER BY a.id';

        } elseif ($searchParam['category'] == 'Forsale') {

            $query =     'SELECT count(a.id) AS count FROM ad a
                        LEFT JOIN ad_location al ON a.id = al.ad_id
                        LEFT JOIN user u ON a.user_id = u.id
                        WHERE a.status_id = 25
                        AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                        AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                        AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (2) AND node.rgt - node.lft =1)
                        AND a.category_id NOT IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (266) AND node.rgt - node.lft =1)
                        AND (a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                        AND a.price > 0
                        AND al.town_id > 0
                        AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                        ORDER BY a.id';
        } elseif ($searchParam['category'] == 'Gardening') {

            $query =     'SELECT count(a.id) AS count FROM ad a
                        LEFT JOIN ad_location al ON a.id = al.ad_id
                        LEFT JOIN user u ON a.user_id = u.id
                        WHERE a.status_id = 25
                        AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                        AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                        AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (266) AND node.rgt - node.lft =1)
                        AND ( a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                        AND a.price > 0
                        AND al.town_id > 0
                        AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                        ORDER BY a.id';
        } elseif ($searchParam['category'] == 'Pets') {

            $query =    'SELECT count(a.id) AS count FROM ad a
                        LEFT JOIN ad_location al ON a.id = al.ad_id
                        LEFT JOIN user u ON a.user_id = u.id
                        WHERE a.status_id = 25
                        AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                        AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                        AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (758, 726, 766) AND node.rgt - node.lft =1)
                        AND ( a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                        AND a.price > 0
                        AND al.town_id > 0
                        AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                        ORDER BY a.id';
        } elseif ($searchParam['category'] == 'Property') {

            $query =    'SELECT count(a.id) AS count FROM ad a
                        LEFT JOIN ad_location al ON a.id = al.ad_id
                        LEFT JOIN user u ON a.user_id = u.id
                        WHERE a.status_id = 25
                        AND al.town_id in (SELECT l.town_id from location_group_location l where l.location_group_id != 13)
                        AND a.id NOT IN (SELECT DISTINCT ap.ad_id FROM ad_print ap WHERE ap.insert_date > UNIX_TIMESTAMP() AND ap.is_paid = 1)
                        AND a.category_id IN (SELECT node.id FROM category AS node, category AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id IN (678) AND node.rgt - node.lft =1)
                        AND ( a.is_feed_ad = 0 OR a.is_feed_ad IS NULL)
                        AND a.price > 0
                        AND al.town_id > 0
                        AND (a.published_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )) OR a.updated_at >= UNIX_TIMESTAMP((CURDATE() - INTERVAL '.$searchParam['last_days'].' DAY )))
                        ORDER BY a.id';
        }

        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchAll();
        return $count[0]['count'];
    }


    /**
     * get ads from solr
     *
     * @param array  $ids
     *
     * @return multitype:
     */
    protected function getSolrAds($ids)
    {
        $entityCache = $this->getContainer()->get('fa.entity.cache.manager');
        $solrManager = $this->getContainer()->get('fa.solrsearch.manager');
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['id'] = $ids;

        $solrManager->init('ad', null, $data, 1, 1000, 0);
        $solrResponse = $solrManager->getSolrResponse();

        // fetch result set from solr
        $result      = $solrManager->getSolrResponseDocs($solrResponse);
        return $result;
    }
}
