<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fa\Bundle\AdBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * This controller is used for report ad management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdReportController extends CoreController {
	/**
	 * Add ad to favorite.
	 *
	 * @param integer $adId
	 *        	Ad id.
	 * @param Request $request
	 *        	Request object.
	 *        	
	 * @return Response|JsonResponse A Response or JsonResponse object.
	 */
	public function ajaxAdReportAction($adId, Request $request) {
		$error = '';
		$redirectToUrl = '';
		$reportedAds = array ();
		$setCookie = false;
		$objCookie = $request->cookies;
		$objResponse = new Response ();
		$userId = null;
		
		if ($request->isXmlHttpRequest ()) {
			$ad = $this->getRepository ( 'FaAdBundle:Ad' )->find ( $adId );
			// check for ad
			if (! $ad || ($ad && $ad->getStatus ()->getId () != EntityRepository::AD_STATUS_LIVE_ID)) {
				$error = $this->get ( 'translator' )->trans ( 'Unable to find Live Ad.', array (), 'frontend-show-ad' );
			} else {
				if ($this->isAuth ()) {
					$userId = $this->getLoggedInUser ()->getId ();

					if ($objCookie->has ( 'reported_ads' )) {
						$reportedAds = unserialize ( $objCookie->get ( 'reported_ads' ) );
						if ($reportedAds && count ( $reportedAds ) > 0 && in_array ( $adId, $reportedAds )) {
							$error = $this->get ( 'translator' )->trans ( 'You already have reported this ad.', array (), 'frontend-show-ad' );
						}
					} 
					
					if ($ad->getUser () && $userId == $ad->getUser ()->getId ()) {
						$error = $this->get ( 'translator' )->trans ( 'You can not report your own ad.', array (), 'frontend-show-ad' );
					} else {
						$setCookie = true;
					}
				} else {
					if ($objCookie->has ( 'reported_ads' )) {
						$reportedAds = unserialize ( $objCookie->get ( 'reported_ads' ) );
						if ($reportedAds && count ( $reportedAds ) > 0 && in_array ( $adId, $reportedAds )) {
							$error = $this->get ( 'translator' )->trans ( 'You already have reported this ad.', array (), 'frontend-show-ad' );
						} else {
							$setCookie = true;
						}
					} else {
						$setCookie = true;
					}
				}
				
				if ($setCookie) {
					$reportedAds [] = $adId;
					$objResponse->headers->setCookie ( new Cookie ( 'reported_ads', serialize ( $reportedAds ), time () + 3600 * 24 * 7 ) );
					$objResponse->sendHeaders ();
					$this->getRepository ( 'FaAdBundle:AdReport' )->addAdReport ( $ad, $userId, $request->getClientIp (), $this->container );
				}
			}
			return new JsonResponse ( array (
					'error' => $error,
					'redirectToUrl' => $redirectToUrl 
			) );
		} else {
			return new Response ();
		}
	}
}
