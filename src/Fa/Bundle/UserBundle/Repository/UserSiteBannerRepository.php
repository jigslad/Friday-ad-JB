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
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Manager\UserSiteBannerManager;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSiteBannerRepository extends EntityRepository
{
    const ALIAS = 'usb';

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
     * Get user site banner otherimages.
     *
     * @return mixed
     */
    public function getUserSiteBannerOtherImages()
    {
        $query = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.category_id IS NULL')
        ->addOrderBy(self::ALIAS.'.ord', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * Update user banner.
     *
     * @param object $userObj User object
     */
    public function updateUserBanner($userObj, $container)
    {
        if (($userObj->getRole()->getName() == RoleRepository::ROLE_BUSINESS_SELLER || $userObj->getRole()->getName() == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) && $userObj->getBusinessCategoryId()) {
            $userSite = $this->_em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userObj->getId()));
            if (!$userSite) {
                $userSite = new UserSite();
                $this->_em->persist($userSite);
                $this->_em->flush($userSite);
            }

            $userSiteId = $userSite->getId();
            if (!$userSite->getBannerPath()) {
                $userSiteId = $userSite->getId();
                $userSiteBanner = $this->_em->getRepository('FaUserBundle:UserSiteBanner')->findOneBy(array('category_id' => $userObj->getBusinessCategoryId()));
                $bannerUpdated = $this->changeBanner($userSiteId, $userSiteBanner, $container);
                if ($bannerUpdated) {
                    $userSite->setBannerPath($container->getParameter('fa.user.site.image.dir').'/'.CommonManager::getGroupDirNameById($userSiteId));
                    $this->_em->persist($userSite);
                    $this->_em->flush($userSite);
                }
            }

            return true;
        }

        return false;
    }

    public function changeBanner($userSiteId, $userSiteBanner, $container)
    {
        $imagePath  = $container->getParameter('fa.user.site.banner.image.dir');
        $webPath = $container->get('kernel')->getRootDir().'/../web';
        $siteBannerImagePath = $webPath.DIRECTORY_SEPARATOR.$imagePath;
        $orgImagePath  = $webPath.DIRECTORY_SEPARATOR.$container->getParameter('fa.user.site.image.dir').'/'.CommonManager::getGroupDirNameById($userSiteId);
        CommonManager::createGroupDirectory($webPath.DIRECTORY_SEPARATOR.$container->getParameter('fa.user.site.image.dir'), $userSiteId);

        $usersiteBannerManager = new UserSiteBannerManager($container, $userSiteId, $orgImagePath);
        //save original jpg image
        $usersiteBannerManager->assignDefaultCategoryBanner($userSiteBanner, $siteBannerImagePath);

        return true;
    }
}
