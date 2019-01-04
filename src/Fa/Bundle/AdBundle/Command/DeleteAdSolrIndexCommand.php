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
 * This command is used to delete ad solr index for ads with status other than active, expired or sold.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DeleteAdSolrIndexCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:delete:ad-solr-index')
        ->setDescription("Delete solr index for ads.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Ad ids', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'User id', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'delete last few days only', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Delete solr index with ad information

Command:
 - php app/console fa:delete:ad-solr-index --last_days="1"
 - php app/console fa:delete:ad-solr-index --last_days="1" --user_id="XXXX"
 - php app/console fa:delete:ad-solr-index --last_days="1" --id="XXXX"

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

        $solrClient = $this->getContainer()->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            $output->writeln('Solr service is not available. Please start it.', true);
            return false;
        }

        //get options passed in command
        $ids      = $input->getOption('id');
        $offset   = $input->getOption('offset');
        $lastDays = $input->getOption('last_days');
        $userId   = $input->getOption('user_id');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        $statusId = array(
                        \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_MODERATED_ID,
                        \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_REJECTED_ID,
                        \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_REJECTEDWITHREASON_ID,
                        \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_IN_MODERATION_ID,
                        \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_INACTIVE_ID,
                    );


        $searchParam = array();
        $searchParam['ad'] = array(
                                 'id'     => $ids,
                                 'status' => $statusId
                             );

        if ($lastDays) {
            $searchParam['ad']['updated_at_from_to'] =  strtotime('-'.$lastDays.' day').'|';
        }

        if ($userId != '') {
            $searchParam['user']['id'] = $userId;
        }

        if (isset($offset)) {
            $this->updateSolrIndexWithOffset($solrClient, $searchParam, $input, $output);
        } else {
            $this->updateSolrIndex($solrClient, $searchParam, $input, $output);
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
        $idsFound = array();
        $qb       = $this->getAdQueryBuilder($searchParam);
        $step     = 100;
        $offset   = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();
        foreach ($ads as $ad) {
            $idsFound[] = $ad->getId();
        }

        $solr = $solrClient->connect();
        if (count($idsFound) > 0) {
            $idsFound = array_map('trim', $idsFound);
            $solr->deleteByIds($idsFound);
            $solr->commit(true);
            //$solr->optimize();

            $output->writeln('Solr index removed for ad ids: '.join(", ", $idsFound), true);
        }

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
        $step      = 100;
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:delete:ad-solr-index '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $solr = $solrClient->connect();
        //$solr->optimize();

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

        $data                 = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter'] = array('ad' => array('id' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adRepository, $data);

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
