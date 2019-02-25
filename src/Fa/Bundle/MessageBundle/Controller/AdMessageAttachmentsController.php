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

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\MessageBundle\Entity\MessageAttachments;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Twig\CoreExtension;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\MessageBundle\Form\MessageAttachmentsType;

/**
 * This controller is used for ad post management.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdMessageAttachmentsController extends CoreController
{
    /**
     * Show attachments uploader for message.
     *
     * @param integer $messageId
     * @param string  $vertical
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showMessageAttachmentsUploaderAction($messageId = null, $formName = null, $maxValue = null, $userId = null, $from = null, Request $request)
    {
        if (!$messageId) {
            if (!$this->container->get('session')->has('message_id')) {
                $this->container->get('session')->set('message_id', CommonManager::generateHash());
            }

            $messageId = $this->container->get('session')->get('message_id');

            $objMessageAttachments = $this->getRepository('FaMessageBundle:MessageAttachments')->findBy(array('session_id' => $messageId));
            if ($objMessageAttachments) {
                $attachmentIds = array();
                foreach ($objMessageAttachments as $objMessageAttachment) {
                    if (!$objMessageAttachment->getMessage()) {
                        $attachmentIds[] = $objMessageAttachment->getId();
                        $webPath         = $this->container->get('kernel')->getRootDir().'/../web';
                        $fileExtension   = substr(strrchr($objMessageAttachment->getOriginalFileName(), '.'), 1);
                        $fileName        = $objMessageAttachment->getSessionId().'_'.$objMessageAttachment->getHash().'.'.$fileExtension;
                        $filePath        = $webPath.DIRECTORY_SEPARATOR.$objMessageAttachment->getPath().'/'.$fileName;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                $this->getRepository('FaMessageBundle:MessageAttachments')->removeMessageAttachment($attachmentIds);
            }
        }

        if (!$userId) {
            $objLoggedInUser = $this->getLoggedInUser();
            $userId          = $objLoggedInUser->getId();
        }

        return $this->render('FaMessageBundle:AdMessageAttachments:showAttachmentsUploader.html.twig', array('messageId' => $messageId, 'userId' => $userId, 'formName' => $formName, 'maxValue' => $maxValue, 'from' => $from));
    }

    /**
     * Save uploaded attachments using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxSaveUploadedAttachmentsAction(Request $request)
    {
        if ($request->ismethod('post')) {
            $error  = '';
            $params = $request->get('fa_message_attachments');
            $messageId = trim($params['message_id']);
            $userId = (isset($params['user_id']) ? trim($params['user_id']) : null);
            if ($messageId) {
                $message               = $this->getRepository('FaMessageBundle:Message')->find($messageId);
                $formManager           = $this->get('fa.formmanager');
                $objMessageAttachments = new MessageAttachments();
                $form                  = $formManager->createForm(MessageAttachmentsType::class, $objMessageAttachments);
                $formParams            = array('session_id' => $messageId);

                $files = $request->files->get('fa_message_attachments');
                $formParams['fileData'] = $files['fileData'];
                $form->submit($formParams);

                try {
                    if ($form->isValid()) {
                        $isAdminUser = $this->isAdminLoggedIn() ? true : false;
                        $this->getRepository('FaMessageBundle:MessageAttachments')->saveAttachment($objMessageAttachments, $this->container);
                    } else {
                        $error = $form->getErrors(true, false);
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }

                return new JsonResponse(array('error' => $error));
            }
        }

        return new Response();
    }

    /**
     * Check is valid user to upload image to ad
     *
     * @param integer $userId User id
     *
     * @return mixed|
     */
    private function checkIsUserIsValidToUploadImage($userId)
    {
        if ($this->isAuth()) {
            return $this->getSecurityTokenStorage()->getToken()->getUser();
            ;
        }
        if ($userId) {
            $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('id' => $userId, 'status' => EntityRepository::USER_STATUS_ACTIVE_ID));
            if ($user) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Get message attachments based on message id using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxRenderUploadedAttachmentsAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $messageId = $request->get('messageId');
            $cache     = $request->get('cache');

            $attachments = $this->renderView('FaMessageBundle:AdMessageAttachments:renderUploadedAttachments.html.twig', array('messageId' => $messageId, 'cache' => $cache));

            return new JsonResponse(array('attachments' => $attachments));
        }

        return new Response();
    }

    /**
     * Delete message attachment using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxDeleteAttachmentAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';

            //remove attachment
            $messageId               = $request->get('messageId');
            $attachmentId            = $request->get('attachmentId');
            $hash                    = $request->get('attachmentHash');
            $imageRemove             = $this->getRepository('FaMessageBundle:MessageAttachments')->removeMessageAttachment($attachmentId);
            //$imageLimit              = ($maxValue ? $maxValue : 10);
            $messageAttachmentsCount = $this->getRepository('FaMessageBundle:MessageAttachments')->getMessageAttachmentsCount($messageId);

            $attachments = $this->renderView('FaMessageBundle:AdMessageAttachments:renderUploadedAttachments.html.twig', array('messageId' => $messageId));

            if (!$imageRemove) {
                $error = $this->get('translator')->trans('Problem in deleting ad image.');
            } else {
                $successMsg = $this->get('translator')->trans('Attachment has been deleted successfully.');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg,'attachments' => $attachments, 'attachmentsLimitRemaining' => 10 - $messageAttachmentsCount, 'messageAttachmentsCount' => $messageAttachmentsCount));
        }

        return new Response();
    }

    /**
     * Download message attachment
     *
     * @param Request $request
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxDownloadMessageAttachmentsAction(Request $request)
    {
        $messageId    = $request->get('messageId');
        $attachmentId = $request->get('attachmentId');
        if ($messageId != '' || $attachmentId != '') {
            if ($messageId != '') {
                $objMessageAttachments = $this->getRepository('FaMessageBundle:MessageAttachments')->getMessageAttachments($messageId);
            } elseif ($attachmentId != '') {
                $objMessageAttachments = $this->getRepository('FaMessageBundle:MessageAttachments')->find($attachmentId);
            }

            if ($objMessageAttachments) {
                if (count($objMessageAttachments) == 1) {
                    $downloadFileName = $objMessageAttachments->getOriginalFileName();
                    $fileExtension    = substr(strrchr($downloadFileName, '.'), 1);
                    $fileName         = $objMessageAttachments->getSessionId().'_'.$objMessageAttachments->getHash().'.'.$fileExtension;
                    $downloadFilePath = $this->container->get('kernel')->getRootDir().'/../web/'.$objMessageAttachments->getPath().'/'.$fileName;

                    if (file_exists($downloadFilePath)) {
                        CommonManager::downloadFile($downloadFilePath, $downloadFileName);
                    } else {
                        $messageManager = $this->get('fa.message.manager');
                        $messageManager->setFlashMessage($this->get('translator')->trans('Sorry attachment file was not found!', array(), 'frontend-inbox'), 'error');
                        return $this->redirectToRoute('user_ad_message_all');
                    }
                } elseif (count($objMessageAttachments) > 1) {
                    $zipFilesArray = array();
                    foreach ($objMessageAttachments as $key => $objMessageAttachment) {
                        $messageId        = $objMessageAttachment->getMessage()->getId();
                        $downloadFileName = $objMessageAttachment->getOriginalFileName();
                        $fileExtension    = substr(strrchr($downloadFileName, '.'), 1);
                        $fileName         = $objMessageAttachment->getSessionId().'_'.$objMessageAttachment->getHash().'.'.$fileExtension;
                        $fileToAddInZip   = $this->container->get('kernel')->getRootDir().'/../web/'.$objMessageAttachment->getPath().'/'.$fileName;

                        if (file_exists($fileToAddInZip)) {
                            $zipFilesArray[]  = $fileToAddInZip;
                        } else {
                            $messageManager = $this->get('fa.message.manager');
                            $messageManager->setFlashMessage($this->get('translator')->trans('Sorry attachment file was not found!', array(), 'frontend-inbox'), 'error');
                            return $this->redirectToRoute('user_ad_message_all');
                        }
                    }

                    $flag = CommonManager::createZip($zipFilesArray, $this->container->getParameter('fa.ad.image.tmp.dir').'/'.$messageId.'.zip');
                    CommonManager::downloadZipFile($this->container->getParameter('fa.ad.image.tmp.dir').'/'.$messageId.'.zip', $messageId.'.zip');
                }
            }
        }

        return new Response();
    }
}
