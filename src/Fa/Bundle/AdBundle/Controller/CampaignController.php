<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Fa\Bundle\UserBundle\Entity\User;
use Facebook\FacebookRequest;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdPostManager;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\UserBundle\Event\FormEvent;
use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\UserBundle\Event\FilterUserResponseEvent;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Controller\ThirdPartyLoginController;
use Fa\Bundle\UserBundle\Manager\UserImageManager;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\Form\FormError;
use Fa\Bundle\AdBundle\Repository\CampaignsRepository;
use Fa\Bundle\AdBundle\Repository\PaaLiteFieldRuleRepository;
use Fa\Bundle\AdBundle\Form\PaaLiteRegistrationType;
use Fa\Bundle\AdBundle\Entity\PaaLiteEmailNotification;
use Fa\Bundle\AdBundle\Repository\PaaLiteEmailNotificationRepository;
use Fa\Bundle\UserBundle\Encoder\Sha1PasswordEncoder;
use Fa\Bundle\PaymentBundle\Form\CyberSourceCheckoutType;
use Fa\Bundle\AdBundle\Form\PaaLiteCommonType;
use Fa\Bundle\AdBundle\Form\AdPostCategorySelectType;
use Fa\Bundle\AdBundle\Form\PaaLiteLoginType;
use Fa\Bundle\UserBundle\Form\UserSiteType;
use Fa\Bundle\AdBundle\Entity\AdUserPackage;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageUpsellRepository;

