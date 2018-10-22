<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Resource;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
// use Symfony\Component\Locale\Stub\DateFormat;
use Symfony\Component\Intl\DateFormatter\DateFormat;

/**
 * This controller is used for dot mailer integration.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
abstract class DotMailerRequestBuild
{

    /**
     * Response from dot mailer.
     *
     * @var string
     */
    private $response = null;

    /**
     * Httpcode from dot mailer.
     *
     * @var string
     */
    private $httpcode = null;

    /**
     * Response body from dotmailer.
     *
     * @var string
     */
    private $response_body = null;

    /**
     * Data that we want to submit to dot mailer in json format.
     *
     * @var string
     */
    protected $dataToSubmit;

    /**
     * Get dot mailer resource.
     * http://api.dotmailer.com/v2/help/wadl
     */
    abstract public function getDotMailerResource();

    /**
     * Get http method.
     */
    abstract public function getHttpMethod();

    /**
     * Get customer request method.
     */
    public function getCustomRequest() {
        return null;
    }

    /**
     * Set data to submit.
     *
     * @param string $data
     */
    abstract public function setDataToSubmit($data);

    /**
     * Get container.
     */
    abstract public function getContainer();

    /**
     * Initialize dot mailer request build.
     *
     * @return array
     */
    public function init()
    {
    }

    /**
     * Get response of dot mailer.
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Get response body of dot mailer.
     */
    public function getResponseBody() {
        return $this->response_body;
    }

    /**
     * Get httpcode from dot mailer.
     */
    public function getHttpcode() {
        return $this->httpcode;
    }

    /**
     * Send request to ad moderation url.
     *
     * @return boolean
     */
    public function sendRequest()
    {
        $url = $this->getContainer()->getParameter('fa.dotmailer.api.url').'/'.$this->getContainer()->getParameter('fa.dotmailer.api.version').'/';

        // build url by appending resource to it.
        $url = $url.$this->getDotMailerResource();

        $username = $this->getContainer()->getParameter('fa.dotmailer.api.username');
        $password = $this->getContainer()->getParameter('fa.dotmailer.api.password');

        if ($this->getHttpMethod() == 'GET') {
            $url = $url.'/'.$this->dataToSubmit;
        }

        // Build the HTTP Request Headers
        $ch = curl_init($url);

        if ($this->getHttpMethod() == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->dataToSubmit);
        }

        //curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HEADER, true);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        if ($this->getCustomRequest()) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->getCustomRequest());
        }

        $this->response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->response_body = substr($this->response, $header_size);

        //var_dump($this->response);

        $this->httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
    }
}
