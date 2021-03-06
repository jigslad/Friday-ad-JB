<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdMain;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\AdBundle\Entity\AdUserPackage;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Fa\Bundle\AdBundle\Entity\PaaLiteEmailNotification;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Entity\AdLocation;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Repository\PaaLiteEmailNotificationRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentTransactionRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Symfony\Component\Validator\Constraints\IsNull;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
/**
 * Ad post manager.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdPostManager
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Entity manager.
     *
     * @var object
     */
    protected $em;

    /**
     * Request instance.
     *
     * @var object
     */
    protected $request;

    /**
     * Paa field rules.
     *
     * @var array;
     */
    protected $paaFieldRules = array();

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->request   = $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     *  Save ad main.
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMain
     */
    public function saveAdMain()
    {
        $adMain = new AdMain();

        $this->em->persist($adMain);
        $this->em->flush($adMain);

        return $adMain;
    }

    /**
     *  Save ad data.
     *
     * @param array   $data        Ad data.
     * @param integer $adId        Ad id.
     * @param boolean $saveAllData Save all data or not.
     * @param boolean $isAdmin     Save from admin or not.
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function saveAd($data, $adId = null, $saveAllData = false, $isAdmin = false)
    {
        $this->setPaaFieldRules($data['category_id']);

        $isAssignUserToDetachedAd = false;
        $oldPhone                 = null;
        $oldUsePrivacyNumber      = null;
        $user                     = $this->em->getReference('FaUserBundle:User', $data['user_id']);
        if ($isAdmin) {
            if (!$adId || !is_integer($adId)) {
                if ($data['user_id'] == 'no_user') {
                    $user = null;
                }
            } else {
                if (isset($data['email']) && $data['email']) {
                    $user                     = $this->em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $data['email'], 'is_half_account' => '0'));
                    $isAssignUserToDetachedAd = true;
                } elseif ($data['user_id'] == 'no_user') {
                    $user = null;
                }
            }
        }

        $category = $this->em->getReference('FaEntityBundle:Category', $data['category_id']);
        $status   = $this->em->getReference('FaEntityBundle:Entity', $data['ad_status_id']);
        $rootCategoryId = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryId($data['category_id'], $this->container);
        $secondRootCategoryId = $this->em->getRepository('FaEntityBundle:Category')->getSecondRootCategoryId($data['category_id'], $this->container);

        $isSaveImage = false;
        $isNewAd     = false;
        $previousCategoryId = null;
        if (!$adId || !is_integer($adId)) {
            $adMain = $this->saveAdMain();

            // set class meta data
            $metadata = $this->em->getClassMetaData('Fa\Bundle\AdBundle\Entity\Ad');
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

            $ad = new Ad();
            $ad->setId($adMain->getId());
            $ad->setAdMain($adMain);
            $ad->setCreationIp($this->request->getClientIp());
            $ad->setModifyIp($this->request->getClientIp());

            // set notification for draft ad
            if ($user && !isset($data['future_publish_at'])) {
                $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_incomplete', $ad->getId(), $user->getId(), strtotime('+10 minute'), true);
            }

            $isSaveImage = true;
            $isNewAd     = true;
        } else {
            $ad                  = $this->em->getRepository('FaAdBundle:Ad')->find($adId);
            $previousCategoryId  = ($ad && $ad->getCategory())?$ad->getCategory()->getId():null;
            $oldPhone            = ($ad)?$ad->getPhone():null;
            $oldUsePrivacyNumber = ($ad)?$ad->getUsePrivacyNumber():null;

            $ad->setModifyIp($this->request->getClientIp());

            if ($ad->getStatus()->getId() != EntityRepository::AD_STATUS_DRAFT_ID) {
                $ad->setEditedAt(time());
            }
        }

        $ad->setUser($user);
        $ad->setStatus($status);
        $ad->setCategory($category);

        // save postage price for for sale.
        if (isset($data['postage_price']) && $data['postage_price'] && isset($data['delivery_method_option_id']) && (in_array($data['delivery_method_option_id'], array(DeliveryMethodOptionRepository::POSTED_ID, DeliveryMethodOptionRepository::POSTED_OR_COLLECT_ID)))) {
            $ad->setPostagePrice($data['postage_price']);
        } else {
            $ad->setPostagePrice(0);
        }

        // Save ad is trade ad or not
        if ($user) {
            $userRoles = $this->em->getRepository('FaUserBundle:User')->getUserRolesArray($user);
            if (count($userRoles)) {
                if ((in_array(RoleRepository::ROLE_BUSINESS_SELLER, $userRoles)) || (in_array(RoleRepository::ROLE_NETSUITE_SUBSCRIPTION, $userRoles))) {
                    $ad->setIsTradeAd(1);

                    // Save ad specific phone number for business user.
                    $businessPhone = null;
                    if (isset($data['business_phone']) && $data['business_phone']) {
                        $businessPhone = $data['business_phone'];
                    }

                    $ad->setBusinessPhone($businessPhone);
                } elseif (in_array(RoleRepository::ROLE_SELLER, $userRoles)) {
                    $ad->setIsTradeAd(0);
                }
            }
        } else {
            $ad->setIsTradeAd(null);
        }

        if (isset($data['ad_type_id']) && $data['ad_type_id']) {
            $ad->setType($this->em->getReference('FaEntityBundle:Entity', $data['ad_type_id']));
        }

        // Save default title for cars, commericial veh. and motorsbike if title is blank.
        if (in_array($secondRootCategoryId, array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID, CategoryRepository::MOTORBIKES_ID)) && (!isset($data['title']) || !$data['title'])) {
            $categoryPathArray = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($data['category_id'], false, $this->container);
            $data['title']     = null;
            if (in_array($secondRootCategoryId, array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID))) {
                $data['title'] = implode(' ', array_slice($categoryPathArray, -2, 2));
                if (isset($data['reg_year']) && $data['reg_year']) {
                    $data['title'] .= ' '.$data['reg_year'];
                }
            } elseif (in_array($secondRootCategoryId, array(CategoryRepository::MOTORBIKES_ID))) {
                if (isset($data['make_id_autocomplete']) && $data['make_id_autocomplete']) {
                    $data['title'] = $data['make_id_autocomplete'];
                }

                if (isset($data['model_id']) && $data['model_id']) {
                    $data['title'] .= ' '.$this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Entity', $data['model_id']);
                }

                if (isset($data['reg_year']) && $data['reg_year']) {
                    $data['title'] .= ' '.$data['reg_year'];
                }
            }

            $data['title'] = trim($data['title']);
        }

        $this->setAdPaaFields($ad, $data);

        // save qty for for sale if user does not have item qty upsell.
        if ($user) {
            if ($rootCategoryId == CategoryRepository::FOR_SALE_ID && isset($data['qty'])) {
                $userUpsells = $this->em->getRepository('FaUserBundle:UserUpsell')->getUserUpsellArray($user->getId());
                if (!in_array(UpsellRepository::SHOP_ITEM_QUANTITIES_ID, $userUpsells)) {
                    $ad->setQty(1);
                }
            }
        } else {
            if ($rootCategoryId == CategoryRepository::FOR_SALE_ID) {
                $ad->setQty(1);
            }
        }

        // save phone for detached ad and remove phone when detached ad moved to normal ad if user assigned to ad.
        // Save email in edit if exist
        if ($isAdmin) {
            $phone            = null;
            $usePrivacyNumber = null;

            if (isset($data['phone']) && $data['phone'] && !$user) {
                $phone = $data['phone'];
            }

            if (isset($data['use_privacy_number']) && $data['use_privacy_number'] && $phone) {
                $usePrivacyNumber = $data['use_privacy_number'];
            }

            $ad->setPhone($phone ? (str_replace(array(' '), '', $phone)) : null);
            //$ad->setUsePrivacyNumber($usePrivacyNumber);
            $ad->setUsePrivacyNumber(0); // made zero when we removed Yac FFR-3756

            if (isset($data['email']) && $data['email']) {
                $ad->setEmail($data['email']);
            }

            if (isset($data['future_publish_at']) && $data['future_publish_at']) {
                $ad->setFuturePublishAt(CommonManager::getTimeStampFromStartDate($data['future_publish_at']));
                $ad->setStatus($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_SCHEDULED_ADVERT_ID));
            } else {
                $ad->setFuturePublishAt(null);
            }

            // save the source as admin when ad posted from admin side.
            if ($isNewAd) {
                $ad->setSource(AdRepository::SOURCE_ADMIN);
                $loggedinAdminUser = CommonManager::getLoggedInUser($this->container);
                if ($loggedinAdminUser) {
                    $ad->setAdminUserId($loggedinAdminUser->getId());
                }
            }
        }
        
        if (!isset($data['payment_method_id'])) {
            $ad->setPaymentMethodId(null);
        }

        $this->em->persist($ad);
        $this->em->flush($ad);
        $this->em->getRepository('FaAdBundle:AdIpAddress')->checkAndLogIpAddress($ad, $this->request->getClientIp());



        $this->saveVerticalData($ad, $data, $previousCategoryId, $isAdmin);
        
        if ($saveAllData) {
            $this->saveAdLocation($ad, $data);

            if ($isSaveImage) {
                $this->saveAdImages($ad, $isAdmin, $data);
            }
        }

        // Change ad moderate status by ad staus if ad edited from admin.
        if ($isAdmin) {
            $this->replaceAdModerateStatusWithAdStatus($ad);
            $this->makeAdImagesActive($ad);

            // Update ad data to solr
            $this->container->get('fa_ad.entity_listener.ad')->handleSolr($ad, 1);

            // Add user id to package and payment of this ad.
            if ($isAssignUserToDetachedAd) {
                $this->assignUserToDetachedAd($ad);
            }
        }

        // Update ad yac number.
        //$this->updateAdYacNumber($ad, $isNewAd, $oldPhone, $oldUsePrivacyNumber, $isAssignUserToDetachedAd);
        // commented when we removed Yac FFR-3756

        if ($isAdmin) {
            $this->container->get('session')->set('admin_ad_id_'.(isset($data['admin_ad_counter']) ? $data['admin_ad_counter'] : ''), $ad->getId());
        } else {
            $this->container->get('session')->set('ad_id', $ad->getId());
        }

        return $ad;
    }

    /**
     *  Save paa lite ad data.
     * @param array   $user        User data.
     * @param array   $data        Ad data.
     * @param integer $adId        Ad id.
     * @param boolean $saveAllData Save all data or not.
     * @param boolean $isAdmin     Save from admin or not.
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function savePaaLiteAd($data, $campaign = null)
    {
        $this->setPaaLiteFieldRules($data['campaign_id']);

        $user                     = $this->em->getReference('FaUserBundle:User', $data['user_id']);
        
        $category = $this->em->getReference('FaEntityBundle:Category', $data['category_id']);
        $status   = $this->em->getReference('FaEntityBundle:Entity', $data['ad_status_id']);
        $rootCategoryId = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryId($data['category_id'], $this->container);
        $secondRootCategoryId = $this->em->getRepository('FaEntityBundle:Category')->getSecondRootCategoryId($data['category_id'], $this->container);

        $isSaveImage = true;
        $isNewAd     = true;
        $previousCategoryId = null;
       
        $adMain = $this->saveAdMain();

        // set class meta data
        $metadata = $this->em->getClassMetaData('Fa\Bundle\AdBundle\Entity\Ad');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $ad = new Ad();
        $ad->setId($adMain->getId());
        $ad->setAdMain($adMain);
        $ad->setCreationIp($this->request->getClientIp());
        $ad->setModifyIp($this->request->getClientIp());

        // set notification for draft ad
        if ($user && !isset($data['future_publish_at'])) {
            $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_incomplete', $ad->getId(), $user->getId(), strtotime('+10 minute'), true);
        }

        $ad->setUser($user);
        $ad->setStatus($status);
        $ad->setCategory($category);
        $ad->setCampaign($campaign);
        $ad->setSource('paa_lite');

        // save postage price for for sale.
        if (isset($data['postage_price']) && $data['postage_price'] && isset($data['delivery_method_option_id']) && (in_array($data['delivery_method_option_id'], array(DeliveryMethodOptionRepository::POSTED_ID, DeliveryMethodOptionRepository::POSTED_OR_COLLECT_ID)))) {
            $ad->setPostagePrice($data['postage_price']);
        } else {
            $ad->setPostagePrice(0);
        }

        // Save ad is trade ad or not
        if ($user) {
            $userRoles = $this->em->getRepository('FaUserBundle:User')->getUserRolesArray($user);
            if (count($userRoles)) {
                if ((in_array(RoleRepository::ROLE_BUSINESS_SELLER, $userRoles)) || (in_array(RoleRepository::ROLE_NETSUITE_SUBSCRIPTION, $userRoles))) {
                    $ad->setIsTradeAd(1);

                    // Save ad specific phone number for business user.
                    $businessPhone = null;
                    if (isset($data['business_phone']) && $data['business_phone']) {
                        $businessPhone = $data['business_phone'];
                    }

                    $ad->setBusinessPhone($businessPhone);
                } elseif (in_array(RoleRepository::ROLE_SELLER, $userRoles)) {
                    $ad->setIsTradeAd(0);
                }
            }
        } else {
            $ad->setIsTradeAd(null);
        }

        if (isset($data['ad_type_id']) && $data['ad_type_id']) {
            $ad->setType($this->em->getReference('FaEntityBundle:Entity', $data['ad_type_id']));
        }

        // Save default title for cars, commericial veh. and motorsbike if title is blank.
        if (in_array($secondRootCategoryId, array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID, CategoryRepository::MOTORBIKES_ID)) && (!isset($data['title']) || !$data['title'])) {
            $categoryPathArray = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($data['category_id'], false, $this->container);
            $data['title']     = null;
            if (in_array($secondRootCategoryId, array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID))) {
                $data['title'] = implode(' ', array_slice($categoryPathArray, -2, 2));
                if (isset($data['reg_year']) && $data['reg_year']) {
                    $data['title'] .= ' '.$data['reg_year'];
                }
            } elseif (in_array($secondRootCategoryId, array(CategoryRepository::MOTORBIKES_ID))) {
                if (isset($data['make_id_autocomplete']) && $data['make_id_autocomplete']) {
                    $data['title'] = $data['make_id_autocomplete'];
                }

                if (isset($data['model_id']) && $data['model_id']) {
                    $data['title'] .= ' '.$this->container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Entity', $data['model_id']);
                }

                if (isset($data['reg_year']) && $data['reg_year']) {
                    $data['title'] .= ' '.$data['reg_year'];
                }
            }

            $data['title'] = trim($data['title']);
        }

        $this->setAdPaaFields($ad, $data);

        // save qty for for sale if user does not have item qty upsell.
        if ($user) {
            if ($rootCategoryId == CategoryRepository::FOR_SALE_ID && isset($data['qty'])) {
                $userUpsells = $this->em->getRepository('FaUserBundle:UserUpsell')->getUserUpsellArray($user->getId());
                if (!in_array(UpsellRepository::SHOP_ITEM_QUANTITIES_ID, $userUpsells)) {
                    $ad->setQty(1);
                }
            }
        } else {
            if ($rootCategoryId == CategoryRepository::FOR_SALE_ID) {
                $ad->setQty(1);
            }
        }

        // save phone for detached ad and remove phone when detached ad moved to normal ad if user assigned to ad.
        // Save email in edit if exist
        
        if (!isset($data['payment_method_id'])) {
            $ad->setPaymentMethodId(null);
        } else {
            $ad->setPaymentMethodId($data['payment_method_id']);
            if (in_array($data['payment_method_id'], array(PaymentRepository::PAYMENT_METHOD_PAYPAL_ID, PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID))) {
                $this->setPaypalDetailsForUser($data);
            }
        }

        $this->em->persist($ad);
        $this->em->flush($ad);
        $this->em->getRepository('FaAdBundle:AdIpAddress')->checkAndLogIpAddress($ad, $this->request->getClientIp());

        $this->saveVerticalData($ad, $data, $previousCategoryId);

        
        $this->saveAdLocation($ad, $data);

        if ($isSaveImage && (isset($data['photo_error']) && $data['photo_error']>0)) {
            $this->saveAdImages($ad, false, $data);
        }

        // Update ad yac number.
        //$this->updateAdYacNumber($ad, $isNewAd);
        $this->setAdUserFreePackage($ad, $category->getId(), $user);

        if ($this->container->get('session')->get('redirect_to_cart')==0) {
            $paaLiteEmailNotification = new PaaLiteEmailNotification();
            $paaLiteEmailNotification->setAd($ad);
            $paaLiteEmailNotification->setUser($user);
            $paaLiteEmailNotification->setCreatedAt(time());
            $paaLiteEmailNotification->setIsAdConfirmationMailSent(0);
            $paaLiteEmailNotification->setIsAdConfirmationNotificationSent(0);

            $this->em->persist($paaLiteEmailNotification);
            $this->em->flush($paaLiteEmailNotification);
            
            $this->em->getRepository('FaAdBundle:Ad')->sendCompleteAdvertEmail($user, $ad, $paaLiteEmailNotification, $this->container);
            $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('complete_advert', $ad->getId(), $user->getId());
            //$this->container->get('session')->set('ad_id', $ad->getId());
            $this->container->get('session')->set('show_ad_live_popup', 1);
            $this->container->get('session')->set('paa_lite_ad_success', 1);
        }
        
        return $ad;
    }

    protected function setPaypalDetailsForUser($data)
    {
        $paymentMethodId = $data['payment_method_id'];
        $paypalEmail     = $data['paypal_email'];
        $paypalFirstName = $data['paypal_first_name'];
        $paypalLastName  = $data['paypal_last_name'];

        $userObj = $this->getLoggedInUser();
        $userObj->setPaypalEmail($paypalEmail);
        $userObj->setPaypalFirstName($paypalFirstName);
        $userObj->setPaypalLastName($paypalLastName);
        $userObj->setIsPaypalVefiried(1);
        $this->em->persist($userObj);
        $this->em->flush($userObj);
    }
    protected function setAdUserFreePackage($ad, $categoryId, $user)
    {
        $packageIds = array();
        $adPackageId = '';
        $adId = $ad->getId();
        $userId = $user->getId();
        $systemUserRoles  = $this->em->getRepository('FaUserBundle:Role')->getRoleArrayByType('C', $this->container);
        $userRole         = $this->em->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);
        $userRolesArray[] = array_search($userRole, $systemUserRoles);
        $locationGroupIds = $this->em->getRepository('FaAdBundle:AdLocation')->getLocationGroupIdForAd($adId, true);
        $packages         = $this->em->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, array(), $this->container);
        $categoryId = $ad->getCategory()->getId();

        foreach ($packages as $package) {
            $packageIds[] = array('id'=> $package->getPackage()->getId(),'price'=>$package->getPackage()->getPrice());
        }
        if (!empty($packageIds)) {
            usort($packageIds, function ($a, $b) {
                return $a['price'] - $b['price'];
            });
            $adPackageId = $packageIds[0]['id'];

            if ($adPackageId!='' && $packageIds[0]['price']>0) {
                $returnTxt = $this->addPackageToCart($adPackageId, $categoryId, $ad, $user);
                if ($returnTxt=='null') {
                    $this->container->get('session')->set('redirect_to_cart', 1);
                    $this->container->get('session')->set('cart_ad_id', $ad->getId());
                    $this->container->get('session')->set('cart_package_id', $adPackageId);
                    $adDraftStatus =  $this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_DRAFT_ID);
                    $ad->setStatus($adDraftStatus);
                    $this->em->persist($ad);
                    $this->em->flush($ad);
                } else {
                    $this->container->get('session')->set('show_error_pop_up', 1);
                    $this->container->get('session')->set('paa-lite-error', $returnTxt);
                }
            } else {
                $adUserPackage = new AdUserPackage();

                // find & set package
                $selpackage = $this->em->getRepository('FaPromotionBundle:Package')->find($adPackageId);
                $adUserPackage->setPackage($selpackage);

                // set ad
                $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($adId);
                $adUserPackage->setAdMain($adMain);
                $adUserPackage->setAdId($adId);
                $adUserPackage->setStatus(AdUserPackageRepository::STATUS_ACTIVE);
                $adUserPackage->setStartedAt(time());
                if ($selpackage->getDuration()) {
                    $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($selpackage->getDuration()));
                } elseif ($ad) {
                    $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                    $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
                }

                // set user
                if ($user) {
                    $adUserPackage->setUser($user);
                }

                $adUserPackage->setPrice($selpackage->getPrice());
                $adUserPackage->setDuration($selpackage->getDuration());
                $this->em->persist($adUserPackage);
                $this->em->flush();

                foreach ($selpackage->getUpsells() as $upsell) {
                    $this->addAdUserPackageUpsell($ad, $adUserPackage, $upsell);
                }

                $adExpiryDays     = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
                $selectedPackagePrintId = null;
                $printEditionValues = array();
                
                $cart            = $this->em->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container, false, false, false, true);
                $cartDetails     = $this->em->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
                if ($cartDetails) {
                    $adCartDetails   = $this->em->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartIdAndAdId($cart->getId(), $adId);
                    if ($adCartDetails) {
                        $adCartDetailValue = unserialize($adCartDetails[0]->getValue());
                    }
                }

                $this->container->get('session')->set('paa_lite_card_code', $cart->getCartCode());
                //get Package Detail
                $selectedPackageObj = $this->em->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $adPackageId));
                $selectedPackagePrint = null;
                
                $privateUserAdParams = $this->em->getRepository('FaAdBundle:Ad')->getPrivateUserPostAdParams($userId, $categoryId, $adId, $this->container);
                
                //check if cart is empty and package is free then process ad
                $selectedPackage = $this->em->getRepository('FaPromotionBundle:Package')->find($adPackageId);
                
                //remove if same ad is in cart.
                if (count($cartDetails) == 1 && $cartDetails[0]['ad_id'] == $adId) {
                    unset($cartDetails[0]);
                }
        
                $this->addAdPackage($adId, $adPackageId, $adExpiryDays, $cart, $selectedPackagePrintId, false, $printEditionValues, $privateUserAdParams);

                $this->em->beginTransaction();

                try {
                    $cart->setPaymentMethod('free');
                    $this->em->persist($cart);
                    $this->em->flush($cart);
                    $paymentId = $this->em->getRepository('FaPaymentBundle:Payment')->processPaymentSuccess($cart->getCartCode(), null, $this->container);
                    $this->em->getConnection()->commit();
                } catch (\Exception $e) {
                    $this->em->getConnection()->rollback();
                    CommonManager::sendErrorMail($this->container, 'Error: Problem in payment', $e->getMessage(), $e->getTraceAsString());
                    $error = 'Problem in payment.';
                }
            }
        }
    }

    protected function addPackageToCart($adPackageId, $categoryId, $ad, $user)
    {
        $adId = $ad->getId();
        $userId   = ($user ? $user->getId() : null);
        $error = 'null';
       
        //check if user has already purchased pkg or not
        $adUserPackage = $this->em->getRepository('FaAdBundle:AdUserPackage')->getPurchasedAdPackage($adId);
        if ($adUserPackage && $adUserPackage->getStatus() == 1) {
            $error = 'You already have purchased package for ad '.$adId;
        }

        $privateUserUrlParams = array();
        $oldSelectedPrintEditions = array();
        $selectedPrintEditions = array();
        $defaultSelectedPrintEditions = array();
        $selectedPackageId = $adPackageId;
        $printEditionSelectedFlag = true;
        $errorMsg         = null;
        $adCartDetails = null;
        $adCartDetailValue = array();
        $isAdultAdvertPresent = 0;
        /*$cart            = $this->em->getRepository('FaPaymentBundle:Cart')->getUserCart($userId, $this->container,false,false,false,true);
        $cartDetails     = $this->em->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
        if ($cartDetails) {
            $adCartDetails   = $this->em->getRepository('FaPaymentBundle:Transaction')->getTransactionsByCartIdAndAdId($cart->getId(), $adId);
            if ($adCartDetails) {
                $adCartDetailValue = unserialize($adCartDetails[0]->getValue());
            }
        }*/

        $categoryId       = $ad->getCategory()->getId();
        $adRootCategoryId = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
        if ($adRootCategoryId == CategoryRepository::ADULT_ID) {
            $isAdultAdvertPresent = 1;
        }
        $privateUserAdParams = $this->em->getRepository('FaAdBundle:Ad')->getPrivateUserPostAdParams($userId, $categoryId, $adId, $this->container);
        
        $locationGroupIds = $this->em->getRepository('FaAdBundle:AdLocation')->getLocationGroupIdForAd($adId, true);
        $adExpiryDays     = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->container);
        $userCreditId = null;
        $selectedPackagePrintId = null;

        $selectedPackageObj = $this->em->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $selectedPackageId));
        if ($selectedPackageObj && $selectedPackageObj->getPrice() <= 0 && isset($privateUserAdParams['allowPrivateUserToPostAdFlag']) && !$privateUserAdParams['allowPrivateUserToPostAdFlag']) {
            $error = 'Sorry, maximum number of ad placements reached.';
        }
        //check for print edition
        $printEditionValues = array();

        $totalCredit = null;
        //check for user credit
        /*if ($userCreditId) {
            $totalCredit = 1;
            if ($selectedPackagePrintId) {
                $selectedPackagePrintObj = $this->em->getRepository('FaPromotionBundle:PackagePrint')->findOneBy(array('id' => $selectedPackagePrintId));
                $totalWeeks = (int) $selectedPackagePrintObj->getDuration();
                $totalCredit = ceil(($totalWeeks / 4));
            }
            $userActiveCredits = $this->em->getRepository('FaUserBundle:UserCredit')->getActiveCreditForUserByCategory($userId, $adRootCategoryId, $cart->getId(), $adId);
            if (count($userActiveCredits)) {
                $activeShopPackageDetail = $this->em->getRepository('FaUserBundle:UserPackage')->getShopPackageDetailByUserIdForAdReport($userId);
                $packageSrNoCredits = $this->em->getRepository('FaUserBundle:UserCredit')->getPackageWiseActiveCreditForUser($userActiveCredits);
                $isValidUserCredit = ($selectedPackageObj->getPackageSrNo() && isset($userActiveCredits[$userCreditId]) && in_array($selectedPackageObj->getPackageSrNo(), $userActiveCredits[$userCreditId]['package_sr_no']) && $userActiveCredits[$userCreditId]['credit'] >= $totalCredit);
                if (!$isValidUserCredit) {
                    $error = 'Sorry you do not have enough credits.';
                }
            } else {
                $error = 'Sorry you do not have enough credits.';
            }
        }*/

        if ($printEditionSelectedFlag) {
            // Remove session for redirec back to PAA steps.
            $this->container->get('session')->remove('back_url_from_ad_package_page');
                        
            //$this->addAdPackage($adId, $selectedPackageId, $adExpiryDays, $cart, $selectedPackagePrintId, null, null, true, $printEditionValues, $userCreditId, $totalCredit, $privateUserAdParams);
        }

        return $error;
    }

    public function addAdPackage($adId, $packageId, $adExpiryDays, $cart, $selectedPackagePrintId, $type = null, $activeAdUserPackageId = null, $addAdToModeration = false, $printEditionValues = array(), $userCreditId = null, $totalCredit = null, $privateUserAdParams = array())
    {
        $ad      = $this->em->getRepository('FaAdBundle:Ad')->find($adId);
        $user    = $ad->getUser();
        $package = $this->em->getRepository('FaPromotionBundle:Package')->find($packageId);

        $this->em->getRepository('FaPaymentBundle:Cart')->addPackageToCart($user->getId(), $adId, $packageId, $this->container, true, $adExpiryDays, $selectedPackagePrintId, $type, $activeAdUserPackageId, $addAdToModeration, $cart, $printEditionValues, $userCreditId, $totalCredit, $privateUserAdParams);

        //apply discount code if it is already applied for one ad
        $loggedinUser = $user;
        $cart = $this->em->getRepository('FaPaymentBundle:Cart')->getUserCart($loggedinUser->getId(), $this->container, false, false, false, true);
        $cartValue = unserialize($cart->getValue());
        if ($cart->getDiscountAmount() > 0 && isset($cartValue['discount_values']) && count($cartValue['discount_values']) && isset($cartValue['discount_values']['code'])) {
            $codeObj = $this->em->getRepository('FaPromotionBundle:PackageDiscountCode')->findOneBy(array('code' => $cartValue['discount_values']['code'], 'status' => 1));
            $cartDetails  = $this->em->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
            $this->em->getRepository('FaPaymentBundle:TransactionDetail')->removeCodeFromAllItems($cartDetails);
            $this->em->getRepository('FaPromotionBundle:PackageDiscountCode')->processDiscountCode($codeObj, $cart, $cartDetails, $loggedinUser, $this->container, false);
        } elseif ($cart->getDiscountAmount() <= 0 && isset($cartValue['discount_values']) && count($cartValue['discount_values']) && isset($cartValue['discount_values']['code'])) {
            $cartDetails  = $this->em->getRepository('FaPaymentBundle:Transaction')->getCartDetail($cart->getId());
            $this->em->getRepository('FaPaymentBundle:TransactionDetail')->removeCodeFromAllItems($cartDetails);
        }
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
        $adUserPackageUpsellObj = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findOneBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId(), 'status' => 1, 'upsell' => $upsell->getId()));
        if (!$adUserPackageUpsellObj) {
            $adUserPackageUpsell = new AdUserPackageUpsell();
            $adUserPackageUpsell->setUpsell($upsell);

            // set ad user package id.
            if ($adUserPackage) {
                $adUserPackageUpsell->setAdUserPackage($adUserPackage);
            }

            // set ad
            $adMain = $this->em->getRepository('FaAdBundle:AdMain')->find($adId);
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
                $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            $this->em->persist($adUserPackageUpsell);
            $this->em->flush();
        }
    }

    /**
     * Update YAC number for ad.
     *
     * @param object $ad Ad instance.
     */
    protected function updateAdYacNumber($ad, $isNewAd = false, $oldPhone = null, $oldUsePrivacyNumber = null, $isAssignUserToDetachedAd = false)
    {
        if ($ad->getUser()) {
            if ($isAssignUserToDetachedAd || $isNewAd) {
                if ($ad->getUser()->getIsPrivatePhoneNumber() && $ad->getUser()->getPhone()) {
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-yac-number allocate --ad_id='.$ad->getId().' >/dev/null &');
                } else {
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-yac-number setsold --ad_id='.$ad->getId().' >/dev/null &');
                }
            } elseif ($ad->getUser()->getIsPrivatePhoneNumber() && $ad->getUser()->getPhone() && !$ad->getPrivacyNumber()) {
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-yac-number allocate --ad_id='.$ad->getId().' >/dev/null &');
            } else {
                //Phone Number Edited & Use Privacy Number is set
                if ($ad->getUser()->getIsPrivatePhoneNumber() && $ad->getUsePrivacyNumber() && ($oldPhone && $oldPhone != $ad->getPhone())) {
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-yac-number edit --ad_id='.$ad->getId().' >/dev/null &');
                }
            }
        } else {
            // Detached ad : update yac number if phone number changes and ad has set user privacy number.
            if ($ad->getUsePrivacyNumber() && ($oldPhone && $oldPhone != $ad->getPhone())) {
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-yac-number edit --ad_id='.$ad->getId().' >/dev/null &');
            }

            // Detached ad : update yac number if privacy phone number setting is changes.
            if ($oldUsePrivacyNumber != $ad->getUsePrivacyNumber()) {
                if ($ad->getUsePrivacyNumber()) {
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-yac-number allocate --ad_id='.$ad->getId().' >/dev/null &');
                } else {
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-yac-number setsold --ad_id='.$ad->getId().' >/dev/null &');
                }
            }
        }
    }

    /**
     * Assign user to ad package and payment after detached ad assign with user.
     *
     * @param object $ad Ad instance.
     */
    protected function assignUserToDetachedAd($ad)
    {
        $user = $ad->getUser();

        // Assign user to detached ad package
        $this->em->getRepository('FaAdBundle:AdUserPackage')->getBaseQueryBuilder()
                 ->update()
                 ->set(AdUserPackageRepository::ALIAS.'.user', $user->getId())
                 ->andWhere(AdUserPackageRepository::ALIAS.'.ad_id = '.$ad->getId())
                 ->getQuery()
                 ->execute();

        // Assign user to detached ad payment transaction
        $this->em->getRepository('FaPaymentBundle:PaymentTransaction')->getBaseQueryBuilder()
                 ->update()
                 ->set(PaymentTransactionRepository::ALIAS.'.user', $user->getId())
                 ->andWhere(PaymentTransactionRepository::ALIAS.'.ad = '.$ad->getId())
                 ->getQuery()
                 ->execute();

        // Assign user to detached ad payment
        $paymentTransactions = $this->em->getRepository('FaPaymentBundle:PaymentTransaction')->findBy(array('ad' => $ad->getId()));
        if ($paymentTransactions && count($paymentTransactions)) {
            $paymentIds = array();
            foreach ($paymentTransactions as $paymentTransaction) {
                $paymentIds[] = $paymentTransaction->getPayment()->getId();
            }

            if (count($paymentIds)) {
                $this->em->getRepository('FaPaymentBundle:Payment')->getBaseQueryBuilder()
                ->update()
                ->set(PaymentRepository::ALIAS.'.user', $user->getId())
                ->andWhere(PaymentRepository::ALIAS.'.id IN (:payment_ids)')
                ->setParameter('payment_ids', $paymentIds)
                ->getQuery()
                ->execute();
            }
        }
    }

    /**
     * Save ad data.
     *
     * @param object $ad   Ad instance.
     * @param array  $data Ad data.
     */
    protected function setAdPaaFields($ad, $data = array())
    {
        $paaFields = $this->em->getRepository('FaAdBundle:PaaField')->getCommonPaaFields();
        foreach ($paaFields as $paaField) {
            $field = $paaField->getField();
            if (isset($data[$field])) {
                if ($field == 'delivery_method_option_id') {
                    $deliveryMethodOption = $this->em->getReference('FaPaymentBundle:DeliveryMethodOption', $data[$field]);
                    $this->setField('delivery_method_option', $deliveryMethodOption, $ad);
                } else {
                    if ($this->getFieldType($field) == 'text_float' || $this->getFieldType($field) == 'text_int') {
                        $fieldData = str_replace(',', '', $data[$field]);
                        $this->setField($field, $fieldData, $ad);
                    } else {
                        $this->setField($field, $data[$field], $ad);
                    }
                }
            }
        }
    }

    /**
     * Save ad location.
     *
     * @param object $ad   Ad instance.
     * @param array  $data Ad data.
     */
    protected function saveAdLocation($ad, $data = array())
    {
        $insertAdLocationFlag = true;
        $adLocationArray = $this->em->getRepository('FaAdBundle:AdLocation')->getAdLocationDataForLog($ad->getId());
        $adLocationFormArray = array();

        $location    = isset($data['location']) ? $data['location'] : null;
        $postCode    = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($location);
        $postcodeVal = null;
        $latitude    = null;
        $longitude   = null;
        $locality    = null;
        $town        = null;
        $locationArea = isset($data['area']) ? $data['area'] : null;
        $area = null;
        
        if ($postCode && $postCode->getTownId()) {
            $town      = $this->em->getRepository('FaEntityBundle:Location')->find($postCode->getTownId());
            $postcodeVal  = $postCode->getPostCode();
            $latitude  = $postCode->getLatitude();
            $longitude = $postCode->getLongitude();
            if ($postCode->getLocalityId()) {
                $locality  = $this->em->getRepository('FaEntityBundle:Locality')->find($postCode->getLocalityId());
            }
        } else {
            if (preg_match('/^\d+$/', $location)) {
                $town = $this->em->getRepository('FaEntityBundle:Location')->getTownAndAreaById($location, $this->container);
            } elseif (preg_match('/^([\d]+,[\d]+)$/', $location)) {
                $localityTown = explode(',', $location);
                $localityId = $localityTown[0];
                $townId     = $localityTown[1];
                if ($localityId && $townId) {
                    $postCode  = $this->em->getRepository('FaEntityBundle:Postcode')->findOneBy(array('locality_id' => $localityId, 'town_id' => $townId));
                    $locality  = $this->em->getRepository('FaEntityBundle:Locality')->findOneBy(array('id' => $localityId));
                    $town      = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $townId, 'lvl' => '3'));
                }
            } else {
                $town = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $location, 'lvl' => '3'));
                if (!$town) {
                    $locality = $this->em->getRepository('FaEntityBundle:Locality')->findOneBy(array('name' => $location));
                    if ($locality) {
                        $postCode = $this->em->getRepository('FaEntityBundle:Postcode')->findOneBy(array('locality_id' => $locality->getId()));
                        $town     = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $postCode->getTownId(), 'lvl' => '3'));
                    }
                }
            }

            if ($postCode) {
                $latitude  = $postCode->getLatitude();
                $longitude = $postCode->getLongitude();
            } elseif ($town) {
                $latitude  = $town->getLatitude();
                $longitude = $town->getLongitude();
            }
        }
        if ($latitude!='' && $latitude!=0.00000000 && $longitude!='' && $longitude!=0.00000000) {
            $postalcodeVal = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodTextByLatLong($latitude, $longitude);
        }
        
        //get Location Area record
        if ($town) {
            //check area is based on London Location
            if ($town && ($town->getId() == LocationRepository::LONDON_TOWN_ID || $town->getLvl() == 4)) {
                if (preg_match('/^\d+$/', $locationArea)) {
                    $area = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id'=>$locationArea, 'lvl'=>'4'));
                } else {
                    $area = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array(
                            'name' 	=> $locationArea,
                            'lvl'	=>'4'
                    ));
                }
                
                if (!empty($area)) {
                    $latitude  = $area->getLatitude();
                    $longitude = $area->getLongitude();
                }
            }
        }
        
        $adLocationFormArray[]['country_id']  = ($town ? $town->getParent()->getParent()->getId() : null);
        $adLocationFormArray[]['domicile_id'] = ($town ? $town->getParent()->getId() : null);
        $adLocationFormArray[]['town_id']     = ($town ? $town->getId() : null);
        $adLocationFormArray[]['locality_id'] = ($locality ? $locality->getId() : null);
        $adLocationFormArray[]['postcode']    = $postcodeVal;
        $adLocationFormArray[]['latitude']    = round($latitude, 6);
        $adLocationFormArray[]['longitude']   = round($longitude, 6);
        $adLocationFormArray[]['area_id']     = ($area ? $area->getId() : null);

        if (md5(serialize($adLocationArray)) == md5(serialize($adLocationFormArray))) {
            $insertAdLocationFlag = false;
        }
        // Remove previous locations and add new locations
        if ($insertAdLocationFlag) {
            foreach ($ad->getAdLocations() as $adLocation) {
                $this->em->remove($adLocation);
                $this->em->flush();
            }
        }
        
        if ($town && $insertAdLocationFlag) {
            $adLocation = new AdLocation();
            $adLocation->setAd($ad);
            if (isset($area) && $area->getLvl() == '4') {
                $adLocation->setLocationTown($area->getParent());
                $adLocation->setLocationDomicile($area->getParent()->getParent());
                $adLocation->setLocationCountry($area->getParent()->getParent()->getParent());
                $adLocation->setPostcode($postcodeVal);
                $adLocation->setLatitude($latitude);
                $adLocation->setLongitude($longitude);
                $adLocation->setLocationArea($area);
            } else {
                $adLocation->setLocationTown($town);
                $adLocation->setLocationDomicile($town->getParent());
                $adLocation->setLocationCountry($town->getParent()->getParent());
                $adLocation->setPostcode($postcodeVal);
                $adLocation->setLatitude($latitude);
                $adLocation->setLongitude($longitude);
                $adLocation->setLocationArea(null);
            }

            if ($locality) {
                $adLocation->setLocality($locality);
            }
            
            
            $this->em->persist($adLocation);
            $this->em->flush();
        }
    }

    /**
     * Save ad data in edit mode.
     *
     * @param object  $ad      Ad instance.
     * @param boolean $isAdmin Is from admin side or frontside.
     */
    private function saveAdImages($ad, $isAdmin = false, $data = array())
    {
        if ($isAdmin) {
            $adTempId = $this->container->get('session')->get('admin_ad_id_'.(isset($data['admin_ad_counter']) ? $data['admin_ad_counter'] : ''));
        } elseif ($this->container->get('session')->has('paa_image_id')) {
            $adTempId = $this->container->get('session')->get('paa_image_id');
        } else {
            $adTempId = $this->container->get('session')->get('ad_id');
        }

        $webPath        = $this->container->get('kernel')->getRootDir().'/../web';
        $adTempImageDir = $webPath.'/'.$this->container->getParameter('fa.ad.image.tmp.dir');
        $adImageDir     = $webPath.'/'.$this->container->getParameter('fa.ad.image.dir');

        CommonManager::createGroupDirectory($adImageDir, $ad->getId());
        $adGroupDir = CommonManager::getGroupDirNameById($ad->getId());

        $adImages = $this->em->getRepository('FaAdBundle:AdImage')->getAdImages($adTempId);
        if (count($adImages)) {
            foreach ($adImages as $adImage) {
                $adTempImg = $adTempImageDir.'/'.$adTempId.'_'.$adImage->getHash().'.jpg';
                $adImg     = $adImageDir.'/'.$adGroupDir.'/'.$ad->getId().'_'.$adImage->getHash().'.jpg';

                if (is_file($adTempImg)) {
                    rename($adTempImg, $adImg);
                }

                $thumbSize = $this->container->getParameter('fa.image.thumb_size');
                if (is_array($thumbSize)) {
                    foreach ($thumbSize as $d) {
                        $d         = strtoupper($d);
                        $adTempImg = $adTempImageDir.'/'.$adTempId.'_'.$adImage->getHash().'_'.$d.'.jpg';
                        $adImg     = $adImageDir.'/'.$adGroupDir.'/'.$ad->getId().'_'.$adImage->getHash().'_'.$d.'.jpg';

                        if (is_file($adTempImg)) {
                            rename($adTempImg, $adImg);
                        }
                    }
                }

                $cropSize = $this->container->getParameter('fa.image.crop_size');
                if (is_array($cropSize)) {
                    foreach ($cropSize as $d) {
                        $d         = strtoupper($d);
                        $adTempImg = $adTempImageDir.'/'.$adTempId.'_'.$adImage->getHash().'_'.$d.'_c.jpg';
                        $adImg     = $adImageDir.'/'.$adGroupDir.'/'.$ad->getId().'_'.$adImage->getHash().'_'.$d.'_c.jpg';

                        if (is_file($adTempImg)) {
                            rename($adTempImg, $adImg);
                        }
                    }
                }

                $adImage->setAd($ad);
                $adImage->setSessionId(null);
                $adImage->setStatus(1);
                $adImage->setPath($this->container->getParameter('fa.ad.image.dir').'/'.$adGroupDir);
                $this->em->persist($adImage);
                $this->em->flush();
                
                $imagePath = $this->container->getParameter('fa.ad.image.dir').'/'.CommonManager::getGroupDirNameById($adImage->getAd()->getId());
                $adImageManager 		= new AdImageManager($this->container, $adImage->getAd()->getId(), $adImage->getHash(), $imagePath);
                $adImageManager->uploadImagesToS3($adImage, $this->container);
            }
        }
    }

    /**
     * Save ad data.
     *
     * @param object  $ad                 Ad instance.
     * @param array   $data               Ad data.
     * @param integer $previousCategoryId Previous category id.
     * @param boolean $isAdmin            Save from admin or not.
     */
    protected function saveVerticalData($ad, $data = array(), $previousCategoryId = null, $isAdmin = false)
    {
        $categoryId  = $data['category_id'];
        $verticalObj = $this->getVerticalObject($categoryId, $ad, $previousCategoryId, $isAdmin);
        $verticalObj->setAd($ad);

        $metaData    = array();
        $isDimension = false;
        foreach ($this->getVerticalFields($categoryId) as $field) {
            if (isset($data[$field])) {
                if ($data[$field] && !is_array($data[$field])) {
                    $data[$field] = trim($data[$field]);
                }
                // First: Check autosuggest value with entity name with category dimension if, found then store entity id.
                // Seocnd:  Check autosuggest value entity id, if found then store entity id.
                // Third: if entity id not found then store value as free text in meta data with remvoe "_id" from field name.
                if ($this->getFieldType($field) == 'text_autosuggest' && $this->getFieldCategoryDimenionId($field)) {
                    $entityId = $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($this->getFieldCategoryDimenionId($field), $data[$field]);
                    if ($entityId) {
                        $data[$field] = $entityId;
                    } else {
                        $entity = $this->em->getRepository('FaEntityBundle:Entity')->findOneBy(array('id' => $data[$field], 'category_dimension' => $this->getFieldCategoryDimenionId($field)));
                        if ($entity) {
                            $data[$field] = $entity->getId();
                        } else {
                            $fieldData = $data[$field];
                            if (strstr($field, '_id', true) !== false) {
                                $field = strstr($field, '_id', true);
                            }
                            $data[$field] = $fieldData;
                        }
                    }
                }
                
                if (in_array($field, $this->getNotIndexedVerticalFields($categoryId))) {
                    if ($data[$field] !== null && $data[$field] !== false && $data[$field] !== '') {
                        if (in_array($field, array('dimensions_length', 'dimensions_width', 'dimensions_height')) && !$isDimension) {
                            $isDimension = true;
                        }
                        
                        if (is_array($data[$field]) && $data[$field] != 'rates_id') {
                            $metaData[$field] = implode(',', $data[$field]);
                        } else {
                            if ($this->getFieldType($field) == 'text_float' || $this->getFieldType($field) == 'text_int') {
                                $metaData[$field] = str_replace(',', '', $data[$field]);
                            } else {
                                $metaData[$field] = $data[$field];
                            }
                        }
                    }
                } else {
                    if (is_array($data[$field])) {
                        $this->setField($field, implode(',', $data[$field]), $verticalObj);
                    } else {
                        $this->setField($field, $data[$field], $verticalObj);
                    }
                }
            }
        }

        //check rate field is defined
        if (isset($data['rates_id'])) {
            $getRateDimensionId = $this->getFieldCategoryDimenionId('rates_id');
            if ($getRateDimensionId!=null) {
                $ratesData= $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($getRateDimensionId, $this->container, true, 'id', 'textCollection');
                $ratesRecord = [];
                if (!empty($ratesData)) {
                    foreach ($ratesData as $rate=>$val) {
                        $rateType = explode('_', $val);
                        if ($data[str_replace(' ', '', $val)] != '') {
                            $ratesRecord[$rateType[1]][$rate] = $data[str_replace(' ', '', $val)];
                        }
                    }
                }
                
                if (!empty($ratesRecord)) {
                    $metaData['rates_id'] = $ratesRecord;
                } else {
                    unset($metaData['rates_id']);
                }
            }
        }
        
        if ($isDimension) {
            $metaData['dimensions_unit'] = $data['dimensions_unit'];
        }
        $this->setField('meta_data', serialize($metaData), $verticalObj);

        $this->em->persist($verticalObj);
        $this->em->flush();
    }

    /**
     * Set the step wise data to s.
     *
     * @param array  $data Step data array.
     * @param string $step Ad post step.
     */
    protected function setStepSessionData($data, $step)
    {
        $this->container->get('session')->set('paa_'.$step.'_step_data', serialize($data));
    }

    /**
     * Get the step wise data from session.
     *
     * @param string $step Ad post step.
     */
    protected function getStepSessionData($step)
    {
        $data = array();
        if ($this->container->get('session')->has('paa_'.$step.'_step_data')) {
            $data = unserialize($this->container->get('session')->get('paa_'.$step.'_step_data'));
        }

        return $data;
    }

    /**
     * Set field data.
     *
     * @param string $field    Field name.
     * @param string $fieldVal Field value.
     * @param object $object   Instance.
     */
    protected function setField($field, $fieldVal, $object)
    {
        $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        if (method_exists($object, $methodName) === true) {
            if ($field == 'price') {
                $fieldVal = str_replace('£', '', $fieldVal);
            }

            if ($fieldVal === '') {
                $fieldVal = null;
            }

            call_user_func(array($object, $methodName), $fieldVal);
        }
    }

    /**
     * Set field data.
     *
     * @param string $field  Field name.
     * @param object $object Instance.
     */
    protected function getField($field, $object)
    {
        $fieldVal   = null;
        $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        if (method_exists($object, $methodName) === true) {
            $fieldVal = call_user_func(array($object, $methodName));
        }

        return $fieldVal;
    }

    /**
     * Get vertical object.
     *
     * @param integer $categoryId         Category Id.
     * @param object  $ad                 Ad instance.
     * @param integer $previousCategoryId Previous category id.
     * @param boolean $isAdmin            Save from admin or not.
     *
     * @return object
     */
    public function getVerticalObject($categoryId, $ad = null, $previousCategoryId = null, $isAdmin = false)
    {
        // Remove old vertical entry.
        if ($ad && $ad->getId()) {
            $verticalObj = $this->getVerticalRepository($categoryId)->findOneBy(array('ad' => $ad->getId()));
            if ($verticalObj && !$isAdmin) {
                $this->em->remove($verticalObj);
                $this->em->flush();
            }

            // Remove old vertical entry if category changed in edit case.
            if ($previousCategoryId && $previousCategoryId != $categoryId) {
                $verticalObj = $this->getVerticalRepository($previousCategoryId)->findOneBy(array('ad' => $ad->getId()));
                if ($verticalObj) {
                    $this->em->remove($verticalObj);
                    $this->em->flush();
                    $verticalObj = null;
                }
            }
        }

        // Create new vertical entry.
        if (!$verticalObj) {
            $categoryPath  = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container);
            $categoryNames = array_values($categoryPath);
            $object        = '\Fa\Bundle\AdBundle\Entity\Ad'.str_replace(' ', '', ucwords($categoryNames[0]));

            $verticalObj = new $object();
        }


        return $verticalObj;
    }

    /**
     * Get ad vertical fields.
     *
     * @param integer $categoryId Category Id.
     *
     * @return array
     */
    protected function getVerticalFields($categoryId)
    {
        return $this->getVerticalRepository($categoryId)->getAllFields();
    }

    /**
     * Get ad not-inexed vertical fields.
     *
     * @param integer $categoryId Category Id.
     *
     * @return array
     */
    protected function getNotIndexedVerticalFields($categoryId)
    {
        return $this->getVerticalRepository($categoryId)->getNotIndexedFields();
    }

    /**
     * Get ad vertical repository.
     *
     * @param integer $categoryId Category Id.
     *
     * @return object
     */
    protected function getVerticalRepository($categoryId)
    {
        $categoryPath  = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container);
        $categoryNames = array_values($categoryPath);
        $repoName      = 'FaAdBundle:Ad'.str_replace(' ', '', ucwords($categoryNames[0]));

        return $this->em->getRepository($repoName);
    }

    /**
     *  Set paa lite field rules array.
     *
     * @param integer $campaignId Campaign id.
     */
    private function setPaaLiteFieldRules($campaignId)
    {
        $fieldRules    = array();
        $paaFieldRules = array();
        $paaFieldRules = $this->em->getRepository('FaAdBundle:PaaLiteFieldRule')->getAllPaaLiteFields($campaignId);

        if (!empty($paaFieldRules)) {
            foreach ($paaFieldRules[0] as $paaFieldRule) {
                $paaField = $paaFieldRule['paa_lite_field'];
                unset($paaFieldRule['paa_lite_field']);
                $fieldRules[$paaField['field']] = $paaFieldRule;
                $fieldRules[$paaField['field']]['category_dimension_id'] = $paaField['category_dimension_id'];
                $fieldRules[$paaField['field']]['category_id'] = $categoryId;
            }
        }

        $this->paaFieldRules = $fieldRules;
    }

    /**
     *  Set paa field rules array.
     *
     * @param integer $categoryId Category id.
     */
    private function setPaaFieldRules($categoryId)
    {
        $fieldRules    = array();
        $paaFieldRules = $this->em->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryAncestor($categoryId, $this->container);

        if (count($paaFieldRules)) {
            foreach ($paaFieldRules as $paaFieldRule) {
                $paaField = $paaFieldRule['paa_field'];
                unset($paaFieldRule['paa_field']);

                $fieldRules[$paaField['field']] = $paaFieldRule;
                $fieldRules[$paaField['field']]['category_dimension_id'] = $paaField['category_dimension_id'];
                $fieldRules[$paaField['field']]['category_id'] = $categoryId;
            }
        }

        $this->paaFieldRules = $fieldRules;
    }

    /**
     *  Get field type.
     *
     * @param string $field Field.
     *
     * @return string
     */
    private function getFieldType($field)
    {
        if (isset($this->paaFieldRules[$field]['field_type'])) {
            return $this->paaFieldRules[$field]['field_type'];
        }

        return null;
    }

    /**
     *  Get field category dimension id.
     *
     * @param string $field Field.
     *
     * @return string
     */
    private function getFieldCategoryDimenionId($field)
    {
        if (isset($this->paaFieldRules[$field]['category_dimension_id'])) {
            return $this->paaFieldRules[$field]['category_dimension_id'];
        }

        return null;
    }

    /**
     * Change ad moderate status by ad staus if ad edited from admin.
     *
     * @param string $ad Ad instance.
     */
    protected function replaceAdModerateStatusWithAdStatus($ad)
    {
        $adModerate = $this->em->getRepository('FaAdBundle:AdModerate')->findOneBy(array('ad' => $ad->getId()));
        if ($adModerate) {
            $adModerate->setStatus($ad->getStatus());
            $this->em->persist($adModerate);
            $this->em->flush($adModerate);
        }
    }

    /**
     * Change all ad images active while edited and saved from admin side.
     *
     * @param string $ad Ad instance.
     */
    protected function makeAdImagesActive($ad)
    {
        $this->em->getRepository('FaAdBundle:AdImage')->changeStatusByAdId($ad->getId(), 1);
    }

    /**
     * Handle ad moderate.
     *
     * @param object $adId Ad object.
     * @param array  $data Form data.
     */
    public function sendAdForModeration($ad, $data)
    {
        $this->setPaaFieldRules($ad->getCategory()->getId());
        $this->handleAdModerate($ad, $data);

        // Do not send request for moderation for sold and expired ads.
        if (!in_array($ad->getStatus()->getId(), $this->em->getRepository('FaAdBundle:Ad')->getRepostButtonInEditAdStatus())) {
            $this->em->getRepository('FaAdBundle:AdModerate')->sendAdForModeration($ad, $this->container, true);
        }
    }

    /**
     * Handle ad moderate.
     *
     * @param object $adId Ad object.
     * @param array  $data Form data.
     */
    public function sendAdForModerationPaaLite($ad, $data)
    {
        $this->setPaaLiteFieldRules($ad->getCategory()->getId());
        $this->handleAdModerate($ad, $data);

        // Do not send request for moderation for sold and expired ads.
        if (!in_array($ad->getStatus()->getId(), $this->em->getRepository('FaAdBundle:Ad')->getRepostButtonInEditAdStatus())) {
            $this->em->getRepository('FaAdBundle:AdModerate')->sendAdForModeration($ad, $this->container, true);
        }
    }

    /**
     * Handle ad moderate.
     *
     * @param object $adId Ad object.
     * @param array  $data Form data.
     */
    protected function handleAdModerate($ad, $data)
    {
        $adModerateArray = array();
        $adId            = $ad->getId();

        $adModerateArray['ad']         = $this->prepareAdModerateData($ad, $data);
        $adModerateArray['images']     = $this->em->getRepository('FaAdBundle:AdImage')->findByAdId($adId);
        $adModerateArray['locations']  = $this->prepareAdLocationModerateData($ad, $data);
        $adModerateArray['dimensions'] = $this->prepareAdVerticalModerateData($ad, $data);

        $this->em->getRepository('FaAdBundle:AdModerate')->addAdToModerate($ad, $adModerateArray);
    }

    /**
     * Prepare ad moderate data.
     *
     * @param object $adId Ad object.
     * @param array  $data Form data.
     */
    protected function prepareAdModerateData($ad, $data)
    {
        $adData                                 = array();
        $adData[0]['id']                        = $ad->getId();
        $adData[0]['is_new']                    = isset($data['is_new']) ? $data['is_new'] : null;
        $adData[0]['title']                     = isset($data['title']) ? $data['title'] : null;
        $adData[0]['description']               = isset($data['description']) ? $data['description'] : null;
        $adData[0]['qty']                       = isset($data['qty']) ? $data['qty'] : null;
        $adData[0]['personalized_title']        = isset($data['personalized_title']) ? $data['personalized_title'] : null;
        $adData[0]['price']                     = isset($data['price']) ? $data['price'] : null;
        $adData[0]['price_text']                = isset($data['price_text']) ? $data['price_text'] : null;
        $adData[0]['type_id']                   = isset($data['ad_type_id']) ? $data['ad_type_id'] : null;
        $adData[0]['delivery_method_option_id'] = isset($data['delivery_method_option_id']) ? $data['delivery_method_option_id'] : null;
        $adData[0]['payment_method_id']         = isset($data['payment_method_id']) ? $data['payment_method_id'] : null;
        $adData[0]['postage_price']             = isset($data['postage_price']) ? $data['postage_price'] : 0;
        $adData[0]['business_phone']            = isset($data['business_phone']) ? $data['business_phone'] : null;
        $adData[0]['youtube_video_url']         = isset($data['youtube_video_url']) ? $data['youtube_video_url'] : null;
        $adData[0]['edited_at']                 = time();
        $adData[0]['modify_ip']                 = $this->request->getClientIp();

        return $adData;
    }

    /**
     * Prepare ad location moderate data.
     *
     * @param object $adId Ad object.
     * @param array  $data Form data.
     */
    protected function prepareAdLocationModerateData($ad, $data)
    {
        $adLocationData = array();
        $location       = isset($data['location']) ? $data['location'] : null;


        if ($location) {
            $postcodeVal = null;
            $latitude    = null;
            $longitude   = null;
            $locality    = null;
            $town        = null;
            $locationArea = isset($data['area']) ? $data['area'] : null;
            $postalcodeVal = null;

            $postCode = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($location);
            if ($postCode && $postCode->getTownId()) {
                $town      = $this->em->getRepository('FaEntityBundle:Location')->find($postCode->getTownId());
                $postcodeVal  = $postCode->getPostCode();
                $latitude  = $postCode->getLatitude();
                $longitude = $postCode->getLongitude();
                if ($postCode->getLocalityId()) {
                    $locality  = $this->em->getRepository('FaEntityBundle:Locality')->find($postCode->getLocalityId());
                }
            } else {
                if (preg_match('/^\d+$/', $location)) {
                    $town = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $location, 'lvl' => '3'));
                } elseif (preg_match('/^([\d]+,[\d]+)$/', $location)) {
                    $localityTown = explode(',', $location);
                    $localityId = $localityTown[0];
                    $townId     = $localityTown[1];
                    if ($localityId && $townId) {
                        $postCode  = $this->em->getRepository('FaEntityBundle:Postcode')->findOneBy(array('locality_id' => $localityId, 'town_id' => $townId));
                        $locality  = $this->em->getRepository('FaEntityBundle:Locality')->findOneBy(array('id' => $localityId));
                        $town      = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $townId, 'lvl' => '3'));
                    }
                } else {
                    $town = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $location, 'lvl' => '3'));
                    if (!$town) {
                        $locality = $this->em->getRepository('FaEntityBundle:Locality')->findOneBy(array('name' => $location));
                        if ($locality) {
                            $postCode = $this->em->getRepository('FaEntityBundle:Postcode')->findOneBy(array('locality_id' => $locality->getId()));
                            $town     = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $postCode->getTownId(), 'lvl' => '3'));
                        }
                    }
                }

                if ($postCode) {
                    $latitude  = $postCode->getLatitude();
                    $longitude = $postCode->getLongitude();
                } elseif ($town) {
                    $latitude  = $town->getLatitude();
                    $longitude = $town->getLongitude();
                }
            }
            if ($latitude!='' && $latitude!=0.00000000 && $longitude!='' && $longitude!=0.00000000) {
                $postalcodeVal = $this->em->getRepository('FaEntityBundle:Postcode')->getPostCodTextByLatLong($latitude, $longitude);
            }
            
            //get Location Area record
            if ($town) {
                //check area is based on London Location
                if ($town && ($town->getId() == LocationRepository::LONDON_TOWN_ID || $town->getLvl() == 4)) {
                    if (preg_match('/^\d+$/', $locationArea)) {
                        $area = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('id'=>$locationArea, 'lvl'=>'4'));
                    } else {
                        $area = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array(
                                'name' 	=> $locationArea,
                                'lvl'	=>'4'
                        ));
                    }
                    
                    if (!empty($area)) {
                        $latitude  = $area->getLatitude();
                        $longitude = $area->getLongitude();
                    }
                }
            }
            
            if ($town) {
                $adLocationData[0]['ad_id']       = $ad->getId();
                $adLocationData[0]['postcode']    = $postcodeVal;
                $adLocationData[0]['latitude']    = $latitude;
                $adLocationData[0]['longitude']   = $longitude;
                
                if (isset($area) && $area->getLvl() == '4') {
                    $adLocationData[0]['town_id']     	= $area->getParent()->getId();
                    $adLocationData[0]['domicile_id'] 	= $area->getParent()->getParent()->getId();
                    $adLocationData[0]['area_id']		= $area->getId();
                } else {
                    $adLocationData[0]['town_id']     	= $town->getId();
                    $adLocationData[0]['domicile_id'] 	= $town->getParent()->getId();
                    $adLocationData[0]['area_id']		= null;
                }
                
                if ($locality) {
                    $adLocationData[0]['locality_id'] = $locality->getId();
                } else {
                    $adLocationData[0]['locality_id'] = null;
                }
            }
            $adLocationData[0]['postal_code'] = $postalcodeVal;
        }
        return $adLocationData;
    }

    /**
     * Prepare ad vertical specific moderate data (dimensions data).
     *
     * @param object $adId Ad object.
     * @param array  $data Form data.
     */
    protected function prepareAdVerticalModerateData($ad, $data)
    {
        $adVerticalData             = array();
        $categoryId                 = $ad->getCategory()->getId();
        $adVerticalData[0]['ad_id'] = $ad->getId();

        $metaData    = array();
        $isDimension = false;
        foreach ($this->getVerticalFields($categoryId) as $field) {
            if (isset($data[$field])) {
                if ($data[$field] && !is_array($data[$field])) {
                    $data[$field] = trim($data[$field]);
                }
                // First: Check autosuggest value with entity name with category dimension if, found then store entity id.
                // Seocnd:  Check autosuggest value entity id, if found then store entity id.
                // Third: if entity id not found then store value as free text in meta data with remvoe "_id" from field name.
                if ($this->getFieldType($field) == 'text_autosuggest' && $this->getFieldCategoryDimenionId($field)) {
                    $entityId = $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($this->getFieldCategoryDimenionId($field), $data[$field]);
                    if ($entityId) {
                        $data[$field] = $entityId;
                    } else {
                        $entity = $this->em->getRepository('FaEntityBundle:Entity')->findOneBy(array('id' => $data[$field], 'category_dimension' => $this->getFieldCategoryDimenionId($field)));
                        if ($entity) {
                            $data[$field] = $entity->getId();
                        } else {
                            $fieldData = $data[$field];
                            if (strstr($field, '_id', true) !== false) {
                                $field = strstr($field, '_id', true);
                            }
                            $data[$field] = $fieldData;
                        }
                    }
                }

                if (in_array($field, $this->getNotIndexedVerticalFields($categoryId))) {
                    if ($data[$field] !== null && $data[$field] !== false && $data[$field] !== '') {
                        if (in_array($field, array('dimensions_length', 'dimensions_width', 'dimensions_height')) && !$isDimension) {
                            $isDimension = true;
                        }
                        if (is_array($data[$field]) && $data[$field] != 'rates_id') {
                            $metaData[$field] = implode(',', $data[$field]);
                        } else {
                            if ($this->getFieldType($field) == 'text_float' || $this->getFieldType($field) == 'text_int') {
                                $metaData[$field] = str_replace(',', '', $data[$field]);
                            } else {
                                $metaData[$field] = $data[$field];
                            }
                        }
                    }
                } else {
                    if (is_array($data[$field])) {
                        $adVerticalData[0][$field] = implode(',', $data[$field]);
                    } else {
                        $adVerticalData[0][$field] = $data[$field];
                    }
                }
            }
        }
        
        //check rate field is defined
        if (isset($data['rates_id'])) {
            $ratesData= $this->em->getRepository('FaEntityBundle:Entity')->getEntityArrayByType($this->getFieldCategoryDimenionId('rates_id'), $this->container, true, 'id', 'textCollection');
            $ratesRecord = [];
            if (!empty($ratesData)) {
                foreach ($ratesData as $rate=>$val) {
                    $rateType = explode('_', $val);
                    if ($data[str_replace(' ', '', $val)] != '') {
                        $ratesRecord[$rateType[1]][$rate] = $data[str_replace(' ', '', $val)];
                    }
                }
            }

            if (!empty($ratesRecord)) {
                $metaData['rates_id'] = $ratesRecord;
            } else {
                unset($metaData['rates_id']);
            }
        }

        if ($isDimension) {
            $metaData['dimensions_unit'] = $data['dimensions_unit'];
        }

        if (count($metaData)) {
            $adVerticalData[0]['meta_data'] = serialize($metaData);
        }
        return $adVerticalData;
    }
    
    /**
     *  Save ad data.
     *
     * @param array   $data        Ad data.
     * @param integer $adId        Ad id.
     * @param boolean $saveAllData Save all data or not.
     * @param boolean $isAdmin     Save from admin or not.
     *
     * @return boolean
     */
    public function updateAdMissingLocation($ad, $data = [])
    {
        if (!empty($data)) {
            $this->saveAdLocation($ad, $data);
            return true;
        }
    }
}
