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
use Fa\Bundle\MessageBundle\Form\MessageUserType;

/**
 * This controller is used for resetting user password.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class ContactUserController extends CoreController
{
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
     * @param integer $adId     Ad id.
     * @param integer $sellerId seller id.
     * @param integer $buyerId  buyer id.
     * @param Request $request  A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function contactUserAction($adId, $senderId, $receiverId, $whoToWhome = "B2S", Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $loggedinUser   = $this->getLoggedInUser();
        $loggedInUserId = $loggedinUser->getId();

        if ($request->isXmlHttpRequest()) {
            $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
            //check for ad.
            if (!$ad) {
                $error = $this->get('translator')->trans('Unable to find Ad.', array(), 'frontend-search-result');
            } elseif ($this->isAuth() && $loggedInUserId == $receiverId) {
                $error = $this->get('translator')->trans('You can not contact for your own ad.', array(), 'frontend-show-ad');
                $this->getRepository('FaUserBundle:User')->removeUserCookies();
            } elseif ($ad->getStatus()->getId() == EntityRepository::AD_STATUS_INACTIVE_ID) {
                $error = $this->get('translator')->trans('Sorry ad is no more active, so communication is disabled for this ad!', array(), 'frontend-show-ad');
                $this->getRepository('FaUserBundle:User')->removeUserCookies();
            } else {
                if ($this->isAuth()) {
                    $objSender      = $this->getLoggedInUser();
                    $objReceiver    = $this->getRepository('FaUserBundle:User')->find($receiverId);
                    $parent         = $this->getRepository('FaMessageBundle:Message')->getLastConversionOfTwoUsersForAd($adId, $objSender->getId(), $receiverId);
                    $formManager    = $this->get('fa.formmanager');
                    $message        = new Message();
                    $form           = $formManager->createForm(MessageUserType::class, $message);
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($ad->getCategory()->getId());

                    if ('POST' === $request->getMethod()) {
                        $form->handleRequest($request);

                        if ($form->isValid()) {
                            //save information
                            $message = $this->getRepository('FaMessageBundle:Message')->setMessageDetail($message, $parent, $ad, $objSender, $objReceiver, $request->getClientIp());
                            if ($whoToWhome == 'S2B') {
                                $message->setOriginatorId($objReceiver->getId());
                            }
                            $message = $formManager->save($message);

                            // send message into moderation.
                            try {
                                $this->getRepository('FaMessageBundle:Message')->sendContactIntoModeration($message, $this->container);
                            } catch (\Exception $e) {
                                // No need do take any action as we have cron
                                //  in background to again send the request.
                            }

                            $messageManager = $this->get('fa.message.manager');
                            $messageManager->setFlashMessage($this->get('translator')->trans('Contact request sent successfully.', array(), 'frontend-show-ad'), 'success');
                        } elseif ($request->isXmlHttpRequest()) {
                            $htmlContent = $this->renderView('FaMessageBundle:ContactUser:ajaxContactUser.html.twig', array('form' => $form->createView(), 'objAd' => $ad, 'objSender' => $objSender, 'objReceiver' => $objReceiver, 'rootCategoryId' => $rootCategoryId, 'whoToWhome' => $whoToWhome));
                        }
                    } else {
                        $htmlContent = $this->renderView('FaMessageBundle:ContactUser:ajaxContactUser.html.twig', array('form' => $form->createView(), 'objAd' => $ad, 'objSender' => $objSender, 'objReceiver' => $objReceiver, 'rootCategoryId' => $rootCategoryId, 'whoToWhome' => $whoToWhome));
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

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }
}
