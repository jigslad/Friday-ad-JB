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
use Symfony\Component\Form\FormError;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Manager\CyberSourceManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Fa\Bundle\PaymentBundle\Repository\PaymentAmazonpayRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentCyberSourceRepository;
use Fa\Bundle\PaymentBundle\Form\AmazonpayCheckoutType;
/**
 * This controller is used for Amazonpay payment.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2017 Friday Media Group Ltd
 * @version v1.0
 */
class AmazonpayCheckoutController extends CoreController
{
    /**
     * Checkout action for Amazonpay.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */

    public function checkoutAction(Request $request)
    {
        $amazonpayMode               = $this->container->getParameter('fa.amazon.mode');
        $amazonpayUrl                = $this->container->getParameter('fa.amazon.'.$amazonpayMode.'.url');

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
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('There is no item in your cart.', array(), 'frontend-amazonpay'));
            return new RedirectResponse($this->container->get('router')->generate('show_cart'));
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AmazonpayCheckoutType::class, array('subscription' => $request->get('subscription')));
        $gaStr       = '';
        $amazonPayManager  = $this->get('fa.amazonpay.manager');
        $amazonconfig = $amazonPayManager->getAmazonpayConfig();

        if ('POST' === $request->getMethod()) {
            $amazonJsonResponse = $amazonPayManager->getAmazonOrderProcess($cart,$this->container);
            $amazonResponse = json_decode($amazonJsonResponse);
            $amazon_token = $request->request->get('fa_payment_amazonpay_checkout')['_token'];
           
            if ($amazonResponse->confirm->ResponseStatus==200 && $amazonResponse->authorize->ResponseStatus==200  && $amazonResponse->authorize->ResponseStatus==200) {
                $cartValue = unserialize($cart->getValue());
                if (!is_array($cartValue)) {
                    $cartValue = array();
                }
                $billTo              = $amazonResponse->authorize->AuthorizeResult->AuthorizationDetails->AuthorizationBillingAddress;
                $userAddressBookInfo = $amazonResponse->authorize->AuthorizeResult->AuthorizationDetails->AuthorizationBillingAddress;
                $ipAddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();

                $this->getEntityManager()->beginTransaction();
                try {
                    $cartValue = array_merge($cartValue, array('billing_info' => $billTo, 'user_address_info' => $userAddressBookInfo, 'amazonpay_response' => $amazonResponse, 'ip'=> $ipAddress,'amazon_token'=> $amazon_token));
                    $cart->setValue(serialize($cartValue));
                    $cart->setPaymentMethod(PaymentRepository::PAYMENT_METHOD_AMAZONPAY);
                    $this->getEntityManager()->persist($cart);
                    $this->getEntityManager()->flush($cart);

                    $paymentId = $this->getRepository('FaPaymentBundle:Payment')->processPaymentSuccess($cart->getCartCode(), null, $this->container);
                    $this->getEntityManager()->getConnection()->commit();

                   try {
                        //send ads for moderation
                        $this->getRepository('FaAdBundle:AdModerate')->sendAdsForModeration($paymentId, $this->container);
                        return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-amazonpay'), 'amazonpay_payment_success', array('cartCode' => $cart->getCartCode()), 'success');
                        
                    } catch (\Exception $e) {
                        CommonManager::sendErrorMail($this->container, 'Error: Problem in sending user subscription email', $e->getMessage(), $e->getTraceAsString());
                        return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-amazonpay'), 'amazonpay_payment_success', array('cartCode' => $cart->getCartCode()), 'success');
                    }
                } catch (\Exception $e) {
                    CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                    $this->getEntityManager()->getConnection()->rollback();
                    return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-amazonpay'), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error');
                }
                return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-amazonpay'), 'amazonpay_payment_success', array('cartCode' => $cart->getCartCode()), 'success');
            	
            }
            
        }

        $parameters = array(
            'form' => $form->createView(),
            'subscription' => $request->get('subscription'),
            'trial' => $request->get('trail'),
            'gaStr' => $gaStr,
            'amzconf' => $amazonconfig,
            'amzn_url' => $amazonpayUrl,
            'amz_access_token' => $this->container->get('session')->get('amazon_access_token'),
        );

        return $this->render('FaPaymentBundle:AmazonPayCheckout:checkout.html.twig', $parameters);
    }

    public function ajaxCartDetailsAction(Request $request)
    {
        $loggedinUser = $this->getLoggedInUser();$requestParameters = array();
        $orderReferenceId=$request->get('orderReferenceId');
		$accessToken  = $this->container->get('session')->get('amazon_access_token');
        $cart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
        $cartDetails       = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        $amazonMode = $this->container->getParameter('fa.amazon.mode');

        // calculate vat.
        $totalVat = 0;$totalAmt=0;$payableAmt=0;$sellerNote = '';
        foreach ($cartDetails as $itemNo => $cartDetail) {
            //$totalVat = $totalVat + $cartDetail['vat_amount'];
            $totalAmt = $totalAmt + $cartDetail['amount'];
            $cartDetValue = ($cartDetail['value']!='')?unserialize($cartDetail['value']):array();
            if(!empty($cartDetValue)) {
            	foreach($cartDetValue['package'] as $key=>$val){
            		$packageInfo = $this->getRepository('FaPromotionBundle:Package')->findOneById($key);
            		$sellerNote = $sellerNote.$packageInfo->getTitle().' for the advert '.$cartDetail['title'].', ';
            	}
            }
        }

        //$totalVat = round($totalVat, 2);
        $totalAmt = round($totalAmt, 2);
        $payableAmt = $totalAmt + $totalVat;
        $sellerNote = rtrim($sellerNote,', ').' are the packages you have purchased';

        $requestParameters['amount']            = $payableAmt;
        $requestParameters['seller_note']       = $sellerNote;
        $requestParameters['seller_order_id']   = $cart->getCartCode();
        $requestParameters['store_name']        = $this->container->getParameter('fa.amazon.'.$amazonMode.'.store_name');
        $requestParameters['custom_information']= '';
        $requestParameters['mws_auth_token']    = null; // only non-null if calling API on behalf of someone else
        $requestParameters['amazon_order_reference_id'] = $orderReferenceId;
        $retcartdetails = $this->get('fa.amazonpay.manager')->getAmazonCartDetails($requestParameters,$accessToken);
        $this->container->get('session')->set('amazon_order_reference_id',$orderReferenceId);
    }

    /**
     * Checkout action for Amazonpay.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */

    public function transactionResponseAction(Request $request)
    {
        $AmazonpayManager  = $this->get('fa.Amazonpay.manager');
        $transactionId  = $request->get('tid');
        $transactionDetailResponse = $AmazonpayManager->getTransactionDetailsResponse($transactionId);
    }
    /**
     * Process payment action for Amazonpay.
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
        $AmazonpayToken  = $request->get('token');
        $payerId      = $request->get('PayerID');

        //check for cart price and item
        if (!$cart->getAmount() || !count($cartDetails)) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('There is no item in your cart.', array(), 'frontend-Amazonpay'));
            return new RedirectResponse($this->container->get('router')->generate('show_cart'));
        }

        // calculate vat.
        $totalVat = 0;
        foreach ($cartDetails as $itemNo => $cartDetail) {
            $totalVat = $totalVat + $cartDetail['vat_amount'];
        }
        $totalVat = round($totalVat, 2);

        //check Amazonpay detail against token and payer id.
        $AmazonpayManager                  = $this->get('fa.Amazonpay.manager');
        $AmazonpayGetExpressCheckoutDetail = $AmazonpayManager->getExpressCheckoutDetailsResponse($AmazonpayToken);

        if (($AmazonpayGetExpressCheckoutDetail['ACK'] && $AmazonpayGetExpressCheckoutDetail['ACK'] != PaymentAmazonpayRepository::SUCCESS_ACK) || (isset($AmazonpayGetExpressCheckoutDetail['PAYMENTREQUEST_0_ITEMAMT']) && $AmazonpayGetExpressCheckoutDetail['PAYMENTREQUEST_0_ITEMAMT'] != $cart->getAmount() && isset($AmazonpayGetExpressCheckoutDetail['PAYMENTREQUEST_0_TAXAMT']) && $AmazonpayGetExpressCheckoutDetail['PAYMENTREQUEST_0_TAXAMT'] != $totalVat)) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('In-valid Amazonpay token.', array(), 'frontend-Amazonpay'));
            return new RedirectResponse($this->container->get('router')->generate('show_cart'));
        }

        $AmazonpayDoExpCheckoutResponse = $AmazonpayManager->getDoExpressCheckoutPaymentResponse($AmazonpayToken, $payerId, $cart, $cartDetails);

        //complete Amazonpay payment.
        if (isset($AmazonpayDoExpCheckoutResponse['TOKEN']) && $AmazonpayDoExpCheckoutResponse['TOKEN'] && isset($AmazonpayDoExpCheckoutResponse['ACK']) && $AmazonpayDoExpCheckoutResponse['ACK'] === PaymentAmazonpayRepository::SUCCESS_ACK) {
            $AmazonpayDoExpCheckoutResponse['ipAddress'] = $request->getClientIp();
            $this->getEntityManager()->beginTransaction();
            try {
                $cartValue = unserialize($cart->getValue());
                if (!is_array($cartValue)) {
                    $cartValue = array();
                }
                if (isset($AmazonpayGetExpressCheckoutDetail['PAYERID'])) {
                    $AmazonpayDoExpCheckoutResponse['PAYERID'] = $AmazonpayGetExpressCheckoutDetail['PAYERID'];
                }
                $cartValue = $cartValue + array('Amazonpay_do_response' => $AmazonpayDoExpCheckoutResponse);
                $cart->setValue(serialize($cartValue));
                $this->getEntityManager()->persist($cart);
                $this->getEntityManager()->flush($cart);
                $paymentId = $this->getRepository('FaPaymentBundle:Payment')->processPaymentSuccess($cart->getCartCode(), null, $this->container);
                $this->getEntityManager()->getConnection()->commit();

                //send ads for moderation
                $this->getRepository('FaAdBundle:AdModerate')->sendAdsForModeration($paymentId, $this->container);

                return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-Amazonpay'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success');
            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-Amazonpay'), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error');
            }
            return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-Amazonpay'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success');
        } else {
            return $this->handleMessage($AmazonpayManager->getError($AmazonpayDoExpCheckoutResponse), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error');
        }
    }
}
