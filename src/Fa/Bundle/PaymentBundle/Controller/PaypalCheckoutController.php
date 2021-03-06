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
use Fa\Bundle\PaymentBundle\Repository\PaymentPaypalRepository;

/**
 * This controller is used for paypal payment.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PaypalCheckoutController extends CoreController
{
    /**
     * Checkout action for paypal.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */

    public function checkoutAction(Request $request)
    {
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $loggedinUser = $this->getLoggedInUser();
        $cart         = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
        $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());

        //check for cart price and item
        if (!$cart->getAmount() || !count($cartDetails)) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('There is no item in your cart.', array(), 'frontend-paypal'));
            return new RedirectResponse($this->container->get('router')->generate('show_cart'));
        }

        $paypalManager                = $this->get('fa.paypal.manager');
        $baseUrl = $this->container->getParameter('base_url');
        
        $returnUrl                    = $baseUrl.$this->generateUrl('paypal_process_payment', array(), true);
        $cacelUrl                     = $baseUrl.$this->generateUrl('show_cart', array(), true);
        $paypalSetExpCheckoutResponse = $paypalManager->getSetExpressCheckoutResponse($returnUrl, $cacelUrl, $cart, $cartDetails);

        //check for paypal token
        if (isset($paypalSetExpCheckoutResponse['TOKEN']) && $paypalSetExpCheckoutResponse['TOKEN']) {
            try {
                //update cart value and payment method
                $cartValue = unserialize($cart->getValue());
                if (!is_array($cartValue)) {
                    $cartValue = array();
                }
                $cartValue = array_merge($cartValue, array('paypal_set_response' => $paypalSetExpCheckoutResponse));
                $cart->setValue(serialize($cartValue));
                $cart->setPaymentMethod(PaymentRepository::PAYMENT_METHOD_PAYPAL);
                $this->getEntityManager()->persist($cart);
                $this->getEntityManager()->flush($cart);

                return $this->redirect($paypalManager->getPaypalUrl($paypalSetExpCheckoutResponse['TOKEN']));
            } catch (\Exception $e) {
                CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-paypal'), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error');
            }
            return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-paypal'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success');
        } else {
            $this->container->get('session')->getFlashBag()->add('error', $paypalManager->getError($paypalSetExpCheckoutResponse));
        }
        return $this->redirectToRoute('show_cart');
    }

    /**
     * Process payment action for paypal.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */

    public function processPaymentAction(Request $request)
    {
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $loggedinUser = $this->getLoggedInUser();
        $cart         = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
        $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        $paypalToken  = $request->get('token');
        $payerId      = $request->get('PayerID');

        //check for cart price and item
        if (!$cart->getAmount() || !count($cartDetails)) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('There is no item in your cart.', array(), 'frontend-paypal'));
            return new RedirectResponse($this->container->get('router')->generate('show_cart'));
        }

        // calculate vat.
        $totalVat = 0;
        foreach ($cartDetails as $itemNo => $cartDetail) {
            $totalVat = $totalVat + $cartDetail['vat_amount'];
        }
        $totalVat = round($totalVat, 2);

        //check paypal detail against token and payer id.
        $paypalManager                  = $this->get('fa.paypal.manager');
        $paypalGetExpressCheckoutDetail = $paypalManager->getExpressCheckoutDetailsResponse($paypalToken);

        if (($paypalGetExpressCheckoutDetail['ACK'] && $paypalGetExpressCheckoutDetail['ACK'] != PaymentPaypalRepository::SUCCESS_ACK) || (isset($paypalGetExpressCheckoutDetail['PAYMENTREQUEST_0_ITEMAMT']) && $paypalGetExpressCheckoutDetail['PAYMENTREQUEST_0_ITEMAMT'] != $cart->getAmount() && isset($paypalGetExpressCheckoutDetail['PAYMENTREQUEST_0_TAXAMT']) && $paypalGetExpressCheckoutDetail['PAYMENTREQUEST_0_TAXAMT'] != $totalVat)) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('In-valid paypal token.', array(), 'frontend-paypal'));
            return new RedirectResponse($this->container->get('router')->generate('show_cart'));
        }

        $paypalDoExpCheckoutResponse = $paypalManager->getDoExpressCheckoutPaymentResponse($paypalToken, $payerId, $cart, $cartDetails);

        //complete paypal payment.
        if (isset($paypalDoExpCheckoutResponse['TOKEN']) && $paypalDoExpCheckoutResponse['TOKEN'] && isset($paypalDoExpCheckoutResponse['ACK']) && $paypalDoExpCheckoutResponse['ACK'] === PaymentPaypalRepository::SUCCESS_ACK) {
            $paypalDoExpCheckoutResponse['ipAddress'] = $request->getClientIp();
            $this->getEntityManager()->beginTransaction();
            try {
                $cartValue = unserialize($cart->getValue());
                if (!is_array($cartValue)) {
                    $cartValue = array();
                }
                if (isset($paypalGetExpressCheckoutDetail['PAYERID'])) {
                    $paypalDoExpCheckoutResponse['PAYERID'] = $paypalGetExpressCheckoutDetail['PAYERID'];
                }
                $cartValue = $cartValue + array('paypal_do_response' => $paypalDoExpCheckoutResponse);
                $cart->setValue(serialize($cartValue));
                $this->getEntityManager()->persist($cart);
                $this->getEntityManager()->flush($cart);
                $paymentId = $this->getRepository('FaPaymentBundle:Payment')->processPaymentSuccess($cart->getCartCode(), null, $this->container);
                $this->getEntityManager()->getConnection()->commit();
                if($paymentId) {
                //send ads for moderation
sleep(5);
                $this->getRepository('FaAdBundle:AdModerate')->sendAdsForModeration($paymentId, $this->container);

                return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-paypal'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success');
               } else {
                   return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-paypal'), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error');
               }
            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-paypal'), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error');
            }
            return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-paypal'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success');
        } else {
            return $this->handleMessage($paypalManager->getError($paypalDoExpCheckoutResponse), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error');
        }
    }
}
