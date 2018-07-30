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

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\PaymentBundle\Repository\PaymentCyberSourceRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\PaymentBundle\Manager\CyberSourceManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\PaymentBundle\Form\CyberSourceCheckoutType;

/**
 * This controller is used for upgrading with package.
 *
 * @author Gaurav Aggarwal <gaurav.aggarwal@fridaymediagroup.com>
 * @copyright  2018 Friday Media Group Ltd
 * @version v1.0
 */
class UpgradeAdController extends CoreController
{
	/**
	 * Upgrade To Featured Ad.
	 *
	 * @param Request $request A Request object.
	 *
	 * @return Response|JsonResponse A Response or JsonResponse object.
	 */
	public function ajaxUpgradeToFeaturedAdAction($adId, $rootCategoryId, Request $request)
	{
		$redirectToUrl = '';
		$error         = '';
		$htmlContent   = '';
		$deadlockError = '';
		$deadlockRetry = $request->get('deadlockRetry', 0);
		$cybersource3DSecureResponseFlag = false;
		$redirectUrl	= '';
		$gaStr	        = '';
		
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
			$errorMsg	= null;
			if(!empty($loggedinUser)) {
				$user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
				if(!empty($user)) {
					$isFeaturedTopisEnabledForCateg = $this->getRepository('FaAdBundle:Ad')->checkIsfeaturedUpgradeEnabledForCategory($rootCategoryId, $this->container);
					if(empty($isFeaturedTopisEnabledForCateg)) {
						return new JsonResponse(array('error' => 'Featured upgrade is not enable for this root category', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));  //featured upgrade is not enable for this root category
					}
					//get basic Live advert if exist
					$getBasicAdResult = $this->getRepository('FaAdBundle:Ad')->getLastBasicPackageAdvertForUpgrade($rootCategoryId, $user->getId(), null, $this->container);
					if(empty($getBasicAdResult)) {
						return new JsonResponse(array('error' => 'No Basic Advert Found', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));  //featured upgrade is not enable for this root category
					}
					
					if(!empty($getBasicAdResult) && isset($getBasicAdResult[0]['id'])) { 
						$adId 			  = $getBasicAdResult[0][AdSolrFieldMapping::ID];
						$categoryId       = $getBasicAdResult[0][AdSolrFieldMapping::CATEGORY_ID];
						$adRootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
						if ($adRootCategoryId == CategoryRepository::ADULT_ID) {
							$isAdultAdvertPresent = 1;
						}
						
						//get user roles.
						$systemUserRoles  = $this->getRepository('FaUserBundle:Role')->getRoleArrayByType('C', $this->container);
						$userRole         = $this->getRepository('FaUserBundle:User')->getUserRole($user->getId(), $this->container);
						$userRolesArray[] = array_search($userRole, $systemUserRoles);
						$locationGroupIds = $this->getRepository('FaAdBundle:AdLocation')->getLocationGroupByAdId($adId);
						$availablePackages = $this->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, array(), $this->container);
						$adExpiryDays     = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
						
						//loop through all show packages
						foreach ($availablePackages as $package) { 
							$availablePackageIds[] = $package->getPackage()->getId();
						}
						//get User featured Top Package
						$getUserLastAdvert = $this->getRepository('FaAdBundle:Ad')->getUserLastBasicLiveAdvert($user->getId(), $adId, $adRootCategoryId, $this->container);
						//check last user advert is Basic
						if(isset($availablePackageIds[0]) && in_array($getUserLastAdvert['packageId'], $availablePackageIds) && $getUserLastAdvert['package_price'] == 0){
							//remove basic advert from package list and check Featured Top upsell exist for this package
							array_shift($availablePackageIds); 							
							$packageIds[] = $this->getRepository('FaAdBundle:Ad')->getFeaturedAdForUpgrade($availablePackageIds);
							//no featured top upsell exist
							if(empty($packageIds)) {
								return new JsonResponse(array('error' => 'No Featured Top Package Found', 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
							}
						}
						//get available fetaured top package
						$packages = $this->getRepository('FaPromotionBundle:PackageRule')->getPackageByCategoryId($packageIds[0]);
						//get Print Edition if exist
						$printEditionLimits = $this->getRepository('FaPromotionBundle:Package')->getPrintEditionLimitForPackages($packageIds);
						
						if(!empty($printEditionLimits)) {
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
							
								
								//Add to the cart
								$addCartInfo = $this->addInfoToCart($user->getId(), $adId, $selectedPackageId, $selectedPackagePrintId, $printEditionLimits, $adExpiryDays, $printEditionValues, $request, $categoryId);
								if($addCartInfo) {
									//make it cybersource payment
									$redirectUrl = $request->headers->get('referer');
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
									'packages' => $packages,
									'adExpiryDays' => $adExpiryDays,
									'adId' => $adId,
									'purchase' => true,
									'adObj'   => $getUserLastAdvert,
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
									'adId'	=>	$adId,
									'gaStr' => $gaStr
							);
							
							
							$htmlContent = $this->renderView('FaAdBundle:Ad:upgradeFeaturedmodalBox.html.twig', $parameters);
						}
						
					} else{  
						$error = "Oops! Something went wrong.";						
					}
				}
			}
			return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
		} else {
			return new Response();
		}
	}
	
