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
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;

/**
 * This command is used to add/update/delete solr index for ads.
 *
 * @author Samir Amrutya <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateAdSolrIndexCustomCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-solr-index-custom')
        ->setDescription("Update solr index for ads.")
        ->addArgument('action', InputArgument::REQUIRED, 'add or update or delete')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'add or update for last few days only', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Add/Update/Delete solr index with ad information
- Can be run to add/update specific ad information to solr index

Command:
 - php app/console fa:update:ad-solr-index-custom add
 - php app/console fa:update:ad-solr-index-custom update
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

        //get arguments passed in command
        $action = $input->getArgument('action');

        //get options passed in command
        $offset   = $input->getOption('offset');

        if ($action == 'add' || $action == 'update') {

            $searchParam = null;

            if (isset($offset)) {
                $this->updateSolrIndexWithOffset($solrClient, $searchParam, $input, $output);
            } else {
                $this->updateSolrIndex($solrClient, $searchParam, $input, $output);
            }
        } else {
            $output->writeln('Invalid action supplied (allowed only add / update).', true);
            return false;
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
        $qb          = $this->getAdQueryBuilder();
        $step        = 1000;
        $offset      = $input->getOption('offset');
        $em          = $this->getContainer()->get('doctrine')->getManager();

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();
        $adSolrIndex = $this->getContainer()->get('fa.ad.solrindex');
        foreach ($ads as $ad) {
            if ($adSolrIndex->update($solrClient, $ad, $this->getContainer(), true)) {
                $output->writeln('Solr index updated for Ad '.$ad->getId(), true);
            } else {
                $output->writeln('Solr index not updated for Ad '.$ad->getId(), true);
            }
        }

        $solr = $solrClient->connect();
        $solr->commit();
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
        $qb        = $this->getAdQueryBuilder(TRUE);
        $count     = $qb->getQuery()->getSingleScalarResult();
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:ad-solr-index-custom '.$commandOptions.' '.$input->getArgument('action');
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
    protected function getAdQueryBuilder($onlyCount = FALSE)
    {
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $qb = $adRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            $qb->select('COUNT('.AdRepository::ALIAS.'.id)');
        } else {
            $qb->select(AdRepository::ALIAS);
        }

        $qb->where(AdRepository::ALIAS.'.ti_ad_id > 0 and '.AdRepository::ALIAS.'.status IN (25,27,28)');

        return $qb;
    }
}
