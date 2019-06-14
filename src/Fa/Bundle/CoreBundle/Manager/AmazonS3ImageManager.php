<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Manager;

use Aws\S3\S3Client;
use Doctrine\ORM\EntityManager;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Amazon S3 manager.
 *
 * @author    Akash M. Pai <akash.pai@fridaymediagroup.com>
 * @copyright 2019 Friday Media Group Ltd
 * @version   1.0
 */
class AmazonS3ImageManager
{

    private static $instance = null;

    /**
     * Container service class object.
     *
     * @var ContainerInterface|Container
     */
    private $container;

    /**
     * Entity manager object.
     *
     * @var EntityManager
     */
    private $em;

    /**
     * @var string $region
     */
    private $region;

    /**
     * @var string $key
     */
    private $key;

    /**
     * @var string $secret
     */
    private $secret;

    /**
     * @var S3Client|null
     */
    public $s3Client;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    private function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
        $this->init();
        $this->s3Client = $this->getS3Client();
    }

    public static function getInstance(ContainerInterface $container)
    {
        if (self::$instance == null) {
            self::$instance = new AmazonS3ImageManager($container);
        }

        return self::$instance;
    }

    /**
     * Initiates the S3 parameters
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function init()
    {
        $this->region = $this->container->getParameter('fa.aws_region');
        $this->key = $this->container->getParameter('fa.aws_key');
        $this->secret = $this->container->getParameter('fa.aws_secret');
    }

    /**
     * @return S3Client|null
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function getS3Client()
    {
        try {
            return new S3Client([
                'version' => 'latest',
                'region' => $this->region,
                'credentials' => [
                    'key' => $this->key,
                    'secret' => $this->secret,
                ],
            ]);
        } catch (\Exception $e) {
            // todo handle for different S3 Exceptions
        }
        return null;
    }

    /**
     * @param bool $keepOriginal
     * @todo function already available in AdImageManager. Optimize it and move here.
     */
    public function removeFromAmazonS3($keepOriginal = false)
    {
        $fileKeys = array();

        // todo algo
        if (!$keepOriginal) {
            // get original image urls
        }
        // get thumbnail image urls

        try {
            $result = $this->s3Client->deleteObjects(array(
                'Bucket' => $this->container->getParameter('fa.aws_bucket'),
                'Delete' => array('Objects' => $fileKeys),
            ));
        } catch (\Exception $e) {
            // todo handle result exception if any
        }
        // return appropriate result
    }

    /**
     * To upload multiple images at once to S3
     * @param array $images
     * @todo if required.
     */
    public function uploadImagesToS3(array $images)
    {
        // algo.
        // get each image as $valImage
        // $this->uploadImageToS3($valImage).
        // update urls to DB. here??
        // index to solr ?
        // take care of tmp images. needed??
    }

    /**
     * Check images exist on AWS
     * @param string $imageUrl
     * @return bool
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    public function checkImageExistOnAws($imageUrl)
    {
        try {
            $response = $this->s3Client->doesObjectExist($this->container->getParameter('fa.aws_bucket'), $imageUrl);
            return $response;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *
     * @param string $imageSrc  Input image file path
     * @param string $imageDest Destination path
     * @return string
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    public function uploadImageToS3($imageSrc, $imageDest)
    {
        // todo default no-image image url
        $resImageUrl = "";
        try {
            $objS3Client = $this->getS3Client();
            if (is_null($objS3Client)) {
                return $resImageUrl;
            }
            $result = $objS3Client->putObject(array(
                'Bucket' => $this->container->getParameter('fa.aws_bucket'),
                'Key' => $imageDest,
                'CacheControl' => 'max-age=21600',
                'ACL' => 'public-read',
                'SourceFile' => $imageSrc,
                'Metadata' => array(
                    'Last-Modified' => time(),
                ),
            ));
            $resultMetaData = $result->get('@metadata');
            if ($resultMetaData['statusCode'] == 200) {
                // VarDumper::dump("success.");
                $resImageUrl = $result->get('ObjectURL');
                // } else {
                // VarDumper::dump("failed.");
            }
        } catch (\Exception $e) {
            // handle for different S3 Exceptions
            // VarDumper::dump($e->getMessage());
            // return default no-image image url
            return $resImageUrl;
        }
        return $resImageUrl;
    }

    /**
     * Lists all the directories and files with specified substr, of a given bucket.
     * Ref: https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listobjectsv2
     * @param $bucketName
     * @param $pattern
     * @return array
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    public function listObjectsWithPattern($bucketName, $pattern)
    {
        $nextContinuationToken = "";
        $listObjects = [];
        do {
            $listObjectArgs = [
                'Prefix' => $pattern,
                'Bucket' => $bucketName,
            ];
            if ($nextContinuationToken) {
                $listObjectArgs['ContinuationToken'] = $nextContinuationToken;
            }
            $resList = $this->s3Client->listObjectsV2($listObjectArgs);
            $isTruncated = $resList['IsTruncated'];
            if ($isTruncated) {
                $nextContinuationToken = $resList['NextContinuationToken'];
            }
            if (!empty($resList['Contents'])) {
                foreach ($resList['Contents'] as $valContent) {
                    array_push($listObjects, $valContent['Key']);
                }
            }
        } while ($isTruncated);
        return $listObjects;
    }

}
