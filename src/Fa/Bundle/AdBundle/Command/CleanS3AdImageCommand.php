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
 * @author Rohini Subburam <rohini.subburam@fridaymediagroup.com>
 * @copyright 2019 Friday Media Group Ltd
 * @version 1.0
 */
class CleanS3AdImageCommand extends ContainerAwareCommand
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
        ->setName('fa:clean-s3-ad-image-data')
        ->setDescription('Clean S3 ad image data')
        ->addOption('action', null, InputOption::VALUE_OPTIONAL, 'Action', null)
        ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path', null)
        ->addOption('marker', null, InputOption::VALUE_OPTIONAL, 'starting point of image folder', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'number of images to be fetched', null)
        ->setHelp(
            <<<EOF
            Cron: To be setup to run at mid-night.
            
            Actions:
            - Can be run to remove unwanted feed data.
            
            Command:
             - php bin/console fa:clean-s3-ad-image-data  --path="uploads/image/17587101_17587200" --marker="uploads/image/15549701_15549800/restaurant-with-coffee-shop-and-pub-in-dursley-for-sale-15549759-1.jpg"  --offset=1000
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
        $stat_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $path = $input->getOption('path');
        $marker = $input->getOption('marker');
        $offset = $input->getOption('offset');
        
        if($path) {
            $checkFolder = $path;
        } else {
            $checkFolder = 'uploads/image';
        }
        if($marker) { $marker = $marker; } else { $marker = ''; }
        if($offset) { $offset = $offset; } else { $offset = 1000; }
        
        $adImageRepository  = $this->em->getRepository('FaAdBundle:AdImage');
        
        $client = new S3Client([
            'version'     => 'latest',
            'region'      => $this->getContainer()->getParameter('fa.aws_region'),
            'credentials' => [
                'key'    => $this->getContainer()->getParameter('fa.aws_key'),
                'secret' => $this->getContainer()->getParameter('fa.aws_secret'),
            ],
        ]);
        
        try{
            # initializing our object
            $files = $client->getIterator('ListObjects', [ # this is a Generator Object (its yields data rather than returning)
                'Bucket' => $this->getContainer()->getParameter('fa.aws_bucket'),
                'Prefix' => $checkFolder.'/',
                'Marker' => $marker,
                'MaxKeys' => $offset
            ]);
            $awsBucket = $this->getContainer()->getParameter('fa.aws_bucket');
            
            # printing our data
            foreach($files as $file) {
                $awsKey = $file['Key'];
                //if(strpos($awsKey, $checkFolder) === 0) {
                    $explodeAwsKey = explode('/',$awsKey);
                    $awsImageDBPath = $explodeAwsKey[0].'/'.$explodeAwsKey[1].'/'.$explodeAwsKey[2];
                    $explodeAwsImageName = explode('.',$explodeAwsKey[3]);
                    $awsImageName = explode('_',$explodeAwsImageName[0]);
                    $awsImageDBName = $awsImageName[0];
                    $adImageObj = $adImageRepository->findOneBy(array('path' => $awsImageDBPath, 'image_name' => $awsImageDBName));
                    $awsDestKey = $this->getContainer()->getParameter('fa.ad.image.bin.dir').'/'.$explodeAwsKey[2].'/'.$explodeAwsKey[3];
                    if(!$adImageObj) {
                        $awsImageDBHash = (isset($awsImageName) && isset($awsImageName[1]))?$awsImageName[1]:'';
                        if($awsImageDBHash) {
                            $adImageObj = $adImageRepository->findOneBy(array('path' => $awsImageDBPath, 'hash' => $awsImageDBHash));
                        }
                        if(!$adImageObj) {
                            $fileKeys = array();
                            $fileKeys[] = array('Key' => $awsKey);
                            /*$result = $client->deleteObjects(array(
                             'Bucket'  => $this->getContainer()->getParameter('fa.aws_bucket'),
                             'Delete'  => array('Objects' => $fileKeys)
                             ));
                             print_r($result);
                             $output->writeln('Image deleted from aws : '.$awsKey, true);*/
                            
                            $result = $client->copyObject([
                                'Bucket'     => $awsBucket,
                                'Key'        => $awsDestKey,
                                'CopySource' => $awsBucket.'/'.$awsKey,
                            ]);
                            $this->getContainer()->get('moved_s3_images_to_bin_logger')->info('Image moved from image-folder to image-bin-folder ' . $awsKey);
                            $output->writeln('Image moved from image-folder to image-bin-folder : '.$awsKey, true);
                            
                        }
                    } else {
                        $this->getContainer()->get('images_exists_s3_logger')->info('Image not moved exists in database : '.$awsKey);
                        $output->writeln('Image not moved exists in database : '.$awsKey, true);
                    }
                //}
            }
        } catch(\Exception $ex){
            echo "Error Occurred\n", $ex->getMessage();
        }
        
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
}