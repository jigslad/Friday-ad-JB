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
 * This command is used to remove image from aws.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2019 Friday Media Group Ltd
 * @version v1.0
 */
class RemoveSingleImageToAwsS3Command extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:remove:single-image-s3')
        ->setDescription('Remove images to s3')
        ->addOption('file_path', null, InputOption::VALUE_OPTIONAL, 'File Path', null);
    }
    /*  php bin/console fa:remove:single-image-s3 --file_path=uploads/category/app/category_animals_s.jpg */
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
	
        $filePath = $input->getOption('file_path');

	$client = new S3Client([
            'version'     => 'latest',
            'region'      => $this->getContainer()->getParameter('fa.aws_region'),
            'credentials' => [
                'key'    => $this->getContainer()->getParameter('fa.aws_key'),
                'secret' => $this->getContainer()->getParameter('fa.aws_secret'),
            ],
        ]);
        
        $awsPath = $this->getContainer()->getParameter('fa.static.aws.url');
        $awsSourceImg = $awsPath.'/uploads/'.$filePath;
                         
        $fileKeys[] = array('Key' => $filePath);
               
        if(!empty($fileKeys)) {
            $result = $client->deleteObjects(array(
                'Bucket'  => $this->getContainer()->getParameter('fa.aws_bucket'),
                'Delete'  => array('Objects' => $fileKeys)
            ));
            echo "Deleted file successfully ".$filePath."\n"; 
        } else {
		echo "File does not exists in this path ".$filePath."\n";  
        } 
    }
}
