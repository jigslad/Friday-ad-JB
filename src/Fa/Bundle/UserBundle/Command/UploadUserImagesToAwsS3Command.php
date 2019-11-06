<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2019, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\UserBundle\Repository\UserImageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Manager\UserImageManager;

/**
 * This command is used to generate entity cache.
 *
 * php bin/console fa:upload-user:image-s3  --user_id="xxxx" --image_type="company"
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2019 Friday Media Group Ltd
 * @version v1.0
 */
class UploadUserImagesToAwsS3Command extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:upload-user:image-s3')
        ->setDescription('Upload user images to s3')
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'User ID', null)
        ->addOption('image_type', null, InputOption::VALUE_OPTIONAL, 'Image Type', null);
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

        $start_time = time();
        echo "Command Started At: ".date('Y-m-d H:i:s', $start_time)."\n";
        
        $image_type = $input->getOption('image_type');
        
        if($image_type=='company') {
            $userImageFolder = $this->getContainer()->getParameter('fa.company.image.dir');
        } elseif($image_type=='user') {
            $userImageFolder = $this->getContainer()->getParameter('fa.user.image.dir');
        } 

        $userImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/'.$userImageFolder.'/';

        $QUERY_BATCH_SIZE = 10;
        $done             = false;
        $last_id          = 0;

        $ids = $input->getOption('user_id');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        while (!$done) {
            if ((time() - $start_time) > (60*5)) {
                $done = true;
            }
            
            if ($ids) {
                foreach ($ids as $id) {
                    $imagePath  = $userImageDir.CommonManager::getGroupDirNameById($id, 5000);
                    $userImageManager = new UserImageManager($this->getContainer(), $id, null, $imagePath);
                    $userImageManager->uploadImagesToS3($id,$image_type);
                    echo "Uploaded to s3 image Id".$id."\n";
                }  
                $done = true;
            } else {
                $done = true;
            }
        }
    }

}

