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

use Fa\Bundle\MessageBundle\Entity\Message;
use Fa\Bundle\MessageBundle\Moderation\ContactModerationFieldMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
// use Symfony\Component\Locale\Stub\DateFormat;
use Symfony\Component\Intl\DateFormatter\DateFormat;

/**
 * This controller is used for contact moderation.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ContactModerationRequestBuild
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
    public function init(Message $message)
    {
        $this->buildOtherParametersArray();

        $this->buildBasicFieldArray($message);

        $this->buildClassificationArray($message);

        $this->buildAttachmentsArray($message);

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
    protected function buildBasicFieldArray(Message $message)
    {
        $this->moderationRequest[ContactModerationFieldMappingInterface::SENDER] = ($message->getSenderEmail() ? $message->getSenderEmail() : null);

        $this->moderationRequest[ContactModerationFieldMappingInterface::RECIPIENT] = ($message->getReceiverEmail() ? $message->getReceiverEmail() : null);

        $this->moderationRequest[ContactModerationFieldMappingInterface::SUBJECT] = ($message->getSubject() ? $message->getSubject() : 'Your Advert on Friday-Ad');

        $this->moderationRequest[ContactModerationFieldMappingInterface::BODY] = ($message->getHtmlMessage() ? $message->getHtmlMessage() : ($message->getTextMessage() ? $message->getTextMessage(): null));

        $this->moderationRequest[ContactModerationFieldMappingInterface::IP_ADDRESS] = ($message->getIpAddress() ? $message->getIpAddress() : 0);

        $mainMessage = $this->container->get('doctrine')->getManager()->getRepository('FaMessageBundle:Message')->getMainMessage($message->getId());

        if ($mainMessage) {
            $this->moderationRequest[ContactModerationFieldMappingInterface::THREAD_ID] = $mainMessage->getId();
        } else {
            $this->moderationRequest[ContactModerationFieldMappingInterface::THREAD_ID] = $message->getId();
        }

        $this->moderationRequest[ContactModerationFieldMappingInterface::SUPPLEMENTARY_INFORMATION] = $message->getId();

        if ($message->getAd()) {
            $this->moderationRequest[ContactModerationFieldMappingInterface::ADREF] = $message->getAd()->getId();
        }
    }

    /**
     * Prepare classification array.
     *
     * @param Message $message Message object.
     */
    protected function buildClassificationArray(Message $message)
    {
        if ($message->getAd() && $message->getAd()->getCategory()) {
            $categoryId = $message->getAd()->getCategory()->getId();

            $categoryArray = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId);
            //$categoryArray = array_reverse($categoryArray, true);
            $classification = array();

            $i = 0;
            $parentId = 0;
            foreach ($categoryArray as $id => $title) {
                $classification[$i]['id']       = $id;
                $classification[$i]['parentid'] = $parentId;
                $classification[$i]['title']    = $title;
                $parentId = $id;
                $i++;
            }

            $this->moderationRequest[ContactModerationFieldMappingInterface::CATEGORIES] = $classification;
        }
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
        $url = $this->container->getParameter('fa.contact.moderation.api.url').'/'.$this->container->getParameter('fa.contact.moderation.api.version').'/appkey/'.$this->container->getParameter('fa.contact.moderation.api.appKey').'/startmoderation';

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
     * Prepare classification array.
     *
     * @param Message $objMessage Message object.
     */
    protected function buildAttachmentsArray(Message $objMessage)
    {
        if ($objMessage) {
            $objMessageAttachments = $this->container->get('doctrine')->getManager()->getRepository('FaMessageBundle:MessageAttachments')->getMessageAttachments($objMessage->getId());
            $attachmentsArray      = array();
            $i                     = 0;

            foreach ($objMessageAttachments as $key => $objMessageAttachment) {
                $fileExtension                       = substr(strrchr($objMessageAttachment->getOriginalFileName(), '.'), 1);
                $fileName                            = $objMessageAttachment->getSessionId().'_'.$objMessageAttachment->getHash().'.'.$fileExtension;
                $attachmentsArray[$i]['Bytes']       = $objMessageAttachment->getSize();
                $attachmentsArray[$i]['ContentType'] = $objMessageAttachment->getMimeType();
                $attachmentsArray[$i]['Location']    = CommonManager::getMessageAttachmentUrl($this->container, $objMessageAttachment);
                $attachmentsArray[$i]['Name']        = $fileName;
                $i++;
            }

            $this->moderationRequest[ContactModerationFieldMappingInterface::MESSAGE_ATTACHMENTS] = $attachmentsArray;
        }
    }
}
