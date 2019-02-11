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
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Entity\SearchKeywordCategory;
use Fa\Bundle\AdBundle\Repository\SearchKeywordCategoryRepository;

/**
 * This command is used to process search keywords.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ProcessSearchKeywordsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:process:search-keywords')
        ->setDescription("Process search keywords.")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('keyword', null, InputOption::VALUE_OPTIONAL, 'Keyword', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Process search keywords.

Command:
 - php app/console fa:process:search-keywords
 - php app/console fa:process:search-keywords --keyword="XXX"
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

        $solrClient = $this->getContainer()->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            $output->writeln('Solr service is not available. Please start it.', true);
            return false;
        }

        $offset   = $input->getOption('offset');
        $keywords = $input->getOption('keyword');

        if ($keywords) {
            $keywords = explode(',', $keywords);
            $keywords = array_map('trim', $keywords);
        } else {
            $keywords = null;
        }

        $searchParam = array();
        if ($keywords) {
            $searchParam['search_keyword'] = array('keyword' => $keywords);
        }

        if (isset($offset)) {
            $this->findKeywordsWithOffset($searchParam, $input, $output);
        } else {
            $this->findKeywords($searchParam, $input, $output);
        }
    }

    /**
     * Process search keywords.
     *
     * @param object $searchKeyword Search Keyword instance.
     * @param object $output        Output object.
     */
    protected function processSearchKeyword($searchKeyword, $output)
    {
        $keyword = $searchKeyword->getKeyword();

        // if do not overwrite category is set then only update search counter instead of insert new category entries.
        if ($searchKeyword->getDoNotOverwriteCategory()) {
            $updateQuery = $this->em->getRepository('FaAdBundle:SearchKeywordCategory')->createQueryBuilder(SearchKeywordCategoryRepository::ALIAS)
            ->update()
            ->set(SearchKeywordCategoryRepository::ALIAS.'.search_count', $searchKeyword->getSearchCount())
            ->andWhere(SearchKeywordCategoryRepository::ALIAS.'.search_keyword_id = :search_keyword_id')
            ->setParameter('search_keyword_id', $searchKeyword->getId());

            $updateQuery->getQuery()->execute();
        } else {
            // initialize solr search manager service and fetch data based of above prepared search options
            $data = array();
            $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
            $data['facet_fields'] = array(
                                        AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID => array('min_count' => 1),
                                        AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID => array('min_count' => 1)
                                    );

            $this->getContainer()->get('fa.solrsearch.manager')->init('ad', $keyword, $data, 1, 1, 0, true);

            $facetResult = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseFacetFields();
            $facetResult = get_object_vars($facetResult);

            // find third level category as bottom level category
            $data['facet_fields'] = array(AdSolrFieldMapping::CATEGORY_ID => array('min_count' => 1));
            $data['query_filters']['item']['category_level'] = 3;

            $this->getContainer()->get('fa.solrsearch.manager')->init('ad', $keyword, $data, 1, 1, 0, true);
            $thirdLevelFacetResult = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseFacetFields();
            $thirdLevelFacetResult = get_object_vars($thirdLevelFacetResult);

            // find second level category as bottom level category
            $data['facet_fields'] = array(AdSolrFieldMapping::CATEGORY_ID => array('min_count' => 1));
            $data['query_filters']['item']['category_level'] = 2;

            $this->getContainer()->get('fa.solrsearch.manager')->init('ad', $keyword, $data, 1, 1, 0, true);
            $secondLevelFacetResult = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseFacetFields();
            $secondLevelFacetResult = get_object_vars($secondLevelFacetResult);

            $thirdLevelCategories  = array();
            $secondLevelCategories = array();
            if (isset($facetResult[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID])) {
                $thirdLevelCategories = get_object_vars($facetResult[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID]);
            }

            if (isset($thirdLevelFacetResult[AdSolrFieldMapping::CATEGORY_ID])) {
                $thirdLevelCategories = $thirdLevelCategories + get_object_vars($thirdLevelFacetResult[AdSolrFieldMapping::CATEGORY_ID]);
            }

            if (isset($facetResult[AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID])) {
                $secondLevelCategories = get_object_vars($facetResult[AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID]);
            }

            if (isset($secondLevelFacetResult[AdSolrFieldMapping::CATEGORY_ID])) {
                $secondLevelCategories = $secondLevelCategories + get_object_vars($secondLevelFacetResult[AdSolrFieldMapping::CATEGORY_ID]);
            }

            $processedKeyword = array();
            $processedKeyword[0]['search_keyword_id'] = $searchKeyword->getId();
            $processedKeyword[0]['category_id']       = null;
            $processedKeyword[0]['keyword']           = $searchKeyword->getKeyword();
            $processedKeyword[0]['search_count']      = $searchKeyword->getSearchCount();

            if (count($thirdLevelCategories)) {
                arsort($thirdLevelCategories);
                $categories = array_keys($thirdLevelCategories);
                if (isset($categories[0]) && $categories[0]) {
                    $processedKeyword[1]['search_keyword_id'] = $searchKeyword->getId();
                    $processedKeyword[1]['category_id']       = $categories[0];
                    $processedKeyword[1]['keyword']           = $searchKeyword->getKeyword().' in <span>'.$this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categories[0]).'</span>';
                    $processedKeyword[1]['search_count']      = $searchKeyword->getSearchCount();
                }
            }

            if (count($secondLevelCategories)) {
                arsort($secondLevelCategories);
                $categories = array_keys($secondLevelCategories);
                if (isset($categories[0]) && $categories[0]) {
                    $processedKeyword[2]['search_keyword_id'] = $searchKeyword->getId();
                    $processedKeyword[2]['category_id']       = $categories[0];
                    $processedKeyword[2]['keyword']           = $searchKeyword->getKeyword().' in <span>'.$this->getContainer()->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $categories[0]).'</span>';
                    $processedKeyword[2]['search_count']      = $searchKeyword->getSearchCount();
                }
            }

            // Remove old entries before new process for keyword.
            $this->em->getRepository('FaAdBundle:SearchKeywordCategory')->removeByKeywordId($searchKeyword->getId());

            // Insert new entries.
            foreach ($processedKeyword as $processedKeywordInfo) {
                $searchKeywordCategory = new SearchKeywordCategory();
                $searchKeywordCategory->setSearchKeywordId($processedKeywordInfo['search_keyword_id']);
                $searchKeywordCategory->setCategoryId($processedKeywordInfo['category_id']);
                $searchKeywordCategory->setKeyword($processedKeywordInfo['keyword']);
                $searchKeywordCategory->setSearchCount($processedKeywordInfo['search_count']);

                $this->em->persist($searchKeywordCategory);
                $this->em->flush($searchKeywordCategory);
            }
        }

        $output->writeln('Keyword is processed : '.$keyword, true);
    }

    /**
     * Process search keywords with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function findKeywordsWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getKeywordQueryBuilder($searchParam);
        $step        = 100;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $searchKeywords = $qb->getQuery()->getResult();

        foreach ($searchKeywords as $searchKeyword) {
            $this->processSearchKeyword($searchKeyword, $output);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Process search keywords.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function findKeywords($searchParam, $input, $output)
    {
        $count     = $this->getKeywordCount($searchParam);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total keywords to process: '.$count, true);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:process:search-keywords '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        // move file to import directory
        if (file_exists($this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/processing/search_keywords.csv')) {
            rename($this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/processing/search_keywords.csv', $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/keyword/processed/search_keywords.csv');
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getKeywordQueryBuilder($searchParam)
    {
        $searchKeywordRepository = $this->em->getRepository('FaAdBundle:SearchKeyword');

        $data                  = array();
        $data['query_filters'] = $searchParam;

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($searchKeywordRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keywords.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getKeywordCount($searchParam)
    {
        $qb = $this->getKeywordQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');
        return $qb->getQuery()->getSingleScalarResult();
    }
}
