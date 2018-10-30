<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\lib\Migration;

use Fa\Bundle\AdBundle\Entity\AdProperty;
use Fa\Bundle\AdBundle\Entity\AdJobs;
use Fa\Bundle\AdBundle\Entity\AdMotors;
use Fa\Bundle\AdBundle\lib\Migration\Car;
use Gedmo\Sluggable\Util\Urlizer as Urlizer;
use Fa\Bundle\UserBundle\Entity\User;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Encoder\Pbkdf2PasswordEncoder;

/**
 * Fa\Bundle\AdBundle\lib\Migration
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class BusinessAd
{
    private $container;

    private $user;

    private $data = array();


    public function __construct(User $user, $em, $container)
    {
        $this->user = $user;
        $this->em = $em;
        $this->container = $container;
    }


    public function update()
    {
        $this->setUserData();
    }

    public function setUserData()
    {
        $userObj    = $this->user;
        $this->data = unserialize(base64_decode($userObj->getOldMetaXml()));

        $sellerRole = $this->em->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_BUSINESS_SELLER));
        $userObj->removeRole($sellerRole);
        $this->em->persist($userObj);
        $this->em->flush();

        $userObj->addRole($sellerRole);
        $userObj->setRole($sellerRole);


        $encoder = new Pbkdf2PasswordEncoder();
        $password = $encoder->encodePassword($userObj->getId(), null);
        $userObj->setPassword($password);

        $userSiteObj = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userObj));

        if (!$userSiteObj) {
            $userSiteObj = new UserSite();
            $userSiteObj->setUser($userObj);
        }

        if (isset($this->data['BusinessDescriptionHTML'])) {
            $userSiteObj->setAboutUs(stripslashes($this->data['BusinessDescriptionHTML']));
        }

        if (isset($this->data['BusinessHeadLine'])) {
            $userSiteObj->setCompanyWelcomeMessage(stripslashes($this->data['BusinessHeadLine']));
        }

        if (isset($this->data['AdRef'])) {
            $userSiteObj->setAdRef($this->data['AdRef']);
        }

        if (isset($this->data['PrimaryClass'])) {
            $Mappedcategory = $this->em->getRepository('FaEntityBundle:MappingCategory')->findOneBy(array('id' => $this->data['PrimaryClass']));

            if ($Mappedcategory) {
                $businessCategory = $this->getFirstLevelParent($Mappedcategory->getNewId());
                $userObj->setBusinessCategoryId($businessCategory['id']);

                if ($businessCategory['id'] == CategoryRepository::SERVICES_ID || $businessCategory['id'] == CategoryRepository::ADULT_ID) {
                    $userSiteObj->setProfileExposureCategoryId($Mappedcategory->getNewId());
                }
            } else {
                $cat_id = $this->getNonMappedCategoryId($this->data['PrimaryClass']);
                if ($cat_id) {
                    $businessCategory = $this->getFirstLevelParent($cat_id);
                    $userObj->setBusinessCategoryId($businessCategory['id']);

                    if ($cat_id == CategoryRepository::SERVICES_ID || $cat_id == CategoryRepository::ADULT_ID) {
                        $userSiteObj->setProfileExposureCategoryId($cat_id);
                    }
                } else {
                    echo "Mapping not found for category id".$this->data['PrimaryClass'];
                    $businessCategory = $this->getFirstLevelParent(2);
                    $userObj->setBusinessCategoryId($businessCategory['id']);
                }
            }
        }

        $this->em->persist($userObj);

        $full_address = array();
        $full_address[] = isset($this->data['AddressLine1']) && $this->data['AddressLine1'] != '' ? $this->data['AddressLine1']: null;
        $full_address[] = isset($this->data['AddressLine2']) && $this->data['AddressLine2'] != '' ? $this->data['AddressLine2']: null;
        $full_address[] = isset($this->data['Town']) && $this->data['Town'] != '' ? $this->data['Town'] : null;
        $full_address[] = isset($this->data['County']) && $this->data['County'] != '' ? $this->data['County']: null;
        $full_address[] = isset($this->data['Postcode']) && $this->data['Postcode'] != '' ? $this->data['Postcode']: null;
        $full_address = array_filter($full_address);
        $userSiteObj->setCompanyAddress(implode(', ', $full_address));
        $userSiteObj->setStatus(1);

        if (isset($this->data['TelephoneNumber']) && $this->data['TelephoneNumber'] != '') {
            $userSiteObj->setPhone1($this->data['TelephoneNumber']);
        }

        if (isset($this->data['AlternativeTelephoneNo']) && $this->data['AlternativeTelephoneNo'] == 1) {
            $userSiteObj->setPhone2($this->data['AlternativeTelephoneNo']);
        }

        if (isset($this->data['WebAddress']) && $this->data['WebAddress'] != '') {
            $userSiteObj->setWebsiteLink($this->data['WebAddress']);
        }

        $this->em->persist($userSiteObj);
        $profile = $this->em->getRepository('FaUserBundle:User')->getUserProfileSlug($userObj->getId(), $this->container, false);

        $package =  $this->em->getRepository('FaPromotionBundle:Package')->getSecondShopPackageByCategory($userObj->getBusinessCategoryId());

        if ($package) {
            $userPackage = $this->em->getRepository('FaUserBundle:UserPackage')->assignPackageToUser($userObj, $package, 'choose-package-backend', null, false, $this->container, false);
            echo 'Updated for user id'.$userObj->getId()."\n";
        } else {
            echo 'Not done for: '.$userObj->getId()."\n";
        }
    }

    /**
     * get first level parent
     *
     * @param integer $category_id
     *
     * @return object
     */
    private function getFirstLevelParent($category_id)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($category_id, false, $this->container);
        return $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById(key($cat), $this->container);
    }

    public function getNonMappedCategoryId($id)
    {
        $cat = array();
        $cat['6510'] = 2129;
        $cat['6511'] = 2317;
        $cat['1263'] = 649;
        $cat['8117'] = 21;
        $cat['1234'] = 21;
        $cat['8116'] = 21;
        $cat['972'] = 551;

        if (isset($cat[$id])) {
            return $cat[$id];
        }
    }
}
