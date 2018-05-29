<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Moderation;

use Fa\Bundle\MessageBundle\Entity\MessageSpammer;
use Fa\Bundle\MessageBundle\Moderation\ContactModerationFieldMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for contact moderation.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ContactModerationReportRequestBuild
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
     * @param Message $message Message object.
     *
     * @return array
     */
    public function init(MessageSpammer $messageSpammer)
    {
        $this->buildOtherParametersArray();

        $this->buildBasicFieldArray($messageSpammer);

        return $this->moderationRequest;
    }

    /**
     * Build other parameters array.
     */
    protected function buildOtherParametersArray()
    {
        $this->moderationRequest[ContactModerationFieldMappingInterface::SITE_ID] = $this->container->getParameter('fa.contact.moderation.site.id');

        //$this->moderationRequest[ContactModerationFieldMappingInterface::CALLBACK_URL] = $this->container->get('router')->generate('contact_moderation_response', array(), true);
    }

    /**
     * Prepare array for basic field.
     *
     * @param Message $message Message object.
     */
    protected function buildBasicFieldArray(MessageSpammer $messageSpammer)
    {
        $this->moderationRequest[ContactModerationFieldMappingInterface::THREAD_ID] = $messageSpammer->getMessage()->getId();

        $this->moderationRequest[ContactModerationFieldMappingInterface::COMMENT] = ($messageSpammer->getReason() ? $messageSpammer->getReason() : null);

        $this->moderationRequest[ContactModerationFieldMappingInterface::REPORTED_EMAIL_ADDRESS] = ($messageSpammer->getSpammer() ? $messageSpammer->getSpammer()->getEmail() : null);
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
        $url = $this->container->getParameter('fa.contact.moderation.api.url').'/'.$this->container->getParameter('fa.contact.moderation.api.version').'/appkey/'.$this->container->getParameter('fa.contact.moderation.api.appKey').'/moderationfeedback';

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
