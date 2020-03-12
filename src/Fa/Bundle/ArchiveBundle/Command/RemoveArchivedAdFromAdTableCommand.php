<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2019, FMG
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
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2019 Friday Media Group Ltd
 * @version 1.0
 */
class RemoveArchivedAdFromAdTableCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:remove:archived-ad-from-ad-table')
        ->setDescription("Remove archived advert from advert table.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Ad ids', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Remove archived advert from advert table.

Command:
 - php bin/console fa:remove:archived-ad-from-ad-table --id="16791897,16794589,16795117,16795124"
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

        //get options passed in command
        $ids    = $input->getOption('id');

        if ($ids) {
            $adIds = explode(',', $ids);
        } else {
            $adIds = $this->getAds();
        }
        
        $removedAdIds = array();
        if(!empty($adIds)) {            
            try {
                foreach ($adIds as $adId) {
                    $ad = $entityManager->getRepository('FaAdBundle:Ad')->find($adId);
                    $entityManager->getRepository('FaArchiveBundle:ArchiveAd')->removeAd($ad, $this->getContainer());
                    $removedAdIds[] = $adId;
                    $output->writeln('Ad has been removed from advert table, ad id: '.$adId, true);
                }
            } catch (\Exception $e) {
                $output->writeln('Exception for removing archived ads from advert table '.$e, true);
            }
            if(!empty($removedAdIds)) {
                try {  
                    $removedAdIds = array_map('trim', $removedAdIds);
                    $solrClient = $this->getContainer()->get('fa.solr.client.ad');
                    if ($solrClient->ping()) {
                        $solr = $solrClient->connect();
                        $solr->deleteByIds($removedAdIds);
                        $solr->commit();
                        $output->writeln('Adverts from advert solr : '.implode(',',$removedAdIds), true);
                    }
                } catch (\Exception $e) {
                    $output->writeln('Exception for removing archived ads from solr '.$e, true);
                }
            }
        }        
    }    

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAds()
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $adTableName   = $entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName();
        $archiveTableName   = $entityManager->getClassMetadata('FaArchiveBundle:ArchiveAd')->getTableName();
        $sql = 'SELECT distinct(a.id) as id FROM '.$adTableName.' a, '.$archiveTableName.' ar WHERE ar.ad_main = a.id and a.status_id != 25';
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $adArr = $stmt->fetchAll();
        foreach($adArr as $ad) {
            $ads[] = $ad['id'];
        }
        return $ads;
    }   
}

