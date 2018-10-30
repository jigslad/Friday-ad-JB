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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * This controller is used for favourite ad management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdFavoriteController extends CoreController
{
    /**
     * Add ad to favorite.
     *
     * @param integer $adId    Ad id.
     * @param Request $request Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxAddToFavoriteAction($adId, Request $request)
    {
        $error         = '';
        $anchorHtml    = '';
        $redirectToUrl = '';
        $type          = $request->get('type', 'list');

        if ($request->isXmlHttpRequest()) {
            $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
            //check for ad.
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                $error = $this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-search-result');
            } else {
                if ($this->isAuth()) {
                    $userId = $this->getLoggedInUser()->getId();
                } else {
                    $userId = $this->get('session')->getId();
                }

                if ($ad->getUser() && $userId == $ad->getUser()->getId()) {
                    $error = $this->get('translator')->trans('You can not add your own ad to favourite.', array(), 'frontend-search-result');
                } elseif ((!$ad->getUser() && $this->getRepository('FaAdBundle:AdFavorite')->addAdToFavorite($ad, $userId)) || ($userId != $ad->getUser()->getId() && $this->getRepository('FaAdBundle:AdFavorite')->addAdToFavorite($ad, $userId))) {
                    if ($type == 'list') {
                        $anchorHtml = '<a href="javascript:void(0)" onclick="return removeFromFavorite('.$ad->getId().');" class="saved-item outside-tricky">FA</a>';
                    } elseif ($type == 'detail') {
                        $anchorHtml = '<a href="javascript:void(0);" onclick="return removeFromFavorite('.$ad->getId().');" class="saved-item-btn added-in-fav"><span class="saved-icon">save</span>'.$this->get('translator')->trans('Favourite', array(), 'frontend-show-ad').'</a>';
                    }
                } else {
                    $error = $this->get('translator')->trans('Problem in adding ad to favourite.', array(), 'frontend-search-result');
                }
            }
            //if not logged in then set redirect url.
            if (!$this->isAuth()) {
                //set new cookies for add ad to fav.
                $response = new Response();
                //remove all cookies.
                $this->getRepository('FaUserBundle:User')->removeUserCookies($response);
                $response->headers->setCookie(new Cookie('frontend_redirect_after_login_path_info', $request->get('redirectUrl'), time() + 3600 * 24 * 7));
                $response->headers->setCookie(new Cookie('add_to_fav_session_id', $userId, time() + 3600 * 24 * 7));
                $response->headers->setCookie(new Cookie('save_add_to_fav_flag', true, time() + 3600 * 24 * 7));
                $response->sendHeaders();

                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($this->get('translator')->trans('Please login to add ad to favourite.', array(), 'frontend-search-result'), 'success');
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'anchorHtml' => $anchorHtml, 'redirectToUrl' => $redirectToUrl));
        } else {
            return new Response();
        }
    }

    /**
     * Remove ad from favorite.
     *
     * @param integer $adId    Ad id.
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxRemoveFromFavoriteAction($adId, Request $request)
    {
        $error      = '';
        $anchorHtml = '';
        $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);
        $type       = $request->get('type', 'list');

        if ($this->isAuth()) {
            $userId = $this->getLoggedInUser()->getId();
        } else {
            $userId = $this->get('session')->getId();
        }

        if ($this->getRepository('FaAdBundle:AdFavorite')->removeFromFavorite($ad, $userId)) {
            if ($type == 'list') {
                $anchorHtml = '<a href="javascript:void(0)" onclick="return addToFavorite('.$ad->getId().');" class="unsaved-item outside-tricky">FA</a>';
            } elseif ($type == 'detail') {
                $anchorHtml = '<a href="javascript:void(0);" onclick="return addToFavorite('.$ad->getId().');" class="save-item-btn"><span class="save-icon">save</span>'.$this->get('translator')->trans('Favourite', array(), 'frontend-show-ad').'</a>';
            }
        } else {
            $error = $this->get('translator')->trans('Problem in removing ad from favourite.', array(), 'frontend-search-result');
        }

        return new JsonResponse(array('error' => $error, 'anchorHtml' => $anchorHtml));
    }
}
