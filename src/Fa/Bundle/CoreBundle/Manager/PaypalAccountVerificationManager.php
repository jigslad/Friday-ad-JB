<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * This manager is used to verifiy paypal account of user based on email address.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class PaypalAccountVerificationManager
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container Container identifier.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Verify user's paypal account based on email address.
     *
     * @param string $emailAddress  User's email address.
     * @param string $matchCriteria Either NONE or NAME.
     * @param string $firstName     This field is required if $matchCriteria is NAME.
     * @param string $lastName      This field is required if $matchCriteria is NAME.
     *
     * @return boolean
     */
    /*public function verifyPaypalAccountByEmail($emailAddress, $matchCriteria = "NONE", $firstName = null, $lastName = null)
    {
        $mode         = $this->container->getParameter('fa.paypal.mode');
        $paypalParams = $this->container->getParameter('fa.paypal.'.$mode);

        $body['emailAddress']  = $emailAddress;
        $body['matchCriteria'] = $matchCriteria;
        if ($matchCriteria == 'NAME') {
            $body['firstName'] = $firstName;
            $body['lastName']  = $lastName;
        }
        // Build the HTTP Request Headers
        $ch = curl_init($paypalParams['account_verification_url']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        if ($this->container->getParameter('fa.paypal.mode') == 'test') {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        }
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'X-PAYPAL-SECURITY-USERID: '.$paypalParams['userid'],
                'X-PAYPAL-SECURITY-PASSWORD : '.$paypalParams['password'],
                'X-PAYPAL-SECURITY-SIGNATURE : '.$paypalParams['signature'],
                'X-PAYPAL-APPLICATION-ID : '.$paypalParams['applicationid'],
                'X-PAYPAL-REQUEST-DATA-FORMAT : JSON',
                'X-PAYPAL-RESPONSE-DATA-FORMAT : JSON',
                'X-PAYPAL-DEVICE-IPADDRESS : '.$this->container->get('request_stack')->getCurrentRequest()->getClientIp(),
            )
        );

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (isset($response['responseEnvelope']) && isset($response['responseEnvelope']['ack']) && strtolower($response['responseEnvelope']['ack']) == 'success' && isset($response['accountStatus']) && strtolower($response['accountStatus']) == 'verified') {
            return true;
        }

        return false;
    }*/
    public function verifyPaypalAccountByEmail($emailAddress, $matchCriteria = "NONE", $firstName = null, $lastName = null)
    {
        $mode         = $this->container->getParameter('fa.paypal.mode');
        $paypalParams = $this->container->getParameter('fa.paypal.'.$mode);
        
        $body['emailAddress']  = $emailAddress;
        $body['matchCriteria'] = $matchCriteria;
        if ($matchCriteria == 'NAME') {
            $body['firstName'] = $firstName;
            $body['lastName']  = $lastName;
        }
        $postData = json_encode($body);
        // Build the HTTP Request Headers
        $ch = curl_init();
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => $paypalParams['account_verification_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Length: ".strlen($postData),
                "Content-Type: application/x-www-form-urlencoded",
                "X-PAYPAL-APPLICATION-ID: ".$paypalParams['applicationid'],
                "X-PAYPAL-REQUEST-DATA-FORMAT: JSON",
                "X-PAYPAL-RESPONSE-DATA-FORMAT: JSON",
                "X-PAYPAL-SECURITY-PASSWORD: ".$paypalParams['password'],
                "X-PAYPAL-SECURITY-SIGNATURE: ".$paypalParams['signature'],
                "X-PAYPAL-SECURITY-USERID: ".$paypalParams['userid'],
            ),
        ));
        
        $exeCurl = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $response = json_decode($exeCurl,true);
        if ($err) {
            echo "cURL Error #:" . $err;
        }
        if (isset($response['responseEnvelope']) && isset($response['responseEnvelope']['ack']) && strtolower($response['responseEnvelope']['ack']) == 'success' && isset($response['accountStatus']) && strtolower($response['accountStatus']) == 'verified') {
            return true;
        }        
        return false;
    }
}
