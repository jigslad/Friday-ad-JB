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
use Fa\Bundle\UserBundle\Repository\UserImageRepository;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:download:feed download  --type="gun" --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DownloadOldUserImagesCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:download:old-user-images')
        ->setDescription('Download user images from old site')
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

        $imageDir = '/var/media/data/olduserimages';

        $QUERY_BATCH_SIZE = 1000;
        $done          = false;
        $last_id      = 0;
        $update_type   = $input->getOption('update_type');

        while (!$done) {
            $images = $this->getImages($last_id, $QUERY_BATCH_SIZE, $update_type);
            if ($images) {
                foreach ($images as $image) {
                    if ($image->getOldPathOrg() != "") {
                        if (!file_exists($imageDir.'/'.basename($image->getOldPathOrg()))) {
                            if ($this->checkFileExistsOnUrl($image->getOldPathOrg()) == '200') {
                                $this->writeDataFromURL($image->getOldPathOrg(), $imageDir.'/'.basename($image->getOldPathOrg()), true);
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $mime_type = finfo_file($finfo, $imageDir.'/'.basename($image->getOldPathOrg()));

                                if (strpos($mime_type, 'image') !== false) {
                                    echo 'Image ORG downloaded successfully '.$imageDir.'/'.basename($image->getOldPathOrg())."\n";
                                } else {
                                    if (basename($image->getOldPathOrg()) != '') {
                                        unlink($imageDir.'/'.basename($image->getOldPathOrg()));
                                    }
                                    echo 'Removed ORG downloaded non image file '.$imageDir.'/'.basename($image->getOldPathOrg())."\n";
                                }
                            } else {
                                echo ".";
                            }
                        } else {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime_type = finfo_file($finfo, $imageDir.'/'.basename($image->getOldPathOrg()));

                            if (strpos($mime_type, 'image') !== false) {
                                echo "Already Downloaded ORG :".$imageDir.'/'.basename($image->getOldPathOrg())."\n";
                            } else {
                                if (basename($image->getOldPathOrg()) != '') {
                                    unlink($imageDir.'/'.basename($image->getOldPathOrg()));
                                    echo 'Removed already exists ORG non image file '.$imageDir.'/'.basename($image->getOldPathOrg())."\n";
                                }
                            }
                        }
                    }


                    if (!file_exists($imageDir.'/'.basename($image->getOldPath()))) {
                        if ($this->checkFileExistsOnUrl($image->getOldPath()) == '200') {
                            $this->writeDataFromURL($image->getOldPath(), $imageDir.'/'.basename($image->getOldPath()), true);
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime_type = finfo_file($finfo, $imageDir.'/'.basename($image->getOldPath()));

                            if (strpos($mime_type, 'image') !== false) {
                                echo 'Image downloaded successfully '.$imageDir.'/'.basename($image->getOldPath())."\n";
                            } else {
                                if (basename($image->getOldPath()) != '') {
                                    unlink($imageDir.'/'.basename($image->getOldPath()));
                                    echo 'Removed downloaded non image file '.$imageDir.'/'.basename($image->getOldPath())."\n";
                                }
                            }
                        } else {
                            echo ".";
                        }
                    } else {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $imageDir.'/'.basename($image->getOldPath()));

                        if (strpos($mime_type, 'image') !== false) {
                            echo "Already Downloaded :".$imageDir.'/'.basename($image->getOldPath())."\n";
                        } else {
                            if (basename($image->getOldPath()) != '') {
                                unlink($imageDir.'/'.basename($image->getOldPath()));
                                echo 'Removed already exists non image file '.$imageDir.'/'.basename($image->getOldPath())."\n";
                            }
                        }
                    }
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
     * @param integer $last_id
     * @param integer $QUERY_BATCH_SIZE
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

    public function checkFileExistsOnUrl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $httpCode;
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
