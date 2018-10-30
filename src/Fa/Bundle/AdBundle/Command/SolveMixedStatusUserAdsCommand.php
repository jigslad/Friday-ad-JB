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
use Fa\Bundle\AdBundle\Repository\MixedStatusUserSolrAdsRepository;
use Fa\Bundle\AdBundle\Entity\MixedStatusUserSolrAds;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * Main purpose of this cron is to solve where adverts solr status and DB advert status are different.
 * This cron update solr adverts status with actual database advert status
 *
 * @author Vijay <vijay.namburi@fridaymediafroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SolveMixedStatusUserAdsCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:solvemixedstatus-user-ads')
        ->setDescription("Update solr adverts status with actual database advert status.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Update solr adverts status with actual database advert status.

Command:
 - php app/console fa:update:solvemixedstatus-user-ads --offset="xxxx"
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
            $advertId = $objAd['adId'];
            $advertStatus = intval($objAd['adStatus']);
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
                    $solrQuery->addField('id')->addField('a_status_id_i');
                    $result1 = $solrClient->connect()->query($solrQuery);
                    $resObj1 = $result1->getResponse();
            
                    if (isset($resObj1['response']['docs'])) {
                        if (!empty($resObj1['response']['docs'])) {
                            if ($resObj1['response']['docs'][0]['a_status_id_i'] == $advertStatus) {
                                $inactiveAdUserObj = $this->em->getRepository('FaAdBundle:MixedStatusUserSolrAds')->findOneBy(array('adId' => $advertId, 'userId' => $userId, 'status'=> MixedStatusUserSolrAds::ACTION_NOT_TAKEN));
                                if (!empty($inactiveAdUserObj)) {
                                    $inactiveAdUserObj->setStatus(MixedStatusUserSolrAds::ACTION_TAKEN);
                                    $this->em->persist($inactiveAdUserObj);
                                    $this->em->flush();
                                }
                                $output->writeln("Advert Id: $advertId updated to solr", true);
                            }
                        }
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:solvemixedstatus-user-ads '.$commandOptions;
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
        $adRepository  = $this->em->getRepository('FaAdBundle:MixedStatusUserSolrAds');
        $qb = $adRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            $qb->select('COUNT('.MixedStatusUserSolrAdsRepository::ALIAS.'.id)');
        } else {
            $qb->select(MixedStatusUserSolrAdsRepository::ALIAS.'.adId AS adId', MixedStatusUserSolrAdsRepository::ALIAS.'.userId AS userId', MixedStatusUserSolrAdsRepository::ALIAS.'.adStatus AS adStatus');
        }
        
        $qb->where(MixedStatusUserSolrAdsRepository::ALIAS.'.status = :status')
            ->setParameter('status', MixedStatusUserSolrAds::ACTION_NOT_TAKEN);
        
        if (!$onlyCount) {
            $qb->orderBy(MixedStatusUserSolrAdsRepository::ALIAS.'.adId');
        }

        return $qb;
    }
}
