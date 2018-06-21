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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\MessageBundle\Entity\Message;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Form\UserHalfAccountType;
use Fa\Bundle\MessageBundle\Form\ContactSellerType;

/**
 * This controller is used for resetting user password.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class ContactSellerController extends CoreController
{
    /**
     * Contact seller.
     *
     * @param integer $adId    Ad id.
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function contactSellerAction($adId, Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $deadlockError = '';
        $deadlockRetry = $request->get('deadlockRetry', 0);

        if ($request->isXmlHttpRequest()) {
            $ad       = $this->getRepository('FaAdBundle:Ad')->find($adId);
            $adUserId = (($ad && $ad->getUser()) ? $ad->getUser()->getId() : null);
            //check for ad.
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                $error = $this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-search-result');
            } else {
                $loggedinUser = null;
                if ($this->isAuth()) {
                    //check for own ad.
                    $loggedinUser = $this->getLoggedInUser();
                    if ($loggedinUser->getId() == $adUserId) {
                        $error = $this->get('translator')->trans('You can not contact for your own ad.', array(), 'frontend-show-ad');
                        $this->getRepository('FaUserBundle:User')->removeUserCookies();
                    }
                }

                if (!$error) {
                    $formManager    = $this->get('fa.formmanager');
                    $message        = new Message();
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($ad->getCategory()->getId());
                    $form           = $formManager->createForm(ContactSellerType::class, $message, array('rootCategoryId' => $rootCategoryId));

                    if ('POST' === $request->getMethod()) {
                        $form->handleRequest($request);
                        if ($form->isValid()) {
                            // Create half account in case of user not logged in.
                            if (!$this->isAuth()) {
                                $halfAccountData = array(
                                                       'email'      => $form->get('sender_email')->getData(),
                                                       'first_name' => $form->get('sender_first_name')->getData(),
                                                       '_token'     => $this->container->get('form.csrf_provider')->generateCsrfToken('fa_user_half_account')
                                                   );

                                $halfAccountForm = $formManager->createForm(UserHalfAccountType::class, null, array('method' => 'POST'));
                                $halfAccountForm->submit($halfAccountData);

                                $loggedinUser = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('sender_email')->getData()));
                                if (!$loggedinUser) {
                                    $error = $this->get('translator')->trans('Unable to find user.', array(), 'frontend-show-ad');
                                } elseif ($loggedinUser->getStatus() && $loggedinUser->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                                    $error = $this->get('translator')->trans('Your account was blocked.', array(), 'frontend-show-ad');
                                } elseif ($loggedinUser->getId() == $adUserId) {
                                    $error = $this->get('translator')->trans('You can not contact for your own ad.', array(), 'frontend-show-ad');
                                }
                            }

                            if ($loggedinUser && !$error) {
                                try {
                                    //save information
                                    $parent  = $this->getRepository('FaMessageBundle:Message')->getLastMessage($adId, $loggedinUser->getId());
                                    $message = $this->getRepository('FaMessageBundle:Message')->updateContactSellerMessage($message, $parent, $loggedinUser, $ad, $request->getClientIp());
                                    $message = $formManager->save($message);
                                    CommonManager::updateCacheCounter($this->container, 'ad_enquiry_email_send_link_'.strtotime(date('Y-m-d')).'_'.$adId);

                                    $objUploader = $form->get('attachment')->getData();
                                    if ($objUploader) {
                                        $uploadDetailArray = $this->uploadAttachment($objUploader, $message);
                                        $message->setAttachmentPath($uploadDetailArray['attachmentPath']);
                                        $message->setAttachmentFileName($uploadDetailArray['attachmentFileName']);
                                        $message->setAttachmentOrgFileName($objUploader->getClientOriginalName());
                                        $formManager->save($message);
                                    }
                                } catch (\Exception $e) {
                                    $deadlockError = $this->get('translator')->trans('Our system is currently busy, please try again.', array(), 'frontend-show-ad');
                                    if ($deadlockRetry == 3) {
                                        CommonManager::sendErrorMail($this->container, 'Error in contact seller', $e->getMessage(), $e->getTraceAsString());
                                    }
                                    $htmlContent = $this->renderView('FaMessageBundle:ContactSeller:ajaxContactSeller.html.twig', array('form' => $form->createView(), 'ad' => $ad, 'rootCategoryId' => $rootCategoryId, 'deadlockRetry' => $deadlockRetry));
                                    return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
                                }

                                // send message into moderation.
                                try {
                                    $this->getRepository('FaMessageBundle:Message')->sendContactIntoModeration($message, $this->container);
                                } catch (\Exception $e) {
                                    // No need do take any action as we have cron
                                    //  in background to again send the request.
                                }

                                //update email alerts
                                if ($form->get('email_alert')->getData()) {
                                    $loggedinUser->setIsEmailAlertEnabled(1);
                                } else {
                                    $loggedinUser->setIsEmailAlertEnabled(0);
                                }

                                //update third party email alerts
                                if ($form->get('third_party_email_alert')->getData()) {
                                    $loggedinUser->setIsThirdPartyEmailAlertEnabled(1);
                                } else {
                                    $loggedinUser->setIsThirdPartyEmailAlertEnabled(0);
                                }
                                $this->getEntityManager()->persist($loggedinUser);
                                $this->getEntityManager()->flush($loggedinUser);

                                //save search agent.
                                if ($form->get('search_agent')->getData()) {
                                    $this->getRepository('FaUserBundle:UserSearchAgent')->saveUserSearch($ad, $loggedinUser, $this->container);
                                } else {
                                    $this->getRepository('FaUserBundle:UserSearchAgent')->removeUserSearch($ad, $loggedinUser, $this->container);
                                }
                            }
                        } elseif ($request->isXmlHttpRequest()) {
                            $htmlContent = $this->renderView('FaMessageBundle:ContactSeller:ajaxContactSeller.html.twig', array('form' => $form->createView(), 'ad' => $ad, 'rootCategoryId' => $rootCategoryId, 'deadlockRetry' => $deadlockRetry));
                        }
                    } else {
                        CommonManager::updateCacheCounter($this->container, 'ad_enquiry_contact_seller_click_'.strtotime(date('Y-m-d')).'_'.$adId);
                        $htmlContent = $this->renderView('FaMessageBundle:ContactSeller:ajaxContactSeller.html.twig', array('form' => $form->createView(), 'ad' => $ad, 'rootCategoryId' => $rootCategoryId, 'deadlockRetry' => $deadlockRetry));
                    }
                }
            }

            return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
        } else {
            return new Response();
        }
    }

    /**
     * Contact seller.
     *
     * @param Uploader $objUploader Uploader object.
     * @param Message  $objMessage  Message object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function uploadAttachment($objUploader, $objMessage)
    {
        $webPath       = $this->container->get('kernel')->getRootDir().'/../web';
        $attachmentDir = $webPath.'/'.$this->container->getParameter('fa.message.attachment.dir');
        CommonManager::createGroupDirectory($attachmentDir, $objMessage->getId());
        $messageGroupDir    = CommonManager::getGroupDirNameById($objMessage->getId());
        $attachmentPath     = $attachmentDir.'/'.$messageGroupDir;
        $fileExtension      = pathinfo($objUploader->getClientOriginalName(), PATHINFO_EXTENSION);
        $attachmentFileName = $objMessage->getId().'.'.$fileExtension;
        $objUploader->move($attachmentPath, $attachmentFileName);

        $returnArray = array('attachmentPath' => $this->container->getParameter('fa.message.attachment.dir').'/'.$messageGroupDir, 'attachmentFileName' => $attachmentFileName);

        return $returnArray;
    }

    /**
     * Contact buyer.
     *
     * @param integer $adId    Ad id.
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function contactBuyerAction($adId, $buyerId, Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $deadlockError = '';
        $deadlockRetry = $request->get('deadlockRetry', 0);

        if ($request->isXmlHttpRequest()) {
            $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
            //check for ad.
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                $error = $this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-search-result');
            } else {
                if ($this->isAuth()) {
                    $objSeller      = $this->getLoggedInUser();
                    $objBuyer       = $this->getRepository('FaUserBundle:User')->find($buyerId);
                    $parent         = $this->getRepository('FaMessageBundle:Message')->getLastConversionOfSenderReceiverForAd($adId, $objSeller->getId(), $buyerId);
                    $formManager    = $this->get('fa.formmanager');
                    $message        = new Message();
                    $form           = $formManager->createForm(ContactSellerType::class, $message);
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($ad->getCategory()->getId());

                    if ('POST' === $request->getMethod()) {
                        $form->handleRequest($request);

                        if ($form->isValid()) {
                            try {
                                //save information
                                $message = $this->getRepository('FaMessageBundle:Message')->setMessageDetail($message, $parent, $ad, $objSeller, $objBuyer, $request->getClientIp());
                                $message = $formManager->save($message);

                                $objUploader = $form->get('attachment')->getData();
                                if ($objUploader) {
                                    $uploadDetailArray = $this->uploadAttachment($objUploader, $message);
                                    $message->setAttachmentPath($uploadDetailArray['attachmentPath']);
                                    $message->setAttachmentFileName($uploadDetailArray['attachmentFileName']);
                                    $message->setAttachmentOrgFileName($objUploader->getClientOriginalName());
                                    $formManager->save($message);
                                }
                            } catch (\Exception $e) {
                                $deadlockError = $this->get('translator')->trans('Our system is currently busy, please try again.', array(), 'frontend-show-ad');
                                if ($deadlockRetry == 3) {
                                    CommonManager::sendErrorMail($this->container, 'Error in contact seller', $e->getMessage(), $e->getTraceAsString());
                                }
                                $htmlContent = $this->renderView('FaMessageBundle:ContactSeller:ajaxContactSeller.html.twig', array('form' => $form->createView(), 'ad' => $ad, 'rootCategoryId' => $rootCategoryId, 'deadlockRetry' => $deadlockRetry));
                                return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
                            }

                            // send message into moderation.
                            try {
                                $this->getRepository('FaMessageBundle:Message')->sendContactIntoModeration($message, $this->container);
                            } catch (\Exception $e) {
                                // No need do take any action as we have cron
                                //  in background to again send the request.
                            }

                            //update email alerts
                            if ($form->get('email_alert')->getData()) {
                                $objUser->setIsEmailAlertEnabled(1);
                            } else {
                                $objUser->setIsEmailAlertEnabled(0);
                            }
                            $this->getEntityManager()->persist($objUser);
                            $this->getEntityManager()->flush($objUser);

                            //save search agent.
                            if ($form->get('search_agent')->getData()) {
                                $this->getRepository('FaUserBundle:UserSearchAgent')->saveUserSearch($ad, $objUser, $this->container);
                            } else {
                                $this->getRepository('FaUserBundle:UserSearchAgent')->removeUserSearch($ad, $objUser, $this->container);
                            }
                        } elseif ($request->isXmlHttpRequest()) {
                            $htmlContent = $this->renderView('FaMessageBundle:ContactSeller:ajaxContactSeller.html.twig', array('form' => $form->createView(), 'ad' => $ad, 'rootCategoryId' => $rootCategoryId, 'deadlockRetry' => $deadlockRetry));
                        }
                    } else {
                        $htmlContent = $this->renderView('FaMessageBundle:ContactSeller:ajaxContactSeller.html.twig', array('form' => $form->createView(), 'ad' => $ad, 'rootCategoryId' => $rootCategoryId, 'deadlockRetry' => $deadlockRetry));
                    }
                } else {
                    //set new cookies for contact seller.
                    $response = new Response();
                    //remove all cookies.
                    $response = $this->getRepository('FaUserBundle:User')->removeUserCookies($response);
                    $response->headers->setCookie(new Cookie('frontend_redirect_after_login_path_info', $request->get('redirectUrl'), time() + 3600 * 24 * 7));
                    $response->headers->setCookie(new Cookie('contact_seller_flag', true, time() + 3600 * 24 * 7));
                    $response->sendHeaders();

                    $messageManager = $this->get('fa.message.manager');
                    $messageManager->setFlashMessage($this->get('translator')->trans('Please login to contact buyer.', array(), 'frontend-show-ad'), 'success');
                    $redirectToUrl = $this->container->get('router')->generate('login');
                }
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry, 'deadlockError' => $deadlockError));
        } else {
            return new Response();
        }
    }

    /**
     * Ajax get email alert by email.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetEmailAlertByEmailAction(Request $request)
    {
        $isEmailAlertEnabled = 0;
        $isThirdPartyEmailAlertEnabled = 0;
        $email = $request->get('emailAddress');

        if ($request->isXmlHttpRequest() && $email) {
            $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $email));
            if ($user) {
                $isEmailAlertEnabled = (int) $user->getIsEmailAlertEnabled();
                $isThirdPartyEmailAlertEnabled = (int) $user->getIsThirdPartyEmailAlertEnabled();
            }
            return new JsonResponse(array('isEmailAlertEnabled' => $isEmailAlertEnabled, 'isThirdPartyEmailAlertEnabled' => $isThirdPartyEmailAlertEnabled));
        } else {
            return new Response();
        }
    }
}
