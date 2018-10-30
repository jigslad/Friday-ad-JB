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
use Buzz\Browser;
use Buzz\Client\Curl;
use Fa\Bundle\CoreBundle\lib\Carweb\Consumer;
use Fa\Bundle\CoreBundle\lib\Carweb\Cache\DBCache;

/**
 * Fa\Bundle\CoreBundle\Manager\CarwebManager
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class CarwebManager
{
    private $consumer;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->init();
    }

    /**
     * google api initialization
     *
     * @param string $redirectRoute Google redirect route name
     * @param array  $routeParams   Parameters of route
     *
     * @return boolean
     */
    public function init()
    {
        $strUserName = $this->container->getParameter('fa.carweb.username'); //'Spidersnet';
        $strPassword = $this->container->getParameter('fa.carweb.password'); //'854629';
        $strKey1     = $this->container->getParameter('fa.carweb.key'); //'h391bv87z';
        $clientRef   = $this->container->getParameter('fa.carweb.client_ref'); //'fiarefad';
        $clientDesc  = $this->container->getParameter('fa.carweb.client_desc'); //'fiarefad';

        $client  = new Browser(new Curl());
        $em = $this->container->get('doctrine')->getManager();
        $this->consumer = new Consumer($client, $strUserName, $strPassword, $strKey1, new DBCache($em), $clientRef, $clientDesc);
    }

    /**
     * get client
     *
     * @return Fa\Bundle\CoreBundle\lib\Carweb\Consumer
     */
    public function getClient()
    {
        return $this->consumer;
    }

    /**
     * Proxy method for strB2BGetVehicleByVRM
     *
     * @param string $vin
     * @return mixed|void
     */
    public function findByVIN($vin)
    {
        return $this->consumer->findByVIN($vin);
    }

    /**
     * Proxy method for strB2BGetVehicleByVRM
     *
     * @param string $vrm
     * @return mixed|void
     */
    public function findByVRM($vrm)
    {
        return $this->consumer->findByVRM($vrm);
    }
}
