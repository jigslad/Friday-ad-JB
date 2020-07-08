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
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This controller is used for cart management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CartController extends CoreController
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
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $cart         = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
        $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());

        $isAdultAdvertPresent = $this->isAdultAdvertPresent($cartDetails);

        $parameters = array(
            'cart'                 => $cart,
            'cartDetails'          => $cartDetails,
            'isAdultAdvertPresent' => $isAdultAdvertPresent,
        );


        return $this->render('FaPaymentBundle:Cart:showCart.html.twig', $parameters);
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
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $cart         = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);

        $transactions = $this->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartId($cart->getId());
        if (!$transactions) {
            return $this->handleMessage($this->get('translator')->trans('You do not have any item in your cart.', array(), 'frontend-cart-payment'), 'fa_frontend_homepage', array(), 'error');
        }

        $cartValue = unserialize($cart->getValue());
        if ($cart->getDiscountAmount() > 0 && isset($cartValue['discount_values']) && count($cartValue['discount_values']) && isset($cartValue['discount_values']['code'])) {
            $codeObj = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->findOneBy(array('code' => $cartValue['discount_values']['code'], 'status' => 1));
            if ($codeObj) {
                $latestDiscountCodeArray = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->getPackageDiscountValueArray($codeObj);
                $discountcodeDiff = array_diff_assoc($cartValue['discount_values'], $latestDiscountCodeArray);

                if (isset($discountcodeDiff['discount_given'])) {
                    unset($discountcodeDiff['discount_given']);
                }

                if (count($discountcodeDiff)) {
                    return $this->handleMessage($this->get('translator')->trans('There is change in code, please remove code and re-enter code.', array(), 'frontend-cart-payment'), 'show_cart', array(), 'error');
                }
            } else {
                $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $this->getRepository('FaPaymentBundle:TransactionDetail')->removeCodeFromAllItems($cartDetails);

                return $this->handleMessage($this->get('translator')->trans('Invalid code.', array(), 'frontend-cart-payment'), 'show_cart', array(), 'error');
            }
        }

        //redirect to payment method or process payment.
        if ($cart->getAmount() <= 0 || ($cart->getAmount() > 0 && $this->container->getParameter('by_pass_payment'))) {
            //update cart vlaue and payment method.
            $this->getEntityManager()->beginTransaction();

            try {
                $cart->setPaymentMethod($paymentMethod);
                $this->getEntityManager()->persist($cart);
                $this->getEntityManager()->flush($cart);
                $paymentId = $this->getRepository('FaPaymentBundle:Payment')->processPaymentSuccess($cart->getCartCode(), null, $this->container);
                $this->getEntityManager()->getConnection()->commit();


                //send ads for moderation
                $this->getRepository('FaAdBundle:AdModerate')->sendAdsForModeration($paymentId, $this->container);

                //redirect back to manage my ads active tab.
                $this->container->get('session')->set('payment_success_redirect_url', $this->generateUrl('manage_my_ads_active'));

                return $this->handleMessage($this->get('translator')->trans('Your free advert posted successfully.', array(), 'frontend-cart-payment'), 'checkout_payment_success', array('cartCode' => $cart->getCartCode()), 'success');

            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Problem in payment.', array(), 'frontend-cart-payment'), 'checkout_payment_failure', array('cartCode' => $cart->getCartCode()), 'error');
            }
        } else {
            //redirect user to selected payment method.
            $route = $this->getRepository('FaPaymentBundle:Payment')->getPaymentMethodRoute($paymentMethod);
            if ($paymentMethod=='amazonpay' && $request->get('access_token')!='') {
                $this->container->get('session')->set('amazon_access_token', $request->get('access_token'));
            }
            if (!$route) {
                return $this->handleMessage($this->get('translator')->trans('Please select valid payment method.', array(), 'frontend-cart-payment'), 'show_cart', array(), 'error');
            } else {
                return $this->redirectToRoute($route);
            }
        }
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
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $cart         = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
        $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        $cartItemIds  = array();

        if (count($cartDetails)) {
            foreach ($cartDetails as $cartDetail) {
                $cartItemIds[] = $cartDetail['id'];
            }
        }

        //check cart item & delete it.
        if (in_array($transactionId, $cartItemIds)) {
            if ($this->getRepository('FaPaymentBundle:Transaction')->removeByTransactionId($transactionId, $cart)) {
                return $this->handleMessage($this->get('translator')->trans('Cart item deleted successfully.', array(), 'frontend-cart-payment'), 'show_cart', array(), 'success');
            } else {
                return $this->handleMessage($this->get('translator')->trans('Problem in deleting cart item.', array(), 'frontend-cart-payment'), 'show_cart', array(), 'error');
            }
        } else {
            return $this->handleMessage($this->get('translator')->trans('Item is not available in your cart.', array(), 'frontend-cart-payment'), 'show_cart', array(), 'error');
        }
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
        if ($redirectResponse === true && $request->isXmlHttpRequest()) {
            $loggedinUser = $this->getLoggedInUser();
            $cart         = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
            $htmlContent = '';
            if (!$cart) {
                $error = $this->get('translator')->trans('Invalid cart.', array(), 'frontend-cart-payment');
            } else {
                $codeObj = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->findOneBy(array('code' => $code, 'status' => 1));
                $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());

                list($error, $codeAppliedFlag) = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->processDiscountCode($codeObj, $cart, $cartDetails, $loggedinUser, $this->container);
            }

            if (!$error && $codeAppliedFlag) {
                $isAdultAdvertPresent = $this->isAdultAdvertPresent($cartDetails);
                $this->getRepository('FaPaymentBundle:Cart')->clear();
                $this->getRepository('FaPaymentBundle:Transaction')->clear();
                $cart        = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
                $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $parameters = array(
                    'cart' => $cart,
                    'cartDetails' => $cartDetails,
                    'isAdultAdvertPresent' => $isAdultAdvertPresent,
                );
                $htmlContent = $this->renderView('FaPaymentBundle:Cart:cart.html.twig', $parameters);
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
        if ($redirectResponse === true && $request->isXmlHttpRequest()) {
            $loggedinUser = $this->getLoggedInUser();
            $cart         = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
            $error = '';
            $htmlContent = '';
            if (!$cart) {
                $error = $this->get('translator')->trans('Invalid cart.', array(), 'frontend-cart-payment');
            } else {
                $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $cartValue = unserialize($cart->getValue());
                if (!isset($cartValue['discount_values'])) {
                    $error = $this->get('translator')->trans('No code applied to your cart.', array(), 'frontend-cart-payment');
                } else {
                    $this->getRepository('FaPaymentBundle:TransactionDetail')->removeCodeFromAllItems($cartDetails);
                }
            }

            if (!$error) {
                $isAdultAdvertPresent = $this->isAdultAdvertPresent($cartDetails);
                $this->getRepository('FaPaymentBundle:Cart')->clear();
                $this->getRepository('FaPaymentBundle:Transaction')->clear();
                $cart        = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
                $this->getEntityManager()->refresh($cart);
                $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $parameters = array(
                    'cart' => $cart,
                    'cartDetails' => $cartDetails,
                    'isAdultAdvertPresent' => $isAdultAdvertPresent,
                );
                $htmlContent = $this->renderView('FaPaymentBundle:Cart:cart.html.twig', $parameters);
            }

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
        }

        return new Response();
    }

    /**
     * Remove package credit.
     *
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function ajaxRemovePackageCreditAction(Request $request)
    {
        //check user is logged in or not.
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse === true && $request->isXmlHttpRequest()) {
            $loggedinUser = $this->getLoggedInUser();
            $cart         = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
            $error = '';
            $htmlContent = '';
            $codeObj = '';
            if (!$cart) {
                $error = $this->get('translator')->trans('Invalid cart.', array(), 'frontend-cart-payment');
            } else {
                $cartValue = unserialize($cart->getValue());
                $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $this->getRepository('FaPaymentBundle:TransactionDetail')->removeCreditFromAllItems($cartDetails);
                if (isset($cartValue['discount_values']) && isset($cartValue['discount_values']['code'])) {
                    $codeObj = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->findOneBy(array('code' => $cartValue['discount_values']['code']));
                }
            }

            if (!$error) {
                $this->getRepository('FaPaymentBundle:Cart')->clear();
                $this->getRepository('FaPaymentBundle:Transaction')->clear();
                $this->getRepository('FaPaymentBundle:TransactionDetail')->clear();
                $cart        = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
                $this->getEntityManager()->refresh($cart);
                $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                if ($codeObj) {
                    list($error, $codeAppliedFlag) = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->processDiscountCode($codeObj, $cart, $cartDetails, $loggedinUser, $this->container, false);
                }
                $this->getRepository('FaPaymentBundle:Cart')->clear();
                $this->getRepository('FaPaymentBundle:Transaction')->clear();
                $this->getRepository('FaPaymentBundle:TransactionDetail')->clear();
                $cart        = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
                $this->getEntityManager()->refresh($cart);
                $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                $isAdultAdvertPresent = $this->isAdultAdvertPresent($cartDetails);
                $parameters = array(
                    'cart' => $cart,
                    'cartDetails' => $cartDetails,
                    'isAdultAdvertPresent' => $isAdultAdvertPresent
                );
                $htmlContent = $this->renderView('FaPaymentBundle:Cart:cart.html.twig', $parameters);
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
                    return true;
                }
            }
        }

        return false;
    }
}
