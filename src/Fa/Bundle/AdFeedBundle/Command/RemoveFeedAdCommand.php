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

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:download:feed download  --type="gun" --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class RemoveFeedAdCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:feed:remove-ad-only-use-for-cleaning')
        ->setDescription("Update feed ads")
        ->addArgument('action', InputArgument::REQUIRED, 'remove')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Ad type', null)
        ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Ad Status', null)
        ->addOption('ad_ref', null, InputOption::VALUE_REQUIRED, 'Ad ref', null)
        ->addOption('ad_id', null, InputOption::VALUE_REQUIRED, 'Ad ID', null)
        ->addOption('site_id', null, InputOption::VALUE_REQUIRED, 'Referance site id', 10)
        ->addOption('force', null, InputOption::VALUE_REQUIRED, 'Referance site id', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:feed:remove-ad-only-use-for-cleaning
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

        $adImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/image/';

        $QUERY_BATCH_SIZE = 100;
        $done             = false;
        $last_id          = 0;

        $searchParam = array();

        if ($input->getOption('ad_ref')) {
            $searchParam['ad_ref'] = explode(',', $input->getOption('ad_ref'));
        }

        if ($input->getOption('ad_id')) {
            $searchParam['ad_id'] = explode(',', $input->getOption('ad_id'));
        }

        if ($input->getOption('status')) {
            $searchParam['status'] = explode(',', $input->getOption('status'));
        }

        if ($input->getOption('type')) {
            $searchParam['ref_site_id'] = $this->getAdFeedSiteIdByType($input->getOption('type'), $input->getOption('site_id'));
        }

        while (!$done) {
            $ids     = array();
            $FeedAds = $this->getFeedAdResult($searchParam, $last_id, $QUERY_BATCH_SIZE);
            if ($FeedAds) {
                foreach ($FeedAds as $FeedAd) {
                    if ($FeedAd->getAd()) {
                        $ad = $FeedAd->getAd();
                        $ids[] = $ad->getId();
                        $adImages  = $this->em->getRepository('FaAdBundle:AdImage')->getAdImages($ad->getId());
                        foreach ($adImages as $image) {
                            $this->em->getRepository('FaAdBundle:AdImage')->removeAdImage($ad->getId(), $image->getId(), $image->getHash(), $this->getContainer());
                        }
                        $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($ad->getId());

                        if ($adMain) {
                            $this->em->remove($adMain);
                        }

                        if ($ad) {
                            $this->em->remove($ad);
                        }
                        echo 'Ad removed with -> '.$ad->getId()."\n";
                    } else {
                        echo 'Feed Ad entry removed -> '.$FeedAd->getId()."\n";
                        $this->em->remove($FeedAd);
                    }
                }
                $last_id = $FeedAd->getId();

                if (count($ids) > 0) {
                    $idString = implode(',', $ids);
                    $memoryLimit = '';
                    if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                        $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
                    }
                    $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:ad-solr-index --id="'.$idString.'" --status="A,E" add';
                    $output->writeln($command, true);
                    passthru($command, $returnVar);
                }
            } else {
                $done = true;
            }
            $this->em->flush();
            $this->em->clear();
        }
    }


    protected function getFeedAdResult($searchParam, $last_id, $QUERY_BATCH_SIZE)
    {
        $adFeedRepository  = $this->em->getRepository('FaAdFeedBundle:AdFeed');
        $qb = $adFeedRepository->createQueryBuilder(AdFeedRepository::ALIAS);
        $qb->andWhere(AdFeedRepository::ALIAS.'.id > :id');
        $qb->setParameter('id', $last_id);
        $qb->addOrderBy(AdFeedRepository::ALIAS.'.id');

        if (isset($searchParam['ad_ref'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.trans_id IN (:ref)');
            $qb->setParameter('ref', $searchParam['ad_ref']);
        }

        if (isset($searchParam['ad_id'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.ad IN (:ad_id)');
            $qb->setParameter('ad_id', $searchParam['ad_id']);
        }

        if (isset($searchParam['status'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.status = :status');
            $qb->setParameter('status', $searchParam['status']);
        }

        if (isset($searchParam['ref_site_id'])) {
            $qb->andWhere(AdFeedRepository::ALIAS.'.ref_site_id = :ref_site_id');
            $qb->setParameter('ref_site_id', $searchParam['ref_site_id']);
        }

        $qb->setMaxResults($QUERY_BATCH_SIZE);
        $qb->setFirstResult(0);
        return $qb->getQuery()->getResult();
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
