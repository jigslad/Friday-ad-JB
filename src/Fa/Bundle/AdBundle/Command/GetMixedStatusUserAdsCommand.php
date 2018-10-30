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
use Fa\Bundle\AdBundle\Entity\MixedStatusUserSolrAds;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * Main purpose of this cron is to generate reports where adverts solr status and DB advert status are different. Advert status like 25,27,28.
 *
 * @author Vijay <vijay.namburi@fridaymediafroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class GetMixedStatusUserAdsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:getmixedstatus-user-ads')
        ->setDescription("Get Mixed status user ads.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'User id', 0)
        ->addOption('start_date', null, InputOption::VALUE_OPTIONAL, 'startDate in unix timestamp', 0)
        ->addOption('end_date', null, InputOption::VALUE_OPTIONAL, 'endDate in unix timestamp', 0)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Get reports where adverts solr status and DB advert status are different. Advert status like 25,27,28.

Command:
 - php app/console fa:update:getmixedstatus-user-ads --user_id="xxxx"
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
        //Create SolrQuery instance
        $solrQuery = new \SolrQuery();
        
        //get options passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->mixedAdStatusWithOffset($input, $output, $solrClient, $solrQuery);
        } else {
            $this->mixedAdStatus($input, $output);
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function mixedAdStatusWithOffset($input, $output, $solrClient, $solrQuery)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getAdQueryBuilder(false, $input);
        $step        = 100;
        $offset      = $input->getOption('offset');
        $em          = $this->getContainer()->get('doctrine')->getManager();

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $objAds = $qb->getQuery()->execute();
        
        foreach ($objAds as $objAd) {
            $dataFlag = false;
            $advertId = $objAd['adId'];
            $advertStatus = intval($objAd['adStatus']);
            
            $solrQuery->setQuery("id:$advertId");
            $solrQuery->addField('id')->addField('a_status_id_i');
            $result = $solrClient->connect()->query($solrQuery);
            $resObj = $result->getResponse();
            
            if (isset($resObj['response']['docs'])) {
                if (!empty($resObj['response']['docs'])) {
                    if ($resObj['response']['docs'][0]['a_status_id_i'] != $advertStatus) {
                        $dataFlag = true;
                    }
                }
                
                if ($dataFlag) {
                    $mixedAdUserObj = $this->em->getRepository('FaAdBundle:MixedStatusUserSolrAds')->findOneBy(array('adId' => $advertId, 'status'=> MixedStatusUserSolrAds::ACTION_NOT_TAKEN));
                    if (empty($mixedAdUserObj)) {
                        $mixedAdUserObj = new MixedStatusUserSolrAds();
                        $mixedAdUserObj->setAdId($objAd['adId']);
                        $mixedAdUserObj->setUserId($objAd['userId']);
                        $mixedAdUserObj->setUserStatus($objAd['userStatus']);
                        $mixedAdUserObj->setAdStatus($objAd['adStatus']);
                        $mixedAdUserObj->setStatus(MixedStatusUserSolrAds::ACTION_NOT_TAKEN);
                        $this->em->persist($mixedAdUserObj);
                        $this->em->flush();
                    }
                }
            } else {
                $output->writeln('Got Error for AdvertId : '.$advertId, true);
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
    protected function mixedAdStatus($input, $output)
    {
        $qb        = $this->getAdQueryBuilder(true, $input);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:getmixedstatus-user-ads '.$commandOptions;
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
    protected function getAdQueryBuilder($onlyCount = false, $input)
    {
        $userId = intval($input->getOption('user_id'));
        $startDate = intval($input->getOption('start_date'));
        $endDate = intval($input->getOption('end_date'));
        
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $qb = $adRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            $qb->select('COUNT('.AdRepository::ALIAS.'.id)');
        } else {
            $qb->select(AdRepository::ALIAS.'.id AS adId', UserRepository::ALIAS.'.id AS userId', 'IDENTITY('.UserRepository::ALIAS.'.status)'.' AS userStatus', 'IDENTITY('.AdRepository::ALIAS.'.status)'.'  AS adStatus');
        }
        $qb->innerJoin(AdRepository::ALIAS.'.user', UserRepository::ALIAS, 'WITH', AdRepository::ALIAS.'.user = '.UserRepository::ALIAS.'.id');
        
        $qb->where(UserRepository::ALIAS.'.status = :userStatus')
            ->andWhere(AdRepository::ALIAS.'.status IN ('.EntityRepository::AD_STATUS_LIVE_ID.','.EntityRepository::AD_STATUS_EXPIRED_ID.','.EntityRepository::AD_STATUS_SOLD_ID.')')
            ->setParameter('userStatus', EntityRepository::USER_STATUS_ACTIVE_ID);
        
        if (!empty($userId)) {
            $qb->andWhere(UserRepository::ALIAS.'.id = :userId')
                ->setParameter('userId', $userId);
        }
        
        if (!empty($startDate) && !empty($endDate)) {
            $qb->andWhere(AdRepository::ALIAS.'.updated_at >= :startDate')
                ->andWhere(AdRepository::ALIAS.'.updated_at <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }
        
        $qb->orderBy(UserRepository::ALIAS.'.id');

        return $qb;
    }
}
