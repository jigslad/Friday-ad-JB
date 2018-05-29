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
use Fa\Bundle\PaymentBundle\Manager\AmazonPayManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\PaymentBundle\Form\AmazonpayCheckoutType;

/**
 * This controller is used for Amazonpay payment.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2017 Friday Media Group Ltd
 * @version v1.0
 */
class AmazonpayCheckoutAdminController extends CoreController implements ResourceAuthorizationController
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

        $cart = $this->isValidCart();
        if ($cart instanceof RedirectResponse) {
            return $cart;
        }
        $adUserObj   = $cart->getUser();
        $cartUserId  = $adUserObj->getId();
        $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());

        //check for cart price and item
        if (!$cart->getAmount() || !count($cartDetails)) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('There is no item in your cart.', array(), 'backend-amazonpay'));
            return new RedirectResponse($this->container->get('router')->generate('fa_admin_homepage'));
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AmazonpayCheckoutType::class, array('cartUser' => $adUserObj));
        $gaStr       = '';
        $amazonPayManager  = $this->get('fa.amazonpay.manager');
        $amazonconfig = $amazonPayManager->getAmazonpayConfig();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $loggedinUser        = $this->getLoggedInUser();
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
                    //update cart value and payment method.
                    $this->getEntityManager()->beginTransaction();
                    try {
                        $cartValue = array_merge($cartValue, array('billing_info' => $billTo, 'user_address_info' => $userAddressBookInfo, 'amazonpay_response' => $amazonResponse, 'ip'=> $ipAddress,'amazon_token'=> $amazon_token));
                        $cart->setValue(serialize($cartValue));
                        $cart->setPaymentMethod(PaymentRepository::PAYMENT_METHOD_AMAZONPAY);
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
                        return $this->handleMessage($this->get('translator')->trans('Your advert has been published successfully.', array(), 'backend-amazonpay'), 'checkout_payment_success_admin', array('cartCode' => $cart->getCartCode()), 'success');
                    } catch (\Exception $e) {
                        $this->getEntityManager()->getConnection()->rollback();
                        CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                        return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'backend-amazonpay'), 'checkout_payment_failure_admin', array('cartCode' => $cart->getCartCode()), 'error');
                    }
                    return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'backend-amazonpay'), 'checkout_payment_success_admin', array('cartCode' => $cart->getCartCode()), 'success');
                	
                }
            }
            //echo '<pre>';print_r($amazonResponse);die;
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
            'amzconf' => $amazonconfig,
            'amzn_url' => $amazonpayUrl,
            'amz_access_token' => $this->container->get('session')->get('amazon_access_token'),

        );

        return $this->render('FaPaymentBundle:AmazonPayCheckoutAdmin:checkout.html.twig', $parameters);
    }

    public function ajaxCartDetailsAction(Request $request)
    {
        
        $orderReferenceId=$request->get('orderReferenceId');
        $userId = $request->get('userId');
		$accessToken  = $this->container->get('session')->get('amazon_access_token');
        $cart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container);
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
        echo $retcartdetails;die;
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
