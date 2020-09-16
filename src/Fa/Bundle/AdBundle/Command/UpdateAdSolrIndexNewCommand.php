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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\AdBundle\Repository\AdLocationRepository;

/**
 * This command is used to add/update/delete solr index for ads.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateAdSolrIndexNewCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-solr-index-new')
        ->setDescription("Update solr index for ads.")
        ->addArgument('action', InputArgument::REQUIRED, 'add or update or delete')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Ad status', 'A')
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Ad ids', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('category', null, InputOption::VALUE_OPTIONAL, 'Category name', null)
        ->addOption('update_type', null, InputOption::VALUE_OPTIONAL, 'Update type', null)
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'User id', null)
        ->addOption('town_id', null, InputOption::VALUE_OPTIONAL, 'Town id', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'add or update for last few days only', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Add/Update/Delete solr index with ad information
- Can be run to add/update/delete specific ad information to solr index

Command:
 - php app/console fa:update:ad-solr-index-new --status="A" add
 - php app/console fa:update:ad-solr-index-new --status="A" --id="xxxx" update
 - php app/console fa:update:ad-solr-index-new --status="A" --id="xxxx" add
 - php app/console fa:update:ad-solr-index-new --id="xxxx" delete
   php app/console fa:update:ad-solr-index-new --town_id="xxxx" update
   php app/console fa:update:ad-solr-index-new --category="For Sale" --status="A" add
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

        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $solrClient = $this->getContainer()->get('fa.solr.client.ad.new');
        if (!$solrClient->ping()) {
            $output->writeln('Solr service is not available. Please start it.', true);
            return false;
        }

        //get arguments passed in command
        $action = $input->getArgument('action');

        exec('nohup'.' '.$this->getContainer()->getParameter('fa.php.path').' '.$this->getContainer()->getParameter('project_path').'/console fa:cache:entities  >/dev/null &');

        //get options passed in command
        $ids      = $input->getOption('id');
        $status   = $input->getOption('status');
        $offset   = $input->getOption('offset');
        $category = $input->getOption('category');
        $update_type = $input->getOption('update_type');
        $lastDays = $input->getOption('last_days');
        $userId = $input->getOption('user_id');
        $townId = $input->getOption('town_id');

        $categoryIds = array();

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        if ($status) {
            $status = explode(',', $status);
        } else {
            $status = array('A');
        }

        if ($category) {
            $categoryObj = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => $category));
            if ($categoryObj) {
                $children = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getNodesHierarchyQuery($categoryObj)->getArrayResult();

                $categoryIds[] = $categoryObj->getId();
                foreach ($children as $child) {
                    $categoryIds[] = $child['id'];
                }
            } else {
                echo 'Invalid category -- '.$category."\n";
                exit;
            }
        }

        if ($action == 'add' || $action == 'update') {
            $statusId = array();
            foreach ($status as $code) {
                if ($code == 'A') {
                    $statusId[] = \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID;
                }
                if ($code == 'S') {
                    $statusId[] = \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_SOLD_ID;
                }
                if ($code == 'E') {
                    $statusId[] = \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_EXPIRED_ID;
                }
            }

            $searchParam = null;

            if ($action == 'add') {
                $searchParam['ad'] = array('id' => $ids, 'status' => $statusId);
                if (!empty($categoryIds)) {
                    $searchParam['ad']['category'] = $categoryIds;
                }
                if ($lastDays) {
                    $searchParam['ad']['created_at_from_to'] =  strtotime('-'.$lastDays.' day').'|';
                }
            } else {
                $searchParam['ad'] = array('id' => $ids, 'status' => $statusId);
                if (!empty($categoryIds)) {
                    $searchParam['ad']['category'] = $categoryIds;
                }
                if ($lastDays) {
                    $searchParam['ad']['updated_at_from_to'] =  strtotime('-'.$lastDays.' day').'|';
                }
            }

            if ($update_type != '') {
                $searchParam['ad']['update_type'] = $update_type;
            }

            if ($userId != '') {
                $searchParam['user']['id'] = $userId;
            }

            $searchParam['ad']['town_id'] = '';
            if ($townId!='') {
                $searchParam['ad']['town_id'] = $townId;
            }


            //$searchParam['ad']['is_blocked_ad'] = 0;

            if (isset($offset)) {
                $this->updateSolrIndexWithOffset($solrClient, $searchParam, $input, $output);
            } else {
                $this->updateSolrIndex($solrClient, $searchParam, $input, $output);
            }
        } elseif ($action == 'delete') {
            $solr = $solrClient->connect();

            if ($ids && is_array($ids)) {
                $solr->deleteByIds($ids);
            } else {
                //$solr->deleteByQuery('*');
            }

            $solr->commit(true);
            //$solr->optimize();

            if ($ids && is_array($ids)) {
                $output->writeln('Solr index removed for ad id: '.join(',', $ids), true);
            } else {
                $output->writeln('Solr index removed for all ads.', true);
            }
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param object $solrClient  Solr service object.
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateSolrIndexWithOffset($solrClient, $searchParam, $input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();

        $adSolrIndex = $this->getContainer()->get('fa.ad.solrindex');
        foreach ($ads as $ad) {
            $idsFound[] = $ad->getId();
            if ($adSolrIndex->updateNew($solrClient, $ad, $this->getContainer(), true)) {
                $output->writeln('Solr index updated for ad id: '.$ad->getId(), true);
            } else {
                $output->writeln('Solr index not updated for ad id: '.$ad->getId(), true);
            }
        }

        if (isset($searchParam['ad']['id'])) {
            $idsNotFound = array_diff($searchParam['ad']['id'], $idsFound);
        }

        $solr = $solrClient->connect();
        if (count($idsNotFound) > 0) {
            $solr->deleteByIds($idsNotFound);
        }
        $solr->commit(true);
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update solr index.
     *
     * @param object $solrClient  Solr service object.
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateSolrIndex($solrClient, $searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 1000;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:ad-solr-index-new '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
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
    protected function getAdQueryBuilder($searchParam)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adRepository  = $entityManager->getRepository('FaAdBundle:Ad');
        $townIds = ($searchParam['ad']['town_id'])?$searchParam['ad']['town_id']:'';

        $data                 = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter'] = array('ad' => array('id' => 'asc'));


        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adRepository, $data);

        $queryBuilder = $searchManager->getQueryBuilder();

        if ($townIds!='') {
            $townId = explode(',', $townIds);
            $queryBuilder->leftJoin('FaAdBundle:AdLocation', AdLocationRepository::ALIAS, 'WITH', AdLocationRepository::ALIAS.'.ad = '.AdRepository::ALIAS);
            $queryBuilder->andWhere(AdLocationRepository::ALIAS.'.location_town IN (:ad_location_town_id)');
            $queryBuilder->setParameter('ad_location_town_id', $townId);
        }



        return $searchManager->getQueryBuilder();
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
        $qb = $this->getAdQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
