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
use Fa\Bundle\UserBundle\Repository\UserSiteImageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Manager\UserSiteImageManager;

/**
 * This command is used to generate entity cache.
 *
 * php bin/console fa:upload-user-site:image-s3  --user_id="xxxx"
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2019 Friday Media Group Ltd
 * @version v1.0
 */
class UploadUserSiteImagesToAwsS3Command extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:upload-user-site:image-s3')
        ->setDescription('Upload user images to s3')
        ->addOption('user_site_id', null, InputOption::VALUE_OPTIONAL, 'User Site ID', null);
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
        
        $userImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/'.$this->getContainer()->getParameter('fa.user.site.image.dir').'/';

        $QUERY_BATCH_SIZE = 10;
        $done             = false;
        $last_id          = 0;

        $ids = $input->getOption('user_site_id');

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
                $userSiteImages = $this->getUserSiteImages($ids);
                foreach ($userSiteImages as $userSiteImage) {
                    $imagePath  = $userImageDir.CommonManager::getGroupDirNameById($id, 5000);
                    $userSiteImageManager = new UserSiteImageManager($this->getContainer(), $userSiteImage->getUserSite()->getId(), $userSiteImage->getHash(), $userSiteImage->getPath());
                    
                    $userSiteImageManager->uploadImagesToS3($userSiteImage->getUserSite()->getId(), $userSiteImage->getHash(), $userSiteImage->getPath());
                    echo "Uploaded to s3 image Id".$id."\n";
                }  
                $done = true;
            } else {
                $done = true;
            }
        }
    }
    
    public function getUserSiteImages($ids)
    {
        $userSiteImageRepository  = $this->em->getRepository('FaUserBundle:UserSiteImage');
        $qb = $userSiteImageRepository->createQueryBuilder(UserSiteImageRepository::ALIAS);
        $qb->andWhere(UserSiteImageRepository::ALIAS.'.user_site IN (:user_sites)');
        $qb->setParameter('user_sites', $ids);
        return $qb->getQuery()->getResult();
    }

}


