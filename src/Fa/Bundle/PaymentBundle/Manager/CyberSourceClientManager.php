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

/**
 * This manager is used to call cyber source payment.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CyberSourceClientManager extends \SoapClient
{
    /**
     * Merchant id.
     *
     * @var string
     */
    private $merchant_id;
    
    /**
     * Transaction key.
     *
     * @var string
     */
    private $transaction_key;
    
    /**
     * Constructor.
     *
     * @param string $wsdl            Wsdl url.
     * @param string $options         Option's array.
     * @param string $merchant_id     Merchant id.
     * @param string $transaction_key Transaction key.
     */
    public function __construct($wsdl, $options = null, $merchant_id = null, $transaction_key = null)
    {
        parent::__construct($wsdl, $options);
        $this->merchant_id = $merchant_id;
        $this->transaction_key = $transaction_key;
    }
    
    /**
     * Do request.
     *
     * @param string $request  Request.
     * @param string $location Location.
     * @param string $action   Action.
     * @param string $version  Version.
     * @param string $one_way  One way.
     *
     * @return object.
     */
    public function __doRequest($request, $location, $action, $version, $one_way = null)
    {
        $soapHeader = "<SOAP-ENV:Header xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\"><wsse:Security SOAP-ENV:mustUnderstand=\"1\"><wsse:UsernameToken><wsse:Username>$this->merchant_id</wsse:Username><wsse:Password Type=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText\">$this->transaction_key</wsse:Password></wsse:UsernameToken></wsse:Security></SOAP-ENV:Header>";
    
        $requestDOM = new \DOMDocument('1.0');
        $soapHeaderDOM = new \DOMDocument('1.0');
    
        try {
            $requestDOM->loadXML($request);
            $soapHeaderDOM->loadXML($soapHeader);
    
            $node = $requestDOM->importNode($soapHeaderDOM->firstChild, true);
            $requestDOM->firstChild->insertBefore($node, $requestDOM->firstChild->firstChild);
    
            $request = $requestDOM->saveXML();
    
        } catch (\Exception $e) {
            die( 'Error adding UsernameToken: ' . $e->code);
        }
    
        return parent::__doRequest($request, $location, $action, $version);
    }
}
