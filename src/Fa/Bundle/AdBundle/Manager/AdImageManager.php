<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Manager;

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
class AdImageManager
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
     * Id of ad.
     *
     * @var stirng $adId
     */
    protected $adId = null;

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
     * Id of the ad.
     *
     * @param string $adId Id of ad.
     */
    public function setAdId($adId)
    {
        $this->adId = $adId;
    }

    /**
     * Get ad id.
     *
     * @return AdImageType
     */
    public function getAdId()
    {
        return $this->adId;
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
     * Get image_name.
     *
     * @return AdImageType
     */
    public function getImageName()
    {
        return $this->image_name;
    }

    /**
     * Set image_name.
     *
     * @param string $image_name
     */
    public function setImageName($image_name)
    {
        $this->image_name = $image_name;
    }

    /**
     * Get hash.
     *
     * @return AdImageType
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
     * Set Image path.
     *
     * @param string $imagePath
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * Get Org image path.
     *
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param integer            $adId
     * @param integer            $hash
     * @param string             $orgImagePath
     * @param string             $imageName
     */
    public function __construct(ContainerInterface $container, $adId, $hash, $orgImagePath, $imageName = null, $imagePath = null)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->setAdId($adId);
        $this->setHash($hash);
        $this->setOrgImagePath($orgImagePath);
        $this->setImageName($imageName);
        $this->setImagePath($imagePath);
    }


    /**
     * Save original jpg from uploaded image.
     *
     * @param string $orgImageName Original image name.
     */
    public function saveOriginalJpgImage($orgImageName, $keepOriginal = false)
    {
        $dimension = getimagesize($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
        $imageQuality = $this->container->getParameter('fa.image.quality');
        
        exec('convert -auto-orient '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.trim($orgImageName,"'"));
        
        //convert original image to jpg
        if ($dimension['mime'] == 'image/png') {
            exec('convert -flatten '.escapeshellarg($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName).' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.png');
            exec('convert '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.png '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.jpg');
            if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.png')) {
                unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.png');
            }
        } else {
            $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, $imageQuality, 'ImageMagickManager');
            $origImage->loadFile($this->getOrgImagePath().DIRECTORY_SEPARATOR.$orgImageName);
            $origImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.jpg', 'image/jpeg');
        }
        //if image is animated gif, use first layer and remove other layers.
        if ($dimension['mime'] == 'image/gif') {
            if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'-0.jpg')) {
                rename($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'-0.jpg', $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.jpg');
            }

            passthru('rm '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'-*.jpg 2> /dev/null');
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
    public function createThumbnail($zoomFromCenter = true)
    {
        $thumbSize = $this->container->getParameter('fa.image.thumb_size');
        $thumbSize = array_map('strtoupper', $thumbSize);
        $imageQuality = $this->container->getParameter('fa.image.quality');
        if (is_array($thumbSize)) {
            // zoom image from center and convert to big image
            $orig_image = $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.jpg';

            // do auto crop during upload for 300X225 only
            if ($zoomFromCenter) {
                $dimension  = @getimagesize($orig_image);
                $bigImgSize = $thumbSize[0];
                exec('convert -auto-orient -define jpeg:size='.$dimension[0].'x'.$dimension[1].' '.$orig_image.' -thumbnail '.$bigImgSize.' -gravity center -extent '.$bigImgSize.' '.$this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'_'.$bigImgSize.'.jpg');
                unset($thumbSize[0]);
                $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'_'.$bigImgSize.'.jpg';
            }

            try {
                foreach ($thumbSize as $d) {
                    $dim        = explode('X', $d);

                    $thumbImage = new ThumbnailManager($dim[0], $dim[1], true, false, $imageQuality, 'ImageMagickManager');
                    $thumbImage->loadFile($orig_image);
                    $thumbImage->save($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'_'.$d.'.jpg', 'image/jpeg');

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
     * Create croped thumbnails.
     *
     * @throws Exception
     */
    public function createCropedThumbnail()
    {
        $cropSize = $this->container->getParameter('fa.image.crop_size');
        $cropSize = array_map('strtoupper', $cropSize);

        foreach ($cropSize as $value) {
            $org_size      = array();
            $double_width  = null;
            $double_height = null;
            $sourceImg    = $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.jpg';
            $destImg      = $this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'_'.$value.'_c.jpg';

            if (file_exists($sourceImg)) {
                $org_size = explode('X', $value);

                $double_width  = ($org_size[0] * 2);
                $double_height = ($org_size[1] * 2);

                $return = '';
                passthru('convert -auto-orient '.$sourceImg.' -resize x'.$double_height.' -resize "'.$double_width.'x<" -resize 50% -gravity center  -crop '.$value.'+0+0 +repage '.$destImg, $return);
            } else {
                throw new \Exception('Source image '.$sourceImg.' to generate croped image could not be found');
            }
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
        if (!$keepOriginal && is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.jpg')) {
            unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'.jpg');
        }

        //remove thumbnail
        $thumbSize = $this->container->getParameter('fa.image.thumb_size');
        $thumbSize = array_map('strtoupper', $thumbSize);

        if (is_array($thumbSize)) {
            foreach ($thumbSize as $size) {
                if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'_'.$size.'.jpg')) {
                    unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'_'.$size.'.jpg');
                }
            }
        }

        $cropSize = $this->container->getParameter('fa.image.crop_size');
        $cropSize = array_map('strtoupper', $cropSize);

        //remove brand image
        if (is_array($cropSize)) {
            foreach ($cropSize as $size) {
                if (is_file($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'_'.$size.'_c.jpg')) {
                    unlink($this->getOrgImagePath().DIRECTORY_SEPARATOR.$this->getAdId().'_'.$this->getHash().'_'.$size.'_c.jpg');
                }
            }
        }

        try {
            $this->removeFromAmazoneS3($keepOriginal);
        } catch (\Exception $e) {
        }
    }

    public function removeFromAmazoneS3($keepOriginal = false)
    {
        $client = new S3Client([
                'version'     => 'latest',
                'region'      => $this->container->getParameter('fa.aws_region'),
                'credentials' => [
                        'key'    => $this->container->getParameter('fa.aws_key'),
                        'secret' => $this->container->getParameter('fa.aws_secret'),
                ],
        ]);

        $fileKyes = array();

        if (!$keepOriginal) {
            if ($this->getImageName() != '') {
                $key = $this->getImagePath().'/'.$this->getImageName().'.jpg';
            } else {
                $key = $this->getImagePath().'/'.$this->getAdId().'_'.$this->getHash().'.jpg';
            }

            $fileKyes[] = array('Key' => $key);
        }

        //remove thumbnail
        $thumbSize = $this->container->getParameter('fa.image.thumb_size');
        $thumbSize = array_map('strtoupper', $thumbSize);

        if (is_array($thumbSize)) {
            foreach ($thumbSize as $size) {
                if ($this->getImageName() != '') {
                    $key = $this->getImagePath().'/'.$this->getImageName().'_'.$size.'.jpg';
                } else {
                    $key = $this->getImagePath().'/'.$this->getAdId().'_'.$this->getHash().'_'.$size.'.jpg';
                }
                $fileKyes[] = array('Key' => $key);
            }
        }

        $cropSize = $this->container->getParameter('fa.image.crop_size');
        $cropSize = array_map('strtoupper', $cropSize);

        //remove brand image
        if (is_array($cropSize)) {
            foreach ($cropSize as $size) {
                if ($this->getImageName() != '') {
                    $key = $this->getImagePath().'/'.$this->getImageName().'_'.$size.'.jpg';
                } else {
                    $key = $this->getImagePath().'/'.$this->getAdId().'_'.$this->getHash().'_'.$size.'.jpg';
                }
                $fileKyes[] = array('Key' => $key);
            }
        }

        $result = $client->deleteObjects(array(
                'Bucket'  => $this->container->getParameter('fa.aws_bucket'),
                'Delete'  => array('Objects' => $fileKyes)
        ));
    }

    public function uploadImagesToS3($image)
    {
        $em = $this->container->get('doctrine')->getManager();
        if ($image->getAd()) {
            $client = new S3Client([
                    'version'     => 'latest',
                    'region'      => $this->container->getParameter('fa.aws_region'),
                    'credentials' => [
                            'key'    => $this->container->getParameter('fa.aws_key'),
                            'secret' => $this->container->getParameter('fa.aws_secret'),
                    ],
            ]);

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

                if ($image->getImageName() != '') {
                    $key = $image->getPath().'/'.$image->getImageName().$size.'.jpg';
                } else {
                    $key = $image->getPath().'/'.$image->getAd()->getId().'_'.$image->getHash().$size.'.jpg';
                }

                if ($this->container->getParameter('fa.aws_bucket') == $this->container->getParameter('fa.aws_bucket_compare')) {
                    $result = $client->putObject(array(
                      'Bucket'     => $this->container->getParameter('fa.aws_bucket'),
                      'Key'        => $key,
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
                      'Key'        => $key,
                      'CacheControl' => 'max-age=21600',
                      'SourceFile' => $im,
                      'Metadata'   => array(
                          'Last-Modified' => time(),
                      )
                  ));
                }

                $resultData =  $result->get('@metadata');

                if ($resultData['statusCode'] == 200) {
                    $image->setAws(1);
                } else {
                    $image->setAws(0);
                }
            }

            $em->persist($image);
            $em->flush();
            $adListner = new AdListener($this->container);
            $adListner->handleSolr($image->getAd());
        }
    }

    public function removS3ImagesFromLocal($image)
    {
        $em = $this->container->get('doctrine')->getManager();
        if ($image->getAd()) {
            $webPath = $this->container->get('kernel')->getRootDir().'/../web';

            $thumbSize = $this->container->getParameter('fa.image.thumb_size');
            $thumbSize = array_map('strtoupper', $thumbSize);

            $images = array();
            
            foreach ($thumbSize as $d) {
                $sourceImg = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$image->getHash().'_'.$d.'.jpg';
                
                if (file_exists($sourceImg)) {
                    $images[$d] = $sourceImg;
                }
            }

            $sourceImg = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$image->getHash().'.jpg';

            if (file_exists($sourceImg)) {
                $images[''] = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$image->getHash().'.jpg';
            }
            
            //for cropped Image
            $sourceImg = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getAd()->getId().'_'.$image->getHash().'_org.jpg';
            if (file_exists($sourceImg)) {
                $images['crop_org'] = $sourceImg;
            }
            
            
            if (count($images) > 0) {
                foreach ($images as $key => $im) {
                    unlink($im);
                    echo 'Removed file '.$im."\n";
                    //logging removed file
                    $this->container->get('clean_local_images_logger')->info('Removed file ' . $im);
                }
            } else {
                echo 'File not exists at '.$webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getAd()->getId().'*'."\n";
            }

            $image->setLocal(0);
            $em->persist($image);
            $em->flush();
        }
    }
    
    public function removeArchiveAdImagesFromLocal($image)
    {
        $em = $this->container->get('doctrine')->getManager();
        if ($image->getArchiveAd()) {
            $webPath = $this->container->get('kernel')->getRootDir().'/../web';
            
            $thumbSize = $this->container->getParameter('fa.image.thumb_size');
            $thumbSize = array_map('strtoupper', $thumbSize);
            
            $images = array();
            
            foreach ($thumbSize as $d) {
                $sourceImg = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getArchiveAd()->getId().'_'.$image->getHash().'_'.$d.'.jpg';
                
                if (file_exists($sourceImg)) {
                    $images[$d] = $sourceImg;
                }
            }
            
            $sourceImg = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getArchiveAd()->getId().'_'.$image->getHash().'.jpg';
            
            if (file_exists($sourceImg)) {
                $images[''] = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getArchiveAd()->getId().'_'.$image->getHash().'.jpg';
            }
            
            //for cropped Image
            $sourceImg = $webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getArchiveAd()->getId().'_'.$image->getHash().'_org.jpg';
            if (file_exists($sourceImg)) {
                $images['crop_org'] = $sourceImg;
            }
            
            
            if (count($images) > 0) {
                foreach ($images as $key => $im) {
                    unlink($im);
                    echo 'Removed file Archive Image '.$im."\n";
                    //logging removed file
                    $this->container->get('clean_local_images_logger')->info('Removed file Archive Image ' . $im);
                }
            } else {
                echo 'Archive Image File not exists at '.$webPath.DIRECTORY_SEPARATOR.$image->getPath().DIRECTORY_SEPARATOR.$image->getArchiveAd()->getId().'*'."\n";
            }
        }
    }
    
    
    /**
     * Check images exist on AWS
     *
     * @param boolean $keepOriginal Flag for keep original image.
     */
    public function checkImageExistOnAws($imageUrl)
    {
        $client = new S3Client([
                'version'     => 'latest',
                'region'      => $this->container->getParameter('fa.aws_region'),
                'credentials' => [
                        'key'    => $this->container->getParameter('fa.aws_key'),
                        'secret' => $this->container->getParameter('fa.aws_secret'),
                ],
        ]);
        $response = $client->doesObjectExist($this->container->getParameter('fa.aws_bucket'), $imageUrl);
        return $response;
    }
    
    public function removeImageFromAmazoneS3($imageUrl)
    {
        $client = new S3Client([
            'version'     => 'latest',
            'region'      => $this->container->getParameter('fa.aws_region'),
            'credentials' => [
                'key'    => $this->container->getParameter('fa.aws_key'),
                'secret' => $this->container->getParameter('fa.aws_secret'),
            ],
        ]);
        
        $fileKeys = array();
        $fileKeys[] = array('Key' => $imageUrl);
        
        
        //remove thumbnail
        /* $thumbSize = $this->container->getParameter('fa.image.thumb_size');
         $thumbSize = array_map('strtoupper', $thumbSize);
         
         if (is_array($thumbSize)) {
         foreach ($thumbSize as $size) {
         $key = $imageUrl.'/'.$imageName.'_'.$size.'.jpg';
         $fileKeys[] = array('Key' => $key);
         }
         }
         
         $cropSize = $this->container->getParameter('fa.image.crop_size');
         $cropSize = array_map('strtoupper', $cropSize);
         
         //remove brand image
         if (is_array($cropSize)) {
         foreach ($cropSize as $size) {
         $key = $imageUrl.'/'.$imageName.'_'.$size.'.jpg';
         $fileKeys[] = array('Key' => $key);
         }
         }*/
        
        $result = $client->deleteObjects(array(
            'Bucket'  => $this->container->getParameter('fa.aws_bucket'),
            'Delete'  => array('Objects' => $fileKeys)
        ));
    }
}
