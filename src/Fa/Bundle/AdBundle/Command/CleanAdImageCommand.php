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
use Gedmo\Sluggable\Util\Urlizer;
use Aws\S3\S3Client;
/**
 * This command is used to clean ad image data
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CleanAdImageCommand extends ContainerAwareCommand
{
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
        ->setName('fa:clean-ad-image-data')
        ->setDescription('Clean ad image data')
        ->addOption('action', null, InputOption::VALUE_OPTIONAL, 'Action', null)
        ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to remove unwanted feed data.

Command:
 - php app/console fa:clean-ad-image-data --action="remove-empty-folders"
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
        $managerRegistry = $this->getContainer()->get('doctrine');
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $stat_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);

        $webPath = $this->getContainer()->get('kernel')->getRootDir().'/../web';
        $awsPath = $this->getContainer()->getParameter('fa.static.aws.url');
        $adImagePath = $webPath.'/uploads/image/';
        $adImageRepository  = $this->em->getRepository('FaAdBundle:AdImage');

        $action = $input->getOption('action');
        $path = $input->getOption('path');
        
        $imageDir = $this->getContainer()->getParameter('fa.ad.image.dir').'/';

        if ($action == 'remove-empty-folders') {
            $adImageFolders = glob($adImagePath.'*');
            if (!empty($adImageFolders)) {
                foreach ($adImageFolders as $adImageFolder) {
                    CommonManager::removeEmptySubFolders($adImageFolder, $output);
                }
            }
        } elseif ($action == 'remove-images') {
            if (!$path) {
                $adImageFolders = glob($adImagePath.'*');
                if (!empty($adImageFolders)) {
                    foreach ($adImageFolders as $adImageFolder) {
                        $this->removeAdImagesCommand($input, $output, $adImageFolder);
                    }
                }
            } elseif ($path) {
                $adImages = glob($path.'/*.jpg');
                foreach ($adImages as $adImage) {
                    $imagePath = '';
                    $explodeRes = explode('_', basename($adImage));                    
                    if ($explodeRes[0]) {
                        $explodeRes[1] = str_replace('.jpg', '', $explodeRes[1]);                        
                        $adImageObj = $adImageRepository->findOneBy(array('ad' => $explodeRes[0], 'hash' => $explodeRes[1], 'aws' => 1, 'local' => 0));
                        if (!$adImageObj) {                           
                            $adImageObj = $adImageRepository->findOneBy(array('ad' => $explodeRes[0], 'hash' => $explodeRes[1]));                           
                            if (!$adImageObj) {
                                if (is_file($adImage)) {
                                   if (unlink($adImage)) {
                                        $output->writeln('Image deleted for : '.$adImage, true);
                                        
                                        $imagePath  = $imageDir.CommonManager::getGroupDirNameById($explodeRes[0]);
                                        $imageUrl   = $imagePath.'/'.basename($adImage);
                                        
                                        $adImageManager 		= new AdImageManager($this->getContainer(), $explodeRes[0], $explodeRes[1], $imagePath);
                                        $checkImageExistOnAWS 	= $adImageManager->checkImageExistOnAws($imageUrl);
                                        
                                        if ($checkImageExistOnAWS === true) {
                                            $adImageManager->removeImageFromAmazoneS3($imageUrl);
                                            $output->writeln('Image deleted from local and aws : '.$adImage, true);
                                        }                                       
                                    } else {
                                        $output->writeln('Problem in deleting image in local : '.$adImage, true);
                                    } 
                                } else {
                                    $output->writeln('Image not found for : '.$adImage, true);
                                }
                            } else {
                                
                                $imagePath  			= $imageDir.CommonManager::getGroupDirNameById($adImageObj->getAd()->getId());
                                if ($adImageObj->getImageName() != '') {
                                    $imageUrl = $adImageObj->getPath().'/'.$adImageObj->getImageName().'.jpg';                                    
                                } else {
                                    $imageUrl = $adImageObj->getPath().'/'.$adImageObj->getAd()->getId().'_'.$adImageObj->getHash().'.jpg';                  
                                }
                                $adImageManager 		= new AdImageManager($this->getContainer(), $adImageObj->getAd()->getId(), $adImageObj->getHash(), $imagePath);
                                $checkImageExistOnAWS 	= $adImageManager->checkImageExistOnAws($imageUrl);
                                
                                if ($checkImageExistOnAWS === false) {
                                    $adImageManager->uploadImagesToS3($adImageObj);
                                    if (is_file($adImage)) {
                                        if (unlink($adImage)) {
                                            $output->writeln('Image uploaded to aws & deleted from local : '.$adImage, true);
                                        } else {
                                            $output->writeln('Problem in deleting image from local : '.$adImage, true);
                                        }
                                    } 
                                } else {
                                    if (is_file($adImage)) {
                                        if (unlink($adImage)) {
                                            $output->writeln('Image deleted from local since it exists in aws : '.$adImage, true);
                                        } else {
                                            $output->writeln('Image exists in aws and problem in deleting image from local : '.$adImage, true);
                                        }
                                    }
                                }
                            }
                        } else {
                            $imagePath  			= $imageDir.CommonManager::getGroupDirNameById($adImageObj->getAd()->getId());
                            if ($adImageObj->getImageName() != '') {
                                $imageUrl = $adImageObj->getPath().'/'.$adImageObj->getImageName().'.jpg';
                            } else {
                                $imageUrl = $adImageObj->getPath().'/'.$adImageObj->getAd()->getId().'_'.$adImageObj->getHash().'.jpg';
                            }
                            $adImageManager 		= new AdImageManager($this->getContainer(), $adImageObj->getAd()->getId(), $adImageObj->getHash(), $imagePath);
                            $checkImageExistOnAWS 	= $adImageManager->checkImageExistOnAws($imageUrl);
                            
                            if ($checkImageExistOnAWS === false) {
                                $adImageManager->uploadImagesToS3($adImageObj);
                                if (is_file($adImage)) {
                                    if (unlink($adImage)) {
                                        $output->writeln('Image uploaded to aws & deleted from local : '.$adImage, true);
                                    } else {
                                        $output->writeln('Problem in deleting image from local : '.$adImage, true);
                                    }
                                }
                            } else {
                                if (is_file($adImage)) {
                                    if (unlink($adImage)) {
                                        $output->writeln('Image deleted from local since it exists in aws : '.$adImage, true);
                                    } else {
                                        $output->writeln('Image exists in aws and problem in deleting image from local : '.$adImage, true);
                                    }
                                }
                            }                            
                        }
                    }
                }
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
    
    /**
     * Check images exist on AWS
     *
     * @param boolean $keepOriginal Flag for keep original image.
     */
    public function checkImageExistOnAws($imageUrl)
    {
        $client = new S3Client([
            'version'     => 'latest',
            'region'      => $this->getContainer()->getParameter('fa.aws_region'),
            'credentials' => [
                'key'    => $this->getContainer()->getParameter('fa.aws_key'),
                'secret' => $this->getContainer()->getParameter('fa.aws_secret'),
            ],
        ]);
        $response = $client->doesObjectExist($this->getContainer()->getParameter('fa.aws_bucket'), $imageUrl);
        return $response;
    }

    /**
     * Remove ad images.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function removeAdImagesCommand($input, $output, $path)
    {
        $commandOptions = null;
        foreach ($input->getOptions() as $option => $value) {
            if ($value) {
                $commandOptions .= ' --'.$option.'='.$value;
            }
        }

        if ($path) {
            $commandOptions .= ' --path='.$path;
        }
        $memoryLimit = '';
        if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
            $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
        }
        $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:clean-ad-image-data '.' '.$commandOptions;
        $output->writeln($command, true);
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            $output->writeln('Error occurred during subtask', true);
        }
    }
}
