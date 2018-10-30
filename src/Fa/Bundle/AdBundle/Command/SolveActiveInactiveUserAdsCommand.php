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
use Fa\Bundle\AdBundle\Repository\InActiveUserSolrAdsRepository;
use Fa\Bundle\AdBundle\Entity\InActiveUserSolrAds;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

// use Symfony\Component\Validator\Constraints\False;
/**
 * Main purpose of this cron is to solve solr active and inactive user ads which are in solr and which are not solr.
 * This command is used to add active adverts yo solr which are not in solr
 * This command is used to remove inactive user adverts from solr which are in solr.
 *
 * @author Vijay <vijay.namburi@fridaymediafroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SolveActiveInactiveUserAdsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:solveactiveinactive-user-ads')
        ->setDescription("Solve active and inactive user ads.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('user_status', null, InputOption::VALUE_OPTIONAL, 'User account status like 52,53 and 54', 0)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Solve active and inactive user ads.

Command:
 - php app/console fa:update:solveactiveinactive-user-ads --user_status="xxxx"
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
            $this->syncSolrStatusWithOffset($input, $output, $solrClient, $solrQuery);
        } else {
            $this->syncSolrStatus($input, $output);
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function syncSolrStatusWithOffset($input, $output, $solrClient, $solrQuery)
    {
        $userStatus = intval($input->getOption('user_status'));
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getAdQueryBuilder(false, $input);
        $step        = 100;
        $offset      = $input->getOption('offset');
        $em          = $this->getContainer()->get('doctrine')->getManager();
        
        $memoryLimit = ' -d memory_limit=100M';
        if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
            $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $objAds = $qb->getQuery()->execute();
        
        foreach ($objAds as $objAd) {
            $dataFlag = true;
            $userId = $objAd['userId'];
            if ($userStatus == EntityRepository::USER_STATUS_ACTIVE_ID) {
                $advertId = $objAd['adId'];
                $solrQuery->setQuery("id:$advertId");
                $solrQuery->addField('id')->addField('a_user_id_i');
                $result = $solrClient->connect()->query($solrQuery);
                $resObj = $result->getResponse();
                
                if (isset($resObj['response']['docs'])) {
                    if (empty($resObj['response']['docs'])) {
                        $addUpdate = 'add';
                    } else {
                        $addUpdate = 'update';
                    }
                    
                    $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-solr-index --id="'.$advertId.'" --status="A,S,E" '.$addUpdate;
                    $output->writeln($command, true);
                    passthru($command, $returnVar);
                    
                    if ($returnVar !== 0) {
                        $output->writeln('Error occurred during subtask', true);
                        $dataFlag = false;
                    }
                    
                    if ($dataFlag) {
                        $solrQuery->setQuery("id:$advertId");
                        $solrQuery->addField('id')->addField('a_user_id_i');
                        $result1 = $solrClient->connect()->query($solrQuery);
                        $resObj1 = $result1->getResponse();
                
                        if (isset($resObj1['response']['docs'])) {
                            if (!empty($resObj1['response']['docs'])) {
                                $inactiveAdUserObj = $this->em->getRepository('FaAdBundle:InActiveUserSolrAds')->findOneBy(array('adId' => $advertId, 'userId' => $userId, 'status'=> InActiveUserSolrAds::ACTION_NOT_TAKEN));
                                if (!empty($inactiveAdUserObj)) {
                                    $inactiveAdUserObj->setStatus(InActiveUserSolrAds::ACTION_TAKEN);
                                    $this->em->persist($inactiveAdUserObj);
                                    $this->em->flush();
                                }
                                $output->writeln("Advert Id: $advertId add to solr", true);
                            }
                        }
                    }
                } else {
                    $output->writeln('Got Error for AdvertId : '.$advertId, true);
                }
            } else {
                $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:inactive-user-ads --user_id="'.$userId.'"';
                $output->writeln($command, true);
                passthru($command, $returnVar);
                
                if ($returnVar !== 0) {
                    $output->writeln('Error occurred during subtask', true);
                    $dataFlag = false;
                }
                
                if ($dataFlag) {
                    $inactiveAdUserResults = $this->em->getRepository('FaAdBundle:InActiveUserSolrAds')->findBy(array('userId' => $userId, 'status'=> InActiveUserSolrAds::ACTION_NOT_TAKEN));
                    foreach ($inactiveAdUserResults as $inactiveAdObj) {
                        $inactiveAdObj->setStatus(InActiveUserSolrAds::ACTION_TAKEN);
                        $this->em->persist($inactiveAdObj);
                        $this->em->flush();
                    }
                    $output->writeln("User Id: $userId related adverts removed from solr", true);
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
    protected function syncSolrStatus($input, $output)
    {
        $qb        = $this->getAdQueryBuilder(true, $input);
        $count     = $qb->getQuery()->getSingleScalarResult();
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total Records : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:solveactiveinactive-user-ads '.$commandOptions;
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
        $userStatus = intval($input->getOption('user_status'));
        
        $adRepository  = $this->em->getRepository('FaAdBundle:InActiveUserSolrAds');
        $qb = $adRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            if ($userStatus == EntityRepository::USER_STATUS_ACTIVE_ID) {
                $qb->select('COUNT('.InActiveUserSolrAdsRepository::ALIAS.'.id)');
            } else {
                $qb->select('COUNT(DISTINCT '.InActiveUserSolrAdsRepository::ALIAS.'.userId)');
            }
        } else {
            if ($userStatus == EntityRepository::USER_STATUS_ACTIVE_ID) {
                $qb->select(InActiveUserSolrAdsRepository::ALIAS.'.adId AS adId', InActiveUserSolrAdsRepository::ALIAS.'.userId AS userId');
            } else {
                $qb->select('DISTINCT '.InActiveUserSolrAdsRepository::ALIAS.'.userId AS userId');
            }
        }
        
        $qb->where(InActiveUserSolrAdsRepository::ALIAS.'.status = :status')
            ->setParameter('status', InActiveUserSolrAds::ACTION_NOT_TAKEN);
        
        if ($userStatus == EntityRepository::USER_STATUS_ACTIVE_ID) {
            $qb->andWhere(InActiveUserSolrAdsRepository::ALIAS.'.userStatus = :userStatus')
                ->setParameter('userStatus', EntityRepository::USER_STATUS_ACTIVE_ID);
        } else {
            $qb->andWhere(InActiveUserSolrAdsRepository::ALIAS.'.userStatus <> :userStatus')
                ->setParameter('userStatus', EntityRepository::USER_STATUS_ACTIVE_ID);
        }
        if (!$onlyCount) {
            $qb->orderBy(InActiveUserSolrAdsRepository::ALIAS.'.userId');
        }

        return $qb;
    }
}
