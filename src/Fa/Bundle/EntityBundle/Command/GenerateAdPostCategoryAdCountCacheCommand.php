<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used generate categories ad count cache for ad post.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateAdPostCategoryAdCountCacheCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:adpost-category-ad-count-cache')
        ->setDescription("Generate category ad count cache for ad post")
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Generate category ad count cache for ad post.

Command:
 - php app/console fa:generate:adpost-category-ad-count-cache
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
        $stat_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $firstLevelCategories = $entityManager->getRepository('FaEntityBundle:Category')->getCategoryByLevel(1);

        $categoryCountArray = array();
        foreach ($firstLevelCategories as $firstLevelCategory) {
            $categoryNestedArray = $entityManager->getRepository('FaEntityBundle:Category')->getNestedLeafChildrenIdsByCategoryId($firstLevelCategory->getId(), $this->getContainer());
            if (count($categoryNestedArray)) {
                $total = count($categoryNestedArray);
                $step  = 500;
                $loop  = ($total > $step) ? ceil($total / $step) : 1;

                $categoryArray = array();
                $start = 0;
                for ($i = 0; $i < $loop; $i++) {
                    $categoryArray[$i] = array_slice($categoryNestedArray, $start, $step, true);
                    $start += $step;
                }

                foreach ($categoryArray as $categoryIds) {
                    $data                  = array();
                    $data['query_filters'] = array('ad' => array('category_id' => $categoryIds));
                    $data['facet_fields']  = array('a_category_id_i' => array('min_count' => 1));

                    // initialize solr search manager service and fetch data based of above prepared search options
                    $this->getContainer()->get('fa.solrsearch.manager')->init('ad', null, $data);
                    $solrResponse = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponse();

                    // fetch result set from solr
                    $result             = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);
                    $categoryCountArray = $categoryCountArray + get_object_vars($result['a_category_id_i']);
                }
            }
        }

        $tableName = $entityManager->getClassMetadata('FaEntityBundle:Category')->getTableName();
        $cacheKey  = $tableName.'|adCount|adPost';
        CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $categoryCountArray);

        $output->writeln('Cache generated for category ad count.', true);
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}
