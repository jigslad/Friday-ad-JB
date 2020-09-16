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
use Fa\Bundle\DotMailerBundle\Entity\Dotmailer;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;
use Symfony\Component\Routing\Route;


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
                $loggedInUser = null;
                if ($this->isAuth()) {
                    //check for own ad.
                    $loggedInUser = $this->getLoggedInUser();
                    if ($loggedInUser->getId() == $adUserId) {
                        $error = $this->get('translator')->trans('Sorry, it looks like you’re trying to enquire on your own advert!', array(), 'frontend-show-ad');
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
                            if (!$this->isAuth()) {
                                $loggedInUser = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('sender_email')->getData()));
                                if (!$loggedInUser) {
                                    // Create half account in case of user not logged in.
                                    $halfAccountData = array(
                                        'email'      => $form->get('sender_email')->getData(),
                                        'first_name' => $form->get('sender_first_name')->getData(),
                                        '_token'     => $this->get('security.csrf.token_manager')->getToken('fa_user_half_account')->getValue()
//                                     $this->container->get('form.csrf_provider')->generateCsrfToken('fa_user_half_account')
                                    );
                                    $halfAccountForm = $formManager->createForm(UserHalfAccountType::class, null, array('method' => 'POST'));
                                    $halfAccountForm->submit($halfAccountData);
                                    $this->getEntityManager()->flush();sleep(5);
                                    $loggedInUser = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('sender_email')->getData()));
                                    if(!$loggedInUser){
                                        $error = $this->get('translator')->trans('Unable to find user.', array(), 'frontend-show-ad');
                                    }
                                }
                                elseif ($loggedInUser->getStatus() && $loggedInUser->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                                    $error = $this->get('translator')->trans('Your account was blocked.', array(), 'frontend-show-ad');
                                }
                                elseif ($loggedInUser->getId() == $adUserId) {
                                    $error = $this->get('translator')->trans('Sorry, it looks like you’re trying to enquire on your own advert!', array(), 'frontend-show-ad');
                                }
                                else{
                                    $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Please log in to your account to send the message.', array(), 'frontend-show-ad'));
                                    $redirectToUrl = $this->generateUrl('login');
                                    $error = $this->get('translator')->trans('You need to login to send to message to this ad. click <a href="'.$redirectToUrl.'">here</a> to login', array(), 'frontend-show-ad');
                                }
                            }
                            if ($loggedInUser && !$error) {
                                try {
                                    //save information
                                    $parent  = $this->getRepository('FaMessageBundle:Message')->getLastMessage($adId, $loggedInUser->getId());
                                    $message = $this->getRepository('FaMessageBundle:Message')->updateContactSellerMessage($message, $parent, $loggedInUser, $ad, $request->getClientIp());
                                    $message = $formManager->save($message);
                                    CommonManager::updateCacheCounter($this->container, 'ad_enquiry_email_send_link_'.strtotime(date('Y-m-d')).'_'.$adId);
                                    
                                    //update email alerts
                                    if ($form->get('email_alert')->getData()) {
                                        $loggedInUser->setIsEmailAlertEnabled(1);
                                    } else {
                                        $loggedInUser->setIsEmailAlertEnabled(0);
                                    }
                                    
                                    //update third party email alerts
                                    if ($form->get('third_party_email_alert')->getData()) {
                                        $loggedInUser->setIsThirdPartyEmailAlertEnabled(1);
                                    } else {
                                        $loggedInUser->setIsThirdPartyEmailAlertEnabled(0);
                                    }
                                    $this->getEntityManager()->persist($loggedInUser);
                                    $this->getEntityManager()->flush($loggedInUser);
                                    
                                    $newsletterTypeIds = array();
                                    $userEmail = ($loggedInUser)?$loggedInUser->getEmail():$form->get('sender_email')->getData();
                                    $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $userEmail));
                                    if($dotmailer) {
                                        if($loggedInUser->getIsThirdPartyEmailAlertEnabled()==1) {
                                            $newsletterTypeIds[] = 48;
                                        }
                                        
                                        if ($loggedInUser->getIsEmailAlertEnabled()==1 || $loggedInUser->getIsThirdPartyEmailAlertEnabled()==1) {
                                            $newsletterTypeIds[] = 49;
                                            
                                            if ($dotmailer->getDotmailerNewsletterTypeId()) {
                                                $newsletterTypeIds = array_merge($newsletterTypeIds, $dotmailer->getDotmailerNewsletterTypeId());
                                                $newsletterTypeIds = array_unique($newsletterTypeIds);
                                            }
                                            
                                            $dotmailer->setDotmailerNewsletterTypeId($newsletterTypeIds);
                                            
                                            if (($dotmailer->getIsContactSent() == null) || ($dotmailer->getIsContactSent() !=1))  {
                                                $dotmailer->setFirstTouchPoint(DotmailerRepository::TOUCH_POINT_ENQUIRY);
                                                $dotmailer->setIsContactSent(1);
                                            }
                                        }
                                        
                                        $this->getEntityManager()->persist($dotmailer);
                                        $this->getEntityManager()->flush($dotmailer);
                                        
                                        if ($loggedInUser->getIsEmailAlertEnabled()==1 || $loggedInUser->getIsThirdPartyEmailAlertEnabled()==1) {
                                            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:subscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
                                        }
                                    }
                                    else {
                                        $dotMailer = new Dotmailer();
                                        $dotMailer->setDotmailerNewsletterUnsubscribe(0);
                                        $dotMailer->setEmail($form->get('sender_email')->getData());
                                        $dotMailer->setGuid(CommonManager::generateGuid($form->get('sender_email')->getData()));
                                        $dotMailer->setIsSuppressed(0);
                                        $dotMailer->setIsHalfAccount(1);
                                        $dotMailer->setCreatedAt(time());
                                        $dotMailer->setUpdatedAt(time());
                                        $dotMailer->setOptInType(DotmailerRepository::OPTINTYPE);
                                        $dotMailer->setFirstName($form->get('sender_first_name')->getData());
                                        
                                        if($form->get('third_party_email_alert')->getData()) {
                                            $newsletterTypeIds[] = 48;
                                        }
                                        
                                        if ($form->get('email_alert')->getData() ==1 || $form->get('third_party_email_alert')->getData() ==1) {
                                            $newsletterTypeIds[] = 49;
                                            $dotMailer->setDotmailerNewsletterTypeId($newsletterTypeIds);
                                            $dotMailer->setFirstTouchPoint(DotmailerRepository::TOUCH_POINT_ENQUIRY);
                                            $dotMailer->setIsContactSent(1);
                                        }
                                        
                                        $this->getEntityManager()->persist($dotMailer);
                                        $this->getEntityManager()->flush($dotMailer);
                                        
                                        if ($form->get('email_alert')->getData() ==1 || $form->get('third_party_email_alert')->getData() ==1) {
                                            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:subscribe-contact --id='.$dotMailer->getId().' >/dev/null &');
                                        }
                                        
                                        
                                    }
                                    
                                     
                                    $objUploader = $form->has('attachment') ? $form->get('attachment')->getData(): false;
                                    if ($objUploader) {
                                        $uploadDetailArray = $this->uploadAttachment($objUploader, $message);
                                        $message->setAttachmentPath($uploadDetailArray['attachmentPath']);
                                        $message->setAttachmentFileName($uploadDetailArray['attachmentFileName']);
                                        $message->setAttachmentOrgFileName($objUploader->getClientOriginalName());
                                        $formManager->save($message);
                                    }
                                }
                                catch (\Exception $e) {
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
                                }
                                catch (\Exception $e) {
                                    // No need do take any action as we have cron
                                    //  in background to again send the request.
                                }

                                $this->getEntityManager()->persist($loggedInUser);
                                $this->getEntityManager()->flush($loggedInUser);

                                //save search agent.
                                if ($form->get('search_agent')->getData()) {
                                    $this->getRepository('FaUserBundle:UserSearchAgent')->saveUserSearch($ad, $loggedInUser, $this->container);
                                }
                                else {
                                    $this->getRepository('FaUserBundle:UserSearchAgent')->removeUserSearch($ad, $loggedInUser, $this->container);
                                }
                            }
                        }
                        elseif ($request->isXmlHttpRequest()) {
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

                                $objUploader = $form->has('attachment') ? $form->get('attachment')->getData(): false;
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
