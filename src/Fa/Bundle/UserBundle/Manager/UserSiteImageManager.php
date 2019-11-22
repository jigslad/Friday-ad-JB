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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\UserSiteImageRepository;
use Gedmo\Sluggable\Util\Urlizer;
use Aws\S3\S3Client;

/**
 * User site image manager.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserSiteImageManager
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
     * Id of user site.
     *
     * @var stirng $userSiteId
     */
    protected $userSiteId = null;

    /**
     * Hash to use for image name.
     *
     * @var string
     */
    protected $hash = null;

    /**
     * Original image path.
     *
     * @var string
     */
    protected $orgImagePath;

    /**
     * Id of the user site.
     *
     * @param string $userSiteId Id of user site.
     */
    public function setUserSiteId($userSiteId)
    {
        $this->userSiteId = $userSiteId;
    }

    /**
     * Get ad id.
     *
     * @return AdImageType
     */
    public function getUserSiteId()
    {
        return $this->userSiteId;
    }

    /**
     * Set hash.
     *
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Get hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
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
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param integer            $userSiteId
     * @param integer            $hash
     * @param string             $orgImagePath
     */
    public function __construct(ContainerInterface $container, $userSiteId, $hash, $orgImagePath)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->setUserSiteId($userSiteId);
        $this->setHash($hash);
        $this->setOrgImagePath($orgImagePath);
    }


    /**
     * Save original jpg from uploaded image.
     *
     * @param string $orgImageName Original image name.
     */
    public function saveOriginalJpgImage($orgImageName, $keepOriginal = false)
    {
        $imageQuality = $this->container->getParameter('fa.image.quality');
        $dimension = getimagesize($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
        //convert original image to jpg
        if ($dimension['mime'] == 'image/png') {
            exec('convert -flatten '.escapeshellarg($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName).' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.png');
            exec('convert '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.png '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.jpg');
            if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.png')) {
                unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.png');
            }
        } else {
            $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, $imageQuality, 'ImageMagickManager');
            $origImage->loadFile($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
            $origImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.jpg', 'image/jpeg');
        }
        if ($dimension['mime'] == 'image/gif') {
            if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'-0.jpg')) {
                rename($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'-0.jpg', $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'.jpg');
            }

            passthru('rm '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'-*.jpg 2> /dev/null');
        }
        if (!$keepOriginal && is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName)) {
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
        $thumbSize = $this->container->getParameter('fa.image.user.site.thumb_size');
        $thumbSize = array_map('strtoupper', $thumbSize);
        $imageQuality = $this->container->getParameter('fa.image.quality');
        
        if (is_array($thumbSize)) {
            $orig_image = $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.jpg';
            // zoom image from center and convert to big image
            $dimension    = getimagesize($orig_image);
            $thumbImgSize = $thumbSize[0];
            exec('convert -define jpeg:size='.$dimension[0].'x'.$dimension[1].' '.$orig_image.' -thumbnail '.$thumbImgSize.'^ \
                     -gravity center -extent '.$thumbImgSize.' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'_'.$thumbImgSize.'.jpg');
            unset($thumbSize[0]);
            $orig_image = $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.jpg';

            try {
                foreach ($thumbSize as $d) {
                    $dim        = explode('X', $d);

                    $thumbImage = new ThumbnailManager($dim[0], $dim[1], true, false, $imageQuality, 'ImageMagickManager');
                    $thumbImage->loadFile($orig_image);
                    $thumbImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'_'.$d.'.jpg', 'image/jpeg');

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
     * Remove image.
     *
     * @param boolean $keepOriginal Flag for keep original image.
     */
    public function removeImage($keepOriginal = false)
    {
        //remove original file
        if (!$keepOriginal && is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.jpg')) {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'.jpg');
        }

        //remove thumbnail
        $thumbSize = $this->container->getParameter('fa.image.thumb_size');
        $thumbSize = array_map('strtoupper', $thumbSize);

        if (is_array($thumbSize)) {
            foreach ($thumbSize as $size) {
                if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'_'.$size.'.jpg')) {
                    unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'_'.$size.'.jpg');
                }
            }
        }

        $cropSize = $this->container->getParameter('fa.image.crop_size');
        $cropSize = array_map('strtoupper', $cropSize);

        //remove brand image
        if (is_array($cropSize)) {
            foreach ($cropSize as $size) {
                if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'_'.$size.'_c.jpg')) {
                    unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserSiteId().'_'.$this->getHash().'_'.$size.'_c.jpg');
                }
            }
        }
    }
    
    public function uploadImagesToS3($id)
    {
        $em = $this->container->get('doctrine')->getManager();
        if ($id) {
            $client = new S3Client([
                'version'     => 'latest',
                'region'      => $this->container->getParameter('fa.aws_region'),
                'credentials' => [
                    'key'    => $this->container->getParameter('fa.aws_key'),
                    'secret' => $this->container->getParameter('fa.aws_secret'),
                ],
            ]);
            
            $webPath = $this->container->get('kernel')->getRootDir().'/../web/'.$this->container->getParameter('fa.user.site.image.dir').'/';
            $thumbSize = $this->container->getParameter('fa.image.user.site.thumb_size');
            $imageDir = CommonManager::getGroupDirNameById($id);
            $imagePath  = $webPath.$imageDir;
            
            $images = array();
            
            $userSiteImagesById = $this->em->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImages($id);
            
            echo '<pre>'; print_r($userSiteImagesById);die;
            
            $sourceImg = $imagePath.'/'.$id.'.jpg';
            
            if (file_exists($sourceImg)) {
                $images[''] = $imagePath.'/'.$id.'.jpg';
                $images['org'] = $imagePath.'/'.$id.'_org.jpg';
                $images['original'] = $imagePath.'/'.$id.'_original.jpg';
            }
            
            foreach ($images as $key => $im) {
                $imagekey = '';
                if ($key!='') {
                    $imagekey = $this->container->getParameter('fa.user.site.image.dir').'/'.$imageDir.'/'.$id.'_'.$key.'.jpg';
                } else {
                    $imagekey = $this->container->getParameter('fa.user.site.image.dir').'/'.$imageDir.'/'.$id.'.jpg';
                }
                
                if ($this->container->getParameter('fa.aws_bucket') == $this->container->getParameter('fa.aws_bucket_compare')) {
                    $result = $client->putObject(array(
                        'Bucket'     => $this->container->getParameter('fa.aws_bucket'),
                        'Key'        => $imagekey,
                        'CacheControl' => 'max-age=21600',
                        'ACL'        => 'public-read',
                        'SourceFile' => $im,
                        'Metadata'   => array(
                            'Last-Modified' => time(),
                        )
                    ));
                } else {
                    $result = $client->putObject(array(
                        'Bucket'     => $this->container->getParameter('fa.aws_bucket'),
                        'Key'        => $imagekey,
                        'CacheControl' => 'max-age=21600',
                        'SourceFile' => $im,
                        'Metadata'   => array(
                            'Last-Modified' => time(),
                        )
                    ));
                }
                
                $resultData =  $result->get('@metadata');
                
                if ($resultData['statusCode'] == 200) {
                    echo 'Moved File to AWS is Successfull ## '.$imagekey ;
                    unlink($im);
                } else {
                    echo 'Failed moving to AWS ## '.$imagekey ;
                }
            }
        }
    }
}
