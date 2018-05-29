<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ArchiveBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\AdBundle\Entity\AdReport;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ArchiveBundle\Entity\ArchiveAdImage;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ArchiveAdImageRepository extends EntityRepository
{
    const ALIAS = 'aai';

    /**
     * prepareQueryBuilder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Move ad to archive.
     *
     * @param object $archiveAd Archive ad.
     * @param object $container Container object.
     */
    public function moveAdImagesToArchive($archiveAd, $container = null)
    {
        $adImages = $this->getEntityManager()->getRepository('FaAdBundle:AdImage')->findImagesByAdId($archiveAd->getId());

        if ($adImages && count($adImages)) {
            // Remove previous images and add new images
            foreach ($this->findImagesByAdId($archiveAd->getId()) as $archiveAdImage) {
                $this->getEntityManager()->remove($archiveAdImage);
                $this->getEntityManager()->flush($archiveAdImage);
            }

            foreach ($adImages as $adImage) {
                $archiveAdImage = new ArchiveAdImage();
                $archiveAdImage->setArchiveAd($archiveAd);
                $archiveAdImage->setPath($adImage->getPath());
                $archiveAdImage->setHash($adImage->getHash());
                $archiveAdImage->setOrd($adImage->getOrd());
                $archiveAdImage->setVideo($adImage->getVideo());
                $archiveAdImage->setVideoUrl($adImage->getVideoUrl());
                $archiveAdImage->setStatus($adImage->getStatus());
                $archiveAdImage->setSessionId($adImage->getSessionId());
                $archiveAdImage->setTransId($adImage->getTransId());
                $archiveAdImage->setUpdateType($adImage->getUpdateType());
                $archiveAdImage->setAdRef($adImage->getAdRef());
                $archiveAdImage->setOldPath($adImage->getOldPath());
                $archiveAdImage->setCreatedAt($adImage->getCreatedAt());
                $archiveAdImage->setUpdatedAt($adImage->getUpdatedAt());

                $this->getEntityManager()->persist($archiveAdImage);
                $this->getEntityManager()->flush($archiveAdImage);
            }

            //Remove ad images after move to archive
            foreach ($adImages as $adImage) {
                $imageHash = $adImage->getHash();
                $imagePath = $adImage->getPath();

                $this->getEntityManager()->remove($adImage);
                $this->getEntityManager()->flush($adImage);

                $adImageManager = new AdImageManager($container, $archiveAd->getId(), $imageHash, $container->get('kernel')->getRootDir().'/../web/'.$imagePath);
                $adImageManager->removeImage(true);
            }
        }
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

        $qb->andWhere(self::ALIAS.'.archive_ad IN (:adId)');
        $qb->setParameter('adId', $adId);

        if ($status) {
            $qb->andWhere(self::ALIAS.'.status = '.$status);
        }
        return $qb->getQuery()->getResult();
    }
}
