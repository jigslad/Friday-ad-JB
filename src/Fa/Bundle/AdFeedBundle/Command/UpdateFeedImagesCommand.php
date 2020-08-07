<?php
/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2020, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\AdBundle\Entity\AdImage;
use Gedmo\Sluggable\Util\Urlizer;

/**
 * This command is used to update images of feed ad.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2020 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateFeedImagesCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 10;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
            ->setName('fa:update:feed-ad-images')
            ->setDescription("Update feed ad images.")
            ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
            ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad IDs', null)
            ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'User IDs', null)
            ->addOption('date_from', null, InputOption::VALUE_OPTIONAL, 'Date From', null)
            ->addOption('date_to', null, InputOption::VALUE_OPTIONAL, 'Date To', null)
            ->setHelp(
                <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to Update feed ad images.

Command:
 - php bin/console fa:update:feed-ad-images
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
        // set entity manager.
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();

        //get arguments passed in command
        $offset = $input->getOption('offset');

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        }

        if (isset($offset)) {
            $this->UpdateFeedAdImagesWithOffset($input, $output);
        } else {
            $output->writeln('Total entries to process: '.$this->getAdCount($input, $output), true);
            $this->UpdateFeedAdImages($input, $output);
        }

        if (!isset($offset)) {
            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
    }

    /**
     * Execute raw query.
     *
     * @param string  $sql           Sql query to run.
     * @param object  $entityManager Entity manager.
     *
     * @return object
     */
    private function executeRawQuery($sql, $entityManager)
    {
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Update total ad count.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function UpdateFeedAdImages($input, $output)
    {
        $count  = $this->getAdCount($input, $output);

        for ($i = 0; $i < $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:feed-ad-images '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update feed ads with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function UpdateFeedAdImagesWithOffset($input, $output)
    {
        $offset =0;
        if($input->getOption('offset')) { $offset = $input->getOption('offset'); } else { $offset =0; }
        $getAdResults  = $this->getAdResult($input, $output, $offset, $this->limit);
        $adImageDir = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/image/';

        foreach ($getAdResults as $adfeed) {
            $adId = $adfeed['ad_id'];
            $imagePath  = $adImageDir.CommonManager::getGroupDirNameById($adId);

            $adText = unserialize($adfeed['ad_text']);
            $imgCount = (isset($adText['image_count']))?$adText['image_count']:0;
            if($imgCount>0) {
                $imageData = (isset($adText['images'])) ? $adText['images'] : array();
                for($i=1; $i<$imgCount; $i++) {
                    if(isset($imageData[$i]) && isset($imageData[$i]['local_path'])) {
                        $filePath = $this->getContainer()->getParameter('fa.feed.data.dir').'/images/'.$imageData[$i]['local_path'];
                        $dimension = @getimagesize($filePath);
                    }
                    if (file_exists($filePath) && $dimension) {
                        $hash = CommonManager::generateHash();
                        CommonManager::createGroupDirectory($adImageDir, $adId);

                        $ad = $this->entityManager->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId));
                        $image = $this->entityManager->getRepository('FaAdBundle:AdImage')->findOneBy(array('ad' => $adId, 'ord' => $i));
                        if ($image) {
                            $image->setHash($hash);
                        } else {
                            $image = new AdImage();
                            $image->setHash($hash);
                        }
                        $image->setPath('uploads/image/' . CommonManager::getGroupDirNameById($adId));
                        $image->setOrd($i);
                        $image->setAd($ad);
                        $image->setStatus(1);
                        $image->setImageName(Urlizer::urlize($ad->getTitle() . '-' . $adId . '-' . $i));
                        $image->setAws(0);
                        $this->entityManager->persist($image);

                        exec('convert -flatten ' . escapeshellarg($filePath) . ' ' . $imagePath . '/' . $adId . '_' . $hash . '.jpg');

                        $adImageManager = new AdImageManager($this->getContainer(), $adId, $hash, $imagePath);
                        $adImageManager->createThumbnail();
                        $adImageManager->createCropedThumbnail();

                        $adImgPath = $imagePath . '/' . $ad->getId() . '_' . $hash . '.jpg';

                        if (file_exists($adImgPath)) {
                            $adImageManager->uploadImagesToS3($image);
                            $output->writeln('Ad feed image updated : '.$adImgPath, true);
                            //unlink($filePath);
                        }
                    }
                }
            }
        }

    }

    /**
     * Get query builder for ads.
     *
     * @return count
     */
    protected function getAdCount($input, $output)
    {
        $whereSql = '';

        if ($input->getOption('ad_id')) {
            $whereSql .= " AND af.ad_id IN (".$input->getOption('ad_id').")";
        }
        if ($input->getOption('user_id')) {
            $whereSql .= " AND af.user_id IN (".$input->getOption('user_id').")";
        }
        if ($input->getOption('date_from')) {
            if($input->getOption('date_to')) {
                $whereSql .= " AND (af.last_modified >= '".$input->getOption('date_from')." 00:00:00' AND af.last_modified <= '".$input->getOption('date_to')." 23:59:59')";
            } else {
                $whereSql .= " AND (af.last_modified >= '".$input->getOption('date_from')." 00:00:00')";
            }
        }

        $sql = "SELECT COUNT(af.id) as total FROM  `ad_feed` af WHERE status='A' and ad_id !=''".$whereSql;

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $countRes = $stmt->fetch();

        return $countRes['total'];
    }

    /**
     * Get ad count results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdResult($input, $output, $offset, $limit)
    {
        $whereSql = '';
        if ($input->getOption('ad_id')) {
            $whereSql .= " AND af.ad_id IN (".$input->getOption('ad_id').")";
        }
        if ($input->getOption('user_id')) {
            $whereSql .= " AND af.user_id IN (".$input->getOption('user_id').")";
        }
        if ($input->getOption('date_from')) {
            if($input->getOption('date_to')) {
                $whereSql .= " AND (af.last_modified >= '".$input->getOption('date_from')." 00:00:00' AND af.last_modified <= '".$input->getOption('date_to')." 23:59:59')";
            } else {
                $whereSql .= " AND (af.last_modified >= '".$input->getOption('date_from')." 00:00:00')";
            }
        }

        $sql = "SELECT * FROM  `ad_feed` af WHERE status='A' and ad_id !=''".$whereSql." LIMIT ".$limit." OFFSET ".$offset;


        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
