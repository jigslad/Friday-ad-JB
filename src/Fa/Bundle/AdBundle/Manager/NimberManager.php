<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * This manager is used to get nimber delivery.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class NimberManager
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Nimber post url.
     *
     * @var string
     */
    private $url;

    /**
     * Nimber token.
     *
     * @var string
     */
    private $token;

    /**
     * Constructor.
     *
     * @param object $container Container identifier.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $mode         = $this->container->getParameter('fa.nimber.mode');
        $nimberParams = $this->container->getParameter('fa.nimber.'.$mode);
        $this->url    = $nimberParams['url'];
        $this->token  = $nimberParams['token'];
    }

    /**
     * Get price suggestion.
     *
     * @param string  $from Pickup location
     * @param string  $to   Delivery location
     * @param integer $size Size
     *
     * @return array
     */
    public function getPriceSuggestion($from, $to, $size)
    {
        $nimberParams = array();
        $nimberParams['country_code'] = 'GB';
        $nimberParams['currency'] = 'GBP';
        $nimberParams['from'] = $from;
        $nimberParams['to'] = $to;
        $nimberParams['size'] = $size;

        // Build the HTTP Request Headers
        $curlUrl = $this->url.'/external/partners/tasks/price_suggestions?'.http_build_query($nimberParams);
        $ch = curl_init($curlUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        $userAgent = $this->container->get('request_stack')->getCurrentRequest()->server->get('HTTP_USER_AGENT');
        if (!$userAgent) {
            $userAgent = 'curl/7.19.7 (x86_64-redhat-linux-gnu) libcurl/7.19.7 NSS/3.16.2.3 Basic ECC zlib/1.2.3 libidn/1.18 libssh2/1.4.2';
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array (
                'X-Partner-Token: '.$this->token,
                'Content-Type: application/json',
            )
        );

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }

    /**
     * Get price suggestion.
     *
     * @param string  $from Pickup location
     * @param string  $to   Delivery location
     * @param integer $size Size
     *
     * @return array
     */
    public function createTask($nimberPostParams)
    {
        $nimberPostParams['country_code'] = 'GB';
        $nimberPostParams['currency'] = 'GBP';

        // Build the HTTP Request Headers
        $curlUrl = $this->url.'/external/partners/tasks';
        $ch = curl_init($curlUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($nimberPostParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        $userAgent = $this->container->get('request_stack')->getCurrentRequest()->server->get('HTTP_USER_AGENT');
        if (!$userAgent) {
            $userAgent = 'curl/7.19.7 (x86_64-redhat-linux-gnu) libcurl/7.19.7 NSS/3.16.2.3 Basic ECC zlib/1.2.3 libidn/1.18 libssh2/1.4.2';
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array (
                'X-Partner-Token: '.$this->token,
                'Content-Type: application/json',
            )
        );

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }
}
