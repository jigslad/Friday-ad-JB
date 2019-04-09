<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\DotMailerBundle\Entity\Dotmailer;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Form\NewsletterUpdateType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Entity\NewsletterFeedback;
use Fa\Bundle\UserBundle\Form\NewsletterSubscribeType;
use Fa\Bundle\UserBundle\Form\NewsletterResubscribeType;
use Fa\Bundle\UserBundle\Form\NewsletterFeedbackType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
/**
 * This controller is used for admin side role management.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class NewsletterController extends CoreController
{
    /**
     * This is index action.
     *
     * @param Request $request
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function indexAction(Request $request)
    {
        $dotmailer = null;$newRecord = 0;

        if ($request->query->get('guid')) {
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('guid' => $request->query->get('guid')));
            $action    = $this->generateUrl('my_account_newsletter').'?guid='.$request->query->get('guid');
        } else {
            $action = $this->generateUrl('my_account_newsletter');
        }

        if (!$dotmailer) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $user->getEmail()));
            if (!$dotmailer) {
                $dotmailer = new Dotmailer();
                $dotmailer->setFadUser(1);
                $newRecord = 1;
            }
        }
        
      if(!empty($dotmailer)) {
            //echo 'getDotmailerNewsletterTypeId===<pre>';print_r($dotmailer->getDotmailerNewsletterTypeId());die;
            if($dotmailer->getDotmailerNewsletterUnsubscribe()==1) {
                return $this->redirect($this->generateUrl('newsletter_resubscribe').'?guid='.$request->query->get('guid'));
            }
            elseif($dotmailer->getDotmailerNewsletterUnsubscribe()==0 && empty($dotmailer->getDotmailerNewsletterTypeId()) && $dotmailer->getIsSuppressed()==0) { 
                return $this->redirect($this->generateUrl('newsletter_subscribe').'?guid='.$request->query->get('guid'));
            }
        }
       
        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(NewsletterUpdateType::class, $dotmailer, array('action' => $action));


        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $getClickVal = $form->get('clickedElementValue')->getData(); 
                
                if ($getClickVal =='unsubscribe') {                    
                    $feedbackUrl = $this->generateUrl('newsletter_feedback').'?guid='.$request->query->get('guid');
                    return $this->redirect($feedbackUrl);                                    
                } else {
                    if($dotmailer->getDotmailerNewsletterUnsubscribe()==1) {
                        $resubscribeUrl = $this->generateUrl('newsletter_resubscribe').'?guid='.$request->query->get('guid');
                        return $this->redirect($resubscribeUrl);
                    } elseif($newRecord ==1) {
                        $subscribeUrl = $this->generateUrl('newsletter_subscribe').'?guid='.$request->query->get('guid');
                        return $this->redirect($subscribeUrl);
                    } else {
                        $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('You have successfully updated your account.'), 'success');
                    }                   
                }
                return $this->redirect($action);
            } else {
                $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Form is not validated properly.'), 'error');
            }
        }

        $parameters  = array('form' => $form->createView());
        return $this->render('FaUserBundle:Newsletter:index.html.twig', $parameters);
    }
    
    /**
     * Newsletter ReSubscribe
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function newsletterResubscribeAction(Request $request)
    {
        $dotmailer = null;
        $userEmail = null;
        if ($request->query->get('guid')) {
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('guid' => $request->query->get('guid')));
            $action    = $this->generateUrl('newsletter_resubscribe').'?guid='.$request->query->get('guid');
            if($dotmailer) {
                $userEmail = $dotmailer->getEmail();
            }
        }
        
        if(!$dotmailer) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $user->getEmail()));
            $userEmail = $dotmailer->getEmail();
        } else {
            $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $userEmail));
        }
       
        if(!$dotmailer) {
            throw new NotFoundHttpException(410);
        }
        
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(NewsletterResubscribeType::class, null, array('method' => 'POST'));
        $error		 = '';
        
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            
            if ($form->isValid()) {
                if ($dotmailer && !$error) {
                    //update email alerts
                    if ($form->get('email_alert')->getData()) {
                        $user->setIsEmailAlertEnabled(1);
                    }
                    
                    //update third party email alerts
                    if ($form->get('third_party_email_alert')->getData()) {
                        $user->setIsThirdPartyEmailAlertEnabled(1);
                    }
                    
                    $this->getEntityManager()->persist($user);
                    $this->getEntityManager()->flush($user);
                                       
                    $newsletterSuccessUrl = $this->generateUrl('newsletter_resubscribe_success').'?guid='.$request->query->get('guid');
                    return $this->redirect($newsletterSuccessUrl);
                    //return new JsonResponse(array('success' => '1', 'user_id' => $dotmailer->getUser()->getId()));
                    
                } else {
                    return new JsonResponse(array('success' => '', 'user_id' => '', 'errorMessage' => $error));
                }
            } else {
                foreach ($form->getErrors(true, true) as  $formError) {
                    $error .= $formError->getMessage()."<br>";
                }
                
                return new JsonResponse(array('success' => '', 'user_id' => '', 'errorMessage' => $error));
            }
        }
        
        $parameters = array('form' => $form->createView());
        
        return $this->render('FaUserBundle:Newsletter:newsletterResubscribe.html.twig', $parameters);
    }
    
    /**
     * Newsletter Subscribe
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function newsletterSubscribeAction(Request $request)
    {
        $dotmailer = null;
        $userEmail = null;
        if ($request->query->get('guid')) {
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('guid' => $request->query->get('guid')));
            $action    = $this->generateUrl('newsletter_subscribe').'?guid='.$request->query->get('guid');
            if($dotmailer) {
                $userEmail = $dotmailer->getEmail();
            }
        }
        
        if(!$dotmailer) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $user->getEmail()));
            $userEmail = $dotmailer->getEmail();
        } else {
            $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $userEmail));
        }
        
        if(!$dotmailer) {
            throw new NotFoundHttpException(410);
        }
        
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(NewsletterResubscribeType::class, null, array('method' => 'POST'));
        $error		 = '';
        
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            
            if ($form->isValid()) {
                if ($dotmailer && !$error) {
                    //update email alerts
                    if ($form->get('email_alert')->getData()) {
                        $user->setIsEmailAlertEnabled(1);
                    }
                    
                    //update third party email alerts
                    if ($form->get('third_party_email_alert')->getData()) {
                        $user->setIsThirdPartyEmailAlertEnabled(1);
                    }
                    
                    $this->getEntityManager()->persist($user);
                    $this->getEntityManager()->flush($user);
                    
                    $newsletterSuccessUrl = $this->generateUrl('newsletter_subscribe_success').'?guid='.$request->query->get('guid');
                    return $this->redirect($newsletterSuccessUrl);
                    //return new JsonResponse(array('success' => '1', 'user_id' => $dotmailer->getUser()->getId()));
                    
                } else {
                    return new JsonResponse(array('success' => '', 'user_id' => '', 'errorMessage' => $error));
                }
            } else {
                foreach ($form->getErrors(true, true) as  $formError) {
                    $error .= $formError->getMessage()."<br>";
                }
                
                return new JsonResponse(array('success' => '', 'user_id' => '', 'errorMessage' => $error));
            }
        }
        
        $parameters = array('form' => $form->createView());
        
        return $this->render('FaUserBundle:Newsletter:newsletterSubscribe.html.twig', $parameters);
    }
    
    /**
     * Footer Newsletter
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function footerNewsletterAction(Request $request)
    {
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(NewsletterSubscribeType::class, null, array('method' => 'POST'));
        $error		 = '';
        if ($request->isXmlHttpRequest() && 'POST' === $request->getMethod()) {
            $form->handleRequest($request);
            
            if ($form->isValid()) {
                $loggedinUser = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData()));
                if (!$loggedinUser) {
                    $error = $this->get('translator')->trans('Unable to find user.', array(), 'frontend-show-ad');
                } elseif ($loggedinUser->getStatus() && $loggedinUser->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                    $error = $this->get('translator')->trans('Your account was blocked.', array(), 'frontend-show-ad');
                }
                
                if ($loggedinUser && !$error) {
                    //update email alerts
                    if ($form->get('email_alert')->getData()) {
                        $loggedinUser->setIsEmailAlertEnabled(1);
                    }
                    
                    //update third party email alerts
                    if ($form->get('third_party_email_alert')->getData()) {
                        $loggedinUser->setIsThirdPartyEmailAlertEnabled(1);
                    }
                    
                    $this->getEntityManager()->persist($loggedinUser);
                    $this->getEntityManager()->flush($loggedinUser);
                    
                    return new JsonResponse(array('success' => '1', 'user_id' => $loggedinUser->getId()));
                    
                } else {
                    return new JsonResponse(array('success' => '', 'user_id' => '', 'errorMessage' => $error));
                }
            } else {
                foreach ($form->getErrors(true, true) as  $formError) {
                    $error .= $formError->getMessage()."<br>";
                }
                
                return new JsonResponse(array('success' => '', 'user_id' => '', 'errorMessage' => $error));
            }
        }
        
        $parameters = array('form' => $form->createView());
        
        return $this->render('FaUserBundle:Newsletter:footerNewsletter.html.twig', $parameters);
    }
    
    
    /**
     * This is feedback action.
     *
     * @param Request $request
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function feedbackAction(Request $request)
    {
        $dotmailer = null;
        $userEmail = null;
        if ($request->query->get('guid')) {
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('guid' => $request->query->get('guid')));
            if($dotmailer) {
                $userEmail = $dotmailer->getEmail();
            }
        }
        
        if(!$dotmailer) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $user->getEmail()));
            $userEmail = $dotmailer->getEmail();
        }
        
        if(!$dotmailer) {
            throw new NotFoundHttpException(410);
        }
        
        $newsletterFeedback = $this->getRepository('FaUserBundle:NewsletterFeedback')->findOneBy(array('email' => $userEmail, 'guid' => $request->query->get('guid')));
        if(!$newsletterFeedback) {
            $newsletterFeedback = new NewsletterFeedback();
            $newsletterFeedback->setCreatedAt(time());
        }
        
        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(NewsletterFeedbackType::class, $newsletterFeedback, array());
        
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $newsletterFeedback->setEmail($userEmail);
                $newsletterFeedback->setUpdatedAt(time());
                $newsletterFeedback->setGuid($request->query->get('guid'));
                $newsletterFeedback->setReason($form->get('reason')->getData());
                if ($form->get('reason')->getData() && $form->get('reason')->getData() == '6' && $form->get('otherReason')->getData() != '') {
                    $newsletterFeedback->setOtherReason($form->get('otherReason')->getData());
                } else {
                    $newsletterFeedback->setOtherReason(null);
                }
                
                $this->getEntityManager()->persist($newsletterFeedback);
                $this->getEntityManager()->flush($newsletterFeedback);
                $this->container->get('session')->set('newsletter_feedback_success', 1);
                $feedbackUrl = $this->generateUrl('newsletter_feedback_success').'?guid='.$request->query->get('guid');
                return $this->redirect($feedbackUrl);
            }
        }
        
        $parameters  = array('form' => $form->createView());
        return $this->render('FaUserBundle:Newsletter:newsletterFeedback.html.twig', $parameters);
        
    }
    
    /**
     * This is feedback success action.
     *
     * @param Request $request
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function successAction(Request $request)
    {
        if ($request->query->get('guid')) {
            $newsletterFeedback = $this->getRepository('FaUserBundle:NewsletterFeedback')->findOneBy(array('guid' => $request->query->get('guid')));
        }
        
        /*if(!$newsletterFeedback) {
            throw new NotFoundHttpException(410);
        }*/
        
        $gaString = '';
        if ($this->container->get('session')->has('newsletter_feedback_success')) {
            //get the feeback option for GA tracking
            if($newsletterFeedback->getReason() != '6') {
                $feedbackOption = $this->getRepository('FaUserBundle:NewsletterFeedback')->getFeedbackOptions();
                $gaString = $feedbackOption[$newsletterFeedback->getReason()];
            } elseif ($newsletterFeedback->getReason() == '6') {
                $gaString = 'Other - '.$newsletterFeedback->getOtherReason();
            }
        }
        
        $this->container->get('session')->remove('newsletter_feedback_success');
        $parameters  = array('guid' => $request->query->get('guid'), 'feedback' => $newsletterFeedback, 'gaCode' => $gaString);
        return $this->render('FaUserBundle:Newsletter:feedbackSuccess.html.twig', $parameters);
    }
    
    /**
     * This is resubscribe success action.
     *
     * @param Request $request
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function resubscribeSuccessAction(Request $request)
    {        
        return $this->render('FaUserBundle:Newsletter:newsletterResubscribeSuccess.html.twig');
    }
    
    /**
     * This is subscribe success action.
     *
     * @param Request $request
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function subscribeSuccessAction(Request $request)
    {
        return $this->render('FaUserBundle:Newsletter:newsletterSubscribeSuccess.html.twig');
    }
}
