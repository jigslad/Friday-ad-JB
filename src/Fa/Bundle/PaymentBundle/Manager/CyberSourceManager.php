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
use Fa\Bundle\PaymentBundle\Manager\CyberSourceClientManager;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This manager is used to call cyber source payment.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CyberSourceManager
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

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
     * Wsdl url.
     *
     * @var string
     */
    private $wsdl_url;

    /**
     * Merchant reference code.
     *
     * @var string
     */
    private $merchant_reference_code;

    /**
     * Constructor.
     *
     * @param object  $container      Container identifier.
     *
     */
    public function __construct(Container $container)
    {
        $this->container               = $container;
        $cyberSourceMode               = $this->container->getParameter('fa.cyber.source.mode');
        $cyberSourceParams             = $this->container->getParameter('fa.cyber.source.'.$cyberSourceMode);
        $this->merchant_id             = $cyberSourceParams['merchant_id'];
        $this->transaction_key         = $cyberSourceParams['transaction_key'];
        $this->wsdl_url                = $cyberSourceParams['wsdl_url'];
        $this->merchant_reference_code = $cyberSourceParams['merchant_reference_code'];
    }

    /**
     * Set merchant reference code.
     */
    public function setMerchantReferenceCodeForSubscription()
    {
        $cyberSourceMode               = $this->container->getParameter('fa.cyber.source.mode');
        $cyberSourceParams             = $this->container->getParameter('fa.cyber.source.'.$cyberSourceMode);
        $this->merchant_reference_code = $cyberSourceParams['merchant_reference_code_subscription'];
    }

    /**
     * Set merchant reference code for admin.
     */
    public function setMerchantReferenceCodeForAdmin()
    {
        $cyberSourceMode               = $this->container->getParameter('fa.cyber.source.mode');
        $cyberSourceParams             = $this->container->getParameter('fa.cyber.source.'.$cyberSourceMode);
        $this->merchant_reference_code = $cyberSourceParams['merchant_reference_code_admin'];
    }

    /**
     * Get cyber source response.
     *
     * @param object  $userObj                   User object.
     * @param array   $billToInfo                Billing information array.
     * @param array   $cardInfo                  Credit card information array.
     * @param string  $cart                      Cart.
     * @param array   $cartDetail                Cart detail array.
     * @param boolean $tokenization              Use tokenization or not.
     * @param array   $recurringSubscriptionInfo Recurring subscription info.
     *
     * @throws \Exception
     *
     * @return object
     */
    public function getCyberSourceReply($userObj, array $billToInfo, array $cardInfo, $cart, array $cartDetail, $tokenization = false, $recurringSubscriptionInfo = array(), $allow_zero_amount = false, $payerAuthEnrollService = false, $payerAuthValidateParams = array())
    {
        try {
            $soapClient = new CyberSourceClientManager($this->wsdl_url, array(), $this->merchant_id, $this->transaction_key);

            /*
             To see the functions and types that the SOAP extension can automatically
            generate from the WSDL file, uncomment this section:
            $functions = $soapClient->__getFunctions();
            print_r($functions);
            $types = $soapClient->__getTypes();
            print_r($types);
            */

            $request = new \stdClass();

            $request->merchantID = $this->merchant_id;

            // Before using this example, replace the generic value with your own.
            $request->merchantReferenceCode = $this->merchant_reference_code.$cart->getCartCode();

            // To help us troubleshoot any problems that you may encounter,
            // please include the following information about your PHP application.
            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();

            if ($payerAuthEnrollService && count($payerAuthValidateParams)) {
                $paRes = $payerAuthValidateParams['PaRes'].$payerAuthValidateParams['MD'];
                $paRes = str_replace(array("\n", "\r", "\t", " ", "\o", "\xOB"), '', $paRes); //Strips White space from PARes
                $payerAuthValidateService = new \stdClass();
                $payerAuthValidateService->run = "true";
                $payerAuthValidateService->signedPARes = $paRes;
                $request->payerAuthValidateService = $payerAuthValidateService;
            } elseif ($payerAuthEnrollService) {
                $payerAuthEnrollService = new \stdClass();
                $payerAuthEnrollService->run = "true";
                $request->payerAuthEnrollService = $payerAuthEnrollService;
            }

            if (!$allow_zero_amount) {
                $request->ccAuthService = $this->getCcAuthService();
                $request->ccCaptureService = $this->getCcCaptureService();
            }
            $request->billTo        = $this->getBillingInfo($billToInfo);
            $request->card          = $this->getCardInfo($cardInfo);
            $request->item          = $this->getCartInfo($cartDetail, $allow_zero_amount);

            // if tokenization then pass subsciption info.
            if ($tokenization) {
                $request->paySubscriptionCreateService = $this->getPaySubscriptionCreateService();
                $request->recurringSubscriptionInfo    = $this->getRecurringSubscriptionInfo($recurringSubscriptionInfo);
            }

            $purchaseTotals = new \stdClass();
            $purchaseTotals->currency = CommonManager::getCurrencyCode($this->container);
            $request->purchaseTotals = $purchaseTotals;

            return $soapClient->runTransaction($request);
        } catch (\SoapFault $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
            $messageManager = $this->container->get('fa.message.manager');
            $messageManager->setFlashMessage($e->getMessage(), 'error');
            return false;
        }
    }


    /**
     * Get cyber source response.
     *
     * @param object  $userObj                   User object.
     * @param array   $billToInfo                Billing information array.
     * @param string  $cart                      Cart.
     * @param array   $cartDetail                Cart detail array.
     * @param array   $recurringSubscriptionInfo Recurring subscription info.
     * @param array   $allow_zero_amount         Allow zero amount transcation
     *
     * @throws \Exception
     *
     * @return object
     */
    public function getCyberSourceReplyForToken($userObj, array $billToInfo, $cart, array $cartDetail, $recurringSubscriptionInfo = array(), $allow_zero_amount = false, $payerAuthEnrollService = false, $payerAuthValidateParams = array())
    {
        try {
            $soapClient = new CyberSourceClientManager($this->wsdl_url, array(), $this->merchant_id, $this->transaction_key);
            $request    = new \stdClass();

            $request->merchantID = $this->merchant_id;
            // Before using this example, replace the generic value with your own.
            $request->merchantReferenceCode = $this->merchant_reference_code.$cart->getCartCode();
            // To help us troubleshoot any problems that you may encounter,
            // please include the following information about your PHP application.
            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();

            if ($payerAuthEnrollService && count($payerAuthValidateParams)) {
                $paRes = $payerAuthValidateParams['PaRes'].$payerAuthValidateParams['MD'];
                $paRes = str_replace(array("\n", "\r", "\t", " ", "\o", "\xOB"), '', $paRes); //Strips White space from PARes
                $payerAuthValidateService = new \stdClass();
                $payerAuthValidateService->run = "true";
                $payerAuthValidateService->signedPARes = $paRes;
                $request->payerAuthValidateService = $payerAuthValidateService;
            } elseif ($payerAuthEnrollService) {
                $payerAuthEnrollService = new \stdClass();
                $payerAuthEnrollService->run = "true";
                $request->payerAuthEnrollService = $payerAuthEnrollService;
            }

            $request->ccAuthService             = $this->getCcAuthService();
            $request->ccCaptureService          = $this->getCcCaptureService();
            $request->billTo                    = $this->getBillingInfo($billToInfo);
            $request->item                      = $this->getCartInfo($cartDetail, $allow_zero_amount);
            $request->recurringSubscriptionInfo = $this->getRecurringSubscriptionInfo($recurringSubscriptionInfo);

            $purchaseTotals = new \stdClass();
            $purchaseTotals->currency = CommonManager::getCurrencyCode($this->container);
            $purchaseTotals->grandTotalAmount = $cart->getAmount();
            $request->purchaseTotals = $purchaseTotals;

            return $soapClient->runTransaction($request);
        } catch (\SoapFault $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
            $messageManager = $this->container->get('fa.message.manager');
            $messageManager->setFlashMessage($e->getMessage(), 'error');
            return false;
        }
    }

    public function getCyberSourceReplyForSubscriptionRecurring($subscriptionId, $package)
    {
        try {
            $soapClient = new CyberSourceClientManager($this->wsdl_url, array(), $this->merchant_id, $this->transaction_key);
            $request    = new \stdClass();
            $request->ccAuthService = $this->getCcAuthService();

            $request->merchantID = $this->merchant_id;
            // Before using this example, replace the generic value with your own.
            $request->merchantReferenceCode = $this->merchant_reference_code.rand();
            // To help us troubleshoot any problems that you may encounter,
            // please include the following information about your PHP application.
            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();

            $cc_capture_service = new \stdClass();
            $cc_capture_service->run = 'true';
            $request->ccCaptureService = $cc_capture_service;

            // actually remember to add the subscription ID that we're billing... duh!
            $subscription_info = new \stdClass();
            $subscription_info->subscriptionID = $subscriptionId;
            $request->recurringSubscriptionInfo = $subscription_info;


            $purchaseTotals = new \stdClass();
            $purchaseTotals->currency = CommonManager::getCurrencyCode($this->container);
            $purchaseTotals->grandTotalAmount = $package->getPrice();
            $request->purchaseTotals = $purchaseTotals;

            return $soapClient->runTransaction($request);
        } catch (\SoapFault $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
            return false;
        }
    }

    public function checkSavedToken($subscriptionId)
    {
        try {
            $soapClient = new CyberSourceClientManager($this->wsdl_url, array(), $this->merchant_id, $this->transaction_key);
            $request    = new \stdClass();

            $request->merchantID = $this->merchant_id;
            // Before using this example, replace the generic value with your own.
            $request->merchantReferenceCode = $this->merchant_reference_code.rand();
            // To help us troubleshoot any problems that you may encounter,
            // please include the following information about your PHP application.
            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();

            $subscription_retrieve = new \stdClass();
            $subscription_retrieve->run = 'true';
            $request->paySubscriptionRetrieveService = $subscription_retrieve;


            // this also is pretty stupid, particularly the name
            $purchase_totals = new \stdClass();
            $purchase_totals->currency = CommonManager::getCurrencyCode($this->container);
            $request->purchaseTotals = $purchase_totals;

            // actually remember to add the subscription ID that we're billing... duh!
            $subscription_info = new \stdClass();
            $subscription_info->subscriptionID = $subscriptionId;
            $request->recurringSubscriptionInfo = $subscription_info;


            return $soapClient->runTransaction($request);
        } catch (\SoapFault $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Get cyber source response.
     *
     * @param object  $userObj                   User object.
     * @param array   $billToInfo                Billing information array.
     * @param array   $cardInfo                  Credit card information array.
     * @param string  $cart                      Cart.
     * @param array   $cartDetail                Cart detail array.
     * @param array   $recurringSubscriptionInfo Recurring subscription info.
     *
     * @throws \Exception
     *
     * @return object
     */
    public function deleteToken($recurringSubscriptionInfo = array())
    {
        try {
            $soapClient = new CyberSourceClientManager($this->wsdl_url, array(), $this->merchant_id, $this->transaction_key);
            $request    = new \stdClass();

            $request->merchantID = $this->merchant_id;
            // Before using this example, replace the generic value with your own.
            $request->merchantReferenceCode = $this->merchant_reference_code;
            // To help us troubleshoot any problems that you may encounter,
            // please include the following information about your PHP application.
            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();

            $request->paySubscriptionDeleteService = $this->getPaySubscriptionDeleteService();
            $request->recurringSubscriptionInfo    = $this->getRecurringSubscriptionInfo($recurringSubscriptionInfo);

            return $soapClient->runTransaction($request);
        } catch (\SoapFault $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
            $messageManager = $this->container->get('fa.message.manager');
            $messageManager->setFlashMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Create customer profile.
     *
     * @param array   $billToInfo                Billing information array.
     * @param array   $cardInfo                  Credit card information array.
     *
     * @throws \Exception
     *
     * @return object
     */
    public function createCustomerProfile(array $billToInfo, array $cardInfo)
    {
        try {
            $soapClient = new CyberSourceClientManager($this->wsdl_url, array(), $this->merchant_id, $this->transaction_key);

            $request = new \stdClass();

            $request->merchantID = $this->merchant_id;

            // Before using this example, replace the generic value with your own.
            $request->merchantReferenceCode = $this->merchant_reference_code; //.$cart->getCartCode();

            // To help us troubleshoot any problems that you may encounter,
            // please include the following information about your PHP application.
            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();

            $request->billTo = $this->getBillingInfo($billToInfo);
            $request->card   = $this->getCardInfo($cardInfo);

            $request->paySubscriptionCreateService = $this->getPaySubscriptionCreateService();
            $request->recurringSubscriptionInfo    = $this->getRecurringSubscriptionInfo(array('frequency' => 'on-demand'));

            $purchaseTotals = new \stdClass();
            $purchaseTotals->currency = CommonManager::getCurrencyCode($this->container);
            $request->purchaseTotals = $purchaseTotals;

            return $soapClient->runTransaction($request);
        } catch (\SoapFault $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Problem in creating customer profile.', $e->getMessage(), $e->getTraceAsString());
            $messageManager = $this->container->get('fa.message.manager');
            $messageManager->setFlashMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get ccAuthService.
     *
     * @return \stdClass
     */
    private function getCcAuthService()
    {
        $ccAuthService = new \stdClass();
        $ccAuthService->run = "true";

        return $ccAuthService;
    }

    /**
     * Get ccCaptureService.
     *
     * @return \stdClass
     */
    private function getCcCaptureService()
    {
        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";

        return $ccCaptureService;
    }

    /**
     * Get PaySubscriptionCreateService.
     *
     * @return \stdClass
     */
    private function getPaySubscriptionCreateService()
    {
        $paySubscriptionCreateService = new \stdClass();
        $paySubscriptionCreateService->run = "true";

        return $paySubscriptionCreateService;
    }

    /**
     * Get PaySubscriptionDeleteService.
     *
     * @return \stdClass
     */
    private function getPaySubscriptionDeleteService()
    {
        $paySubscriptionDeleteService = new \stdClass();
        $paySubscriptionDeleteService->run = "true";

        return $paySubscriptionDeleteService;
    }

    /**
     * Get recurringSubscriptionInfo.
     *
     * @return \stdClass
     */
    private function getRecurringSubscriptionInfo($recurringSubscriptionInfoArr = array())
    {
        $recurringSubscriptionInfo = new \stdClass();
        if (count($recurringSubscriptionInfoArr)) {
            foreach ($recurringSubscriptionInfoArr as $fieldName => $fieldValue) {
                $recurringSubscriptionInfo->$fieldName = $fieldValue;
            }
        } else {
            $recurringSubscriptionInfo->run = "true";
        }

        return $recurringSubscriptionInfo;
    }

    /**
     * Get billing info.
     *
     * @param array $billToInfo Billing info array.
     *
     * @return \stdClass
     */
    private function getBillingInfo($billToInfo)
    {
        $billTo = new \stdClass();
        foreach ($billToInfo as $fieldName => $fieldValue) {
            $billTo->$fieldName = $fieldValue;
        }

        return $billTo;
    }

    /**
     * Get card information.
     *
     * @param array $cardInfo Card info array.
     *
     * @return \stdClass
     */
    private function getCardInfo($cardInfo)
    {
        $card = new \stdClass();
        foreach ($cardInfo as $fieldName => $fieldValue) {
            $card->$fieldName = $fieldValue;
        }

        return $card;
    }

    /**
     * Get cart information
     *
     * @param array   $cartDetail        Cart detail array.
     * @param boolean $allow_zero_amount allow zero amount or not
     *
     * @return mixed
     */
    private function getCartInfo($cartDetail, $allow_zero_amount = false)
    {
        $itemArray = array();

        foreach ($cartDetail as $cartItem => $cartItemDetail) {
            if ($allow_zero_amount || (isset($cartItemDetail['amount']) && $cartItemDetail['amount'] > 0)) {
                $vatAmount = round($cartItemDetail['vat_amount'], 2);
                $item = new \stdClass();
                $item->unitPrice = $cartItemDetail['amount'] - $vatAmount;
                $item->quantity  = "1";
                $item->taxAmount = $vatAmount;
                $item->productName = $cartItemDetail['title'];
                $item->id = $cartItem;
                $itemArray[] = $item;
            }
        }

        return $itemArray;
    }

    /**
     * Get cyber source error.
     *
     * @param integer $reasonCode Reason code.
     *
     * @return string
     */
    public function getError($reasonCode)
    {
        $errorMsg     = 'Error in cyber source payment process';
        $error['101'] = 'The request is missing one or more required fields.';
        $error['102'] = 'One or more fields in the request contains invalid data.';
        $error['104'] = 'The merchantReferenceCode sent with this authorization request matches the merchantReferenceCode of another authorization request that you sent in the last 15 minutes.';
        $error['150'] = 'Error: General system failure.';
        $error['151'] = 'Error: The request was received but there was a server timeout. This error does not include timeouts between the client and the server.';
        $error['152'] = 'Error: The request was received, but a service did not finish running in time.';
        $error['201'] = 'The issuing bank has questions about the request. You do not receive an authorization code programmatically, but you might receive one verbally by calling the processor.';
        $error['202'] = 'Expired card.';
        $error['203'] = 'General decline of the card. No other information provided by the issuing bank.';
        $error['204'] = 'Insufficient funds in the account.';
        $error['205'] = 'Stolen or lost card.';
        $error['207'] = 'Issuing bank unavailable.';
        $error['208'] = 'Inactive card or card not authorized for card-not-present transactions.';
        $error['210'] = 'The card has reached the credit limit.';
        $error['211'] = 'Invalid card verification number.';
        $error['221'] = 'The customer matched an entry on the processor’s negative file.';
        $error['231'] = 'Invalid account number.';
        $error['232'] = 'The card type is not accepted by the payment processor.';
        $error['233'] = 'General decline by the processor.';
        $error['234'] = 'There is a problem with your CyberSource merchant configuration.';
        $error['235'] = 'The requested amount exceeds the originally authorized amount. Occurs, for example, if you try to capture an amount larger than the original authorization amount. This reason code applies if you are processing a capture through the API.';
        $error['236'] = 'Processor failure.';
        $error['238'] = 'The authorization has already been captured. This reason code applies if you are processing a capture through the API.';
        $error['239'] = 'The requested transaction amount must match the previous transaction amount.';
        $error['240'] = 'The card type sent is invalid or does not correlate with the credit card number.';
        $error['241'] = 'The request ID is invalid. This reason code applies when you are processing a capture or credit through the API.';
        $error['242'] = 'You requested a capture through the API, but there is no corresponding, unused authorization record. Occurs if there was not a previously successful authorization request or if the previously successful authorization has already been used by another capture request.';
        $error['250'] = 'Error: The request was received, but there was a timeout at the payment processor.';
        $error['520'] = 'The authorization request was approved by the issuing bank but declined by CyberSource based on your Smart Authorization settings.';
        $error['476'] = 'The customer cannot be authenticated.';

        if (isset($error[$reasonCode])) {
            $errorMsg = $error[$reasonCode];
        }

        return $errorMsg;
    }
}
