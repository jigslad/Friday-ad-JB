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
use Gedmo\Sluggable\Util\Urlizer;
use Aws\S3\S3Client;
/**
 * User site banner manager.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserSiteBannerManager
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
     * Set org Image path.
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
     * @param string             $orgImagePath
     */
    public function __construct(ContainerInterface $container, $userSiteId, $orgImagePath)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->setUserSiteId($userSiteId);
        $this->setOrgImagePath($orgImagePath);
    }

    /**
     * Assign default banner.
     *
     * @param object $userSiteBanner      User site banner obj.
     * @param string $siteBannerImagePath User site image path.
     *
     */
    public function assignDefaultCategoryBanner($userSiteBanner, $siteBannerImagePath)
    {
        $imageQuality = $this->container->getParameter('fa.image.quality');
        if ($userSiteBanner) {
            $this->removeImage();
            $dimension = getimagesize($siteBannerImagePath.DIRECTORY_SEPARATOR.$userSiteBanner->getFilename());
            //convert original image to jpg
            $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, $imageQuality, 'ImageMagickManager');
            $origImage->loadFile($siteBannerImagePath.DIRECTORY_SEPARATOR.$userSiteBanner->getFilename());
            $origImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'.jpg', 'image/jpeg');
            copy($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'.jpg', $this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'_org.jpg');
            exec('convert -rotate 0 -resize 100% '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'_org.jpg'.' -crop 1190x400+0+'.($dimension[1]*45/100).' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'.jpg');
        }
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
        $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, $imageQuality, 'ImageMagickManager');
        $origImage->loadFile($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
        $origImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'.jpg', 'image/jpeg');
        if ($dimension['mime'] == 'image/gif') {
            if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'-0.jpg')) {
                rename($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'-0.jpg', $this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'.jpg');
            }

            passthru('rm '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'-*.jpg 2> /dev/null');
        }

        copy($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'.jpg', $this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'_org.jpg');

        if (!$keepOriginal && is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName)) {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
        }
    }

    /**
     * Remove image.
     *
     * @param boolean $keepOriginal Flag for keep original image.
     */
    public function removeImage($keepOriginal = false)
    {
        //remove original banner
        if (!$keepOriginal && is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'_org.jpg')) {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'_org.jpg');
        }
        //remove banner
        if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'.jpg')) {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.'banner_'.$this->getUserSiteId().'.jpg');
        }
        removeFromAmazonS3();
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
        

        $imageFolder = $this->container->getParameter('fa.user.site.image.dir');
                
        $awsPath = $this->container->getParameter('fa.static.aws.url');
        $imageDir = CommonManager::getGroupDirNameById($this->getUserSiteId());
        $awsSourceImg = $awsPath.'/'.$imageFolder.'/'.$imageDir.'/banner_'.$this->getUserSiteId().'.jpg';
        
        $images = $fileKeys = array();
        
        if (false!==file($awsSourceImg)) {
            $images[''] = $imageFolder.'/'.$imageDir.'/banner_'.$this->getUserSiteId().'.jpg';
            $images['org'] = $imageFolder.'/'.$imageDir.'/banner_'.$this->getUserSiteId().'_org.jpg';
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
            $imageDir = CommonManager::getGroupDirNameById($id);
            $imagePath  = $webPath.$imageDir;
            
            $images = array();

            
            $sourceImg = $imagePath.'/banner_'.$id.'.jpg';
            
            if (file_exists($sourceImg)) {
                $images[''] = $imagePath.'/banner_'.$id.'.jpg';
                $images['org'] = $imagePath.'/banner_'.$id.'_org.jpg';
            }
            
            foreach ($images as $key => $im) {
                $imagekey = '';
                if ($key!='') {
                    $imagekey = $this->container->getParameter('fa.user.site.image.dir').'/'.$imageDir.'/banner_'.$id.'_'.$key.'.jpg';
                } else {
                    $imagekey = $this->container->getParameter('fa.user.site.image.dir').'/'.$imageDir.'/banner_'.$id.'.jpg';
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
