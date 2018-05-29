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
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
/**
 * This controller is used for ad package management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdPackageAdminController extends CoreController implements ResourceAuthorizationController
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
        $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);

        try {
            if (!$ad) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_admin_homepage');
        }

        if ('POST' !== $request->getMethod()) {
            if ($request->get('popup') == 1) {
                $this->container->get('session')->set('popup', 1);
                $popup = true;
            } else {
                $this->container->get('session')->remove('popup');
            }
        }

        if ($this->container->get('session')->get('popup')) {
            $popup = true;
        } else {
            $popup = false;
        }

        $user   = null;
        $userId = null;

        if ($ad->getUser()) {
            $user   = $ad->getUser();
            $userId = $user->getId();
        }

        if ($user && $user->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
            return $this->handleMessage($this->get('translator')->trans('You can not assign package, as user is not active!'), 'ad_admin', array(), 'error');
        }

        $loggedinUser = $this->getLoggedInUser();
        $loggedinUserRole = $this->getRepository('FaUserBundle:User')->getUserRole($loggedinUser->getId(), $this->container);
        if (!$user && $loggedinUserRole == RoleRepository::ROLE_ADMIN_HIDE_SKIP_PAYMENT) {
            return $this->handleMessage($this->get('translator')->trans('You can not assign package to detached ad.'), 'ad_admin', array(), 'error');
        }

        //get user roles.
        $systemUserRoles = array_keys(RoleRepository::getUserTypes());
        $userRolesArray  = array();
        if ($user) {
            foreach ($user->getRoles() as $userRole) {
                if (in_array($userRole->getId(), $systemUserRoles)) {
                    $userRolesArray[] = $userRole->getId();
                }
            }
        }

        //check if user has already purchased pkg or not
        $adUserPackage = $this->getRepository('FaAdBundle:AdUserPackage')->getPurchasedAdPackage($adId);
        if ($adUserPackage) {
            $adUserPackageUpsell = $this->getRepository('FaAdBundle:AdUserPackageUpsell')->getAdPackageUpsell($adId, $adUserPackage->getId());
            $parameters = array(
                'adUserPackage' => $adUserPackage,
                'adUserPackageUpsell' => $adUserPackageUpsell,
                'adId' => $adId,
                'popup' => $popup,
            );

            return $this->render('FaAdBundle:AdPackageAdmin:showPurchaseAdPackage.html.twig', $parameters);
        }

        $oldSelectedPrintEditions = array();
        $selectedPrintEditions = array();
        $defaultSelectedPrintEditions = array();
        $selectedPackageId = $request->get('packageId', null);;
        $printEditionSelectedFlag = true;
        $errorMsg         = null;
        $adCartDetails = null;
        $isAdultAdvertPresent = 0;
        $categoryId       = $ad->getCategory()->getId();
        $adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
        if ($adRootCategoryId == CategoryRepository::ADULT_ID) {
            $isAdultAdvertPresent = 1;
        }
        $locationGroupIds = $this->getRepository('FaAdBundle:AdLocation')->getLocationGroupIdForAd($adId, true);
        $packages         = $this->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, array(), $this->container, false);

        $adminPackages = 0;
        $paaPackages   = 0;
        if ($packages && count($packages) > 0) {
            foreach ($packages as $objPackageRule) {
                $objPackage = $objPackageRule->getPackage();
                if ($objPackage) {
                    if ($objPackageRule->getPackage()->getIsAdminPackage()) {
                        $adminPackages++;
                    } else {
                        $paaPackages++;
                    }
                }
            }
        }

        if ($adminPackages == 0) {
            $adminPackages = $this->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, array(), $this->container, false, false, true);
            if ($adminPackages && count($adminPackages)) {
                if ($packages && count($packages)) {
                    foreach ($adminPackages as $objAdminPackage) {
                        $packages[count($packages)] = $objAdminPackage;
                    }
                }
            }
        }

        if ($paaPackages == 0) {
            $paaPackages = $this->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, array(), $this->container, true, false);
            if ($paaPackages && count($paaPackages)) {
                if ($packages && count($packages)) {
                    foreach ($paaPackages as $objPaaPackage) {
                        $packages[count($packages)] = $objPaaPackage;
                    }
                }
            }
        }

        $adExpiryDays  = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
        $packageIds    = array();
        $cart          = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container);
        $adCartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartIdAndAdId($cart->getId(), $adId);
        if ($userId && !$selectedPackageId && $ad->getStatus() && in_array($ad->getStatus()->getId(), array(EntityRepository::AD_STATUS_EXPIRED_ID, EntityRepository::AD_STATUS_SOLD_ID))) {
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
            if(count($defaultSelectedPrintEditions)>0) {
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
            if (!in_array($selectedPackageId, $packageIds)) {
                $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Please select atleast one ad package.', array(), 'backend-ad-package'), 'error');
            } else {
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
                    if (isset($printDurationPrices[$selectedPackageId]) && !in_array($selectedPackagePrintId, $printDurationPrices[$selectedPackageId])) {
                        $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Please select valid print option.', array(), 'frontend-ad-package'), 'error');
                    } else {
                        if ($userId && !$this->getRepository('FaPaymentBundle:Transaction')->checkTransactionsForUser($cart->getId(), $userId)) {
                            $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans("Please add only one user's ad in cart.", array(), 'backend-ad-package'), 'error');
                        } else {
                            return $this->addAdPackage($adId, $selectedPackageId, $adExpiryDays, $selectedPackagePrintId, $cart, $printEditionValues);
                        }
                    }
                }
            }
        }

        $parameters = array(
            'packages' => $packages,
            'adExpiryDays' => $adExpiryDays,
            'adId' => $adId,
            'popup' => $popup,
            'isAdultAdvertPresent' => $isAdultAdvertPresent,
            'printEditionSelectedFlag' => $printEditionSelectedFlag,
            'selectedPackageId' => $selectedPackageId,
            'printEditionLimits' => $printEditionLimits,
            'selectedPrintEditions' => $selectedPrintEditions,
            'defaultSelectedPrintEditions' => $defaultSelectedPrintEditions,
            'errorMsg' => $errorMsg,
        );

        return $this->render('FaAdBundle:AdPackageAdmin:purchaseAdPackage.html.twig', $parameters);
    }

    /**
     * Assign ad package.
     *
     * @param integer $adId                   Ad id.
     * @param integer $packageId              Package id.
     * @param integer $adExpiryDays           Ad expiring days.
     * @param integer $selectedPackagePrintId Print duration id.
     * @param object  $cart                   Cart instance.
     * @param array   $printEditionValues     Print edition array.
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function addAdPackage($adId, $packageId, $adExpiryDays, $selectedPackagePrintId, $cart = null, $printEditionValues = array())
    {
        $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);
        $package    = $this->getRepository('FaPromotionBundle:Package')->find($packageId);
        $cartUserId = ($ad->getUser() ? $ad->getUser()->getId() : null);

        $selectedPackagePrint = null;
        if ($selectedPackagePrintId) {
            $selectedPackagePrint = $this->getRepository('FaPromotionBundle:PackagePrint')->find($selectedPackagePrintId);
        }

        try {
            if (!$ad) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Draft Ad.'));
            }
            if (!$package) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find package.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'fa_admin_homepage');
        }

        $this->getRepository('FaPaymentBundle:Cart')->addPackageToCart($cartUserId, $adId, $packageId, $this->container, true, $adExpiryDays, $selectedPackagePrintId, null, null, false, $cart, $printEditionValues);

        if (!$cart) {
            $cart = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($cartUserId, $this->container);
        }

        $cartDetails = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());

        // set cart id in session for later user in payment process.
        $this->container->get('session')->set('cart_id', $cart->getId());

        $isFreePaymentMethod = false;
        if ($package->getIsAdminPackage()) {
            $isFreePaymentMethod = (($selectedPackagePrint && $selectedPackagePrint->getAdminPrice() === 0.00) || (!$selectedPackagePrint && $package->getAdminPrice() === 0.00)) ? true : false;
        } else {
            if ($selectedPackagePrint) {
                if (($selectedPackagePrint->getAdminPrice() === 0.00) || ($selectedPackagePrint->getAdminPrice() === null && $selectedPackagePrint->getPrice() === 0.00)) {
                    $isFreePaymentMethod = true;
                }
            } else {
                if (($package->getAdminPrice() === 0.00) || ($package->getAdminPrice() === null && $package->getPrice() === 0.00)) {
                    $isFreePaymentMethod = true;
                }
            }
        }

        // if detached ad posted or free package selected then bypass payment.
        if (!$cartUserId || ($isFreePaymentMethod && (count($cartDetails) == 1 && $cartDetails[0]['ad_id'] == $adId))) {
            return $this->redirectToRoute('process_payment_admin', array('paymentMethod' => PaymentRepository::PAYMENT_METHOD_FREE));
        }

        return $this->redirectToRoute('show_cart_admin');
    }
}
