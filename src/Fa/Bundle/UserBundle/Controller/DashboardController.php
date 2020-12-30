<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\UserBundle\Repository\UserSearchAgentRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\PromotionBundle\Repository\CategoryUpsellRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageUpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;

/**
 * This controller is used for dashboard home page.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class DashboardController extends CoreController
{
    /**
     * This is index action.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function indexAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $location = null;

        $cookieLocationDetails = json_decode($request->cookies->get('location'), true);
        if (is_array($cookieLocationDetails) && isset($cookieLocationDetails['location']) && $cookieLocationDetails['location']) {
            $location = $cookieLocationDetails['location'];
        }

        $baseUrl = $this->container->getParameter('base_url');
        $routeManager    = $this->container->get('fa_ad.manager.ad_routing');
        $searchResultUrl = $baseUrl.$routeManager->getListingUrl(array('item__location' => ($location ? $location : LocationRepository::COUNTY_ID)));

        //get recently viewed ads.
        $recentlyViewedAds = $this->getRecentlyViewedAds($request);

        //get my ads.
        $myAdsParameters = $this->getMyAds($request);

        //get my messages
        $myMessagesParameters = $this->getMyMessages($request);

        //get my favourites
        $myFavouritesParameters = $this->getMyFavourites($request);

        //get my saved searches
        $mySavedSearchesParameters = $this->getMySavedSearches($request);

        //get my reviews
        $myReviewsParameters = $this->getMyReviews($request);
        
        $moderationToolTipText = EntityRepository::inModerationTooltipMsg();

        $remainingDaysToRenewBoost=0;
        $boostedAdCount=0;
        $isBoostEnabled = 0;
        $boostMaxPerMonth = 0;
        $boostAdRemaining = 0;
        $getExipryDate = $boostRenewDate = '';
        
        $loggedinUser = $this->getLoggedInUser();
        $userRole     = $this->getRepository('FaUserBundle:User')->getUserRole($loggedinUser->getId(), $this->container);
        
        $activeFeaturedCreditCount = 0; $activeBasicCreditCount=0;
        $shopFeaturedPackageCredit = 0; $usedFeaturedCreditCount = 0;$remainingFeaturedCredits=0;
        if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            $activeShopPackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($loggedinUser);
            
            if ($activeShopPackage && $activeShopPackage->getPackage()) {
                $activeFeaturedCreditCount = $this->getRepository('FaUserBundle:UserCreditUsed')->getActiveFeaturedCreditCountForUser($loggedinUser->getId());
                $activeBasicCreditCount = $this->getRepository('FaUserBundle:UserCreditUsed')->getActiveBasicCreditCountForUser($loggedinUser->getId());
                $shopFeaturedPackageCreditArr = $this->getRepository('FaPromotionBundle:ShopPackageCredit')->getFeaturedCreditsByPackageId($activeShopPackage->getPackage()->getId());
                if(!empty($shopFeaturedPackageCreditArr)) { $shopFeaturedPackageCredit = $shopFeaturedPackageCreditArr[0]->getCredit(); }
                $usedFeaturedCreditCount = ((int)$shopFeaturedPackageCredit - (int)$activeFeaturedCreditCount);
                $remainingFeaturedCredits = $activeFeaturedCreditCount;
            }
        }
        
        $parameters = array('recentlyViewedAds' => $recentlyViewedAds, 'myAdsParameters' => $myAdsParameters, 'myMessagesParameters' => $myMessagesParameters, 'myFavouritesParameters' => $myFavouritesParameters, 'mySavedSearchesParameters' => $mySavedSearchesParameters, 'myReviewsParameters' => $myReviewsParameters, 'searchResultUrl' => $searchResultUrl, 'modToolTipText'  => $moderationToolTipText,
            'isBoostEnabled'  => $isBoostEnabled,
            'boostMaxPerMonth'=> $boostMaxPerMonth,
            'boostAdRemaining'=> $boostAdRemaining,
            'boostRenewDate'  => $remainingDaysToRenewBoost,
            'boostedAdCount'  => $boostedAdCount,
            'activeShopPackage' => $activeShopPackage,
            'activeFeaturedCreditCount' => $activeFeaturedCreditCount,
            'activeBasicCreditCount' => $activeBasicCreditCount,
            'shopFeaturedPackageCredit'=> $shopFeaturedPackageCredit,
            'usedFeaturedCreditCount' => $usedFeaturedCreditCount,
            'remainingFeaturedCredits' => $remainingFeaturedCredits,
        );
        
        return $this->render('FaUserBundle:Dashboard:index.html.twig', $parameters);
    }

    /**
     * render notifications
     *
     * @param string $template
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function notificationAction($template, Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $notifications = $this->getRepository('FaMessageBundle:NotificationMessageEvent')->getActiveNotification($this->getLoggedInUser()->getId());

        if ($template == 'dashboard') {
            $nt = $this->getNotificationArray($notifications);
            return $this->render('FaUserBundle:Dashboard:notification.html.twig', array('notifications' => $nt));
        } elseif ($template == 'menu') {
            return $this->render('FaUserBundle:Dashboard:notificationMenuHeading.html.twig', array('count' => count($notifications)));
        } elseif ($template == 'mobile_menu') {
            return $this->render('FaUserBundle:Dashboard:notificationMenuMobile.html.twig', array('count' => count($notifications)));
        }
    }

    /**
     * get notification array
     *
     * @return void
     */
    private function getNotificationArray($notifications)
    {
        $nt = array();

        foreach ($notifications as $notification) {
            $message = $notification['message'];
            $value = unserialize($notification['value']);
            if (isset($notification['ad_id'])) {
                $ad         = $this->getRepository('FaAdBundle:Ad')->find($notification['ad_id']);
                $adTitle    = 'Ad title';
                if ($ad) {
                    $adTitle = $ad->getTitle();
                }

                $message    = str_replace('{site_url}', $this->container->getParameter('base_url'), $message);
                $message    = str_replace('{advert_title}', $adTitle, $message);
                $editAdUrl  = $this->generateUrl('ad_edit', array('id' => $notification['ad_id']));
                $message    = str_replace('{url_to_edit_advert}', $editAdUrl, $message);

                $duration = CommonManager::encryptDecrypt('R', $notification['display_from']);
                $refreshUrl = $this->generateUrl('manage_my_ads_refresh_ad', array('adId' => $notification['ad_id'], 'date' => $duration));
                $soldUrl    = $this->generateUrl('manage_my_ads_mark_as_sold', array('adId' => $notification['ad_id']));
                $upgradeUrl    = $this->generateUrl('ad_package_purchase', array('adId' => $notification['ad_id']));

                $message = str_replace('{url_mark_advert_as_sold}', $soldUrl, $message);
                $message = str_replace('{url_to_refresh_advert}', $refreshUrl, $message);
                $message = str_replace('{url_to_upgrade_advert}', $upgradeUrl, $message);

                if ($notification['indentifier'] == 'advert_upgraded_print' && $ad) {
                    $nextPrintDate = $this->getRepository('FaAdBundle:AdPrint')->getNextPrintEntry($ad);
                    if ($nextPrintDate) {
                        $message = str_replace('{next_print_date}', CommonManager::formatDate($nextPrintDate->getInsertDate(), $this->container), $message);
                    }
                }
            }

            if ($notification['indentifier'] == 'share_on_facebook_twitter') {
                $adDetailUrl = $this->generateUrl('ad_detail_page_by_id', array('id' => $notification['ad_id']), true);
                $adFaceBookShareUrl = $this->container->getParameter('fa.social.share.url').'/facebook/offer?url='.$adDetailUrl.'&ct=1&title='.$adTitle.'&pubid='.$this->container->getParameter('fa.add.this.pubid').'&pco=tbxnj-1.0';
                $adTwitterShareUrl  = $this->container->getParameter('fa.social.share.url').'/twitter/offer?url='.$adDetailUrl.'&ct=1&title='.$adTitle.'&pubid='.$this->container->getParameter('fa.add.this.pubid').'&pco=tbxnj-1.0';
                $message = str_replace('{url_facebook_share}', $adFaceBookShareUrl, $message);
                $message = str_replace('{url_twitter_share}', $adTwitterShareUrl, $message);
            }

            if ($notification['indentifier'] == 'message_from_seller' || $notification['indentifier'] == 'message_from_buyer_seller') {
                $inboxUrl = $this->generateUrl('user_ad_message_all');
                $message = str_replace('{url_to_inbox}', $inboxUrl, $message);
            }
            if ($notification['indentifier'] == 'you_have_new_review' || $notification['indentifier'] == 'you_submitted_review') {
                $message = str_replace('{name}', $notification['user_name'], $message);
                $reviewUrl = $this->generateUrl('user_review_list');
                $message = str_replace('{url_to_users_reviews}', $reviewUrl, $message);
            }

            if ($notification['indentifier'] == 'you_have_a_new_order') {
                if (isset($value['ad_buy_now_cart_code'])) {
                    $orderUrl = $this->generateUrl('my_orders', array('orderId' => $value['ad_buy_now_cart_code']));
                } else {
                    $orderUrl = $this->generateUrl('my_orders');
                }

                $message = str_replace('{url_to_view_orders}', $orderUrl, $message);
            }

            if ($notification['indentifier'] == 'leave_review_for_buyer_after_buy_now' || $notification['indentifier'] == 'leave_review_for_seller_after_buy_now' || $notification['indentifier'] == 'leave_review_for_buyer_after_contact' || $notification['indentifier'] == 'leave_review_for_seller_after_contact') {
                if ($notification['indentifier'] == 'leave_review_for_seller_after_buy_now') {
                    if (isset($value['ad_buy_now_cart_code'])) {
                        $reviewUrl = $this->generateUrl('my_orders', array('orderId' => $value['ad_buy_now_cart_code']));
                    } else {
                        $reviewUrl = $this->generateUrl('my_orders');
                    }

                    $message = str_replace('{url_to_leave_review}', $reviewUrl, $message);
                } elseif ($notification['indentifier'] == 'leave_review_for_buyer_after_buy_now') {
                    if (isset($value['ad_buy_now_cart_code'])) {
                        $reviewUrl = $this->generateUrl('my_purchases', array('orderId' => $value['ad_buy_now_cart_code']));
                    } else {
                        $reviewUrl = $this->generateUrl('my_purchases');
                    }

                    $message = str_replace('{url_to_leave_review}', $reviewUrl, $message);
                } else {
                    $reviewUrl = $this->generateUrl('user_review_list');
                    $message = str_replace('{url_to_leave_review}', $reviewUrl, $message);
                }
            }

            $nt[$notification['id']]['message']           = $message;
            $nt[$notification['id']]['id']                = $notification['id'];
            $nt[$notification['id']]['notification_type'] = $notification['notification_type'];
        }

        return $nt;
    }

    public function displayNotificationForMenuAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $notifications = $this->getRepository('FaMessageBundle:NotificationMessageEvent')->getActiveNotification($this->getLoggedInUser()->getId());
        $nt = $this->getNotificationArray($notifications);
        return $this->render('FaUserBundle:Dashboard:notificationMenu.html.twig', array('notifications' => $nt));
    }

    public function displayNotificationMenuHeadingAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $notifications = $this->getRepository('FaMessageBundle:NotificationMessageEvent')->getActiveNotification($this->getLoggedInUser()->getId());

        if ($template == 'menu') {
            return $this->render('FaUserBundle:Dashboard:notificationMenu.html.twig', array('notifications' => $nt));
        } elseif ($template == 'mobile_menu') {
            return $this->render('FaUserBundle:Dashboard:notificationMenuMobile.html.twig', array('notifications' => $nt));
        }
    }


    /**
     * Remove ad from favorite.
     *
     * @param integer $messageId message id
     * @param Request $request   request id.
     *
     * @return Response A Response object.
     */
    public function ajaxRemoveMessageAction($messsageId, Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $error         = '';
        $anchorHtml    = '';
        $notification  = $this->getRepository('FaMessageBundle:NotificationMessageEvent')->find($messsageId);

        if ($notification) {
            $type          = $request->get('type', 'list');
            $userId = $this->getLoggedInUser()->getId();
            $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByIdAndUserId($notification->getId(), $userId);
            return new JsonResponse();
        }
    }

    /**
     * Get recently viwed ads.
     *
     * @param Request $request A Request object.
     *
     * @return array
     */
    private function getRecentlyViewedAds($request)
    {
        $cookies = $request->cookies;

        if ($cookies->has('ad_view_ids')) {
            $adIds = explode(',', $cookies->get('ad_view_ids'));
            $adIds = array_reverse($adIds);

            if (count($adIds) > 12) {
                $adIds = array_slice($adIds, 0, 12);
            }

            $data           = array();
            $keywords       = null;
            $page           = 1;
            $recordsPerPage = 12;

            //set ad criteria to search
            $data['query_filters']['item']['id']          = $adIds;
            $data['query_filters']['item']['status_id']   = EntityRepository::AD_STATUS_LIVE_ID;

            // initialize solr search manager service and fetch data based of above prepared search options
            $solrSearchManager = $this->get('fa.solrsearch.manager');
            $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
            $solrResponse = $solrSearchManager->getSolrResponse();

            // fetch result set from solr
            $recentlyViewedAds = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);

            $recentyViwedAdsArray = array();
            foreach ($recentlyViewedAds as $recentlyViewedAd) {
                $key = array_search($recentlyViewedAd->id, $adIds);
                $recentyViwedAdsArray[$key] = $recentlyViewedAd;
            }
            ksort($recentyViwedAdsArray);

            return $recentyViwedAdsArray;
        }

        return null;
    }

    /**
      * Get my ads.
      *
      * @param Request $request A Request object.
      *
      * @return array
      */
    public function getMyAds(Request $request)
    {
        $loggedinUser = $this->getLoggedInUser();
        $activeAdCount   = 0;
        $type            = $request->get('type', 'both');
        $query                = $this->getRepository('FaAdBundle:Ad')->getMyAdsQuery($loggedinUser->getId(),$type);
        // initialize pagination manager service and prepare listing with pagination based of data
        $page = $request->get('page', 1);
        $this->get('fa.pagination.manager')->init($query, $page, 2);
        $pagination = $this->get('fa.pagination.manager')->getPagination();
        $totalAdCount = $pagination->getNbResults();

        $parameters = array('pagination'   => $pagination,
                            'totalAdCount' => $totalAdCount);

        return $parameters;
    }

    /**
     * Get my messages.
     *
     * @param Request $request A Request object.
     *
     * @return array
     */
    public function getMyMessages(Request $request)
    {
        $messageDetailArray = array();
        $userLogosArray     = array();
        $type  = $request->get('messageType', 'all');
        $query = $this->getRepository('FaMessageBundle:Message')->getUserMessageIdsQuery($this->getLoggedInUser()->getId(), $type)->setMaxResults(2);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\MessageBundle\Walker\MessageCountSqlWalker');
        $query->setHint("messageCountSqlWalkerSqlWalker.replaceCountField", true);

        $unreadUserAdMsgCount           = $this->getRepository('FaMessageBundle:Message')->getMessageCount($this->getLoggedInUser()->getId(), 'receiver', $this->container);
        $unreadUserInterestedAdMsgCount = $this->getRepository('FaMessageBundle:Message')->getMessageCount($this->getLoggedInUser()->getId(), 'sender', $this->container);
        $totalMsgCount                  = $this->getRepository('FaMessageBundle:Message')->getMessageCount($this->getLoggedInUser()->getId(), 'all', $this->container);

        $this->get('fa.pagination.manager')->init($query, $request->get('page', 1), 2);
        $pagination = $this->get('fa.pagination.manager')->getPagination(false);

        if ($pagination->getNbResults()) {
            $messageIds = array();
            $senderIds  = array();
            foreach ($pagination->getCurrentPageResults() as $messageArray) {
                $messageIds[] = $messageArray['msg_id'];
                $messageDetailArray[$messageArray['msg_id']]['ad_id']    = $messageArray['ad_id'];
                $messageDetailArray[$messageArray['msg_id']]['ad_title'] = $messageArray['ad_title'];
                $messageDetailArray[$messageArray['msg_id']]['subject'] = $messageArray['subject'];
                $messageDetailArray[$messageArray['msg_id']]['message_ad_id'] = $messageArray['message_ad_id'];
            }

            if (count($messageIds) > 0) {
                $messageDetails = $this->getRepository('FaMessageBundle:Message')->getUserMessageDetailsByIds($messageIds);
                if ($messageDetails && is_array($messageDetails)) {
                    foreach ($messageDetails as $msgDetail) {
                        $msgId = $msgDetail['id'];
                        $senderIds[] = $msgDetail['sender_id'];
                        $senderIds[] = $msgDetail['receiver_id'];
                        $messageDetailArray[$msgId] = $messageDetailArray[$msgId]+$msgDetail;
                    }
                }
            }

            if (count($senderIds) > 0) {
                $userLogosArray = $this->getRepository('FaUserBundle:User')->getUserDetailForMessage($senderIds);
            }
        }

        $parameters = array(
            'pagination'                     => $pagination,
            'totalMsgCount'                  => $totalMsgCount,
            'unreadUserAdMsgCount'           => $unreadUserAdMsgCount,
            'unreadUserInterestedAdMsgCount' => $unreadUserInterestedAdMsgCount,
            'messageDetailArray'             => $messageDetailArray,
            'userLogosArray'                 => $userLogosArray);

        return $parameters;
    }

    /**
     * Show user ads.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function getMyFavourites(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $objLoggedInUser = $this->getLoggedInUser();
        $adIdsArray      = $this->getRepository('FaAdBundle:AdFavorite')->getFavoriteAdByUserId($objLoggedInUser->getId(), $this->container);

        $cookieLocation     = json_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('location'), true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        $keywords       = null;
        $page           = ($request->get('page') ? $request->get('page') : 1);
        $recordsPerPage = 50;

        if (is_array($adIdsArray) && count($adIdsArray) > 0) {
            //set ad criteria to search
            $data['query_filters']['item']['id'] = $adIdsArray;

            // initialize solr search manager service and fetch data based of above prepared search options
            $solrSearchManager = $this->get('fa.solrsearch.manager');
            $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
            if (!empty($cookieLocation) && !empty($adIdsArray) && isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
                $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocation['latitude'].','.$cookieLocation['longitude']);
                $this->get('fa.solrsearch.manager')->setGeoDistQuery($geoDistParams);
            }
            $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();
            $result       = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
            $resultCount  = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);
            $this->get('fa.pagination.manager')->init($result, $page, $recordsPerPage, $resultCount);
            $pagination = $this->get('fa.pagination.manager')->getSolrPagination();

            $parameters = array('pagination' => $pagination);
            return $parameters;
        }

        return array();
    }

    /**
     * Get my saved searches.
     *
     * @param Request $request A Request object.
     *
     * @return array
     */
    public function getMySavedSearches(Request $request)
    {
        $loggedinUser = $this->getLoggedInUser();
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaUserBundle:UserSearchAgent'), $this->getRepositoryTable('FaUserBundle:UserSearchAgent'));
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $data['select_fields']  = array('user_search_agent' => array('id', 'name', 'criteria', 'is_email_alerts'));
        $data['static_filters'] = UserSearchAgentRepository::ALIAS.'.user = '.$loggedinUser->getId();
        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaUserBundle:UserSearchAgent'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $query        = $queryBuilder->getQuery()->setMaxResults(2);

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 2);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        $parameters = array(
            'pagination'  => $pagination,
        );

        return $parameters;
    }

    /**
     * Get my saved searches.
     *
     * @param Request $request A Request object.
     *
     * @return array
     */
    public function getMyReviews(Request $request)
    {
        // Make tab active based on recent review from sellers or from buyers
        $isRecentReviewByWhom = $this->getRepository('FaUserBundle:UserReview')->isRecentReviewByWhom($this->getLoggedInUser()->getId());
        $request->attributes->set('reviewType', $isRecentReviewByWhom);

        $reviewFromSellerCount    = $this->getRepository('FaUserBundle:UserReview')->getReviewCount($this->getLoggedInUser()->getId(), 'from_sellers');
        $reviewFromBuyerCount     = $this->getRepository('FaUserBundle:UserReview')->getReviewCount($this->getLoggedInUser()->getId(), 'from_buyers');
        $reviewLeftForOthersCount = $this->getRepository('FaUserBundle:UserReview')->getReviewLeftForOthersCount($this->getLoggedInUser()->getId());
        $totalReviewsCount        = $reviewFromSellerCount + $reviewFromBuyerCount + $reviewLeftForOthersCount;

        $type = $request->get('reviewType', 'from_buyers');
        if ($type == 'from_buyers' || $type == 'from_sellers') {
            $query = $this->getRepository('FaUserBundle:UserReview')->getUserReviewsQuery($this->getLoggedInUser()->getId(), $type)->setMaxResults(2);
        } else {
            $query = $this->getRepository('FaUserBundle:UserReview')->getUserReviewsLeftForOthersQuery($this->getLoggedInUser()->getId())->setMaxResults(2);
        }

        $this->get('fa.pagination.manager')->init($query, $request->get('page', 1), 2, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination(false);

        $parameters = array(
            'reviewFromSellerCount'    => $reviewFromSellerCount,
            'reviewFromBuyerCount'     => $reviewFromBuyerCount,
            'reviewLeftForOthersCount' => $reviewLeftForOthersCount,
            'totalReviewsCount'        => $totalReviewsCount,
            'pagination'               => $pagination
        );

        return $parameters;
    }
}
