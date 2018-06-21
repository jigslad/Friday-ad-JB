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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to Remove old feed adverts from solr and changed status to expire.
 * While updating feed advert status if there is any error or change in format of feed advert, at this time we are not updating advert status
 * and we are not updateing expire date of feed advert because of this issue expire adverts are still in solr.
 *
 *
 * @author vijay <vijay.namburi@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class RemoveOldFeedAdFromSolrCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:feed:remove-old-feed-ad-from-solr')
        ->setDescription("Update feed ads status and remove from solr")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('trans_id', null, InputOption::VALUE_REQUIRED, 'feed advert unique Id', null)
        ->addOption('start_date', null, InputOption::VALUE_OPTIONAL, 'startDate in unix timestamp', 0)
        ->addOption('end_date', null, InputOption::VALUE_OPTIONAL, 'endDate in unix timestamp', 0)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:feed:remove-old-feed-ad-from-solr --trans_id=12345
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
        
        /* $transId = trim($input->getOption('trans_id'));
        $ll = $this->feedAdvertRequest($transId);
        var_dump($ll);
        die('here I am'); */
        
        //get options passed in command
        $offset = $input->getOption('offset');
        
        if (isset($offset)) {
            $this->updateAdStatusWithOffset($input, $output);
        } else {
            $this->updateAdStatus($input, $output);
        }
    }
    
    /**
     * Update solr index with given offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdStatusWithOffset($input, $output)
    {
        $qb          = $this->getAdQueryBuilder(FALSE, $input);
        $step        = 100;
        $offset      = $input->getOption('offset');
        $em          = $this->getContainer()->get('doctrine')->getManager();
        
        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $objAds = $qb->getQuery()->execute();
        
        foreach ($objAds as $objAd) {
            $transId = $objAd->getTransId();
            $feedAd = $this->feedAdvertRequest($transId);
            if (empty($feedAd)) {
                $objAd->setStatus($this->em->getReference('FaEntityBundle:Entity', \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_EXPIRED_ID));
                $this->em->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByAdId($objAd->getId(), $this->getContainer());
                $this->em->persist($objAd);
                $this->em->flush();
                $output->writeln('Removed feed advert Id: '.$objAd->getId(), true);
            }else {
                if (isset($feedAd['Message'])) {
                    $output->writeln('Some thing went wrong for trans Id: '.$transId, true);
                }else {
                    if(isset($feedAd['EndDate']) && ($feedAd['EndDate'] != '0001-01-01T00:00:00Z')) {
                        $objAd->setStatus($this->em->getReference('FaEntityBundle:Entity', \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_EXPIRED_ID));
                        $objAd->setExpiresAt(strtotime($feedAd['EndDate']));
                        $this->em->persist($objAd);
                        $this->em->flush();
                        $this->em->getRepository('FaAdBundle:Ad')->updateAdStatusInSolrByAd($objAd, $this->getContainer());
                        $output->writeln('Updated feed advert Id: '.$objAd->getId(), true);  
                    }
                    // Feed Advert found. TODO Update expire date and status.
                }
            }
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }
    
    /**
     * Update solr index.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateAdStatus($input, $output)
    {
        $qb        = $this->getAdQueryBuilder(TRUE, $input);
        $count     = $qb->getQuery()->getSingleScalarResult();
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
            
            $memoryLimit = ' -d memory_limit=100M';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:feed:remove-old-feed-ad-from-solr '.$commandOptions;
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
    protected function getAdQueryBuilder($onlyCount = FALSE, $input)
    {
        $transId = trim($input->getOption('trans_id'));
        $startDate = intval($input->getOption('start_date'));
        $endDate = intval($input->getOption('end_date'));
        
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $qb = $adRepository->getBaseQueryBuilder();
        
        if ($onlyCount) {
            $qb->select('COUNT('.AdRepository::ALIAS.'.id)');
        }else {
            $qb->select(AdRepository::ALIAS);
        }
        
        $qb->where(AdRepository::ALIAS.'.is_feed_ad = :is_feed_ad')
            ->andWhere(AdRepository::ALIAS.'.expires_at IS NULL')
            ->setParameter('is_feed_ad', 1);
        
        if (!empty($transId)) {
            $qb->andWhere(AdRepository::ALIAS.'.trans_id = :trans_id')
                ->setParameter('trans_id', $transId);
        }
        
        if (!empty($startDate) && !empty($endDate)) {
            $qb->andWhere(AdRepository::ALIAS.'.created_at >= :startDate')
                ->andWhere(AdRepository::ALIAS.'.created_at <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }
        if (!$onlyCount) {
            $qb->orderBy(AdRepository::ALIAS.'.id');
        }
        
        return $qb;
    }
    
    /**
     * Handle fmgfeedaggregation request.
     *
     * @param string $transId fmgfeedaggregation request.
     *
     * @return string
     */
    protected function feedAdvertRequest($transId)
    {
        $mode = $this->getContainer()->getParameter('fa.feed.mode');
        $mainUrl = $this->getContainer()->getParameter('fa.feed.'.$mode.'.url');
        $url = $mainUrl.'/adverts/GetAdvertById?appkey='.$this->getContainer()->getParameter('fa.feed.api.id').'&advertId='.$transId;
        
        // Build the HTTP Request Headers
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        
        $response = json_decode(curl_exec($ch), true);
        
        return $response;
    }
}
