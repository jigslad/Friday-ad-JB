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
use Fa\Bundle\UserBundle\Manager\UserImageManager;
use Fa\Bundle\UserBundle\Manager\UserSiteBannerManager;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Solr\UserShopDetailSolrFieldMapping;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSiteRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'us';

    /**
     * Remove user site data.
     *
     * @param integer $userId    User id.
     * @param object  $container Container object.
     */
    public function removeBusinessUserSiteData($userId, $container)
    {
        $userSite = $this->findOneBy(array('user' => $userId));

        if ($userSite) {
            $webPath = $container->get('kernel')->getRootDir().'/../web';
            $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$userSite->getPath();

            $userImageManager = new UserImageManager($container, $userId, $orgImagePath, true);
            $userImageManager->removeImage();

            $userSiteBannerManager = new UserSiteBannerManager($container, $userSite->getId(), $webPath.'/'.$userSite->getBannerPath());
            $userSiteBannerManager->removeImage();

            $userSiteId = $userSite->getId();
            $userSiteImages = $this->_em->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImages($userSiteId);
            foreach ($userSiteImages as $userSiteImage) {
                $this->_em->getRepository('FaUserBundle:UserSiteImage')->removeUserSiteImage($userSiteId, $userSiteImage->getId(), $userSiteImage->getHash(), $container);
            }
            $deleteManager = $container->get('fa.deletemanager');
            $deleteManager->delete($userSite);
        }
    }

    /**
     * Get user site table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getUserSiteTableName()
    {
        return $this->_em->getClassMetadata('FaUserBundle:UserSite')->getTableName();
    }

    /**
     * Get id by slug.
     *
     * @param integer $slug      Category slug.
     * @param object  $container Container identifier.
     *
     * @return integer
     */
    public function getUserIdBySlug($slug, $container = null)
    {
        if ($container) {
            $tableName   = $this->getUserSiteTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$slug;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $userSite = $this->findOneBy(array('slug' => $slug));

        if ($userSite) {
            if ($container) {
                CommonManager::setCacheVersion($container, $cacheKey, $userSite->getUser()->getId());
            }

            return $userSite->getUser()->getId();
        }
    }

    /**
     * Returns user solr document object.
     *
     * @param object $user User object.
     *
     * @return Apache_Solr_Document
     */
    public function getSolrDocument($user, $container = null)
    {
        $document = new \SolrInputDocument($user);
        $userSite = $this->_em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
        $adsCount = 0;
        $document = $this->addField($document, UserShopDetailSolrFieldMapping::ID, $user->getId());
        $document = $this->addField($document, UserShopDetailSolrFieldMapping::POSTCODE, $user->getZip());
        $document = $this->addField($document, UserShopDetailSolrFieldMapping::DOMICILE_ID, ($user->getLocationDomicile() ? $user->getLocationDomicile()->getId() : null));
        $document = $this->addField($document, UserShopDetailSolrFieldMapping::TOWN_ID, ($user->getLocationTown() ? $user->getLocationTown()->getId() : null));
        $document = $this->addField($document, UserShopDetailSolrFieldMapping::USER_STATUS_ID, ($user->getStatus() ? $user->getStatus()->getId() : null));
        $document = $this->addField($document, UserShopDetailSolrFieldMapping::USER_PROFILE_NAME, $user->getProfileName());
        if ($userSite) {
            $document = $this->addField($document, UserShopDetailSolrFieldMapping::COMPANY_WELCOME_MESSAGE, $userSite->getCompanyWelcomeMessage());
            $document = $this->addField($document, UserShopDetailSolrFieldMapping::ABOUT_US, $userSite->getAboutUs());
            $document = $this->addField($document, UserShopDetailSolrFieldMapping::USER_COMPANY_LOGO_PATH, $userSite->getPath());
            if ($userSite->getProfileExposureCategoryId()) {
                $document = $this->addField($document, UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_CATEGORY_ID, $userSite->getProfileExposureCategoryId());
            } elseif ($user->getBusinessCategoryId()) {
                $document = $this->addField($document, UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_CATEGORY_ID, $user->getBusinessCategoryId());
            }

            // Index for parent categories
            if ($userSite->getProfileExposureCategoryId()) {
                $parentCategories = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($userSite->getProfileExposureCategoryId(), false, $container);
                array_pop($parentCategories);

                if (count($parentCategories)) {
                    $level = 1;
                    foreach ($parentCategories as $parentId => $parentName) {
                        $document = $this->addField($document, 'u_s_d_parent_profile_exposure_category_lvl_'.$level++.'_id_i', $parentId);
                    }
                }
            }
        }

        $hasProfileExposureMiles = '-1';
        $userUpsells = $this->_em->getRepository('FaUserBundle:UserUpsell')->getUserUpsellArrayWithValue($user->getId());
        foreach ($userUpsells as $upsellId => $upsellValue) {
            if (in_array($upsellId, $this->_em->getRepository('FaPromotionBundle:Upsell')->getProfileExposureUpsellIdsIdsArray())) {
                $hasProfileExposureMiles = (!$upsellValue['upsell_value'] ? '0' : $upsellValue['upsell_value']);
                break;
            }
        }

        $document = $this->addField($document, UserShopDetailSolrFieldMapping::PROFILE_EXPOSURE_MILES, $hasProfileExposureMiles);

        if ($user->getLocationTown() && $user->getLocationTown()->getLatitude() && $user->getLocationTown()->getLongitude()) {
            $document = $this->addField($document, UserShopDetailSolrFieldMapping::STORE, $user->getLocationTown()->getLatitude().','.$user->getLocationTown()->getLongitude());
        } elseif ($user->getLocationDomicile() && $user->getLocationDomicile()->getLatitude() && $user->getLocationDomicile()->getLongitude()) {
            $document = $this->addField($document, UserShopDetailSolrFieldMapping::STORE, $user->getLocationDomicile()->getLatitude().','.$user->getLocationDomicile()->getLongitude());
        }

        $userPackagePurchasedAt = null;
        $userActivePackage = $this->_em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);
        if ($userActivePackage) {
            $userPackagePurchasedAt = ($userActivePackage->getUpdatedAt() ? $userActivePackage->getUpdatedAt() : $userActivePackage->getCreatedAt());
        }

        $document = $this->addField($document, UserShopDetailSolrFieldMapping::SHOP_PACKAGE_PURCHASED_AT, $userPackagePurchasedAt);
        $adsCount = $this->_em->getRepository('FaAdBundle:Ad')->getActiveAdCountForUser($user->getId());        
        $document = $this->addField($document, UserShopDetailSolrFieldMapping::USER_LIVE_ADS_COUNT, $adsCount);
        
        
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
        if ($value != null) {
            $document->addField($field, $value);
        }

        return $document;
    }
}
