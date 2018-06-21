<?php
namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Fa\Bundle\CoreBundle\Manager\YacManager
 *
 * This manager is used to allocate / update / remove yac numbers.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class YacManager
{
    /**
     * Container service class object
     *
     * @var object
     */
    private $container;

    /**
     * Yac request url
     */
    private $yacRequestUrl;

    /**
     * Yac affiliated id
     */
    private $yacAffiliateId;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * init
     */
    public function init()
    {
        $yacParams            = $this->container->getParameter('fa.yac');
        $this->yacRequestUrl  = $yacParams['url'];
        $this->yacAffiliateId = $yacParams['affiliateId'];
    }

    /**
     * get yac response
     *
     * @param string $xmlBody
     *
     * @return string
     */
    private function getYacResponse($xmlBody)
    {
        // Build the HTTP Request Headers
        $ch = curl_init($this->yacRequestUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     *
     * @param integer $adId
     * @param string  $phoneNumber
     * @param integer $expireDate
     * @param string  $info
     *
     * @return array
     */
    public function allocateYacNumber($adId, $phoneNumber, $expireDate, $info = '')
    {
        try {
            // extend expiry 10 days more than expire.
            if ($expireDate) {
                $expireDate = date('Ymd', strtotime('+10 days', $expireDate));
            }
            $xmlBody  = '<request>
                        <f_affiliateID>'.$this->yacAffiliateId.'</f_affiliateID>
                        <f_requestType>Allocate</f_requestType>
                        <f_ID>'.$adId.'</f_ID>
                        <f_phoneNumber1>'.$phoneNumber.'</f_phoneNumber1>
                        <f_expireDate>'.$expireDate.'</f_expireDate>
                        <f_info>'.$info.'</f_info>
                    </request>';
            echo $xmlBody;
            $xmlResponse = $this->getYacResponse($xmlBody);
            $xml         = new \SimpleXMLElement($xmlResponse);
            $yacStatus   = $xml->f_status->__toString();
            if ($yacStatus != 0) {
                $errorMsg = $this->getYacError($yacStatus);
                CommonManager::sendErrorMail($this->container, 'Error: Yac manager allocateYacNumber for ad '.$adId, $errorMsg, '');
                return array('error' => $errorMsg, 'YacNumber' => '', 'errorCode' => $yacStatus);
            } elseif ($yacStatus == 0) {
                return array('error' => '', 'YacNumber' => $xml->f_YACnumber->__toString(), 'errorCode' => $yacStatus);
            }
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Yac manager allocateYacNumber for ad '.$adId, $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     *
     * @param integer $yacNumber
     *
     * @return array
     */
    public function removeYacNumber($yacNumber)
    {
        try {
            $xmlBody  = '<request>
                        <f_affiliateID>'.$this->yacAffiliateId.'</f_affiliateID>
                        <f_requestType>SetSold</f_requestType>
                        <f_ID></f_ID>
                        <f_YACnumber>'.$yacNumber.'</f_YACnumber>
                        <f_phoneNumber1></f_phoneNumber1>
                        <f_phoneNumber2></f_phoneNumber2>
                        <f_durationInDays></f_durationInDays>
                        <f_info></f_info>
                        <f_emailAddress></f_emailAddress>
                    </request>';

            $xmlResponse = $this->getYacResponse($xmlBody);
            $xml         = new \SimpleXMLElement($xmlResponse);
            $yacStatus = $xml->f_status->__toString();
            if ($yacStatus != 0) {
                $errorMsg = $this->getYacError($yacStatus);
                CommonManager::sendErrorMail($this->container, 'Error: Yac manager removeYacNumber for number '.$yacNumber, $errorMsg, '');
                return array('error' => $errorMsg, 'errorCode' => $yacStatus);
            } elseif ($yacStatus == 0) {
                return true;
            }
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Yac manager removeYacNumber for yacNumber '.$yacNumber, $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     *
     * @param integer $yacNumber
     * @param integer $phoneNumber
     *
     * @return multitype:string |boolean
     */
    public function editPhoneNumber($yacNumber, $phoneNumber)
    {
        try {
            $xmlBody  = '<request>
                        <f_affiliateID>'.$this->yacAffiliateId.'</f_affiliateID>
                        <f_requestType>Edit</f_requestType>
                        <f_YACnumber>'.$yacNumber.'</f_YACnumber>
                        <f_phoneNumber1>'.$phoneNumber.'</f_phoneNumber1>
                        <f_phoneNumber2></f_phoneNumber2>
                        <f_info></f_info>
                        <f_emailAddress></f_emailAddress>
                    </request>';

            $xmlResponse = $this->getYacResponse($xmlBody);

            $xml         = new \SimpleXMLElement($xmlResponse);
            $yacStatus = $xml->f_status->__toString();
            if ($yacStatus != 0) {
                $errorMsg = $this->getYacError($yacStatus);
                CommonManager::sendErrorMail($this->container, 'Error: Yac manager editPhoneNumber for yacnumber '.$yacNumber, $errorMsg, '');
                return array('error' => $errorMsg, 'errorCode' => $yacStatus);
            } elseif ($yacStatus == 0) {
                return true;
            }
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Yac manager editPhoneNumber for yacNumber '.$yacNumber, $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     *
     * @param integer $yacNumber
     * @param string  $newExpiryDate
     *
     * @return multitype:string |boolean
     */
    public function extendYacNumber($yacNumber, $newExpiryDate)
    {
        try {
            // extend expiry 10 days more than expire.
            if ($newExpiryDate) {
                $newExpiryDate = date('Ymd', strtotime('+10 days', $newExpiryDate));
            }
            $xmlBody  = '<request>
                        <f_affiliateID>'.$this->yacAffiliateId.'</f_affiliateID>
                        <f_requestType>Extend</f_requestType>
                        <f_YACnumber>'.$yacNumber.'</f_YACnumber>
                        <f_expireDate>'.$newExpiryDate.'</f_expireDate>
                    </request>';

            $xmlResponse = $this->getYacResponse($xmlBody);

            if (!trim($xmlResponse)) {
                return array('error' => 'Unable to pase xml', 'errorCode' => 'XML_ERROR');
            } else {
                $xml         = new \SimpleXMLElement($xmlResponse);
                $yacStatus = $xml->f_status->__toString();
                if ($yacStatus != 0) {
                    $errorMsg = $this->getYacError($yacStatus);
                    CommonManager::sendErrorMail($this->container, 'Error: Yac manager extendYacNumber for yacnumber'.$yacNumber, $errorMsg, '');
                    return array('error' => $errorMsg, 'errorCode' => $yacStatus);
                } elseif ($yacStatus == 0) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Yac manager extendYacNumber for yacNumber '.$yacNumber, $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     * getYacError
     *
     * @param string $yacStatus
     *
     * @return string
     */
    private function getYacError($yacStatus)
    {
        $yacErrors = array(
            '1' => 'Reserved.',
            '2' => 'YAC server side error.',
            '3' => 'Reserved.',
            '4' => 'Phone number1 invalid.',
            '5' => 'Phone number2 invalid.',
            '6' => 'No number in pool for Allocate operation, number expired or in SetSold state for SetSold, Edit and Extend operations.',
            '7' => 'Wrong input data, can not parse XML.',
            '8' => 'Non existing ID for SetSold, Edit and Extend operations.',
            '-100' =>  'Reserved.',
            '-101' => 'The email address is too long.',
            '-102' => 'The first phone number to redirect is too long.',
            '-103' => 'The second phone number to redirect is too long.',
            '-104' => 'The first phone number to redirect is a mandatory parameter for this request and can not be empty.',
            '-105' => 'The expiry date should be in future.',
            '-106' => 'The ID field value is too long.',
            '-107' => 'The info field value is too long.',
            '-108' => 'Invalid Virtual YAC Number passed.',
            '-109' => 'The Virtual YAC number is a mandatory parameter for this request and can not be empty.',
            '-110' => 'Either expiry date or duration must be specified.',
            '-111' => 'The only one of the parameters expiry date or duration should be specified.',
            '-112' => 'The duration in days parameter value must be a positive integer.',
            '-113' => '-114 -115 - reserved',
            '-116' => 'Invalid Virtual YAC Number passed.',
            '-117' => 'The status of the account is "SOLD". Any editing is prohibited. Please, allocate the new number.',
            '-118' => 'Reserved.',
            '-119' => 'You are not allowed to set status "SOLD" for the expired account.',
            '-120' => 'There are no available numbers in the pool.',
            '-121' => 'This number has not been allocated.',
        );

        return isset($yacErrors[$yacStatus]) ? $yacErrors[$yacStatus] : 'Undefined error occured.';
    }
}
