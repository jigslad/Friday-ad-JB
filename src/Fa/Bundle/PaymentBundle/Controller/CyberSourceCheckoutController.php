<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\PaymentBundle\Repository\PaymentCyberSourceRepository;
use Symfony\Component\Form\FormError;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Manager\CyberSourceManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Fa\Bundle\PaymentBundle\Form\CyberSourceCheckoutType;

/**
 * This controller is used for cyber source payment.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CyberSourceCheckoutController extends CoreController
{
    /**
     * Checkout action for cyber source.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */

    public function checkoutAction(Request $request)
    {
        $cybersource3DSecureResponseFlag = false;
        if ($request->get('PaRes') && $request->get('MD')) {
            $cybersource3DSecureResponseFlag = true;
        }
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $loggedinUser = $this->getLoggedInUser();
        if ($request->get('subscription') == 1) {
            $cart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container, false, true);
        } else {
            $cart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
        }

        $cartDetails       = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        $allow_zero_amount = $request->get('trail') ? true : false;
        $userPackage       = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($loggedinUser);

        //check for cart price and item
        if ((!$cart->getAmount() && !$allow_zero_amount) || !count($cartDetails)) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('There is no item in your cart.', array(), 'frontend-cyber-source'));
            return new RedirectResponse($this->container->get('router')->generate('show_cart'));
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(CyberSourceCheckoutType::class, array('subscription' => $request->get('subscription')));
        $gaStr       = '';

        if ('POST' === $request->getMethod() || $this->container->get('session')->has('upgrade_cybersource_params_'.$loggedinUser->getId()) ) {
        	
        	if ($cybersource3DSecureResponseFlag) { 
                $csrfToken     = $this->container->get('form.csrf_provider')->generateCsrfToken('fa_payment_cyber_source_checkout');
                $cyberSourceData = $this->get('session')->get('cybersource_params_'.$loggedinUser->getId()) + array('_token' => $csrfToken);
                if (array_key_exists('subscription', $cyberSourceData)) {
                    $request->attributes->set('subscription', $cyberSourceData['subscription']);
                    unset($cyberSourceData['subscription']);
                }
                // Bind data from session
                $form->submit($cyberSourceData);
            } elseif ($this->container->get('session')->has('upgrade_cybersource_params_'.$loggedinUser->getId())) { 
            	$csrfToken     = $this->container->get('form.csrf_provider')->generateCsrfToken('fa_payment_cyber_source_checkout');
            	$upgradeSourceData = $this->get('session')->get('upgrade_cybersource_params_'.$loggedinUser->getId()) + array('_token' => $csrfToken);
            	$form->submit($upgradeSourceData);
            } else {
                $form->handleRequest($request);
            }

            if ($form->isValid() || $this->container->get('session')->has('upgrade_cybersource_params_'.$loggedinUser->getId()) ) {
                if (!$cybersource3DSecureResponseFlag) {
                	if(isset($upgradeSourceData) && !empty($upgradeSourceData)) {
                		$this->get('session')->set('cybersource_params_'.$loggedinUser->getId(), array_merge($form->getData(), $upgradeSourceData));
                	} else {
                		$this->get('session')->set('cybersource_params_'.$loggedinUser->getId(), array_merge($form->getData(), $request->get('fa_payment_cyber_source_checkout')));
                	}
                }
                $cyberSourceManager  = $this->get('fa.cyber.source.manager');
                if ($request->get('subscription')) {
                    $cyberSourceManager->setMerchantReferenceCodeForSubscription();
                }
                $billTo              = $this->getBillToArray($loggedinUser, $form);
                $userAddressBookInfo = $this->getBillToArray($loggedinUser, $form, true);
                $cardInfo            = $this->getCardInfoArray($form);
                $paymentMethod       = $form->get('payment_method')->getData();
                $saveToken           = false;
                $subscriptionId      = null;
                if ($paymentMethod) {
                    $token = $this->getRepository('FaPaymentBundle:PaymentTokenization')->isValidUserToken($loggedinUser->getId(), $paymentMethod);
                    if (!$token) {
                        $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Invalid token.', array(), 'frontend-cyber-source'));
                    } else {
                        $tokenValue = unserialize($token->getValue());
                        if (is_array($tokenValue) && count($tokenValue) && isset($tokenValue['billto'])) {
                            $billTo = $tokenValue['billto'];
                            $userAddressBookInfo = $billTo;
                        }

                        $subscriptionId            = $token->getSubscriptionId();
                        $recurringSubscriptionInfo = array('subscriptionID' => $token->getSubscriptionId());
                        if ($allow_zero_amount) {
                            $cyberSourceReply = $cyberSourceManager->checkSavedToken($subscriptionId);
                        } else {
                            if ($cybersource3DSecureResponseFlag) {
                                $cyberSourceReply = $cyberSourceManager->getCyberSourceReplyForToken($loggedinUser, $billTo, $cart, $cartDetails, $recurringSubscriptionInfo, $allow_zero_amount, true, array('PaRes' => $request->get('PaRes'), 'MD' => $request->get('MD')));
                            } else {
                                $cyberSourceReply = $cyberSourceManager->getCyberSourceReplyForToken($loggedinUser, $billTo, $cart, $cartDetails, $recurringSubscriptionInfo, $allow_zero_amount, true);
                            }
                        }
                    }
                } else {
                    $saveToken                 = $form->get('is_save_credit_card')->getData();
                    $recurringSubscriptionInfo = array('frequency' => 'on-demand');
                    if ($cybersource3DSecureResponseFlag) {
                        $cyberSourceReply = $cyberSourceManager->getCyberSourceReply($loggedinUser, $billTo, $cardInfo, $cart, $cartDetails, $saveToken, $recurringSubscriptionInfo, $allow_zero_amount, true, array('PaRes' => $request->get('PaRes'), 'MD' => $request->get('MD')));
                    } else {
                        $cyberSourceReply = $cyberSourceManager->getCyberSourceReply($loggedinUser, $billTo, $cardInfo, $cart, $cartDetails, $saveToken, $recurringSubscriptionInfo, $allow_zero_amount, true);
                    }
                }
                
                //remove session for upgrade modal box
                $this->container->get('session')->remove('upgrade_cybersource_params_'.$loggedinUser->getId());
                
                if ((!$cybersource3DSecureResponseFlag && $cyberSourceReply && property_exists($cyberSourceReply, 'reasonCode') && $cyberSourceReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE) || ($cyberSourceReply && property_exists($cyberSourceReply, 'reasonCode') && $cyberSourceReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE && property_exists($cyberSourceReply, "payerAuthValidateReply") && property_exists($cyberSourceReply->payerAuthValidateReply, "authenticationResult") && in_array($cyberSourceReply->payerAuthValidateReply->authenticationResult, array(0, 1)))) {
                    $cartValue = unserialize($cart->getValue());
                    if (!is_array($cartValue)) {
                        $cartValue = array();
                    }
                    //check for 3d secure xid
                    if ($cyberSourceReply && property_exists($cyberSourceReply, "payerAuthValidateReply") && property_exists($cyberSourceReply->payerAuthValidateReply, "xid") && isset($cartValue['cyber_source_3d_response']) && property_exists($cartValue['cyber_source_3d_response'], "payerAuthValidateReply") && property_exists($cartValue['cyber_source_3d_response']->payerAuthValidateReply, "xid") && $cartValue['cyber_source_3d_response']->payerAuthEnrollReply->xid != $cyberSourceReply->payerAuthValidateReply->xid) {
                        return $this->handleMessage($this->get('translator')->trans('Problem in payment, transaction id does not match.', array(), 'frontend-cyber-source'), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error', $cybersource3DSecureResponseFlag);
                    }
                    // save token if user want to save.
                    if ($saveToken && property_exists($cyberSourceReply, 'paySubscriptionCreateReply') && property_exists($cyberSourceReply->paySubscriptionCreateReply, 'reasonCode') && $cyberSourceReply->paySubscriptionCreateReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE && property_exists($cyberSourceReply->paySubscriptionCreateReply, 'subscriptionID') && $cyberSourceReply->paySubscriptionCreateReply->subscriptionID) {
                        $subscriptionId = $cyberSourceReply->paySubscriptionCreateReply->subscriptionID;
                        $cardHolderName = $form->get('card_holder_name')->getData();
                        $cardType       = $form->get('card_type')->getData();
                        if ($form->get('card_number')->getData()) {
                            $cardNumber = substr($form->get('card_number')->getData(), -4);
                        }
                        $this->getRepository('FaPaymentBundle:PaymentTokenization')->addNewToken($loggedinUser->getId(), $subscriptionId, $cardNumber, $cardHolderName, $cardType, PaymentRepository::PAYMENT_METHOD_CYBERSOURCE, $billTo);
                    }
                    //update cart value and payment method.
                    $this->getEntityManager()->beginTransaction();
                    try {
                        $cartValue = array_merge($cartValue, array('billing_info' => $billTo, 'user_address_info' => $userAddressBookInfo, 'cyber_source_response' => $cyberSourceReply, 'subscriptionID' => $subscriptionId));
                        $cart->setValue(serialize($cartValue));
                        $cart->setPaymentMethod(PaymentRepository::PAYMENT_METHOD_CYBERSOURCE);
                        $this->getEntityManager()->persist($cart);
                        $this->getEntityManager()->flush($cart);
                        $paymentId = $this->getRepository('FaPaymentBundle:Payment')->processPaymentSuccess($cart->getCartCode(), null, $this->container);
                        $this->getEntityManager()->getConnection()->commit();

                        try {
                            //send ads for moderation
                            $this->getRepository('FaAdBundle:AdModerate')->sendAdsForModeration($paymentId, $this->container);

                            if ($request->get('subscription') == 1) {
                                $this->sendSubscriptionBillingEmail($loggedinUser, $cartDetails, $userPackage, $cart, $subscriptionId, $allow_zero_amount);
                            }

                            if ($request->get('subscription') == 1) {
                                $packageObj = null;
                                $values = unserialize($cartDetails[0]['value']);
                                $package = $values['package'];
                                $p = array_pop($package);

                                if ((isset($p['package_for']) && $p['package_for'] == 'shop')) {
                                    $packageObj = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $p['id']));
                                }
                                return $this->handleMessage($this->get('translator')->trans('You have successfully upgraded to %package-name%. Please check and update your profile information now!. Your transaction ID is %transaction_id%.', array('%package-name%' => ($packageObj ? $packageObj->getTitle() : ''), '%transaction_id%' => $cart->getCartCode()), 'frontend-cyber-source'), 'my_profile', array('transactionId' => $cart->getCartCode()), 'success', $cybersource3DSecureResponseFlag);
                            } else {
                                return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-cyber-source'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success', $cybersource3DSecureResponseFlag);
                            }
                        } catch (\Exception $e) {
                            CommonManager::sendErrorMail($this->container, 'Error: Problem in sending user subscription email', $e->getMessage(), $e->getTraceAsString());
                            if ($request->get('subscription') == 1) {
                                return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-cyber-source'), 'my_profile', array('cartCode' => $cart->getCartCode()), 'success', $cybersource3DSecureResponseFlag);
                            } else {
                                return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-cyber-source'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success', $cybersource3DSecureResponseFlag);
                            }
                        }
                    } catch (\Exception $e) {
                        CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                        $this->getEntityManager()->getConnection()->rollback();

                        if ($request->get('subscription') == 1) {
                            return $this->handleMessage($this->get('translator')->trans('Problem in payment. Your transaction ID is %transaction_id%.', array('%transaction_id%' => $cart->getCartCode()), 'frontend-cyber-source'), 'my_profile', array(), 'error', $cybersource3DSecureResponseFlag);
                        } else {
                            return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-cyber-source'), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error', $cybersource3DSecureResponseFlag);
                        }
                    }
                    return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-cyber-source'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success', $cybersource3DSecureResponseFlag);
                } elseif ($cyberSourceReply && property_exists($cyberSourceReply, 'decision') && $cyberSourceReply->decision == PaymentCyberSourceRepository::SUCCESS_3D_REASON_TEXT && property_exists($cyberSourceReply, 'reasonCode') && $cyberSourceReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_3D_REASON_CODE && property_exists($cyberSourceReply, 'payerAuthEnrollReply') && $cyberSourceReply->payerAuthEnrollReply && property_exists($cyberSourceReply->payerAuthEnrollReply, 'reasonCode') && $cyberSourceReply->payerAuthEnrollReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_3D_REASON_CODE && property_exists($cyberSourceReply->payerAuthEnrollReply, 'acsURL') && $cyberSourceReply->payerAuthEnrollReply->acsURL && property_exists($cyberSourceReply->payerAuthEnrollReply, 'paReq') && $cyberSourceReply->payerAuthEnrollReply->paReq && property_exists($cyberSourceReply->payerAuthEnrollReply, 'xid') && $cyberSourceReply->payerAuthEnrollReply->xid) {
                    $cartValue = unserialize($cart->getValue());
                    if (!$cartValue) {
                        $cartValue = array();
                    }
                    $cartValue = array_merge($cartValue, array('cyber_source_3d_response' => $cyberSourceReply));
                    $cart->setValue(serialize($cartValue));
                    $this->getEntityManager()->persist($cart);
                    $this->getEntityManager()->flush($cart);

                    $parameters = array(
                        'subscription' => $request->get('subscription'),
                        'cyberSourceReply' => $cyberSourceReply,
                        'trail' => $request->get('trail'),
                        'termUrl' => $this->generateUrl('cybersource_checkout', array('subscription' => $request->get('subscription'), 'trail' => $request->get('trail')), UrlGeneratorInterface::ABSOLUTE_URL),
                    );

                    return $this->render('FaPaymentBundle:CyberSourceCheckout:checkout3dSecure.html.twig', $parameters);
                } elseif ($cyberSourceReply) {
                    if ($cybersource3DSecureResponseFlag) {
                        $reasonCode = $cyberSourceReply->reasonCode;
                        if ($reasonCode == 100) {
                            $reasonCode = 476;
                        }
                        return $this->handleMessage($cyberSourceManager->getError($reasonCode), 'cybersource_checkout', array('subscription' => $request->get('subscription'), 'trail' => $request->get('trail')), 'error', $cybersource3DSecureResponseFlag);
                    } else {
                        $this->container->get('session')->getFlashBag()->add('error', $cyberSourceManager->getError($cyberSourceReply->reasonCode));
                    }
                }
            } else {
                $formErrors    = $formManager->getFormSimpleErrors($form, 'label');
                $errorMessages = '';
                foreach ($formErrors as $fieldName => $errorMessage) {
                    if ($errorMessages != '') {
                        $errorMessages = $errorMessages . ' | ' . $fieldName . ': ' . $errorMessage[0];
                    } else {
                        $errorMessages = $fieldName . ': ' . $errorMessage[0];
                    }
                }
                $gaStr = $gaStr . $errorMessages;
            }
        }

        $parameters = array(
            'form' => $form->createView(),
            'subscription' => $request->get('subscription'),
            'trial' => $request->get('trail'),
            'gaStr' => $gaStr,
        );

        return $this->render('FaPaymentBundle:CyberSourceCheckout:checkout.html.twig', $parameters);
    }

    private function sendSubscriptionBillingEmail($user, $cartDetails, $userPackage, $cart, $subscriptionId, $allow_zero_amount = false)
    {
        $values = unserialize($cartDetails[0]['value']);
        $package = $values['package'];
        $p = array_pop($package);

        if ((isset($p['package_for']) && $p['package_for'] == 'shop')) {
            $package = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $p['id']));

            if ($allow_zero_amount == false) {
                $this->getRepository('FaUserBundle:UserPackage')->sendUserPackageBillingEmail($user, $package, $userPackage, $cart->getCartCode(), $subscriptionId, 'subscription_billing_receipt', $this->container);
            }

            if ($userPackage && ($userPackage->getPackage()->getPrice() < $package->getPrice())) {
                if ($package->getPackageText() == 'enhanced') {
                    $this->getRepository('FaUserBundle:UserPackage')->sendUserPackageEmail($user, $package, 'upgraded_to_enhanced_profile_package_welcome', $this->container, $subscriptionId);
                } elseif ($package->getPackageText() == 'premium') {
                    $this->getRepository('FaUserBundle:UserPackage')->sendUserPackageEmail($user, $package, 'upgraded_to_premium_profile_package_welcome', $this->container, $subscriptionId);
                }
            }
        }
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
        $firstName = $loggedinUser->getFirstName() ? $loggedinUser->getFirstName() : $form->get('card_holder_name')->getData();
        $lastName  = $loggedinUser->getLastName() ? $loggedinUser->getLastName() : $form->get('card_holder_name')->getData();
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
     * Deletes a record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A response object.
     */
    public function deleteTokenAction(Request $request, $id)
    {
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $redirectRoute = 'cybersource_checkout';
        if ($request->get('from') == 'my_account') {
            $redirectRoute = 'my_account';
        } elseif ($request->get('subscription')) {
            $redirectRoute = 'cybersource_subscription_checkout';
        }

        $deleteManager = $this->get('fa.deletemanager');
        $entity        = $this->getRepository('FaPaymentBundle:PaymentTokenization')->find($id);

        $token = $this->getRepository('FaPaymentBundle:PaymentTokenization')->isValidUserToken($loggedinUser->getId(), $id);
        if (!$token) {
            return parent::handleMessage($this->get('translator')->trans('Invalid payment source.', array(), 'checkout_payment_success'), $redirectRoute, array(), 'error');
        }
        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find payment source.', array(), 'checkout_payment_success'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $redirectRoute);
        }

        try {
            $cyberSourceManager  = $this->get('fa.cyber.source.manager');
            $recurringSubscriptionInfo = array('subscriptionID' => $entity->getSubscriptionId());
            $cyberSourceReply = $cyberSourceManager->deleteToken($recurringSubscriptionInfo);

            if ($cyberSourceReply && $cyberSourceReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE) {
                $deleteManager->delete($entity);
            } else if ($cyberSourceReply) {
                return parent::handleMessage($cyberSourceManager->getError($cyberSourceReply->reasonCode), $redirectRoute, array(), 'error');
            }
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'checkout_payment_success'), $redirectRoute, array(), 'error');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', $redirectRoute);
        }

        if ($request->get('from') == 'my_account') {
            $this->container->get('session')->getFlashBag()->add('card_success', $this->get('translator')->trans('Payment source has been deleted successfully.', array(), 'frontend-new-card'));
            return $this->redirectToRoute('my_account');
        }

        return parent::handleMessage($this->get('translator')->trans('Payment source has been deleted successfully.', array(), 'checkout_payment_success'), $redirectRoute);
    }
}
