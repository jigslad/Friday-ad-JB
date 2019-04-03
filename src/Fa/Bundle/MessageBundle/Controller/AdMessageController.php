<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\MessageBundle\Repository\MessageRepository;
use Doctrine\ORM\Query;
use Fa\Bundle\MessageBundle\Entity\Message;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\MessageBundle\Entity\MessageSpammer;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Fa\Bundle\MessageBundle\Form\MessageAdType;
use Fa\Bundle\MessageBundle\Form\ReportUserType;

/**
 * This controller is used for user ad messages.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdMessageController extends CoreController
{
    /**
     * Show user ad messages.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function userAdMessageAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $type = $request->get('type', 'all');

        $messageDetailArray = array();
        $userLogosArray     = array();

        $query = $this->getRepository('FaMessageBundle:Message')->getUserMessageIdsQuery($this->getLoggedInUser()->getId(), $type);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\MessageBundle\Walker\MessageCountSqlWalker');
        $query->setHint("messageCountSqlWalkerSqlWalker.replaceCountField", true);

        $unreadUserAdMsgCount           = $this->getRepository('FaMessageBundle:Message')->getMessageCount($this->getLoggedInUser()->getId(), 'receiver', $this->container);
        $unreadUserInterestedAdMsgCount = $this->getRepository('FaMessageBundle:Message')->getMessageCount($this->getLoggedInUser()->getId(), 'sender', $this->container);
        $totalMsgCount                  = $this->getRepository('FaMessageBundle:Message')->getMessageCount($this->getLoggedInUser()->getId(), 'all', $this->container);

        $this->get('fa.pagination.manager')->init($query, $request->get('page', 1));
        $pagination = $this->get('fa.pagination.manager')->getPagination(false);

        if ($pagination->getNbResults()) {
            $messageIds = array();
            $senderIds  = array();
            foreach ($pagination->getCurrentPageResults() as $messageArray) {
                if ($messageArray['msg_id'] < 0) {
                    $lastMessageId = $this->getRepository('FaMessageBundle:Message')->getLastConversationId($messageArray['msg_id']);
                    if ($lastMessageId) {
                        $messageArray['msg_id'] = $lastMessageId;
                    }
                }

                $messageIds[] = $messageArray['msg_id'];
                $messageDetailArray[$messageArray['msg_id']]['ad_id']    = $messageArray['ad_id'];
                $messageDetailArray[$messageArray['msg_id']]['ad_title'] = $messageArray['ad_title'];
                $messageDetailArray[$messageArray['msg_id']]['subject'] = $messageArray['subject'];
                $messageDetailArray[$messageArray['msg_id']]['message_ad_id'] = $messageArray['message_ad_id'];
            }

            if (count($messageIds) > 0) {
                $messageDetails = $this->getRepository('FaMessageBundle:Message')->getUserMessageDetailsByIds($messageIds);
                if ($messageDetails && is_array($messageDetails)) {
                    foreach ($messageDetails as $msgDetail) {
                        $msgId = $msgDetail['id'];
                        $senderIds[] = $msgDetail['sender_id'];
                        $senderIds[] = $msgDetail['receiver_id'];
                        $messageDetailArray[$msgId] = $messageDetailArray[$msgId]+$msgDetail;
                    }
                }
            }

            if (count($senderIds) > 0) {
                $userLogosArray = $this->getRepository('FaUserBundle:User')->getUserDetailForMessage($senderIds);
            }
        }

        $parameters = array(
                        'pagination'                     => $pagination,
                        'totalMsgCount'                  => $totalMsgCount,
                        'unreadUserAdMsgCount'           => $unreadUserAdMsgCount,
                        'unreadUserInterestedAdMsgCount' => $unreadUserInterestedAdMsgCount,
                        'messageDetailArray'             => $messageDetailArray,
                        'userLogosArray'                 => $userLogosArray);

        return $this->render('FaMessageBundle:AdMessage:userAdMessage.html.twig', $parameters);
    }

    /**
     * Show user ad reply message.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function userAdMessageReplyEmailAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        if ($request->get('replyId')) {
            $message = $this->getRepository('FaMessageBundle:Message')->find($request->get('replyId'));
            if ($message) {
                $queryParams = $request->query->all();
                $adId           = $message->getMessageAdId();
                $userId1        = $message->getReceiver()->getId();
                $userId2        = $message->getSender()->getId();
                $lastMessageObj = $this->getRepository('FaMessageBundle:Message')->getLastConversionOfTwoUsersForAd($adId, $userId1, $userId2);
                $loggedinUser   = $this->getLoggedInUser();

                if ($lastMessageObj && $lastMessageObj->getReceiver()->getId() == $loggedinUser->getId()) {
                    $messageType = 'receiver';
                } else {
                    $messageType = 'sender';
                }

                return $this->redirectToRoute('user_ad_message_reply', array_merge(array('type' => $messageType, 'replyId' => $lastMessageObj->getId()) + $queryParams));
            }
        }

        throw $this->createNotFoundException($this->get('translator')->trans('Unable to find message conversation.'));
    }

    /**
     * Show user ad reply message.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function userAdMessageReplyAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $replyId = $request->get('replyId');
        $message = $this->getRepository('FaMessageBundle:Message')->find($replyId);

        $fullConversation = $this->getRepository('FaMessageBundle:Message')->getFullconversation($replyId, $this->getLoggedInUser()->getId());

        if (!$message || !$message->getReceiver() || ($message->getReceiver()->getId() != $this->getLoggedInUser()->getId() && $message->getSender()->getId() != $this->getLoggedInUser()->getId())) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You do not have permission to access this resource.', array(), 'frontend-inbox'));
            return $this->redirectToRoute('user_ad_message');
        }

        if ($message->getSender()->getId() != $this->getLoggedInUser()->getId()) {
            //update read message
            $this->getRepository('FaMessageBundle:Message')->updateIsRead($replyId, $this->getLoggedInUser()->getId(), $this->container);
        }

        $formManager = $this->get('fa.formmanager');
        $message     = new Message();
        $form        = $formManager->createForm(MessageAdType::class, $message);

        $parameters = array(
            'message'          => $message,
            'fullConversation' => $fullConversation,
            'form'             => $form->createView(),
            'replyMessageId'   => $replyId,
        );

        return $this->render('FaMessageBundle:AdMessage:userAdMessageReply.html.twig', $parameters);
    }

    /**
     * Save ad reply message.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxUserAdMessageReplySaveAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) === true && $request->isXmlHttpRequest()) {
            $error        = '';
            $successMsg   = '';
            $newReplyId   = '';
            $messagesHtml = '';
            $replyId      = $request->get('replyId', 0);
            $sessionId    = $request->get('sessionId');
            $adMessage    = $this->getRepository('FaMessageBundle:Message')->find($replyId);
            $loggedinUser = $this->getLoggedInUser();

            //validate user and message
            if (!$adMessage || ($adMessage && $adMessage->getReceiver()->getId() != $loggedinUser->getId() && $adMessage->getSender()->getId() != $loggedinUser->getId())) {
                $error = $this->get('translator')->trans('You do not have permission to access this resource.', array(), 'frontend-inbox');
                return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
            }

            //If receiver is active than only allow to message
            if ($adMessage->getReceiver()->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                $error = $this->get('translator')->trans('Sorry, receiver is not active!', array(), 'frontend-inbox');
                return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
            }

            $formManager = $this->get('fa.formmanager');
            $message     = new Message();
            $form        = $formManager->createForm(MessageAdType::class, $message);

            $formParams = $request->get('fa_message_message_ad');
            if (!isset($formParams['text_message']) || !strlen(trim($formParams['text_message']))) {
                $error = $this->get('translator')->trans('Please enter message.', array(), 'frontend-inbox');
                return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
            }
            $form->submit($formParams);

            if ($form->isValid()) {
                $message = $this->saveUserAdMessage($message, $adMessage, $form, $request);
                if ($sessionId != '') {
                    $message->setHasAttachments(1);
                }
                $message = $formManager->save($message);

                if ($sessionId != '' && $message && $message->getId()) {
                    $ans = $this->moveAttachmentsTmpToActualFolder($sessionId, $message->getId());
                }

                // send message into moderation.
                try {
                    $this->getRepository('FaMessageBundle:Message')->sendContactIntoModeration($message, $this->container);
                } catch (\Exception $e) {
                    // No need do take any action as we have cron
                    //  in background to again send the request.
                }

                $newReplyId = $message->getId();
                $successMsg = $this->get('translator')->trans('Message successfully sent.', array(), 'frontend-inbox');
                $fullConversation = $this->getRepository('FaMessageBundle:Message')->getFullconversation($replyId, $loggedinUser->getId());
                $messagesHtml = $this->renderView('FaMessageBundle:AdMessage:showReplyMessage.html.twig', array('fullConversation' => $fullConversation));
            } else {
                $error = $form->getErrors(true, false);
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg, 'newReplyId' => $newReplyId, 'messagesHtml' => $messagesHtml));
        }

        return new Response();
    }

    /**
     * Save user ad message.
     *
     * @param object $message       Message object.
     * @param object $parentMessage Parent message object.
     * @param object $form          Form object.
     * @param object $request       Request objext.
     *
     * @return Message object.
     */
    private function saveUserAdMessage($message, $parentMessage, $form, $request)
    {
        $type = $request->get('type', 0);

        if ($parentMessage) {
            $ad           = $parentMessage->getAd();
            $receiver     = $parentMessage->getReceiver();
            $sender       = $parentMessage->getSender();
            $loggedinUser = $this->getLoggedInUser();



            if (($type == 'receiver' && $sender->getId() != $loggedinUser->getId())) {
                $message->setSender($loggedinUser);
                $message->setSenderFirstName($loggedinUser->getProfileName());
                $message->setSenderEmail($loggedinUser->getEmail());
                $message->setReceiver($sender);
                $message->setReceiverFirstName($sender->getProfileName());
                $message->setReceiverEmail($sender->getEmail());

                if ($parentMessage->getId() != '1') {
                    $originatorID = $parentMessage->getOriginatorId();
                } else {
                    $originatorID = $loggedinUser->getId();
                }
                $message->setOriginatorId($originatorID);
            } elseif (($type == 'receiver' && $sender->getId() == $loggedinUser->getId()) || ($type == 'sender' && $receiver->getId() != $loggedinUser->getId())) {
                $message->setSender($loggedinUser);
                $message->setSenderFirstName($loggedinUser->getProfileName());
                $message->setSenderEmail($loggedinUser->getEmail());
                $message->setReceiver($receiver);
                $message->setReceiverFirstName($receiver->getProfileName());
                $message->setReceiverEmail($receiver->getEmail());

                if ($parentMessage->getId() != '1') {
                    $originatorID = $parentMessage->getOriginatorId();
                } else {
                    $originatorID = $loggedinUser->getId();
                }
                $message->setOriginatorId($originatorID);
            } elseif (($type == 'sender' && $receiver->getId() == $loggedinUser->getId())) {
                $message->setSender($receiver);
                $message->setSenderFirstName($receiver->getProfileName());
                $message->setSenderEmail($receiver->getEmail());
                $message->setReceiver($sender);
                $message->setReceiverFirstName($sender->getProfileName());
                $message->setReceiverEmail($sender->getEmail());

                if ($parentMessage->getId() != '1') {
                    $originatorID = $parentMessage->getOriginatorId();
                } else {
                    $originatorID = $receiver->getId();
                }
                $message->setOriginatorId($originatorID);
            }
            $message->setParent($parentMessage);
            $message->setIsRead(0);
            $message->setStatus(0);
            $message->setAd($ad);
            $message->setMessageAdId($parentMessage->getMessageAdId());
            $message->setCreatedAt(time());
            $message->setIpAddress($request->getClientIp());
        }

        return $message;
    }

    /**
     * Download message attachment
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function downloadMessageAttachmentAction($messageId)
    {
        if ($messageId) {
            $objMessage = $this->getRepository('FaMessageBundle:Message')->find($messageId);
            if ($objMessage && $objMessage->getAttachmentPath() && $objMessage->getAttachmentFileName()) {
                $downloadFilePath = $this->container->get('kernel')->getRootDir().'/../web/'.$objMessage->getAttachmentPath().'/'.$objMessage->getAttachmentFileName();
                $downloadFileName = $objMessage->getAttachmentOrgFileName();
                if (file_exists($downloadFilePath)) {
                    CommonManager::downloadFile($downloadFilePath, $downloadFileName);
                } else {
                    $messageManager = $this->get('fa.message.manager');
                    $messageManager->setFlashMessage($this->get('translator')->trans('Sorry attachment file was not found!', array(), 'frontend-inbox'), 'error');
                    return $this->redirectToRoute('user_ad_message_all');
                }
            }
        }

        return new Response();
    }

    /**
     * Update ad mark as sold.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxUserAdMessageMarkAsSoldAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) == true && $request->isXmlHttpRequest()) {
            $error        = '';
            $successMsg   = '';
            $adId         = $request->get('adId', 0);
            $loggedinUser = $this->getLoggedInUser();

            //update ad status to sold
            $ans = $this->getRepository('FaAdBundle:Ad')->changeAdStatus($adId, EntityRepository::AD_STATUS_SOLD_ID, $this->container);

            if ($ans) {
                $successMsg = $this->get('translator')->trans('Ad marked as sold successfully.', array(), 'frontend-inbox');
            } else {
                $error = $this->get('translator')->trans('Problem in marking ad as sold.', array(), 'frontend-inbox');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        }

        return new Response();
    }

    /**
     * Show user ad reply message.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxShowUserMessagesAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $error        = '';
        $successMsg   = '';
        $replyId = $request->get('replyId');
        $message = $this->getRepository('FaMessageBundle:Message')->find($replyId);
        $fullConversation = $this->getRepository('FaMessageBundle:Message')->getFullconversation($replyId);

        if (!$message || !$message->getReceiver() || ($message->getReceiver()->getId() != $this->getLoggedInUser()->getId() && $message->getSender()->getId() != $this->getLoggedInUser()->getId())) {
            $error = 'You do not have permission to access this resource.';
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You do not have permission to access this resource.', array(), 'frontend-inbox'));
            return $this->redirectToRoute('user_ad_message');
        }

        $messagesHtml        = $this->renderView('FaMessageBundle:AdMessage:showReplyMessage.html.twig', array('fullConversation' => $fullConversation));
        $leaveReviewLinkHtml = $this->renderView('FaMessageBundle:AdMessage:showLeaveReviewLink.html.twig', array('fullConversation' => $fullConversation));

        return new JsonResponse(array('error' => $error, 'messagesHtml' => $messagesHtml, 'leaveReviewLinkHtml' => $leaveReviewLinkHtml));
    }

    /**
     * Report user.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxReportUserAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $loggedinUser = $this->getLoggedInUser();
        $error         = '';
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $replyId = $request->get('replyId');
            $adMessage = $this->getRepository('FaMessageBundle:Message')->find($replyId);
            //validate user and message
            if (!$adMessage || ($adMessage && $adMessage->getReceiver()->getId() != $loggedinUser->getId() && $adMessage->getSender()->getId() != $loggedinUser->getId())) {
                $error = $this->get('translator')->trans('You do not have permission to access this resource.', array(), 'frontend-inbox');
            }
            $adId = $adMessage->getMessageAdId();
            if ($adId > 0) {
                $ad       = $this->getRepository('FaAdBundle:Ad')->find($adId);
                $adUserId = (($ad && $ad->getUser()) ? $ad->getUser()->getId() : null);
            } else {
                $adUserId = $adMessage->getSender()->getId();
            }

            if ($loggedinUser->getId() != $adUserId) {
                $adMessageSpammer = $this->getRepository('FaMessageBundle:MessageSpammer')->findOneBy(array('ad_id' => $adId, 'reporter' => $loggedinUser->getId(), 'spammer' => $adUserId));
            } elseif ($loggedinUser->getId() == $adUserId) {
                $adMessageSpammer = $this->getRepository('FaMessageBundle:MessageSpammer')->findOneBy(array('ad_id' => $adId, 'reporter' => $loggedinUser->getId(), 'spammer' => $adMessage->getSender()->getId()));
            }
            if ($adMessageSpammer) {
                if ($adId > 0) {
                    $error = $this->get('translator')->trans('You already have reported user of this advert.', array(), 'frontend-inbox');
                } else {
                    $error = $this->get('translator')->trans('You already have reported user of this message subject.', array(), 'frontend-inbox');
                }
            }
            //check for ad.
            if ($adId > 0 && (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID))) {
                $error = $this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-search-result');
            } else {
                if (!$error) {
                    $formManager    = $this->get('fa.formmanager');
                    $messageSpammer = new MessageSpammer();
                    $form           = $formManager->createForm(ReportUserType::class, $messageSpammer, array('replyId' => $replyId, 'adOwnerId' => $adUserId));

                    if ('POST' === $request->getMethod()) {
                        $form->handleRequest($request);
                        if ($form->isValid()) {
                            $messageSpammer = $formManager->save($messageSpammer);
                            try {
                                if ($messageSpammer) {
                                    $messageSpammerBuildRequest = $this->container->get('fa_message_spammer.contact_moderation.request_build');
                                    $moderationRequest = $messageSpammerBuildRequest->init($messageSpammer);
                                    $moderationRequest = json_encode($moderationRequest);
                                    if ($messageSpammerBuildRequest->sendRequest($moderationRequest)) {
                                        $messageSpammer->setStatus(MessageRepository::MODERATION_QUEUE_STATUS_OKAY);
                                        $this->getEntityManager()->persist($messageSpammer);
                                    }
                                }
                            } catch (\Exception $e) {
                                // No need do take any action as we have cron
                                //  in background to again send the request.
                            }
                        } elseif ($request->isXmlHttpRequest()) {
                            $htmlContent = $this->renderView('FaMessageBundle:AdMessage:ajaxReportUser.html.twig', array('form' => $form->createView(), 'replyId' => $replyId));
                        }
                    } else {
                        CommonManager::updateCacheCounter($this->container, 'ad_enquiry_contact_seller_click_'.strtotime(date('Y-m-d')).'_'.$adId);
                        $htmlContent = $this->renderView('FaMessageBundle:AdMessage:ajaxReportUser.html.twig', array('form' => $form->createView(), 'replyId' => $replyId));
                    }
                }
            }

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Delete message
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxDeleteMessageAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) == true && $request->isXmlHttpRequest()) {
            $error            = '';
            $successMsg       = '';
            $messageId        = $request->get('messageId', 0);
            $objMessage       = $this->getRepository('FaMessageBundle:Message')->find($messageId);
            $messagesArray    = $this->getRepository('FaMessageBundle:Message')->getMessageConversationByMessage($objMessage);
            $objLogggedInUser = $this->getLoggedInUser();

            if ($objLogggedInUser && $messagesArray && count($messagesArray) > 0) {
                $this->getRepository('FaMessageBundle:Message')->removeMessageCache($objMessage, $this->container);
                $deleteCount = 0;
                foreach ($messagesArray as $key => $msgArray) {
                    if ($msgArray['deleted_by_user1'] == 0) {
                        $fieldName = 'deleted_by_user1';
                    } else {
                        $fieldName = 'deleted_by_user2';
                    }

                    //If a message is already marked deleted by user then do not mark again.
                    if ($msgArray['deleted_by_user1'] != $objLogggedInUser->getId() && $msgArray['deleted_by_user2'] != $objLogggedInUser->getId()) {
                        $deleteCount++;
                        $ans = $this->getRepository('FaMessageBundle:Message')->deleteMessages(array($msgArray['id']), $objLogggedInUser->getId(), $fieldName);
                    }
                }

                //mark message as delete
                if ($deleteCount > 0) {
                    $successMsg = $this->get('translator')->trans('Message deleted successfully.', array(), 'frontend-inbox');
                    $messageManager = $this->get('fa.message.manager');
                    $messageManager->setFlashMessage($successMsg, 'success');
                } else {
                    $error = $this->get('translator')->trans('Problem in deleting message.', array(), 'frontend-inbox');
                }
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        }

        return new Response();
    }

    /**
     * This action is used to reply one click enquiry.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function replyOneClickEnqAjaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest() && $request->get('adId')) {
            $objAd            = $this->getRepository('FaAdBundle:Ad')->find($request->get('adId'));
            $objParentMessage = $this->getRepository('FaMessageBundle:Message')->find($request->get('messageId'));
            if ($objParentMessage && $objAd) {
                $replyAns     = $request->get('ans');
                $objSender    = $this->getLoggedInUser();
                $objReceiver  = $objParentMessage->getSender();

                if ($replyAns == 'Yes') {
                    $deliveryMethodText = 'a viewing';
                    if ($objAd->getDeliveryMethodOptionId() && $objAd->getDeliveryMethodOptionId() != null) {
                        if ($objAd->getDeliveryMethodOptionId() == DeliveryMethodOptionRepository::COLLECTION_ONLY_ID) {
                            $deliveryMethodText = 'collection';
                        } elseif ($objAd->getDeliveryMethodOptionId() == DeliveryMethodOptionRepository::POSTED_ID) {
                            $deliveryMethodText = 'delivery';
                        } elseif ($objAd->getDeliveryMethodOptionId() == DeliveryMethodOptionRepository::POSTED_OR_COLLECT_ID) {
                            $deliveryMethodText = 'collection or delivery';
                        }
                    }
                    $htmlMessageText = "Great news! The item is still available. Message me for more information or to arrange ".$deliveryMethodText.".";
                    $textMessageText = "Great news! The item is still available. Message me for more information or to arrange ".$deliveryMethodText.".";
                } else {
                    $manageMyAdUrl      = $this->container->get('router')->generate('manage_my_ads_active', array(), true);
                    $manageMyAdsUrlLink = '<a href="'.$manageMyAdUrl.'">Manage my ads</a>';
                    $htmlMessageText    = "<b>".$objReceiver->getProfileName()."</b>"." has been notified that the item has been sold. Your ad has been marked as sold and is no longer live. If you'd like to re-post your ad, please visit ".$manageMyAdsUrlLink." and click on the 'inactive ads' tab.";
                    $textMessageText    = $objReceiver->getProfileName()." has been notified that the item has been sold. Your ad has been marked as sold and is no longer live. If you'd like to re-post your ad, please visit Manage my ads and click on the 'inactive ads' tab.";
                    $this->getRepository('FaAdBundle:Ad')->changeAdStatus($objAd->getId(), EntityRepository::AD_STATUS_SOLD_ID, $this->container);
                }
                $objMessage  = new Message();
                $objMessage  = $this->getRepository('FaMessageBundle:Message')->setMessageDetail($objMessage, $objParentMessage, $objAd, $objSender, $objReceiver, $request->getClientIp());
                $objMessage->setSubject($objAd->getTitle());
                $objMessage->setTextMessage($textMessageText);
                $objMessage->setHtmlMessage($htmlMessageText);
                $objMessage->setIsOneclickenqMessage(1);
                $objMessage->setOneclickenqReply($replyAns);
                $objMessage->setStatus(0);

                $this->getEntityManager()->persist($objMessage);
                $this->getEntityManager()->flush($objMessage);

                // send message into moderation.
                try {
                    $this->getRepository('FaMessageBundle:Message')->sendContactIntoModeration($objMessage, $this->container);
                } catch (\Exception $e) {
                    // No need do take any action as we have cron
                    //  in background to again send the request.
                }

                $messageManager = $this->get('fa.message.manager');
                $successMsg     = $this->get('translator')->trans('Your answer was sent successfully.', array(), 'frontend-inbox');
                $messageManager->setFlashMessage($successMsg, 'success');
                $response = new Response();
                return new JsonResponse(array('response' => true));
            }
            return new JsonResponse(array('response' => false));
        }

        return new JsonResponse(array('response' => false));
    }

    /**
     * This action is used to move attachments from tmp folder to actual folder.
     *
     * @param string $sessionId A session id.
     * @param string $messageId A message id.
     *
     * @return Response A Response object.
     */
    public function moveAttachmentsTmpToActualFolder($sessionId, $messageId)
    {
        $objMessageAttachments = $this->getRepository('FaMessageBundle:MessageAttachments')->getMessageAttachments($sessionId);

        if ($objMessageAttachments) {
            foreach ($objMessageAttachments as $key => $objMessageAttachment) {
                $webPath        = $this->container->get('kernel')->getRootDir().'/../web';
                $attachmentDir  = $webPath.'/'.$this->container->getParameter('fa.message.attachment.dir');
                $attachmentPath = $this->container->getParameter('fa.ad.image.tmp.dir');

                CommonManager::createGroupDirectory($attachmentDir, $messageId);
                $messageGroupDir = CommonManager::getGroupDirNameById($messageId);
                $fileExtension   = substr(strrchr($objMessageAttachment->getOriginalFileName(), '.'), 1);
                $actualFileName  = $sessionId.'_'.$objMessageAttachment->getHash().'.'.$fileExtension;

                $tmpFilePath     = $webPath.DIRECTORY_SEPARATOR.$attachmentPath.'/'.$actualFileName;
                $destinationPath = $attachmentDir.'/'.$messageGroupDir.'/'.$actualFileName;

                copy($tmpFilePath, $destinationPath);
                unlink($tmpFilePath);
            }

            $path = $this->container->getParameter('fa.message.attachment.dir').'/'.$messageGroupDir;
            $this->getRepository('FaMessageBundle:MessageAttachments')->updateMessageAttachment($sessionId, $messageId, $path);
        }

        return true;
    }

    /**
     * Mark message field.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxMarkMessageFieldAction(Request $request)
    {
        $updateFlag = false;
        if ($request->isXmlHttpRequest() && $request->get('messageId') != '' && $request->get('fieldName') != '' && $request->get('fieldValue') != '') {
            $updateFlag = $this->getRepository('FaMessageBundle:Message')->updateMessageField($request->get('messageId'), $request->get('fieldName'), $request->get('fieldValue'));
        }

        return new JsonResponse(array('response' => $updateFlag));
    }
}
