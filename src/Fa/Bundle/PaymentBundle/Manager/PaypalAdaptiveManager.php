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
class PaypalAdaptiveManager
{
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
     * Paypal applicationid.
     *
     * @var string
     */
    private $applicationid;

    /**
     * Paypal redirect url.
     *
     * @var string
     */
    private $paypal_redirect_url;

    /**
     * Paypal mobile redirect url.
     *
     * @var string
     */
    private $paypal_mobile_redirect_url;

    /**
     * Paypal secondary email.
     *
     * @var string
     */
    private $secondary_paypal_email;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container                  = $container;
        $paypalMode                       = $this->container->getParameter('fa.paypal.mode');
        $paypalParams                     = $this->container->getParameter('fa.paypal.'.$paypalMode);
        $this->userid                     = $paypalParams['userid'];
        $this->password                   = $paypalParams['password'];
        $this->url                        = $paypalParams['adaptive_payment_url'];
        $this->signature                  = $paypalParams['signature'];
        $this->paypal_redirect_url        = $paypalParams['adaptive_payment_redirect_url'];
        $this->paypal_mobile_redirect_url = $paypalParams['adaptive_payment_mobile_redirect_url'];
        $this->applicationid              = $paypalParams['applicationid'];
        $this->secondary_paypal_email     = $paypalParams['adaptive_payment_secondary_paypal_email'];
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($this->container->getParameter('fa.paypal.mode') == 'test') {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        }
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array (
                'X-PAYPAL-SECURITY-USERID: '.$this->userid,
                'X-PAYPAL-SECURITY-PASSWORD : '.$this->password,
                'X-PAYPAL-SECURITY-SIGNATURE : '.$this->signature,
                'X-PAYPAL-APPLICATION-ID : '.$this->applicationid,
                'X-PAYPAL-REQUEST-DATA-FORMAT : NV',
                'X-PAYPAL-RESPONSE-DATA-FORMAT : NV',
            )
        );

        parse_str(curl_exec($ch), $response);

        return $response;
    }

    /**
     * Get paypal adaptive payment response
     *
     * @param string  $returnUrl        Redirect url.
     * @param string  $cacelUrl         Cancel url.
     * @param object  $cartObj          Cart object.
     * @param integer $paypalCommission Paypal commission.
     *
     * @return array
     */
    public function getAdaptivePaymentResponse($returnUrl, $cacelUrl, $cartObj, $paypalCommission)
    {
        $this->url = $this->url.'Pay';
        $commissionPrice = (($cartObj->getAmount() * $paypalCommission) / 100);
        $commissionPrice = round($commissionPrice, 2);
        $cartValues      = unserialize($cartObj->getValue());
        //set parameters for adaptive payment.
        if (isset($cartValues['paypal']) && isset($cartValues['paypal']['ad_id'])) {
            $adObj = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $cartValues['paypal']['ad_id']));
            if ($adObj) {
                $paypalAdaptiveFields['actionType']    = 'PAY';
                $paypalAdaptiveFields['cancelUrl']     = $cacelUrl;
                $paypalAdaptiveFields['returnUrl']     = $returnUrl;
                $paypalAdaptiveFields['currencyCode']  = $cartObj->getCurrency();
                $paypalAdaptiveFields['receiverList.receiver(0).amount']  = $cartObj->getAmount();
                $paypalAdaptiveFields['receiverList.receiver(0).email']   = $adObj->getUser()->getPaypalEmail();
                if ($commissionPrice > 0) {
                    $paypalAdaptiveFields['receiverList.receiver(0).primary'] = true;
                    $paypalAdaptiveFields['feesPayer']                        = 'PRIMARYRECEIVER';
                    $paypalAdaptiveFields['receiverList.receiver(1).amount']  = $commissionPrice;
                    $paypalAdaptiveFields['receiverList.receiver(1).email']   = $this->secondary_paypal_email;
                    $paypalAdaptiveFields['receiverList.receiver(1).primary'] = false;
                    //$paypalAdaptiveFields['ipn_notification_url']             = $returnUrl;
                }
                $paypalAdaptiveFields['requestEnvelope.errorLanguage']    = 'en_US';
            }
        }

        return $this->getPaypalResponse($paypalAdaptiveFields);
    }

    /**
     * Get paypal adaptive payment response
     *
     * @param string  $returnUrl        Redirect url.
     * @param string  $cacelUrl         Cancel url.
     * @param object  $cartObj          Cart object.
     * @param integer $paypalCommission Paypal commission.
     *
     * @return array
     */
    public function getPaymentDetailResponse($payKey)
    {
        $this->url = $this->url.'PaymentDetails';
        //set parameters for adaptive payment.
        $paypalAdaptiveFields['payKey']                        = $payKey;
        $paypalAdaptiveFields['requestEnvelope.errorLanguage'] = 'en_US';

        return $this->getPaypalResponse($paypalAdaptiveFields);
    }

    /**
     * Get paypal redirect url.
     *
     * @param string $payKey Paypal pay key.
     *
     * @return string
     */
    public function getPaypalUrl($payKey)
    {
        return $this->paypal_redirect_url.$payKey;
    }

    /**
     * Get paypal mobile redirect url.
     *
     * @param string $payKey Paypal pay key.
     *
     * @return string
     */
    public function getMobilePaypalUrl($payKey)
    {
        return $this->paypal_mobile_redirect_url.$payKey;
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
        if (isset($responseArray['error(0)_message']) && isset($responseArray['error(0)_errorId'])) {
            return $responseArray['error(0)_errorId'].': '.$responseArray['error(0)_message'];
        } else {
            return 'Problem in payment.';
        }
    }
}
