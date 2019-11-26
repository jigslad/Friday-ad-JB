<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Fa\Bundle\AdBundle\Entity\Ad;
use Doctrine\ORM\EntityRepository;
use Fa\Bundle\AdBundle\Entity\AdImage;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdImageRepository extends EntityRepository
{
    const ALIAS = 'ai';

    /**
     * prepareQueryBuilder.
     *
     * @return QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Get ad image max count.
     *
     * @param string  $adId  Ad id.
     * @param boolean $isNew Flag for new.
     *
     * @return integer
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMaxOrder($adId, $isNew = false)
    {
        $query = $this->createQueryBuilder(self::ALIAS);

        if ($isNew) {
            $query->andWhere(self::ALIAS.'.session_id = :session_id')
                ->setParameter('session_id', $adId);
        } else {
            $query->andWhere(self::ALIAS.'.ad = :ad_id')
            ->setParameter('ad_id', $adId);
        }

        $query->addOrderBy(self::ALIAS.'.ord', 'DESC')
            ->setMaxResults(1);
        $maxOrderObj = $query->getQuery()->getOneOrNullResult();

        $maxOrder = 1;
        if ($maxOrderObj) {
            $maxOrder = $maxOrderObj->getOrd() + 1;
        }

        return $maxOrder;
    }

    /**
     * Get ad image count.
     *
     * @param string $adId Ad id.
     *
     * @return integer
     */
    public function getAdImageCount($adId)
    {
        $query = $this->createQueryBuilder(self::ALIAS);

        if (preg_match('/^\d+$/', $adId)) {
            $query->andWhere(self::ALIAS.'.ad = :ad_id')
                ->setParameter('ad_id', $adId);
        } else {
            $query->andWhere(self::ALIAS.'.session_id = :session_id')
                ->setParameter('session_id', $adId);
        }

        $query->select('COUNT('.self::ALIAS.'.id) as image_count')
            ->setMaxResults(1);

        $adImage = $query->getQuery()->getArrayResult();

        return $adImage[0]['image_count'];
    }

    /**
     * Get ad images.
     *
     * @param string $adId   Ad id.
     * @param mixed  $status Image status.
     *
     * @return AdImage[]
     */
    public function getAdImages($adId, $status = null)
    {
        $query = $this->createQueryBuilder(self::ALIAS);

        if (preg_match('/^\d+$/', $adId)) {
            $query->andWhere(self::ALIAS.'.ad = :ad_id')
            ->setParameter('ad_id', $adId);
        } else {
            $query->andWhere(self::ALIAS.'.session_id = :session_id')
            ->setParameter('session_id', $adId);
        }

        if ($status != null) {
            $query->andWhere(self::ALIAS.'.status = '.$status);
        }

        $query->addOrderBy(self::ALIAS.'.ord', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * Get ad image query by ad id, image id & hash.
     *
     * @param integer $adId      Ad id.
     * @param integer $imageId   Image id.
     * @param string  $imageHash Image hash value.
     *
     * @return QueryBuilder
     */
    public function getAdImageQueryByAdIdImageIdHash($adId, $imageId, $imageHash)
    {
        $query = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.id = :imageId')
        ->setParameter('imageId', $imageId)
        ->andWhere(self::ALIAS.'.hash = :imageHash')
        ->setParameter('imageHash', $imageHash);

        if (preg_match('/^\d+$/', $adId)) {
            $query->andWhere(self::ALIAS.'.ad = :ad_id')
            ->setParameter('ad_id', $adId);
        } else {
            $query->andWhere(self::ALIAS.'.session_id = :session_id')
            ->setParameter('session_id', $adId);
        }

        return $query;
    }

    /**
     * Get ad image query by ad id & hash.
     *
     * @param integer $adId      Ad id.
     * @param string  $imageHash Image hash value.
     *
     * @return QueryBuilder
     */
    public function getAdImageQueryByAdIdImageHash($adId, $imageHash)
    {
        $query = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.hash = :imageHash')
        ->setParameter('imageHash', $imageHash);

        if (preg_match('/^\d+$/', $adId)) {
            $query->andWhere(self::ALIAS.'.ad = :ad_id')
            ->setParameter('ad_id', $adId);
        } else {
            $query->andWhere(self::ALIAS.'.session_id = :session_id')
            ->setParameter('session_id', $adId);
        }

        return $query;
    }

    /**
     * Removes ad image.
     *
     * @param integer $adId      Ad id.
     * @param integer $imageId   Image id.
     * @param string  $imageHash Image hash value.
     * @param object  $container Container identifier.
     *
     * @return boolean
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function removeAdImage($adId, $imageId, $imageHash, $container)
    {
        /**
         * @var AdImage $adImageObj
         */
        $query = $this->getAdImageQueryByAdIdImageIdHash($adId, $imageId, $imageHash);

        $adImageObj = $query->getQuery()->getOneOrNullResult();
        $delAdId    = null;
        $delHash    = null;
        $delPath    = null;
        $ad         = null;

        //update order
        if ($adImageObj) {
            if ($adImageObj->getSessionId()) {
                $delAdId = $adImageObj->getSessionId();
            } else {
                $delAdId = $adImageObj->getAd()->getId();
                $ad      = $adImageObj->getAd();
            }
            $delPath    = $adImageObj->getPath();
            $delHash    = $adImageObj->getHash();
            $ord        = $adImageObj->getOrd();
            $imageName  = $adImageObj->getImageName();

            $updateQuery = $this->createQueryBuilder(self::ALIAS)
            ->update()
            ->set(self::ALIAS.'.ord', self::ALIAS.'.ord - 1')
            ->andwhere(self::ALIAS.'.ord > '. $ord);
            if (preg_match('/^\d+$/', $adId)) {
                $updateQuery->andWhere(self::ALIAS.'.ad = :ad_id')
                    ->setParameter('ad_id', $adId);
            } else {
                $updateQuery->andWhere(self::ALIAS.'.session_id = :session_id')
                    ->setParameter('session_id', $adId);
            }

            $updateQuery->getQuery()->execute();
        }

        //delete image.
        $deleteQuery = $this->getAdImageQueryByAdIdImageIdHash($adId, $imageId, $imageHash)
            ->delete();

        if (preg_match('/^\d+$/', $adId)) {
            $deleteQuery->andWhere(self::ALIAS.'.ad = :ad_id')
                ->setParameter('ad_id', $adId);
        } else {
            $deleteQuery->andWhere(self::ALIAS.'.session_id = :session_id')
                ->setParameter('session_id', $adId);
        }
        $deleteFlag = $deleteQuery->getQuery()->execute();

        if ($deleteFlag && $delAdId && $delHash) {
            $adImageManager = new AdImageManager($container, $delAdId, $delHash, $container->get('kernel')->getRootDir().'/../web/'.$delPath, $imageName, $delPath);
            //remove thumbnails
            $adImageManager->removeImage();
        }

        // remove ad image from solr.
        if ($ad
            && ($ad->getStatus()->getId() == BaseEntityRepository::AD_STATUS_LIVE_ID
            || $ad->getStatus()->getId() == BaseEntityRepository::AD_STATUS_SOLD_ID)) {
            $this->updateImageToSolr($ad, $container);
        }
        
        if ($ad) {
            $adImageCountArray = $this->getAdImageCountArrayByAdId(array($ad->getId()));
            $ad->setImageCount((isset($adImageCountArray[$ad->getId()]) ? $adImageCountArray[$ad->getId()] : 0));
            $this->_em->persist($ad);
            $this->_em->flush($ad);
        }

        return $deleteFlag;
    }

    /**
     * Change order of the image.
     *
     * @param integer $id     Id of image.
     * @param string  $adId  Id of ad.
     * @param integer $ord    Existing order of the image.
     * @param integer $newOrd New order of the image.
     *
     * @return boolean
     */
    public function changeOrder($id, $adId, $ord, $newOrd)
    {
        $updateQuery = $this->createQueryBuilder(self::ALIAS)
        ->update()
        ->set(self::ALIAS.'.ord', $ord)
        ->where(self::ALIAS.'.ord = '.$newOrd);
        if (preg_match('/^\d+$/', $adId)) {
            $updateQuery->andWhere(self::ALIAS.'.ad = :ad_id')
            ->setParameter('ad_id', $adId);
        } else {
            $updateQuery->andWhere(self::ALIAS.'.session_id = :session_id')
            ->setParameter('session_id', $adId);
        }
        $updateQuery->getQuery()->execute();

        $updateQuery = $this->createQueryBuilder(self::ALIAS)
        ->update()
        ->set(self::ALIAS.'.ord', $newOrd)
        ->where(self::ALIAS.'.id = '.$id);

        if (preg_match('/^\d+$/', $adId)) {
            $updateQuery->andWhere(self::ALIAS.'.ad = :ad_id')
            ->setParameter('ad_id', $adId);
        } else {
            $updateQuery->andWhere(self::ALIAS.'.session_id = :session_id')
            ->setParameter('session_id', $adId);
        }

        return $updateQuery->getQuery()->execute();
    }

    /**
     * Returns ad solr document object.
     *
     * @param object  $ad         Ad object.
     * @param mixed   $document   Solr document object.
     * @param integer $imageLimit Image limit.
     *
     * @return Apache_Solr_Document
     */
    public function getSolrDocument($ad, $document = null, $imageLimit = 0)
    {
        /**
         * @var AdImage[] $images
         */
        if (!$document) {
            $document = new \SolrInputDocument($ad);
        }

        $images = $this->findBy(array('ad' => $ad->getId(), 'status' => 1), array('ord' => 'ASC'), $imageLimit);

        foreach ($images as $image) {
            $document = $this->addField($document, AdSolrFieldMapping::PATH, $image->getPath());
            $document = $this->addField($document, AdSolrFieldMapping::ORD, $image->getOrd());
            $document = $this->addField($document, AdSolrFieldMapping::HASH, $image->getHash());
            $document = $this->addField($document, AdSolrFieldMapping::AWS, $image->getAws());
            $document = $this->addField($document, AdSolrFieldMapping::IMAGE_NAME, $image->getImageName());
        }

        // Store total images counter.
        $document = $this->addField($document, AdSolrFieldMapping::TOTAL_IMAGES, count($images));

        return $document;
    }

    /**
     * Add field to solr document.
     *
     * @param object $document Solr document object.
     * @param string $field    Field to index or store.
     * @param string $value    Value of field.
     *
     * @return object
     */
    private function addField($document, $field, $value)
    {
        if ($value !== null) {
            $document->addField($field, $value);
        }

        return $document;
    }

    /**
     * Find the images by ad id.
     *
     * @param integer $adId   Ad id.
     * @param mixed   $status Status.
     *
     * @return array
     */
    public function findByAdId($adId, $status = null)
    {
        $qb = $this->getBaseQueryBuilder();

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        $qb->andWhere(self::ALIAS.'.ad IN (:adId)');
        $qb->setParameter('adId', $adId);

        if ($status) {
            $qb->andWhere(self::ALIAS.'.status = '.$status);
        }
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Update data from moderation.
     *
     * @param array $data Data from moderation.
     */
    public function updateDataFromModeration($data)
    {
        foreach ($data as $element) {
            $object = $this->findOneBy(array('id' => $element['id']));
            if ($object) {
                foreach ($element as $field => $value) {
                    $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
                    if (method_exists($object, $methodName) === true) {
                        if ($field == 'status') {
                            $value = 1;
                        }
                        $object->$methodName($value);
                    }
                }
                $this->_em->persist($object);
                $this->_em->flush($object);
            }
        }
    }

    /**
     * Get image from solr result.
     *
     * @param object  $container Container object.
     * @param array   $ad        Ad solr result.
     * @param integer $size      Size.
     * @param integer $ord       Image order.
     *
     * @return string
     */
    public function getImagePath($container, $ad, $size, $ord = 1)
    {
        $webPath = $container->get('kernel')->getRootDir().'/../web';
        if (isset($ad[AdSolrFieldMapping::ORD]) && isset($ad[AdSolrFieldMapping::PATH]) && isset($ad[AdSolrFieldMapping::HASH])) {
            foreach ($ad[AdSolrFieldMapping::ORD] as $imgNo => $imgOrd) {
                if ($imgOrd == $ord) {
                    if (isset($ad[AdSolrFieldMapping::PATH][$imgNo])) {
                        return CommonManager::getAdImageUrl($container, $ad[AdSolrFieldMapping::ID], $ad[AdSolrFieldMapping::PATH][$imgNo], $ad[AdSolrFieldMapping::HASH][$imgNo], $size, $ad[AdSolrFieldMapping::AWS][$imgNo], $ad[AdSolrFieldMapping::IMAGE_NAME][$imgNo]);
                    } else {
                        return null;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get image url.
     * Pass either static url or container
     *
     * @param Ad      $ad        Ad solr result.
     * @param integer $size      Size.
     * @param integer $ord       Image order.
     * @param object  $container Container identifier.
     *
     * @return string
     */
    public function getImageUrl($ad, $size, $ord = 1, $container = null)
    {
        $url = null;

        if (!$container) {
            return $url;
        }

        $image = $this->findOneBy(array('ad' => $ad->getId(), 'ord' => $ord));
        if ($image) {
            $url = CommonManager::getAdImageUrl($container, $ad->getId(), $image->getPath(), $image->getHash(), $size, 1, $image->getImageName());
        }

        return $url;
    }

    /**
     * Update order of the image.
     *
     * @param integer $id  Id of image.
     * @param integer $ord Existing order of the image.
     *
     * @return boolean
     */
    public function updateOrder($id, $ord)
    {
        $updateQuery = $this->createQueryBuilder(self::ALIAS)
        ->update()
        ->set(self::ALIAS.'.ord', $ord)
        ->where(self::ALIAS.'.id = '.$id);

        return $updateQuery->getQuery()->execute();
    }

    /**
     * Set data on object from moderation.
     *
     * @param array $element Element from moderation.
     *
     * @return object
     */
    public function setObjectFromModerationData($element)
    {
        $object = $this->findOneBy(array('id' => $element['id']));
        foreach ($element as $field => $value) {
            $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            if (method_exists($object, $methodName) === true) {
                $object->$methodName($value);
            }
        }

        return $object;
    }

    /**
     * This method is used do save object.
     *
     * @param Object  $entity      Entity to save.
     * @param Object  $container   Container object.
     * @param boolean $isAdminUser
     *
     * @return Object
     */
    public function saveImage($entity, $container, $isAdminUser = false)
    {
        if (!$isAdminUser && $entity->getAd() && $entity->getAd()->getStatus()->getId() != BaseEntityRepository::AD_STATUS_DRAFT_ID) {
            $entity->setStatus(0);
        }

        $this->_em->persist($entity);
        $this->_em->flush();

        // add ad for moderation & send for moderation.
        if (!$isAdminUser && $entity->getAd() && !in_array($entity->getAd()->getStatus()->getId(), array(BaseEntityRepository::AD_STATUS_DRAFT_ID, BaseEntityRepository::AD_STATUS_EXPIRED_ID, BaseEntityRepository::AD_STATUS_SOLD_ID))) {
            $modifyIp = $container->get('request_stack')->getCurrentRequest()->getClientIp();
            $this->_em->getRepository('FaPaymentBundle:Payment')->handleAdModerate($entity->getAd(), $modifyIp);
            $this->_em->getRepository('FaAdBundle:AdModerate')->sendAdForModeration($entity->getAd(), $container);
        }

        if ($isAdminUser && $entity->getAd()
            && ($entity->getAd()->getStatus()->getId() == BaseEntityRepository::AD_STATUS_LIVE_ID
            || $entity->getAd()->getStatus()->getId() == BaseEntityRepository::AD_STATUS_SOLD_ID)) {
            $ad = $entity->getAd();
            $ad->setEditedAt(time());
            $this->_em->persist($ad);
            $this->_em->flush();
            $this->updateImageToSolr($entity->getAd(), $container);
        }

        return $entity;
    }

    /**
     * Update image solr index.
     *
     * @param Ad     $ad
     * @param Object $container Container object.
     *
     * @return boolean
     */
    public function updateImageToSolr($ad, $container)
    {
        $solrClient = $container->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            return false;
        }

        $adSolrIndex = $container->get('fa.ad.solrindex');

        // TODO: update only partial document where pecl will add support for it
        //return $adSolrIndex->updateImage($solrClient, $ad, $container, false);

        return $adSolrIndex->update($solrClient, $ad, $container, false);
    }

    /**
     * Get image from ad id.
     *
     * @param array $adId Ad id array.
     *
     * @return array
     */
    public function getAdMainImageArrayByAdId($adId = array())
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.ord = 1');

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $qb->andWhere(self::ALIAS.'.ad IN (:adId)');
            $qb->setParameter('adId', $adId);
        }

        $adImages   = $qb->getQuery()->setHint(\Doctrine\ORM\Query::HINT_INCLUDE_META_COLUMNS, true)->getArrayResult();
        $adImageArr = array();
        if (count($adImages)) {
            foreach ($adImages as $adImage) {
                $adImageArr[$adImage['ad_id']] = array(
                                                    'path' => $adImage['path'],
                                                    'hash' => $adImage['hash'],
                                                    'aws' => $adImage['aws'],
                                                    'image_name' => $adImage['image_name'],
                                                   );
            }
        }

        return $adImageArr;
    }

    /**
     * Find the images by ad id.
     *
     * @param integer $adId   Ad id.
     * @param mixed   $status Status.
     *
     * @return array
     */
    public function findImagesByAdId($adId, $status = null)
    {
        $qb = $this->getBaseQueryBuilder();

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        $qb->andWhere(self::ALIAS.'.ad IN (:adId)');
        $qb->setParameter('adId', $adId);

        if ($status) {
            $qb->andWhere(self::ALIAS.'.status = '.$status);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Create image from source.
     *
     * @param object  $container
     * @param string  $sourcePath
     * @param string  $sourceName
     * @param integer $adId
     * @param integer $sessionId
     */
    public function createImageFromSource($container, $sourcePath, $sourceName, $adId = null, $sessionId = null)
    {
        $imagePath = null;
        if ($adId) {
            $imagePath = $container->getParameter('fa.ad.image.dir').'/'.CommonManager::getGroupDirNameById($adId);
        } elseif ($sessionId) {
            $imagePath = $container->getParameter('fa.ad.image.tmp.dir');
        }

        if ($imagePath) {
            $image = new AdImage();

            $webPath   = $container->get('kernel')->getRootDir().'/../web';

            $hash = CommonManager::generateHash();
            $image->setHash($hash);
            $image->setPath($imagePath);
            $image->setOrd(1);
            $image->setStatus('1');
            $image->setAws(0);

            if ($adId) {
                $image->setAd($this->_em->getReference('FaAdBundle:Ad', $adId));
            } elseif ($sessionId) {
                $image->setSessionId($sessionId);
            }

            $orgImageName = $sourceName;
            $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imagePath;

            if (!is_dir($orgImagePath)) {
                $old     = umask(0);
                mkdir($orgImagePath, 0777);
                umask($old);
            }
            
            $orginFilePath = $sourcePath.'/'.$sourceName;
            if (file_exists($orginFilePath)) {
                $docopy = 1;
            } else {
                $docopy = 0;
            }
            
            if ($docopy==1) {
                //create original image.
                copy($sourcePath.'/'.$sourceName, $orgImagePath.'/'.$orgImageName);
            }

            $this->_em->persist($image);
            $this->_em->flush($image);

            if ($adId) {
                $adImageManager = new AdImageManager($container, $adId, $hash, $orgImagePath);
            } else {
                $adImageManager = new AdImageManager($container, $sessionId, $hash, $orgImagePath);
            }
            
            if ($docopy==1) {
                //save original jpg image.
                $adImageManager->saveOriginalJpgImage($orgImageName);
                //create thumbnails
                $adImageManager->createThumbnail();
                //create cope thumbnails
                $adImageManager->createCropedThumbnail();
            }
            $adImageManager->uploadImagesToS3($image);
        }        
    }

    /**
     * Change image status.
     *
     * @param string  $adId  Id of ad.
     * @param integer $status Image status
     */
    public function changeStatusByAdId($adId, $status = 1)
    {
        $updateQuery = $this->createQueryBuilder(self::ALIAS)
        ->update()
        ->set(self::ALIAS.'.status', $status);

        if (preg_match('/^\d+$/', $adId)) {
            $updateQuery->andWhere(self::ALIAS.'.ad = :ad_id')
            ->setParameter('ad_id', $adId);
        } else {
            $updateQuery->andWhere(self::ALIAS.'.session_id = :session_id')
            ->setParameter('session_id', $adId);
        }

        $updateQuery->getQuery()->execute();
    }

    /**
     * Get image count for ad id.
     *
     * @param array $adId Ad id array.
     *
     * @return array
     */
    public function getAdImageCountArrayByAdId($adId = array())
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
            ->select('IDENTITY('.self::ALIAS.'.ad) as ad_id', 'COUNT('.self::ALIAS.'.id) as ad_image_count')
            ->andWhere(self::ALIAS.'.status = 1')
            ->groupBy(self::ALIAS.'.ad');

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $qb->andWhere(self::ALIAS.'.ad IN (:adId)')
                ->setParameter('adId', $adId);
        }

        $adImages   = $qb->getQuery()->getArrayResult();
        $adImageArr = array();
        if (count($adImages)) {
            foreach ($adImages as $adImage) {
                $adImageArr[$adImage['ad_id']] = $adImage['ad_image_count'];
            }
        }

        return $adImageArr;
    }

}
