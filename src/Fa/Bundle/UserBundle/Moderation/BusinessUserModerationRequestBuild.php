<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Moderation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\User;

/**
 * This controller is used for content moderation.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class BusinessUserModerationRequestBuild
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Moderation request array.
     *
     * @var array
     */
    private $moderationRequest = array();

    /**
     * UserSite object.
     *
     * @var object
     */
    private $userSite;

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->userSite = null;
        $this->moderationRequest = array();
        $this->em = $this->container->get('doctrine')->getManager();
    }

    /**
     * Initialize moderation request build.
     *
     * @param User   $user                     User object.
     * @param number $priority                 Priority for moderation.
     * @param string $isForManualModeration    Whether you are sending ad for manual moderation or not.
     * @param string $manualModerationReason   What is the reason for manual moderation.
     *
     * @return array
     */
    public function init(User $user, $priority = 1, $isForManualModeration = false, $manualModerationReason = '')
    {
        if ($user && $user->getId()) {
            $this->userSite = $this->em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
        }
        $this->buildOtherParametersArray($user, $priority, $isForManualModeration, $manualModerationReason);

        $this->buildBasicFieldArray($user);

        $this->buildUserFieldArray($user);

        $this->buildClassificationArray($user);

        $this->buildImageArray($user);

        $this->buildAdditionalFieldArray($user);

        $this->buildOtherFieldArray($user);

        $this->buildAdLocationFieldArray($user);

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::IP_ADDRESS] = ($user->getIpAddress() ? $user->getIpAddress() : null);

        return $this->moderationRequest;
    }

    /**
     * Build other parameters array.
     *
     * @param User   $user                     User object.
     * @param number $priority                 Priority for moderation.
     * @param string $isForManualModeration    Whether you are sending ad for manual moderation or not.
     * @param string $manualModerationReason   What is the reason for manual moderation.
     */
    protected function buildOtherParametersArray(User $user, $priority = 1, $isForManualModeration = false, $manualModerationReason = '')
    {
        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::SITE_ID] = $this->container->getParameter('fa.business_user.moderation.site.id');

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::CALLBACK_URL] = $this->container->get('router')->generate('business_user_moderation_response', array(), true);

        if ($user->getId()) {
            $this->moderationRequest[BusinessUserModerationFieldMappingInterface::EDIT_CALLBACK_URL] = $this->container->get('router')->generate('user_show_admin', array("id" => $user->getId()), true);
        }

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::EXPECTED_RESPONSE] = null;

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::RESPONSE_DELAY_SECONDS] = null;

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::PRIORITY] = $priority;

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::IS_FOR_MANUAL_MODERATION] = $isForManualModeration;

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::MANUAL_MODERATION_REASON] = $manualModerationReason;
    }

    /**
     * Prepare array for basic field.
     *
     * @param User $user User object.
     */
    protected function buildBasicFieldArray(User $user)
    {
        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADREF] = ($user->getId() ? $user->getId() : null);

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::TITLE] = ($user->getBusinessName() ? $user->getBusinessName() : null);

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::SUBTITLE] = ($this->userSite && $this->userSite->getCompanyWelcomeMessage() ? $this->userSite->getCompanyWelcomeMessage() : null);

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::DESCRIPTION][0][BusinessUserModerationFieldMappingInterface::TYPE]   = 'online';
        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::DESCRIPTION][0][BusinessUserModerationFieldMappingInterface::DETAIL] = ($this->userSite && $this->userSite->getAboutUs() ? strip_tags($this->userSite->getAboutUs()) : null);

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::DESCRIPTION][1][BusinessUserModerationFieldMappingInterface::TYPE]   = 'print';
        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::DESCRIPTION][1][BusinessUserModerationFieldMappingInterface::DETAIL] = ($this->userSite && $this->userSite->getAboutUs() ? strip_tags($this->userSite->getAboutUs()) : null);

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::PRICE] = 0;

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::CURRENCY] = null;

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::IP_ADDRESS] = ($user->getIpAddress() ? $user->getIpAddress() : null);

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::DATE_CREATED] = $this->getDate($user->getCreatedAt());

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::DATE_MODIFIED] = $this->getDate(time());
    }

    /**
     * Build user field array to send.
     *
     * @param User $user User object.
     */
    protected function buildUserFieldArray(User $user)
    {
        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::EMAIL] = ($user ? $user->getEmail() : null);

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::USERNAME] = null;

        //build ad owner information array.
        $ownerInfo = array();
        if ($user) {
            $ownerObj = $user;
            $ownerInfo[BusinessUserModerationFieldMappingInterface::AD_OWNER_FIRSTNAME] = $ownerObj->getFirstName();
            $ownerInfo[BusinessUserModerationFieldMappingInterface::AD_OWNER_LASTNAME] = $ownerObj->getLastName();
            $ownerInfo[BusinessUserModerationFieldMappingInterface::AD_OWNER_USERTYPE] = ($ownerObj->getRole() ? $this->getUserType($ownerObj->getRole()->getId()) : null);
            $ownerInfo[BusinessUserModerationFieldMappingInterface::AD_OWNER_BUSINESSNAME] = $ownerObj->getBusinessName();
            $ownerInfo[BusinessUserModerationFieldMappingInterface::AD_OWNER_DATEREGISTERED] = $this->getDate($ownerObj->getCreatedAt());
        }
        //remove empty values
        array_filter($ownerInfo);

        if (count($ownerInfo)) {
            $this->moderationRequest[BusinessUserModerationFieldMappingInterface::AD_OWNER] = $ownerInfo;
        }
    }

    /**
     * Prepare classification array.
     *
     * @param User $user User object.
     */
    protected function buildClassificationArray(User $user)
    {
        if ($this->userSite && $this->userSite->getProfileExposureCategoryId()) {
            $categoryId = $this->userSite->getProfileExposureCategoryId();
        } else {
            $categoryId = $user->getBusinessCategoryId();
        }



        $categoryArray = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId);
        $classification = array();

        $i = 0;
        foreach ($categoryArray as $id => $title) {
            $classification[$i]['id']    = $id;
            $classification[$i]['title'] = $title;
            $i++;
        }

        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::CLASSIFICATION] = $classification;
    }

    /**
     * Prepare image array.
     *
     * @param User $user User object.
     */
    protected function buildImageArray(User $user)
    {
        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::IMAGES] = array();

        // user banner image url
        if ($this->userSite && $this->userSite->getId()) {
            $bannerUrl = $this->container->getParameter('fa.static.shared.url').'/'.$this->userSite->getBannerPath().'/banner_'.$this->userSite->getId().'.jpg?'.time();
            if (!preg_match("~^(?:ht)tps?://~i", $bannerUrl)) {
                $bannerUrl = str_replace('//', 'http://', $bannerUrl);
            }
            $this->moderationRequest[BusinessUserModerationFieldMappingInterface::IMAGES][] = $bannerUrl;
        }

        // user logo
        $userLogoUrl = CommonManager::getUserLogoByUserId($this->container, $user->getId(), true, true);
        if ($userLogoUrl) {
            if (!preg_match("~^(?:ht)tps?://~i", $userLogoUrl)) {
                $userLogoUrl = str_replace('//', 'http://', $userLogoUrl);
            }
            $this->moderationRequest[BusinessUserModerationFieldMappingInterface::IMAGES][] = $userLogoUrl;
        }
        //user site images
        if ($this->userSite && $this->userSite->getId()) {
            $userSiteImages = $this->em->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImages($this->userSite->getId());
            if (count($userSiteImages)) {
                foreach ($userSiteImages as $userSiteImage) {
                    $userSiteImageUrl = CommonManager::getUserSiteImageUrl($this->container, $this->userSite->getId(), $userSiteImage->getPath(), $userSiteImage->getHash(), '800X600');
                    if (!preg_match("~^(?:ht)tps?://~i", $userSiteImageUrl)) {
                        $userSiteImageUrl = str_replace('//', 'http://', $userSiteImageUrl);
                        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::IMAGES][] = $userSiteImageUrl;
                    }
                }
            }
        }
    }

    /**
     * Prepare additional field array.
     *
     * @param User $user User object.
     */
    protected function buildAdditionalFieldArray(User $user)
    {
        if ($this->userSite && $this->userSite->getId()) {
            $key = 0;

            //company address
            if ($this->userSite->getCompanyAddress()) {
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::KEY] = 'Company address';
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::VALUE] = $this->userSite->getCompanyAddress();
                $key++;
            }

            //Telephone(s)
            if ($this->userSite->getPhone1() || $this->userSite->getPhone2()) {
                $phones = array();
                $phones[] = $this->userSite->getPhone1();
                $phones[] = $this->userSite->getPhone2();
                $phones = array_filter($phones);
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::KEY] = 'Telephone(s)';
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::VALUE] = implode(', ', $phones);
                $key++;
            }

            //Website link
            if ($this->userSite->getWebsiteLink()) {
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::KEY] = 'Website link';
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::VALUE] = $this->userSite->getWebsiteLink();
                $key++;
            }

            //Social profiles
            if ($this->userSite->getFacebookUrl() || $this->userSite->getGoogleUrl() || $this->userSite->getTwitterUrl() || $this->userSite->getPinterestUrl() || $this->userSite->getInstagramUrl()) {
                $socialProfiles = array();
                $socialProfiles[] = $this->userSite->getFacebookUrl();
                $socialProfiles[] = $this->userSite->getGoogleUrl();
                $socialProfiles[] = $this->userSite->getTwitterUrl();
                $socialProfiles[] = $this->userSite->getPinterestUrl();
                $socialProfiles[] = $this->userSite->getInstagramUrl();
                $socialProfiles = array_filter($socialProfiles);
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::KEY] = 'Social profiles';
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::VALUE] = implode(', ', $socialProfiles);
                $key++;
            }

            //Video
            if ($this->userSite->getYoutubeVideoUrl()) {
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::KEY] = 'Video';
                $this->moderationRequest[BusinessUserModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][BusinessUserModerationFieldMappingInterface::VALUE] = $this->userSite->getYoutubeVideoUrl();
                $key++;
            }
        }
    }

    /**
     * Prepare ad location field array.
     *
     * @param User $user User object.
     */
    protected function buildAdLocationFieldArray(User $user)
    {
         //post code
         if ($user->getZip()) {
             $this->moderationRequest[BusinessUserModerationFieldMappingInterface::AD_POSTCODE] = $user->getZip();
         }
    }

    /**
     * Prepare other fild array.
     * Like phone number, is_paid etc...
     *
     * @param User $user User object.
     */
    protected function buildOtherFieldArray(User $user)
    {
        $userActivePackage = $this->em->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);
        $this->moderationRequest[BusinessUserModerationFieldMappingInterface::PAID] = (($userActivePackage && $userActivePackage->getPackage() && $userActivePackage->getPackage()->getPrice() > 0)  ? true : false);
    }

    /**
     * Get date.
     *
     * @param string $timestamp Time stamp.
     */
    protected function getDate($timestamp)
    {
        // set the default timezone to use. Available since PHP 5.1
        $currentTimezone = date_default_timezone_get();
        date_default_timezone_set("UTC");
        $date = date('c', $timestamp);
        $timezone = date_default_timezone_set($currentTimezone);
        return $date;
    }

    /**
     * Send request to ad moderation url.
     *
     * @param string $requestBody Request body.
     *
     * @return boolean
     */
    public function sendRequest($requestBody)
    {
        $url = $this->container->getParameter('fa.ad.moderation.api.url').'/'.$this->container->getParameter('fa.ad.moderation.api.version').'/appkey/'.$this->container->getParameter('fa.ad.moderation.api.appKey').'/ModerationRequest';

        // Build the HTTP Request Headers
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        //curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HEADER, true);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpcode !== 200) {
            return false;
        }

        return true;
    }

    /**
     * Get user type.
     *
     * @return array
     */
    public function getUserType($roleId)
    {
        $roles = array(
            RoleRepository::ROLE_SELLER_ID => 'Private Seller',
            RoleRepository::ROLE_BUSINESS_SELLER_ID => 'Business Seller',
        );

        return (isset($roles[$roleId]) ? $roles[$roleId] : null);
    }
}
