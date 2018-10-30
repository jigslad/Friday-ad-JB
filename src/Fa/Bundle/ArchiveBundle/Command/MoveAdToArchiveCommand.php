<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ArchiveBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to move sold/expired ads to archive table.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class MoveAdToArchiveCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:move:ad-to-archive')
        ->setDescription("Move sold/expired ads to archive table.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Ad ids', null)
        ->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Count to compare', 1)
        ->addOption('move_before', null, InputOption::VALUE_OPTIONAL, 'Move all the ads older than 90 days', 0)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Move sold/expired ads to archive table.

Command:
 - php app/console fa:move:ad-to-archive --id="xxxx"
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
        $entityManager         = $this->getContainer()->get('doctrine')->getManager();
        $lastDaysForExpriedAds = $entityManager->getRepository('FaCoreBundle:Config')->getPeriodBeforeCheckingViewsForMoveExpiredAdsToArvhice();

        //get options passed in command
        $ids    = $input->getOption('id');
        $offset = $input->getOption('offset');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        $searchParam                     = array();
        $searchParam['move_before']      = $input->getOption('move_before');
        $searchParam['entity_ad_status'] = array(
                                                'id' => array(
                                                             \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_EXPIRED_ID,
                                                             \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_SOLD_ID
                                                        )
                                           );

        if ($ids) {
            $searchParam['ad'] = array('id' => $ids);
        }

        if ($lastDaysForExpriedAds) {
            if ($searchParam['move_before']) {
                $searchParam['ad']['sold_at'] =  strtotime('-'.$lastDaysForExpriedAds.' day');
                $searchParam['ad']['expires_at'] =  strtotime('-'.$lastDaysForExpriedAds.' day');
            } else {
                $date = date('d/m/Y', strtotime('-'.$lastDaysForExpriedAds.' day'));
                $from = CommonManager::getTimeStampFromStartDate($date);
                $to   = CommonManager::getTimeStampFromEndDate($date);

                $searchParam['ad']['sold_at_from_to']    =  $from.'|'.$to;
                $searchParam['ad']['expires_at_from_to'] =  $from.'|'.$to;
            }
        }

        if (isset($offset)) {
            $this->moveAdToArchiveWithOffset($searchParam, $input, $output);
        } else {
            $this->moveAdToArchive($searchParam, $input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function moveAdToArchiveWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 100;
        //$offset      = 0;//$input->getOption('offset');
        $originalOffset = $input->getOption('offset');
        $count       = $input->getOption('count');
        $notMoved    = 0;

        if ($input->getOption('offset') == 0) {
            $fp = fopen($this->getContainer()->get('kernel')->getRootDir()."/../data/notMovedToArchive.txt", "w+");
            fwrite($fp, 0);
            fclose($fp);
        }

        // read offset from file
        $fp       = fopen($this->getContainer()->get('kernel')->getRootDir()."/../data/notMovedToArchive.txt", "r");
        $notMovedPrevious = fread($fp, filesize($this->getContainer()->get('kernel')->getRootDir()."/../data/notMovedToArchive.txt"));
        fclose($fp);

        echo "not moved previous: ".$notMovedPrevious."\n";
        echo "offset before sum: ".$input->getOption('offset')."\n";

        //$offset   =  $input->getOption('offset') + $notMovedPrevious;
        $offset = $notMovedPrevious;

        echo "offset: ".$offset."\n";

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $ads = $qb->getQuery()->getResult();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $adIds      = array();
        $movedAdIds = array();
        foreach ($ads as $ad) {
            $adIds[]= $ad->getId();
        }

        if (count($adIds)) {
            // Check if ad view found in last 30 days then no need to move to archive, even ad has been expired before 90 days.
            $precedingDays  = $entityManager->getRepository('FaCoreBundle:Config')->getPrecedingPeriodToCheckViewsForMoveExpiredAdsToArvhice();
            $adViewCounters = $entityManager->getRepository('FaAdBundle:AdViewCounter')->getAdViewCounterByPrecedingDays($precedingDays, $adIds);
            $printAdIds     = $entityManager->getRepository('FaAdBundle:AdPrint')->getAllPrintByGivenTimeForAd(strtotime('-28 days'), $adIds);

            foreach ($ads as $ad) {
                $adId = $ad->getId();
                if (isset($adViewCounters[$adId])) {
                    echo "adViewCounters[adId]: ".$adViewCounters[$adId]."\n";
                }
                if (!isset($printAdIds[$adId]) && (!isset($adViewCounters[$adId]) || !$adViewCounters[$adId] || (isset($adViewCounters[$adId]) && $adViewCounters[$adId] < $count))) {
                    try {
                        $entityManager->getRepository('FaArchiveBundle:ArchiveAd')->moveAdtoArchive($ad, $this->getContainer());
                        $movedAdIds[] = $adId;
                        $output->writeln('Ad has been moved to archive, ad id: '.$adId, true);
                    } catch (\Exception $e) {
                        $output->writeln('Exception for, ad id: '.$adId."== ".$e->getMessage(), true);
                    }
                } else {
                    $output->writeln('Ad has been NOT moved for ad id: '.$adId, true);
                    $notMoved++;
                }

                if (isset($printAdIds[$adId])) {
                    $output->writeln('Ad has been NOT moved to archive because ad has future insert date for ad id: '.$adId, true);
                }
            }

            try {
                $movedAdIds = array_map('trim', $movedAdIds);
                $solrClient = $this->getContainer()->get('fa.solr.client.ad');
                if ($solrClient->ping()) {
                    $solr = $solrClient->connect();
                    $solr->deleteByIds($movedAdIds);
                    $solr->commit();
                }
            } catch (\Exception $e) {
                $output->writeln('Exception for removing ads from solr for offset: '.$originalOffset, true);
            }

            $fp = fopen($this->getContainer()->get('kernel')->getRootDir()."/../data/notMovedToArchive.txt", "w+");
            fwrite($fp, ($notMovedPrevious + $notMoved));
            fclose($fp);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function moveAdToArchive($searchParam, $input, $output)
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:move:ad-to-archive '.$commandOptions;
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

        if ($searchParam['move_before']) {
            $data                   = array();
            $data['static_filters'] = 'a.sold_at <= '.$searchParam['ad']['sold_at'].' OR a.expires_at <= '.$searchParam['ad']['expires_at'];
            unset($searchParam['ad']['sold_at'], $searchParam['ad']['expires_at']);
        } else {
            list($soldAtFrom, $soldAtTo)       = explode('|', $searchParam['ad']['sold_at_from_to']);
            list($expiresAtFrom, $expiresAtTo) = explode('|', $searchParam['ad']['expires_at_from_to']);

            $data                   = array();
            $data['static_filters'] = '(a.sold_at >= '.$soldAtFrom.' AND a.sold_at <= '.$soldAtTo.') OR (a.expires_at >= '.$expiresAtFrom.' AND a.expires_at <= '.$expiresAtTo.')';
            unset($searchParam['ad']['sold_at_from_to'], $searchParam['ad']['expires_at_from_to']);
        }

        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad' => array('id' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        if (isset($data['query_filters']) && isset($data['query_filters']['move_before'])) {
            unset($data['query_filters']['move_before']);
        }
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
