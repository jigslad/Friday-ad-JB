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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ad moderation manager.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdModerationManager
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
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Handle moderation request.
     *
     * @param array $moderationRequest Moderation request.
     *
     * @return boolean
     */
    public function handleRequest($moderationRequest)
    {
        $url = $this->container->getParameter('fa.ad.moderation.api.url').'/'.$this->container->getParameter('fa.ad.moderation.api.version').'/appKey/'.$this->container->getParameter('fa.ad.moderation.api.version').'/moderation-request';

        // Build the HTTP Request Headers
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($moderationRequest));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);

        $response = json_decode(curl_exec($ch), true);

        return true;
    }
}
