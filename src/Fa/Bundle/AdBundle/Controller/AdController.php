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
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Solr\AdAnimalsSolrFieldMapping;
use Fa\Bundle\AdBundle\Solr\AdMotorsSolrFieldMapping;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\MessageBundle\Entity\Message;

/**
 * This controller is used for ad post management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdController extends CoreController
{
    /**
     * Ad detail.
     *
     * @param integer $adId
     * @param Request $request
     *
     * @throws createNotFoundException
     * @return Response A Response object.
     */
    public function showAdPreviewAction($adId, Request $request)
    {
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

        try {
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_DRAFT_ID)) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Draft Ad.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage');
        }

        $response = $this->checkIsValidAdUser($ad->getUser()->getId());
        if ($response !== true) {
            return $response;
        }

        //get location from cookie.
        $locationDetails = json_decode($request->cookies->get('location'), true);
        if (!isset($locationDetails['location'])) {
            $locationDetails['location'] = null;
        }

        $adDetail = $this->getRepository('FaAdBundle:Ad')->getAdDetailArray($adId, $this->container);
        
        $paaFieldArray = array();
        $paaFieldRules = $this->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryAncestor($adDetail['category_id']);
        foreach ($paaFieldRules as $paaFieldRule) {
            $paaFieldArray[] = $paaFieldRule['paa_field']['field'];
        }

        $parameters = array(
            'ad' => $ad,
            'adDetail' => $adDetail,
            'location_id' => $locationDetails['location'],
            'paaFieldArray' => $paaFieldArray,
        );

        if (isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
            $parameters['cookieLatitude'] = $cookieLocation['latitude'];
            $parameters['cookieLongitude'] = $cookieLocation['longitude'];
        }

        return $this->render('FaAdBundle:Ad:showAdPreview.html.twig', $parameters);
    }

    /**
     * Get car data using vrm.
     *
     * @param string $vrm
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function getCarwebDataByVRMAction($vrm)
    {
        try {
            $webcar = $this->get('fa.webcar.manager');
            $data   = $webcar->findByVRM($vrm);
            return new JsonResponse($data);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage');
        }
    }

    /**
     * Get car data using vrm by ajax.
     *
     * @param string $vrm
     * @param object $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function ajaxGetCarwebDataByVRMAction($vrm, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            try {
                $webcar     = $this->get('fa.webcar.manager');
                $carWebData = $webcar->findByVRM($vrm);

                $fieldsData = array();
                if (!isset($carWebData['error'])) {
                    $data = $this->getRepository('FaAdBundle:AdMotors')->getFieldDataByCarWebDataArray($carWebData, $request->get('category_id'), $this->container);
                    if (count($data)) {
                        $fieldsData['reg_no_data'] = $data;
                    }
                }

                return new JsonResponse($fieldsData);
            } catch (\Exception $e) {
                return new JsonResponse(array());
            }
        }

        return new Response();
    }

    /**
     * Show active ad detail page.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showAdByIdAction(Request $request)
    {
        $adId = $request->get('id', 0);

        if ($adId) {
            $objAd = $this->getRepository('FaAdBundle:Ad')->find($adId);

            if ($objAd) {
                if ($objAd->getAffiliate() && $objAd->getTrackBackUrl()) {
                    $url = $objAd->getTrackBackUrl();
                } else {
                    $routeManager = $this->container->get('fa_ad.manager.ad_routing');
                    $url          = $routeManager->getDetailUrl($objAd);
                    $queryParams = $request->query->all();
                    if (count($queryParams)) {
                        $url = $url.'?'.http_build_query($queryParams);
                    }
                }

                return $this->redirect($url, 301);
            }

            //check for removed ads
            $redirectUrl = $this->handleRemovedAds($adId);
            if ($redirectUrl) {
                return $this->redirect($redirectUrl, 301);
            }
        }

        throw new HttpException(410, 'Oops, it seems this advert is no longer available!');
    }

    /**
     * Show active ad detail page.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showTiAdByIdAction(Request $request)
    {
        $objAd = null;
        $adId = $request->get('id', 0);

        if ($adId) {
            $adObj = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('ti_ad_id' => $adId));
            if ($adObj) {
                $objAd = $adObj;
            } elseif (!$adObj) {
                $tiAdFeedObj = $this->getRepository('FaAdFeedBundle:TiAdFeed')->findOneBy(array('ad_id' => $adId));
                if ($tiAdFeedObj) {
                    if ($tiAdFeedObj->getRefSiteId() == 1) {
                        $adFeedObj = $this->getRepository('FaAdFeedBundle:AdFeed')->findOneBy(array('trans_id' => $tiAdFeedObj->getTransId()));
                    } else {
                        $adFeedObj = $this->getRepository('FaAdFeedBundle:AdFeed')->findOneBy(array('unique_id' => $tiAdFeedObj->getUniqueId()));
                    }
                    if ($adFeedObj) {
                        $objAd = $adFeedObj->getAd();
                    }
                }
            }
            if ($objAd) {
                if ($objAd->getAffiliate() && $objAd->getTrackBackUrl()) {
                    $url = $objAd->getTrackBackUrl();
                } else {
                    $routeManager = $this->container->get('fa_ad.manager.ad_routing');
                    $url          = $routeManager->getDetailUrl($objAd);
                }

                $queryParams = $request->query->all();
                if (count($queryParams)) {
                    $url = $url.'?'.http_build_query($queryParams);
                }
                $key = md5($request->getClientIp().$request->headers->get('User-Agent'));

                CommonManager::setCacheVersion($this->container, 'ti_url_'.$key, str_replace(array($this->container->getParameter('base_url'), $objAd->getId()), array($this->container->getParameter('ti_base_url'), $adId), $url));

                return $this->redirect($url, 301);
            }

            if ($redirectUrl) {
                return $this->redirect($redirectUrl, 301);
            }
        }

        $url = $this->get('router')->generate('ad_detail_page_by_id', array('id' => $adId), true);
        return $this->redirect($url, 301);
    }

    /**
     * Show active ad detail page.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showAdAction(Request $request)
    {
        $adId          = $request->get('id', 0);
        $prevRouteName = null;
        $successPaymentModalbox = false;
        $refererUrl    = $request->server->get('HTTP_REFERER');
        if ($refererUrl) {
            $urlParams = parse_url($refererUrl);

            if (isset($urlParams['scheme']) && isset($urlParams['host']) && isset($urlParams['path'])) {
                $refererUrl    = str_replace(array($urlParams['scheme'].'://'.$urlParams['host'], $request->getBaseURL()), '', $urlParams['path']);
                try {
                    $prevRouteName = $this->get('router')->match($refererUrl)['_route'];
                } catch (ResourceNotFoundException $e) {
                    $prevRouteName = null;
                }
                if ($prevRouteName == 'listing_page') {
                    $this->container->get('session')->set('back_to_search_url', $request->server->get('HTTP_REFERER'));
                } else {
                    $this->container->get('session')->remove('back_to_search_url');
                }
            }
        } elseif ($this->container->get('session')->has('back_to_search_url')) {
            $refererUrl = $this->container->get('session')->get('back_to_search_url');
            $urlParams  = parse_url($refererUrl);
            if (isset($urlParams['scheme']) && isset($urlParams['host']) && isset($urlParams['path'])) {
                $refererUrl = str_replace(array($urlParams['scheme'].'://'.$urlParams['host'], $request->getBaseURL()), '', $urlParams['path']);
                try {
                    $prevRouteName = $this->get('router')->match($refererUrl)['_route'];
                } catch (ResourceNotFoundException $e) {
                    $prevRouteName = null;
                }
                if ($prevRouteName != 'listing_page') {
                    $this->container->get('session')->remove('back_to_search_url');
                }
            }
        }
        
        $transactionJsArr = [];
        //check session for upgrade Payment has been done successfully
        if ($this->container->get('session')->has('payment_success_for_upgrade') && $this->container->get('session')->has('payment_success_for_upgrade') != '') {
            $successPaymentModalbox 	= true;
            if ($this->container->get('session')->has('upgrade_payment_transaction_id')) {
                $loggedinUser = $this->getLoggedInUser();
                if ($loggedinUser) {
                    $transcations	= $this->getRepository('FaPaymentBundle:Payment')->getTranscationDetailsForGA($this->container->get('session')->get('upgrade_payment_transaction_id'), $loggedinUser, true);
                    $transactionJsArr['getTranscationJs'] = CommonManager::getGaTranscationJs($transcations);
                    $transactionJsArr['getItemJs']        = CommonManager::getGaItemJs($transcations);
                    $transactionJsArr['ga_transaction']   = $transcations;
                }
            }
            $this->container->get('session')->remove('payment_success_for_upgrade');
            $this->container->get('session')->remove('upgrade_payment_success_redirect_url');
            $this->container->get('session')->remove('upgrade_payment_transaction_id');
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'));
        $data           = $this->get('fa.searchfilters.manager')->getFiltersData();
        $keywords       = null;
        $page           = 1;
        $recordsPerPage = 1;

        //set ad criteria to search
        $data['query_filters']['item']['id']        = $adId;
        $data['query_filters']['item']['status_id'] = array(EntityRepository::AD_STATUS_LIVE_ID, EntityRepository::AD_STATUS_SOLD_ID, EntityRepository::AD_STATUS_EXPIRED_ID);
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
        // fetch location from cookie.
        $cookieLocation  = $request->cookies->get('location');
        if ($cookieLocation && $cookieLocation != CommonManager::COOKIE_DELETED) {
            $cookieLocation = get_object_vars(json_decode($cookieLocation));
            if (isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
                $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocation['latitude'].','.$cookieLocation['longitude']);
                $solrSearchManager->setGeoDistQuery($geoDistParams);
            }
        }
        $solrResponse = $solrSearchManager->getSolrResponse();

        // fetch result set from solr
        $adDetail = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);

        if (!count($adDetail)) {
            $redirectUrl = $this->handleRemovedAds($adId);

            if ($redirectUrl) {
                return $this->redirect($redirectUrl, 301);
            } else {
                throw new HttpException(410, 'Oops, it seems this advert is no longer available!');
            }
        }
        //update ad view counter in redis.
        $this->getRepository('FaAdBundle:AdViewCounter')->updateAdViewCounter($this->container, $adId);

        //get similar ads.
        $adCategoryId     = $adDetail[0][AdSolrFieldMapping::CATEGORY_ID];
        $adTitle          = $adDetail[0][AdSolrFieldMapping::TITLE];
        $similarAds       = $this->getRepository('FaAdBundle:Ad')->getPaaSimilarAdverts($this->container, $adCategoryId, $adTitle, 1, 12, 0, 'geodist', ' AND -id:'.$adId);
        $adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($adCategoryId, $this->container);

        $paaFieldArray = array();
        $paaFieldRules = $this->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryAncestor($adCategoryId,$this->container,'edit');
        foreach ($paaFieldRules as $paaFieldRule) {
            $paaFieldArray[] = $paaFieldRule['paa_field']['field'];
        }
            
        //remove script tag from description
        if (isset($adDetail[0][AdSolrFieldMapping::DESCRIPTION])) {
            $adDetail[0][AdSolrFieldMapping::DESCRIPTION] = preg_replace('#<a.*?>([^>]*)</a>#i', '$1', $adDetail[0][AdSolrFieldMapping::DESCRIPTION]);
            $adDetail[0][AdSolrFieldMapping::DESCRIPTION] = CommonManager::stripTagsContent(htmlspecialchars_decode($adDetail[0][AdSolrFieldMapping::DESCRIPTION]), '<em><strong><b><i><u><p><ul><li><ol><div><span><br>');
        }
        //remove phone or email from title
        if (isset($adDetail[0][AdSolrFieldMapping::TITLE])) {
            $adDetail[0][AdSolrFieldMapping::TITLE] = CommonManager::hideOrRemovePhoneNumber($adDetail[0][AdSolrFieldMapping::TITLE], 'remove','');
            $adDetail[0][AdSolrFieldMapping::TITLE] = CommonManager::hideOrRemoveEmail($adId, $adDetail[0][AdSolrFieldMapping::TITLE], 'remove','');
        }

        //get location from cookie.
        if (!isset($cookieLocation['location'])) {
            $cookieLocation             = array();
            $cookieLocation['location'] = null;
            $cookieLocation['slug'] = null;
        }

        //get user detail.
        $userDetailArray = array();
        if ($adDetail[0]['a_user_id_i']) {
            $userDetailArray = $this->getRepository('FaUserBundle:User')->getAdUserDetail($adDetail[0]['a_user_id_i']);
        }
        if (count($userDetailArray)) {
            $adDetail[0]['user'] = $userDetailArray;
        }
        $parameters = array(
            'adDetail' => $adDetail[0],
            'location_id' => $cookieLocation['location'],
            'location_slug' => $cookieLocation['slug'],
            'similarAds' => $similarAds,
            'successPaymentModalbox' => $successPaymentModalbox,
            'paymentTransactionJs'	 => $transactionJsArr,
            'paaFieldArray' => $paaFieldArray,
        );

        if (isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
            $parameters['cookieLatitude'] = $cookieLocation['latitude'];
            $parameters['cookieLongitude'] = $cookieLocation['longitude'];
        }

        $routeManager = $this->container->get('fa_ad.manager.ad_routing');
        $url          = $routeManager->getDetailUrl($adDetail[0]);

        $requestUri = parse_url($request->getUri());
        // For symfony 3.4, $this->router->generate() is not giving absolute url so below keep only $requestUri['path']
//         $constructedUri = $requestUri['scheme'].'://'.$requestUri['host'].$requestUri['path'];
        $constructedUri = $requestUri['path'];

        if ($constructedUri != $url) {
            if (isset($requestUri['query'])) {
                $url = $url.'?'.$requestUri['query'];
            }
            return $this->redirect($url, 301);
        }

        if ($adDetail[0][AdSolrFieldMapping::IS_AFFILIATE_AD] == 1 && strstr($adDetail[0][AdSolrFieldMapping::TRACK_BACK_URL], $this->container->getParameter('ti_base_url')) === false) {
            return $this->redirect($adDetail[0][AdSolrFieldMapping::TRACK_BACK_URL], 301);
        }

        $objResponse = null;
        if (isset($adRootCategoryId) && $adRootCategoryId == CategoryRepository::ADULT_ID) {
            $objResponse = CommonManager::setCacheControlHeaders();
        }

        if ($adCategoryId && $adRootCategoryId && $adRootCategoryId != CategoryRepository::ADULT_ID) {
            $relatedBusinesses = $this->getRepository('FaUserBundle:UserPackage')->getRelatedBusinesses($adCategoryId, $this->container);
            $parameters['relatedBusinesses'] = $relatedBusinesses;
            $relatedBusinessesHeading = null;
            switch ($adRootCategoryId) {
                case CategoryRepository::FOR_SALE_ID:
                    $relatedBusinessesHeading = $this->get('translator')->trans('Related shops', array(), 'frontend-show-ad');
                    break;
                case CategoryRepository::MOTORS_ID:
                    $relatedBusinessesHeading = $this->get('translator')->trans('Related dealers', array(), 'frontend-show-ad');
                    break;
                case CategoryRepository::JOBS_ID:
                    $relatedBusinessesHeading = $this->get('translator')->trans('Related recruiters', array(), 'frontend-show-ad');
                    break;
                case CategoryRepository::PROPERTY_ID:
                    $relatedBusinessesHeading = $this->get('translator')->trans('Related agencies', array(), 'frontend-show-ad');
                    break;
                case CategoryRepository::ANIMALS_ID:
                    $relatedBusinessesHeading = $this->get('translator')->trans('Related breeders', array(), 'frontend-show-ad');
                    break;
                case CategoryRepository::SERVICES_ID:
                case CategoryRepository::COMMUNITY_ID:
                default:
                    $relatedBusinessesHeading = $this->get('translator')->trans('Related businesses', array(), 'frontend-show-ad');
                    break;
            }

            $parameters['relatedBusinessesHeading'] = $relatedBusinessesHeading;
        }

        $recommendedSlotArray = $this->getRepository('FaEntityBundle:CategoryRecommendedSlot')->getCategoryRecommendedSlotArrayByCategoryId($adCategoryId, $this->container);
        $parameters['recommendedSlotArray'] = $recommendedSlotArray;
        
        return $this->render('FaAdBundle:Ad:showAd.html.twig', $parameters, $objResponse);
    }

    /**
     * Show active ad detail page.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showTiAdAction(Request $request)
    {
        $redirectUrl = null;
        $adId = $request->get('id', 0);

        $objAd = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('ti_ad_id' => $adId));
        if ($objAd) {
            $routeManager = $this->container->get('fa_ad.manager.ad_routing');
            $redirectUrl          = $routeManager->getDetailUrl($objAd);
            $queryParams = $request->query->all();
            if (count($queryParams)) {
                $redirectUrl = $redirectUrl.'?'.http_build_query($queryParams);
            }
        } elseif (!$objAd) {
            $tiAdFeedObj = $this->getRepository('FaAdFeedBundle:TiAdFeed')->findOneBy(array('ad_id' => $adId));
            if ($tiAdFeedObj) {
                if ($tiAdFeedObj->getRefSiteId() == 1) {
                    $adFeedObj = $this->getRepository('FaAdFeedBundle:AdFeed')->findOneBy(array('trans_id' => $tiAdFeedObj->getTransId()));
                } else {
                    $adFeedObj = $this->getRepository('FaAdFeedBundle:AdFeed')->findOneBy(array('unique_id' => $tiAdFeedObj->getUniqueId()));
                }
                if ($adFeedObj) {
                    $objAd = $adFeedObj->getAd();
                    $routeManager = $this->container->get('fa_ad.manager.ad_routing');
                    $redirectUrl          = $routeManager->getDetailUrl($objAd);
                    $queryParams = $request->query->all();
                    if (count($queryParams)) {
                        $redirectUrl = $redirectUrl.'?'.http_build_query($queryParams);
                    }
                }
            }
        }

        if ($redirectUrl) {
            $key = md5($request->getClientIp().$request->headers->get('User-Agent'));
            CommonManager::setCacheVersion($this->container, 'ti_url_'.$key, str_replace(array($this->container->getParameter('base_url'), $objAd->getId()), array($this->container->getParameter('ti_base_url'), $adId), $redirectUrl));
            return $this->redirect($redirectUrl, 301);
        } else {
            $catFullSlug = $this->getRepository('FaEntityBundle:Category')->getCategoryByFullSlugBySlug($request->get('category_string'), $this->container);
            if ($catFullSlug && $request->get('location')) {
                $listUrl = $this->container->get('router')->generate('listing_page', array(
                    'location' => $request->get('location'),
                    'page_string' => $catFullSlug,
                    'advertgone' => true,
                ), true);

                return $this->redirect($listUrl, 301);
            } else {
                $url = $this->get('router')->generate('ad_detail_page', array('location' => $request->get('location'), 'category_string' => $request->get('category_string'), 'ad_string' => $request->get('ad_string'), 'id' => $request->get('id')), true);
                return $this->redirect($url, 301);
            }
        }
    }

    /**
     * Handle removed ads.
     *
     * @param integer $adId Ad id.
     *
     * @return mixed
     */
    private function handleRemovedAds($adId)
    {
        $listUrl = null;
        $objAd = $this->getRepository('FaAdBundle:Ad')->find($adId);
        if ($objAd) {
            $adLocations = $this->getRepository('FaAdBundle:AdLocation')->getIdArrayByAdId(array($adId));
            $locationSlug = null;
            $categorySlug = null;
            if (isset($adLocations[$adId])) {
                $adLocationExplode = explode(',', $adLocations[$adId]);
                if (isset($adLocationExplode[1])) {
                    $locationSlug = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getSlugById($adLocationExplode[1], $this->container);
                }
            }

            // if no location slug then set to default location
            if (!$locationSlug) {
                $locationSlug = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getSlugById(LocationRepository::COUNTY_ID, $this->container);
            }

            if ($objAd->getCategory()) {
                $categorySlug = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getFullSlugById($objAd->getCategory()->getId(), $this->container);
            }

            if ($locationSlug && $categorySlug) {
                $this->container->get('session')->getFlashBag()->add('info', $this->get('translator')->trans('It seems this advert is no longer available, why not check out other ads in <i>%category_name%</i>.', array('%category_name%' => $objAd->getCategory()->getName())));
                $listUrl = $this->container->get('router')->generate('listing_page', array(
                        'location' => $locationSlug,
                        'page_string' => $categorySlug,
                        'advertgone' => true,
                    ), true);
            }
        }

        //check in archive ads
        if (!$listUrl) {
            $archiveAdObj = $this->getRepository('FaArchiveBundle:ArchiveAd')->findOneBy(array('ad_main' => $adId));

            if ($archiveAdObj) {
                $adLocations = unserialize($archiveAdObj->getAdLocationData());
                $adDatas = unserialize($archiveAdObj->getAdData());
                $locationSlug = null;
                $categorySlug = null;
                $categoryObj = null;
                if (!empty($adLocations) && isset($adLocations[0]) && isset($adLocations[0]['town_id'])) {
                    $locationSlug = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getSlugById($adLocations[0]['town_id'], $this->container);
                }

                // if no location slug then set to default location
                if (!$locationSlug) {
                    $locationSlug = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getSlugById(LocationRepository::COUNTY_ID, $this->container);
                }

                if (!empty($adDatas) && isset($adDatas['category_id'])) {
                    $categoryObj = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->find($adDatas['category_id']);
                    $categorySlug = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getFullSlugById($adDatas['category_id'], $this->container);
                }

                if ($locationSlug && $categorySlug) {
                    $this->container->get('session')->getFlashBag()->add('info', $this->get('translator')->trans('It seems this advert is no longer available, why not check out other ads in <i>%category_name%</i>.', array('%category_name%' => ($categoryObj ? $categoryObj->getName() : null))));
                    $listUrl = $this->container->get('router')->generate('listing_page', array(
                        'location' => $locationSlug,
                        'page_string' => $categorySlug,
                        'advertgone' => true,
                    ), true);
                }
            }
        }

        return $listUrl;
    }

    /**
     * Show 410 page for archive site.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showArchive410PageAction(Request $request)
    {
        throw new HttpException(410);
    }

    /**
     * Show ad detail seo blocks.
     *
     * @param Request $request     Request object.
     * @param integer $categoryId  Category id.
     * @param String  $location    Location.
     * @param Array   $seoPageRule Seo rule array.
     *
     * @return Response A Response object.
     */
    public function showAdDetailSeoBlocksAction(Request $request, $categoryId, $location = null, $seoPageRule = null, $adLocationName = null)
    {
        // fetch location from cookie.
        $cookieLocation = CommonManager::getLocationDetailFromParamsOrCookie($location, $request, $this->container);
        $parentCategoryIds = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container));
        $rootCategoryId = (isset($parentCategoryIds[0]) ? $parentCategoryIds[0] : null);
        $adlocationId = 0;
        //get Ad location id based on location Name
        if (!is_null($adLocationName)) {
            $adLocObj = $this->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $adLocationName));
            if (!empty($adLocObj)) {
                $adlocationId = $adLocObj->getId();
            }
        }
        
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'));

        $blocks = $this->getAdDetailSeoBlockParams($categoryId, $parentCategoryIds, $seoPageRule, $adlocationId);
        
        if (!empty($blocks)) {
            foreach ($blocks as $solrFieldName => $block) {
                // initialize solr search manager service and fetch data based of above prepared search options
                $this->get('fa.solrsearch.manager')->init('ad', '', $block['data']);
                $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();

                // fetch result set from solr
                $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);
                $facetResult = array_map("get_object_vars", get_object_vars($facetResult));
                
                if ($rootCategoryId == CategoryRepository::JOBS_ID && isset($facetResult[$solrFieldName]) && !count($facetResult[$solrFieldName])) {
                    $seoSearchParams['item__category_id'] = $parentCategoryIds[0];
                    $data = array();
                    // Active ads
                    $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                    $data['query_filters']['item']['category_id'] = $rootCategoryId;
                    $data['facet_fields'] = array(
                        AdSolrFieldMapping::CATEGORY_ID => array(
                            'limit' => 10,
                            'min_count' => 1,
                        )
                    );

                    $blocks[AdSolrFieldMapping::CATEGORY_ID] = array(
                        'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-ad-detail-seo-block'),
                        'search_field_name' => 'item__category_id',
                        'repository'           => 'FaEntityBundle:Category',
                        'data' => $data,
                        'seoSearchParams' => $seoSearchParams,
                    );
                    $solrFieldName = AdSolrFieldMapping::CATEGORY_ID;
                    $this->get('fa.solrsearch.manager')->init('ad', '', $data);
                    if (!empty($cookieLocation)) {
                        if (isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
                            $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocation['latitude'].','.$cookieLocation['longitude']);
                            $this->get('fa.solrsearch.manager')->setGeoDistQuery($geoDistParams);
                        }
                    }
                    $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();

                    // fetch result set from solr
                    $facetResult = $this->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);
                    $facetResult = array_map("get_object_vars", get_object_vars($facetResult));
                }

                if (isset($facetResult[$solrFieldName]) && !empty($facetResult[$solrFieldName])) {
                    if ($rootCategoryId == CategoryRepository::MOTORS_ID && isset($parentCategoryIds[1]) && isset($blocks[$solrFieldName]['first_entry_as_uk']) && $blocks[$solrFieldName]['first_entry_as_uk']) {
                        $seoSearchParams['item__category_id'] = $parentCategoryIds[1];
                        $seoSearchParams['item__location'] = LocationRepository::COUNTY_ID;
                        $searchResultUrl = $this->container->get('fa_ad.manager.ad_routing')->getListingUrl($seoSearchParams);
                        $facetResult[$solrFieldName] = array('0' => array('title' => $this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $parentCategoryIds[1]), 'url' => $searchResultUrl)) + $facetResult[$solrFieldName];
                    }

                    if ((isset($blocks[$solrFieldName]['facet']) && is_array($blocks[$solrFieldName]['facet']) && count($blocks[$solrFieldName]['facet']) == 0) || !isset($blocks[$solrFieldName]['facet'])) {
                        $blocks[$solrFieldName]['facet'] = $facetResult[$solrFieldName];
                        //add location areas
                        if (isset($facetResult[AdSolrFieldMapping::AREA_ID]) && !empty($facetResult[AdSolrFieldMapping::AREA_ID])) {
                            $blocks[$solrFieldName]['facet'] = $blocks[$solrFieldName]['facet'] + $facetResult[AdSolrFieldMapping::AREA_ID];
                        }
                    }
                }
            }
        }

        $parameters = array(
            'blocks'          => $blocks,
        );
        return $this->render('FaAdBundle:Ad:showAdDetailSeoBlocks.html.twig', $parameters);
    }

    /**
     * Get ad detail blocks.
     *
     * @param integer $categoryId        Category id.
     * @param array   $parentCategoryIds Array of parent categories.
     * @param array   $seoPageRule       Seo rule array.
     *
     * @return array
     */
    private function getAdDetailSeoBlockParams($categoryId, $parentCategoryIds, $seoPageRule, $adlocationId = 0)
    {
        $topLinkArray = [];
        $rootCategoryId = (isset($parentCategoryIds[0]) ? $parentCategoryIds[0] : null);
        $blocks = array();
        
        if (count($seoPageRule) && isset($seoPageRule['seo_tool_id'])) {
            $topLinkArray = $this->getRepository('FaContentBundle:SeoToolTopLink')->getTopLinkArrayBySeoToolId($seoPageRule['seo_tool_id'], $this->container);
        }

        if (in_array($rootCategoryId, array(CategoryRepository::FOR_SALE_ID, CategoryRepository::PROPERTY_ID, CategoryRepository::ANIMALS_ID, CategoryRepository::SERVICES_ID))) {
            if (count($parentCategoryIds) >= 3) {
                $lastElementOfCategory = count($parentCategoryIds) - 1;
                $seoSearchParams['item__category_id']         = $parentCategoryIds[$lastElementOfCategory];
                $data = array();
                $data['query_filters']['item']['category_id'] = $rootCategoryId;
                // Active ads
                $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                $data['facet_fields'] = array(
                    AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID => array(
                            'limit' => 10,
                            'min_count' => 1,
                            )
                );

                $blocks[AdSolrFieldMapping::PARENT_CATEGORY_LVL_3_ID] = array(
                    'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-ad-detail-seo-block'),
                    'search_field_name' => 'item__category_id',
                    'repository'           => 'FaEntityBundle:Category',
                    'data' => $data,
                    'seoSearchParams' => $seoSearchParams,
                    'is_top_links' => (count($topLinkArray) ? true : false),
                    'facet' => (count($topLinkArray) ? $topLinkArray : array()),
                );
            } else {
                $lastElementOfCategory = count($parentCategoryIds) - 1;
                $seoSearchParams['item__category_id']         = $parentCategoryIds[$lastElementOfCategory];
                $data = array();
                // Active ads
                $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                $data['query_filters']['item']['category_id'] = $rootCategoryId;
                $data['facet_fields'] = array(
                    AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID => array(
                        'limit' => 10,
                        'min_count' => 1,
                    )
                );

                $blocks[AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID] = array(
                    'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-ad-detail-seo-block'),
                    'search_field_name' => 'item__category_id',
                    'repository'           => 'FaEntityBundle:Category',
                    'data' => $data,
                    'seoSearchParams' => $seoSearchParams,
                    'is_top_links' => (count($topLinkArray) ? true : false),
                    'facet' => (count($topLinkArray) ? $topLinkArray : array()),
                );
            }
            if ($rootCategoryId == CategoryRepository::ANIMALS_ID) {
                if (isset($parentCategoryIds[2]) && !in_array($parentCategoryIds[2], array(CategoryRepository::BIRDS))) {
                    $data = array();
                    $data['query_filters']['item']['category_id'] = (isset($parentCategoryIds[2]) ? $parentCategoryIds[2] : $rootCategoryId);
                    // Active ads
                    $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                    $data['facet_fields'] = array(
                        AdAnimalsSolrFieldMapping::BREED_ID => array(
                            'limit' => 10,
                            'min_count' => 1,
                        )
                    );
                    $blocks[AdAnimalsSolrFieldMapping::BREED_ID] = array(
                        'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-ad-detail-seo-block'),
                        'search_field_name' => 'item_animals__breed_id',
                        'repository'           => 'FaEntityBundle:Entity',
                        'data' => $data,
                        'seoSearchParams' => $seoSearchParams,
                    );
                } else {
                    $data = array();
                    $data['query_filters']['item']['category_id'] = (isset($parentCategoryIds[2]) ? $parentCategoryIds[2] : $rootCategoryId);
                    // Active ads
                    $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                    $data['facet_fields'] = array(
                        AdAnimalsSolrFieldMapping::SPECIES_ID => array(
                            'limit' => 10,
                            'min_count' => 1,
                        )
                    );
                    $blocks[AdAnimalsSolrFieldMapping::SPECIES_ID] = array(
                        'heading' => $this->get('translator')->trans('Top Species', array(), 'frontend-ad-detail-seo-block'),
                        'search_field_name' => 'item_animals__species_id',
                        'repository'           => 'FaEntityBundle:Entity',
                        'data' => $data,
                        'seoSearchParams' => $seoSearchParams,
                    );
                }
            }
        } elseif (in_array($rootCategoryId, array(CategoryRepository::JOBS_ID))) {
            $lastElementOfCategory = count($parentCategoryIds) - 1;
            $seoSearchParams['item__category_id'] = $parentCategoryIds[$lastElementOfCategory];
            $data = array();
            // Active ads
            $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
            $data['query_filters']['item']['category_id'] = $rootCategoryId;
            $data['facet_fields'] = array(
                AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID => array(
                    'limit' => 10,
                    'min_count' => 1,
                )
            );

            $blocks[AdSolrFieldMapping::PARENT_CATEGORY_LVL_2_ID] = array(
                'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-ad-detail-seo-block'),
                'search_field_name' => 'item__category_id',
                'repository'           => 'FaEntityBundle:Category',
                'data' => $data,
                'seoSearchParams' => $seoSearchParams,
                'is_top_links' => (count($topLinkArray) ? true : false),
                'facet' => (count($topLinkArray) ? $topLinkArray : array()),
            );
        } elseif ($rootCategoryId == CategoryRepository::MOTORS_ID) {
            // check for cars and commercial vehicles
            if (isset($parentCategoryIds[1]) && in_array($parentCategoryIds[1], array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID))) {
                $lastElementOfCategory = count($parentCategoryIds) - 1;
                $seoSearchParams['item__category_id']         = $parentCategoryIds[$lastElementOfCategory];
                $data = array();
                // Active ads
                $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                $data['query_filters']['item']['category_id'] = (isset($parentCategoryIds[1]) ? $parentCategoryIds[1] : $rootCategoryId);
                $data['facet_fields'] = array(
                    AdMotorsSolrFieldMapping::CATEGORY_MAKE_ID => array(
                        'limit' => 9,
                        'min_count' => 1,
                    )
                );

                $blocks[AdMotorsSolrFieldMapping::CATEGORY_MAKE_ID] = array(
                    'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-ad-detail-seo-block'),
                    'search_field_name' => 'item__category_id',
                    'repository'           => 'FaEntityBundle:Category',
                    'data' => $data,
                    'seoSearchParams' => $seoSearchParams,
                    'first_entry_as_uk' => true,
                    'is_top_links' => (count($topLinkArray) ? true : false),
                    'facet' => (count($topLinkArray) ? $topLinkArray : array()),
                );
            } elseif (isset($parentCategoryIds[1]) && in_array($parentCategoryIds[1], array(CategoryRepository::BOATS_ID, CategoryRepository::FARM_ID))) {
                $lastElementOfCategory = count($parentCategoryIds) - 1;
                $seoSearchParams['item__category_id']         = $parentCategoryIds[$lastElementOfCategory];
                $data = array();
                // Active ads
                $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                $data['query_filters']['item']['category_id'] = (isset($parentCategoryIds[1]) ? $parentCategoryIds[1] : $rootCategoryId);
                $data['facet_fields'] = array(
                    AdMotorsSolrFieldMapping::MANUFACTURER_ID => array(
                        'limit' => 9,
                        'min_count' => 1,
                    )
                );

                $blocks[AdMotorsSolrFieldMapping::MANUFACTURER_ID] = array(
                    'heading' => $this->get('translator')->trans('Top Manufacturers in UK', array(), 'frontend-ad-detail-seo-block'),
                    'search_field_name' => 'item_motors__manufacturer_id',
                    'repository'           => 'FaEntityBundle:Entity',
                    'data' => $data,
                    'seoSearchParams' => $seoSearchParams,
                    'first_entry_as_uk' => true,
                    'is_top_links' => (count($topLinkArray) ? true : false),
                    'facet' => (count($topLinkArray) ? $topLinkArray : array()),
                );
            } else {
                $lastElementOfCategory = count($parentCategoryIds) - 1;
                $seoSearchParams['item__category_id']         = $parentCategoryIds[$lastElementOfCategory];
                $data = array();
                // Active ads
                $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
                $data['query_filters']['item']['category_id'] = (isset($parentCategoryIds[1]) ? $parentCategoryIds[1] : $rootCategoryId);
                $data['facet_fields'] = array(
                    AdMotorsSolrFieldMapping::MAKE_ID => array(
                        'limit' => 9,
                        'min_count' => 1,
                    )
                );

                $blocks[AdMotorsSolrFieldMapping::MAKE_ID] = array(
                    'heading' => $this->get('translator')->trans('Popular Searches', array(), 'frontend-ad-detail-seo-block'),
                    'search_field_name' => 'item_motors__make_id',
                    'repository'           => 'FaEntityBundle:Entity',
                    'data' => $data,
                    'seoSearchParams' => $seoSearchParams,
                    'first_entry_as_uk' => true,
                    'is_top_links' => (count($topLinkArray) ? true : false),
                    'facet' => (count($topLinkArray) ? $topLinkArray : array()),
                );
            }
        } elseif ($rootCategoryId == CategoryRepository::ADULT_ID || $rootCategoryId == CategoryRepository::COMMUNITY_ID) {
            $lastElementOfCategory = count($parentCategoryIds) - 1;
            $seoSearchParams['item__category_id'] = $parentCategoryIds[$lastElementOfCategory];
        }

        $data = array();
        $data['query_filters']['item']['category_id'] = $seoSearchParams['item__category_id'];
        // Active ads
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        
        //check ad loaction is exist
        if ($rootCategoryId == CategoryRepository::ADULT_ID && $adlocationId !== 0) {
            $seoSearchParams['item__category_id'] = (!empty($parentCategoryIds) && isset($parentCategoryIds[1]))? $parentCategoryIds[1]:null;
            $data['query_filters']['item']['category_id'] = $seoSearchParams['item__category_id'];
            $data['query_filters']['item']['distance'] = CategoryRepository::ADULT_TOP_LOCATION_AT_DETAIL_BY_DISTANCE;
            $data['query_filters']['item']['location'] = $adlocationId."|".CategoryRepository::ADULT_TOP_LOCATION_AT_DETAIL_BY_DISTANCE;
        }
        
        $data['facet_fields'] = array(
            AdSolrFieldMapping::TOWN_ID => array(
                'limit' => 10,
                'min_count' => 1,
            ),
            AdSolrFieldMapping::AREA_ID => array(
                'limit' => 10,
                'min_count' => 1,
            )
        );
        $blocks[AdSolrFieldMapping::TOWN_ID] = array(
            'heading' => $this->get('translator')->trans('Top Locations', array(), 'frontend-ad-detail-seo-block'),
            'search_field_name' => 'item__location',
            'repository'           => 'FaEntityBundle:Location',
            'data' => $data,
            'seoSearchParams' => $seoSearchParams,
        );
        
        return $blocks;
    }

    /**
     * This action is used to remember one click enquire.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function gotItOneClickEnqAjaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new Response();
            $response->headers->setCookie(new Cookie('got_it_one_click_enq', 1, time() + 30 * 86400));
            $response->sendHeaders();
            return new JsonResponse(array('response' => true));
        }

        return new JsonResponse(array('response' => false));
    }


    /**
     * This action is used to send one click enquiry.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function sendOneClickEnqAjaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest() && $request->get('adId')) {
            $objAd = $this->getRepository('FaAdBundle:Ad')->find($request->get('adId'));
            if ($objAd) {
                $isAlreadyEnquired = $this->getRepository('FaMessageBundle:Message')->isAlreadyEnquired($this->getLoggedInUser()->getId(), $objAd->getId());
                if ($isAlreadyEnquired == false) {
                    $objSeller       = $objAd->getUser();
                    $objBuyer        = $this->getLoggedInUser();
                    $adUrl           = $this->container->get('router')->generate('ad_detail_page_by_id', array('id' => $objAd->getId()), true);
                    $adUrlLink       = '<a href="'.$adUrl.'" target="_blank">'.$objAd->getTitle().'</a>';
                    $htmlMessageText = 'Is the '.$adUrlLink.' still available?';
                    $textMessageText = 'Is the '.$objAd->getTitle().' still available?';
                    $objMessage      = new Message();
                    $objMessage      = $this->getRepository('FaMessageBundle:Message')->setMessageDetail($objMessage, null, $objAd, $objBuyer, $objSeller, $request->getClientIp());
                    $objMessage->setSubject($objAd->getTitle());
                    $objMessage->setTextMessage($textMessageText);
                    $objMessage->setHtmlMessage($htmlMessageText);
                    $objMessage->setIsOneclickenqMessage(1);
                    $objMessage->setStatus(0);

                    $this->getEntityManager()->persist($objMessage);
                    $this->getEntityManager()->flush($objMessage);

                    // send message into moderation.
                    try {
                        $this->getRepository('FaMessageBundle:Message')->sendContactIntoModeration($objMessage, $this->container);
                    } catch (\Exception $e) {
                        // No need do take any action as we have cron
                        //  in background to again send the request.
                    }

                    $response = new Response();
                    return new JsonResponse(array('response' => true));
                } else {
                    return new JsonResponse(array('response' => false));
                }
            }
            return new JsonResponse(array('response' => false));
        }

        return new JsonResponse(array('response' => false));
    }

    /**
     * This action is used to send one click enquiry.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function fetchCategoryPathByAdAjaxAction(Request $request)
    {
        $catString = '';
        $delimiter = '-';
        if ($request->isXmlHttpRequest() && $request->get('adId')) {
            $objAd = $this->getRepository('FaAdBundle:Ad')->find($request->get('adId'));
            if ($objAd) {
                $categoryPath = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($objAd->getCategory()->getId(), false, $this->container);
                if ($categoryPath && is_array($categoryPath) && count($categoryPath) > 0) {
                    foreach ($categoryPath as $categoryId => $categoryName) {
                        $catString = $catString . $delimiter . $categoryName;
                    }
                }
            }
        }

        $catString = trim($catString, '-');
        return new JsonResponse(array('categoryText' => $catString));
    }

    /**
     * This action is used for create user half account.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxSetGACookieAction(Request $request)
    {
        $isSet = false;
        if ($request->isXmlHttpRequest() && $request->get('ad_id', null) != null) {
            $response   = new Response();
            $cookieName = $request->get('ad_id').'_ad_detail_ga_tracking';
            $response->headers->setCookie(new Cookie($cookieName, 1, time() + 7 * 86400));
            $response->sendHeaders();
            $isSet = true;
        }
        return new JsonResponse(array('is_set' => $isSet));
    }

    /**
     * This action is used for create user half account.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxSetGAAfterCookieAction(Request $request)
    {
        $isSet = false;
        if ($request->isXmlHttpRequest() && $request->get('ad_id', null) != null) {
            $response   = new Response();
            $cookieName = $request->get('ad_id').'_ad_detail_ga_tracking_after';
            $response->headers->setCookie(new Cookie($cookieName, 1, time() + 7 * 86400));
            $response->sendHeaders();
            $isSet = true;
        }
        return new JsonResponse(array('is_set' => $isSet));
    }
    
    /**
     * This action is used for create user half account.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxEighteenPlusWarnningModelAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response   = new Response();
            $cookieName = $request->get('ad_id').'_ad_detail_ga_tracking_after';
            $parameters['redirectlink'] = null;
            if ($request->get('redirectlink') != null) {
                $parameters['redirectlink'] = $request->get('redirectlink');
            }

            if ($request->get('popupModification') != null && $request->get('popupModification') == 1) {
                $parameters['modification'] = true;
            } else {
                $parameters['for_third_party_link'] = true;
            }
            $htmlContent = $this->renderView('FaFrontendBundle::adultWarnningPopup.html.twig', $parameters);
            return new JsonResponse(array('success' => true, 'htmlContent' => $htmlContent));
        }
        return new Response();
    }
}
