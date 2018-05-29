<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2017, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use AmazonPay\Client as AmazonClient;

/**
 * This manager is used to call Amazon Pay payment.
 *
 * @author Rohini <rohini@fridaymediagroup.com>
 * @copyright 2017 Friday Media Group Ltd
 * @version v1.0
 */
class AmazonPayManager
{
    const AMAZONPAY_VERSION = '1.0';

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Amazon Pay merchant id.
     *
     * @var string
     */
    private $merchant_id;

    /**
     * Amazon Pay access key.
     *
     * @var string
     */
    private $access_key;

    /**
     * Amazon Pay post url.
     *
     * @var string
     */
    private $url;

    /**
     * Amazon Pay secret key.
     *
     * @var string
     */
    private $secret_key;

    /**
     * Amazon Pay redirect url.
     *
     * @var string
     */
    private $amazonpay_redirect_url;

    /**
     * Amazon Pay client id.
     *
     * @var string
     */
    private $client_id;
    
    /**
     * Amazon Pay client secret.
     *
     * @var string
     */
    private $client_secret;

 /**
     * Amazon Pay currency code.
     *
     * @var string
     */
    private $currency_code;

 /**
     * Amazon Pay region.
     *
     * @var string
     */
    private $region;

 /**
     * Amazon Pay sandbox.
     *
     * @var boolean
     */
    private $sandbox;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container             = $container;
        $amazonconfig                = array();
        $amazonpayMode               = $this->container->getParameter('fa.amazon.mode');
        $amazonpayParams             = $this->container->getParameter('fa.amazon.'.$amazonpayMode);
        $this->merchant_id = $amazonconfig['merchant_id'] = $amazonpayParams['amazon_merchant_id'];
        $this->access_key = $amazonconfig['access_key'] = $amazonpayParams['amazon_access_key'];
        $this->secret_key = $amazonconfig['secret_key'] = $amazonpayParams['amazon_secret_key'];
        $this->client_id = $amazonconfig['client_id'] = $amazonpayParams['amazon_client_id'];
        $this->currency_code = $amazonconfig['currency_code'] = $amazonpayParams['amazon_currency_code'];
        $this->region = $amazonconfig['region'] = $amazonpayParams['amazon_region'];
        $this->sandbox = $amazonconfig['sandbox'] = $amazonpayParams['amazon_sandbox'];
        $this->url                   = $this->container->getParameter('fa.amazon.'.$amazonpayMode.'.url');
        $this->amazonpay_redirect_url= $amazonpayParams['amazon_sandbox'];
        $amazonClient = new AmazonClient($amazonconfig);
    }

     /**
     * Get Amazon Pay config array.
     *
     * @param array $amazonpayParams Amazon Pay param array.
     *
     * @return array
     */
    public function getAmazonpayConfig()
    {
        $amazonconfig                = array();
        $amazonconfig['merchant_id'] = $this->merchant_id;
        $amazonconfig['access_key'] = $this->access_key;
        $amazonconfig['secret_key'] = $this->secret_key;
        $amazonconfig['client_id'] = $this->client_id;
        $amazonconfig['currency_code'] = $this->currency_code;
        $amazonconfig['region'] = $this->region;
        $amazonconfig['sandbox'] = $this->sandbox;
        return $amazonconfig;
    }
 
    public function getAmazonCartDetails($requestParameters,$accessToken)
    {
        $amazonconfig = $this->getAmazonpayConfig();
        $amazonClient = new AmazonClient($amazonconfig);
        // Create the parameters array to set the order
        $requestParameters['currency_code']     = $this->currency_code;

        // Set the Order details by making the SetOrderReferenceDetails API call
        $response = $amazonClient->setOrderReferenceDetails($requestParameters);

        // If the API call was a success Get the Order Details by making the GetOrderReferenceDetails API call
        if ($amazonClient->success)
        {
            $requestParameters['access_token'] = $accessToken;
            $response = $amazonClient->getOrderReferenceDetails($requestParameters);
        }
        // Pretty print the Json and then echo it for the Ajax success to take in
        $json = json_decode($response->toJson());
        return json_encode($json, JSON_PRETTY_PRINT);
    }

    public function getAmazonOrderProcess($cart,$container)
    {
        
        $requestParameters = array();
        $amazonconfig = $this->getAmazonpayConfig();
        $amazonClient = new AmazonClient($amazonconfig);
        // Refer to GetDetails.php where the Amazon Order Reference ID was set
        $requestParameters['amazon_order_reference_id'] = $container->get('session')->get('amazon_order_reference_id');

        // Confirm the order by making the ConfirmOrderReference API call
        $response = $amazonClient->confirmOrderReference($requestParameters);

        $responsearray['confirm'] = json_decode($response->toJson());

        // If the API call was a success make the Authorize API call
        if($amazonClient->success)
        {
            $requestParameters['authorization_amount'] = $cart->getAmount();
            $requestParameters['authorization_reference_id'] = uniqid();
            $requestParameters['seller_authorization_note'] = 'Authorizing payment';
            $requestParameters['transaction_timeout'] = 0;

            $response = $amazonClient->authorize($requestParameters);
            $responsearray['authorize'] = json_decode($response->toJson());
        }

        // If the Authorize API call was a success, make the Capture API call when you are ready to capture for the order (for example when the order has been dispatched)
        if($amazonClient->success)
        {
            $requestParameters['amazon_authorization_id'] = $responsearray['authorize']->AuthorizeResult->AuthorizationDetails->AmazonAuthorizationId;
            $requestParameters['capture_amount'] = $responsearray['authorize']->AuthorizeResult->AuthorizationDetails->CapturedAmount->Amount;
            $requestParameters['currency_code'] = $responsearray['authorize']->AuthorizeResult->AuthorizationDetails->CapturedAmount->CurrencyCode;
            $requestParameters['capture_reference_id'] = $responsearray['authorize']->AuthorizeResult->AuthorizationDetails->AuthorizationReferenceId;

            $response = $amazonClient->capture($requestParameters);
            $responsearray['capture'] = json_decode($response->toJson());
        }

        // Echo the Json encoded array for the Ajax success
        return json_encode($responsearray);
    }

    /**
     * Get Amazon Pay response.
     *
     * @param array $amazonpayParams Amazon Pay param array.
     *
     * @return array
     */
    public function getAmazonpayResponse($amazonpayParams)
    {
        // Build the HTTP Request Headers
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($amazonpayParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($this->container->getParameter('fa.amazon.mode') == 'test') {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        }
        echo $this->url;
        $response = curl_exec($ch);
        curl_close($ch);
        parse_str($response, $responseArray);

        return $responseArray;
    }    
}