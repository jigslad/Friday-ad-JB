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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This controller is used for cart management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CartAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Show user cart.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function showCartAction(Request $request)
    {
        $cart = $this->isValidCart();
        if (!$cart) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('No active cart exist, please add one item to it.', array(), 'backend-cart'));
            return new RedirectResponse($this->container->get('router')->generate('fa_admin_homepage'));
        }
        $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        $isAdultAdvertPresent = 0;
        $isAdultAdvertPresent = $this->isAdultAdvertPresent($cartDetails);


        if ($this->container->get('session')->get('popup')) {
            $popup = true;
        } else {
            $popup = false;
        }

        $parameters = array(
            'cart' => $cart,
            'cartDetails' => $cartDetails,
            'popup' => $popup,
            'isAdultAdvertPresent' => $isAdultAdvertPresent,
        );

        return $this->render('FaPaymentBundle:CartAdmin:showCart.html.twig', $parameters);
    }

    /**
     * Remove cart item.
     *
     * @param integer $transactionId Transaction id.
     * @param Request $request       Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function removeCartItemAction($transactionId, Request $request)
    {
        $cart        = $this->isValidCart();
        $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        $cartItemIds = array();

        if (count($cartDetails)) {
            foreach ($cartDetails as $cartDetail) {
                $cartItemIds[] = $cartDetail['id'];
            }
        }
        if (in_array($transactionId, $cartItemIds)) {
            if ($this->getRepository('FaPaymentBundle:Transaction')->removeByTransactionId($transactionId, $cart)) {
                return $this->handleMessage($this->get('translator')->trans('Cart item deleted successfully.', array(), 'backend-cart-payment'), 'show_cart_admin', array(), 'success');
            } else {
                return $this->handleMessage($this->get('translator')->trans('Problem in deleting cart item.', array(), 'backend-cart-payment'), 'show_cart_admin', array(), 'error');
            }
        } else {
            return $this->handleMessage($this->get('translator')->trans('Item is not available in your cart.', array(), 'backend-cart-payment'), 'show_cart_admin', array(), 'error');
        }
    }

    /**
     * Process payment by selected method.
     *
     * @param string  $paymentMethod Payment method.
     * @param Request $request       Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function processPaymentAction($paymentMethod, Request $request)
    {
        $loggedinUser = $this->getLoggedInUser();
        $cart         = $this->isValidCart();
        $transactions = $this->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartId($cart->getId());
        if (!$transactions) {
            return $this->handleMessage($this->get('translator')->trans('You do not have any item in your cart.', array(), 'backend-cart-payment'), 'fa_admin_homepage', array(), 'error');
        }

        if ($request->get('offline_payment') && $request->get('offline_payment') == 1 && $paymentMethod != PaymentRepository::PAYMENT_METHOD_OFFLINE_PAYMENT) {
            $paymentMethod = PaymentRepository::PAYMENT_METHOD_OFFLINE_PAYMENT;
        }
        if (!$request->get('skip_payment_reason') && $cart->getAmount() > 0 && (in_array($paymentMethod, array(PaymentRepository::PAYMENT_METHOD_FREE, PaymentRepository::PAYMENT_METHOD_OFFLINE_PAYMENT)) && $this->get('fa.resource.authorization.manager')->isGranted('show_cart_skip_payment_button')) || ($cart->getAmount() > 0 && $this->container->getParameter('by_pass_payment_admin'))) {
            $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
            $skipPaymentReasonError = true;
            if ($this->container->get('session')->get('popup')) {
                $popup = true;
            } else {
                $popup = false;
            }
            $parameters = array(
                'cart' => $cart,
                'cartDetails' => $cartDetails,
                'skipPaymentReasonError' => $skipPaymentReasonError,
                'popup' => $popup
            );

            return $this->render('FaPaymentBundle:CartAdmin:showCart.html.twig', $parameters);
        }
        if ($cart->getAmount() <= 0 || (in_array($paymentMethod, array(PaymentRepository::PAYMENT_METHOD_FREE, PaymentRepository::PAYMENT_METHOD_OFFLINE_PAYMENT)) && $this->get('fa.resource.authorization.manager')->isGranted('show_cart_skip_payment_button')) || ($cart->getAmount() > 0 && $this->container->getParameter('by_pass_payment_admin'))) {
            $this->getEntityManager()->beginTransaction();

            try {
                $cart->setPaymentMethod($paymentMethod);
                $this->getEntityManager()->persist($cart);
                $this->getEntityManager()->flush($cart);
                $paymentId = $this->getRepository('FaPaymentBundle:Payment')->processPaymentSuccess($cart->getCartCode(), $loggedinUser, $this->container);

                // activate the ads by payment id
                $this->getRepository('FaAdBundle:Ad')->activateAdsByPaymentId($paymentId, $this->container);

                $this->getEntityManager()->getConnection()->commit();

                // Remove session for cart id
                $this->container->get('session')->remove('cart_id');

                if ($paymentId && $request->get('skip_payment_reason') && $cart->getAmount() > 0 && ($paymentMethod == PaymentRepository::PAYMENT_METHOD_FREE && $this->get('fa.resource.authorization.manager')->isGranted('show_cart_skip_payment_button')) || ($cart->getAmount() > 0 && $this->container->getParameter('by_pass_payment_admin'))) {
                    $patmentObj = $this->getRepository('FaPaymentBundle:Payment')->findOneBy(array('id' => $paymentId));
                    if ($patmentObj) {
                        $patmentObj->setSkipPaymentReason($request->get('skip_payment_reason'));
                        $this->getEntityManager()->persist($patmentObj);
                        $this->getEntityManager()->flush($patmentObj);
                    }
                }
                // send ads for moderation
                // $this->getRepository('FaAdBundle:AdModerate')->sendAdsForModeration($paymentId, $this->container);

                //send ad package emails.
                $this->getRepository('FaAdBundle:Ad')->sendAdPackageEmailByPaymentId($paymentId, $this->container);

                return $this->handleMessage($this->get('translator')->trans('Your advert has been published successfully.', array(), 'backend-cart-payment'), 'checkout_payment_success_admin', array('cartCode' => $cart->getCartCode()), 'success');
            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'backend-cart-payment'), 'checkout_payment_failure_admin', array('cartCode' => $cart->getCartCode()), 'error');
            }
        } else {
            //redirect user to selected payment method.
            $route = $this->getRepository('FaPaymentBundle:Payment')->getPaymentMethodRoute($paymentMethod);
            if($paymentMethod=='amazonpay' && $request->get('access_token')!='') {
                $this->container->get('session')->set('amazon_access_token', $request->get('access_token'));
            }
            if (!$route) {
                return $this->handleMessage($this->get('translator')->trans('Please select valid payment method.', array(), 'backend-cart-payment'), 'show_cart_admin', array(), 'error');
            } else {
                return $this->redirectToRoute($route.'_admin');
            }
        }
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
            return new RedirectResponse($this->container->get('router')->generate('fa_admin_homepage'));
        }

        return $cart;
    }

    /**
     * Apply package discount code.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function ajaxApplyPackageDiscountCodeAction(Request $request)
    {
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        $code = $request->get('code', null);
        $isAdultAdvertPresent = 0;
        if ($redirectResponse === true && $request->isXmlHttpRequest()) {
            $cart         = $this->isValidCart();
            $loggedinUser = $cart->getUser();
            $htmlContent = '';
            if (!$cart) {
                $error = $this->get('translator')->trans('Invalid cart.', array(), 'frontend-cart-payment');
            } else {
                $codeObj = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->findOneBy(array('code' => $code, 'status' => 1));
                $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $isAdultAdvertPresent = $this->isAdultAdvertPresent($cartDetails);
                list($error, $codeAppliedFlag) = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->processDiscountCode($codeObj, $cart, $cartDetails, $loggedinUser, $this->container);

            }

            if ($this->container->get('session')->get('popup')) {
                $popup = true;
            } else {
                $popup = false;
            }
   
            if (!$error && $codeAppliedFlag) {
                $this->getRepository('FaPaymentBundle:Cart')->clear();
                $this->getRepository('FaPaymentBundle:Transaction')->clear();
                $cart        = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
                $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $parameters = array(
                    'cart' => $cart,
                    'cartDetails' => $cartDetails,
                    'isAdultAdvertPresent' => $isAdultAdvertPresent,
                    'popup' => $popup,
                );
                $htmlContent = $this->renderView('FaPaymentBundle:CartAdmin:cart.html.twig', $parameters);
            }

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
        }

        return new Response();
    }

    /**
     * Remove package discount code.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function ajaxRemovePackageDiscountCodeAction(Request $request)
    {
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        $isAdultAdvertPresent = 0;
        if ($redirectResponse === true && $request->isXmlHttpRequest()) {
            $cart         = $this->isValidCart();
            $loggedinUser = $cart->getUser();
            $error = '';
            $htmlContent = '';
            if (!$cart) {
                $error = $this->get('translator')->trans('Invalid cart.', array(), 'frontend-cart-payment');
            } else {
                $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $isAdultAdvertPresent = $this->isAdultAdvertPresent($cartDetails);
                $cartValue = unserialize($cart->getValue());
                if (!isset($cartValue['discount_values'])) {
                    $error = $this->get('translator')->trans('No code applied to your cart.', array(), 'frontend-cart-payment');
                } else {
                    $this->getRepository('FaPaymentBundle:TransactionDetail')->removeCodeFromAllItems($cartDetails);
                }
            }

            if ($this->container->get('session')->get('popup')) {
                $popup = true;
            } else {
                $popup = false;
            }

            if (!$error) {
               
                $this->getRepository('FaPaymentBundle:Cart')->clear();
                $this->getRepository('FaPaymentBundle:Transaction')->clear();
                $cart        = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
                $this->getEntityManager()->refresh($cart);
                $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $parameters = array(
                    'cart' => $cart,
                    'cartDetails' => $cartDetails,
                    'isAdultAdvertPresent' => $isAdultAdvertPresent,
                    'popup' => $popup,
                );
                $htmlContent = $this->renderView('FaPaymentBundle:CartAdmin:cart.html.twig', $parameters);
            }

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
        }

        return new Response();
    }

    /**
     * Is adult advert present
     *
     * @param Array $cartDetails Array.
     *
     * @return Boolean.
     */
    public function isAdultAdvertPresent($cartDetails)
    {
        if ($cartDetails && is_array($cartDetails)) {
            foreach ($cartDetails as $key => $cartDetail) {
                $objCategory     = $this->getRepository('FaEntityBundle:Category')->find($cartDetail['category_id']);
                $objRootCategory = $this->getRepository('FaEntityBundle:Category')->getRootNodeByCategory($objCategory);

                if ($objRootCategory && $objRootCategory->getId() == CategoryRepository::ADULT_ID) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }
}
