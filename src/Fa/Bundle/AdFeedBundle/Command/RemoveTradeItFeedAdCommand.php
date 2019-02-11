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
use Fa\Bundle\AdFeedBundle\Repository\AdFeedRepository;
use Fa\Bundle\AdBundle\Entity\Ad;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;

/**
 * This command is used to update feed ads.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class RemoveTradeItFeedAdCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:remove:tradeit-feed-ad')
        ->setDescription("Remove trade-it ads")
        ->addArgument('action', InputArgument::REQUIRED, 'delete')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_id', null, InputOption::VALUE_REQUIRED, 'Ad type', null)
        ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Ad Status', null)
        ->addOption('free_text', null, InputOption::VALUE_REQUIRED, 'Free text format', null)
        ->addOption('ad_ref', null, InputOption::VALUE_REQUIRED, 'Ad ref', null)
        ->addOption('ad_id', null, InputOption::VALUE_REQUIRED, 'Ad ID', null)
        ->addOption('force', null, InputOption::VALUE_REQUIRED, 'Referance site id', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:remove:tradeit-feed-ad
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
        ->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);



        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        //get arguments passed in command
        $action = $input->getArgument('action');

        //get options passed in command
        $offset   = $input->getOption('offset');
        $searchParam = array();

        if ($input->getOption('ad_ref')) {
            $searchParam['ad_ref'] = explode(',', $input->getOption('ad_ref'));
        }

        if ($input->getOption('ad_id')) {
            $searchParam['ad_id'] = explode(',', $input->getOption('ad_id'));
        }

        $searchParam['status'] = array('A');

        if ($input->getOption('last_id')) {
            $searchParam['last_id'] = explode(',', $input->getOption('last_id'));
        }

        $searchParam['ref_site_id'] = 10;

        if ($input->getOption('free_text')) {
            $searchParam['free_text'] = $input->getOption('free_text');
        }


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
        $feedReader  = $this->getContainer()->get('fa_ad.manager.ad_feed_reader');
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getFeedAdQueryBuilder($searchParam);
        $step        = 100;
        $offset      = $input->getOption('offset');
        $force       = $input->getOption('force') != '' ? $input->getOption('force') : null ;

        if ($input->getOption('status')) {
            $qb->setMaxResults($step);
        } else {
            $qb->setFirstResult($offset);
            $qb->setMaxResults($step);
        }

        $FeedAds = $qb->getQuery()->getResult();
        $em  = $this->getContainer()->get('doctrine')->getManager();

        $ids = array();

        foreach ($FeedAds as $FeedAd) {
            $ad = $FeedAd->getAd();

            if ($ad instanceof Ad) {
                if ($this->em->getRepository('FaAdBundle:Ad')->changeAdStatus($ad->getId(), BaseEntityRepository::AD_STATUS_INACTIVE_ID, $this->getContainer(), false)) {
                    echo 'Inactivate ads -> '.$ad->getId()."\n";
                }

                $FeedAd->setStatus('E');
                $this->em->persist($FeedAd);
                $this->em->flush();
            }
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
        $count     = $this->getAdFeedCount($searchParam);

        $step      = 100;
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

            if ($input->getOption('status')) {
                $mqb    = $this->getMaxId($searchParam);

                if (isset($searchParam['last_id']) && $searchParam['last_id'] > 0) {
                    $mqb->setMaxResults($step);
                } else {
                    $mqb->setFirstResult($low);
                    $mqb->setMaxResults($step);
                }

                $mresult = $mqb->getQuery()->getResult();
                $top_id = isset($mresult[0]) ? $mresult[0] : null;

                if (isset($top_id['id'])) {
                    $commandOptions .= ' --last_id='.$top_id['id'];
                    $searchParam['last_id'] =  array_pop($mresult);
                }
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:remove:tradeit-feed-ad -v '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    protected function getFeedAdQueryBuilder($searchParam)
    {
        $adFeedRepository  = $this->em->getRepository('FaAdFeedBundle:AdFeed');
        $qb = $adFeedRepository->createQueryBuilder(AdFeedRepository::ALIAS);
        $qb->orderBy(AdFeedRepository::ALIAS.'.id', 'ASC');
        $qb->andWhere(AdFeedRepository::ALIAS.'.is_updated = :is_updated');
        $qb->setParameter('is_updated', 0);

        if (isset($searchParam['ad_ref'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.trans_id IN (:ref)');
            $qb->setParameter('ref', $searchParam['ad_ref']);
        }

        if (isset($searchParam['free_text'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.ad_text LIKE :ad_text');
            $qb->setParameter('ad_text', '%'.$searchParam['free_text'].'%');
        }

        if (isset($searchParam['ad_id'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.ad IN (:ad_id)');
            $qb->setParameter('ad_id', $searchParam['ad_id']);
        }

        if (isset($searchParam['status'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.status = :status');
            $qb->setParameter('status', $searchParam['status']);

            if (isset($searchParam['last_id'])) {
                $qb->andWhere(AdFeedRepository::ALIAS.'.id >= :last_id');
                $qb->setParameter('last_id', $searchParam['last_id']);
            }
        }

        if (isset($searchParam['ref_site_id'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.ref_site_id = :ref_site_id');
            $qb->setParameter('ref_site_id', $searchParam['ref_site_id']);
        }


        return $qb;
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdFeedCount($searchParam)
    {
        $qb = $this->getFeedAdQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getMaxId($searchParam)
    {
        $qb = $this->getFeedAdQueryBuilder($searchParam);
        return $qb->select($qb->getRootAlias().'.id');
    }

    /**
     * get ad feed site by type
     *
     * @param string $type
     * @param integer $siteID
     *
     * @return integer
     */
    public function getAdFeedSiteIdByType($type, $siteID = 10)
    {
        $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $siteID));

        if ($ad_feed_site) {
            return  $ad_feed_site->getId();
        }
    }
}
