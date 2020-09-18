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
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\PaymentBundle\Form\CyberSourceCheckoutType;

/**
 * This controller is used for cyber source payment.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CyberSourceCheckoutAdminController extends CoreController implements ResourceAuthorizationController
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
        $cart = $this->isValidCart();
        if ($cart instanceof RedirectResponse) {
            return $cart;
        }
        $adUserObj   = $cart->getUser();
        $cartUserId  = $adUserObj->getId();
        $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());

        //check for cart price and item
        if (!$cart->getAmount() || empty($cartDetails)) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('There is no item in your cart.', array(), 'backend-cyber-source'));
            return new RedirectResponse($this->container->get('router')->generate('fa_admin_homepage'));
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(CyberSourceCheckoutType::class, array('cartUser' => $adUserObj));

        $expire = date('D, d M Y H:i:s', time() + (86400 * 180)); // 3 months from now
        header("Set-cookie: PHPSESSID=".$request->cookies->get('PHPSESSID')."; expires=".$expire."; path=/; HttpOnly; SameSite=None; Secure");


        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $loggedinUser        = $this->getLoggedInUser();
                $cyberSourceManager  = $this->get('fa.cyber.source.manager');
                $cyberSourceManager->setMerchantReferenceCodeForAdmin();
                $billTo              = $this->getBillToArray($adUserObj, $form);
                $userAddressBookInfo = $this->getBillToArray($adUserObj, $form, true);
                $cardInfo            = $this->getCardInfoArray($form);
                $paymentMethod       = $form->get('payment_method')->getData();
                $saveToken           = false;

                if ($paymentMethod) {
                    $token = $this->getRepository('FaPaymentBundle:PaymentTokenization')->isValidUserToken($adUserObj->getId(), $paymentMethod);
                    if (!$token) {
                        $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Invalid token.', array(), 'frontend-cyber-source'));
                    } else {
                        $tokenValue = unserialize($token->getValue());
                        if (is_array($tokenValue) && !empty($tokenValue) && isset($tokenValue['billto'])) {
                            $billTo = $tokenValue['billto'];
                            $userAddressBookInfo = $billTo;
                        }
                        $recurringSubscriptionInfo = array('subscriptionID' => $token->getSubscriptionId());
                        $cyberSourceReply = $cyberSourceManager->getCyberSourceReplyForToken($adUserObj, $billTo, $cart, $cartDetails, $recurringSubscriptionInfo);
                    }
                } else {
                    $saveToken                 = $form->get('is_save_credit_card')->getData();
                    $recurringSubscriptionInfo = array('frequency' => 'on-demand');
                    $cyberSourceReply = $cyberSourceManager->getCyberSourceReply($adUserObj, $billTo, $cardInfo, $cart, $cartDetails, $saveToken, $recurringSubscriptionInfo);
                }
                //validate cyber source response
                if ($cyberSourceReply && $cyberSourceReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE) {
                    // save token if user want to save.
                    if ($saveToken && $cyberSourceReply->paySubscriptionCreateReply && $cyberSourceReply->paySubscriptionCreateReply->reasonCode == PaymentCyberSourceRepository::SUCCESS_REASON_CODE && $cyberSourceReply->paySubscriptionCreateReply->subscriptionID) {
                        $subscriptionId = $cyberSourceReply->paySubscriptionCreateReply->subscriptionID;
                        $cardHolderName = $form->get('card_holder_name')->getData();
                        $cardType       = $form->get('card_type')->getData();
                        if ($form->get('card_number')->getData()) {
                            $cardNumber     = substr($form->get('card_number')->getData(), -4);
                        }
                        $this->getRepository('FaPaymentBundle:PaymentTokenization')->addNewToken($adUserObj->getId(), $subscriptionId, $cardNumber, $cardHolderName, $cardType, PaymentRepository::PAYMENT_METHOD_CYBERSOURCE, $billTo);
                    }
                    //update cart value and payment method.
                    $this->getEntityManager()->beginTransaction();
                    try {
                        $cartValue = unserialize($cart->getValue());
                        if (!is_array($cartValue)) {
                            $cartValue = array();
                        }
                        $cartValue = array_merge($cartValue, array('billing_info' => $billTo, 'user_address_info' => $userAddressBookInfo, 'cyber_source_response' => $cyberSourceReply));
                        $cart->setValue(serialize($cartValue));
                        $cart->setPaymentMethod(PaymentRepository::PAYMENT_METHOD_CYBERSOURCE);
                        $this->getEntityManager()->persist($cart);
                        $this->getEntityManager()->flush($cart);
                        $paymentId = $this->getRepository('FaPaymentBundle:Payment')->processPaymentSuccess($cart->getCartCode(), $loggedinUser, $this->container);

                        // activate the ads by payment id
                        $this->getRepository('FaAdBundle:Ad')->activateAdsByPaymentId($paymentId, $this->container);

                        $this->getEntityManager()->getConnection()->commit();

                        // Remove session for cart id
                        $this->container->get('session')->remove('cart_id');

                        //send ads for moderation
                        //$this->getRepository('FaAdBundle:AdModerate')->sendAdsForModeration($paymentId, $this->container);

                        //send ad package emails.
                        $this->getRepository('FaAdBundle:Ad')->sendAdPackageEmailByPaymentId($paymentId, $this->container);

                        return $this->handleMessage($this->get('translator')->trans('Your advert has been published successfully.', array(), 'backend-cyber-source'), 'checkout_payment_success_admin', array('cartCode' => $cart->getCartCode()), 'success');
                    } catch (\Exception $e) {
                        $this->getEntityManager()->getConnection()->rollback();
                        CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                        return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'backend-cyber-source'), 'checkout_payment_failure_admin', array('cartCode' => $cart->getCartCode()), 'error');
                    }
                    return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'backend-cyber-source'), 'checkout_payment_success_admin', array('cartCode' => $cart->getCartCode()), 'success');
                } elseif ($cyberSourceReply) {
                    $this->container->get('session')->getFlashBag()->add('error', $cyberSourceManager->getError($cyberSourceReply->reasonCode));
                }
            }
        }

        if ($this->container->get('session')->get('popup')) {
            $popup = true;
        } else {
            $popup = false;
        }

        $parameters = array(
            'form' => $form->createView(),
            'popup' => $popup,
            'cartUserId' => $cartUserId,
        );

        return $this->render('FaPaymentBundle:CyberSourceCheckoutAdmin:checkout.html.twig', $parameters);
    }

    /**
     * Get billing array.
     *
     * @param object  $adUserObj      Ad user object.
     * @param object  $form           Form object.
     * @param boolean $forAddressBook Flag for user address book.
     *
     * @return array
     */
    private function getBillToArray($adUserObj, $form, $forAddressBook = false)
    {
        $billTo    = array();
        $firstName = $adUserObj->getFirstName() ? $adUserObj->getFirstName() : $form->get('card_holder_name')->getData();
        $lastName  = $adUserObj->getLastName() ? $adUserObj->getLastName() : $form->get('card_holder_name')->getData();
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
        $billTo['email']      = $adUserObj->getEmail();
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
        $cart = $this->isValidCart();
        if ($cart instanceof RedirectResponse) {
            return $cart;
        }
        $adUserObj = $cart->getUser();

        $deleteManager = $this->get('fa.deletemanager');
        $entity        = $this->getRepository('FaPaymentBundle:PaymentTokenization')->find($id);

        $token = $this->getRepository('FaPaymentBundle:PaymentTokenization')->isValidUserToken($adUserObj->getId(), $id);
        if (!$token) {
            return parent::handleMessage($this->get('translator')->trans('Invalid token.', array(), 'checkout_payment_success'), 'cybersource_checkout_admin', array(), 'error');
        }
        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find token.', array(), 'checkout_payment_success'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'cybersource_checkout_admin');
        }

        try {
            $deleteManager->delete($entity);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'checkout_payment_success'), 'cybersource_checkout_admin', array(), 'error');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', 'cybersource_checkout_admin');
        }

        return parent::handleMessage($this->get('translator')->trans('Token has been deleted successfully.', array(), 'checkout_payment_success'), 'cybersource_checkout_admin');
    }

    /**
     * Check is valid cart.
     *
     * @return mixed
     */
    private function isValidCart()
    {
        $cart = null;

        $cartId = $this->container->get('session')->get('cart_id');
        if ($cartId) {
            $cart = $this->getRepository('FaPaymentBundle:Cart')->find($cartId);
        }

        if (!$cart) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('No active cart exist, please add one item to it.', array(), 'backend-cart'));
            $response  = new RedirectResponse($this->container->get('router')->generate('fa_admin_homepage'));
            return $response->send();
        }

        return $cart;
    }
}
