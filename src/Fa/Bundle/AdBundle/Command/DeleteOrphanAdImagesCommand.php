<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2019, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\AmazonS3ImageManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * This command is used to generate entity cache.
 *
 * php bin/console fa:delete-orphan-ad-images --curr_dir="17440201_17440300"
 *
 * @property string
 * @author     Akash M. Pai <makashpai@gmail.com, akashmpai@gmail.com>
 * @copyright  2019 Friday Media Tech India Pvt. Ltd.
 */
class DeleteOrphanAdImagesCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var string
     */
    private $targetImageDir;

    /**
     * @var string
     */
    private $curr_dir;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this->setName('fa:delete-orphan-ad-images')
            ->setDescription('Delete orphaned ad images from NFS');

        $this->addOption('curr_dir', null, InputOption::VALUE_OPTIONAL, 'Directory', null);

    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->output =& $output;
        $this->input =& $input;

        if (!$this->setTargetDir($input)) {
            $output->writeln("Invalid directory.");
            return false;
        }

        echo "Command Started At: " . date('Y-m-d H:i:s', time()) . "\n";

        $this->targetImageDir = getcwd(); // todo remove before committing
        $output->writeln($this->targetImageDir);

        $this->curr_dir = $input->getOption('curr_dir');

        $subDirs = scandir($this->targetImageDir);

        if (count($subDirs) == 2) { // to exclude ./ and ../ in the count.
            $output->writeln("Images dir empty");
            return false;
        }

        if (!empty($this->curr_dir)) {
            $output->writeln("curr_dir set");
            $this->processCurrDir($subDirs);
            $this->setNextDir($subDirs);
        } else {
            $output->writeln("curr_dir not set");
            $this->getFirstDir($subDirs);
        }
        $this->callMyself();

        echo "EOF";
        return true;
    }

    /**
     * Sets the absolute path of the current directory being processed.
     * @param InputInterface $input
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function setTargetDir($input)
    {
        if (!$input->hasOption('type')) {
            return false;
        }
        $type = "image";
        /*
        if (empty($type = $input->getOption('type'))) {
            return false;
        }
        */
        if (!in_array($type, ['image', 'user', 'usersite'])) {
            return false;
        }
        $this->targetImageDir = $this->getContainer()->get('kernel')->getRootDir() . DIRECTORY_SEPARATOR .
            ".." . DIRECTORY_SEPARATOR .
            "web" . DIRECTORY_SEPARATOR .
            "uploads" . DIRECTORY_SEPARATOR .
            "{$type}" . DIRECTORY_SEPARATOR;
        if (!is_dir($this->targetImageDir)) {
            return false;
        }
        return true;
    }

    /**
     * Get the contents of the curr directory and process each image further.
     * @param array $subDirs
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function processCurrDir(array $subDirs)
    {
        // get all files and check if the images are existing in the S3 bucket or if they exist in the DB in ad_image and in archive_ad_image

        if (!in_array($this->curr_dir, $subDirs)) {
            return false;
        }

        $imageData = $this->getImageData();

        if (empty($imageData)) {
            return false;
        }

        // hashes of thumbnails are obtained too. Remove them.
        $imageHashPairsUnique = array_column($imageData, 'ad_id', 'hash');

        $uniqueHashes = array_keys($imageHashPairsUnique);
        $resMatches = $this->getAdImages($uniqueHashes);

        if (!empty($resMatches)) {
            $this->processMatches($resMatches);
        } else {
            $this->output->writeln("No matches found in ad_image table.");
        }

        $resHashes = array_column($resMatches, 'hash');

        $resArcHashes = $this->seggregateOrphanedFiles($imageHashPairsUnique, $resHashes);
        $this->handleRemainingHashesDeletion($imageData, $resArcHashes);


        return true;
    }

    /**
     * @return array
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function getImageData()
    {
        $imageData = [];
        $tempCurrDir = $this->targetImageDir . $this->curr_dir;
        if (!is_dir($tempCurrDir)) {
            $this->output->writeln("$tempCurrDir is not a valid directory.");
            return $imageData;
        }

        $imagesList = scandir($tempCurrDir);
        if (!empty($imagesList)) {
            foreach ($imagesList as $valImg) {
                $fileName = $tempCurrDir . DIRECTORY_SEPARATOR . $valImg;
                if (is_file($fileName)) {
                    $valImgName = str_replace(".jpg", "", $valImg);
                    $filename = explode("_", $valImgName);
                    if (count($filename) == 3) {
                        unset($filename[2]);
                    } else if (count($filename) < 2) {
                        continue;
                    }
                    $imageData[$valImg] = ['ad_id' => $filename[0], 'hash' => $filename[1]];
                }
            }
        }
        return $imageData;
    }

    /**
     * Obtains the images from ad_images table.
     * @param $imageHashes
     * @return array|bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function getAdImages($imageHashes)
    {
        $resMatches = [];
        $strHash = "'" . implode("','", $imageHashes) . "'";
        $query = "select id, ad_id, hash, local, aws, image_name from ad_image where hash in ({$strHash});";
        try {
            $resMatches = $this->executeQuery($query, [], false);
        } catch (\Exception $e) {
            $this->output->writeln("Unable to get records");
            $this->output->writeln($e->getMessage());
        }
        return $resMatches;
    }

    /**
     * Used to execute a query or set of queries.
     * @param string $query
     * @param array  $valuesPrepared
     * @param bool   $executeOnly
     * @param bool   $flagFirst
     * @param bool   $flagFKC
     * @return array|bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     * @throws \Exception
     */
    protected function executeQuery($query, $valuesPrepared = [], $executeOnly = true, $flagFirst = false, $flagFKC = false)
    {
        $conn = $this->em->getConnection();
        if ($executeOnly) {
            $conn->beginTransaction();
        }
        try {
            if ($flagFKC) {
                $stmt = $conn->prepare("SET FOREIGN_KEY_CHECKS = 0;");
                $stmt->execute();
            }
            $stmt = $conn->prepare("{$query}");
            if (!empty($valuesPrepared)) {
                $stmt->execute($valuesPrepared);
            } else {
                $stmt->execute();
            }
            if ($flagFKC) {
                $stmt = $conn->prepare("SET FOREIGN_KEY_CHECKS = 1;");
                $stmt->execute();
            }
            if ($executeOnly) {
                $conn->commit();
                return true;
            } else {
                return $flagFirst ? reset($stmt->fetchAll()) : $stmt->fetchAll();
            }
        } catch (\Exception $e) {
            $errorMessage = "Exception in query.\n";
            $this->output->writeln($errorMessage);
            $this->output->writeln($e->getTraceAsString());
            $this->output->writeln("----------------------");
            $this->output->writeln($e->getMessage());
            if ($executeOnly) {
                $conn->rollBack();
            }
            return false;
        }
    }

    /**
     * @param array $imageHashPairsUnique
     * @param array $resHashes
     * @return array
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function seggregateOrphanedFiles($imageHashPairsUnique, $resHashes)
    {
        $orphanedHashPairs = array_diff_key($imageHashPairsUnique, array_flip($resHashes));
        $orphanedAdids = array_unique($orphanedHashPairs);

        $nonOrphanedHashes = $this->obtainNonOrphanedHashes($orphanedAdids, $orphanedHashPairs);

        $resArcHashes = $this->obtainArchiveAdHashMatches($nonOrphanedHashes);

        return $resArcHashes;
    }

    /**
     * @param array $orphanedAdids
     * @param array $orphanedHashPairs
     * @return array
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function obtainNonOrphanedHashes($orphanedAdids, $orphanedHashPairs)
    {
        $nonOrphanedHashPairs = [];
        if (!empty($orphanedAdids)) {
            try {
                // check if ad exists in ad table for the orphaned hash ad_ids
                $strOrphanIds = implode(",", $orphanedAdids);
                $qOrphanedAdIds = "select id from ad where id in ({$strOrphanIds})";
                $resAds = $this->executeQuery($qOrphanedAdIds, [], false);
                $resAdIds = array_column($resAds, 'id');
                $nonOrphanedHashPairs = array_diff($orphanedHashPairs, $resAdIds);
            } catch (\Exception $e) {
                $this->output->writeln($e->getMessage());
            }
        }

        return array_keys($nonOrphanedHashPairs);
    }

    /**
     * @param array $nonOrphanedHashes
     * @return array
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function obtainArchiveAdHashMatches($nonOrphanedHashes)
    {
        $resArcHashes = [];
        $strNOHashes = "'" . implode("','", $nonOrphanedHashes) . "'";
        $qArcHashes = "select id, hash from archive_ad_image where hash in ({$strNOHashes});";
        try {
            $resArcHashMatches = $this->executeQuery($qArcHashes, [], false);
            $resArcHashes = array_column($resArcHashMatches, 'hash');
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
        }
        return $resArcHashes;
    }

    /**
     * Delete remain images be checking whether it is archvied or orphaned image.
     * @param $imageData
     * @param $resArcHashes
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function handleRemainingHashesDeletion($imageData, $resArcHashes)
    {
        foreach ($imageData as $keyImageData => $valImageData) {
            if (in_array($valImageData['hash'], $resArcHashes)) {
                // Delete only thumbnails of non-orphaned images.
                // Meaning for archive-images original shouldn't be deleted. Rest images can be deleted.
                if (strpos($keyImageData, "800X600") !== false || strpos($keyImageData, "300X225")) {
                    $this->deleteImage($this->targetImageDir . $this->curr_dir . DIRECTORY_SEPARATOR . $keyImageData);
                }
            } else {
                // remaining orphaned images should be deleted.
                // since they are not available in ad_image nor archive_ad_image nor the ad_id is available in the ad table.
                $this->deleteImage($this->targetImageDir . $this->curr_dir . DIRECTORY_SEPARATOR . $keyImageData);
            }
        }
    }

    /**
     * Process the image entry matches obtained form the database.
     * @param $resMatches
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function processMatches($resMatches)
    {
        $fixedImages = [];
        $objAS3IM = AmazonS3ImageManager::getInstance($this->getContainer());
        if (empty($objAS3IM->s3Client)) {
            $this->output->writeln("Unable to instantiate S3Client.");
            return false;
        }
        $imageDir = "uploads" . DIRECTORY_SEPARATOR .
            "image" . DIRECTORY_SEPARATOR .
            $this->curr_dir;

        $bucketName = $this->getContainer()->getParameter('fa.aws_bucket');
        $s3Objects = $objAS3IM->listObjectsWithPattern($bucketName, $imageDir);

        foreach ($resMatches as $valMatch) {
            $imageName = $this->getImageName($imageDir, $valMatch);
            $this->processImage($objAS3IM, $s3Objects, $valMatch, $imageName);
        }

        return $this->updateFixedImages($fixedImages);
    }

    /**
     * Obtain the image name based on the data in table.
     * either adId_hash.jpg or adName-adId-order.jpg
     * @param $imageDir
     * @param $valMatch
     * @return string
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function getImageName($imageDir, $valMatch)
    {
        $imageName = $imageDir;
        if (!empty($valMatch['image_name'])) {
            $imageName .= $valMatch['image_name'] . ".jpg";
        } else {
            $imageName .= $valMatch['ad_id'] . "_" . $valMatch['hash'] . ".jpg";
        }
        return $imageName;
    }

    /**
     * Checks if the image is available in AWS. Upload if not exists. and delete if uploaded successful.
     * @param AmazonS3ImageManager $objAS3IM
     * @param array                $s3Objects
     * @param array                $valMatch
     * @param string               $imageName
     */
    private function processImage(AmazonS3ImageManager $objAS3IM, array $s3Objects, array $valMatch, $imageName)
    {
        $unlink = false;
        $imageFilepath = $this->getContainer()->get('kernel')->getRootDir() . DIRECTORY_SEPARATOR .
            ".." . DIRECTORY_SEPARATOR .
            "web" . DIRECTORY_SEPARATOR .
            $imageName;

        if ($this->checkImageExistOnAws($s3Objects, $imageName)) {
            $unlink = true;
        } else if ($objAS3IM->uploadImageToS3($imageFilepath, $imageName)) {
            $unlink = true;
        }

        if ($unlink) {
            if ($this->deleteImage($imageFilepath)) {
                array_push($fixedImages, $valMatch['id']);
            }
        }
    }

    /**
     * Check if the image in the specified path exists in aws.
     * @param array  $s3Objects
     * @param string $imageName
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function checkImageExistOnAws($s3Objects, $imageName)
    {
        return in_array($imageName, $s3Objects);
    }

    /**
     * Unlinks the image from local machine.
     * @param string $imageFilepath
     * @param string $from
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function deleteImage($imageFilepath, $from = "local")
    {
        if ($from == "local") {
            return unlink($imageFilepath);
        }
        return false;
    }

    /**
     * Update the aws and local flag of deleted images
     * @param array $fixedImages
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function updateFixedImages($fixedImages)
    {
        $strFixedIds = implode(",", $fixedImages);
        $qUpdateFixedImages = "UPDATE ad_image SET local = 0, aws = 1 WHERE id IN ({$strFixedIds});";
        try {
            $this->executeQuery($qUpdateFixedImages);
        } catch (\Exception $e) {
            $this->output->writeln("Unable to update the fixed images : {$strFixedIds}.");
            $this->output->writeln($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Sets the next directory to be used when calling myself next time.
     * @param array $subDirs
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function setNextDir(array $subDirs)
    {
        $keyCurrDir = array_search($this->curr_dir, $subDirs);

        $this->curr_dir = "";
        if ($keyCurrDir !== false) {
            if (array_key_exists($keyCurrDir + 1, $subDirs)) {
                $this->curr_dir = $subDirs[$keyCurrDir + 1];
            }
        }
    }

    /**
     * Set the directory to be processed while running the command initially.
     * @param array $subDirs
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function getFirstDir($subDirs)
    {
        $dirNotFound = true;
        foreach ($subDirs as $valSubDir) {
            if (is_dir($valSubDir)) {
                $dirNotFound = false;
                $this->output->writeln("found dir $valSubDir.");
                $this->curr_dir = $valSubDir;
                break;
            }
        }
        return !$dirNotFound;
    }

    /**
     * Call myself if the next directory is available.
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function callMyself()
    {
        $this->output->writeln("Calling myself.");
        $commandOptions = null;
        foreach ($this->input->getOptions() as $option => $value) {
            if ($value) {
                $commandOptions .= ' --' . $option . '="' . $value . '"';
            }
        }

        if (empty($this->curr_dir)) {
            return false;
        }

        $commandOptions .= ' --curr_dir=' . $this->curr_dir;

        $memoryLimit = '';
        if ($this->input->hasOption("memory_limit") && $this->input->getOption("memory_limit")) {
            $memoryLimit = ' -d memory_limit=' . $this->input->getOption("memory_limit");
        }

        $command = $this->getContainer()->getParameter('fa.php.path') . $memoryLimit . ' ' .
            $this->getContainer()->getParameter('project_path') . '/console' . ' ' .
            $this->getName() . ' ' .
            $commandOptions;

        $this->output->writeln($command, true);
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            $this->output->writeln('Error occurred during subtask', true);
            return false;
        }
        return true;
    }

}
