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
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Entity\Ad;

/**
 * Command is used to remove Trade-IT Ads
 *
 * @author Manuel Gripentrog <manuel.gripentrog@fiare.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class RemoveTradeitAdsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
            ->setName('fa:remove:tradeit-ads')
            ->setDescription("Remove trade-it ads")
            ->addArgument('action', InputArgument::REQUIRED, 'delete')
            ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
            ->setHelp(
                <<<EOF
    Actions:
Command:
   php app/console fa:remove:tradeit-ads delete
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

        //get arguments passed in command
        $action = $input->getArgument('action');

        //get options passed in command
        $offset   = $input->getOption('offset');
        $searchParam = array();

        if ($action == 'delete') {
            if (isset($offset)) {
                $this->updateDimensionWithOffset($searchParam, $input, $output);
            } else {
                $this->updateDimension($searchParam, $input, $output);
            }
        }
    }

    /**
     * Update dimension with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimensionWithOffset($searchParam, $input, $output)
    {
        $step        = 20;
        $offset      = 0;//$input->getOption('offset');
        $adFeeds     = $this->getFeedAdQueryBuilder($searchParam, $offset, $step);
        $em          = $this->getContainer()->get('doctrine')->getManager();

        foreach ($adFeeds as $adFeed) {
            $adFeed = $this->em->getRepository('FaAdFeedBundle:AdFeed')->find($adFeed['id']);
            $ad = $adFeed->getAd();

            if ($ad) {
                $this->removeAdFromSolr($ad);
                $this->em->remove($ad);
                echo 'Ad removed with id -> '.$ad->getId()."\n";
            }

            if ($adFeed) {
                $this->em->remove($adFeed);
                echo 'AdFeed removed with id -> '.$adFeed->getId()."\n";
            }
        }

        $em->flush();
        $this->em->clear();
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
        $count     = $this->getAdFeedCount();
        $step      = 20;
        $stat_time = time();
        $returnVar = null;

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i = ($i + $step);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:remove:tradeit-ads '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    protected function getFeedAdQueryBuilder($searchParam, $offset, $step)
    {
        $query = 'select a.id from ad_feed a where a.ref_site_id = 10 ORDER BY a.id ASC LIMIT '.$offset.', '.$step;
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $ads = $stmt->fetchAll();
        return $ads;
    }

    protected function getAdFeedCount()
    {
        $query = 'select count(a.id) as count from ad_feed a where a.ref_site_id = 10';
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchAll();
        return $count[0]['count'];
    }

    /**
     * Remove ad from solr.
     *
     * @param Ad $ad
     *
     * return boolean
     */
    private function removeAdFromSolr(Ad $ad)
    {
        $solrClient = $this->getContainer()->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            return false;
        }

        $solr = $solrClient->connect();
        $solr->deleteById($ad->getId());
        $solr->commit(true);
        return true;
    }
}
