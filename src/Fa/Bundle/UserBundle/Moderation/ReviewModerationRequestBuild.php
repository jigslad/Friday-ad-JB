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

use Fa\Bundle\UserBundle\Entity\UserReview;
use Fa\Bundle\UserBundle\Moderation\ReviewModerationFieldMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
// use Symfony\Component\Locale\Stub\DateFormat;
use Symfony\Component\Intl\DateFormatter\DateFormat;

/**
 * This manager is used for review moderation.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ReviewModerationRequestBuild
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
    }

    /**
     * Initialize moderation request build.
     *
     * @param UserReview $userReview               UserReview object.
     * @param string     $isForManualModeration    Whether you are sending ad for manual moderation or not.
     *
     * @return array
     */
    public function init(UserReview $userReview, $isForManualModeration = false)
    {

        $this->buildOtherParametersArray($userReview, $isForManualModeration);

        $this->buildBasicFieldArray($userReview);

        $this->buildUserFieldArray($userReview);

        $this->buildClassificationArray($userReview);

        $this->buildAdditionalFieldArray($userReview);

        return $this->moderationRequest;
    }

    /**
     * Build other parameters array.
     *
     * @param UserReview $userReview               UserReview object.
     * @param string     $isForManualModeration    Whether you are sending ad for manual moderation or not.
     */
    protected function buildOtherParametersArray(UserReview $userReview, $isForManualModeration = false)
    {
        $this->moderationRequest[ReviewModerationFieldMappingInterface::SITE_ID] = $this->container->getParameter('fa.review.moderation.site.id');

        $this->moderationRequest[ReviewModerationFieldMappingInterface::CALLBACK_URL] = $this->container->get('router')->generate('review_moderation_response', array(), true);

        $this->moderationRequest[ReviewModerationFieldMappingInterface::IS_FOR_MANUAL_MODERATION] = $isForManualModeration;
    }

    /**
     * Prepare array for basic field.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildBasicFieldArray(UserReview $userReview)
    {
        $this->moderationRequest[ReviewModerationFieldMappingInterface::REVIEW_ID] = ($userReview->getId() ? $userReview->getId() : null);

        $this->moderationRequest[ReviewModerationFieldMappingInterface::TITLE] = ($userReview->getMessage() ? implode(' ', array_slice(explode(' ', $userReview->getMessage()), 0, 5)) : null);

        $this->moderationRequest[ReviewModerationFieldMappingInterface::DESCRIPTION][0][ReviewModerationFieldMappingInterface::TYPE] = 'online';
        $this->moderationRequest[ReviewModerationFieldMappingInterface::DESCRIPTION][0][ReviewModerationFieldMappingInterface::DETAIL] = ($userReview->getMessage() ? strip_tags($userReview->getMessage()) : null);

        $this->moderationRequest[ReviewModerationFieldMappingInterface::PRICE] = 0;

        $this->moderationRequest[ReviewModerationFieldMappingInterface::CURRENCY] = CommonManager::getCurrencyCode($this->container);

        $this->moderationRequest[ReviewModerationFieldMappingInterface::IP_ADDRESS] = ($userReview->getIpAddress() ? $userReview->getIpAddress() : null);

        $this->moderationRequest[ReviewModerationFieldMappingInterface::DATE_CREATED] = ($userReview->getCreatedAt() ? $this->getDate($userReview->getCreatedAt()) : null);
    }

    /**
     * Build user field array to send.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildUserFieldArray(UserReview $userReview)
    {
        $this->moderationRequest[ReviewModerationFieldMappingInterface::EMAIL] = ($userReview->getReviewer() ? $userReview->getReviewer()->getEmail() : null);

        $this->moderationRequest[ReviewModerationFieldMappingInterface::USERNAME] = ($userReview->getReviewer() ? $userReview->getReviewer()->getEmail() : null);
    }

    /**
     * Prepare classification array.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildClassificationArray(UserReview $userReview)
    {
        $classification = array();

        $i = 0;
        $classification[$i]['id']    = 1;
        $classification[$i]['title'] = 'Reviews';

        $this->moderationRequest[ReviewModerationFieldMappingInterface::CLASSIFICATION] = $classification;
    }

    /**
     * Prepare additional field array.
     *
     * @param Ad $ad Ad object.
     */
    protected function buildAdditionalFieldArray(UserReview $userReview)
    {
        $key = 0;

        $this->moderationRequest[ReviewModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][ReviewModerationFieldMappingInterface::KEY] = 'email';
        $this->moderationRequest[ReviewModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][ReviewModerationFieldMappingInterface::VALUE] = $userReview->getUser()->getEmail();
        $key++;

        if ($userReview->getAd()) {
            $this->moderationRequest[ReviewModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][ReviewModerationFieldMappingInterface::KEY] = 'adref';
            $this->moderationRequest[ReviewModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][ReviewModerationFieldMappingInterface::VALUE] = $userReview->getAd()->getId();
            $key++;
        }

        if ($userReview->getRating()) {
            $ratings = CommonManager::getStraRatingLabels($this->container);
            $this->moderationRequest[ReviewModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][ReviewModerationFieldMappingInterface::KEY] = 'rating';
            $this->moderationRequest[ReviewModerationFieldMappingInterface::ADDITIONAL_FIELDS][$key][ReviewModerationFieldMappingInterface::VALUE] = $ratings[$userReview->getRating()];
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
        $url = $this->container->getParameter('fa.review.moderation.api.url').'/'.$this->container->getParameter('fa.review.moderation.api.version').'/appkey/'.$this->container->getParameter('fa.review.moderation.api.appKey').'/ModerationRequest';

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
}
