<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\UserBundle\Manager\UserSiteImageManager;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSiteImageRepository extends EntityRepository
{
    const ALIAS = 'usi';

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
     * Get user site image max count.
     *
     * @param integer $userSiteId User site id.
     *
     * @return integer
     */
    public function getMaxOrder($userSiteId)
    {
        $query = $this->createQueryBuilder(self::ALIAS);

        $query->andWhere(self::ALIAS.'.user_site = :userSiteId')
            ->setParameter('userSiteId', $userSiteId);

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
     * Get user site image count.
     *
     * @param integer $userSiteId User site id.
     *
     * @return integer
     */
    public function getUserSiteImageCount($userSiteId)
    {
        $query = $this->createQueryBuilder(self::ALIAS);

        $query->andWhere(self::ALIAS.'.user_site = :userSiteId')
            ->setParameter('userSiteId', $userSiteId);

        $query->select('COUNT('.self::ALIAS.'.id) as image_count')
            ->setMaxResults(1);

        $userSiteImage = $query->getQuery()->getArrayResult();

        return $userSiteImage[0]['image_count'];
    }

    /**
     * Get user site images.
     *
     * @param integer $userSiteId User site id.
     *
     * @return mixed
     */
    public function getUserSiteImages($userSiteId)
    {
        $query = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.user_site = :userSiteId')
            ->setParameter('userSiteId', $userSiteId);

        $query->addOrderBy(self::ALIAS.'.ord', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * Get user site image query by user site id, image id & hash.
     *
     * @param integer $userSiteId User site id.
     * @param integer $imageId    Image id.
     * @param string  $imageHash  Image hash value.
     *
     * @return QueryBuilder
     */
    public function getUserSiteImageQueryByUserSiteIdImageIdHash($userSiteId, $imageId, $imageHash)
    {
        $query = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.id = :imageId')
        ->setParameter('imageId', $imageId)
        ->andWhere(self::ALIAS.'.hash = :imageHash')
        ->setParameter('imageHash', $imageHash);

        $query->andWhere(self::ALIAS.'.user_site = :userSiteId')
            ->setParameter('userSiteId', $userSiteId);

        return $query;
    }

    /**
     * Removes user site image.
     *
     * @param integer $userSiteId User site id.
     * @param integer $imageId    Image id.
     * @param string  $imageHash  Image hash value.
     * @param object  $container  Container identifier.
     *
     * @return boolean
     */
    public function removeUserSiteImage($userSiteId, $imageId, $imageHash, $container)
    {
        $query = $this->getUserSiteImageQueryByUserSiteIdImageIdHash($userSiteId, $imageId, $imageHash);

        $userSiteImageObj = $query->getQuery()->getOneOrNullResult();
        $delHash    = null;
        $delPath    = null;

        //update order
        if ($userSiteImageObj) {
            $delPath = $userSiteImageObj->getPath();
            $delHash = $userSiteImageObj->getHash();
            $ord     = $userSiteImageObj->getOrd();

            $updateQuery = $this->createQueryBuilder(self::ALIAS)
            ->update()
            ->set(self::ALIAS.'.ord', self::ALIAS.'.ord - 1')
            ->andwhere(self::ALIAS.'.ord > '. $ord)
            ->andWhere(self::ALIAS.'.user_site = :userSiteId')
            ->setParameter('userSiteId', $userSiteId);

            $updateQuery->getQuery()->execute();
        }

        //delete image.
        $deleteQuery = $this->getUserSiteImageQueryByUserSiteIdImageIdHash($userSiteId, $imageId, $imageHash)
            ->delete();
        $deleteFlag = $deleteQuery->getQuery()->execute();

        if ($deleteFlag && $userSiteId && $delHash) {
            $userSiteImageManager = new UserSiteImageManager($container, $userSiteId, $delHash, $container->get('kernel')->getRootDir().'/../web/'.$delPath);
            //remove thumbnails
            $userSiteImageManager->removeImage();
        }

        return $deleteFlag;
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
     * Change order of the image.
     *
     * @param integer $id         Id of image.
     * @param integer $userSiteId User site id.
     * @param integer $ord        Existing order of the image.
     * @param integer $newOrd     New order of the image.
     *
     * @return boolean
     */
    public function changeOrder($id, $userSiteId, $ord, $newOrd)
    {
        $updateQuery = $this->createQueryBuilder(self::ALIAS)
        ->update()
        ->set(self::ALIAS.'.ord', $ord)
        ->where(self::ALIAS.'.ord = '.$newOrd)
        ->andWhere(self::ALIAS.'.user_site = :userSiteId')
        ->setParameter('userSiteId', $userSiteId);
        $updateQuery->getQuery()->execute();

        $updateQuery = $this->createQueryBuilder(self::ALIAS)
        ->update()
        ->set(self::ALIAS.'.ord', $newOrd)
        ->where(self::ALIAS.'.id = '.$id)
        ->andWhere(self::ALIAS.'.user_site = :userSiteId')
        ->setParameter('userSiteId', $userSiteId);

        return $updateQuery->getQuery()->execute();
    }
}
