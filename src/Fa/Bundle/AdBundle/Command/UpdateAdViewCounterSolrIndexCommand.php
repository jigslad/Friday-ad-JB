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
use Symfony\Component\Validator\Constraints\Date;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Entity\AdViewCounter;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This command is used for update counter.
 * php app/console fa:update:ad-view-counter-solr-index
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAdViewCounterSolrIndexCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-view-counter-solr-index')
        ->setDescription("Update ad last 7 days total view counter to solr.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null);
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

        $solrClient = $this->getContainer()->get('fa.solr.client.ad.view.counter');
        if (!$solrClient->ping()) {
            $output->writeln('Solr service is not available. Please start it.', true);
            return false;
        }

        $offset = $input->getOption('offset');
        if (isset($offset)) {
            $this->updateAdViewCounterWithOffset($solrClient, $input, $output);
        } else {
            // Remove all indexes first time
            $this->removeAdViewCounterSolrIndex($solrClient, $input, $output);

            // Add index for last 7 days
            $this->updateAdViewCounter($solrClient, $input, $output);
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param object $solrClient  Solr service object.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdViewCounterWithOffset($solrClient, $input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getAdViewCounterQueryBuilder();
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads                    = $qb->getQuery()->getResult();
        $adViewCounterSolrIndex = $this->getContainer()->get('fa.ad.view.counter.solrindex');
        foreach ($ads as $ad) {
            if ($adViewCounterSolrIndex->update($solrClient, $ad, $this->getContainer(), true)) {
                $output->writeln('Solr ad view counter index updated for ad id: '.$ad['ad_id'], true);
            } else {
                $output->writeln('Solr ad view counter index not updated for ad id: '.$ad['ad_id'], true);
            }
        }

        $solr = $solrClient->connect();
        $solr->commit();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update solr index.
     *
     * @param object $solrClient Solr service object.
     * @param object $input      Input object.
     * @param object $output     Output object.
     */
    protected function updateAdViewCounter($solrClient, $input, $output)
    {
        $count     = $this->getRecordCount();
        $step      = 1000;
        $stat_time = time();

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-view-counter-solr-index '.$commandOptions;
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
     * @return Doctrine_Query Object.
     */
    protected function getAdViewCounterQueryBuilder()
    {
        $entityManager            = $this->getContainer()->get('doctrine')->getManager();
        $adViewCounterRepository  = $entityManager->getRepository('FaAdBundle:AdViewCounter');

        $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime('-8 days')));
        $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime('-1 days')));

        $qb = $adViewCounterRepository->getBaseQueryBuilder();
        $qb->select('SUM('.$qb->getRootAlias().'.hits) as total_hits', AdRepository::ALIAS.'.id as ad_id', CategoryRepository::ALIAS.'.id as category_id')
           ->leftJoin($qb->getRootAlias().'.ad', AdRepository::ALIAS)
           ->leftJoin(AdRepository::ALIAS.'.category', CategoryRepository::ALIAS)
           ->andWhere($qb->getRootAlias().'.created_at >= :created_at_from')->setParameter('created_at_from', $startDate)
           ->andWhere($qb->getRootAlias().'.created_at <= :created_at_to')->setParameter('created_at_to', $endDate)
           ->addGroupBy($qb->getRootAlias().'.ad');

        return $qb;
    }

    /**
     * Get query builder for ads.
     *
     * @return Doctrine_Query Object.
     */
    protected function getRecordCount()
    {
        $entityManager            = $this->getContainer()->get('doctrine')->getManager();
        $adViewCounterRepository  = $entityManager->getRepository('FaAdBundle:AdViewCounter');

        $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime('-8 days')));
        $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime('-1 days')));

        $qb = $adViewCounterRepository->getBaseQueryBuilder();
        $qb->select('COUNT(DISTINCT '.$qb->getRootAlias().'.ad)')
           ->andWhere($qb->getRootAlias().'.created_at >= :created_at_from')->setParameter('created_at_from', $startDate)
           ->andWhere($qb->getRootAlias().'.created_at <= :created_at_to')->setParameter('created_at_to', $endDate);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Update solr index.
     *
     * @param object $solrClient Solr service object.
     * @param object $input      Input object.
     * @param object $output     Output object.
     */
    protected function removeAdViewCounterSolrIndex($solrClient, $input, $output)
    {
        $solr = $solrClient->connect();

        $solr->deleteByQuery('*');
        $solr->commit();
        $solr->optimize();

        $output->writeln('Solr ad view counter index removed for all ads.', true);
    }
}
