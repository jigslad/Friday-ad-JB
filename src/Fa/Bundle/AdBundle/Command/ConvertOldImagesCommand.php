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
use Gedmo\Sluggable\Util\Urlizer;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:download:feed download  --type="gun" --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 * @deprecated Command created in 2015. Not used now. Finish todos in code if this is to be used.
 */
class ConvertOldImagesCommand extends ContainerAwareCommand
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:convert:old-images')
        ->setDescription('Convert images from old site')
        ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'update all images', null)
        ->addOption('aws', null, InputOption::VALUE_OPTIONAL, 'update aws', null)
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
        $imageDir = '/var/media/data/oldimages';
        $adImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/mig_uploads/image/';

        $QUERY_BATCH_SIZE = 1000;
        $done             = false;
        $last_id          = 0;
        $update_type   = $input->getOption('update_type');
        $force   = $input->getOption('force');
        $aws     = $input->getOption('aws');

        while (!$done) {
            $images = $this->getImages($last_id, $QUERY_BATCH_SIZE, $update_type);
            if ($images) {
                foreach ($images as $image) {
                    $adObj = $image->getAd();
                    $path = $imageDir.'/'.basename($image->getOldPathOrg());
                    $imagePath  = $adImageDir.CommonManager::getGroupDirNameById($image->getAd()->getId());
                    $dimension  = @getimagesize($path);

                    if (file_exists($path) && $dimension && $this->checkValidImage($path)) {
                        $hash = substr(md5(serialize($dimension).$path), 0, 32);
                        if (!file_exists($imagePath.'/'.$image->getAd()->getId().'_'.$hash.'.jpg') || $force == true) {
                            CommonManager::createGroupDirectory($adImageDir, $image->getAd()->getId());
                            $image->setHash($hash);
                            $image->setPath('uploads/image/'.CommonManager::getGroupDirNameById($image->getAd()->getId()));
                            $image->setStatus(1);
                            $adObj = $image->getAd();
                            if ($adObj) {
                                $image->setImageName(Urlizer::urlize($adObj->getTitle().'-'.$adObj->getId().'-'.$image->getOrd()));
                            }

                            $image->setAws(0);

                            $this->getContainer()->get('doctrine')->getManager()->persist($image);

                            $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, 75, 'ImageMagickManager');
                            $origImage->loadFile($path);
                            $origImage->save($imagePath.'/'.$image->getAd()->getId().'_'.$hash.'.jpg', 'image/jpeg');

                            //if image is animated gif, use first layer and remove other layers.
                            if ($dimension['mime'] == 'image/gif') {
                                if (is_file($imagePath.DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$hash.'-0.jpg')) {
                                    rename($imagePath.DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$hash.'-0.jpg', $imagePath.DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$hash.'.jpg');
                                }

                                passthru('rm '.$imagePath.DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$hash.'-*.jpg 2> /dev/null');
                            }

                            $adImageManager = new AdImageManager($this->getContainer(), $image->getAd()->getId(), $hash, $imagePath);
                            // todo need to change to upload direct to AmazonS3 if this function is correct.
                            // Command not used anymore ? No need to make changes here.
                            $adImageManager->createThumbnail();
                            $adImageManager->createCropedThumbnail();

                            echo "ORG converted :".$imagePath.'/'.$image->getAd()->getId().'_'.$hash.'.jpg'."\n";
                        } else {
                            if ($image->getHash() == '') {
                                $image->setHash($hash);
                                $image->setPath('uploads/image/'.CommonManager::getGroupDirNameById($image->getAd()->getId()));
                                $image->setStatus(1);
                                $image->setAws(0);
                                $this->em->persist($image);
                            } else {
                                $image->setAws(1);
                                $this->em->persist($image);
                            }
                            echo "ORG Already converted :".$imagePath.'/'.$image->getAd()->getId().'_'.$hash.'.jpg'."\n";
                        }
                    } else {
                        $path = $imageDir.'/'.basename($image->getOldPath());
                        $imagePath  = $adImageDir.CommonManager::getGroupDirNameById($image->getAd()->getId());
                        $dimension  = @getimagesize($path);

                        if (file_exists($path) && $dimension && $this->checkValidImage($path)) {
                            $hash = substr(md5(serialize($dimension).$path), 0, 32);
                            if (!file_exists($imagePath.'/'.$image->getAd()->getId().'_'.$hash.'.jpg') || $force == true) {
                                CommonManager::createGroupDirectory($adImageDir, $image->getAd()->getId());
                                $image->setHash($hash);
                                $image->setPath('uploads/image/'.CommonManager::getGroupDirNameById($image->getAd()->getId()));
                                $image->setStatus(1);
                                $adObj = $image->getAd();

                                if ($image->getImageName() == '') {
                                    $image->setImageName(Urlizer::urlize($adObj->getTitle().'-'.$adObj->getId().'-'.$image->getOrd()));
                                    $image->setAws(0);
                                }

                                if ($aws == true) {
                                    $image->setAws(0);
                                }

                                $this->getContainer()->get('doctrine')->getManager()->persist($image);

                                $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, 75, 'ImageMagickManager');
                                $origImage->loadFile($path);
                                $origImage->save($imagePath.'/'.$image->getAd()->getId().'_'.$hash.'.jpg', 'image/jpeg');

                                //if image is animated gif, use first layer and remove other layers.
                                if ($dimension['mime'] == 'image/gif') {
                                    if (is_file($imagePath.DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$hash.'-0.jpg')) {
                                        rename($imagePath.DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$hash.'-0.jpg', $imagePath.DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$hash.'.jpg');
                                    }

                                    passthru('rm '.$imagePath.DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$hash.'-*.jpg 2> /dev/null');
                                }

                                $adImageManager = new AdImageManager($this->getContainer(), $image->getAd()->getId(), $hash, $imagePath);
                                // todo need to change to upload direct to AmazonS3 if this function is correct.
                                $adImageManager->createThumbnail();
                                $adImageManager->createCropedThumbnail();

                                echo "converted :".$imagePath.'/'.$image->getAd()->getId().'_'.$hash.'.jpg'."\n";
                            } else {
                                if ($image->getHash() == '') {
                                    $image->setHash($hash);
                                    $image->setPath('uploads/image/'.CommonManager::getGroupDirNameById($image->getAd()->getId()));
                                    $image->setStatus(1);
                                    $image->setAws(0);
                                    $this->em->persist($image);

                                    if ($image->getImageName() == '') {
                                        $image->setImageName(Urlizer::urlize($adObj->getTitle().'-'.$adObj->getId().'-'.$image->getOrd()));
                                    }
                                }
                                echo "Already converted :".$imagePath.'/'.$image->getAd()->getId().'_'.$hash.'.jpg'."\n";
                            }
                        } else {
                            echo "Not found :".$path."\n";
                        }
                    }

                    $this->getContainer()->get('doctrine')->getManager()->flush();
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
        $q = $this->em->getRepository('FaAdBundle:AdImage')->createQueryBuilder(AdImageRepository::ALIAS);
        $q->andWhere(AdImageRepository::ALIAS.'.old_path IS NOT NULL');
        $q->andWhere(AdImageRepository::ALIAS.'.aws != :aws');
        $q->andWhere(AdImageRepository::ALIAS.'.id > :id');
        $q->andWhere(AdImageRepository::ALIAS.'.update_type IS NOT NULL');
        $q->setParameter('id', $last_id);
        $q->setParameter('aws', 1);
        $q->addOrderBy(AdImageRepository::ALIAS.'.id');
        $q->setMaxResults($QUERY_BATCH_SIZE);
        $q->setFirstResult(0);

        if ($update_type != '') {
            if ($update_type =='blank_hash') {
                $q->andWhere(AdImageRepository::ALIAS.'.hash = :hash');
                $q->setParameter('hash', '');
            } else {
                $q->andWhere(AdImageRepository::ALIAS.'.update_type LIKE :update_type');
                $q->setParameter('update_type', $update_type);
            }
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

    /**
     * FetchData from url.
     *
     * @param string  $url    Url.
     * @param string  $source Source file name.
     * @param boolean $binary Download as binary.
     */
    public function writeDataFromURL($url, $source, $binary = false)
    {
        $ch = curl_init($url);
        if ($binary == true) {
            $fp = fopen($source, 'wb');
        } else {
            $fp = fopen($source, 'w+');
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        if ($binary == true) {
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        }

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }
}
