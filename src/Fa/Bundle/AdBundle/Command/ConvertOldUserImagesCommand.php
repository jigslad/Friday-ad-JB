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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdImageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\CoreBundle\Manager\ThumbnailManager;
use Fa\Bundle\UserBundle\Repository\UserSiteRepository;
use Fa\Bundle\UserBundle\Repository\UserImageRepository;
use Fa\Bundle\UserBundle\Manager\UserImageManager;
use Fa\Bundle\UserBundle\Entity\UserSiteImage;
use Fa\Bundle\UserBundle\Manager\UserSiteImageManager;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:download:feed download  --type="gun" --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ConvertOldUserImagesCommand extends ContainerAwareCommand
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:convert:old-user-images')
        ->setDescription('Convert old user images from old site')
        ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'update all images', null)
        ->addOption('update_type', null, InputOption::VALUE_OPTIONAL, 'Update type from migration', null);
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

        //$imageDir   = $this->getContainer()->get('kernel')->getRootDir().'/../data/oldimages';
        $imageDir      = '/var/media/data/olduserimages';
        $userLogoDir   = '/web/mig.friday-ad.cok.uk/web/mig_uploads/company';
        $siteImagesDir = '/web/mig.friday-ad.cok.uk/web/mig_uploads/usersite';


        $QUERY_BATCH_SIZE = 1000;
        $done             = false;
        $last_id          = 0;
        $update_type   = $input->getOption('update_type');
        $force   = $input->getOption('force');

        while (!$done) {
            $images = $this->getImages($last_id, $QUERY_BATCH_SIZE, $update_type);
            if ($images) {
                foreach ($images as $image) {
                    $path  = $imageDir.'/'.basename($image->getOldPath());
                    $dimension  = @getimagesize($path);
                    if (file_exists($path) && $dimension && $this->checkValidImage($path)) {
                        $user  = $image->getUser();
                        $userSite = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user));
                        if ($image->getOrd() == 0) {
                                CommonManager::createGroupDirectory($userLogoDir, $user->getId(), 5000);
                                $imagePath =  $userLogoDir.'/'.CommonManager::getGroupDirNameById($user->getId(), 5000);
                                copy($path, $imagePath.'/'.basename($image->getOldPath()));
                                $userImageManager = new UserImageManager($this->getContainer(), $user->getId(), $imagePath, true);
                                $userImageManager->removeImage();
                                $userImageManager->saveOriginalJpgImage(basename($image->getOldPath()));
                                $userImageManager->createThumbnail();
                                $userSite->setPath('uploads/company/'.CommonManager::getGroupDirNameById($user->getId(), 5000));
                                $this->em->persist($userSite);
                        } else {
                            $path  = $imageDir.'/'.basename($image->getOldPath());
                            $userSiteImage = $this->em->getRepository('FaUserBundle:UserSiteImage')->find($image->getId());

                            if (!$userSiteImage) {
                                $metadata = $this->em->getClassMetaData('Fa\Bundle\UserBundle\Entity\UserSiteImage');
                                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                                $userSiteImage = new UserSiteImage();
                                $userSiteImage->setId($image->getId());
                            }

                            if ($userSite) {
                                $imagePath =  $siteImagesDir.'/'.CommonManager::getGroupDirNameById($userSite->getId(), 5000);
                                $hash = substr(md5(serialize($dimension).$path), 0, 32);
                                if (!file_exists($imagePath.'/'.$userSite->getId().'_'.$hash.'.jpg') || $force == true) {
                                    CommonManager::createGroupDirectory($siteImagesDir, $userSite->getId(), 5000);
                                    copy($path, $imagePath.'/'.basename($image->getOldPath()));
                                    $userSiteImage->setHash($hash);
                                    $userSiteImage->setUserSite($userSite);
                                    $userSiteImage->setPath('uploads/usersite/'.CommonManager::getGroupDirNameById($userSite->getId(), 5000));
                                    $userSiteImage->setOrd($image->getOrd());
                                    $usersiteImageManager = new UserSiteImageManager($this->getContainer(), $userSite->getId(), $hash, $imagePath);
                                    $usersiteImageManager->saveOriginalJpgImage(basename($image->getOldPath()));
                                    $usersiteImageManager->createThumbnail();
                                    $this->em->persist($userSiteImage);

                                    echo "User site image converted :".$imagePath.'/'.$userSite->getId().'_'.$hash.'.jpg'."\n";
                                } else {
                                    if ($userSiteImage->getHash() == '') {
                                        $userSiteImage->setHash($hash);
                                        $userSiteImage->setUserSite($userSite);
                                        $userSiteImage->setPath('uploads/usersite/'.CommonManager::getGroupDirNameById($userSite->getId(), 5000));
                                        $userSiteImage->setOrd($image->getOrd());
                                        $this->em->persist($userSiteImage);
                                    }
                                        echo "User site image already converted :".$imagePath.'/'.$userSite->getId().'_'.$hash.'.jpg'."\n";
                                }
                            }
                        }
                    }
                    $this->em->flush();
                }

                $last_id = $image->getId();

            } else {
                $done = true;
            }

            $this->em->flush();
            $this->em->clear();
        }
    }

    /**
     * Get images.
     *
     * @param integer $last_id           Last id.
     * @param integer $QUERY_BATCH_SIZE  Size of query batch.
     */
    public function getImages($last_id, $QUERY_BATCH_SIZE, $update_type)
    {
        $q = $this->em->getRepository('FaUserBundle:UserImage')->createQueryBuilder(UserImageRepository::ALIAS);
        $q->andWhere(UserImageRepository::ALIAS.'.old_path IS NOT NULL');
        $q->andWhere(UserImageRepository::ALIAS.'.id > :id');
        $q->setParameter('id', $last_id);
        $q->addOrderBy(UserImageRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);

        if ($update_type != '') {
            $q->andWhere(UserImageRepository::ALIAS.'.update_type LIKE :update_type');
            $q->setParameter('update_type', $update_type);
        }

        return $q->getQuery()->getResult();
    }


    private function checkValidImage($image_file)
    {
        $ext = pathinfo($image_file, PATHINFO_EXTENSION);
        if ($ext == 'jpg') {
            $ext = 'jpeg';
        }
        $function = 'imagecreatefrom' . $ext;
        if (function_exists($function) && @$function($image_file) === false) {
            return false;
        }

        return true;
    }
}
