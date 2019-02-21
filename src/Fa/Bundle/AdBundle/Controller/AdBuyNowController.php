<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\PaymentBundle\Repository\PaymentPaypalRepository;
use Fa\Bundle\PaymentBundle\Repository\TransactionDetailRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Fa\Bundle\AdBundle\Form\AdBuyNowDeliveryAddressType;

/**
 * This controller is used for ad package management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdBuyNowController extends CoreController
{
    /**
     * Ad buy now for collection only.
     *
     * @param integer $adId
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function collectionBuyNowAdAction($adId, Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

        try {
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-buy-now'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage');
        }

        $adDetail     = $this->getAdDetailUrl($adId, true);
        $adDetailUrl  = $this->get('fa_ad.manager.ad_routing')->getDetailUrl($adDetail);
        $loggedinUser = $this->getLoggedInUser();
        // check ad is valid for buy now.
        $this->checkValidAdForBuyNow($loggedinUser, $ad, $adDetailUrl);

        if ($ad && $ad->getDeliveryMethodOption() && $ad->getDeliveryMethodOption()->getId() == DeliveryMethodOptionRepository::COLLECTION_ONLY_ID) {
            if ($request->getMethod() == 'POST') {
                return $this->buyNowAdRedirectToPaypal($adId, $request);
            } else {
                $parameters = array(
                    'adObj' => $ad,
                    'adDetail' => $adDetail,
                );
            }

            return $this->render('FaAdBundle:AdBuyNow:collectionBuyNowAd.html.twig', $parameters);
        } else {
            return $this->handleMessage($this->get('translator')->trans('Invalid delivery method option.', array(), 'frontend-buy-now'), 'fa_frontend_homepage', array(), 'error');
        }
    }

    /**
     * Ad buy now for posted or collection only.
     *
     * @param integer $adId
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function postedBuyNowAdAction($adId, Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

        try {
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-buy-now'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage');
        }

        $adDetail     = $this->getAdDetailUrl($adId, true);
        $adDetailUrl  = $this->get('fa_ad.manager.ad_routing')->getDetailUrl($adDetail);
        $loggedinUser = $this->getLoggedInUser();
        // check ad is valid for buy now.
        $this->checkValidAdForBuyNow($loggedinUser, $ad, $adDetailUrl);

        if ($ad && $ad->getDeliveryMethodOption() && in_array($ad->getDeliveryMethodOption()->getId(), array(DeliveryMethodOptionRepository::POSTED_ID, DeliveryMethodOptionRepository::POSTED_OR_COLLECT_ID))) {
            $formManager = $this->get('fa.formmanager');
            $form        = $formManager->createForm(AdBuyNowDeliveryAddressType::class, array('userId' => $loggedinUser->getId(), 'deliveryMethodId' => $ad->getDeliveryMethodOption()->getId()));
            if ('POST' === $request->getMethod()) {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $userAddress       = false;
                    $deliveryAddressId = $form->get('delivery_address')->getData();
                    if ($deliveryAddressId > 0) {
                        $userAddress = $this->getRepository('FaUserBundle:UserAddressBook')->isValidUserAddress($loggedinUser->getId(), $deliveryAddressId);
                    }
                    if ($deliveryAddressId > 0 && !$userAddress) {
                        $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Invalid address.', array(), 'frontend-buy-now'));
                    } else {
                        // update postage price.
                        $postagePrice = $ad->getPostagePrice();
                        if ($ad->getDeliveryMethodOption()->getId() == DeliveryMethodOptionRepository::POSTED_OR_COLLECT_ID && $deliveryAddressId == -1) {
                            $postagePrice = 0;
                        }

                        $deliveryAddress = $this->getDeliveryAddressArray($form);

                        return $this->buyNowAdRedirectToPaypal($adId, $request, $deliveryAddress, $postagePrice, $deliveryAddressId);
                    }
                }
            }
            $parameters = array(
                'adObj' => $ad,
                'adDetail' => $adDetail,
                'form' => $form->createView(),
            );

            return $this->render('FaAdBundle:AdBuyNow:postedBuyNowAd.html.twig', $parameters);
        } else {
            return $this->handleMessage($this->get('translator')->trans('Invalid delivery method option.', array(), 'frontend-buy-now'), 'fa_frontend_homepage', array(), 'error');
        }
    }

    /**
     * Ad buy now purchase redirect to paypal.
     *
     * @param integer $adId
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function buyNowAdAction($adId, Request $request)
    {
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

        try {
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-buy-now'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage');
        }

        $adDetailUrl = $this->getAdDetailUrl($adId);

        //remove all cookies.
        $this->getRepository('FaUserBundle:User')->removeUserCookies();
        //if not logged in then set redirect url.
        if (!$this->isAuth()) {
            $this->container->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Please login to buy item.', array(), 'frontend-buy-now'));
            $response = new RedirectResponse($this->container->get('router')->generate('login'));
            $response->headers->setCookie(new Cookie('frontend_redirect_after_login_path_info', $adDetailUrl, time() + 3600 * 24 * 7));
            $response->headers->setCookie(new Cookie('buy_now_flag', true, time() + 3600 * 24 * 7));
            return $response->send();
        }

        $loggedinUser = $this->getLoggedInUser();
        // check ad is valid for buy now.
        $this->checkValidAdForBuyNow($loggedinUser, $ad, $adDetailUrl);

        if ($ad && $ad->getDeliveryMethodOption()) {
            $deliveryMethodId = $ad->getDeliveryMethodOption()->getId();
            if ($deliveryMethodId == DeliveryMethodOptionRepository::COLLECTION_ONLY_ID) {
                return $this->redirectToRoute('collection_buy_now', array('adId' => $adId));
            } else {
                return $this->redirectToRoute('posted_buy_now', array('adId' => $adId));
            }
        } else {
            return $this->handleMessage($this->get('translator')->trans('Ad has not any delivery method selected.', array(), 'frontend-buy-now'), 'fa_frontend_homepage', array(), 'error');
        }
    }

    /**
     * Ad buy now purchase redirect to paypal.
     *
     * @param integer $adId
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function buyNowAdRedirectToPaypal($adId, Request $request, $deliveryAddress = array(), $postagePrice = 0, $deliveryAddressId = null)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

        try {
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-buy-now'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage');
        }

        $adDetailUrl = $this->getAdDetailUrl($adId);

        $loggedinUser = $this->getLoggedInUser();
        // check ad is valid for buy now.
        $this->checkValidAdForBuyNow($loggedinUser, $ad, $adDetailUrl);

        $paypalCommission = $this->getRepository('FaUserBundle:UserConfigRule')->getActivePaypalCommission($loggedinUser->getId(), $this->container);
        // get global paypal commission.
        if (!$paypalCommission) {
            $townId             = (isset($adDetail[0][AdSolrFieldMapping::MAIN_TOWN_ID]) ? $adDetail[0][AdSolrFieldMapping::MAIN_TOWN_ID] : null);
            $adLocationGroupIds = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getLocationGroupIdByTownDomicile(array($townId));
            $paypalCommission   = $this->getRepository('FaCoreBundle:ConfigRule')->getActiveHighestPaypalCommission($adLocationGroupIds, $this->container);
        }
        $this->getRepository('FaPaymentBundle:Cart')->addBuyNowAdToCart($loggedinUser, $ad, $paypalCommission, $this->container, $postagePrice);
        $cart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container, true);

        $paypalAdaptiveManager         = $this->get('fa.paypal.adaptive.manager');
        $baseUrl = $this->container->getParameter('base_url');
        
        $returnUrl                     = $baseUrl.$this->generateUrl('paypal_adaptive_process_payment', array('cartCode' => $cart->getCartCode()), true);
        $cacelUrl                      = $baseUrl.$adDetailUrl;
        $paypalAdaptivePaymentResponse = $paypalAdaptiveManager->getAdaptivePaymentResponse($returnUrl, $cacelUrl, $cart, $paypalCommission);

        //check for paypal token
        if (isset($paypalAdaptivePaymentResponse['payKey']) && $paypalAdaptivePaymentResponse['payKey']) {
            if ($deliveryAddressId > 0) {
                $userAddress = $this->getRepository('FaUserBundle:UserAddressBook')->getAddressByUser($loggedinUser->getId(), $deliveryAddressId);
                $deliveryAddress = $userAddress[$deliveryAddressId];
            } elseif ($deliveryAddressId == -1) {
                $deliveryAddress = array();
            }
            try {
                $paypalAdaptivePaymentResponse['ipAddress'] = $request->getClientIp();
                //update cart value and payment method
                $cartValue = unserialize($cart->getValue());
                if (!is_array($cartValue)) {
                    $cartValue = array();
                }
                // update delivery method.
                if ($ad && $ad->getDeliveryMethodOption()) {
                    $cart->setDeliveryMethodOption($ad->getDeliveryMethodOption());
                }
                $cartValue = array_merge($cartValue, array('paypal_adaptive_pay_response' => $paypalAdaptivePaymentResponse, 'delivery_address_info' => $deliveryAddress, 'postage_price' => $postagePrice, 'delivery_address_id' => $deliveryAddressId));
                $cart->setValue(serialize($cartValue));
                $cart->setPaymentMethod(PaymentRepository::PAYMENT_METHOD_PAYPAL_ADAPTIVE);
                $this->getEntityManager()->persist($cart);
                $this->getEntityManager()->flush($cart);

                return $this->redirect($paypalAdaptiveManager->getPaypalUrl($paypalAdaptivePaymentResponse['payKey']));
            } catch (\Exception $e) {
                CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-paypal'), 'my_purchases', array('cartCode' => $cart->getCartCode()), 'error');
            }
        } else {
            $this->container->get('session')->getFlashBag()->add('error', $paypalAdaptiveManager->getError($paypalAdaptivePaymentResponse));
        }

        return $this->redirect($adDetailUrl);
    }

    /**
     * Check ad is valid for buy now or not.
     *
     * @param object $loggedinUser Logged in user object.
     * @param object $ad           Ad object.
     * @param string $adDetailUrl  Ad detail url.
     *
     * @return RedirectResponse
     */
    private function checkValidAdForBuyNow($loggedinUser, $ad, $adDetailUrl)
    {
        if ($loggedinUser && $loggedinUser->getId() == $ad->getUser()->getId()) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You can not buy your own item.', array(), 'frontend-buy-now'));
            $response  = new RedirectResponse($adDetailUrl);
            $response->send();
        }

        if ($ad && $ad->getQty() <= 0) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Item you are trying to purchase is out of stock.', array(), 'frontend-buy-now'));
            $response  = new RedirectResponse($adDetailUrl);
            $response->send();
        }
    }

    /**
     * Get ad detail url.
     *
     * @param integer $adId          Ad id.
     * @param boolean $getSolrDetail Get solr detail array.
     *
     * @return mixed
     */
    private function getAdDetailUrl($adId, $getSolrDetail = false)
    {
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'));
        $data           = $this->get('fa.searchfilters.manager')->getFiltersData();
        $keywords       = null;
        $page           = 1;
        $recordsPerPage = 1;
        //set ad criteria to search
        $data['query_filters']['item']['id']        = $adId;
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
        $solrResponse = $solrSearchManager->getSolrResponse();
        // fetch result set from solr
        $adDetail = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);

        if ($getSolrDetail) {
            return $adDetail[0];
        } else {
            return $this->get('fa_ad.manager.ad_routing')->getDetailUrl($adDetail[0]);
        }
    }

    /**
     * Ad buy now purchase.
     *
     * @param integer $adId
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function ajaxBuyNowAdAction($adId, Request $request)
    {
        $error             = '';
        $paypalRedirectUrl = '';
        $redirectToUrl     = '';

        if ($request->isXmlHttpRequest()) {
            $ad    = $this->getRepository('FaAdBundle:Ad')->find($adId);

            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                $error = $this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-buy-now');
            }

            $adDetailUrl = $this->getAdDetailUrl($adId);

            //remove all cookies.
            $this->getRepository('FaUserBundle:User')->removeUserCookies();
            //if not logged in then set redirect url.
            if (!$this->isAuth()) {
                $this->container->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Please login to buy item.', array(), 'frontend-buy-now'));
                //set new cookies for add ad to fav.
                $response = new Response();
                $response->headers->setCookie(new Cookie('frontend_redirect_after_login_path_info', $adDetailUrl, time() + 3600 * 24 * 7));
                $response->headers->setCookie(new Cookie('buy_now_flag', true, time() + 3600 * 24 * 7));
                $response->sendHeaders();
                $redirectToUrl = $this->container->get('router')->generate('login');
            } else {
                $loggedinUser = $this->getLoggedInUser();
                if ($loggedinUser->getId() == $ad->getUser()->getId()) {
                    $error = $this->get('translator')->trans('You can not buy your own item.', array(), 'frontend-buy-now');
                }

                if ($ad && $ad->getQty() <= 0) {
                    $error = $this->get('translator')->trans('Item you are trying to purchase is out of stock.', array(), 'frontend-buy-now');
                }

                if (!$error) {
                    $paypalCommission = $this->getRepository('FaUserBundle:UserConfigRule')->getActivePaypalCommission($loggedinUser->getId(), $this->container);
                    // get global paypal commission.
                    if (!$paypalCommission) {
                        $townId             = (isset($adDetail[0][AdSolrFieldMapping::MAIN_TOWN_ID]) ? $adDetail[0][AdSolrFieldMapping::MAIN_TOWN_ID] : null);
                        $adLocationGroupIds = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getLocationGroupIdByTownDomicile(array($townId));
                        $paypalCommission   = $this->getRepository('FaCoreBundle:ConfigRule')->getActiveHighestPaypalCommission($adLocationGroupIds, $this->container);
                    }
                    $this->getRepository('FaPaymentBundle:Cart')->addBuyNowAdToCart($loggedinUser, $ad, $paypalCommission, $this->container);
                    $cart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container, true);

                    $paypalAdaptiveManager         = $this->get('fa.paypal.adaptive.manager');
                    $returnUrl                     = $this->generateUrl('paypal_adaptive_process_payment', array('cartCode' => $cart->getCartCode()), true);
                    $cacelUrl                      = $adDetailUrl;
                    $paypalAdaptivePaymentResponse = $paypalAdaptiveManager->getAdaptivePaymentResponse($returnUrl, $cacelUrl, $cart, $paypalCommission);

                    //check for paypal token
                    if (isset($paypalAdaptivePaymentResponse['payKey']) && $paypalAdaptivePaymentResponse['payKey']) {
                        try {
                            $paypalAdaptivePaymentResponse['ipAddress'] = $request->getClientIp();
                            //update cart value and payment method
                            $cartValue = unserialize($cart->getValue());
                            if (!is_array($cartValue)) {
                                $cartValue = array();
                            }
                            $cartValue = array_merge($cartValue, array('paypal_adaptive_pay_response' => $paypalAdaptivePaymentResponse));
                            $cart->setValue(serialize($cartValue));
                            $cart->setPaymentMethod(PaymentRepository::PAYMENT_METHOD_PAYPAL_ADAPTIVE);
                            $this->getEntityManager()->persist($cart);
                            $this->getEntityManager()->flush($cart);

                            $paypalRedirectUrl = $paypalAdaptiveManager->getMobilePaypalUrl($paypalAdaptivePaymentResponse['payKey']);
                        } catch (\Exception $e) {
                            CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                            $error = $this->get('translator')->trans('Problem in payment.', array(), 'frontend-paypal');
                        }
                    } else {
                        $error = $paypalAdaptiveManager->getError($paypalAdaptivePaymentResponse);
                    }
                }
            }

            return new JsonResponse(array('error' => $error, 'paypalRedirectUrl' => $paypalRedirectUrl, 'redirectToUrl' => $redirectToUrl));
        } else {
            return new Response();
        }
    }

    /**
     * Process ad buy now purchase.
     *
     * @param strring $cartCode Cart code.
     * @param Request $request  Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function processBuyNowAdAction($cartCode, Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $cartValue = array();
        $cart      = $this->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode, 'is_buy_now' => 1, 'status' => 1));

        if ($cart) {
            $cartValue = unserialize($cart->getValue());
        }

        if ($cart && isset($cartValue['paypal_adaptive_pay_response']) && isset($cartValue['paypal_adaptive_pay_response']['payKey']) && $cartValue['paypal_adaptive_pay_response']['payKey']) {
            $paypalAdaptiveManager         = $this->get('fa.paypal.adaptive.manager');
            $paypalAdaptivePaymentResponse = $paypalAdaptiveManager->getPaymentDetailResponse($cartValue['paypal_adaptive_pay_response']['payKey']);

            //complete paypal payment.
            if (isset($paypalAdaptivePaymentResponse['responseEnvelope_ack']) && $paypalAdaptivePaymentResponse['responseEnvelope_ack'] == PaymentPaypalRepository::SUCCESS_ACK && isset($paypalAdaptivePaymentResponse['status']) && $paypalAdaptivePaymentResponse['status'] === PaymentPaypalRepository::AP_COMPLETED && isset($paypalAdaptivePaymentResponse['paymentInfoList_paymentInfo(0)_receiver_amount']) && $paypalAdaptivePaymentResponse['paymentInfoList_paymentInfo(0)_receiver_amount'] == $cart->getAmount()) {
                $this->getEntityManager()->beginTransaction();
                try {
                    $cartValue = unserialize($cart->getValue());
                    if (!is_array($cartValue)) {
                        $cartValue = array();
                    }
                    $cartValue = $cartValue + array('paypal_adaptive_payment_detail_response' => $paypalAdaptivePaymentResponse);
                    $cart->setValue(serialize($cartValue));
                    $this->getEntityManager()->persist($cart);
                    $this->getEntityManager()->flush($cart);
                    $paymentId = $this->getRepository('FaPaymentBundle:Payment')->processBuyNowPaymentSuccess($cartCode, $this->container);
                    $this->getEntityManager()->getConnection()->commit();

                    //send email to buyer and seller
                    if ($cart && isset($cartValue['paypal']) && isset($cartValue['paypal']['ad_id']) && $cartValue['paypal']['ad_id']) {
                        $adObj = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $cartValue['paypal']['ad_id']));
                        if ($adObj) {
                            // update payment seller id and order status
                            $paymentObject = $this->getRepository('FaPaymentBundle:Payment')->findOneBy(array('id' => $paymentId));
                            if ($paymentObject) {
                                $paymentObject->setSellerUserId($adObj->getUser()->getId());
                                $paymentObject->setBuyNowStatusId(PaymentRepository::BN_NEW_ORDER_ID);
                                $this->getEntityManager()->persist($paymentObject);
                                $this->getEntityManager()->flush($paymentObject);
                            }

                            $this->getRepository('FaAdBundle:Ad')->sendBuyNowBuyerEmail($adObj, $cart->getUser(), $cart->getAmount(), $cartCode, $this->container);
                            $this->getRepository('FaAdBundle:Ad')->sendBuyNowSellerEmail($adObj, $cart->getUser(), $cartCode, $paymentId, $this->container);

                            $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('you_have_a_new_order', $adObj->getId(), $adObj->getUser()->getId(), '0d', false, null, array('ad_buy_now_cart_code' => $cartCode));

                            $isBuyerReviewd  = $this->getRepository('FaUserBundle:UserReview')->isAdReviewedByUser($adObj->getId(), $adObj->getUser()->getId(), $loggedinUser->getId());
                            $isSellerReviewd = $this->getRepository('FaUserBundle:UserReview')->isAdReviewedByUser($adObj->getId(), $loggedinUser->getId(), $adObj->getUser()->getId());

                            if (!$isBuyerReviewd) {
                                $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('leave_review_for_buyer_after_buy_now', $adObj->getId(), $loggedinUser->getId(), '0d', false, null, array('ad_buy_now_cart_code' => $cartCode));
                            }

                            if (!$isSellerReviewd) {
                                $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('leave_review_for_seller_after_buy_now', $adObj->getId(), $adObj->getUser()->getId(), '0d', false, null, array('ad_buy_now_cart_code' => $cartCode));
                            }
                        }
                    }

                    return $this->handleMessage($this->get('translator')->trans('Your payment received successfully.', array(), 'frontend-paypal'), 'my_purchases', array('cartCode' => $cartCode), 'success');
                } catch (\Exception $e) {
                    $this->getEntityManager()->getConnection()->rollback();
                    CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                    return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-paypal'), 'my_purchases', array('cartCode' => $cartCode), 'error');
                }
            } else {
                return $this->handleMessage('Invalid pay key.', 'my_purchases', array('cartCode' => $cartCode), 'error');
            }
        } else {
            $cart = $this->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode, 'is_buy_now' => 1, 'status' => 0));
            if ($cart) {
                return $this->redirectToRoute('my_purchases', array('cartCode' => $cartCode));
            } else {
                return $this->handleMessage('Invalid cart.', 'fa_frontend_homepage', array(), 'error');
            }
        }
    }

    /**
     * Get delivery address array.
     *
     * @param object  $loggedinUser   Logged in user object.
     * @param object  $form           Form object.
     * @param boolean $forAddressBook Flag for user address book.
     *
     * @return array
     */
    private function getDeliveryAddressArray($form)
    {
        $deliveryAddress    = array();
        $deliveryAddress['street_address']   = $form->get('street_address')->getData();
        $deliveryAddress['street_address_2'] = $form->get('street_address_2')->getData();
        $deliveryAddress['town_name'] = $form->get('town')->getData();
        if ($form->get('county')->getData()) {
            $deliveryAddress['domicile_name'] = $form->get('county')->getData();
        }
        $deliveryAddress['zip'] = $form->get('zip')->getData();

        return array_filter(array_map('trim', $deliveryAddress));
    }
}
