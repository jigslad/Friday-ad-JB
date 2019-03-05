<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Moderation;

use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Moderation\AdModerationFieldMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
// use Symfony\Component\Locale\Stub\DateFormat;
use Symfony\Component\Intl\DateFormatter\DateFormat;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;

/**
 * This controller is used for content moderation.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdModerationRequestBuild
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Moderation request array.
     *
     * @var array
     */
    private $moderationRequest = array();

    /**
     * Serialized moderated value.
     *
     * @var array
     */
    private $values = array();

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->values = array();
        $this->moderationRequest = array();
    }

    /**
     * Initialize moderation request build.
     *
     * @param Ad     $ad                       Ad object.
     * @param string $values                   Seriazlied moderated values.
     * @param number $priority                 Priority for moderation.
     * @param string $isForManualModeration    Whether you are sending ad for manual moderation or not.
     * @param string $manualModerationReason   What is the reason for manual moderation.
     *
     * @return array
     */
    public function init(Ad $ad, $values, $priority = 1, $isForManualModeration = false, $manualModerationReason = '',$remoderation = false )
    {
        $this->values = array();
        $this->values = unserialize($values);

        if (isset($this->values['ad'])) {
            foreach ($this->values['ad'] as $array) {
                $ad = CommonManager::convertArrayToDoctrineObject($ad, $array);
            }
        }

        $this->buildOtherParametersArray($ad, $priority, $isForManualModeration, $manualModerationReason,$remoderation);

        $this->buildBasicFieldArray($ad);

        $this->buildUserFieldArray($ad);

        $this->buildClassificationArray($ad);

        $this->buildImageArray($ad);

        $this->buildAdditionalFieldArray($ad);

        $this->buildOtherFieldArray($ad);

        $this->buildAdLocationFieldArray($ad);

        return $this->moderationRequest;
    }

    /**
     * Build other parameters array.
     *
     * @param Ad     $ad                       Ad object.
     * @param number $priority                 Priority for moderation.
     * @param string $isForManualModeration    Whether you are sending ad for manual moderation or not.
     * @param string $manualModerationReason   What is the reason for manual moderation.
     */
    protected function buildOtherParametersArray(Ad $ad, $priority = 1, $isForManualModeration = false, $manualModerationReason = '',$remoderation=false)
    {
        if(!$remoderation) {
            $baseUrl = $this->container->getParameter('base_url');
        } else { $baseUrl = ''; }
        $this->moderationRequest[AdModerationFieldMappingInterface::SITE_ID] = $this->container->getParameter('fa.ad.moderation.site.id');

        $this->moderationRequest[AdModerationFieldMappingInterface::CALLBACK_URL] = $baseUrl.$this->container->get('router')->generate('ad_moderation_response', array(), true);

        if ($ad->getId()) {
            $this->moderationRequest[AdModerationFieldMappingInterface::EDIT_CALLBACK_URL] = $baseUrl.$this->container->get('router')->generate('ad_post_edit_admin', array("id" => $ad->getId()), true);
        }

        $this->moderationRequest[AdModerationFieldMappingInterface::EXPECTED_RESPONSE] = null;

        $this->moderationRequest[AdModerationFieldMappingInterface::RESPONSE_DELAY_SECONDS] = null;

        $this->moderationRequest[AdModerationFieldMappingInterface::PRIORITY] = $priority;

        $this->moderationRequest[AdModerationFieldMappingInterface::IS_FOR_MANUAL_MODERATION] = $isForManualModeration;

        $this->moderationRequest[AdModerationFieldMappingInterface::MANUAL_MODERATION_REASON] = $manualModerationReason;
    }

    /**
     * Prepare array for basic field.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildBasicFieldArray(Ad $ad)
    {
        $this->moderationRequest[AdModerationFieldMappingInterface::ADREF] = ($ad->getId() ? $ad->getId() : null);

        $this->moderationRequest[AdModerationFieldMappingInterface::TITLE] = ($ad->getTitle() ? $ad->getTitle() : null);

        $this->moderationRequest[AdModerationFieldMappingInterface::SUBTITLE] = ($ad->getPersonalizedTitle() ? $ad->getPersonalizedTitle() : null);

        $this->moderationRequest[AdModerationFieldMappingInterface::DESCRIPTION][0][AdModerationFieldMappingInterface::TYPE]   = 'online';
        $this->moderationRequest[AdModerationFieldMappingInterface::DESCRIPTION][0][AdModerationFieldMappingInterface::DETAIL] = ($ad->getDescription() ? strip_tags($ad->getDescription()) : null);

        $this->moderationRequest[AdModerationFieldMappingInterface::DESCRIPTION][1][AdModerationFieldMappingInterface::TYPE]   = 'print';
        $this->moderationRequest[AdModerationFieldMappingInterface::DESCRIPTION][1][AdModerationFieldMappingInterface::DETAIL] = ($ad->getDescription() ? strip_tags($ad->getDescription()) : null);

        $this->moderationRequest[AdModerationFieldMappingInterface::PRICE] = ($ad->getPrice() ? $ad->getPrice() : 0);

        $this->moderationRequest[AdModerationFieldMappingInterface::CURRENCY] = CommonManager::getCurrencyCode($this->container);

        $this->moderationRequest[AdModerationFieldMappingInterface::IP_ADDRESS] = ($ad->getModifyIp() ? $ad->getModifyIp() : null);

        $this->moderationRequest[AdModerationFieldMappingInterface::DATE_CREATED] = ($ad->getCreatedAt() ? $this->getDate($ad->getCreatedAt()) : null);

        $this->moderationRequest[AdModerationFieldMappingInterface::DATE_MODIFIED] = ($ad->getUpdatedAt() ? $this->getDate($ad->getUpdatedAt()) : null);
    }

    /**
     * Build user field array to send.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildUserFieldArray(Ad $ad)
    {
        $this->moderationRequest[AdModerationFieldMappingInterface::EMAIL] = ($ad->getUser() ? $ad->getUser()->getEmail() : null);

        $this->moderationRequest[AdModerationFieldMappingInterface::USERNAME] = ($ad->getUser() ? $ad->getUser()->getUsername() : null);

        //build ad owner information array.
        $ownerInfo = array();
        if ($ad->getUser()) {
            $ownerObj = $ad->getUser();
            $ownerInfo[AdModerationFieldMappingInterface::AD_OWNER_FIRSTNAME] = $ownerObj->getFirstName();
            $ownerInfo[AdModerationFieldMappingInterface::AD_OWNER_LASTNAME] = $ownerObj->getLastName();
            $ownerInfo[AdModerationFieldMappingInterface::AD_OWNER_USERTYPE] = ($ownerObj->getRole() ? $this->getUserType($ownerObj->getRole()->getId()) : null);
            $ownerInfo[AdModerationFieldMappingInterface::AD_OWNER_BUSINESSNAME] = $ownerObj->getBusinessName();
            $ownerInfo[AdModerationFieldMappingInterface::AD_OWNER_DATEREGISTERED] = $this->getDate($ownerObj->getCreatedAt());
        }
        //remove empty values
        array_filter($ownerInfo);

        if (count($ownerInfo)) {
            $this->moderationRequest[AdModerationFieldMappingInterface::AD_OWNER] = $ownerInfo;
        }
    }

    /**
     * Prepare classification array.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildClassificationArray(Ad $ad)
    {
        if (isset($this->values['category_id'])) {
            $categoryId = $this->values['category_id'];
        } else {
            $categoryId = $ad->getCategory()->getId();
        }

        $categoryArray = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId);
        $classification = array();

        $i = 0;
        foreach ($categoryArray as $id => $title) {
            $classification[$i]['id']    = $id;
            $classification[$i]['title'] = $title;
            $i++;
        }

        $this->moderationRequest[AdModerationFieldMappingInterface::CLASSIFICATION] = $classification;
    }

    /**
     * Prepare image array.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildImageArray(Ad $ad)
    {
        $this->moderationRequest[AdModerationFieldMappingInterface::IMAGES] = array();

        if (isset($this->values['images'])) {
            $images = $this->values['images'];

            foreach ($images as $key => $array) {
                $image = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdImage')->findOneBy(array('id' => $array['id']));
                if ($image) {
                    $imageUrl = CommonManager::getAdImageUrl($this->container, $ad->getId(), $image->getPath(), $image->getHash(), null, $image->getAws(), $image->getImageName());
                    if (!preg_match("~^(?:ht)tps?://~i", $imageUrl)) {
                        $imageUrl = str_replace('//', 'https://', $imageUrl);
                    }

                    $this->moderationRequest[AdModerationFieldMappingInterface::IMAGES][] = $imageUrl;
                }
            }
        }
    }

    /**
     * Prepare additional field array.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildAdditionalFieldArray(Ad $ad)
    {
        if (isset($this->values['category_id'])) {
            $categoryId = $this->values['category_id'];
        } else {
            $categoryId = $ad->getCategory()->getId();
        }

        $object = null;

        $rootCategoryId = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
        $className      = CommonManager::getCategoryClassNameById($rootCategoryId, true);
        $repository     = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:'.'Ad'.$className);

        if (isset($this->values['dimensions'])) {
            foreach ($this->values['dimensions'] as $array) {
                $object = $repository->findOneBy(array('ad' => $ad->getId()));
                if ($object) {
                    $object = CommonManager::convertArrayToDoctrineObject($object, $array);
                }
            }
        }

        $this->moderationRequest[AdModerationFieldMappingInterface::ADDITIONAL_FIELDS] = array();
        $key = 0;
        if ($object) {
            $metaData = ($object->getMetaData() ? unserialize($object->getMetaData()) : null);
            $paaFields = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:PaaField')->getDimensionPaaFieldsWithLabel($categoryId);
            foreach ($paaFields as $field => $label) {
                $value = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($field, $object, $metaData, $this->container, $className);
                if ($value != null) {
                    $this->moderationRequest[AdModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][AdModerationFieldMappingInterface::KEY] = $label;
                    $this->moderationRequest[AdModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][AdModerationFieldMappingInterface::VALUE] = $value;
                    $key++;
                }
            }
        }
    }

    /**
     * Prepare ad location field array.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildAdLocationFieldArray(Ad $ad)
    {
        $adModerateLocation = array();
        $postalcodeVal = null;
        $adModerateData = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdModerate')->getLatestLocation($ad->getId());
        if ($adModerateData) {
            $unserializeModerationvalue = unserialize($adModerateData->getvalue());
            $adModerateLocation = $unserializeModerationvalue['locations'][0];
            if (isset($adModerateLocation['latitude']) && $adModerateLocation['latitude']!='' && $adModerateLocation['latitude']!=0.00000000 && $adModerateLocation['longitude']!='' && $adModerateLocation['longitude']!=0.00000000) {
                $postalcodeVal = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Postcode')->getPostCodTextByLatLong($adModerateLocation['latitude'], $adModerateLocation['longitude']);
            }
        }
        
        $adLocation = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdLocation')->getLatestLocation($ad->getId());
        
        if (!empty($adModerateLocation)) {
            if ($adLocation && $adLocation->getLocationArea() && $adLocation->getPostcode()) {
                $this->moderationRequest[AdModerationFieldMappingInterface::AD_POSTCODE] = $adLocation->getPostcode();
            } else {
                $this->moderationRequest[AdModerationFieldMappingInterface::AD_POSTCODE] = $postalcodeVal;
            }
            if (isset($adModerateLocation['town_id'])) {
                $this->moderationRequest[AdModerationFieldMappingInterface::AD_TOWN] = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Location')->getNameById($adModerateLocation['town_id']);
            } else {
                $this->moderationRequest[AdModerationFieldMappingInterface::AD_TOWN] = null;
            }
        } else {
            if ($adLocation) {
                //post code
                if ($adLocation->getPostcode()) {
                    $this->moderationRequest[AdModerationFieldMappingInterface::AD_POSTCODE] = $adLocation->getPostcode();
                }

                //town
                if ($adLocation->getLocationTown()) {
                    $this->moderationRequest[AdModerationFieldMappingInterface::AD_TOWN] = $adLocation->getLocationTown()->getName();
                }
            }
        }
    }

    /**
     * Prepare other fild array.
     * Like phone number, is_paid etc...
     *
     * @param Ad $ad Ad object.
     */
    protected function buildOtherFieldArray(Ad $ad)
    {
        $this->moderationRequest[AdModerationFieldMappingInterface::PHONE_NUMBER] = array();
        $this->moderationRequest[AdModerationFieldMappingInterface::PHONE_NUMBER][] = (($ad->getUser() && $ad->getUser()->getPhone()) ? $ad->getUser()->getPhone() : null);
        $this->moderationRequest[AdModerationFieldMappingInterface::YAC]            = (($ad->getUser() && $ad->getUser()->getIsPrivatePhoneNumber()) ? true : false);
        $this->moderationRequest[AdModerationFieldMappingInterface::PAID]           = ($ad->getIsPaidAd() ? true : false);
        $this->moderationRequest[AdModerationFieldMappingInterface::PAID_BEFORE]    = (($ad->getUser() && $ad->getUser()->getIsPaidBefore()) ? true : false);

        // check for buy now
        if ($ad->getPaymentMethodId() && ($ad->getPaymentMethodId() == PaymentRepository::PAYMENT_METHOD_PAYPAL_ID || $ad->getPaymentMethodId() == PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID)) {
            $this->moderationRequest[AdModerationFieldMappingInterface::ISBUYITNOW] = true;
        } else {
            $this->moderationRequest[AdModerationFieldMappingInterface::ISBUYITNOW] = false;
        }
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
            RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID => 'Netsuite Subscription Users',
        );

        return (isset($roles[$roleId]) ? $roles[$roleId] : null);
    }
}
