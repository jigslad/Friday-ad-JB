<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2019, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Aws\S3\S3Client;

/**
 * This command is used to move from local to NFS.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2019 Friday Media Group Ltd
 * @version v1.0
 * @deprecated This command shouldn't be used since the direct image upload to S3 in PAA is implemented.
 */
class MoveImagesToAwsS3Command extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:move:image-s3')
        ->setDescription('Move images to s3')
        ->addOption('folder_name', null, InputOption::VALUE_OPTIONAL, 'Folder Name', null);
    }
    /*  php bin/console fa:move:image-s3 --folder_name=homepopular */
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

        $start_time = time();
        echo "Command Started At: ".date('Y-m-d H:i:s', $start_time)."\n";
	
	    $folderName = $input->getOption('folder_name');

        $imageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/'.$folderName;
        $client = new S3Client([
            'version'     => 'latest',
            'region'      => $this->getContainer()->getParameter('fa.aws_region'),
            'credentials' => [
                'key'    => $this->getContainer()->getParameter('fa.aws_key'),
                'secret' => $this->getContainer()->getParameter('fa.aws_secret'),
            ],
        ]);
        
        if ($handle = opendir($imageDir)) {
            while (false !== ($entry = readdir($handle))) {
                $files[] = $entry;
            }
            $images = preg_grep('/\.(jpg|jpeg|png|gif|svg)(?:[\?\#].*)?$/i', $files);
            
            foreach ($images as $image) {   
                $destinationPath = 'uploads/'.$folderName.'/'.$image;
                $sourcePath = $imageDir.'/'.$image;
                
                $result = $client->putObject(array(
                    'Bucket'     => $this->getContainer()->getParameter('fa.aws_bucket'),
                    'Key'        => $destinationPath,
                    'CacheControl' => 'max-age=21600',
                    'ACL'        => 'public-read',
                    'SourceFile' => $sourcePath,
                    'Metadata'   => array(
                        'Last-Modified' => time(),
                    )
                ));
                
                $resultData =  $result->get('@metadata');
                if ($resultData['statusCode'] == 200) {
                    echo "Uploaded to s3 image ".$image."\n";  
                    unlink($sourcePath);
                } else {
                    echo "Error in uploading to s3 image ".$image."\n";    
                }
            }
            closedir($handle);
        } else {
            echo "Error in opening directory \n"; 
        }
    }
}


