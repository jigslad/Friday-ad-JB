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
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for checkout.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CheckoutController extends CoreController
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
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $cart         = $this->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode, 'status' => 0, 'user' => $loggedinUser->getId()));

        if (!$cart) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You do not have permission to access this resource.'));
            return new RedirectResponse($this->container->get('router')->generate('fa_frontend_homepage'));
        }

        // send advert receipt email.
        if (!$cart->getIsShopPackagePurchase()) {
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

        $transcations = $this->getRepository('FaPaymentBundle:Payment')->getTranscationDetailsForGA($cart->getCartCode(), $loggedinUser);
        $redirectUrl  = null;

        $expire = date('D, d M Y H:i:s', time() + (86400 * 180)); // 3 months from now
        header("Set-cookie: PHPSESSID=".$request->cookies->get('PHPSESSID')."; expires=".$expire."; path=/; HttpOnly; SameSite=None; Secure");


        if ($this->container->get('session')->has('upgrade_payment_success_redirect_url') || $this->container->get('session')->has('payment_success_redirect_url') || $this->container->get('session')->has('paalite_payment_success_redirect_url')) {
            if (!$this->container->get('session')->has('paalite_payment_success_redirect_url') && $this->container->get('session')->has('upgrade_payment_success_redirect_url') && $this->container->get('session')->get('upgrade_payment_success_redirect_url') != '') {
                $this->container->get('session')->set('payment_success_for_upgrade', $this->container->get('session')->get('upgrade_payment_success_redirect_url'));
                $redirectUrl = $this->container->get('session')->get('upgrade_payment_success_redirect_url');
            } elseif ($this->container->get('session')->has('paalite_payment_success_redirect_url') && $this->container->get('session')->get('paalite_payment_success_redirect_url') != '') {
                $redirectUrl = $this->container->get('session')->get('paalite_payment_success_redirect_url');
            } else {
                $redirectUrl = $this->container->get('session')->get('payment_success_redirect_url');
            }
          
            $this->container->get('session')->remove('payment_success_redirect_url');
            if ($redirectUrl && $this->container->get('session')->has('upgrade_payment_success_redirect_url')) {
                $this->container->get('session')->set('upgrade_payment_transaction_id', $cartCode);
                return $this->redirect($redirectUrl);
            } elseif ($redirectUrl && $this->container->get('session')->has('paalite_payment_success_redirect_url')) {
                return $this->redirect($redirectUrl);
            } elseif ($redirectUrl) {
                if (preg_match("/\?/", $redirectUrl)) {
                    $redirectUrl = $redirectUrl."&transaction_id=".$cartCode;
                } else {
                    $redirectUrl = $redirectUrl."?transaction_id=".$cartCode;
                }
                
                $successMsg      = null;
                $flashBag        = $this->get('session')->getFlashBag();
                $successMsgArray = $flashBag->get('success');
                if (isset($successMsgArray[0])) {
                    $successMsg = $successMsgArray[0].' ';
                }
                $this->removePaaSessions();
                $flashBag->set('success', $successMsg.$this->get('translator')->trans('Your transaction ID is %transaction_id%.', array('%transaction_id%' => $cartCode), 'frontend-payment-success'));
                return $this->redirect($redirectUrl);
            }
        }

        $parameters = array(
            'cart' => $cart,
            'subscription' => $request->get('subscription'),
            'getTranscationJs' => $this->getTranscationJs($transcations),
            'getItemJs' => $this->getItemJs($transcations),
            'ga_transaction' => $transcations,
        );

        return $this->render('FaPaymentBundle:Checkout:paymentSuccess.html.twig', $parameters);
    }

    /**
     * Get transcation js.
     *
     * @param string $trans
     *
     * @return string
     */
    protected function getTranscationJs($trans)
    {
        $transactionjs = '';
        if (isset($trans['ID'])) {
            $transactionjs .= <<<HTML
    ga('ecommerce:addTransaction', {
      'id': '{$trans['ID']}',
      'affiliation': '{$trans['Affiliation']}',
      'revenue': '{$trans['Revenue']}',
      'shipping': '{$trans['Shipping']}',
      'tax': '{$trans['Tax']}',
      'currency': '{$trans['Currency']}'
    });
HTML;
        }

        return $transactionjs;
    }

    /**
     * Get item js.
     *
     * @param string $trans
     *
     * @return string
     */
    protected function getItemJs($trans)
    {
        $itemjs = '';

        if (isset($trans['items'])) {
            foreach ($trans['items'] as $item) {
                $itemjs .=  <<<HTML
    ga('ecommerce:addItem',{
  'id': '{$trans['ID']}',
  'name': '{$item['Name']}',
  'sku': '{$item['SKU']}',
  'category': "{$item['Category']}",
  'price': '{$item['Price']}',
  'quantity': '{$item['Quantity']}'
});
HTML;
            }
        }
        return $itemjs;
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
        $loggedinUser = $this->getLoggedInUser();
        $cart         = $this->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $cartCode, 'status' => 1, 'user' => $loggedinUser->getId()));

        if (!$cart) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You do not have permission to access this resource.'));
            return new RedirectResponse($this->container->get('router')->generate('fa_frontend_homepage'));
        }

        $redirectUrl  = null;

        if ($this->container->get('session')->has('payment_success_redirect_url') || $this->container->get('session')->has('upgrade_payment_success_redirect_url')) {
            $redirectUrl = $this->container->get('session')->get('payment_success_redirect_url');
            $this->container->get('session')->remove('payment_success_redirect_url');
            if ($redirectUrl) {
                $successMsg      = null;
                $flashBag        = $this->get('session')->getFlashBag();
                $successMsgArray = $flashBag->get('success');
                if (isset($successMsgArray[0])) {
                    $successMsg = $successMsgArray[0].' ';
                }
                $this->removePaaSessions();
                $flashBag->set('success', $successMsg.$this->get('translator')->trans('Your transaction ID is %transaction_id%.', array('%transaction_id%' => $cartCode), 'frontend-payment-success'));
                return $this->redirect($redirectUrl);
            }
        }
        $parameters = array(
            'cart' => $cart,
        );

        return $this->render('FaPaymentBundle:Checkout:paymentFailure.html.twig', $parameters);
    }

    /**
     * Remove paa session in case of success or cancel payment.
     *
     */
    private function removePaaSessions()
    {
        $this->container->get('session')->remove('paa_first_step_data');
        $this->container->get('session')->remove('paa_second_step_data');
        $this->container->get('session')->remove('paa_third_step_data');
        $this->container->get('session')->remove('paa_fourth_step_data');
        $this->container->get('session')->remove('ad_id');
    }

    /**
     * Update GA status for payment id
     *
     * @param Request $request  Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function ajaxUpdateGaStatusAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) === true && $request->isXmlHttpRequest()) {
            $paymentId    = $request->get('id', 0);
            $loggedinUser = $this->getLoggedInUser();

            //update ga status for payment
            $paymentObj = $this->getRepository('FaPaymentBundle:Payment')->findOneBy(array('id' => $paymentId, 'status' => 1, 'ga_status' => 0, 'user' => $loggedinUser->getId()));

            if ($paymentObj) {
                $paymentObj->setGaStatus(1);
                $this->getEntityManager()->persist($paymentObj);
                $this->getEntityManager()->flush();
            }
        }

        return new Response();
    }
}