	private function addInfoToCart($userId, $adId, $selectedPackageId, $selectedPackagePrintId, $printEditionLimits, $adExpiryDays, $printEditionValues, $request, $categoryId) {
		//Add to the cart
		$cart            = $this->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container, false, false, true);
		$cartDetails     = $this->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
		if ($cartDetails) {
			$adCartDetails   = $this->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartIdAndAdId($cart->getId(), $adId);
			if ($adCartDetails) {
				$adCartDetailValue = unserialize($adCartDetails[0]->getValue());
			}
		}
		
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
	 * Upgrade To Featured Ad.
	 *
	 * @param Request $request A Request object.
	 *
	 * @return Response|JsonResponse A Response or JsonResponse object.
	 */
	public function ajaxPaypalPaymentProcessForUpgradeAction(Request $request) {
		if ($request->isXmlHttpRequest()) {
			$redirectToUrl = '';
			$error         = '';
			$htmlContent   = '';
			$deadlockError = '';
			$deadlockRetry = $request->get('deadlockRetry', 0);
			$loggedinUser     = $this->getLoggedInUser();
			$errorMsg	= null;
			$selectedPackageId = $request->get('package_id');
			$printDurationId = '';
			$printEditionValues = [];
			$packageIds = array($selectedPackageId);
			$printEditionSelectedFlag = true;
			$categoryId	= $request->get('categoryId');
			
			if(!empty($loggedinUser)) {
				$user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
				
				if(!empty($user)) {
					$rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
					if ($rootCategoryId != CategoryRepository::ADULT_ID) {
						//get basic Live advert if exist
						$getBasicAdResult = $this->getRepository('FaAdBundle:Ad')->getLastBasicPackageAdvertForUpgrade($rootCategoryId, $user->getId(), null, $this->container);
						if(!empty($getBasicAdResult) && isset($getBasicAdResult[0]['id'])) { 
							$adId = $getBasicAdResult[0][AdSolrFieldMapping::ID];
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

							//Add to the cart
							$addCartInfo = $this->addInfoToCart($user->getId(), $adId, $selectedPackageId, $selectedPackagePrintId, $printEditionLimits, $adExpiryDays, $printEditionValues, $request, $categoryId);
							if($addCartInfo) {
								$redirectUrl = $request->headers->get('referer');
								$this->container->get('session')->set('upgrade_payment_success_redirect_url', $redirectUrl);
								$htmlContent= array(
										'success' 		=> true,
										'redirectUrl' 	=> $redirectUrl
								);
							}
							
							return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
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
	public function amazonPaymentProcessForUpgradeAction(Request $request) {
		if ($request->isXmlHttpRequest()) {
			$redirectToUrl = '';
			$error         = '';
			$htmlContent   = '';
			$deadlockError = '';
			$deadlockRetry = $request->get('deadlockRetry', 0);
			$loggedinUser     = $this->getLoggedInUser();
			$errorMsg	= null;
			$selectedPackageId = $request->get('package_id');
			$printDurationId = '';
			$printEditionValues = [];
			$packageIds = array($selectedPackageId);
			$printEditionSelectedFlag = true;
			$categoryId	= $request->get('categoryId');
			
			if(!empty($loggedinUser)) {
				$user        = $this->getRepository('FaUserBundle:User')->find($loggedinUser->getId());
				
				if(!empty($user)) {
					
					$rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
					if ($rootCategoryId != CategoryRepository::ADULT_ID) {
						//get basic Live advert if exist
						$getBasicAdResult = $this->getRepository('FaAdBundle:Ad')->getLastBasicPackageAdvertForUpgrade($rootCategoryId, $user->getId(), null, $this->container);
						if(!empty($getBasicAdResult) && isset($getBasicAdResult[0]['id'])) {
							$adId = $getBasicAdResult[0][AdSolrFieldMapping::ID];
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
							
							//Add to the cart
							$addCartInfo = $this->addInfoToCart($user->getId(), $adId, $selectedPackageId, $selectedPackagePrintId, $printEditionLimits, $adExpiryDays, $printEditionValues, $request, $categoryId);
							if($addCartInfo) {
								$redirectUrl = $request->headers->get('referer');
								$this->container->get('session')->set('upgrade_payment_success_redirect_url', $redirectUrl);
								$htmlContent= array(
										'success' 		=> true,
										'redirectUrl' 	=> $redirectUrl
								);
							}
							
							return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
						}
					}
					
					return new JsonResponse(array('error' => $error, 'deadlockError' => $deadlockError, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'deadlockRetry' => $deadlockRetry));
				}
			}
		}
	}
	
	
}