/**
 * This controller is used for ad post management.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class CampaignController extends ThirdPartyLoginController
{
    public function indexAction(Request $request)
    {
        $showLoginPopUp = 1;
        $getCategoryId = 1;
        $getCategoryLevel = 1;
        $showAdLivePopUp = 0;
        $showAdLimitPopUp = 0;
        $getAdPlacedCount = 0;
        $showErrorPopUp=0;
        $showAddToCartPopUp = 0;
        if ($this->isAuth()) {
            $this->container->get('session')->set('paa_skip_login_step', true);
            $showLoginPopUp = 0;
        } else {
            $response = new Response();
            $queryParams = $request->query->all();
            $queryParamKeys = array_keys($queryParams);
            $loginQueryParams = array();
            if (count($queryParamKeys)) {
                foreach ($queryParamKeys as $queryParamKey) {
                    if (in_array($queryParamKey, array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content','AdTitle','Location'))) {
                        $loginQueryParams[$queryParamKey] = (isset($queryParams[$queryParamKey]) ? $queryParams[$queryParamKey] : null);
                        if (isset($queryParams[$queryParamKey])) {
                            unset($queryParams[$queryParamKey]);
                        }
                    }
                }
            }
            $queryString = http_build_query($queryParams);
            $loginQueryString = http_build_query($loginQueryParams);
            $afterLoginUrl = $request->getPathInfo().($queryString ? '?'.$queryString : null);
            $this->getRepository('FaUserBundle:User')->removeUserCookies($response);
            $response->headers->setCookie(new Cookie('frontend_redirect_after_login_path_info', $afterLoginUrl, time() +  3600 * 24 * 7));
            $response->sendHeaders();
        }
        
        $campaign_name = ($request->get('campaign_name'))?$request->get('campaign_name'):($this->container->get('session')->get('campaign_name')?$this->container->get('session')->get('campaign_name'):'');
        $this->container->get('session')->set('campaign_name', $campaign_name);
        $formManager = $this->get('fa.formmanager');
        $form          = $formManager->createForm(PaaLiteCommonType::class, array('campaign_name'=>$campaign_name), array('action' => $this->generateUrl('paa-lite', array('campaign_name'=>$campaign_name))));
        $dispatcher  = $this->container->get('event_dispatcher');
        $transactionJsArr = [];

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            $data = $request->get('fa_paa_lite_common');

            if ($this->container->get('session')->get('redirect_to_cart')==1) {
                $showAddToCartPopUp = 1;
                $this->container->get('session')->remove('redirect_to_cart');
                //return $this->redirectToRoute('show_cart');
            }
            if ($this->container->get('session')->get('show_error_pop_up')==1) {
                $showErrorPopUp = 1;
                $this->container->get('session')->remove('show_error_pop_up');
                $this->container->get('session')->remove('paa-lite-error');
            }
        }

        if ($this->container->get('session')->has('show_ad_live_popup') && $this->container->get('session')->get('show_ad_live_popup')==1) {
            $showAdLivePopUp = 1;
            if ($this->container->get('session')->has('paa_lite_card_code')) {
                $loggedinUser = $this->getLoggedInUser();
                if ($loggedinUser) {
                    $transcations   = $this->getRepository('FaPaymentBundle:Payment')->getTranscationDetailsForGA($this->container->get('session')->get('paa_lite_card_code'), $loggedinUser, false);
                    $transactionJsArr['getTranscationJs'] = CommonManager::getGaTranscationJs($transcations);
                    $transactionJsArr['getItemJs']        = CommonManager::getGaItemJs($transcations);
                    $transactionJsArr['ga_transaction']   = $transcations;
                    if ($this->container->get('session')->has('paalite_payment_success_redirect_url')) {
                        $this->container->get('session')->remove('paalite_payment_success_redirect_url');
                    }
                    $this->container->get('session')->remove('paa_lite_card_code');
                }
            }
            $this->container->get('session')->remove('show_ad_live_popup');
        }
        
        $session = $request->getSession();

        $getPaaLiteFlds = array();
        $getCampaign=array();
        $rootCategory = array();
        $vertical = '';
        $getCampaign = $this->getRepository('FaAdBundle:Campaigns')->getCampaignBySlug($campaign_name);
        $CategoryNxtLevel = 0;
        $getPaaLiteOnlyFlds = array();
        $rootCategoryId = 0;
        $getCatLvlArrs = array();
        $getChildrenForCategory = array();
        $isLastCategory = 0;
        if (!empty($getCampaign)) {
            $user = CommonManager::getLoggedInUser($this->container);

            if ($getCampaign[0]->getFormFillTimes() > 0 || $getCampaign[0]->getFormFillTimes()!=null) {
                $getAdPlacedCount = $this->getRepository('FaAdBundle:Ad')->getAdCountByCampaignUser($getCampaign[0], $user);
            
                if ($getAdPlacedCount >= $getCampaign[0]->getFormFillTimes() && $showAdLivePopUp==0 && $showLoginPopUp == 0) {
                    $showAdLimitPopUp = 1;
                }
            }
            
            $getPaaLiteFlds = $this->getRepository('FaAdBundle:PaaLiteFieldRule')->getAllPaaLiteFields($getCampaign[0]->getId());
            $getCategoryId = $getCampaign[0]->getCategory()->getId();
            $getCategoryLevel = $getCampaign[0]->getCategory()->getLvl();
            $CategoryNxtLevel = $getCategoryLevel+1;
            $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($getCategoryId, $this->container);
            $getChildrenForCategory = $this->getRepository('FaEntityBundle:Category')->getChildrenById($getCategoryId);
            if (empty($getChildrenForCategory)) {
                $isLastCategory = 1;
            }
            if ($rootCategoryId!='') {
                $vertical = CommonManager::getCategoryClassNameById($rootCategoryId);
                $rootCategory = $this->getRepository('FaEntityBundle:Category')->getCategoryArrayById($rootCategoryId, $this->container);
            }

            $getCatPath = array();
            $getCatLvlArrs = array();
            $getCatPath = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($getCategoryId);
            if (!empty($getCatPath)) {
                foreach ($getCatPath as $key=>$value) {
                    $getCatDetail = $this->getRepository('FaEntityBundle:Category')->getCategoryArrayById($key, $this->container);
                    $getCatLvlArrs[$getCatDetail['lvl']] = $getCatDetail['id'];
                }
            }
        } else {
            return $this->render('FaCoreBundle:Exception:error404.html.twig', array('status_text' => 'Page Url Changed'));
        }
        if (!empty($getPaaLiteFlds)) {
            $getPaaLiteOnlyFlds = array();
            foreach ($getPaaLiteFlds as $getPaaLiteFld) {
                $getPaaLiteOnlyFlds[] = $getPaaLiteFld->getPaaLiteField()->getField();
            }
        }
        //for facebook & google login
        $facebookLoginUrl = '';
        $googleLoginUrl = '';

        $facebookRegisterUrl = $this->initFacebook('facebook_paa_lite_register');
        $googleRegisterUrl = $this->initGoogle('google_paa_lite_register');

        //facebook
        $fbManager = $this->get('fa.facebook.manager');
        $fbManager->init('facebook_paa_lite_login', array('fbSuccess' => 1));

        $facebookPermissions = array('email','user_location');
        $facebookLoginUrl = $fbManager->getFacebookHelper()->getLoginUrl($facebookPermissions);

        $session = $request->getSession();

        //google
        $googleManager = $this->get('fa.google.manager');
        $googlePermissions = array('https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/userinfo.email','https://www.googleapis.com/auth/userinfo.profile');
        $googleManager->init($googlePermissions, 'google_paa_lite_login', array('googleSuccess' => 1));
        $googleLoginUrl = $googleManager->getGoogleClient()->createAuthUrl();
        
        $parameters = array(
            'form'             => $form->createView(),
            'Campaign'         => ($getCampaign)?$getCampaign[0]:array(),
            'CategoryId'       => $getCategoryId,
            'CategoryLevel'    => $getCategoryLevel,
            'CategoryNxtLevel' => $CategoryNxtLevel,
            'facebookLoginUrl' => $facebookLoginUrl,
            'googleLoginUrl'   => $googleLoginUrl,
            'facebookRegisterUrl' => $facebookRegisterUrl,
            'googleRegisterUrl'   => $googleRegisterUrl,
            'paa_lite_fields'  => ($getPaaLiteFlds)?$getPaaLiteFlds[0]:array(),
            'paa_lite_fields_only' => $getPaaLiteOnlyFlds,
            'showLoginPopUp'   => $showLoginPopUp,
            'rootCategoryId'   => $rootCategoryId,
            'rootCategory'     => $rootCategory,
            'vertical'         => $vertical,
            'showAdLivePopUp'  => $showAdLivePopUp,
            'showAdLimitPopUp' => $showAdLimitPopUp,
            'getCatLvlArrs'    => $getCatLvlArrs,
            'isLastCategory'   => $isLastCategory,
            'showErrorPopUp'   => $showErrorPopUp,
            'showAddToCartPopUp' => $showAddToCartPopUp,
            'paymentPaaLiteTransactionJs'   => $transactionJsArr,
            'Location'         => ($request->query->get('Location'))? $request->query->get('Location'):'',
        );
        return $this->render('FaAdBundle:Campaign:index.html.twig', $parameters);
    }

    public function ajaxAddToCartAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $deadlockError = '';
        $deadlockRetry = $request->get('deadlockRetry', 0);
        $cybersource3DSecureResponseFlag = false;
        $redirectUrl    = '';
        $gaStr          = '';
        
        if ($request->isXmlHttpRequest()) {
            $cyberSourceManager  = $this->get('fa.cyber.source.manager');
            $loggedinUser     = $this->getLoggedInUser();
            $getBasicAdResult = null;
            $selectedPrintEditions = array();
            $printEditionSelectedFlag = true;
            $selectedPackageId = null;
            $selectedPackagePrintId = null;
            $packageIds = [];
            $availablePackageIds = [];
            $defaultSelectedPrintEditions = [];
            $isAdultAdvertPresent = 0;
            $errorMsg   = null;
            if (!empty($loggedinUser)) {
                $user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
                if (!empty($user)) {
                    $adId             = $this->container->get('session')->get('cart_ad_id');
                    $ad               = $this->getRepository('FaAdBundle:Ad')->find($adId);
                    $categoryId       = $ad->getCategory()->getId();
                    $adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
                    if ($adRootCategoryId == CategoryRepository::ADULT_ID) {
                        $isAdultAdvertPresent = 1;
                    }
                    
                    //get user roles.
                    $systemUserRoles  = $this->getRepository('FaUserBundle:Role')->getRoleArrayByType('C', $this->container);
                    $userRole         = $this->getRepository('FaUserBundle:User')->getUserRole($user->getId(), $this->container);
                    $userRolesArray[] = array_search($userRole, $systemUserRoles);
                    $locationGroupIds = $this->getRepository('FaAdBundle:AdLocation')->getLocationGroupByAdId($adId);
                    $adExpiryDays     = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
                    
                    $packageIds[]     = $this->container->get('session')->get('cart_package_id');
                    //get available fetaured top package
                    $packages = $this->getRepository('FaPromotionBundle:PackageRule')->getPackageByCategoryId($packageIds[0]);
                    //get Print Edition if exist
                    $printEditionLimits = $this->getRepository('FaPromotionBundle:Package')->getPrintEditionLimitForPackages($packageIds);
                    
                    if (!empty($printEditionLimits)) {
                        $defaultSelectedPrintEditions = $this->getRepository('FaAdBundle:AdPrint')->getPrintEditionForAd(max($printEditionLimits), $adId, true, $locationGroupIds);
                        if (count($defaultSelectedPrintEditions)) {
                            $defaultSelectedPrintEditions = array_combine(range(1, count($defaultSelectedPrintEditions)), array_values($defaultSelectedPrintEditions));
                        }
                    }
                    $selectedPrintEditions = $defaultSelectedPrintEditions;
                    //Payment gateway form
                    $formManager = $this->get('fa.formmanager');
                    $form        = $formManager->createForm(CyberSourceCheckoutType::class, array('subscription' => null));
                                            
                    if ('POST' === $request->getMethod()) {
                        $form->handleRequest($request);
                        if ($form->isValid()) {
                            $selectedPackageId = $request->get('package_id', null);
                            
                            $printEditionValues = array();
                            if (isset($printEditionLimits[$selectedPackageId]) && $printEditionLimits[$selectedPackageId]) {
                                for ($editionCntr = 1; $editionCntr <= $printEditionLimits[$selectedPackageId]; $editionCntr++) {
                                    if (!$request->get('print_editions_'.$selectedPackageId.'_'.$editionCntr, null)) {
                                        $printEditionSelectedFlag = false;
                                    }
                                    $printEditionValues[$editionCntr] = $request->get('print_editions_'.$selectedPackageId.'_'.$editionCntr);
                                }
                                
                                $selectedPrintEditions = $printEditionValues;
                                $printEditionValues = array_unique($printEditionValues);
                                if (count($printEditionValues) != $printEditionLimits[$selectedPackageId]) {
                                    $printEditionSelectedFlag = false;
                                    return new JsonResponse(array('error' => 'Please select unique print edition', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));  //select unique print edition
                                }
                            }
                            
                            if ($printEditionSelectedFlag) {
                                $printDurationPrices = $this->getRepository('FaPromotionBundle:PackagePrint')->getPrintDurationForPackages(array($selectedPackageId), true);
                                $selectedPackagePrintId = $request->get('package_print_id_'.$selectedPackageId, null);
                                
                                if (isset($printDurationPrices[$selectedPackageId]) && !in_array($selectedPackagePrintId, $printDurationPrices[$selectedPackageId])) {
                                    return new JsonResponse(array('error' => 'Please select valid print option', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
                                }
                            }
                        
                            $selectedPackageObj = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $selectedPackageId));
                            if ($selectedPackageObj->getDuration()) {
                                $getLastCharacter = substr($selectedPackageObj->getDuration(),-1);
                                $noInDuration = substr($selectedPackageObj->getDuration(),0, -1);
                                if($getLastCharacter=='m') { $adExpiryDays = $noInDuration*28;   }
                                elseif($getLastCharacter=='d') { $adExpiryDays = $noInDuration; }
                                else { $adExpiryDays = $selectedPackageObj->getDuration(); }
                            }
                            //Add to the cart
                            $addCartInfo = $this->addInfoToCart($user->getId(), $adId, $selectedPackageId, $selectedPackagePrintId, $printEditionLimits, $adExpiryDays, $printEditionValues, $request, $categoryId);
                            if ($addCartInfo) {
                                //make it cybersource payment
                                $redirectUrl = $request->headers->get('referer');
                                $this->container->get('session')->set('paa_lite_ad_success', 1);
                                //$this->container->get('session')->set('upgrade_payment_success_redirect_url', $redirectUrl);
                                //$redirectUrl = $this->generateUrl('manage_my_ads_active');
                                $this->container->get('session')->set('show_ad_live_popup', 1);
                                $lastCartCode = $this->container->get('session')->get('paa_lite_card_code');
                                //$this->container->get('session')->set('paalite_payment_success_redirect_url', $redirectUrl.'?transaction_id='.$lastCartCode);
                                $this->container->get('session')->set('paalite_payment_success_redirect_url', $redirectUrl);
                                $this->get('session')->set('upgrade_cybersource_params_'.$loggedinUser->getId(), array_merge($form->getData(), $request->get('fa_payment_cyber_source_checkout')));
                                $htmlContent= array(
                                        'success'       => true,
                                        'redirectUrl'   => $this->generateUrl('process_payment', array('paymentMethod' => PaymentRepository::PAYMENT_METHOD_CYBERSOURCE), true)
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
                                'packages' => $packages,
                                'adExpiryDays' => $adExpiryDays,
                                'adId' => $adId,
                                'purchase' => true,
                                'printEditionSelectedFlag' => $printEditionSelectedFlag,
                                'selectedPackageId' => $selectedPackageId,
                                'printEditionLimits' => $printEditionLimits,
                                'selectedPrintEditions' => $selectedPrintEditions,
                                'defaultSelectedPrintEditions' => $defaultSelectedPrintEditions,
                                'isAdultAdvertPresent' => $isAdultAdvertPresent,
                                'errorMsg' => $errorMsg,
                                'categoryId' => $categoryId,
                                'locationGroupIds' => $locationGroupIds,
                                'form' => $form->createView(),
                                'subscription' => $request->get('subscription'),
                                'gaStr' => $gaStr,
                                'paa_lite_redirect' => 1,
                                'popup_title' => 'Paa Lite',
                        );
                        
                        
                        $htmlContent = $this->renderView('FaAdBundle:Ad:upgradeFeaturedmodalBox.html.twig', $parameters);
                    }
                }
            }
            return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
        } else {
            return new Response();
        }
    }

    /**
     * Upgrade To Featured Ad.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxPaypalPaymentProcessAddToCartAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $redirectToUrl = '';
            $error         = '';
            $htmlContent   = '';
            $deadlockError = '';
            $deadlockRetry = $request->get('deadlockRetry', 0);
            $loggedinUser     = $this->getLoggedInUser();
            $errorMsg   = null;
            $selectedPackageId = $this->container->get('session')->get('cart_package_id');
            $printDurationId = '';
            $printEditionValues = [];
            $packageIds = array($selectedPackageId);
            $printEditionSelectedFlag = true;
            $categoryId = null;
            
            if (!empty($loggedinUser)) {
                $user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
                
                if (!empty($user)) {
                    $adId             = $this->container->get('session')->get('cart_ad_id');
                    $ad               = $this->getRepository('FaAdBundle:Ad')->find($adId);
                    $categoryId       = $ad->getCategory()->getId();

                    $adExpiryDays     = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
                    $printEditionLimits = $this->getRepository('FaPromotionBundle:Package')->getPrintEditionLimitForPackages($packageIds);
                    
                    if (isset($printEditionLimits[$selectedPackageId]) && $printEditionLimits[$selectedPackageId]) {
                        for ($editionCntr = 1; $editionCntr <= $printEditionLimits[$selectedPackageId]; $editionCntr++) {
                            if (!$request->get('print_editions_'.$selectedPackageId.'_'.$editionCntr, null)) {
                                $printEditionSelectedFlag = false;
                            }
                            $printEditionValues[$editionCntr] = $request->get('print_editions_'.$selectedPackageId.'_'.$editionCntr);
                        }
                        
                        $selectedPrintEditions = $printEditionValues;
                        $printEditionValues = array_unique($printEditionValues);
                        if (count($printEditionValues) != $printEditionLimits[$selectedPackageId]) {
                            $printEditionSelectedFlag = false;
                            return new JsonResponse(array('error' => 'Please select unique print edition', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));  //select unique print edition
                        }
                    }
                    
                    if ($printEditionSelectedFlag) {
                        $printDurationPrices = $this->getRepository('FaPromotionBundle:PackagePrint')->getPrintDurationForPackages(array($selectedPackageId), true);
                        $selectedPackagePrintId = $request->get('package_print_id_'.$selectedPackageId, null);
                        
                        if (isset($printDurationPrices[$selectedPackageId]) && !in_array($selectedPackagePrintId, $printDurationPrices[$selectedPackageId])) {
                            return new JsonResponse(array('error' => 'Please select valid print option', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
                        }
                    }
                    
                    $selectedPackageObj = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $selectedPackageId));
                    if ($selectedPackageObj->getDuration()) {
                        $getLastCharacter = substr($selectedPackageObj->getDuration(),-1);
                        $noInDuration = substr($selectedPackageObj->getDuration(),0, -1);
                        if($getLastCharacter=='m') { $adExpiryDays = $noInDuration*28;   }
                        elseif($getLastCharacter=='d') { $adExpiryDays = $noInDuration; }
                        else { $adExpiryDays = $selectedPackageObj->getDuration(); }
                    }
                    
                    //Add to the cart
                    $addCartInfo = $this->addInfoToCart($user->getId(), $adId, $selectedPackageId, $selectedPackagePrintId, $printEditionLimits, $adExpiryDays, $printEditionValues, $request, $categoryId);
                    if ($addCartInfo) {
                        $redirectUrl = $request->headers->get('referer');
                        //$this->container->get('session')->set('show_ad_live_popup', 1);
                        $this->container->get('session')->set('paa_lite_ad_success', 1);
                        //$redirectUrl = $this->generateUrl('manage_my_ads_active');
                        $lastCartCode = $this->container->get('session')->get('paa_lite_card_code');
                        $this->container->get('session')->set('show_ad_live_popup', 1);
                        $this->container->get('session')->set('paalite_payment_success_redirect_url', $redirectUrl);
                        //$this->container->get('session')->set('upgrade_payment_success_redirect_url', $redirectUrl);
                        $htmlContent= array(
                                'success'       => true,
                                'redirectUrl'   => $redirectUrl
                        );
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
    public function amazonPaymentProcessAddToCartAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $redirectToUrl = '';
            $error         = '';
            $htmlContent   = '';
            $deadlockError = '';
            $deadlockRetry = $request->get('deadlockRetry', 0);
            $loggedinUser     = $this->getLoggedInUser();
            $errorMsg   = null;
            $selectedPackageId = $this->container->get('session')->get('cart_package_id');
            $printDurationId = '';
            $printEditionValues = [];
            $packageIds = array($selectedPackageId);
            $printEditionSelectedFlag = true;
            $categoryId = null;
            
            if (!empty($loggedinUser)) {
                $user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
                if (!empty($user)) {
                    $adId             = $this->container->get('session')->get('cart_ad_id');
                    $ad               = $this->getRepository('FaAdBundle:Ad')->find($adId);
                    $categoryId       = $ad->getCategory()->getId();
                    $adExpiryDays     = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
                    $printEditionLimits = $this->getRepository('FaPromotionBundle:Package')->getPrintEditionLimitForPackages($packageIds);
                    
                    if (isset($printEditionLimits[$selectedPackageId]) && $printEditionLimits[$selectedPackageId]) {
                        for ($editionCntr = 1; $editionCntr <= $printEditionLimits[$selectedPackageId]; $editionCntr++) {
                            if (!$request->get('print_editions_'.$selectedPackageId.'_'.$editionCntr, null)) {
                                $printEditionSelectedFlag = false;
                            }
                            $printEditionValues[$editionCntr] = $request->get('print_editions_'.$selectedPackageId.'_'.$editionCntr);
                        }
                        
                        $selectedPrintEditions = $printEditionValues;
                        $printEditionValues = array_unique($printEditionValues);
                        if (count($printEditionValues) != $printEditionLimits[$selectedPackageId]) {
                            $printEditionSelectedFlag = false;
                            return new JsonResponse(array('error' => 'Please select unique print edition', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));  //select unique print edition
                        }
                    }
                    
                    if ($printEditionSelectedFlag) {
                        $printDurationPrices = $this->getRepository('FaPromotionBundle:PackagePrint')->getPrintDurationForPackages(array($selectedPackageId), true);
                        $selectedPackagePrintId = $request->get('package_print_id_'.$selectedPackageId, null);
                        
                        if (isset($printDurationPrices[$selectedPackageId]) && !in_array($selectedPackagePrintId, $printDurationPrices[$selectedPackageId])) {
                            return new JsonResponse(array('error' => 'Please select valid print option', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
                        }
                    }
                    
                    $selectedPackageObj = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $selectedPackageId));
                    if ($selectedPackageObj->getDuration()) {
                        $getLastCharacter = substr($selectedPackageObj->getDuration(),-1);
                        $noInDuration = substr($selectedPackageObj->getDuration(),0, -1);
                        if($getLastCharacter=='m') { $adExpiryDays = $noInDuration*28;   }
                        elseif($getLastCharacter=='d') { $adExpiryDays = $noInDuration; }
                        else { $adExpiryDays = $selectedPackageObj->getDuration(); }
                    }
                    //Add to the cart
                    $addCartInfo = $this->addInfoToCart($user->getId(), $adId, $selectedPackageId, $selectedPackagePrintId, $printEditionLimits, $adExpiryDays, $printEditionValues, $request, $categoryId);
                    if ($addCartInfo) {
                        $redirectUrl = $request->headers->get('referer');
                        //$this->container->get('session')->set('show_ad_live_popup', 1);
                        $this->container->get('session')->set('paa_lite_ad_success', 1);
                        //$redirectUrl = $this->generateUrl('manage_my_ads_active');
                        $lastCartCode = $this->container->get('session')->get('paa_lite_card_code');
                        $this->container->get('session')->set('show_ad_live_popup', 1);
                        $this->container->get('session')->set('paalite_payment_success_redirect_url', $redirectUrl);
                        //$this->container->get('session')->set('upgrade_payment_success_redirect_url', $redirectUrl);
                        $htmlContent= array(
                                'success'       => true,
                                'redirectUrl'   => $redirectUrl
                        );
                    }
                    
                    return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
                }
            }
        }
    }

    private function addInfoToCart($userId, $adId, $selectedPackageId, $selectedPackagePrintId, $printEditionLimits, $adExpiryDays, $printEditionValues, $request, $categoryId)
    {
        //Add to ad user package
        $adUserPackage = new AdUserPackage();
        
        // find & set package
        $selpackage = $this->getRepository('FaPromotionBundle:Package')->find($selectedPackageId);
        $adUserPackage->setPackage($selpackage);
        
        // set ad
        $adMain = $this->getRepository('FaAdBundle:AdMain')->find($adId);
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
        $user = $this->getRepository('FaUserBundle:User')->find($userId);
        $adUserPackage->setAdMain($adMain);
        $adUserPackage->setAdId($adId);
        $adUserPackage->setStatus(AdUserPackageRepository::STATUS_ACTIVE);
        $adUserPackage->setStartedAt(time());
        if ($selpackage->getDuration()) {
            $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($selpackage->getDuration()));
        } elseif ($ad) {
            $expirationDays = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
            $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
        }
        
        // set user
        if ($user) {
            $adUserPackage->setUser($user);
        }
        
        $adUserPackage->setPrice($selpackage->getPrice());
        $adUserPackage->setDuration($selpackage->getDuration());
        $this->container->get('doctrine')->getManager()->persist($adUserPackage);
        $this->container->get('doctrine')->getManager()->flush();
        
        foreach ($selpackage->getUpsells() as $upsell) {
            $this->addAdUserPackageUpsell($ad, $adUserPackage, $upsell);
        }
        
        
        //Add to the cart
        $cart            = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container, false, false, false, true);
        $cartDetails     = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        if ($cartDetails) {
            $adCartDetails   = $this->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartIdAndAdId($cart->getId(), $adId);
            if ($adCartDetails) {
                $adCartDetailValue = unserialize($adCartDetails[0]->getValue());
            }
        }
        
        
        $this->container->get('session')->set('paa_lite_card_code', $cart->getCartCode());
        //get Package Detail
        $selectedPackageObj = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $selectedPackageId));
        $selectedPackagePrint = null;
        
        $privateUserAdParams = $this->getRepository('FaAdBundle:Ad')->getPrivateUserPostAdParams($userId, $categoryId, $adId, $this->container);
        
        //check if cart is empty and package is free then process ad
        $selectedPackage = $this->getRepository('FaPromotionBundle:Package')->find($selectedPackageId);
        
        //remove if same ad is in cart.
        if (count($cartDetails) == 1 && $cartDetails[0]['ad_id'] == $adId) {
            unset($cartDetails[0]);
        }
        
        return $this->addAdPackage($adId, $selectedPackageId, $adExpiryDays, $selectedPackagePrintId, false, $printEditionValues, $privateUserAdParams);
    }
    
    /**
     * Add ad user package upsell
     *
     * @param object $ad
     * @param object $adUserPackage
     * @param object $upsell
     */
    protected function addAdUserPackageUpsell($ad, $adUserPackage, $upsell)
    {
        $adId = $ad->getId();
        $adUserPackageUpsellObj = $this->getRepository('FaAdBundle:AdUserPackageUpsell')->findOneBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId(), 'status' => 1, 'upsell' => $upsell->getId()));
        if (!$adUserPackageUpsellObj) {
            $adUserPackageUpsell = new AdUserPackageUpsell();
            $adUserPackageUpsell->setUpsell($upsell);
            
            // set ad user package id.
            if ($adUserPackage) {
                $adUserPackageUpsell->setAdUserPackage($adUserPackage);
            }
            
            // set ad
            $adMain = $this->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackageUpsell->setAdMain($adMain);
            $adUserPackageUpsell->setAdId($adId);
            
            $adUserPackageUpsell->setValue($upsell->getValue());
            $adUserPackageUpsell->setValue1($upsell->getValue1());
            $adUserPackageUpsell->setDuration($upsell->getDuration());
            $adUserPackageUpsell->setStatus(1);
            $adUserPackageUpsell->setStartedAt(time());
            if ($upsell->getDuration()) {
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($upsell->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }
            
            $this->container->get('doctrine')->getManager()->persist($adUserPackageUpsell);
            $this->container->get('doctrine')->getManager()->flush();
        }
    }
    
    /**
     * Assign ad package.
     *
     * @param integer $adId                   Ad id.
     * @param integer $packageId              Package id.
     * @param integer $adExpiryDays           Ad expiry days.
     * @param integer $selectedPackagePrintId Print duration id.
     * @param integer $type                   Promote or Repost.
     * @param integer $activeAdUserPackageId  Active ad user packge id.
     * @param boolean $addAdToModeration      Need to send ad for moderation or not.
     * @param array   $printEditionValues     Print edition array.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function addAdPackage($adId, $packageId, $adExpiryDays, $selectedPackagePrintId, $addAdToModeration = false, $printEditionValues = array(), $privateUserAdParams)
    {
        $ad      = $this->getRepository('FaAdBundle:Ad')->find($adId);
        $package = $this->getRepository('FaPromotionBundle:Package')->find($packageId);
        
        $response = $this->checkIsValidAdUser($ad->getUser()->getId());
        if ($response !== true) {
            return $response;
        }
        
        $this->getRepository('FaPaymentBundle:Cart')->addPackageToCart($this->getLoggedInUser()->getId(), $adId, $packageId, $this->container, true, $adExpiryDays, $selectedPackagePrintId, 'promote', null, $addAdToModeration, null, $printEditionValues, null, null, $privateUserAdParams);
        return true;
    }

    /**
     * Ad post first step action.
     *
     * @param Request $request
     */
    public function ajaxFirstStepMotorsRegNoFieldsAction(Request $request)
    {
        $categoryId  = $request->get('categoryId');
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdPostCategorySelectType::class, array('categoryId' => $categoryId));

        $parameters  = array(
            'form' => $form->createView(),
            'categoryId' => $categoryId,
        );

        return $this->render('FaAdBundle:AdPost:ajaxFirstStepMotorsRegNofields.html.twig', $parameters);
    }

    /**
     * login action.
     *
     * @param Request $request
     */
    public function ajaxPaaLiteLoginAction(Request $request)
    {
        $error = '';
        $redirectToUrl = '';
        $tempUserId = CommonManager::generateHash();

        //if has user info in session then remove it
        if ($this->container->get('session')->has('paa_lite_user_info')) {
            $this->container->get('session')->remove('paa_lite_user_info');
        }
        $this->removeSession('tempUserIdAPL');
        $this->removeSession('tempUserIdREGPL');
        $dispatcher  = $this->container->get('event_dispatcher');
        
        if ($request->isXmlHttpRequest() && 'POST' === $request->getMethod()) {
            $formManager = $this->get('fa.formmanager');
            $form          = $formManager->createForm(PaaLiteLoginType::class);
            $form->handleRequest($request);
            $event = new FormEvent($form, $request);
            //$data = $request->get('fa_paa_lite_login');
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            
            if ($request->request->get('email_user')==1 && $username && $password) {
                $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('username' => $username));
                if (!$user) {
                    $error =  $this->get('translator')->trans('Invalid email or password.', array(), 'registration');
                    return new JsonResponse(array('error' => $error));
                } elseif ($user && (!$user->getStatus() || $user->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID)) {
                    $error =  $this->get('translator')->trans('Your status is not active.', array(), 'registration');
                    return new JsonResponse(array('error' => $error));
                } else {
                    $factory = $this->container->get('security.encoder_factory');
                    $encoder = $factory->getEncoder($user);
                    if (!$encoder->isPasswordValid($user->getPassword(), $password, null)) {
                        // check with SHA1
                        $encoder = new Sha1PasswordEncoder();
                        if (!$encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
                            $error =  $this->get('translator')->trans('Invalid password.', array(), 'registration');
                            return new JsonResponse(array('error' => $error));
                        } else {
                            return new JsonResponse(array('error' => ''));
                        }
                    } else {
                        return new JsonResponse(array('error' => ''));
                    }
                }
            } else {
                $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('username' => $username));
                if (!$this->container->get('session')->has('tempUserIdPL')) {
                    $this->container->get('session')->set('tempUserIdPL', $tempUserId);
                }

                $token = new UsernamePasswordToken(
                    $user,
                    null,
                    'main',
                        ($user->getRoles() ? $user->getRoles() : [])
                );
                $this->get("security.token_storage")->setToken($token);
                $redirectToUrl = $request->getUri();

                //now dispatch the login event
                $event = new InteractiveLoginEvent($request, $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
                $response = $this->redirect($redirectToUrl);
                return  $response;
            }
        } else {
            return new Response();
        }
    }
    

    /**
     * Ajax Paa Lite registration action.
     *
     * @param Request $request
     */
    public function ajaxPaaLiteRegistrationAction(Request $request)
    {
        $error = '';
        $redirectToUrl = '';
        $tempUserId = CommonManager::generateHash();

        //if has user info in session then remove it
        if ($this->container->get('session')->has('paa_lite_user_info')) {
            $this->container->get('session')->remove('paa_lite_user_info');
        }

        $this->removeSession('tempUserIdAPL');
        $this->removeSession('tempUserIdREGPL');

        $givenemail = $request->request->get('email');
        if ($request->isXmlHttpRequest() && 'POST' === $request->getMethod()) {
            //$formData = $request->get('fa_paa_lite_register');
            if ($request->request->get('email_check')==1 && $request->request->get('email')) {
                //check if user already exists
                $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $request->request->get('email')));
                if ($user) {
                    $error =  $this->get('translator')->trans('An account with this email address already exists.', array(), 'registration');
                    return new JsonResponse(array('error' => $error));
                } else {
                    return new JsonResponse(array('error' => ''));
                }
            } elseif ($givenemail) {
                // check if email address is of half account then, update that account, no need to do new entry.
                $halfAccount = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $givenemail));
                if ($halfAccount) {
                    $error =  $this->get('translator')->trans('An account with this email address already exists.', array(), 'registration');
                    $user = $halfAccount;
                } else {
                    $user = $this->setDefaultValueForUser('paa_lite_user_info');
                }
            } else {
                $error =  $this->get('translator')->trans('Error in registration.', array(), 'registration');
                return new JsonResponse(array('error' => $error));
            }
           

            $formManager = $this->get('fa.formmanager');
            $form          = $formManager->createForm(PaaLiteRegistrationType::class, $user);
            $dispatcher  = $this->container->get('event_dispatcher');

            if ($user && !$error) {
                $user->setEmail($givenemail);
                $user->setUsername($givenemail);
                $user->setIsEmailAlertEnabled(1);
                $sellerRole = $this->getRepository('FaUserBundle:Role')->findOneBy(array('name' => RoleRepository::ROLE_SELLER));
                $user->addRole($sellerRole);
                $user->setRole($sellerRole);
                $user->setViaPaaLite(1);
                //set user password
                $user->setPassword(md5($givenemail));

                //set user status
                $userActiveStatus = $this->getRepository('FaEntityBundle:Entity')->find(EntityRepository::USER_STATUS_ACTIVE_ID);
                $user->setStatus($userActiveStatus);

                // set guid
                $user->setGuid(CommonManager::generateGuid($givenemail));

                $user = $formManager->save($user);
                $redirectToUrl = $request->getUri();
                $event = new FormEvent($form, $request);
                //$dispatcher->dispatch(UserEvents::REGISTRATION_SUCCESS, $event);
                //$user = $formManager->save($user);

                if (!$this->container->get('session')->has('tempUserIdREGPL')) {
                    $this->container->get('session')->set('tempUserIdREGPL', $tempUserId);
                }

                $paaLiteEmailNotification = new PaaLiteEmailNotification();
                $paaLiteEmailNotification->setUser($user);
                $paaLiteEmailNotification->setCreatedAt(time());

                if ($this->container->get('session')->has('tempUserIdREGPL')) {
                    $paaLiteEmailNotification->setIsPaaLiteRegisteredUser(1);
                }
                $paaLiteEmailNotification->setIsRegisteredMailSent(0);
                $paaLiteEmailNotification->setIsRegisterationNotificationSent(0);


                $this->container->get('doctrine')->getManager()->persist($paaLiteEmailNotification);
                $this->container->get('doctrine')->getManager()->flush($paaLiteEmailNotification);


                $sendRegEmail = $this->getRepository('FaUserBundle:User')->sendCompleteRegistrationEmail($user, $paaLiteEmailNotification, $this->container);
                $sendRegNotification = $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('complete_registration', null, $user->getId());
                $paaLiteEmailNotification->setIsRegisteredMailSent(1);
                $paaLiteEmailNotification->setIsRegisterationNotificationSent(1);


                $this->container->get('doctrine')->getManager()->persist($paaLiteEmailNotification);
                $this->container->get('doctrine')->getManager()->flush($paaLiteEmailNotification);


                $token = new UsernamePasswordToken(
                    $user,
                    null,
                    'main',
                    $user->getRoles()
                );
                $this->get("security.token_storage")->setToken($token);

                //now dispatch the login event
                $event = new InteractiveLoginEvent($request, $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                $response = $this->redirect($redirectToUrl);
                $dispatcher->dispatch(UserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));
                return  $response;
            } else {
                $error =  $this->get('translator')->trans('Error in registration.', array(), 'registration');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl));
        } else {
            return new Response();
        }
    }

    /**
     * Facebook login action.
     *
     * @param Request $request
     */
    public function facebookPaaLiteLoginAction(Request $request)
    {
        //$this->removeSession('paa_lite_user_info');
        //$this->removeSession('tempUserIdAPL');
        //$this->removeSession('tempUserIdREGPL');

        $campaign_name = $this->container->get('session')->get('campaign_name');
        $redirectAfterLoginUrl = htmlspecialchars_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'));
        $response = $this->processFacebook($request, 'facebook_paa_lite_login', 'paa-lite', true, null, false, $redirectAfterLoginUrl);

        if (is_array($response)) {
            $this->container->get('session')->set('paa_lite_user_info', $response);
            return $this->redirect();
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Facebook (First Name, Last Name, Email).', array(), 'frontend-register'), 'paa-lite', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$campaign_name)));
        } else {
            return $response;
        }
    }

    /**
     * Google login action.
     *
     * @param Request $request
     */
    public function googlePaaLiteLoginAction(Request $request)
    {
        //$this->removeSession('paa_lite_user_info');
        //$this->removeSession('tempUserIdAPL');
        //$this->removeSession('tempUserIdREGPL');
        $redirectAfterLoginUrl = htmlspecialchars_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'));
        $campaign_name = $this->container->get('session')->get('campaign_name');
        $response = $this->processGoogle($request, 'google_paa_lite_login', 'paa-lite', false, true, null, $redirectAfterLoginUrl);
        if (is_array($response)) {
            $this->container->get('session')->set('paa_lite_user_info', $response);
            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$campaign_name)));
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Google (First Name, Last Name, Email).', array(), 'frontend-paa-lite-login'), 'paa-lite', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$campaign_name)));
        } else {
            return $response;
        }
    }

    /**
     * This action is used for registration through facebook.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function facebookPaaLiteRegisterAction(Request $request)
    {
        //$this->removeSession('paa_lite_user_info');
        //$this->removeSession('tempUserIdAPL');
        //$this->removeSession('tempUserIdREGPL');

        $campaign_name = $this->container->get('session')->get('campaign_name');
        $redirectAfterLoginUrl = htmlspecialchars_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'));
        $response = $this->processFacebook($request, 'facebook_paa_lite_register', 'paa-lite', true, null, false, $redirectAfterLoginUrl);

        if (is_array($response)) {
            $this->container->get('session')->set('paa_lite_user_info', $response);
            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$campaign_name)));
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of the fields required to connect to Facebook is missing.', array(), 'frontend-paa-lite-register'), 'paa-lite', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$campaign_name)));
        } else {
            return $response;
        }
    }

    /**
     * This action is used for registration through google.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function googlePaaLiteRegisterAction(Request $request)
    {
        //$this->removeSession('paa_lite_user_info');
        //$this->removeSession('tempUserIdAPL');
        //$this->removeSession('tempUserIdREGPL');

        $campaign_name = $this->container->get('session')->get('campaign_name');
        $redirectAfterLoginUrl = htmlspecialchars_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'));
        $response = $this->processGoogle($request, 'google_paa_lite_register', 'paa-lite', false, true, null, $redirectAfterLoginUrl);

        if (is_array($response)) {
            $this->container->get('session')->set('paa_lite_user_info', $response);
            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$campaign_name)));
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Google (First Name, Last Name, Email).', array(), 'frontend-paa-lite-register'), 'paa-lite', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$campaign_name)));
        } else {
            return $response;
        }
    }

    /**
     * Add user business detail page after save draft ad if category is adult or sevices selected.
     *
     * @param Request $request
     */
    public function addUserBusinessDetailsAction(Request $request)
    {
        $response = $this->validateStepAndRedirect('add_user_business_details');
        if ($response !== false) {
            return $response;
        }

        $user     = $this->container->get('security.token_storage')->getToken()->getUser();
        $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
        if (!$userSite) {
            $userSite = new UserSite();
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserSiteType::class, $userSite, array('action' => $this->generateUrl('add_user_business_details')));

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                return $this->redirect($this->generateUrl('ad_post_fourth_step'));
            }
        } else {
            $form->get('company_name')->setData($user->getBusinessName());
            if (!$form->getData()->getId()) {
                $form->get('phone1')->setData($user->getPhone());
            }
        }

        return $this->render('FaAdBundle:AdPost:userBusinessDetails.html.twig', array('form' => $form->createView()));
    }

    /**
     * Get price suggestion based on ad title and selected category.
     *
     * @param Request $request
     */
    public function ajaxPriceSuggestionAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $categoryId = $request->get('category_id');
            $title      = $request->get('title');
            $parameters = $this->getRepository('FaAdBundle:Ad')->getPaaSimilarAdverts($this->container, $categoryId, $title, 1, 1);

            // Get lower and upper price suggestion
            if (isset($parameters['totalAds']) && $parameters['totalAds'] >= 5) {
                $lower = round($parameters['totalAds'] * 0.25);
                $upper = round($parameters['totalAds'] * 0.75);

                $lowerPrice = $this->getRepository('FaAdBundle:Ad')->getPriceSuggestion($this->container, $categoryId, $title, 1, 1, ($lower - 1));
                $upperPrice = $this->getRepository('FaAdBundle:Ad')->getPriceSuggestion($this->container, $categoryId, $title, 1, 1, ($upper - 1));

                $priceSuggestion = array(
                     'lowerPrice' => $lowerPrice,
                     'upperPrice' => $upperPrice,
                 );

                $parameters = $parameters + $priceSuggestion;
            }

            return $this->render('FaAdBundle:AdPost:ajaxPriceSuggestion.html.twig', $parameters);
        }

        return $this->render('FaAdBundle:AdPost:ajaxPriceSuggestion.html.twig');
    }

    /**
     * Get similar adverts based on ad title and selected category.
     *
     * @param Request $request
     */
    public function ajaxSimilarAdvertsAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $categoryId = $request->get('category_id');
            $title      = $request->get('title');
            $page       = $request->get('page', 1);
            $parameters = $this->getRepository('FaAdBundle:Ad')->getPaaSimilarAdverts($this->container, $categoryId, $title, $page);
            $parameters = $parameters + array('page' => $page);

            if ($page == 1) {
                return $this->render('FaAdBundle:AdPost:ajaxSimilarAdverts.html.twig', $parameters);
            } else {
                return $this->render('FaAdBundle:AdPost:listSimilarAdverts.html.twig', $parameters);
            }
        }

        return $this->render('FaAdBundle:AdPost:ajaxSimilarAdverts.html.twig');
    }

    /**
     * Get category wise ad post form name.
     *
     * @param integer $categoryId Category id.
     * @param string  $step       Step.
     *
     */
    private function getFormName($categoryId, $step)
    {
        $formName      = '';
        $categoryName  = $this->getRootCategoryName($categoryId);

        if ($categoryName && $step) {
            $formName = 'fa_paa_'.$step.'_step_'.$categoryName;
        }

        return $formName;
    }

    /**
     * Get root category name by lowercaseing it.
     *
     * @param integer $categoryId Category id.
     *
     * @return mixed
     *
     */
    private function getRootCategoryName($categoryId)
    {
        $categoryName = $this->getRepository('FaEntityBundle:Category')->getRootCategoryName($categoryId, $this->container);

        if ($categoryName) {
            return $categoryName;
        }

        return null;
    }

    /**
     * Get category wise ad post form name.
     *
     * @param integer $categoryId Category id.
     * @param string  $step       Step.
     *
     */
    private function getTemplateName($categoryId, $step)
    {
        $templateName  = '';
        $categoryName = $this->getRepository('FaEntityBundle:Category')->getRootCategoryName($categoryId, $this->container, true);

        if ($categoryName && $step) {
            $templateName = 'FaAdBundle:AdPost:'.$step.'Step'.$categoryName.'.html.twig';
        }

        return $templateName;
    }

    /**
     * Set default value from either facebook or google in
     * user object.
     *
     * @param string $sessionName
     *
     * @return object
     */
    protected function setDefaultValueForUser($sessionName)
    {
        $user = new User();
        $sessionData = $this->container->get('session')->get($sessionName, array());
        if (count($sessionData) > 0) {
            if (isset($sessionData['user_email'])) {
                $user->setEmail($sessionData['user_email']);
            }
            if (isset($sessionData['user_first_name'])) {
                $user->setFirstName($sessionData['user_first_name']);
            }
            if (isset($sessionData['user_last_name'])) {
                $user->setLastName($sessionData['user_last_name']);
            }
        }

        return $user;
    }

    public function moveUserLogo($user)
    {
        $webPath      = $this->container->get('kernel')->getRootDir().'/../web';
        $orgImageName = $this->container->get('session')->get('tempUserIdAP');
        $isCompany    = false;
        $imageObj     = null;
        $userId       = $user->getId();

        $userRoleName = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);

        if ($userRoleName == RoleRepository::ROLE_BUSINESS_SELLER || $userRoleName == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            $isCompany = true;
        }

        if ($isCompany) {
            $imagePath = $this->container->getParameter('fa.company.image.dir').'/'.CommonManager::getGroupDirNameById($userId, 5000);
            $imageObj  = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userId));
            CommonManager::createGroupDirectory($webPath.DIRECTORY_SEPARATOR.$this->container->getParameter('fa.company.image.dir'), $userId, 5000);
        } else {
            $imagePath = $this->container->getParameter('fa.user.image.dir').'/'.CommonManager::getGroupDirNameById($userId, 5000);
            $imageObj  = $user;
            CommonManager::createGroupDirectory($webPath.DIRECTORY_SEPARATOR.$this->container->getParameter('fa.user.image.dir'), $userId, 5000);
        }

        // Check if user site entry not found then create first
        if (!$imageObj && $isCompany) {
            $imageObj = new UserSite();
            $imageObj->setUser($user);
        }

        if ($isCompany) {
            $imageObj->setPath($imagePath);
        } else {
            $imageObj->setImage($imagePath);
        }

        rename($webPath.'/uploads/tmp/'.$orgImageName.'.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'.jpg');
        rename($webPath.'/uploads/tmp/'.$orgImageName.'_org.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'_org.jpg');
        rename($webPath.'/uploads/tmp/'.$orgImageName.'_original.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'_original.jpg');

        $userImageManager = new UserImageManager($this->container, $userId, $imagePath, $isCompany);
        $userImageManager->createThumbnail();
    }

    /**
     * Edit from paa fourth step
     *
     * @param Request $request
     */
    public function ajaxEditFromPaaFourthStepAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $this->container->get('session')->set('paa_edit_url', $request->get('url'));
            return new JsonResponse(array('result' => true));
        }

        return new Response();
    }

   

    /**
     * This action is used to remember draft ad popup is opened in last one hour in cookies.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function openDraftAdPopupAjaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new Response();
            $response->headers->setCookie(new Cookie('draft_ad_popup', 1, time() + (3600 * 1)));
            $response->sendHeaders();
            return new JsonResponse(array('response' => true));
        }

        return new JsonResponse(array('response' => false));
    }

    /**
     * Get step data to render on template.
     *
     * @return array
     */
    public function getMotorRegNoFields()
    {
        return array(
            'has_reg_no',
            'reg_no',
            'colour_id_autocomplete',
            'colour_id_dimension_id',
            'colour_id',
            'body_type_id',
            'reg_year',
            'fuel_type_id',
            'transmission_id',
            'engine_size',
            'no_of_doors',
            'no_of_seats',
            'fuel_economy',
            '062mph',
            'top_speed',
            'ncap_rating',
            'co2_emissions',
            'first_step_ordered_fields',
        );
    }
}
