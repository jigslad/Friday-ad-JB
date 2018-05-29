<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Manager;

use Fa\Bundle\AdBundle\Entity\Ad;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This controller is used for ad feed callback.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdFeedCallbackManager
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Moderation request array.
     *
     * @var array
     */
    private $callbackParams = array();


    /**
     * Constructor.
     *
     * @param object $container Container instance.
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Initialize moderation request build.
     *
     * @param object $feedAd Ad feed object.
     *
     * @return array
     */
    public function init($feedAd)
    {
        $this->callbackParams = array();
        $feedAdDetails = unserialize($feedAd->getAdText());
        $feedData = (isset($feedAdDetails['full_data']) ? unserialize($feedAdDetails['full_data']) : array());

        if (isset($feedData['JobId'])) {
            $this->callbackParams['JobId'] = $feedData['JobId'];
        }

        if (isset($feedData['OriginatorsReference'])) {
            $this->callbackParams['OriginatorsReference'] = $feedData['OriginatorsReference'];
        }

        if (isset($feedData['Id'])) {
            $this->callbackParams['AdvertId'] = $feedData['Id'];
        }

        if (isset($feedData['SiteVisibility']) && isset($feedData['SiteVisibility'][0]['SiteId'])) {
            //$this->callbackParams['SourceSiteId'] = $feedData['SiteVisibility'][0]['SiteId'];
            $this->callbackParams['SourceSiteId'] = 10;
        }

        $this->callbackParams['ImportTimestamp'] = time();

        if ($feedAd->getStatus() == 'R' && $feedAd->getRemark()) {
            $this->callbackParams['ErrorMessage'] = $feedAd->getRemark();
            $this->callbackParams['AdStatus'] = 3000;
        }

        if ($feedAd->getStatus() == 'A') {
            $adObj = $feedAd->getAd();

            if ($adObj && $adObj->getAffiliate()) {
                $this->callbackParams['TrackbackUrl'] = $this->container->get('router')->generate('ad_detail_page_by_id', array('id' => $adObj->getId()), true);
            } else {
                if ($adObj) {
                    $this->callbackParams['TrackbackUrl'] = $this->container->get('fa_ad.manager.ad_routing')->getDetailUrl($adObj);
                } else {
                    $this->callbackParams['TrackbackUrl'] = $feedAdDetails['track_back_url'];
                }
            }
            $this->callbackParams['AdStatus'] = 1000;
        }

        if ($feedAd->getStatus() == 'E') {
            $this->callbackParams['AdStatus'] = 1000;
            $this->callbackParams['ErrorMessage'] = 'Advert Expired';
            $this->callbackParams['TrackbackUrl'] = null;
        }

        return $this->callbackParams;
    }
    /**
     * Send request to ad moderation url.
     *
     * @param string $requestBody Request body.
     *
     * @return boolean
     */
    public function sendRequest()
    {
        $mode = $this->container->getParameter('fa.feed.mode');
        $mainUrl = $this->container->getParameter('fa.feed.'.$mode.'.url');
        $url = $mainUrl.'/callback/reportAdvert?appkey='.$this->container->getParameter('fa.feed.api.id');

        //print_r($this->callbackParams);
        // Build the HTTP Request Headers
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->callbackParams));
        //curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        //curl_setopt($ch, CURLOPT_HEADER, true);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        echo $httpcode."\n";
    }
}
