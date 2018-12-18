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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\ArchiveBundle\Repository\ArchiveAdRepository;
use Fa\Bundle\ArchiveBundle\Repository\ArchiveAdImageRepository;

/**
 * This command is used to clean ad image data based on date and checking on aws image exist or not
 *
 * @author Gaurav Aggarwal <gaurav.aggarwal@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class DeleteNFSImagesCommand extends ContainerAwareCommand
{
    const AD_TYPE       = 'ad';
    const ARCHIVE_TYPE  = 'archive';
    
    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;


    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:delete-nfs-images')
        ->setDescription('Delete NFS Images')
        ->addOption('start_date', null, InputOption::VALUE_OPTIONAL, 'startDate in unix timestamp', 0)
        ->addOption('end_date', null, InputOption::VALUE_OPTIONAL, 'endDate in unix timestamp', 0)
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'advert Id should be integer', 0)
        ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'status should be integer', 0)
        ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'type used to get to know ad or archive', 0)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "2000M")
        ->setHelp(
            <<<EOF
Cron: Will run manually if NFS space become less.

Actions:
- Can be run to remove images those are uploaded or not required data.

Command:
 - php app/console fa:delete-nfs-images --start_date=1542540783 --end_date=1542627272
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
        $this->em 					= $this->getContainer()->get('doctrine')->getManager();
        $offset 					= $input->getOption('offset');
        $this->adImageDir 			= 'uploads/image/';
        $searchParam 				= array();
        $ids 						= $input->getOption('ad_id');
        $status 					= $input->getOption('status');
        $searchParam['start_date'] 	= intval($input->getOption('start_date'));
        $searchParam['end_date'] 	= intval($input->getOption('end_date'));
        $searchParam['type']    	= ($input->getOption('type') && $input->getOption('type') == "archive"?self::ARCHIVE_TYPE:self::AD_TYPE);
        $status 					= $input->getOption('status');
        $offset 					= $input->getOption('offset');
        
        echo ucfirst($searchParam['type'])." clearing Command Started At: ".date('Y-m-d H:i:s', time())."\n";
        if ($ids) {
            $ids = explode(',', $ids);
            $searchParam['ad_ids'] = array_map('trim', $ids);
        } else {
            $searchParam['ad_ids'] = null;
        }
        if ($status) {
            $status= explode(',', $status);
            $searchParam['status']= array_map('trim', $status);
        } else {
            $searchParam['status'] = null;
        }
        if (isset($offset)) {
            $this->removeNFSImagesWithOffset($input, $output, $searchParam);
        } else {
            $this->removeNFSImages($input, $output, $searchParam);
        }
    }
    
    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function removeNFSImagesWithOffset($input, $output, $searchParam)
    {
        $qb          		= $this->getAdQueryBuilder($searchParam);
        $step        		= 100;
        $offset    			= $input->getOption('offset');
        
        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        
        $ads = $qb->getQuery()->getResult();
        $entityManager 		= $this->getContainer()->get('doctrine')->getManager();
        
        if (!empty($ads)) {
            foreach ($ads as $ad) {
                if ($searchParam['type'] == self::ARCHIVE_TYPE) {
                    $this->clearArchiveAdvertImages($ad, $entityManager);
                } else {
                    $this->clearAdvertImages($ad, $entityManager, $output);
                }
            }
        }
    }
    
    protected function clearAdvertImages($ad, $entityManager, $output)
    {
        $getAdvertImages 				= $entityManager->getRepository('FaAdBundle:AdImage')->findBy(['ad'=>$ad->getId()]);
        if (!empty($getAdvertImages)) {
            foreach ($getAdvertImages as $image) {
                $imagePath  			= $this->adImageDir.CommonManager::getGroupDirNameById($image->getAd()->getId());
                if ($image->getImageName() != '') {
                    $imageUrl = $image->getPath().'/'.$image->getImageName().'.jpg';
                } else {
                    $imageUrl = $image->getPath().'/'.$ad->getId().'_'.$image->getHash().'.jpg';
                }
                $adImageManager 		= new AdImageManager($this->getContainer(), $image->getAd()->getId(), $image->getHash(), $imagePath);
                $checkImageExistOnAWS 	= $adImageManager->checkImageExistOnAws($imageUrl);
                
                if ($checkImageExistOnAWS === false) {
                    //$image->setAws(0);
                    //$this->em->persist($image);
                    //$this->em->flush();
                    //logging for status changed
                    $this->getContainer()->get('clean_local_images_logger')->info('Code Commented AWS status changed to 0 for Id ' . $image->getId());
                    $output->writeln('Image not existed on Aws bucket: '.$image->getId(), true);
                } elseif ($checkImageExistOnAWS=== true) {
                    $adImageManager->removS3ImagesFromLocal($image);
                }
            }
        }
    }
    
    protected function clearArchiveAdvertImages($ad, $entityManager)
    {
        //chech archive advert still exist in ad table
        $isExistInAdTable 				= $entityManager->getRepository('FaAdBundle:Ad')->find($ad->getId());
        if (is_null($isExistInAdTable)) {
            $getAdvertImages 				= $entityManager->getRepository('FaArchiveBundle:ArchiveAdImage')->findBy(['archive_ad'=>$ad->getId()]);
            if (!empty($getAdvertImages)) {
                foreach ($getAdvertImages as $image) {
                    $imagePath  			= $this->adImageDir.CommonManager::getGroupDirNameById($image->getArchiveAd()->getId());
                    $imageUrl = $this->getContainer()->getParameter('fa.static.aws.url').'/'.$image->getPath().'/'.$ad->getId().'_'.$image->getHash().'.jpg';
                    $adImageManager 		= new AdImageManager($this->getContainer(), $image->getArchiveAd()->getId(), $image->getHash(), $imagePath);
                    $adImageManager->removeArchiveAdImagesFromLocal($image);
                }
            }
        }
    }
    
    /**
     * Remove NFS Image.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function removeNFSImages($input, $output, $searchParam)
    {
        $qb        = $this->getAdQueryBuilder($searchParam);
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
            
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:delete-nfs-images '.$commandOptions;
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
     * @param array $searchParam
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder($searchParam)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $data = array();
        
        if ($searchParam['type'] == self::ARCHIVE_TYPE) {
            $q = $this->em->getRepository('FaArchiveBundle:ArchiveAd')->createQueryBuilder(ArchiveAdRepository::ALIAS);
            if ((isset($searchParam['start_date']) && $searchParam['start_date'] != '') && (isset($searchParam['end_date']) && $searchParam['end_date'] != '')) {
                $q->andWhere(ArchiveAdRepository::ALIAS.'.archived_at >= :startDate');
                $q->setParameter('startDate', $searchParam['start_date']);
                $q->andWhere(ArchiveAdRepository::ALIAS.'.archived_at <= :endDate');
                $q->setParameter('endDate', $searchParam['end_date']);
            } elseif (isset($searchParam['start_date']) && $searchParam['start_date'] != '') {
                $q->andWhere(ArchiveAdRepository::ALIAS.'.archived_at >= :startDate');
                $q->setParameter('startDate', $searchParam['start_date']);
            } elseif (isset($searchParam['end_date']) && $searchParam['end_date'] != '') {
                $q->andWhere(ArchiveAdRepository::ALIAS.'.archived_at <= :endDate');
                $q->setParameter('endDate', $searchParam['end_date']);
            }
            
            if ($searchParam['ad_ids']) {
                $q->andWhere(ArchiveAdRepository::ALIAS.'.id IN (:ids)');
                $q->setParameter('ids', $searchParam['ad_ids']);
            }
            
            $q->addOrderBy(ArchiveAdRepository::ALIAS.'.id');
        } else {
            $q = $this->em->getRepository('FaAdBundle:Ad')->createQueryBuilder(AdRepository::ALIAS);
            if ($searchParam['status']) {
                $q->andWhere(AdRepository::ALIAS.'.status IN (:status_ids)');
                $q->setParameter('status_ids', $searchParam['status']);
            }
            if ((isset($searchParam['start_date']) && $searchParam['start_date'] != '') && (isset($searchParam['end_date']) && $searchParam['end_date'] != '')) {
                $q->andWhere(AdRepository::ALIAS.'.created_at >= :startDate');
                $q->setParameter('startDate', $searchParam['start_date']);
                $q->andWhere(AdRepository::ALIAS.'.created_at <= :endDate');
                $q->setParameter('endDate', $searchParam['end_date']);
            } elseif (isset($searchParam['start_date']) && $searchParam['start_date'] != '') {
                $q->andWhere(AdRepository::ALIAS.'.created_at >= :startDate');
                $q->setParameter('startDate', $searchParam['start_date']);
            } elseif (isset($searchParam['end_date']) && $searchParam['end_date'] != '') {
                $q->andWhere(AdRepository::ALIAS.'.created_at <= :endDate');
                $q->setParameter('endDate', $searchParam['end_date']);
            }
           
            if ($searchParam['ad_ids']) {
                $q->andWhere(AdRepository::ALIAS.'.id IN (:ids)');
                $q->setParameter('ids', $searchParam['ad_ids']);
            }
           
            $q->addOrderBy(AdRepository::ALIAS.'.id');
        }
        
        return $q;
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
