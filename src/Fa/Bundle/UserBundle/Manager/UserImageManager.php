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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Gedmo\Sluggable\Util\Urlizer;
use Aws\S3\S3Client;

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
        exec('convert -auto-orient '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.trim($orgImageName,"'"));

        exec('convert -flatten '.escapeshellarg($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName).' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName.'_original.jpg');

        if ($dimension['mime'] == 'image/png') {
            exec('convert -flatten '.escapeshellarg($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName).' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.png');
            exec('convert '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.png '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg');
            if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.png')) {
                unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.png');
            }
        } else {
             exec('convert -flatten '.escapeshellarg($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName).' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg');
            //$origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, $imageQuality, 'ImageMagickManager');
            //$origImage->loadFile($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
            //$origImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'_original.jpg', 'image/jpeg');
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
                    exec('convert -auto-orient -define jpeg:size='.$dim[0].'x'.$dim[1].' '.$orgImage.' -thumbnail '.$d.' -gravity center -extent '.$d.' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'.jpg');
                    //$thumbImage = new ThumbnailManager($dim[0], $dim[1], true, false, $imageQuality, 'ImageMagickManager');
                    //$thumbImage->loadFile($orgImage);
                    //$thumbImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getUserId().'.jpg', 'image/jpeg');
                    //unset($thumbImage);
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
        try {
            $this->removeFromAmazonS3();
        } catch (\Exception $e) {
        }
    }

    public function removeFromAmazonS3()
    {
        $client = new S3Client([
            'version'     => 'latest',
            'region'      => $this->container->getParameter('fa.aws_region'),
            'credentials' => [
                'key'    => $this->container->getParameter('fa.aws_key'),
                'secret' => $this->container->getParameter('fa.aws_secret'),
            ],
        ]);
        
       if($this->isCompany) {
            $imageFolder = $this->container->getParameter('fa.company.image.dir');
        } else {
            $imageFolder = $this->container->getParameter('fa.user.image.dir');
        }
         
        $awsPath = $this->container->getParameter('fa.static.aws.url');
        $imageDir = CommonManager::getGroupDirNameById($this->getUserId(),5000);
        $awsSourceImg = $awsPath.'/'.$imageFolder.'/'.$imageDir.'/'.$this->getUserId().'.jpg';
        
        $images = $fileKeys = array(); 
        
        if (false!==file($awsSourceImg)) {
            $images[''] = $imageFolder.'/'.$imageDir.'/'.$this->getUserId().'.jpg';
            $images['org'] = $imageFolder.'/'.$imageDir.'/'.$this->getUserId().'_org.jpg';
            $images['original'] = $imageFolder.'/'.$imageDir.'/'.$this->getUserId().'_original.jpg';       
        }
        
        foreach ($images as $key => $im) {          
            $fileKeys[] = array('Key' => $im);
        }
        
        if(!empty($fileKeys)) {
            $result = $client->deleteObjects(array(
                'Bucket'  => $this->container->getParameter('fa.aws_bucket'),
                'Delete'  => array('Objects' => $fileKeys)
            ));
        }
    }
    
    public function uploadImagesToS3($id,$image_type)
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
            
            $webPath = $this->container->get('kernel')->getRootDir().'/../web/uploads/'.$image_type.'/';
            $imageDir = CommonManager::getGroupDirNameById($id,5000);
            $imagePath  = $webPath.$imageDir;
                        
            $images = array();            
                                  
            $sourceImg = $imagePath.'/'.$id.'.jpg';
            
            if (file_exists($sourceImg)) {
                $images[''] = $imagePath.'/'.$id.'.jpg';
                $images['org'] = $imagePath.'/'.$id.'_org.jpg';
                $images['original'] = $imagePath.'/'.$id.'_original.jpg';
            }
            
            foreach ($images as $key => $im) {                               
                $imagekey = '';
                if ($key!='') {
                    $imagekey = 'uploads/'.$image_type.'/'.$imageDir.'/'.$id.'_'.$key.'.jpg';                    
                } else {
                    $imagekey = 'uploads/'.$image_type.'/'.$imageDir.'/'.$id.'.jpg';
                }
                
                if ($this->container->getParameter('fa.aws_bucket') == $this->container->getParameter('fa.aws_bucket_compare')) {
                    $result = $client->putObject(array(
                        'Bucket'     => $this->container->getParameter('fa.aws_bucket'),
                        'Key'        => $imagekey,
                        'CacheControl' => 'max-age=31536000',
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
                        'CacheControl' => 'max-age=31536000',
                        'ACL'        => 'public-read',
                        'SourceFile' => $im,
                        'Metadata'   => array(
                            'Last-Modified' => time(),
                        )
                    ));
                }
                
                $resultData =  $result->get('@metadata');
                
                if ($resultData['statusCode'] == 200) {
                    //echo 'Moved File to AWS is Successfull ## '.$imagekey ;
                    unlink($im);
                } else {
                    //echo 'Failed moving to AWS ## '.$imagekey ;
                }
            }
        }
    }
}
