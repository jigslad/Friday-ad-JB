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
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;

/**
 * This controller is used for checkout.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CheckoutAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Payment success action.
     *
     * @param string  $cartCode Cart code.
     * @param Request $request  Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function paymentSuccessAction($cartCode, Request $request)
    {
        $cart = $this->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode, 'status' => 0));

        if (!$cart) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You do not have permission to access this resource.'));
            return new RedirectResponse($this->container->get('router')->generate('fa_admin_homepage'));
        }

        // send advert receipt email.
        if (!$cart->getIsShopPackagePurchase() && $cart->getPaymentMethod() != PaymentRepository::PAYMENT_METHOD_FREE) {
            try {
                $this->getRepository('FaAdBundle:Ad')->sendAdvertReceiptEmail($cart, $this->container);
            } catch (\Exception $e) {
                CommonManager::sendErrorMail($this->container, 'Error in email: Advert receipt', $e->getMessage(), $e->getTraceAsString());
            }
        }

        try {
            //update ad created ad for paid ads
            $this->getRepository('FaAdBundle:Ad')->updateAdCreatedAtForCart($cart);
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->container, 'Problem in updating ad created at', $e->getMessage(), $e->getTraceAsString());
        }

        if ($this->container->get('session')->get('popup')) {
            $popup = true;
        } else {
            $popup = false;
        }

        $cartCode   = $cart->getCartCode();
        $cartAmount = $cart->getAmount();

        // Redirect ad list page, after payment sucess.
        if ($popup === false) {
            // Delete entry from cart for detached ad means user_id is null.
            if (!$cart->getUser()) {
                $this->getEntityManager()->remove($cart);
                $this->getEntityManager()->flush($cart);
            }

            $adAdminUrl = $this->container->get('router')->generate('ad_admin');
            $objPayment = $this->getRepository('FaPaymentBundle:Payment')->findOneBy(array('cart_code' => $cartCode));
            if ($objPayment) {
                $objPaymentTransactions = $this->getRepository('FaPaymentBundle:PaymentTransaction')->findBy(array('payment' => $objPayment));
                if ($objPaymentTransactions) {
                    $adIds = '';
                    foreach ($objPaymentTransactions as $objPaymentTransaction) {
                        $adIds .= ', '.$objPaymentTransaction->getAd()->getId();
                    }
                    $adIds = trim($adIds, ', ');
                    if (!empty($adIds)) {
                        $adAdminUrl .= "?&fa_ad_ad_search_admin[ad__id]=".$adIds;
                    }
                    return new RedirectResponse($adAdminUrl);
                }
            }

            return new RedirectResponse($adAdminUrl);
        }

        $parameters = array(
            'cartCode'   => $cartCode,
            'cartAmount' => $cartAmount,
            'popup'      => $popup,
        );

        return $this->render('FaPaymentBundle:CheckoutAdmin:paymentSuccess.html.twig', $parameters);
    }

    /**
     * Payment success action.
     *
     * @param string  $cartCode Cart code.
     * @param Request $request  Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function paymentFailureAction($cartCode, Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $cart = $this->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode, 'status' => 1));

        if (!$cart) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You do not have permission to access this resource.'));
            return new RedirectResponse($this->container->get('router')->generate('fa_admin_homepage'));
        }

        if ($this->container->get('session')->get('popup')) {
            $popup = true;
        } else {
            $popup = false;
        }

        $parameters = array(
            'cart' => $cart,
            'popup' => $popup,
        );

        return $this->render('FaPaymentBundle:CheckoutAdmin:paymentFailure.html.twig', $parameters);
    }
}
