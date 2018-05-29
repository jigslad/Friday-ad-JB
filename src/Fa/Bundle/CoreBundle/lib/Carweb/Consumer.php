<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\lib\Carweb;

use Buzz\Message\RequestInterface;
use Buzz\Message\Response;
use Fa\Bundle\CoreBundle\lib\Carweb\Cache\CacheInterface;
use Fa\Bundle\CoreBundle\lib\Carweb\Converter\ConverterInterface;
use Fa\Bundle\CoreBundle\lib\Carweb\Converter\DefaultConverter;
use Fa\Bundle\CoreBundle\lib\Carweb\Exception\ApiException;
use Fa\Bundle\CoreBundle\lib\Carweb\Exception\ValidationException;
use Fa\Bundle\CoreBundle\lib\Carweb\Validator\VRM;

/**
 * Fa\Bundle\CoreBundle\lib\Carweb
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class Consumer
{
    /**
     * Carweb API version, this library is build against
     */
    const API_VERSION = '0.31.5';
    /**
     * API path on the endpoint
     */
    const API_PATH = 'CarweBVrrB2Bproxy/carwebVrrWebService.asmx';
    /**
     * @var array
     * http://www2.carwebuk.com: Removed temporary
     * http://www.cwsecondary.net: Stopped
     */
    protected $api_endpoints = array(
        'http://www1.carwebuk.com',
        'http://www3.carwebuk.com'
    );
    /**
     * @var \Buzz\Browser
     */
    protected $client;
    /**
     * @var string
     */
    protected $strUserName;
    /**
     * @var string
     */
    protected $strPassword;
    /**
     * @var string
     */
    protected $strKey1;
    /**
     * @var null|\Carweb\Cache\CacheInterface
     */
    private $cache;
    /**
     * @var bool
     */
    private $validate = true;
    /**
     * @var array
     */
    protected $converters = array();
    /**
     * Constructor
     *
     * @param $client
     * @param $strUserName
     * @param $strPassword
     * @param $strKey1
     * @param null|\Carweb\Cache\CacheInterface $cache
     */
    public function __construct($client, $strUserName, $strPassword, $strKey1, CacheInterface $cache = null, $validate = true, $strClientRef = "default client", $strClientDescription = "default description")
    {
        $this->client               = $client;
        $this->strUserName          = $strUserName;
        $this->strPassword          = $strPassword;
        $this->strKey1              = $strKey1;
        $this->cache                = $cache;
        $this->validate             = $validate;
        $this->strClientRef         = $strClientRef;
        $this->strClientDescription = $strClientDescription;
    }

    /**
     * Proxy method for strB2BGetVehicleByVRM
     *
     * @param string $vrm
     * @return mixed|void
     */
    public function findByVRM($vrm)
    {
        $vrm = strtoupper(preg_replace('/\s+/', '', $vrm));
        $validator = new VRM();

        if (!$validator->isValid($vrm) && $this->validate) {
            return array('error' => 'Invalid UK VRM');
        }

        $api_method = 'strB2BGetVehicleByVRM';
        $cache_key = sprintf('%s.%s', $api_method, $vrm);
        $converter = $this->getConverter($api_method);

        if ($this->isCached($cache_key)) {
            $content = $this->getCached($cache_key);
            return $converter->convert($content);
        }
        $input = array(
            'strUserName' => $this->strUserName,
            'strPassword' => $this->strPassword,
            'strKey1' => $this->strKey1,
            'strVersion' => self::API_VERSION,
            'strVRM' => $vrm,
            'strClientRef' => $this->strClientRef,
            'strClientDescription' => $this->strClientDescription
        );
        $content = $this->call($api_method, RequestInterface::METHOD_GET, $input);
        $this->setCached($cache_key, $content);
        return $converter->convert($content);
    }

    /**
     * Proxy method for strB2BGetVehicleByVRM
     *
     * @param string $vin
     * @return mixed|void
     */
    public function findByVIN($vin)
    {
        $vin = strtoupper(preg_replace('/\s+/', '', $vin));
        $api_method = 'strB2BGetVehicleByVIN';
        $cache_key = sprintf('%s.%s', $api_method, $vin);
        $converter = $this->getConverter($api_method);

        if ($this->isCached($cache_key)) {
            $content = $this->getCached($cache_key);
            return $converter->convert($content);
        }

        $input = array(
            'strUserName' => $this->strUserName,
            'strPassword' => $this->strPassword,
            'strKey1' => $this->strKey1,
            'strVersion' => self::API_VERSION,
            'strVIN' => $vin,
            'strClientRef' => $this->strClientRef,
            'strClientDescription' => $this->strClientDescription
        );
        $content = $this->call($api_method, RequestInterface::METHOD_GET, $input);
        $this->setCached($cache_key, $content);
        return $converter->convert($content);
    }

    /**
     * call api method
     *
     * @param string $api_method
     * @param string $http_method
     * @param array $query_string
     * @param string $headers
     *
     * @param string $content
     * @return string
     */
    public function call($api_method, $http_method = RequestInterface::METHOD_GET, array $query_string = array(), $headers = array(), $content = '')
    {
        $url = sprintf('%s/%s/%s?%s', $this->api_endpoints[array_rand($this->api_endpoints)], self::API_PATH, $api_method, http_build_query($query_string));
        $response = $this->client->call($url, $http_method, $headers, $content);

        if ($response->isSuccessful()) {
            $this->hasErrors($response->getContent());
            return $response->getContent();
        } else {
            return $this->handleException($response);
        }
    }
    /**
     * Gets converted obj for given API method
     *
     * @param $api_method
     * @return \Carweb\ConverterInterface
     */
    public function getConverter($api_method)
    {
        if (isset($this->converters[$api_method])) {
            return $this->converters[$api_method];
        } else {
            return new DefaultConverter();
        }
    }
    /**
     * Sets converter object for given API method
     *
     * @param $api_method
     * @param ConverterInterface $converter
     * @throws \InvalidArgumentException
     */
    public function setConverter($api_method, $converter)
    {
        if (! $converter instanceof ConverterInterface) {
            throw new \InvalidArgumentException('$converter must be instance of ConverterInterface');
        }

        $this->converters[$api_method] = $converter;
    }
    /**
     * @param Response $response
     * @throws \Exception
     */
    protected function handleException(Response $response)
    {
        throw new ApiException($response->getContent(), $response->getStatusCode());
    }
    protected function hasErrors($xml_string)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml_string);
        $xpath = new \DOMXPath($doc);
        $query = '/VRRError/DataArea/Error/Details';
        $entries = $xpath->query($query);
        if ($entries->length) {
            $error = array();
            foreach ($entries as $entry) {
                foreach ($entry->childNodes as $node) {
                    if ($node->nodeName != '#text') {
                        $error[$node->nodeName] = $node->nodeValue;
                    }
                }
            }

            return array('error' => array($error['ErrorCode'] => $error['ErrorDescription']));
        }
        return false;
    }

    /**
     * Cache proxy
     *
     * @param $key
     * @return bool
     */
    protected function isCached($key)
    {
        if ($this->cache) {
            return $this->cache->has($key);
        } else {
            return false;
        }
    }
    /**
     * Cache proxy
     *
     * @param $key
     * @return mixed
     */
    protected function getCached($key)
    {
        return $this->cache ? $this->cache->get($key) : null;
    }
    /**
     * Cache proxy
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function setCached($key, $value)
    {
        if ($this->cache) {
            return $this->cache->save($key, $value);
        } else {
            return false;
        }
    }
}
