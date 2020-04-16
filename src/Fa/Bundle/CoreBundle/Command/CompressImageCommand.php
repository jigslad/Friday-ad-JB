<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdImageRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\UserSiteRepository;


/**
 * This command is used to compress images in bulk
 *
 * php bin/console fa:compress:images
 *
 * @author Rohini<rohini.subburam@fridaymediagroup.com>
 * @copyright  2019 Friday Media Group Ltd
 * @version 1.0
 */
class CompressImageCommand extends ContainerAwareCommand
{
    
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->setName('fa:compress:images')
        ->addOption('img_folder', null, InputOption::VALUE_OPTIONAL, 'Folder Name', 'company')
        ->addOption('img_filesize', null, InputOption::VALUE_OPTIONAL, 'ImageSize Width x Height', '199x150')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setDescription('Compresses images in the given folder command')
        ->setHelp(
            <<<EOF
Actions:
- Use to compress image
            
Command:
 - php bin/console fa:compress:images
 - php bin/console fa:compress:images --img_folder="company"
- php bin/console fa:compress:images --img_folder="company --img_filesize="199x150"
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
        $this->input = $input;
        $this->output = $output;
        
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        
        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";
        
        //get options passed in command
        $offset   = $input->getOption('offset');
        
        if (isset($offset)) {
            $this->compressImageWithOffset($input, $output);
        } else {
            $this->compressImage($input, $output);
        }
        
        //$this->compressImageCommand();
    }
    
    /**
     * Compress image with given offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function compressImageWithOffset($input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getQueryBuilder();
        $step        = 5;
        $offset      = $input->getOption('offset');
        
        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        
        $results = $qb->getQuery()->getResult();
        
        $imgFolder      =  ($input->getOption('img_folder'))?$input->getOption('img_folder'):'company';
        $imgSize      =  ($input->getOption('img_filesize'))?$input->getOption('img_filesize'):'199X150';
        
        $dir = $this->getContainer()->get('kernel')->getRootDir().'/../web/';
        
        
        
        foreach($results as $result) {
            $fileName = '';
            if($imgFolder=='image') {
                $fileName = $dir.$result->getPath().'/'.$result->getImageName().'.jpg';                
            } elseif($imgFolder=='user') {
                $fileName = $dir.$result->getImage().'/'.$result->getId().'.jpg'; 
            } else {
                $fileName = $dir.$result->getPath().'/'.$result->getUser()->getId().'.jpg'; 
            }
            if(file_exists($fileName)) {
                exec('convert -thumbnail '.$imgSize.' -sampling-factor 4:2:0 -strip -quality 75% '.$fileName.' jpg:'.$fileName);
                $this->output->writeln('Successfully compressed file  '.$fileName);
            }
        
        }
        
    }
    
    /**
     * Compress image.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function compressImage($input, $output)
    {
        $count     = $this->getCount();
        $step      = 5;
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
            
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:compress:images '.$commandOptions.' ';
            $output->writeln($command, true);
            passthru($command, $returnVar);
            
            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
        
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
    
    protected function getQueryBuilder()
    {
        $compressValue      =  ($this->input->getOption('img_folder'))?$this->input->getOption('img_folder'):'company';
        
        if($compressValue=='image') {
            $adImageRepository  = $this->em->getRepository('FaAdBundle:AdImage');
            $qb = $adImageRepository->createQueryBuilder(AdImageRepository::ALIAS);
            $qb->andWhere(AdImageRepository::ALIAS.'.aws =0');
            $qb->andWhere(AdImageRepository::ALIAS.".image_name !=''");
        } elseif($compressValue=='user') {
            $userRepository  = $this->em->getRepository('FaUserBundle:User');
            $qb = $userRepository->createQueryBuilder(UserRepository::ALIAS);
            $qb->andWhere(UserRepository::ALIAS.".image !=''");
        } else {
            $userSiteRepository  = $this->em->getRepository('FaUserBundle:UserSite');
            $qb = $userSiteRepository->createQueryBuilder(UserSiteRepository::ALIAS);
            $qb->andWhere(UserSiteRepository::ALIAS.".path !=''");
        }        
        return $qb;
    }
    
    protected function getCount()
    {
        $compressValue      =  ($this->input->getOption('img_folder'))?$this->input->getOption('img_folder'):'company';
        
        if($compressValue=='image') {
            $adImageRepository  = $this->em->getRepository('FaAdBundle:AdImage');            
            $qb = $adImageRepository->createQueryBuilder(AdImageRepository::ALIAS);
            $qb->select('COUNT('.AdImageRepository::ALIAS.'.id)');
            $qb->andWhere(AdImageRepository::ALIAS.'.aws =0');
            $qb->andWhere(AdImageRepository::ALIAS.".image_name !=''");
        } elseif($compressValue=='user') {
            $userRepository  = $this->em->getRepository('FaUserBundle:User');
            $qb = $userRepository->createQueryBuilder(UserRepository::ALIAS);
            $qb->select('COUNT('.UserRepository::ALIAS.'.id)');
            $qb->andWhere(UserRepository::ALIAS.".image !=''");
        } else {
            $userSiteRepository  = $this->em->getRepository('FaUserBundle:UserSite');
            $qb = $userSiteRepository->createQueryBuilder(UserSiteRepository::ALIAS);
            $qb->select('COUNT('.UserSiteRepository::ALIAS.'.id)');
            $qb->andWhere(UserSiteRepository::ALIAS.".path !=''");
        }
        
        return $qb->getQuery()->getSingleScalarResult();
    }
    
}
 ?>