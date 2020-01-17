<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This manager is used to call paypal payment.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PaypalManager
{
    const PAYPAL_VERSION = '119';

    const PAYPAL_TRANSACTION = 'SALE';

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Paypal user id.
     *
     * @var string
     */
    private $userid;

    /**
     * Paypal password.
     *
     * @var string
     */
    private $password;

    /**
     * Paypal post url.
     *
     * @var string
     */
    private $url;

    /**
     * Paypal signature.
     *
     * @var string
     */
    private $signature;

    /**
     * Paypal redirect url.
     *
     * @var string
     */
    private $paypal_redirect_url;

    /**
     * Paypal redirect url.
     *
     * @var string
     */
    private $paypal_invoice_prefix;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container           = $container;
        $paypalMode                = $this->container->getParameter('fa.paypal.mode');
        $paypalParams              = $this->container->getParameter('fa.paypal.'.$paypalMode);
        $this->userid              = $paypalParams['userid'];
        $this->password            = $paypalParams['password'];
        $this->url                 = $paypalParams['express_checkout_url'];
        $this->signature           = $paypalParams['signature'];
        $this->paypal_redirect_url = $paypalParams['paypal_redirect_url'];
        $this->paypal_invoice_prefix = $paypalParams['express_checkout_invoice_prefix'];
        $isAdminLoggedIn             = CommonManager::isAdminLoggedIn($this->container);
        if ($isAdminLoggedIn) {
            $this->paypal_invoice_prefix = $paypalParams['express_checkout_invoice_prefix_admin'];
        }
    }

    /**
     * Get paypal response.
     *
     * @param array $paypalParams Paypal param array.
     *
     * @return array
     */
    public function getPaypalResponse($paypalParams)
    {
        // Build the HTTP Request Headers
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paypalParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($this->container->getParameter('fa.paypal.mode') == 'test') {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        parse_str($response, $responseArray);

        return $responseArray;
    }

    /**
     * Get paypal set express checkout response.
     *
     * @param string $returnUrl   Redirect url.
     * @param string $cacelUrl    Cancel url.
     * @param object $cartObj     Cart object.
     * @param array  $cartDetails Cart detail array.
     *
     * @return array
     */
    public function getSetExpressCheckoutResponse($returnUrl, $cacelUrl, $cartObj, array $cartDetails)
    {
        $totalVat = 0;
        //set parameters for SetExpressCheckout.
        $paypalExpressCheckoutFields                                   = $this->getPaypalAccountFields();
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_PAYMENTACTION'] = self::PAYPAL_TRANSACTION;
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_CURRENCYCODE']  = $cartObj->getCurrency();
        $paypalExpressCheckoutFields['cancelUrl']                      = $cacelUrl;
        $paypalExpressCheckoutFields['returnUrl']                      = $returnUrl;
        $paypalExpressCheckoutFields['METHOD']                         = 'SetExpressCheckout';
        $paypalExpressCheckoutFields['LOGOIMG']                        = CommonManager::getStaticImageUrl($this->container, 'fafrontend/images', 'new-fad-logo.svg');
        $paypalExpressCheckoutFields['PAYFLOWCOLOR']                   = 'e3e3e3';
        $paypalExpressCheckoutFields['CARTBORDERCOLOR']                = 'a8dc28';
        $paypalExpressCheckoutFields['REQCONFIRMSHIPPING']             = '0';
        $paypalExpressCheckoutFields['NOSHIPPING']                     = '1';

        foreach ($cartDetails as $itemNo => $cartDetail) {
            $paypalExpressCheckoutFields['L_PAYMENTREQUEST_0_NAME'.$itemNo] = $cartDetail['title'];
            $paypalExpressCheckoutFields['L_PAYMENTREQUEST_0_AMT'.$itemNo]  = $cartDetail['amount'] - round($cartDetail['vat_amount'], 2);
            $paypalExpressCheckoutFields['L_PAYMENTREQUEST_0_QTY'.$itemNo]  = 1;
            $totalVat = $totalVat + $cartDetail['vat_amount'];
        }

        // set vat.
        $totalVat = round($totalVat, 2);
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_TAXAMT']  = $totalVat;
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_ITEMAMT'] = $cartObj->getAmount() - $totalVat;
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_AMT']     = $cartObj->getAmount();
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_INVNUM']  = $this->paypal_invoice_prefix.$cartObj->getCartCode();

        return $this->getPaypalResponse($paypalExpressCheckoutFields);
    }

    /**
     * Get paypal details based on token.
     *
     * @param string  $paypalToken Paypal token.
     *
     * @return array
     */
    public function getExpressCheckoutDetailsResponse($paypalToken)
    {
        //set parameters for GetExpressCheckoutDetails.
        $paypalExpressCheckoutFields           = $this->getPaypalAccountFields();
        $paypalExpressCheckoutFields['METHOD'] = 'GetExpressCheckoutDetails';
        $paypalExpressCheckoutFields['TOKEN']  = $paypalToken;

        return $this->getPaypalResponse($paypalExpressCheckoutFields);
    }

    /**
     * Complete paypal payment.
     *
     * @param string $paypalToken Paypal token.
     * @param string $payerId     Paypal payer id.
     * @param object $cartObj     Cart object.
     * @param array  $cartDetails Cart detail array.
     *
     * @return array
     */
    public function getDoExpressCheckoutPaymentResponse($paypalToken, $payerId, $cartObj, array $cartDetails)
    {
        $totalVat = 0;
        //set parameters for DoExpressCheckoutPayment.
        $paypalExpressCheckoutFields                                   = $this->getPaypalAccountFields();
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_PAYMENTACTION'] = self::PAYPAL_TRANSACTION;
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_CURRENCYCODE']  = $cartObj->getCurrency();
        $paypalExpressCheckoutFields['METHOD']                         = 'DoExpressCheckoutPayment';
        $paypalExpressCheckoutFields['TOKEN']                          = $paypalToken;
        $paypalExpressCheckoutFields['PAYERID']                        = $payerId;

        //set payment details fields.
        foreach ($cartDetails as $itemNo => $cartDetail) {
            $paypalExpressCheckoutFields['L_PAYMENTREQUEST_0_NAME'.$itemNo] = $cartDetail['title'];
            $paypalExpressCheckoutFields['L_PAYMENTREQUEST_0_AMT'.$itemNo]  = $cartDetail['amount'] - round($cartDetail['vat_amount'], 2);
            $paypalExpressCheckoutFields['L_PAYMENTREQUEST_0_QTY'.$itemNo]  = 1;
            $totalVat = $totalVat + $cartDetail['vat_amount'];
        }

        // set vat.
        $totalVat = round($totalVat, 2);
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_TAXAMT']  = $totalVat;
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_ITEMAMT'] = $cartObj->getAmount() - $totalVat;
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_AMT']     = $cartObj->getAmount();
        $paypalExpressCheckoutFields['PAYMENTREQUEST_0_INVNUM']  = $this->paypal_invoice_prefix.$cartObj->getCartCode();


        return $this->getPaypalResponse($paypalExpressCheckoutFields);
    }

    /**
     * Get paypal redirect url.
     *
     * @param string $paypalToken Paypal token.
     *
     * @return string
     */
    public function getPaypalUrl($paypalToken)
    {
        return $this->paypal_redirect_url.$paypalToken;
    }

    /**
     * Get paypal user authentication fields.
     *
     * @return array
     */
    public function getPaypalAccountFields()
    {
        $paypalAccountField['USER'] = $this->userid;
        $paypalAccountField['PWD'] = $this->password;
        $paypalAccountField['SIGNATURE'] = $this->signature;
        $paypalAccountField['VERSION'] = self::PAYPAL_VERSION;

        return $paypalAccountField;
    }

    /**
     * Get paypal error.
     *
     * @param array $responseArray Paypal response array.
     *
     * @return string
     */
    public function getError($responseArray)
    {
        if (isset($responseArray['L_ERRORCODE0']) && isset($responseArray['L_LONGMESSAGE0'])) {
            return $responseArray['L_ERRORCODE0'].': '.$responseArray['L_LONGMESSAGE0'];
        } else {
            return 'Problem in payment.';
        }
    }
}
