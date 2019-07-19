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
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Entity\BoostedAd;
use Fa\Bundle\EntityBundle\Entity\LocationGroupLocation;
use Fa\Bundle\PaymentBundle\Form\CyberSourceCheckoutType;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;

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
        $boostedAdCount  = $adsBoostedCount  =  0;
        $type            = $request->get('type', 'active');
        $sortBy            = $request->get('sortBy', 'ad_date');

        
        $onlyActiveAdCount = 0;
        $adLimitCount = 0;
        $activeAdIdarr = $activeAdsarr = array();$activeAdIds = '';

        $activeAdCountArray   = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), 'active', $sortBy, true)->getResult();
        $inActiveAdCountArray = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), 'inactive',  $sortBy, true)->getResult();
        $onlyActiveAdCountArray   = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), 'onlyactive',  $sortBy, true)->getResult();
        $query                = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), $type, $sortBy);
        
        $activeAdsarr   = $this->getRepository('FaAdBundle:Ad')->getMyAdIdsQuery($loggedinUser->getId())->getResult();
        if(!empty($activeAdsarr)) {
            $activeAdIdarr = array_column($activeAdsarr, 'id');
            $activeAdIds  =  implode(',',$activeAdIdarr);
        }
        
        $currentActivePackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($loggedinUser);
        if($currentActivePackage && $currentActivePackage->getPackage())  {
            $adLimitCount = $currentActivePackage->getPackage()->getAdLimit();
        }        
        
        if (is_array($activeAdCountArray)) {
            $activeAdCount = $activeAdCountArray[0]['total_ads'];
        }

        if (is_array($inActiveAdCountArray)) {
            $inActiveAdCount = $inActiveAdCountArray[0]['total_ads'];
        }
        
        if(is_array($onlyActiveAdCountArray)) {
            $onlyActiveAdCount = $onlyActiveAdCountArray[0]['total_ads'];
        }
        $getBoostDetails = $this->getBoostDetails($loggedinUser);

        $adsBoostedCount = $getBoostDetails['adsBoostedCount'];
        $boostedAdCount = $getBoostDetails['boostedAdCount'];

        $totalAdCount = $activeAdCount + $inActiveAdCount + $adsBoostedCount;

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = $request->get('page', 1);
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();
        $moderationToolTipText = EntityRepository::inModerationTooltipMsg();
        
        $onlyActiveAdInPageCount = 0; 
        if ($pagination->getNbResults()) {
            foreach ($pagination->getCurrentPageResults() as $ad) {                
                if ($ad['status_id'] == EntityRepository::AD_STATUS_LIVE_ID) {
                    $onlyActiveAdInPageCount = $onlyActiveAdInPageCount + 1;
                }                
            }
        }
 
        $userRole     = $this->getRepository('FaUserBundle:User')->getUserRole($loggedinUser->getId(), $this->container);
        if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            $activeShopPackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($loggedinUser);
        }
        
        $parameters = array(
            'totalAdCount'      => $totalAdCount,
            'activeAdCount'     => $activeAdCount,
            'inActiveAdCount'   => $inActiveAdCount,
            'onlyActiveAdInPageCount' => $onlyActiveAdInPageCount,
            'activeAdIds'       => $activeAdIds,
            'adsBoostedCount'   => $adsBoostedCount,
            'onlyActiveAdCount' => $onlyActiveAdCount,
            'adLimitCount'      => $adLimitCount,
            'pagination'        => $pagination,
            'modToolTipText'    => $moderationToolTipText,
            'boostedAdCount'    => $boostedAdCount,
            'isBoostEnabled'    => $getBoostDetails['isBoostEnabled'],
            'boostMaxPerMonth'  => $getBoostDetails['boostMaxPerMonth'],
            'boostAdRemaining'  => $getBoostDetails['boostAdRemaining'],
            'boostRenewDate'    => $getBoostDetails['boostRenewDate'],
            'userBusinessCategory' => $getBoostDetails['userBusinessCategory'],
            'activeShopPackage' => $activeShopPackage,
        );

        $showCompetitionPopup = false;

        if ($request->get('transaction_id')) {
            $this->get('session')->getFlashBag()->get('error');
            $transcations                   = $this->getRepository('FaPaymentBundle:Payment')->getTranscationDetailsForGA($request->get('transaction_id'), $loggedinUser);

            if ($this->container->get('session')->has('paa_lite_card_code')) {
                $this->container->get('session')->remove('paa_lite_card_code');
            }
            $parameters['getTranscationJs'] = CommonManager::getGaTranscationJs($transcations);
            $parameters['getItemJs']        = CommonManager::getGaItemJs($transcations);
            $parameters['dimension12']      = $this->getDimension12($transcations);
            $parameters['ga_transaction']   = $transcations;
        }

        $parameters['showCompetitionPopup'] = $showCompetitionPopup;
        $objResponse = CommonManager::setCacheControlHeaders();

        return $this->render('FaUserBundle:ManageMyAd:index.html.twig', $parameters, $objResponse);
    }

    public function getBoostDetails($loggedinUser)
    {
        $isBoostEnabled = 0;
        $boostMaxPerMonth = 0;
        $boostAdRemaining = 0;
        $getExipryDate = $boostRenewDate = '';
        $boostedAdCountArray = array();

        $boostedAdCountArray  = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), 'boosted', 'ad_date', true)->getResult();

        if (!empty($boostedAdCountArray)) {
            $adsBoostedCount = $boostedAdCountArray[0]['total_ads'];
            $boostedAdCount = $this->getRepository('FaAdBundle:BoostedAd')->getMyBoostedAdsCount($loggedinUser->getId());
        }

        $remainingDaysToRenewBoost = '';
        $userBusinessCategory = '';
        if ($loggedinUser->getRole() == 'ROLE_BUSINESS_SELLER' || $loggedinUser->getRole() == 'ROLE_NETSUITE_SUBSCRIPTION') {
            $userBusinessCategory = $loggedinUser->getBusinessCategoryId();
            $getCurrentActivePackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($loggedinUser);
            if ($getCurrentActivePackage) {
                if ($getCurrentActivePackage->getPackage() && $getCurrentActivePackage->getPackage()->getPrice() >0) {
                    $isBoostEnabled = $getCurrentActivePackage->getPackage()->getBoostAdEnabled();
                    $boostMaxPerMonth = ($getCurrentActivePackage->getBoostOveride())?$getCurrentActivePackage->getBoostOveride():$getCurrentActivePackage->getPackage()->getMonthlyBoostCount();
                    $boostAdRemaining = $boostMaxPerMonth;
                    $getExpiryAtDate =  $getCurrentActivePackage->getExpiresAt();
                    $getCreateOrUpdateDate = ($getCurrentActivePackage->getUpdatedAt() > $getCurrentActivePackage->getCreatedAt())?$getCurrentActivePackage->getUpdatedAt():$getCurrentActivePackage->getCreatedAt();
                    if ($getExpiryAtDate=='') {
                        $getExpiryDate = strtotime('+28 days', $getCreateOrUpdateDate);
                    } else {
                        $getExpiryDate =  $getCurrentActivePackage->getExpiresAt();
                    }
                    $todaysTime  = time();
                    if ($todaysTime > $getExpiryDate && $isBoostEnabled==1) {
                        $isBoostEnabled = 0;
                    }

                    $remainingDaysToRenewBoost = date('jS M Y', $getExpiryDate);
                }
            }
        }

        
        if ($boostedAdCount > 0 && $boostMaxPerMonth > 0) {
            $boostAdRemaining = $boostMaxPerMonth - $boostedAdCount;
        }

        $BoostDetails = array(
            'boostedAdCount'  => $boostedAdCount,
            'adsBoostedCount' => $adsBoostedCount,
            'isBoostEnabled'  => $isBoostEnabled,
            'boostMaxPerMonth'=> $boostMaxPerMonth,
            'boostAdRemaining'=> $boostAdRemaining,
            'boostRenewDate'  => $remainingDaysToRenewBoost,
            'userBusinessCategory' => $userBusinessCategory,
        );

        return $BoostDetails;
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
     * Deactivate featured.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxDeactivateFeaturedAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) === true && $request->isXmlHttpRequest()) {
            $error        = '';
            $successMsg   = '';
            $adId         = $request->get('adId', 0);

            $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
            $this->checkIsValidAdUser($ad->getUser()->getId());
            
            $loggedinUser = $this->getLoggedInUser();
            $userId = $loggedinUser->getId();
            $upsellObj = $this->getRepository('FaPromotionBundle:Upsell')->find('5');
                        
            $ans = $this->getRepository('FaAdBundle:AdUserPackageUpsell')->disableFeaturedAdUpsell($adId);
            $this->getRepository('FaUserBundle:UserCreditUsed')->redeemCreditUsedByUpsell($userId,$ad,$upsellObj,$this->container);
            
            if ($ans) {
                $successMsg     = $this->get('translator')->trans('Featured upsell was removed successfully.', array(), 'frontend-manage-my-ad');
                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($successMsg, 'success');
            } else {
                $error          = $this->get('translator')->trans('There was a problem in removing Featured upsell for this advert.', array(), 'frontend-manage-my-ad');
                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($error, 'error');
            }
            
            sleep(2);
            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        }
        
        return new Response();
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

            switch ($oldStatusId) {
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
            } elseif ($invalidNewStatus === false) {
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

            $getBoostDetails = $this->getBoostDetails($loggedinUser);

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
                    $query                = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(), $type, 'ad_date', false, $liveAdStatusArray);
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
                        $htmlContent = $this->renderView('FaUserBundle:ManageMyAd:ajaxGetStatus.html.twig', array('adId' => $liveAd['id'],'status_id' => $liveAd['status_id'], 'ad' => $liveAd, 'adCategoryIdArray' => $adCategoryIdArray, 'adImageArray' => $adImageArray, 'adViewCounterArray' => $adViewCounterArray, 'adPackageArray' => $adPackageArray, 'adModerateArray' => $adModerateArray, 'inModerationLiveAdIds' => $inModerationLiveAdIds, 'isBoostEnabled'  => $getBoostDetails['isBoostEnabled'],'boostMaxPerMonth'=> $getBoostDetails['boostMaxPerMonth'],'boostAdRemaining'=> $getBoostDetails['boostAdRemaining'], 'boostRenewDate'  => $getBoostDetails['boostRenewDate'],'userBusinessCategory' => $getBoostDetails['userBusinessCategory']));
                        $adStatusArray[$liveAd['id']] = $htmlContent;
                    }
                }

                return new JsonResponse($adStatusArray);
            }
        }

        return new Response();
    }

    /**
    * Change boost ad status.
    *
    * @param Request $request
    *        A Request object.
    *
    * @return Response|JsonResponse A Response or JsonResponse object.
    */
    public function ajaxBoostAdAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) === true && $request->isXmlHttpRequest()) {
            $error = '';
            $successMsg = '';
            $loggedinUser = $this->getLoggedInUser();
            $adId = $request->get('adId', 0);
            $boostValue = $request->get('boost_value');
            $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
            $this->getEntityManager()->refresh($ad);
            $gaStr = '';

            $boostedUrl = $this->container->get('router')->generate('manage_my_ads_boosted');

            try {
                $userBusinessCategory = $loggedinUser->getBusinessCategoryId();
                $adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($ad->getCategory()->getId());

                if ($userBusinessCategory == $adRootCategoryId) {
                    $ad->setIsBoosted($boostValue);
                    $ad->setBoostedAt();
                    $ad->setWeeklyRefreshAt(time());
                    $this->getEntityManager()->persist($ad);
                    $this->getEntityManager()->flush($ad);

                    $boostedAd = new BoostedAd();
                    $boostedAd->setAd($ad);
                    $boostedAd->setUser($loggedinUser);
                    $boostedAd->setBoostedAt(time());
                    $boostedAd->setUpdatedAt(time());
                    $boostedAd->setStatus(1);
                    $this->getEntityManager()->persist($boostedAd);
                    $this->getEntityManager()->flush($boostedAd);
                }

                $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

                if ($ad->getIsBoosted() == 1) {
                    $gaStr = $adId;

                    $categoryPaths = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($ad->getCategory()->getId());
                    $catPathCnt = 1;
                    foreach ($categoryPaths as $categoryPath) {
                        if ($catPathCnt==2) {
                            $gaStr .= ':';
                        } else {
                            $gaStr .= '-';
                        }
                        $gaStr .= $categoryPath;
                        $catPathCnt++;
                    }

                    $successMsg = '<span>Congratulations your ads are boosted now.</span> you can view your ads by <a href="'.$boostedUrl.'">clicking here.</a>';
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-solr-index update --status="A,S,E" --user_id="'.$loggedinUser->getId().'" >/dev/null &');

                    $messageManager = $this->get('fa.message.manager');
                    $messageManager->setFlashMessage($successMsg, 'success');
                }
            } catch (\Exception $e) {
                $error = $this->get('translator')->trans('There was a problem in boosting your ad.', array(), 'frontend-manage-my-ad');
                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($error, 'error');
            }

            sleep(2);
            return new JsonResponse(array(
                'error' => $error,
                'successMsg' => $successMsg,
                'gaStr'     => $gaStr,
            ));
        }

        return new Response();
    }

    /**
     * Change boost ad status.
     *
     * @param Request $request
     *        A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxBoostMultipleAdAction(Request $request)
    {
        if ($this->checkIsValidLoggedInUser($request) === true && $request->isXmlHttpRequest()) {
            $error = '';
            $successMsg = '';
            $loggedinUser = $this->getLoggedInUser();
            $adIds = rtrim($request->get('adIds', 0), ',');
            $boostValue = $request->get('boost_value');
            $gaStr = $gaSubStr = '';
            $adCnt = 0;
            $adArray = ($adIds)?explode(',', $adIds):array();
            $userBusinessCategory = $loggedinUser->getBusinessCategoryId();

            $boostedUrl = $this->container->get('router')->generate('manage_my_ads_boosted');
            $activeUrl = $this->container->get('router')->generate('manage_my_ads_active');

            if (!empty($adArray)) {
                foreach ($adArray as $adId) {
                    $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
                    $this->getEntityManager()->refresh($ad);
                    $adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($ad->getCategory()->getId());
                    if ($userBusinessCategory == $adRootCategoryId) {
                        $adCnt++;
                        $ad->setIsBoosted($boostValue);
                        $ad->setBoostedAt();
                        $ad->setWeeklyRefreshAt(time());
                        $this->getEntityManager()->persist($ad);
                        $this->getEntityManager()->flush($ad);

                        $boostedAd = new BoostedAd();
                        $boostedAd->setAd($ad);
                        $boostedAd->setUser($loggedinUser);
                        $boostedAd->setBoostedAt(time());
                        $boostedAd->setUpdatedAt(time());
                        $boostedAd->setStatus(1);
                        $this->getEntityManager()->persist($boostedAd);
                        $this->getEntityManager()->flush($boostedAd);

                        if ($adCnt>1) {
                            $gaSubStr .= ' , ';
                        }

                        $gaSubStr .= $adId;

                        $categoryPaths = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($ad->getCategory()->getId());
                        $catPathCnt = 1;
                        foreach ($categoryPaths as $categoryPath) {
                            if ($catPathCnt==2) {
                                $gaSubStr .= ':';
                            } else {
                                $gaSubStr .= '-';
                            }
                            $gaSubStr .= $categoryPath;
                            $catPathCnt++;
                        }
                    }
                }

                if ($adCnt) {
                    $gaStr = $adCnt.' | '.$gaSubStr;
                    $successMsg = '<span>Congratulations your ads are boosted now.</span> you can view your ads by <a href="'.$boostedUrl.'">clicking here.</a>';
                    $messageManager = $this->get('fa.message.manager');
                    $messageManager->setFlashMessage($successMsg, 'success');

                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-solr-index update --status="A,S,E" --user_id="'.$loggedinUser->getId().'" >/dev/null &');
                    sleep(2);
                    return new JsonResponse(array(
                        'error' => $error,
                        'successMsg' => $successMsg,
                        'gaStr'  => $gaStr,
                    ));
                } else {
                    return new Response();
                }
            } else {
                return new Response();
            }
        }
        return new Response();
    }

    /**
     * @param array $transactions
     * @return string
     * @author Akash M. Pai <akash.pai@fridaymediagroup.com>
     */
    private function getDimension12(&$transactions)
    {
        $flagPrint = false;
        $flagNonprint = false;
        $dimension12 = '';
        $repoLocationGroupLocation = $this->getRepository('FaEntityBundle:LocationGroupLocation');
        $arrPrintLocationTownIds = $repoLocationGroupLocation->getPrintLocationTownIds();
        if (isset($transactions['items']) && !empty($transactions['items']) && !empty($arrPrintLocationTownIds)) {
            foreach ($transactions['items'] as &$valItem) {
                if (!isset($valItem['TownId'])) {
                    continue;
                }
                if (in_array($valItem['TownId'], $arrPrintLocationTownIds)) {
                    $flagPrint = true;
                } else {
                    $flagNonprint = true;
                }
                unset($valItem['TownId']);
            }
        }
        if ($flagPrint && $flagNonprint) {
            $dimension12 = "Both areas";
        } else if ($flagPrint) {
            $dimension12 = "Print";
        } else if ($flagNonprint) {
            $dimension12 = "Non-print";
        }
        return $dimension12;
    }
    
    public function ajaxIndividualUpsellAction($adId, $upsellId, Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $deadlockError = '';
        $deadlockRetry = $request->get('deadlockRetry', 0);
        $cybersource3DSecureResponseFlag = false;
        $redirectUrl	= '';
        $gaStr	        = '';
        $individualUpsellArr = $individualUpsellDetails = array();
        $isAdultAdvertPresent = 0;
        
        if ($request->isXmlHttpRequest()) {
            $cyberSourceManager  = $this->get('fa.cyber.source.manager');
            $loggedinUser     = $this->getLoggedInUser();           
            $errorMsg	= null;
            if (!empty($loggedinUser)) {
                $user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
                if (!empty($user)) {                    
                    //Payment gateway form
                    $formManager = $this->get('fa.formmanager');
                    $form        = $formManager->createForm(CyberSourceCheckoutType::class, array('subscription' => null));

                    $individualUpsellDetails = $this->getRepository('FaPromotionBundle:Upsell')->findBy(array('id'=>$upsellId));
                    if(!empty($individualUpsellDetails)) {
                        $individualUpsellArr['id'] =  $individualUpsellDetails[0]->getId();
                        $individualUpsellArr['title'] =  $individualUpsellDetails[0]->getTitle();
                        $individualUpsellArr['description'] =  $individualUpsellDetails[0]->getDescription();
                        $individualUpsellArr['price'] =  $individualUpsellDetails[0]->getPrice();
                    }
                    $individualUpsellModalDetails = CommonManager::getIndividualUpsellModalDetails($upsellId);
                    
                    $ad 			  = $this->getRepository('FaAdBundle:Ad')->find($adId);
                    
                    $categoryId       = $ad->getCategory()->getId();
                    $adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
                    if ($adRootCategoryId == CategoryRepository::ADULT_ID) {
                        $isAdultAdvertPresent = 1;
                    }
                                            
                    if ('POST' === $request->getMethod()) {
                        $form->handleRequest($request);
                        if ($form->isValid()) {
                            $selectedUpsellId = $upsellId;
                            
                            //Add to the cart
                            $addCartInfo = $this->addUpsellInfoToCart($user->getId(), $adId, $selectedUpsellId, $request, $categoryId);
                            if ($addCartInfo) {
                                //make it cybersource payment
                                $redirectUrl = $request->headers->get('referer');
                                $this->addOrRemoveFeaturedCredits($user->getId(), $adId);
                                $this->container->get('session')->set('upgrade_payment_success_redirect_url', $redirectUrl);
                                $this->get('session')->set('upgrade_cybersource_params_'.$loggedinUser->getId(), array_merge($form->getData(), $request->get('fa_payment_cyber_source_checkout')));
                                $htmlContent= array(
                                    'success' 		=> true,
                                    'redirectUrl' 	=> $this->generateUrl('process_payment', array('paymentMethod' => PaymentRepository::PAYMENT_METHOD_CYBERSOURCE), true)
                                );
                            }
                        } elseif ($request->isXmlHttpRequest()) {
                            $formErrors    = $formManager->getFormSimpleErrors($form, 'label');
                            $errorMessages = '';
                            foreach ($formErrors as $fieldName => $errorMessage) {
                                if ($errorMessages != '') {
                                    $errorMessages = $errorMessages . ' | ' . $fieldName . ': ' . $errorMessage[0];
                                } else {
                                    $errorMessages = $fieldName . ': ' . $errorMessage[0];
                                }
                            }
                            $gaStr = $gaStr . $errorMessages;
                            $parameters = array(
                                'form' => $form->createView(),
                                'subscription' => $request->get('subscription'),
                            );
                            
                            $htmlContent = $this->renderView('FaAdBundle:Ad:upgradePaymentForm.html.twig', $parameters);
                        }
                    } else {
                        $parameters = array(
                            'adId' => $adId,
                            'adRootCategoryId' => $adRootCategoryId,
                            'form' => $form->createView(),
                            'individualUpsellArr' => $individualUpsellArr,
                            'isAdultAdvertPresent' => $isAdultAdvertPresent,
                            'individualUpsellModalDetails' => $individualUpsellModalDetails,
                         );
                         $htmlContent = $this->renderView('FaUserBundle:ManageMyAd:individualUpsellmodalBox.html.twig', $parameters);
                    }
                } else {
                    $error = "Oops! Something went wrong.";
                }
            }
            return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
        } else {
            return new Response();
        }
    }
    
    private function addUpsellInfoToCart($userId, $adId, $selectedUpsellId, $request = null, $categoryId)
    {
        //Add to the cart
        $cart            = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container, false, false, true);
        $cartDetails     = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        if ($cartDetails) {
            $adCartDetails   = $this->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartIdAndAdId($cart->getId(), $adId);
            if ($adCartDetails) {
                $adCartDetailValue = unserialize($adCartDetails[0]->getValue());
            }
        }
        
        //get Upsell Detail
        $selectedUpsellObj = $this->getRepository('FaPromotionBundle:Upsell')->findOneBy(array('id' => $selectedUpsellId));
        $privateUserAdParams = $this->getRepository('FaAdBundle:Ad')->getPrivateUserPostAdParams($userId, $categoryId, $adId, $this->container);
        
        //remove if same ad is in cart.
        if (count($cartDetails) == 1 && $cartDetails[0]['ad_id'] == $adId) {
            unset($cartDetails[0]);
        }
        
        return $this->addAdUpsell($adId, $selectedUpsellId, $privateUserAdParams);
    }
    
    
    /**
     * Assign ad package.
     *
     * @param integer $adId                   Ad id.
     * @param integer $upsellId               Upsell id.
      *
     * @return object.
     */
    public function addAdUpsell($adId, $upsellId, $privateUserAdParams = array())
    {
        $ad      = $this->getRepository('FaAdBundle:Ad')->find($adId);
        
        $response = $this->checkIsValidAdUser($ad->getUser()->getId());
        if ($response !== true) {
            return $response;
        }
        
        $this->getRepository('FaPaymentBundle:Cart')->addUpsellToCart($this->getLoggedInUser()->getId(), $adId, $upsellId, null, $this->container, $privateUserAdParams);
        return true;
    }
    
    /**
     * Upgrade To Featured Ad.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxPaypalPaymentProcessForIndividualUpsellAction($upsellId, $adId, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $redirectToUrl = '';
            $error         = '';
            $htmlContent   = '';
            $deadlockError = '';
            $deadlockRetry = $request->get('deadlockRetry', 0);
            $loggedinUser     = $this->getLoggedInUser();
            $errorMsg	= null;
            $selectedUpsellId = $upsellId;            
            
            if (!empty($loggedinUser)) {
                $user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
                $ad        = $this->getRepository('FaAdBundle:Ad')->find($adId);
                
                if (!empty($user)) {
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($ad->getCategory()->getId(), $this->container);
                    if ($rootCategoryId != CategoryRepository::ADULT_ID) {
                        
                        $selectedUpsellObj = $this->getRepository('FaPromotionBundle:Upsell')->findOneBy(array('id' => $selectedUpsellId));
                        if ($selectedUpsellObj->getDuration()) {
                            $getLastCharacter = substr($selectedUpsellObj->getDuration(),-1);
                            $noInDuration = substr($selectedUpsellObj->getDuration(),0, -1);
                            if($getLastCharacter=='m') { $adExpiryDays = $noInDuration*28;   }
                            elseif($getLastCharacter=='d') { $adExpiryDays = $noInDuration; }
                            else { $adExpiryDays = $selectedUpsellObj->getDuration(); }
                        }
                        
                        //Add to the cart
                        $addCartInfo = $this->addUpsellInfoToCart($user->getId(), $adId, $selectedUpsellId, $request, $ad->getCategory()->getId());
                        if ($addCartInfo) {
                            $redirectUrl = $request->headers->get('referer');
                            $this->container->get('session')->set('upgrade_payment_success_redirect_url', $redirectUrl);
                            $htmlContent= array(
                                'success' 		=> true,
                                'redirectUrl' 	=> $redirectUrl
                            );
                        }                            
                    }                    
                    return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
                }
            }
        }
    } 
    
    /**
     * Upgrade To Featured Ad.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxCreditPaymentProcessForIndividualUpsellAction($upsellId, $adId, Request $request)
    {
        //$upsellId = 5;
        //$adId = 17260111;
        if ($request->isXmlHttpRequest()) {
            $redirectToUrl = '';
            $error         = '';
            $htmlContent   = '';
            $deadlockError = '';
            $deadlockRetry = '';
            $loggedinUser     = $this->getLoggedInUser();
            $errorMsg	= null;
            $selectedUpsellId = $upsellId;
            
            if (!empty($loggedinUser)) {
                $user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
                $ad        = $this->getRepository('FaAdBundle:Ad')->find($adId);
                
                if (!empty($user)) {
                    $selectedUpsellObj = $this->getRepository('FaPromotionBundle:Upsell')->findOneBy(array('id' => $selectedUpsellId));
                    if ($selectedUpsellObj->getDuration()) {
                        $getLastCharacter = substr($selectedUpsellObj->getDuration(),-1);
                        $noInDuration = substr($selectedUpsellObj->getDuration(),0, -1);
                        if($getLastCharacter=='m') { $adExpiryDays = $noInDuration*28;   }
                        elseif($getLastCharacter=='d') { $adExpiryDays = $noInDuration; }
                        else { $adExpiryDays = $selectedUpsellObj->getDuration(); }
                    }
                    
                    //Add to the cart
                    $addCartInfo = $this->addUpsellInfoToCart($user->getId(), $adId, $selectedUpsellId, $request, $ad->getCategory()->getId());

                    if ($addCartInfo) {
                        $upsellObj = $this->getRepository('FaPromotionBundle:Upsell')->find($upsellId);
                        $this->getRepository('FaAdBundle:AdUserPackageUpsell')->setAdUserIndividualUpsell($upsellObj, $ad);
                        $this->getRepository('FaUserBundle:UserCreditUsed')->addCreditUsedByUpsell($user->getId(), $ad, $upsellObj);
                        
                        $redirectUrl = $request->headers->get('referer');
                        $this->container->get('session')->set('upgrade_payment_success_redirect_url', $redirectUrl);
                        
                        $successMsg     = $this->get('translator')->trans('Featured upsell was added successfully.', array(), 'frontend-manage-my-ad');
                        $messageManager = $this->get('fa.message.manager');
                        $messageManager->setFlashMessage($successMsg, 'success');
                        
                        $htmlContent= array(
                            'success' 		=> true,
                            'redirectUrl' 	=> $redirectUrl
                        );
                    }

                    return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
                }
            }
        }
    }  
}
