<?php

/**
 * This file is part of the fa bundle.
 *soldAd
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Form\ManageMyAdSearchType;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for user ads.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ManageMyAdController extends CoreController
{
    /**
     * Show user ads.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $activeAdCount   = 0;
        $inActiveAdCount = 0;
        $type            = $request->get('type', 'active');

        $activeAdCountArray   = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), 'active', true)->getResult();
        $inActiveAdCountArray = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), 'inactive', true)->getResult();
        $query                = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), $type);

        if (is_array($activeAdCountArray)) {
            $activeAdCount = $activeAdCountArray[0]['total_ads'];
        }

        if (is_array($inActiveAdCountArray)) {
            $inActiveAdCount = $inActiveAdCountArray[0]['total_ads'];
        }

        $totalAdCount = $activeAdCount + $inActiveAdCount;

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = $request->get('page', 1);
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();
        $moderationToolTipText = EntityRepository::inModerationTooltipMsg();
        
        $parameters = array(
            'totalAdCount'    => $totalAdCount,
            'activeAdCount'   => $activeAdCount,
            'inActiveAdCount' => $inActiveAdCount,
            'pagination'      => $pagination,
            'modToolTipText'  => $moderationToolTipText
        );

        $showCompetitionPopup = false;

        if ($request->get('transaction_id')) {
            $this->get('session')->getFlashBag()->get('error');
            $transcations                   = $this->getRepository('FaPaymentBundle:Payment')->getTranscationDetailsForGA($request->get('transaction_id'), $loggedinUser);
            /*$competitionObj = $this->getRepository('FaUserBundle:Competition')->findOneBy(array('user' => $loggedinUser->getId()));
            if (!$competitionObj) {
                $showCompetitionPopup = true;
            }*/
            $parameters['getTranscationJs'] = CommonManager::getGaTranscationJs($transcations);
            $parameters['getItemJs']        = CommonManager::getGaItemJs($transcations);
            $parameters['ga_transaction']   = $transcations;
        }

        $parameters['showCompetitionPopup'] = $showCompetitionPopup;
        $objResponse = CommonManager::setCacheControlHeaders();

        return $this->render('FaUserBundle:ManageMyAd:index.html.twig', $parameters, $objResponse);
    }

    /**
     * refresh ad action
     *
     * @param Request $request
     */
    public function refreshAdAction($adId, $date, Request $request)
    {
        $date = CommonManager::encryptDecrypt('R', $date, 'decript');
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
        $this->checkIsValidAdUser($ad->getUser()->getId());
        if ($ad && ($ad->getStatus()->getId() == EntityRepository::AD_STATUS_LIVE_ID) && $loggedinUser->getId() == $ad->getUser()->getId()) {
            $datetime1 = new \DateTime(date('Y-m-d', $date));
            $datetime2 = new \DateTime(date('Y-m-d'));
            $interval = $datetime1->diff($datetime2);
            $d = $interval->format('%R%a days');
            $d  = intval($d);

            $d2 = null;
            if ($ad->getWeeklyRefreshAt() > 0) {
                $datetime1 = new \DateTime(date('Y-m-d', time()));
                $datetime2 = new \DateTime(date('Y-m-d', $ad->getWeeklyRefreshAt()));
                $interval = $datetime1->diff($datetime2);
                $d2 = $interval->format('%R%a days');
                $d2  = intval($d2);
            }

            if (($d >= 0 && $d <=3) && ($d2 === null || $d2 > 3)) {
                $ad->setWeeklyRefreshAt(time());
                $ad->setManualRefresh($ad->getManualRefresh() + 1);
                $this->getEntityManager()->persist($ad);
                $this->getEntityManager()->flush($ad);
                if ($ad->getManualRefresh() == 1) {
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_7_days', $ad->getId());
                } elseif ($ad->getManualRefresh() == 2) {
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_14_days', $ad->getId());
                } elseif ($ad->getManualRefresh() > 2) {
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_21_days', $ad->getId());
                }

                return $this->handleMessage($this->get('translator')->trans('Your advert %advert_title% has been refreshed.', array('%advert_title%' => $ad->getTitle()), 'frontend-ad-edit'), 'manage_my_ads_active', array(), 'success');
            } else {
                return $this->handleMessage($this->get('translator')->trans('You can not refresh this ad now.', array(), 'frontend-ad-edit'), 'manage_my_ads_active', array(), 'error');
            }

        } else {
            return $this->handleMessage($this->get('translator')->trans('You can not refresh this ad.', array(), 'frontend-ad-edit'), 'manage_my_ads_active', array(), 'error');
        }

    }

    /**
     * refresh ad action
     *
     * @param Request $request
     */
    public function soldAdAction($adId, Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

        if ($ad && ($ad->getStatus()->getId() == EntityRepository::AD_STATUS_LIVE_ID || $ad->getStatus()->getId() == EntityRepository::AD_STATUS_EXPIRED_ID) && $loggedinUser->getId() == $ad->getUser()->getId()) {
            $redirectResponse = $this->checkIsValidAdUser($ad->getUser()->getId());
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $this->getRepository('FaAdBundle:Ad')->changeAdStatus($adId, EntityRepository::AD_STATUS_SOLD_ID, $this->container);
            $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_7_days', $ad->getId());
            $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_14_days', $ad->getId());
            $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_live_for_21_days', $ad->getId());
            return $this->handleMessage($this->get('translator')->trans('Your advert %advert_title% has been updated.', array('%advert_title%' => $ad->getTitle()), 'frontend-ad-edit'), 'manage_my_ads_active', array(), 'success');
        } else {
            return $this->handleMessage($this->get('translator')->trans('You can not update this ad.', array(), 'frontend-ad-edit'), 'manage_my_ads_inactive', array(), 'error');
        }

    }

    /**
     * Change ad status.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxChangeAdStatusAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) === true && $request->isXmlHttpRequest()) {
            $error        = '';
            $successMsg   = '';
            $adId         = $request->get('adId', 0);
            $newStatusId  = (string) $request->get('newStatusId', 0);
            $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
            $this->getEntityManager()->refresh($ad);
            $oldStatusId  = (($ad && $ad->getStatus()) ? (string) $ad->getStatus()->getId() : null);
            $this->checkIsValidAdUser($ad->getUser()->getId());

            $loggedinUser = $this->getLoggedInUser();
            $invalidNewStatus = false;

            switch($oldStatusId) {
                case EntityRepository::AD_STATUS_LIVE_ID:
                    if (!in_array($newStatusId, array(EntityRepository::AD_STATUS_SOLD_ID, EntityRepository::AD_STATUS_EXPIRED_ID))) {
                        $invalidNewStatus = true;
                    }
                    break;
                case EntityRepository::AD_STATUS_IN_MODERATION_ID:
                    $invalidNewStatus = true;
                    break;
                case EntityRepository::AD_STATUS_SOLD_ID:
                case EntityRepository::AD_STATUS_EXPIRED_ID:
                case EntityRepository::AD_STATUS_DRAFT_ID:
                case EntityRepository::AD_STATUS_REJECTED_ID:
                case EntityRepository::AD_STATUS_REJECTEDWITHREASON_ID:
                    if (!in_array($newStatusId, array(EntityRepository::AD_STATUS_INACTIVE_ID))) {
                        $invalidNewStatus = true;
                    }
                    break;
            }

            if ($invalidNewStatus === true) {
                $error          = $this->get('translator')->trans('Invalid status supplied.', array(), 'frontend-manage-my-ad');
                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($error, 'error');
            } elseif($invalidNewStatus === false) {
                //update ad status to sold
                $ans = $this->getRepository('FaAdBundle:Ad')->changeAdStatus($adId, $newStatusId, $this->container);

                if ($ans) {
                    $successMsg     = $this->get('translator')->trans('Ad was removed successfully.', array(), 'frontend-manage-my-ad');
                    $messageManager = $this->get('fa.message.manager');
                    $messageManager->setFlashMessage($successMsg, 'success');
                } else {
                    $error          = $this->get('translator')->trans('There was a problem in removing ad.', array(), 'frontend-manage-my-ad');
                    $messageManager = $this->get('fa.message.manager');
                    $messageManager->setFlashMessage($error, 'error');
                }
            }
            sleep(2);
            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        }

        return new Response();
    }

    /**
     * Social share.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function socialShareAction(Request $request)
    {
        $error = '';
        $htmlContent = '';

        if ($this->checkIsValidLoggedInUser($request) === true && $request->isXmlHttpRequest()) {
            $adId         = $request->get('adId');
            $adUrl        = $request->get('adUrl');
            $adTitle      = $request->get('adTitle');
            $loggedinUser = $this->getLoggedInUser();
            $htmlContent  = $this->renderView('FaUserBundle:ManageMyAd:socialShare.html.twig', array('adId' => $adId, 'adUrl' => $adUrl, 'adTitle' => $adTitle));

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
        }

        return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
    }

    /**
     * Get ad status action
     *
     * @param Request $request
     */
    public function ajaxGetStatusAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) === true && $request->isXmlHttpRequest()) {
            $loggedinUser = $this->getLoggedInUser();
            $adIds         = $request->get('adIds', 0);
            $adStatusArray = array();
            $liveAdStatusArray = array();

            if ($adIds) {
                $adIds           = explode(',', $adIds);
                $adStatusIdArray = $this->getRepository('FaAdBundle:Ad')->getStatusIdArrayByAdId($adIds);
                foreach ($adStatusIdArray as $key => $value) {
                    if ($value == EntityRepository::AD_STATUS_LIVE_ID) {
                        $liveAdStatusArray[] = $key;
                    }
                }

                if (count($liveAdStatusArray)) {
                    $type                 = $request->get('type');
                    $query                = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), $type, false, $liveAdStatusArray);
                    $liveAds              = $query->getResult();
                    $adRepository         = $this->getRepository('FaAdBundle:Ad');
                    $adLocationRepository = $this->getRepository('FaAdBundle:AdLocation');
                    $adImageRepository = $this->getRepository('FaAdBundle:AdImage');
                    $adViewCounterRepository = $this->getRepository('FaAdBundle:AdViewCounter');
                    $adUserPackageRepository = $this->getRepository('FaAdBundle:AdUserPackage');
                    $adModerateRepository = $this->getRepository('FaAdBundle:AdModerate');

                    $adCategoryIdArray    = $adRepository->getAdCategoryIdArrayByAdId($liveAdStatusArray);
                    $adImageArray         = $adImageRepository->getAdMainImageArrayByAdId($liveAdStatusArray);
                    $adViewCounterArray   = $adViewCounterRepository->getAdViewCounterArrayByAdId($liveAdStatusArray);
                    $adPackageArray       = $adUserPackageRepository->getAdPackageArrayByAdId($liveAdStatusArray, true);
                    $adModerateArray      = $adModerateRepository->findResultsByAdIdsAndModerationResult($liveAdStatusArray, 'rejected');
                    $inModerationLiveAdIds = $adModerateRepository->getInModerationStatusForLiveAdIds($liveAdStatusArray);

                    foreach ($liveAds as $liveAd) {
                        $htmlContent = $this->renderView('FaUserBundle:ManageMyAd:ajaxGetStatus.html.twig', array('adId' => $liveAd['id'], 'ad' => $liveAd, 'adCategoryIdArray' => $adCategoryIdArray, 'adImageArray' => $adImageArray, 'adViewCounterArray' => $adViewCounterArray, 'adPackageArray' => $adPackageArray, 'adModerateArray' => $adModerateArray, 'inModerationLiveAdIds' => $inModerationLiveAdIds));
                        $adStatusArray[$liveAd['id']] = $htmlContent;
                    }
                }

                return new JsonResponse($adStatusArray);
            }
        }

        return new Response();
    }
    
}
