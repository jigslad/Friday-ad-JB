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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\UserBundle\Controller\ThirdPartyLoginController;
use Fa\Bundle\DotMailerBundle\Entity\Dotmailer;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentCyberSourceRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Form\UserAccountDetailType;
use Fa\Bundle\UserBundle\Form\UserAccountProfileType;
use Fa\Bundle\UserBundle\Form\UserCardType;
use Fa\Bundle\UserBundle\Form\NewsletterType;

/**
 * This controller is used for editing user's account information.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class MyAccountController extends ThirdPartyLoginController
{
    /**
     * Show user ads.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function myAccountAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser     = $this->getLoggedInUser();
        $facebookLoginUrl = null;
        $googleLoginUrl   = null;

        if (!$loggedinUser->getFacebookId()) {
            $facebookLoginUrl = $this->initFacebook('my_account_facebook_login');
        }
        if (!$loggedinUser->getGoogleId()) {
            $googleLoginUrl = $this->initGoogle('my_account_google_login');
        }

        $userDetailForm = $this->getUserAccountDetailForm($request, $loggedinUser);
        $shopPackageDetail = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackageDetail($loggedinUser->getId());
        $userAccountProfileForm = $this->getUserAccountProfileForm($request, $loggedinUser, $shopPackageDetail);
        $userCardForm   = $this->getUserCardForm($request, $loggedinUser);
        $paymentTokens  = $this->getRepository('FaPaymentBundle:PaymentTokenization')->getUserTokens($loggedinUser->getId());
        $userAddresses  = $this->getRepository('FaUserBundle:UserAddressBook')->getAddressByUser($loggedinUser->getId(), null, true);
        $userNewsletterPrefForm = $this->getUserNewsletterPrefForm($request, $loggedinUser);
        $newsletterMainCategoryArray = $this->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getMainCategoryArray($this->container);
        $invoiceMonths = $this->getRepository('FaPaymentBundle:Payment')->getInvoiceMonths($loggedinUser->getId());
        $userInvoices  = array();
        if (count($invoiceMonths)) {
            $invoiceMonthKeys = array_keys($invoiceMonths);
            list($invoiceMonth, $invoiceYear) = explode('_', $invoiceMonthKeys[0]);
            $userInvoices = $this->getRepository('FaPaymentBundle:PaymentTransactionDetail')->getInvoiceDetailsForUserByMonth($loggedinUser->getId(), $invoiceMonth, $invoiceYear);
        }

        // to redirect to my account.
        if ($userDetailForm instanceof RedirectResponse) {
            return $userDetailForm;
        }
        if ($userAccountProfileForm instanceof RedirectResponse) {
            return $userAccountProfileForm;
        }
        if ($userNewsletterPrefForm instanceof RedirectResponse) {
            return $userNewsletterPrefForm;
        }
        if ($userCardForm instanceof RedirectResponse) {
            return $userCardForm;
        }


        $parameters = array(
            'userAddresses'  => $userAddresses,
            'userDetailForm' => $userDetailForm,
            'facebookLoginUrl' => $facebookLoginUrl,
            'googleLoginUrl' => $googleLoginUrl,
            'paymentTokens' => $paymentTokens,
            'userAccountProfileForm' => $userAccountProfileForm,
            'shopPackageDetail' => $shopPackageDetail,
            'userNewsletterPrefForm' => $userNewsletterPrefForm,
            'newsletterMainCategoryArray' => array_keys($newsletterMainCategoryArray),
            'userCardForm' => $userCardForm,
            'invoiceMonths' => $invoiceMonths,
            'userInvoices' => $userInvoices,
        );

        return $this->render('FaUserBundle:MyAccount:myAccount.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @param Request $request      A Request object.
     * @param object  $loggedinUser Logged in object.
     *
     * @return object
     */
    private function getUserAccountDetailForm(Request $request, $loggedinUser)
    {
        // initialize form manager service
        $user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
        $oldUser     = $user;
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserAccountDetailType::class, $user);
        $originalPassword = $user->getPassword();
        $oldEmail         = $user->getEmail();
        $oldIsPrivatePhoneNumber = $user->getIsPrivatePhoneNumber();
        $oldPhoneNumber   = $user->getPhone();

        if ('POST' === $request->getMethod() && $request->request->has($form->getName())) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getEntityManager();
                $em->flush();
                $plainPassword = ($form->has('new_password') ? $form->get('new_password')->getData() : null);
                if (!empty($plainPassword)) {
                    //encode the password
                    $encoder = $this->container->get('security.encoder_factory')->getEncoder($user); //get encoder for hashing pwd later
                    $password = $encoder->encodePassword($plainPassword, $user->getSalt());
                    $user->setPassword($password);

                    $em->persist($user);
                    $em->flush();
                }

                // updating email and social media connections.
                if ($oldEmail != $form->get('email')->getData()) {
                    $user->setEmail($form->get('email')->getData());
                    $user->setUserName($form->get('email')->getData());
                    $user->setFacebookId(null);
                    $user->setGoogleId(null);
                    $user->setIsFacebookVerified(0);
                    $em->persist($user);
                    $em->flush();
                }

                // update yac number if phone number changes and user has set privacy number.
                if ($form->get('is_private_phone_number')->getData() && $oldPhoneNumber != $form->get('phone')->getData()) {
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:user-ad-yac-number edit --user_id='.$loggedinUser->getId().' >/dev/null &');
                }

                // update yac number if privacy phone number setting is changes.
                if ($oldIsPrivatePhoneNumber != $form->get('is_private_phone_number')->getData()) {
                    if ($form->get('is_private_phone_number')->getData()) {
                        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:user-ad-yac-number allocate --user_id='.$loggedinUser->getId().' >/dev/null &');
                    } elseif (!$form->get('is_private_phone_number')->getData()) {
                        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:user-ad-yac-number setsold --user_id='.$loggedinUser->getId().' >/dev/null &');
                    }
                }

                $this->container->get('session')->getFlashBag()->add('account_detail_success', $this->get('translator')->trans('Your detail updated successfully.', array(), 'frontend-user-account-detail'));
                return $this->redirectToRoute('my_account');
            } elseif ($oldEmail != $form->get('email')->getData()) {
                $oldUser->setUserName($oldEmail);
            }
        }

        return $form->createView();
    }

    /**
     * Displays a form to edit an existing User profile.
     *
     * @param Request $request           A Request object.
     * @param object  $loggedinUser      Logged in object.
     * @param object  $shopPackageDetail Shop package object.
     *
     * @return object
     */
    private function getUserAccountProfileForm(Request $request, $loggedinUser, $shopPackageDetail)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserAccountProfileType::class, $loggedinUser);

        if (($shopPackageDetail && !$shopPackageDetail->getPayment()) || !$shopPackageDetail) {
            $form->remove('payment_source');
        }

        if ('POST' === $request->getMethod() && $request->request->has($form->getName())) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getEntityManager();
                $em->flush();

                $this->container->get('session')->getFlashBag()->add('user_account_profile_success', $this->get('translator')->trans('Your profile detail updated successfully.', array(), 'frontend-user-account-profile'));
                return $this->redirectToRoute('my_account');
            }
        }

        return $form->createView();
    }

    /**
     * Displays a form to add new card.
     *
     * @param Request $request      A Request object.
     * @param object  $loggedinUser Logged in object.
     *
     * @return object
     */
    private function getUserCardForm(Request $request, $loggedinUser)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserCardType::class);

        if ('POST' === $request->getMethod() && $request->request->has($form->getName())) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $cyberSourceManager  = $this->get('fa.cyber.source.manager');
                $billTo              = $this->getBillToArray($loggedinUser, $form);
                $userAddressBookInfo = $this->getBillToArray($loggedinUser, $form, true);
                $cardInfo            = $this->getCardInfoArray($form);
                $cyberSourceReply    = $cyberSourceManager->createCustomerProfile($billTo, $cardInfo);

                if ($cyberSourceReply && $cyberSourceReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE) {
                    // save token.
                    if ($cyberSourceReply->paySubscriptionCreateReply && $cyberSourceReply->paySubscriptionCreateReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE && $cyberSourceReply->paySubscriptionCreateReply->subscriptionID) {
                        $subscriptionId = $cyberSourceReply->paySubscriptionCreateReply->subscriptionID;
                        $cardHolderName = $form->get('card_holder_name')->getData();
                        $cardType       = $form->get('card_type')->getData();
                        if ($form->get('card_number')->getData()) {
                            $cardNumber = substr($form->get('card_number')->getData(), -4);
                        }
                        $this->getRepository('FaPaymentBundle:PaymentTokenization')->addNewToken($loggedinUser->getId(), $subscriptionId, $cardNumber, $cardHolderName, $cardType, PaymentRepository::PAYMENT_METHOD_CYBERSOURCE, $billTo);
                        $this->getRepository('FaUserBundle:UserAddressBook')->addUserAddress($loggedinUser, $userAddressBookInfo);

                        $this->container->get('session')->getFlashBag()->add('card_success', $this->get('translator')->trans('New card added successfully.', array(), 'frontend-new-card'));
                    }
                } elseif ($cyberSourceReply) {
                    $this->container->get('session')->getFlashBag()->add('error', $cyberSourceManager->getError($cyberSourceReply->reasonCode));
                }

                return $this->redirectToRoute('my_account');
            }
        }

        return $form->createView();
    }

    /**
     * Displays a form to edit newsletter pref..
     *
     * @param Request $request      A Request object.
     * @param object  $loggedinUser Logged in object.
     *
     * @return object
     */
    private function getUserNewsletterPrefForm(Request $request, $loggedinUser)
    {
        $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $loggedinUser->getEmail()));
        if (!$dotmailer) {
            $dotmailer = new Dotmailer();
            $dotmailer->setFadUser(1);
        }

        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(NewsletterType::class, $dotmailer);

        if ('POST' === $request->getMethod() && $request->request->has($form->getName())) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->container->get('session')->getFlashBag()->add('newsletter_success', $this->get('translator')->trans('Newsletter preferences updated successfully.', array(), 'frontend-newsletter-pref'));
                return $this->redirectToRoute('my_account');
            }
        }

        return $form->createView();
    }

    /**
     * This action is used for connecting through facebook.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function userDetailFacebookLoginAction(Request $request)
    {
        $loggedinUser = $this->getLoggedInUser();
        $response     = $this->processFacebook($request, 'my_account_facebook_login', 'my_account', false, null, true);

        if (is_array($response)) {
            if ($response['user_email'] != $loggedinUser->getEmail()) {
                $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Facebook email and your account email is different.', array(), 'frontend-user-account-detail'));
            } elseif ($response['user_email'] == $loggedinUser->getEmail() && isset($response['user_facebook_id'])) {
                $em = $this->getEntityManager();
                $loggedinUser->setFacebookId($response['user_facebook_id']);
                //set facebook verified field
                if (isset($response['user_is_facebook_verified']) && $response['user_is_facebook_verified']) {
                    $loggedinUser->setIsFacebookVerified(1);
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('no_facebook_signup', null, $loggedinUser->getId());
                }
                $em->persist($loggedinUser);
                $em->flush();
                $this->container->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('You have successfully connected with Facebook.', array(), 'frontend-user-account-detail'));
            }
            return $this->redirectToRoute('my_account');
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Facebook (First Name, Last Name, Email).', array(), 'frontend-user-account-detail'), 'my_account', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('my_account');
        } else {
            return $response;
        }
    }

    /**
     * This action is used for connecting through google.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function userDetailGoogleLoginAction(Request $request)
    {
        $loggedinUser = $this->getLoggedInUser();
        $response     = $this->processGoogle($request, 'my_account_google_login', 'my_account', true);

        if (is_array($response)) {
            if ($response['user_email'] != $loggedinUser->getEmail()) {
                $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Google email and your account email is different.', array(), 'frontend-user-account-detail'));
            } elseif ($response['user_email'] == $loggedinUser->getEmail() && isset($response['user_google_id'])) {
                $em = $this->getEntityManager();
                $loggedinUser->setGoogleId($response['user_google_id']);
                $em->persist($loggedinUser);
                $em->flush();
                $this->container->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('You have successfully connected with Google.', array(), 'frontend-user-account-detail'));
            }
            return $this->redirectToRoute('my_account');
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Facebook (First Name, Last Name, Email).', array(), 'frontend-user-account-detail'), 'my_account', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('my_account');
        } else {
            return $response;
        }
    }

    /**
     * Update payapl email.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxUpdatePaypalEmailAction(Request $request)
    {
        if ($this->isAuth() && $request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $loggedinUser = $this->getLoggedInUser();

            $paypalEmail     = trim($request->get('paypal_email'));
            $paypalFirstName = trim($request->get('paypal_first_name'));
            $paypalLastName  = trim($request->get('paypal_last_name'));
            $isPaypalVerifiedEmail = false;
            if ($paypalEmail && $paypalFirstName && $paypalLastName) {
                $isPaypalVerifiedEmail = $this->container->get('fa.paypal.account.verification.manager')->verifyPaypalAccountByEmail($paypalEmail, 'NAME', $paypalFirstName, $paypalLastName);
                if ($isPaypalVerifiedEmail) {
                    $loggedinUser->setPaypalEmail($paypalEmail);
                    $loggedinUser->setPaypalFirstName($paypalFirstName);
                    $loggedinUser->setPaypalLastName($paypalLastName);
                    $loggedinUser->setIsPaypalVefiried(1);
                    $this->getEntityManager()->persist($loggedinUser);
                    $this->getEntityManager()->flush($loggedinUser);
                    $successMsg = $this->get('translator')->trans('Paypal details updated successfully.', array(), 'frontend-paypal-email');
                } else {
                    $error = $this->get('translator')->trans('Paypal account is not verified.', array(), 'frontend-paypal-email');
                }
            } else {
                $error = $this->get('translator')->trans('Paypal account is not verified.', array(), 'frontend-paypal-email');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        } else {
            return new Response();
        }
    }

    /**
     * Cancel user subscription.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function userCancelSubscriptionAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();

        $activePackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackageDetail($loggedinUser->getId());

        if ($activePackage && $activePackage->getPackage() && $activePackage->getPackage()->getPrice()) {
            try {
                $this->getEntityManager()->beginTransaction();
                $activePackage->setPayment(null);
                $activePackage->setCancelledAt(time());
                $this->getEntityManager()->persist($activePackage);
                $this->getEntityManager()->flush($activePackage);
                $this->getEntityManager()->getConnection()->commit();
            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                CommonManager::sendErrorMail($this->container, 'Error: Problem in cancelling subscription', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Sorry there is an issue in cancelling subscription.'), 'my_account');
            }

            $this->container->get('session')->getFlashBag()->add('user_account_profile_success', $this->get('translator')->trans('Your current upgraded profile package has been cancelled successfully.', array(), 'frontend-user-account-profile'));
        } else {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You can not cancel basic package subscription.', array(), 'frontend-user-account-profile'));
        }

        return $this->redirectToRoute('my_account');
    }

    /**
     * Deactivate user account.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function deactivateAccountAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();

        try {
            $this->getEntityManager()->beginTransaction();

            $loggedinUser->setStatus($this->getEntityManager()->getReference('FaEntityBundle:Entity', EntityRepository::USER_STATUS_INACTIVE_ID));
            $this->getEntityManager()->persist($loggedinUser);
            $this->getEntityManager()->flush($loggedinUser);

            $this->getRepository('FaAdBundle:Ad')->blockUnblockAdByUserId($loggedinUser->getId(), 1);
            $this->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByUserId($loggedinUser->getId(), $this->container);
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->getConnection()->rollback();
            CommonManager::sendErrorMail($this->container, 'Error: Problem in deactivating account', $e->getMessage(), $e->getTraceAsString());
            return $this->handleMessage($this->get('translator')->trans('Sorry there is an issue in updating user status.'), 'my_account');
        }

        $this->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();
        $this->container->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Your account deactivated successfully.', array(), 'frontend-deactivate-account'));

        return $this->redirectToRoute('fa_frontend_homepage');
    }

    /**
     * Get billing array.
     *
     * @param object  $loggedinUser   Logged in user object.
     * @param object  $form           Form object.
     * @param boolean $forAddressBook Flag for user address book.
     *
     * @return array
     */
    private function getBillToArray($loggedinUser, $form, $forAddressBook = false)
    {
        $billTo    = array();
        $firstName = $loggedinUser->getFirstName() ? $loggedinUser->getFirstName() : $loggedinUser->getUserName();
        $lastName  = $loggedinUser->getLastName() ? $loggedinUser->getLastName() : $loggedinUser->getUserName();
        $street1   = trim($form->get('street_address')->getData().', '.$form->get('street_address_2')->getData(), ', ');

        $billTo['firstName'] = $firstName;
        $billTo['lastName']  = $lastName;
        if (!$forAddressBook) {
            $billTo['street1'] = $street1;
        } else {
            $billTo['street_address']   = $form->get('street_address')->getData();
            $billTo['street_address_2'] = $form->get('street_address_2')->getData();
        }
        $billTo['city'] = $form->get('town')->getData();
        if ($form->get('county')->getData()) {
            $billTo['state'] = $form->get('county')->getData();
        }
        $billTo['postalCode'] = $form->get('zip')->getData();
        $billTo['country']    = 'UK';
        $billTo['email']      = $loggedinUser->getEmail();
        $billTo['ipAddress']  = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();

        return array_map('trim', $billTo);
    }

    /**
     * Get card information array.
     *
     * @param object $form Form object.
     *
     * @return array
     */
    private function getCardInfoArray($form)
    {
        $cardInfo                    = array();
        $cardInfo['accountNumber']   = $form->get('card_number')->getData();
        $cardInfo['expirationMonth'] = $form->get('card_expity_month')->getData();
        $cardInfo['expirationYear']  = $form->get('card_expity_year')->getData();
        $cardInfo['cvIndicator']     = 1;
        $cardInfo['cvNumber']        = $form->get('card_security_code')->getData();
        $cardInfo['cardType']        = $form->get('card_type')->getData();

        return $cardInfo;
    }

    /**
     * Send create password link to user.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function sendCreatePasswordLinkAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser   = $this->getLoggedInUser();
        $encryption_key = $this->container->getParameter('reset_password_encryption_key');

        if (!$loggedinUser->getPassword()) {
            $resetPasswordLink = $this->generateUrl('reset_password', array('id' => CommonManager::encryptDecrypt($encryption_key, $loggedinUser->getId()), 'key' => $loggedinUser->getEncryptedKey(), 'mail_time' => CommonManager::encryptDecrypt($encryption_key, time())), true);
            $this->get('fa.mail.manager')->send($loggedinUser->getEmail(), 'create_password_link', array('user_first_name' => $loggedinUser->getFirstName(), 'user_last_name' => $loggedinUser->getLastName(), 'user_email_address' => $loggedinUser->getEmail(), 'url_password_reset' => $resetPasswordLink), CommonManager::getCurrentCulture($this->container));

            $this->container->get('session')->getFlashBag()->add('account_detail_success', $this->get('translator')->trans('You have been sent an email with a link to create a password.', array(), 'frontend-user-account-detail'));
        } else {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You already have old password.', array(), 'frontend-user-account-detail'));
        }

        return $this->redirectToRoute('my_account');
    }

    /**
     * Show user's invoice receipt.
     *
     * @param Integer $orderId Cart code.
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function invoiceReceiptAction($orderId, Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $action       = $request->get("action", null);

        $paymentObj = $this->getRepository('FaPaymentBundle:Payment')->findOneBy(array('cart_code' => $orderId, 'user' => $loggedinUser->getId(), 'payment_method' => array(PaymentRepository::PAYMENT_METHOD_CYBERSOURCE, PaymentRepository::PAYMENT_METHOD_PAYPAL, PaymentRepository::PAYMENT_METHOD_CYBERSOURCE_RECURRING,  PaymentRepository::PAYMENT_METHOD_AMAZONPAY)));
        if (!$paymentObj) {
            return $this->handleMessage($this->get('translator')->trans('You do not have permission to access this resource.', array(), 'frontend-user-invoices'), 'my_account', array(), 'error');
        } else {
            $userInvoiceDetail = $this->getRepository('FaPaymentBundle:PaymentTransactionDetail')->getInvoiceDetailForUserByPaymentId($loggedinUser->getId(), $paymentObj->getId());
            $parameters = array(
                'action' => $action,
                'userInvoiceDetail' => $userInvoiceDetail,
            );

            return $this->render('FaUserBundle:MyAccount:invoiceReceipt.html.twig', $parameters);
        }
    }

    /**
     * Show users invoice.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxLoadInvoiceAction(Request $request)
    {
        $error           = '';
        $listHtmlContent = '';

        if ($this->isAuth() && $request->isXmlHttpRequest()) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $loggedinUser = $this->getLoggedInUser();
            $invoiceMonthYear = $request->get('invoiceMonthYear');
            $invoiceMonths = $this->getRepository('FaPaymentBundle:Payment')->getInvoiceMonths($loggedinUser->getId());
            if (!in_array($invoiceMonthYear, array_keys($invoiceMonths))) {
                $error = $this->get('translator')->trans('Invalid month selected.', array(), 'frontend-user-invoices');
            } else {
                $userInvoices  = array();
                if (count($invoiceMonths)) {
                    list($invoiceMonth, $invoiceYear) = explode('_', $invoiceMonthYear);
                    $userInvoices = $this->getRepository('FaPaymentBundle:PaymentTransactionDetail')->getInvoiceDetailsForUserByMonth($loggedinUser->getId(), $invoiceMonth, $invoiceYear);
                }

                $listHtmlContent = $this->renderView('FaUserBundle:MyAccount:listInvoice.html.twig', array('userInvoices' => $userInvoices));
            }

            return new JsonResponse(array('error' => $error, 'listHtmlContent' => $listHtmlContent));
        } else {
            return new Response();
        }
    }
}
