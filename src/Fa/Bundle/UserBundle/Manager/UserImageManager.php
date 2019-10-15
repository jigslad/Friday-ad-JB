<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\ThumbnailManager;
use Fa\Bundle\AdBundle\Listener\AdListener;
use Gedmo\Sluggable\Util\Urlizer;
use Aws\S3\S3Client;
use \Exception;
use Fa\Bundle\UserBundle\Entity\UserImage;
use Fa\Bundle\CoreBundle\Manager\AmazonS3ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
/**
 * Ad image manager.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserImageManager
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager object.
     *
     * @var object
     */
    private $em;

    /**
     * User id.
     *
     * @var integer $userId
     */
    protected $userId = null;

    /**
     * Is user image or company logo.
     *
     * @var boolean
     */
    protected $isCompany = false;

    /**
     * Original image path.
     *
     * @var string
     */
    protected $orgImagePath;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param integer            $userId
     * @param string             $orgImagePath
     * @param boolean            $isCompany
     */
    public function __construct(ContainerInterface $container, $userId, $orgImagePath, $isCompany = false)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();

        $this->setUserId($userId);
        $this->setIsCompany($isCompany);
        $this->setOrgImagePath($orgImagePath);
    }

    /**
     * Set user id.
     *
     * @param integer $userId User id.
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Get user id.
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set is company.
     *
     * @param boolean $isCompany
     */
    public function setIsCompany($isCompany)
    {
        $this->isCompany = $isCompany;
    }

    /**
     * Get is company.
     *
     * @return boolean
     */
    public function getIsCompany()
    {
        return $this->isCompany;
    }

    /**
     * Set org iImage path.
     *
     * @param string $orgImagePath
     */
    public function setOrgImagePath($orgImagePath)
    {
        $this->orgImagePath = $orgImagePath;
    }

    /**
     * Get Org image path.
     *
     * @return string
     */
    public function getOrgImagePath()
    {
        return $this->orgImagePath;
    }

    /**
     * Save original jpg from uploaded image.
     *
     * @param string $orgImageName Original image name.
     */
    public function saveOriginalJpgImage($orgImageName)
    {
        $imageQuality = $this->container->getParameter('fa.image.quality');
        //convert original image to jpg
        $dimension = getimagesize($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
        if ($dimension['mime'] == 'image/png') {
            exec('convert -flatten '.escapeshellarg($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName).' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.png');
            exec('convert '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.png '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg');
            if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.png')) {
                unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.png');
            }
        } else {
            $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, $imageQuality, 'ImageMagickManager');
            $origImage->loadFile($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
            $origImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg', 'image/jpeg');
        }

        if ($dimension['mime'] == 'image/gif') {
            if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original-0.jpg')) {
                rename($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original-0.jpg', $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg');
            }

            passthru('rm '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original-*.jpg 2> /dev/null');
        }
        exec('convert '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg -resize 290x218 -background white -gravity center -extent 290x218 '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'.jpg');
        copy($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'.jpg', $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_org.jpg');
        if ($orgImageName != $this->getUserId().'.jpg' && $orgImageName != $this->getUserId().'_original.jpg') {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
        }
    }

    /**
     * Create required thumbnails.
     *
     * @throws sfException
     */
    public function createThumbnail()
    {
        $imageQuality = $this->container->getParameter('fa.image.quality');
        if ($this->getIsCompany()) {
            $thumbSize = $this->container->getParameter('fa.company.image_size');
        } else {
            $thumbSize = $this->container->getParameter('fa.user.image_size');
        }

        $thumbSize = array_map('strtoupper', $thumbSize);

        if (is_array($thumbSize)) {
            // zoom image from center and convert to big image
            $orgImage = $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'.jpg';
            try {
                foreach ($thumbSize as $d) {
                    $dim        = explode('X', $d);
                    $thumbImage = new ThumbnailManager($dim[0], $dim[1], true, false, $imageQuality, 'ImageMagickManager');
                    $thumbImage->loadFile($orgImage);
                    $thumbImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'.jpg', 'image/jpeg');
                    unset($thumbImage);
                }
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            throw new \Exception('Please set the thumbnail dimension in configuration file');
        }
    }

    /**
     * Remove user image or company logo.
     *
     */
    public function removeImage()
    {
        if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'.jpg')) {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'.jpg');
        }
        if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_org.jpg')) {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_org.jpg');
        }
        if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg')) {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg');
        }
    }

    /*
    public function uploadImagesToS3($image)
    {
        $em = $this->container->get('doctrine')->getManager();
        if ($image->getAd()) {
            $client =  $this->container->get('platinum_pixs_aws.base');
            $client = $client->get('S3');

            $webPath = $this->container->get('kernel')->getRootDir().'/../web';

            $thumbSize = $this->container->getParameter('fa.image.thumb_size');
            $thumbSize = array_map('strtoupper', $thumbSize);

            $images = array();

            foreach ($thumbSize as $d) {
                $sourceImg = $this->getOrgImagePath().DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$image->getHash().'_'.$d.'.jpg';

                if (file_exists($sourceImg)) {
                    $images[$d] = $sourceImg;
                }
            }

            $sourceImg = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$image->getHash().'.jpg';

            if (file_exists($sourceImg)) {
                $images[''] = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$image->getHash().'.jpg';
            }

            foreach ($images as $key => $im) {
                $size = $key != '' ? '_'.$key : null;

                $result = $client->putObject(array(
                    'Bucket'     => $this->container->getParameter('fa.aws_bucket'),
                    'Key'        => $image->getPath().'/'.$image->getAd()->getId().'_'.$image->getHash().$size.'.jpg',
                    'SourceFile' => $im,
                    'Metadata'   => array(
                        'Last-Modified' => time(),
                    )
                ));
            }

            $image->setAws(1);
            $em->persist($image);
            $em->flush();
            $adListner = new AdListener($this->container);
            $adListner->handleSolr($image->getAd());
        }
    }*/
    
    /**
     * Receives the input image object and uploads to S3.
     * @param UploadedFile $uploadedFile
     * @param string       $imageName
     * @return string
     */
    public function uploadImageDirectlyToS3($uploadedFile, $imageName)
    {
        // no need to generate thumbnail here. Since Amazon Lambda function is written to handle the same.
        $objAS3IM = AmazonS3ImageManager::getInstance($this->container);
        return $objAS3IM->uploadImageToS3($uploadedFile->getRealPath(), $this->getUserImageDestination($imageName));
    }
    
    /** 
     * Get the destination file path where the ia
     * @param string $imageName
     * @return string
     */
    private function getUserImageDestination($imageName)
    {
        return $this->getOrgImagePath() . DIRECTORY_SEPARATOR . $imageName . '.jpg';
    }
}
