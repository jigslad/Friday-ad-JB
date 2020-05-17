<?php

/** This file is part of the fa bundle.
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
use Fa\Bundle\AdBundle\Repository\AdImageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:download:feed download  --type="gun" --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UploadImagesToAwsS3NewCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:upload:image-s3-new')
        ->setDescription('Upload images to s3')
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad ID', null);
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
        
        //get options passed in command
        $offset   = $input->getOption('offset');
        
        if (isset($offset)) {
            $this->UploadImagesToAwsS3WithOffset($input, $output);
        } else {
            $this->UploadImagesToAwsS3($input, $output);
        }
    }
    
    
    protected function UploadImagesToAwsS3WithOffset($input, $output)
    {
        $qb          = $this->getAdQueryBuilder(false, $input);
        $step        = 100;
        $offset      = $input->getOption('offset');
        
        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        
        $images = $qb->getQuery()->execute();
        $adImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/image/';
        
        foreach ($images as $image) {
            if ($image->getAd()) {
                $cleanImage = $this->em->getRepository('FaAdBundle:AdImage')->findOneBy(array('id' => $image->getId()));
                if ($cleanImage && $cleanImage->getAws() == 0) {
                    $imagePath  = $adImageDir.CommonManager::getGroupDirNameById($image->getAd()->getId());
                    $adImageManager = new AdImageManager($this->getContainer(), $image->getAd()->getId(), null, $imagePath);
                    $adImageManager->uploadImagesToS3($image);
                    echo "Uploaded to s3 image Id".$image->getId()."\n";
                }
            }            
        }
        
        $this->em->flush();
        $this->em->clear();
        
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }
    
    /**
     * Update solr index.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function UploadImagesToAwsS3($input, $output)
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
            
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:upload:image-s3-new '.$commandOptions;
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
     */
    protected function getAdQueryBuilder($onlyCount = false, $input)
    {
        $ids = $input->getOption('ad_id');
        
        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }
        
        $qb = $this->em->getRepository('FaAdBundle:AdImage')->createQueryBuilder(AdImageRepository::ALIAS);

        if ($onlyCount) {
            $qb->select('COUNT('.AdImageRepository::ALIAS.'.id)');
        }
        
        if ($ids) {
            $qb->andWhere(AdImageRepository::ALIAS.'.ad IN (:ids)');
            $qb->setParameter('ids', $ids);
        }
        
        $qb->andWhere(AdImageRepository::ALIAS.'.aws = 0');
        $qb->andWhere(AdImageRepository::ALIAS.'.local = 1');
        $qb->addOrderBy(AdImageRepository::ALIAS.'.id');
        return $qb;
    }
}
