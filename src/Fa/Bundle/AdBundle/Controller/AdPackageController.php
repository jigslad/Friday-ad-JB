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

use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\EntityBundle\Entity\LocationGroupLocation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This controller is used for ad package management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdPackageController extends CoreController
{
    /**
     * Ad package purchase.
     *
     * @param integer $adId
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function purchaseAdPackageAction($adId, Request $request)
    {
        /**
         * @var Ad $ad
         */
        if ($this->isAuth()) {
            $this->container->get('session')->set('paa_skip_login_step', true);
        }
        if (!preg_match('/^[-+]?[1-9]\d*$/', $adId)) {
            return $this->render('FaCoreBundle:Exception:error404.html.twig', array('status_text' => 'Page Url Changed'));
        }

        $key = md5($request->getClientIp().$request->headers->get('User-Agent'));
        $tiUrl = CommonManager::getCacheVersion($this->container, 'ti_url_'.$key);

        if ($tiUrl) {
            $tiAdObj = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('ti_ad_id' => $adId));
            if ($tiAdObj) {
                CommonManager::removeCache($this->container, 'ti_url_'.$key);
                return $this->redirectToRoute('ad_package_purchase', array('adId' => $tiAdObj->getId()), 301);
            }
            return $this->handleMessage($this->get('translator')->trans('No ad exists which you want to promote.', array(), 'frontend-ad-edit'), 'fa_frontend_homepage', array(), 'error');
        }

        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
        if (empty($ad)) {
            return $this->render('FaCoreBundle:Exception:error404.html.twig', array('status_text' => 'Page Url Changed'));
        }
        if ($ad->getStatus()->getId() == EntityRepository::AD_STATUS_INACTIVE_ID) {
            return $this->handleMessage($this->get('translator')->trans('Ad has been deleted which you want to promote.', array(), 'frontend-ad-edit'), 'fa_frontend_homepage', array(), 'error');
        }

        $user     = $ad->getUser();
        $userId   = ($user ? $user->getId() : null);
        $response = $this->checkIsValidAdUser($userId);
        if ($response !== true) {
            return $response;
        }

        //get user roles.
        $systemUserRoles  = $this->getRepository('FaUserBundle:Role')->getRoleArrayByType('C', $this->container);
        $userRole         = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);

        //check if user has already purchased pkg or not
        $adUserPackage = $this->getRepository('FaAdBundle:AdUserPackage')->getPurchasedAdPackage($adId);
        if ($adUserPackage && $adUserPackage->getStatus() == 1) {
            return $this->handleMessage($this->get('translator')->trans('You already have purchased package for ad %adId%.', array('%adId%' => $adId), 'frontend-ad-package'), 'fa_frontend_homepage', array(), 'error');
        }

        $privateUserUrlParams = array();
        $oldSelectedPrintEditions = array();
        $selectedPrintEditions = array();
        $defaultSelectedPrintEditions = array();
        $selectedPackageId = $request->get('packageId', null);
        $printEditionSelectedFlag = true;
        $errorMsg         = null;
        $adCartDetails = null;
        $adCartDetailValue = array();
        $isAdultAdvertPresent = 0;
        $cart            = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container);
        $cartDetails     = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        if ($cartDetails) {
            $adCartDetails   = $this->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartIdAndAdId($cart->getId(), $adId);
            if ($adCartDetails) {
                $adCartDetailValue = unserialize($adCartDetails[0]->getValue());
            }
        }

        $categoryId       = $ad->getCategory()->getId();
        $adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
        if ($adRootCategoryId == CategoryRepository::ADULT_ID) {
            $isAdultAdvertPresent = 1;
        }
        $privateUserAdParams = $this->getRepository('FaAdBundle:Ad')->getPrivateUserPostAdParams($userId, $categoryId, $adId, $this->container);
        $userRolesArray[] = array_search($userRole, $systemUserRoles);
        if ($request->get('business') && $request->get('business') == 1) {
            $privateUserUrlParams['business'] = 1;
            if (isset($privateUserAdParams['allowPrivateUserToPostAdFlag']) && !$privateUserAdParams['allowPrivateUserToPostAdFlag']) {
                if (!count($adCartDetailValue) || (count($adCartDetailValue) && isset($adCartDetailValue['privateUserAdParams']) && isset($adCartDetailValue['privateUserAdParams']['allowPrivateUserToPostAdFlag']) && !$adCartDetailValue['privateUserAdParams']['allowPrivateUserToPostAdFlag']) ||  (count($adCartDetailValue) && !isset($adCartDetailValue['privateUserAdParams']))) {
                    $userRolesArray = array(RoleRepository::ROLE_BUSINESS_SELLER_ID,RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID);
                    $privateUserAdParams['business'] = 1;
                }
            }
            if (count($adCartDetailValue) && isset($adCartDetailValue['privateUserAdParams'])) {
                $privateUserAdParams = $adCartDetailValue['privateUserAdParams'];
                $privateUserAdParams['business'] = 1;
            }
        }
        $locationGroupIds = $this->getRepository('FaAdBundle:AdLocation')->getLocationGroupIdForAd($adId, true);
        $packages         = $this->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, array(), $this->container);
        $adExpiryDays     = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
        $packageIds       = array();

        if (!$selectedPackageId && $ad->getStatus() && in_array($ad->getStatus()->getId(), array(EntityRepository::AD_STATUS_EXPIRED_ID, EntityRepository::AD_STATUS_SOLD_ID))) {
            $oldAdUserPackage = $this->getRepository('FaAdBundle:AdUserPackage')->findOneBy(array('user' => $userId), array('id' => 'DESC'), 1);
            if ($oldAdUserPackage && $oldAdUserPackage->getPackage()) {
                $selectedPackageId = $oldAdUserPackage->getPackage()->getId();
                if ('POST' !== $request->getMethod()) {
                    $oldAdUserPackageValue = unserialize($oldAdUserPackage->getValue());
                    if (isset($oldAdUserPackageValue['printEditions'])) {
                        $oldSelectedPrintEditions = $oldAdUserPackageValue['printEditions'];
                    }
                }
            }
        }

        //loop through all show packages
        foreach ($packages as $package) {
            $packageIds[] = $package->getPackage()->getId();
        }

        $printEditionLimits = $this->getRepository('FaPromotionBundle:Package')->getPrintEditionLimitForPackages($packageIds);

        if (count($printEditionLimits) && 'POST' !== $request->getMethod()) {
            $defaultSelectedPrintEditions = $this->getRepository('FaAdBundle:AdPrint')->getPrintEditionForAd(max($printEditionLimits), $adId, true, $locationGroupIds);
            if (count($defaultSelectedPrintEditions)) {
                $defaultSelectedPrintEditions = array_combine(range(1, count($defaultSelectedPrintEditions)), array_values($defaultSelectedPrintEditions));
            }
            if (!$adCartDetails) {
                if (count($oldSelectedPrintEditions)) {
                    $selectedPrintEditions = $oldSelectedPrintEditions;
                } else {
                    $selectedPrintEditions = $defaultSelectedPrintEditions;
                }
            } elseif ($adCartDetails) {
                $adCartDetailValue = unserialize($adCartDetails[0]->getValue());
                if (isset($adCartDetailValue['package'])) {
                    foreach ($adCartDetailValue['package'] as $adCartDetailPackageId => $adCartDetailPackage) {
                        $selectedPackageId = $adCartDetailPackageId;
                        if (isset($adCartDetailPackage['printEditions'])) {
                            $selectedPrintEditions = $adCartDetailPackage['printEditions'];
                        }
                    }
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $selectedPackageId = $request->get('package_id', null);
            $userCreditId = $request->get('credit_id', null);
            if (!in_array($selectedPackageId, $packageIds)) {
                $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Please select atleast one ad package.', array(), 'frontend-ad-package'), 'error');
            } else {
                $selectedPackageObj = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $selectedPackageId));
                if ($selectedPackageObj && $selectedPackageObj->getPrice() <= 0 && isset($privateUserAdParams['allowPrivateUserToPostAdFlag']) && !$privateUserAdParams['allowPrivateUserToPostAdFlag']) {
                    $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Sorry, maximum number of ad placements reached.', array(), 'frontend-ad-package'), 'error');
                    return $this->redirectToRoute('ad_package_purchase', array('adId' => $adId) + $privateUserUrlParams);
                }
                
                if ($selectedPackageObj->getDuration()) {
                    $getLastCharacter = substr($selectedPackageObj->getDuration(),-1);
                    $noInDuration = substr($selectedPackageObj->getDuration(),0, -1);
                    if($getLastCharacter=='m') { $adExpiryDays = $noInDuration*28;   }
                    elseif($getLastCharacter=='d') { $adExpiryDays = $noInDuration; }
                    else { $adExpiryDays = $selectedPackageObj->getDuration(); }                   
                }
                
                //check for print edition
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
                        $errorMsg = $this->get('translator')->trans('Please select unique print editions.', array(), 'frontend-ad-package');
                    }
                }

                if ($printEditionSelectedFlag) {
                    $printDurationPrices = $this->getRepository('FaPromotionBundle:PackagePrint')->getPrintDurationForPackages(array($selectedPackageId), true);
                    $selectedPackagePrintId = $request->get('package_print_id_'.$selectedPackageId, null);
                }

                $totalCredit = null;
                //check for user credit
                if ($userCreditId) {
                    $totalCredit = 1;
                    if ($selectedPackagePrintId) {
                        $selectedPackagePrintObj = $this->getRepository('FaPromotionBundle:PackagePrint')->findOneBy(array('id' => $selectedPackagePrintId));
                        $totalWeeks = (int) $selectedPackagePrintObj->getDuration();
                        $totalCredit = ceil(($totalWeeks / 4));
                    }
                    $userActiveCredits = $this->getRepository('FaUserBundle:UserCredit')->getActiveCreditForUserByCategory($userId, $adRootCategoryId, $cart->getId(), $adId);
                    if (!empty($userActiveCredits)) {
                        $isValidUserCredit = ($selectedPackageObj->getPackageSrNo() && isset($userActiveCredits[$userCreditId]) && in_array($selectedPackageObj->getPackageSrNo(), $userActiveCredits[$userCreditId]['package_sr_no']) && $userActiveCredits[$userCreditId]['credit'] >= $totalCredit);
                        if (!$isValidUserCredit) {
                            $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Sorry you do not have enough credits.', array(), 'frontend-ad-package'), 'error');
                            return $this->redirectToRoute('ad_package_purchase', array('adId' => $adId) + $privateUserUrlParams);
                        }
                    } else {
                        $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Sorry you do not have enough credits.', array(), 'frontend-ad-package'), 'error');
                        return $this->redirectToRoute('ad_package_purchase', array('adId' => $adId) + $privateUserUrlParams);
                    }
                }

                if ($printEditionSelectedFlag) {
                    if (isset($printDurationPrices[$selectedPackageId]) && !in_array($selectedPackagePrintId, $printDurationPrices[$selectedPackageId])) {
                        $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Please select valid print option.', array(), 'frontend-ad-package'), 'error');
                    } else {
                        //check if cart is empty and package is free then process ad
                        $selectedPackage = $this->getRepository('FaPromotionBundle:Package')->find($selectedPackageId);

                        $selectedPackagePrint = null;
                        if ($selectedPackagePrintId) {
                            $selectedPackagePrint = $this->getRepository('FaPromotionBundle:PackagePrint')->find($selectedPackagePrintId);
                        }

                        //remove if same ad is in cart.
                        if (count($cartDetails) == 1 && $cartDetails[0]['ad_id'] == $adId) {
                            unset($cartDetails[0]);
                        }

                        //redirect back to manage my ads active tab.
                        $this->container->get('session')->set('payment_success_redirect_url', $this->generateUrl('manage_my_ads_active'));

                        // Remove session for redirec back to PAA steps.
                        $this->container->get('session')->remove('back_url_from_ad_package_page');

                        $isAdminPostedAd     = ($ad->getSource() == AdRepository::SOURCE_ADMIN ? true : false);
                        $isFreePaymentMethod = false;

                        if ($isAdminPostedAd) {
                            if ($selectedPackagePrint) {
                                if (($selectedPackagePrint->getAdminPrice() === 0.00) || ($selectedPackagePrint->getAdminPrice() === null && $selectedPackagePrint->getPrice() === 0.00)) {
                                    $isFreePaymentMethod = true;
                                }
                            } else {
                                if (($selectedPackage->getAdminPrice() === 0.00) || ($selectedPackage->getAdminPrice() === null && $selectedPackage->getPrice() === 0.00)) {
                                    $isFreePaymentMethod = true;
                                }
                            }
                        } else {
                            $isFreePaymentMethod = (($selectedPackagePrint && $selectedPackagePrint->getPrice() === 0.00) || (!$selectedPackagePrint && $selectedPackage->getPrice() === 0.00)) ? true : false;
                        }

                        if (!count($cartDetails) && $isFreePaymentMethod) {
                            $this->getRepository('FaPaymentBundle:Cart')->addPackageToCart($userId, $adId, $selectedPackageId, $this->container, true, $adExpiryDays, $selectedPackagePrintId, null, null, true, null, $printEditionValues, $userCreditId, $totalCredit, $privateUserAdParams);
                            return $this->redirectToRoute('process_payment', array('paymentMethod' => PaymentRepository::PAYMENT_METHOD_FREE));
                        } else {
                            return $this->addAdPackage($adId, $selectedPackageId, $adExpiryDays, $selectedPackagePrintId, null, null, true, $printEditionValues, $userCreditId, $totalCredit, $privateUserAdParams);
                        }
                    }
                }
            }
        }

        $backUrl = null;
        if ($this->container->get('session')->has('back_url_from_ad_package_page')) {
            $backUrl = $this->container->get('session')->get('back_url_from_ad_package_page');
        }

        $parameters = array(
            'packages' => $packages,
            'adExpiryDays' => $adExpiryDays,
            'adId' => $adId,
            'purchase' => true,
            'backUrl' => $backUrl,
            'adObj'   => $ad,
            'printEditionSelectedFlag' => $printEditionSelectedFlag,
            'selectedPackageId' => $selectedPackageId,
            'printEditionLimits' => $printEditionLimits,
            'selectedPrintEditions' => $selectedPrintEditions,
            'defaultSelectedPrintEditions' => $defaultSelectedPrintEditions,
            'isAdultAdvertPresent' => $isAdultAdvertPresent,
            'errorMsg' => $errorMsg,
            'categoryId' => $categoryId,
            'cart' => $cart,
            'privateUserAdParams' => $privateUserAdParams,
            'locationGroupIds' => $locationGroupIds,
            'dimension12' => $this->getDimension12($ad),
        );
        return $this->render('FaAdBundle:AdPackage:purchaseAdPackage.html.twig', $parameters);
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
    public function addAdPackage($adId, $packageId, $adExpiryDays, $selectedPackagePrintId, $type = null, $activeAdUserPackageId = null, $addAdToModeration = false, $printEditionValues = array(), $userCreditId = null, $totalCredit = null, $privateUserAdParams = array())
    {
        $ad      = $this->getRepository('FaAdBundle:Ad')->find($adId);

        $response = $this->checkIsValidAdUser($ad->getUser()->getId());
        if ($response !== true) {
            return $response;
        }

        $this->getRepository('FaPaymentBundle:Cart')->addPackageToCart($this->getLoggedInUser()->getId(), $adId, $packageId, $this->container, true, $adExpiryDays, $selectedPackagePrintId, $type, $activeAdUserPackageId, $addAdToModeration, null, $printEditionValues, $userCreditId, $totalCredit, $privateUserAdParams);

        //apply discount code if it is already applied for one ad
        $loggedinUser = $this->getLoggedInUser();
        $cart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container);
        $cartValue = unserialize($cart->getValue());
        if ($cart->getDiscountAmount() > 0 && isset($cartValue['discount_values']) && count($cartValue['discount_values']) && isset($cartValue['discount_values']['code'])) {
            $codeObj = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->findOneBy(array('code' => $cartValue['discount_values']['code'], 'status' => 1));
            $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
            $this->getRepository('FaPaymentBundle:TransactionDetail')->removeCodeFromAllItems($cartDetails);
            $this->getRepository('FaPromotionBundle:PackageDiscountCode')->processDiscountCode($codeObj, $cart, $cartDetails, $loggedinUser, $this->container, false);
        } elseif ($cart->getDiscountAmount() <= 0 && isset($cartValue['discount_values']) && count($cartValue['discount_values']) && isset($cartValue['discount_values']['code'])) {
            $cartDetails  = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
            $this->getRepository('FaPaymentBundle:TransactionDetail')->removeCodeFromAllItems($cartDetails);
        }

        return $this->redirectToRoute('show_cart');
    }

    /**
     * Promote ad.
     *
     * @param integer $adId    Ad id.
     * @param string  $type    Promote or Repost.
     * @param Request $request Request object.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function promoteAdAction($type, $adId, Request $request)
    {
        /**
         * @var Ad $ad
         */
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        if ($this->isAuth()) {
            $this->container->get('session')->set('paa_skip_login_step', true);
        }

        $key = md5($request->getClientIp().$request->headers->get('User-Agent'));
        $tiUrl = CommonManager::getCacheVersion($this->container, 'ti_url_'.$key);

        try {
            if ($tiUrl) {
                $tiAdObj = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('ti_ad_id' => $adId));
                if ($tiAdObj) {
                    CommonManager::removeCache($this->container, 'ti_url_'.$key);
                    return $this->redirectToRoute('ad_promote', array('type' => $type, 'adId' => $tiAdObj->getId()), 301);
                }
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage');
        }

        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

        try {
            if (!$ad) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_frontend_homepage');
        }

        //check ad status
        if ($type == 'repost') {
            if ($ad->getStatus() && !in_array($ad->getStatus()->getId(), array(EntityRepository::AD_STATUS_EXPIRED_ID, EntityRepository::AD_STATUS_SOLD_ID))) {
                return $this->handleMessage($this->get('translator')->trans('You can not repost ad %adId%.', array('%adId%' => $adId), 'frontend-ad-package'), 'manage_my_ads_inactive', array(), 'error');
            }

            $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_incomplete', $ad->getId(), $ad->getUser()->getId(), strtotime('+10 minute'), true);
        } elseif ($type == 'promote') {
            $package = $this->getRepository('FaAdBundle:AdUserPackage')->getActiveAdPackage($ad->getId());
            if (($ad->getStatus() && $ad->getStatus()->getId() == EntityRepository::AD_STATUS_LIVE_ID && ($package && $package->getPrice() != 0)) || $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID) {
                return $this->handleMessage($this->get('translator')->trans('You can not promote ad %adId%.', array('%adId%' => $adId), 'frontend-ad-package'), 'manage_my_ads_active', array(), 'error');
            }
        } elseif ($type == 'renew') {
            if ($ad->getStatus() && !in_array($ad->getStatus()->getId(), array(EntityRepository::AD_STATUS_LIVE_ID))) {
                return $this->handleMessage($this->get('translator')->trans('You can not renew ad %adId%.', array('%adId%' => $adId), 'frontend-ad-package'), 'manage_my_ads_active', array(), 'error');
            }

            // User can renew ad once per cycle before defined days(4 days) of expiration.
            $identifier                 = 'ad_needs_renewing_4_days_left';
            $parameters                 = $this->getRepository('FaEmailBundle:EmailTemplate')->getSchedualParameterArray($identifier, CommonManager::getCurrentCulture($this->container));
            $allowRenewBeforeExpiryDays = isset($parameters['advert_with_x_days_left_to_expire']) && $parameters['advert_with_x_days_left_to_expire'] > 0 ? $parameters['advert_with_x_days_left_to_expire'] : 4;
            $remainRenewDays            = (strtotime(date('Y-m-d', $ad->getExpiresAt())) - strtotime(date('Y-m-d')))/(60 * 60 * 24);

            if ($remainRenewDays > $allowRenewBeforeExpiryDays) {
                return $this->handleMessage($this->get('translator')->trans('You can not renew ad %adId%.', array('%adId%' => $adId), 'frontend-ad-package'), 'manage_my_ads_active', array(), 'error');
            }
        }

        //redirect back to manage my ads active tab.
        $this->container->get('session')->set('payment_success_redirect_url', $this->generateUrl('manage_my_ads_active'));

        $user     = $ad->getUser();
        $userId   = ($user ? $user->getId() : null);
        $response = $this->checkIsValidAdUser($userId);
        if ($response !== true) {
            return $response;
        }

        //check User advert has location if not redirect to edit page
        if (count($ad->getAdLocations()) == 0) {
            $redirectUrl = $request->getUri();
            $this->container->get('session')->set('choose_package_location_missing_'.$ad->getId(), $redirectUrl);
            $editPageUrl = $this->generateUrl('ad_edit', array('id' => $ad->getId()));
            return $this->redirect($editPageUrl);
        }

        //get user roles.
        $systemUserRoles  = $this->getRepository('FaUserBundle:Role')->getRoleArrayByType('C', $this->container);
        $userRole         = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);

        // get active package.
        $oldSelectedPrintEditions = array();
        $selectedPackageId = $request->get('packageId', null);
        $activePackageArray = array();
        $activePackage      = null;
        if ($type == 'promote' || $type == 'renew') {
            $activePackage = $this->getRepository('FaAdBundle:AdUserPackage')->getActiveAdPackage($adId);
        }
        
        if ($activePackage && $activePackage->getPackage()) {
            $activePackageArray[] = $activePackage->getPackage()->getId();
            if (!$selectedPackageId && $type == 'renew') {
                $selectedPackageId = $activePackage->getPackage()->getId();
                if ('POST' !== $request->getMethod()) {
                    $activePackageValue = unserialize($activePackage->getValue());
                    if (isset($activePackageValue['printEditions'])) {
                        $oldSelectedPrintEditions = $activePackageValue['printEditions'];
                    }
                }
            }
        }
        if (!$selectedPackageId && $type == 'repost') {
            $oldAdUserPackage = $this->getRepository('FaAdBundle:AdUserPackage')->findOneBy(array('user' => $userId), array('id' => 'DESC'), 1);
            if ($oldAdUserPackage && $oldAdUserPackage->getPackage()) {
                $selectedPackageId = $oldAdUserPackage->getPackage()->getId();
                if ('POST' !== $request->getMethod()) {
                    $oldAdUserPackageValue = unserialize($oldAdUserPackage->getValue());
                    if (isset($oldAdUserPackageValue['printEditions'])) {
                        $oldSelectedPrintEditions = $oldAdUserPackageValue['printEditions'];
                    }
                }
            }
        }

        $privateUserUrlParams = array();
        $selectedPrintEditions = array();
        $defaultSelectedPrintEditions = array();
        $printEditionSelectedFlag = true;
        $errorMsg         = null;
        $adCartDetails = null;
        $adCartDetailValue = array();
        $privateUserAdParams = array();
        $isAdultAdvertPresent = 0;
        $categoryId       = $ad->getCategory()->getId();
        $adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
        if ($adRootCategoryId == CategoryRepository::ADULT_ID) {
            $isAdultAdvertPresent = 1;
        }
        $locationGroupIds = $this->getRepository('FaAdBundle:AdLocation')->getLocationGroupIdForAd($adId, true);
        $cart            = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container);
        $cartDetails     = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        if ($cartDetails) {
            $adCartDetails   = $this->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartIdAndAdId($cart->getId(), $adId);
            if ($adCartDetails) {
                $adCartDetailValue = unserialize($adCartDetails[0]->getValue());
            }
        }

        $userRolesArray[] = array_search($userRole, $systemUserRoles);

        if ($type == 'renew' || $type == 'repost') {
            $privateUserAdParams = $this->getRepository('FaAdBundle:Ad')->getPrivateUserPostAdParams($userId, $categoryId, $adId, $this->container);
            if ($request->get('business') && $request->get('business') == 1) {
                $privateUserUrlParams['business'] = 1;
                if (isset($privateUserAdParams['allowPrivateUserToPostAdFlag']) && !$privateUserAdParams['allowPrivateUserToPostAdFlag']) {
                    if (!count($adCartDetailValue) || (count($adCartDetailValue) && isset($adCartDetailValue['privateUserAdParams']) && isset($adCartDetailValue['privateUserAdParams']['allowPrivateUserToPostAdFlag']) && !$adCartDetailValue['privateUserAdParams']['allowPrivateUserToPostAdFlag']) ||  (count($adCartDetailValue) && !isset($adCartDetailValue['privateUserAdParams']))) {
                        $userRolesArray = array(RoleRepository::ROLE_BUSINESS_SELLER_ID,RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID);
                        $privateUserAdParams['business'] = 1;
                    }
                }
                if (count($adCartDetailValue) && isset($adCartDetailValue['privateUserAdParams'])) {
                    $privateUserAdParams = $adCartDetailValue['privateUserAdParams'];
                    $privateUserAdParams['business'] = 1;
                }
            }
        }

        if ($type == 'renew') {
            $packages = $this->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, $activePackageArray, $this->container, true, true);
        } else {
            $packages = $this->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, $activePackageArray, $this->container);
        }

        $adExpiryDays = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
        $packageIds   = array();

        
        //display all packages if active package price is zero
        $typeAllActivePackage      = null;
        $getFreePackageForCategory = null; $getFreePackageForCategoryId = null;
        $typeAllActivePackageId = null;
        $removeFreePackageId = false;
        if($type == 'all') {
            $typeAllActivePackage = $this->getRepository('FaAdBundle:AdUserPackage')->getActiveAdPackage($adId);
            $getFreePackageForCategory = $this->getRepository('FaPromotionBundle:PackageRule')->getFreeAdPackageByCategory($categoryId, $this->container);
            //echo '<pre>'; var_dump($getFreePackageForCategory);die;
            if ($typeAllActivePackage && $typeAllActivePackage->getPackage()) {
                $typeAllActivePackageId = $typeAllActivePackage->getPackage()->getId();
            }
            if ($getFreePackageForCategory && isset($getFreePackageForCategory[0])) {
                $getFreePackageForCategoryId = $getFreePackageForCategory[0]->getPackage()->getId();
                if($getFreePackageForCategoryId!=$typeAllActivePackageId) {
                    $removeFreePackageId = true;
                }
            }
        }
        //loop through all show packages
        foreach ($packages as $package) {
            if($type == 'all' && $removeFreePackageId== true && $package->getPackage()->getId()==$typeAllActivePackageId) {
                
            } else {
                $packageIds[] = $package->getPackage()->getId();
            }
        }

        $printEditionLimits = $this->getRepository('FaPromotionBundle:Package')->getPrintEditionLimitForPackages($packageIds);

        if (count($printEditionLimits) && 'POST' !== $request->getMethod()) {
            $defaultSelectedPrintEditions = $this->getRepository('FaAdBundle:AdPrint')->getPrintEditionForAd(max($printEditionLimits), $adId, true, $locationGroupIds);
            $defaultSelectedPrintEditions = array_combine(range(1, count($defaultSelectedPrintEditions)), array_values($defaultSelectedPrintEditions));
            if (!$adCartDetails) {
                if (count($oldSelectedPrintEditions)) {
                    $selectedPrintEditions = $oldSelectedPrintEditions;
                } else {
                    $selectedPrintEditions = $defaultSelectedPrintEditions;
                }
            } elseif ($adCartDetails) {
                $adCartDetailValue = unserialize($adCartDetails[0]->getValue());
                if (isset($adCartDetailValue['package'])) {
                    foreach ($adCartDetailValue['package'] as $adCartDetailPackageId => $adCartDetailPackage) {
                        $selectedPackageId = $adCartDetailPackageId;
                        if (isset($adCartDetailPackage['printEditions'])) {
                            $selectedPrintEditions = $adCartDetailPackage['printEditions'];
                        }
                    }
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $selectedPackageId = $request->get('package_id', null);
            $userCreditId = $request->get('credit_id', null);
            if (!in_array($selectedPackageId, $packageIds)) {
                $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Please select atleast one ad package.', array(), 'frontend-ad-package'), 'error');
            } else {
                $selectedPackageObj = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $selectedPackageId));
                if ($selectedPackageObj && $selectedPackageObj->getPrice() <= 0 && isset($privateUserAdParams['allowPrivateUserToPostAdFlag']) && !$privateUserAdParams['allowPrivateUserToPostAdFlag']) {
                    $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Sorry, maximum number of ad placements reached.', array(), 'frontend-ad-package'), 'error');
                    return $this->redirectToRoute('ad_package_purchase', array('adId' => $adId) + $privateUserUrlParams);
                }
                
                if ($selectedPackageObj->getDuration()) {
                    $getLastCharacter = substr($selectedPackageObj->getDuration(),-1);
                    $noInDuration = substr($selectedPackageObj->getDuration(),0, -1);
                    if($getLastCharacter=='m') { $adExpiryDays = $noInDuration*28;   }
                    elseif($getLastCharacter=='d') { $adExpiryDays = $noInDuration; }
                    else { $adExpiryDays = $selectedPackageObj->getDuration(); }
                }
                
                //check for print edition
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
                        $errorMsg = $this->get('translator')->trans('Please select unique print editions.', array(), 'frontend-ad-package');
                    }
                }

                if ($printEditionSelectedFlag) {
                    $printDurationPrices = $this->getRepository('FaPromotionBundle:PackagePrint')->getPrintDurationForPackages(array($selectedPackageId), true);
                    $selectedPackagePrintId = $request->get('package_print_id_'.$selectedPackageId, null);
                }

                $totalCredit = null;
                //check for user credit
                if ($userCreditId) {
                    $totalCredit = 1;
                    if ($selectedPackagePrintId) {
                        $selectedPackagePrintObj = $this->getRepository('FaPromotionBundle:PackagePrint')->findOneBy(array('id' => $selectedPackagePrintId));
                        $totalWeeks = (int) $selectedPackagePrintObj->getDuration();
                        $totalCredit = ceil(($totalWeeks / 4));
                    }
                    $userActiveCredits = $this->getRepository('FaUserBundle:UserCredit')->getActiveCreditForUserByCategory($userId, $adRootCategoryId, $cart->getId(), $adId);
                    if (count($userActiveCredits)) {
                        $isValidUserCredit = ($selectedPackageObj->getPackageSrNo() && isset($userActiveCredits[$userCreditId]) && in_array($selectedPackageObj->getPackageSrNo(), $userActiveCredits[$userCreditId]['package_sr_no']) && $userActiveCredits[$userCreditId]['credit'] >= $totalCredit);
                        if (!$isValidUserCredit) {
                            $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Sorry you do not have enough credits.', array(), 'frontend-ad-package'), 'error');
                            return $this->redirectToRoute('ad_package_purchase', array('adId' => $adId) + $privateUserUrlParams);
                        }
                    } else {
                        $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Sorry you do not have enough credits.', array(), 'frontend-ad-package'), 'error');
                        return $this->redirectToRoute('ad_package_purchase', array('adId' => $adId) + $privateUserUrlParams);
                    }
                }

                if ($printEditionSelectedFlag) {
                    if (isset($printDurationPrices[$selectedPackageId]) && !in_array($selectedPackagePrintId, $printDurationPrices[$selectedPackageId])) {
                        $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Please select valid print option.', array(), 'frontend-ad-package'), 'error');
                    } else {
                        //check if cart is empty and package is free then process ad
                        $selectedPackage = $this->getRepository('FaPromotionBundle:Package')->find($selectedPackageId);

                        $selectedPackagePrint = null;
                        if ($selectedPackagePrintId) {
                            $selectedPackagePrint = $this->getRepository('FaPromotionBundle:PackagePrint')->find($selectedPackagePrintId);
                        }

                        //remove if same ad is in cart.
                        if (count($cartDetails) == 1 && $cartDetails[0]['ad_id'] == $adId) {
                            unset($cartDetails[0]);
                        }

                        $isAdminPostedAd     = ($ad->getSource() == AdRepository::SOURCE_ADMIN ? true : false);
                        $isFreePaymentMethod = false;

                        if ($isAdminPostedAd) {
                            if ($selectedPackagePrint) {
                                if (($selectedPackagePrint->getAdminPrice() === 0.00) || ($selectedPackagePrint->getAdminPrice() === null && $selectedPackagePrint->getPrice() === 0.00)) {
                                    $isFreePaymentMethod = true;
                                }
                            } else {
                                if (($selectedPackage->getAdminPrice() === 0.00) || ($selectedPackage->getAdminPrice() === null && $selectedPackage->getPrice() === 0.00)) {
                                    $isFreePaymentMethod = true;
                                }
                            }
                        } else {
                            $isFreePaymentMethod = (($selectedPackagePrint && $selectedPackagePrint->getPrice() === 0.00) || (!$selectedPackagePrint && $selectedPackage->getPrice() === 0.00)) ? true : false;
                        }

                        // Extend expiry by adding remaining days in expiry date.
                        if ($type == 'renew') {
                            $renewExtendExpiryDays = (strtotime(date('Y-m-d', $ad->getExpiresAt())) - strtotime(date('Y-m-d')))/(60 * 60 * 24);
                            $adExpiryDays          = $adExpiryDays + $renewExtendExpiryDays;
                        }

                        if (!count($cartDetails) && $isFreePaymentMethod) {
                            $this->getRepository('FaPaymentBundle:Cart')->addPackageToCart($userId, $adId, $selectedPackageId, $this->container, true, $adExpiryDays, $selectedPackagePrintId, $type, ($activePackage ? $activePackage->getId() : null), false, null, $printEditionValues, $userCreditId, $totalCredit, $privateUserAdParams);
                            return $this->redirectToRoute('process_payment', array('paymentMethod' => PaymentRepository::PAYMENT_METHOD_FREE));
                        } else {
                            return $this->addAdPackage($adId, $selectedPackageId, $adExpiryDays, $selectedPackagePrintId, $type, ($activePackage ? $activePackage->getId() : null), false, $printEditionValues, $userCreditId, $totalCredit, $privateUserAdParams);
                        }
                    }
                }
            }
        }

        $parameters = array(
            'packages' => $packages,
            'adExpiryDays' => $adExpiryDays,
            'adId' => $adId,
            'promote' => true,
            'adObj'   => $ad,
            'printEditionSelectedFlag' => $printEditionSelectedFlag,
            'selectedPackageId' => $selectedPackageId,
            'printEditionLimits' => $printEditionLimits,
            'selectedPrintEditions' => $selectedPrintEditions,
            'defaultSelectedPrintEditions' => $defaultSelectedPrintEditions,
            'isAdultAdvertPresent' => $isAdultAdvertPresent,
            'errorMsg' => $errorMsg,
            'categoryId' => $categoryId,
            'cart' => $cart,
            'privateUserAdParams' => $privateUserAdParams,
        );

        return $this->render('FaAdBundle:AdPackage:purchaseAdPackage.html.twig', $parameters);
    }

    /**
     *
     * @param Ad $ad
     * @return string
     */
    private function getDimension12(Ad $ad)
    {
        $adLocations = $ad->getAdLocations();
        $flagPrint = false;
        $flagNonprint = false;
        $dimension12 = '';
        $repoLocationGroupLocation = $this->getRepository('FaEntityBundle:LocationGroupLocation');
        $arrPrintLocationTownIds = $repoLocationGroupLocation->getPrintLocationTownIds();
        if(!empty($adLocations)){
            foreach ($adLocations as $valAdLocation){
                if(!empty($valAdLocation->getLocationTown())){
                    $townId = $valAdLocation->getLocationTown()->getId();
                    if (in_array($townId, $arrPrintLocationTownIds)) {
                        $flagPrint = true;
                    } else {
                        $flagNonprint = true;
                    }
                }
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

    /**
     *
     * @return array
     */
    private function getPrintLocationTownIds()
    {
        /**
         * @var LocationGroupLocation[] $resLocationGroupLocations
         */
        $townIds = [];
        $resLocationGroupLocations = $this->getRepository('FaEntityBundle:LocationGroupLocation')->findAll();
        foreach ($resLocationGroupLocations as $valLocation) {
            $townIds[] = $valLocation->getLocationTown()->getId();
        }
        return $townIds;
    }
    
    public function nurseryLocationGroupPackageAction(Request $request)
    {
        $getPackageRuleArray = $getActivePackage = array();
        
        $adId = $request->get('adId');
        $adIdArray = array();
        $adIdArray[] = $adId;
        
        if ($request->get('adId') != null) {
            $getActivePackage = $this->getRepository('FaAdBundle:AdUserPackage')->getAdActivePackageArrayByAdId($adIdArray);
            if ($getActivePackage) {
                $getPackageRuleArray = $this->getRepository('FaPromotionBundle:PackageRule')->getPackageRuleArrayByPackageId($getActivePackage[$adId]['package_id']);
                if(!empty($getPackageRuleArray)) {
                    if($getPackageRuleArray[0]['location_group_id']==14) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

}
