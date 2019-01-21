<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\AdBundle\Entity\AdModerate;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\PaymentBundle\Repository\TransactionDetailRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;

/**
 * This repository is used for ad moderate.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class AdModerateRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'am';

    const MODERATION_RESULT_OKEY = 'okay';

    const MODERATION_RESULT_REJECTED = 'rejected';

    const MODERATION_RESULT_MANUAL_MODERATION = 'awaiting manual';

    const MODERATION_RESULT_SCAM = 'scam';

    const MODERATION_QUEUE_STATUS_SEND = 0;

    const MODERATION_QUEUE_STATUS_SENT = 1;

    const MODERATION_QUEUE_STATUS_OKAY = 2;

    const MODERATION_QUEUE_STATUS_REJECTED = 3;

    const MODERATION_QUEUE_STATUS_MANUAL_MODERATION = 4;

    /**
     * PrepareQueryBuilder.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Add ad status filter to existing query object.
     *
     * @param mixed $moderationQueueStatus Moderation queue status.
     */
    protected function addModerationQueueFilter($moderationQueueStatus = null)
    {
        if ($moderationQueueStatus !== null) {
            if (!is_array($moderationQueueStatus)) {
                $moderationQueueStatus = array($moderationQueueStatus);
            }

            if (count($moderationQueueStatus)) {
                $this->queryBuilder->andWhere($this->getRepositoryAlias().'.moderation_queue IN (:moderation_queue)');
                $this->queryBuilder->setParameter('moderation_queue', $moderationQueueStatus);
            }
        }
    }

    /**
     * Handle moderation result.
     *
     * @param array  $moderationResult Moderation resutl array.
     * @param object $container        Container identifier.
     */
    public function handleModerationResult($moderationResult, $container = null)
    {
        $returnValueArray = array();
        $adRef            = null;

        if (isset($moderationResult['Adref'])) {
            $adRef = $moderationResult['Adref'];
        } elseif (isset($moderationResult['adRef'])) {
            $adRef = $moderationResult['adRef'];
        }

        if (!empty($moderationResult) && $adRef) {
            $adModerate = $this->findOneBy(array('ad' => $adRef));

            if ($adModerate) {
                $adModerateCurrentStatus = $adModerate->getStatus()->getId();

                $returnValueArray['ad_id'] = $adRef;
                $ad = $adModerate->getAd();
                $oldAdStatusId = ($ad && $ad->getStatus() ? $ad->getStatus()->getId() : null);

                $returnValueArray['user_id'] = (!empty($ad->getUser())) ? $ad->getUser()->getId() : null;

                if (isset($moderationResult['ModerationResultId'])) {
                    $adModerate->setModerationResultId($moderationResult['ModerationResultId']);
                }

                if (isset($moderationResult['ModerationResult'])) {
                    $moderationResult['ModerationResult'] =  strtolower($moderationResult['ModerationResult']);
                    $adModerate->setModerationResult($moderationResult['ModerationResult']);
                }

                $adModerate->setModerationResponse(serialize($moderationResult));

                if (isset($moderationResult['ModerationResult']) && $moderationResult['ModerationResult'] == self::MODERATION_RESULT_OKEY) {
                    $adModerate->setModerationQueue(self::MODERATION_QUEUE_STATUS_OKAY);
                    $adModerate->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_LIVE_ID));
                } elseif (isset($moderationResult['ModerationResult']) && $moderationResult['ModerationResult'] == self::MODERATION_RESULT_REJECTED) {
                    $adModerate->setModerationQueue(self::MODERATION_QUEUE_STATUS_REJECTED);
                    $adModerate->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_REJECTED_ID));
                } elseif (isset($moderationResult['ModerationResult']) && $moderationResult['ModerationResult'] == self::MODERATION_RESULT_MANUAL_MODERATION) {
                    $adModerate->setModerationQueue(self::MODERATION_QUEUE_STATUS_MANUAL_MODERATION);
                //$adModerate->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_REJECTEDWITHREASON_ID));
                } elseif (isset($moderationResult['ModerationResult']) && $moderationResult['ModerationResult'] == self::MODERATION_RESULT_SCAM) {
                    $adModerate->setModerationQueue(self::MODERATION_RESULT_SCAM);
                    $adModerate->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_REJECTED_ID));
                }

                $this->_em->persist($adModerate);
                $this->_em->flush($adModerate);

                if (isset($moderationResult['ModerationResult']) && $moderationResult['ModerationResult'] == self::MODERATION_RESULT_OKEY) {
                    // handle ad package.
                    $returnValueArray['moderation_status'] = BaseEntityRepository::AD_STATUS_LIVE_ID;
                    $adUserPackageId = $this->handlePackage($adRef);
                    $returnValueArray['ad_user_package_id'] = $adUserPackageId;

                    // handle ad.
                    $this->handleAdFromModerationResult($adRef, self::MODERATION_RESULT_OKEY, $container);

                    // handle privacy yac number for ad.
                    $this->handleAdPrivacyNumber($adRef, $container);

                    // handle ad edit live email send for private users only.
                    if ($ad && !$ad->getIsFeedAd() && $ad->getStatus() && $ad->getStatus()->getId() == BaseEntityRepository::AD_STATUS_LIVE_ID && $ad->getUser() && $ad->getUser()->getRole() && $ad->getUser()->getRole()->getId() == RoleRepository::ROLE_SELLER_ID) {
                        $adModerateValues = unserialize($adModerate->getValue());
                        if ($oldAdStatusId == BaseEntityRepository::AD_STATUS_LIVE_ID && isset($adModerateValues['ad']) && isset($adModerateValues['ad'][0]) && !in_array('published_at', array_keys($adModerateValues['ad'][0])) && isset($adModerateValues['ad'][0]['edited_at']) && $adModerateValues['ad'][0]['edited_at']) {
                            $ad->setAdEditModeratedAt(time());
                            $this->_em->persist($ad);
                            $this->_em->flush($ad);
                        }
                    }

                    // Update ad data to solr
                    $container->get('fa_ad.entity_listener.ad')->handleSolr($ad);
                } elseif (isset($moderationResult['ModerationResult']) && $moderationResult['ModerationResult'] == self::MODERATION_RESULT_REJECTED) {
                    if (isset($moderationResult['ModerationMessage'])) {
                        if (($ad->getStatus()->getId() != BaseEntityRepository::AD_STATUS_LIVE_ID) || ($ad->getStatus()->getId() == BaseEntityRepository::AD_STATUS_LIVE_ID && $adModerateCurrentStatus == BaseEntityRepository::AD_STATUS_LIVE_ID)) {
                            $ad->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_REJECTEDWITHREASON_ID));
                            $this->_em->persist($ad);
                            $this->_em->flush($ad);
                        }
                        $returnValueArray['moderation_status'] = BaseEntityRepository::AD_STATUS_REJECTEDWITHREASON_ID;
                        $returnValueArray['moderation_message'] = $moderationResult['ModerationMessage'];
                    } else {
                        if (($ad->getStatus()->getId() != BaseEntityRepository::AD_STATUS_LIVE_ID) || ($ad->getStatus()->getId() == BaseEntityRepository::AD_STATUS_LIVE_ID && $adModerateCurrentStatus == BaseEntityRepository::AD_STATUS_LIVE_ID)) {
                            $ad->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_REJECTED_ID));
                            $this->_em->persist($ad);
                            $this->_em->flush($ad);
                        }
                        $returnValueArray['moderation_status'] = BaseEntityRepository::AD_STATUS_REJECTED_ID;
                    }

                    try {
                        // send ad rejected email
                        $this->sendAdRejectedEmail($ad, $moderationResult, $container);
                    } catch (\Exception $e) {
                    }
                } elseif (isset($moderationResult['ModerationResult']) && strtolower($moderationResult['ModerationResult']) == self::MODERATION_RESULT_SCAM) {
                    $ad->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_REJECTED_ID));
                    $this->_em->persist($ad);
                    $this->_em->flush($ad);

                    $user = $ad->getUser();
                    if ($user) {
                        $userStatus = $this->_em->getRepository('FaEntityBundle:Entity')->find(BaseEntityRepository::USER_STATUS_BLOCKED);
                        $user->setStatus($userStatus);
                        $this->_em->persist($user);
                        $this->_em->flush($user);

                        $this->_em->getRepository('FaAdBundle:Ad')->blockUnblockAdByUserId($user->getId(), 1);
                        $this->_em->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByUserId($user->getId(), $container);
                    }
                }
                /*if($ad->getUser()->getStatus() != BaseEntityRepository::USER_STATUS_ACTIVE_ID) {
                    $this->_em->getRepository('FaAdBundle:Ad')->blockUnblockAdByUserId($ad->getUser()->getId(), 1);
                    $this->_em->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByUserId($ad->getUser()->getId(), $container);
                } */
            } else {
                $archiveAd = $this->_em->getRepository('FaArchiveBundle:ArchiveAd')->findOneBy(array('ad_main' => $adRef));
                if ($archiveAd && isset($moderationResult['ModerationResult']) && strtolower($moderationResult['ModerationResult']) == self::MODERATION_RESULT_SCAM) {
                    $userId = $archiveAd->getUserId();
                    $user = $this->_em->getRepository('FaUserBundle:User')->find($userId);
                    if ($user) {
                        $userStatus = $this->_em->getRepository('FaEntityBundle:Entity')->find(BaseEntityRepository::USER_STATUS_BLOCKED);
                        $user->setStatus($userStatus);
                        $this->_em->persist($user);
                        $this->_em->flush($user);

                        $this->_em->getRepository('FaAdBundle:Ad')->blockUnblockAdByUserId($user->getId(), 1);
                        $this->_em->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByUserId($user->getId(), $container);
                    }
                }
            }
        }

        return $returnValueArray;
    }

    /**
     * Handle ad from moderation result.
     *
     * @param integer $adId             Ad id.
     * @param integer $moderationResult Moderation result.
     * @param object  $container
     *
     * @return boolean
     */
    public function handleAdFromModerationResult($adId, $moderationResult = self::MODERATION_RESULT_OKEY, $container = null)
    {
        $ad = $this->_em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId));
        if ($moderationResult) {
            $adModerate = $this->findOneBy(array('ad' => $adId, 'moderation_result' => $moderationResult));
        } else {
            $adModerate = $this->findOneBy(array('ad' => $adId));
        }

        if ($ad && $adModerate) {
            $values = unserialize($adModerate->getValue());

            // fetch category id
            if (isset($this->values['category_id'])) {
                $categoryId = $this->values['category_id'];
            } else {
                $categoryId = $ad->getCategory()->getId();
            }

            // update ad from moderation result
            if (isset($values['ad'])) {
                $this->_em->getRepository('FaAdBundle:Ad')->updateDataFromModeration($values['ad'], $container);

                // update ad category from moderation result

                // update ad dimension from moderation result
                if (isset($values['dimensions'])) {
                    $root       = $this->_em->getRepository('FaEntityBundle:Category')->getRootNodeByCategory($categoryId);
                    $repository = $this->_em->getRepository('FaAdBundle:'.'Ad'.str_replace(' ', '', $root->getName()));

                    $repository->updateDataFromModeration($values['dimensions']);
                }

                // update images from moderation result
                if (isset($values['images'])) {
                    $this->_em->getRepository('FaAdBundle:AdImage')->updateDataFromModeration($values['images']);
                }

                // update locations from moderation result
                if (isset($values['locations'])) {
                    $this->_em->getRepository('FaAdBundle:AdLocation')->updateDataFromModeration($values['locations']);
                }

                // update print ad
                $this->_em->getRepository('FaAdBundle:AdPrint')->enablePrintAd($ad->getId());

                return true;
            }
        }

        return false;
    }

    /**
     * Handle Package.
     *
     * @param integer $adId Ad id.
     */
    public function handlePackage($adId)
    {
        $adUserPackageId = $this->_em->getRepository('FaAdBundle:AdUserPackage')->enableAdUserPackage($adId);
        if ($adUserPackageId) {
            $this->_em->getRepository('FaAdBundle:AdUserPackageUpsell')->enableAdUserPackageUpsell($adUserPackageId);
        }

        return $adUserPackageId;
    }

    /**
     * Add ad to moderation.
     *
     * @param object $ad              Ad object.
     * @param array  $adModerateArray Moderation parameter array.
     */
    public function addAdToModerate(Ad $ad, $adModerateArray)
    {
        $adModerate = $this->findOneBy(array('ad' => $ad->getId()));
        if (!$adModerate) {
            $adModerate = new AdModerate();
        }

        $adModerate->setStatus($ad->getStatus());
        $adModerate->setAd($ad);
        $adModerate->setValue(serialize($adModerateArray));
        $adModerate->setModerationQueue(self::MODERATION_QUEUE_STATUS_SEND);
        $this->_em->persist($adModerate);
        $this->_em->flush($adModerate);
    }

    /**
     * Send ad for moderation.
     *
     * @param integer $paymentId Payment id.
     * @param object  $container Container object.
     */
    public function sendAdsForModeration($paymentId, $container)
    {
        try {
            $paymentDetails = $this->_em->getRepository('FaPaymentBundle:PaymentTransactionDetail')->getPaymentTransactionDetailByPaymentFor($paymentId, TransactionDetailRepository::PAYMENT_FOR_PACKAGE);

            foreach ($paymentDetails as $paymentDetail) {
                $paymentDetailValue = unserialize($paymentDetail->getValue());
                $adObj = $paymentDetail->getPaymentTransaction()->getAd();
                // send ad for modeation
                if ($adObj && isset($paymentDetailValue['addAdToModeration']) && $paymentDetailValue['addAdToModeration']) {
                    $adModerate = $this->findOneBy(array('ad' => $adObj->getId()));

                    if ($adModerate) {
                        $buildRequest      = $container->get('fa_ad.moderation.request_build');
                        $moderationRequest = $buildRequest->init($adObj, $adModerate->getValue());
                        $moderationRequest = json_encode($moderationRequest);
                        $sentForModeration = $buildRequest->sendRequest($moderationRequest);

                        if ($sentForModeration) {
                            $this->_em->refresh($adObj);
                            $adObj = $this->_em->getRepository('FaAdBundle:Ad')->find($adObj->getId());
                            if ($adObj->getStatus()->getId() != BaseEntityRepository::AD_STATUS_LIVE_ID) {
                                $adObj->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_IN_MODERATION_ID));
                                $this->_em->persist($adObj);
                                $this->_em->flush($adObj);
                            }

                            $adModerate->setModerationQueue(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT);
                            $adModerate->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_IN_MODERATION_ID));
                            $this->_em->persist($adModerate);
                            $this->_em->flush($adModerate);

                            // remove notification for draft ad
                            $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_incomplete', $adObj->getId());
                        }
                    }
                }
                // apply package to ad if ad is promoted or reposted.
                if ($adObj && isset($paymentDetailValue['promoteRepostAdFlag']) && in_array($paymentDetailValue['promoteRepostAdFlag'], array('promote', 'repost', 'renew')) && (!isset($paymentDetailValue['addAdToModeration']) || (isset($paymentDetailValue['addAdToModeration']) && !$paymentDetailValue['addAdToModeration']))) {
                    //delete avtive ad package
                    if (isset($paymentDetailValue['active_ad_user_package_id']) && $paymentDetailValue['active_ad_user_package_id']) {
                        $activePackage = $this->_em->getRepository('FaAdBundle:AdUserPackage')->find($paymentDetailValue['active_ad_user_package_id']);
                        if ($activePackage) {
                            $deleteManager       = $container->get('fa.deletemanager');
                            $activePackgeUpsells = $this->_em->getRepository('FaAdBundle:AdUserPackageUpsell')->findUpsellByPackage($paymentDetailValue['active_ad_user_package_id']);

                            foreach ($activePackgeUpsells as $activePackgeUpsell) {
                                $deleteManager->delete($activePackgeUpsell);
                            }
                            $deleteManager->delete($activePackage);
                        }
                    }

                    $adExpiryDays        = 0;
                    $changeRenewedAtFlag = false;
                    if ($paymentDetailValue['promoteRepostAdFlag'] == 'renew' && isset($paymentDetailValue['ad_expiry_days_renew']) && $paymentDetailValue['ad_expiry_days_renew']) {
                        $adExpiryDays        = $paymentDetailValue['ad_expiry_days_renew'];
                        $changeRenewedAtFlag = true;
                    }

                    // activate the ad
                    $this->_em->getRepository('FaAdBundle:Ad')->activateAd($adObj->getId(), true, true, true, true, $adExpiryDays, $container);
                    // send email for package.
                    if (isset($paymentDetailValue['package'])) {
                        $packages = $paymentDetailValue['package'];
                        foreach ($packages as $package) {
                            if (isset($package['id'])) {
                                $packageObj = $this->_em->getRepository('FaPromotionBundle:Package')->find($package['id']);
                                if ($packageObj && $packageObj->getEmailTemplate()) {
                                    $this->_em->getRepository('FaAdBundle:Ad')->sendLiveAdPackageEmail($packageObj->getEmailTemplate()->getIdentifier(), $adObj->getId(), $packageObj->getId(), $container);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($container, 'Error: Problem in moderation', $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     * Send ad for moderation.
     *
     * @param object  $adObj          Ad object.
     * @param object  $container      Container object.
     * @param boolean $fromAdvertEdit From advert edit.
     */
    public function sendAdForModeration($adObj, $container, $fromAdvertEdit = false)
    {
        try {
            if ($adObj) {
                $adModerate = $this->findOneBy(array('ad' => $adObj->getId()));

                if ($adModerate) {
                    $buildRequest      = $container->get('fa_ad.moderation.request_build');
                    $moderationRequest = $buildRequest->init($adObj, $adModerate->getValue());
                    $moderationRequest = json_encode($moderationRequest);
                    $sentForModeration = $buildRequest->sendRequest($moderationRequest);

                    if ($sentForModeration) {
                        $this->_em->refresh($adObj);
                        $adObj = $this->_em->getRepository('FaAdBundle:Ad')->find($adObj->getId());
                        if ($fromAdvertEdit) {
                            if (in_array($adObj->getStatus()->getId(), array(BaseEntityRepository::AD_STATUS_REJECTED_ID,  BaseEntityRepository::AD_STATUS_REJECTEDWITHREASON_ID))) {
                                $adObj->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_IN_MODERATION_ID));
                                $this->_em->persist($adObj);
                                $this->_em->flush($adObj);
                            }
                        } else {
                            if ($adObj->getStatus()->getId() != BaseEntityRepository::AD_STATUS_LIVE_ID) {
                                $adObj->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_IN_MODERATION_ID));
                                $this->_em->persist($adObj);
                                $this->_em->flush($adObj);
                            }
                        }

                        $adModerate->setModerationQueue(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT);
                        $adModerate->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_IN_MODERATION_ID));
                        $this->_em->persist($adModerate);
                        $this->_em->flush($adModerate);

                        // remove notification for draft ad
                        $this->_em->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_incomplete', $adObj->getId());
                    }
                }
            }
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($container, 'Error: Problem in moderation', $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     * Update ad privacy number.
     *
     * @param integer $adId      Ad id.
     * @param object  $container Container identifier.
     */
    public function handleAdPrivacyNumber($adId, $container)
    {
        try {
            $ad     = $this->_em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId));
            $adUser = $ad->getUser();

            // check user has use privacy phone number feature.
            if ($adUser->getPhone() && $adUser->getIsPrivatePhoneNumber()) {
                $yacManager = $container->get('fa.yac.manager');
                $expiryDate = $ad->getExpiresAt();
                $expiryDate = $this->_em->getRepository('FaAdBundle:Ad')->getYacExpiry($ad->getId(), $expiryDate);
                $yacManager->init();
                // if no privacy number assigned then assigned new one else extend.
                if (!$ad->getPrivacyNumber()) {
                    $categoryNames = array_values($this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($ad->getCategory()->getId(), false, $container));
                    $yacResponse = $yacManager->allocateYacNumber($adId, $adUser->getPhone(), $expiryDate, $categoryNames[0]);
                    if (!$yacResponse['error'] && $yacResponse['YacNumber']) {
                        $ad->setPrivacyNumber($yacResponse['YacNumber']);
                    }
                } elseif ($ad->getPrivacyNumber()) {
                    $yacResponse = $yacManager->extendYacNumber($ad->getPrivacyNumber(), $expiryDate);
                    if ($yacResponse['errorCode'] && ($yacResponse['errorCode'] == '-117' || $yacResponse['errorCode'] == 'XML_ERROR')) {
                        $categoryNames = array_values($this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($ad->getCategory()->getId(), false, $container));
                        $yacResponse = $yacManager->allocateYacNumber($adId, $adUser->getPhone(), $expiryDate, $categoryNames[0]);
                        if (!$yacResponse['error'] && $yacResponse['YacNumber']) {
                            $ad->setPrivacyNumber($yacResponse['YacNumber']);
                        }
                    }
                }

                $this->_em->persist($ad);
                $this->_em->flush($ad);
            } elseif ($adUser && !$adUser->getIsPrivatePhoneNumber() && $ad->getPrivacyNumber()) {
                //remove yac number
                $ad->setPrivacyNumber(null);
                $this->_em->persist($ad);
                $this->_em->flush($ad);
            }
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($container, 'Error: Ad moderation handleAdPrivacyNumber.', $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     * Get ad moderate data array.
     *
     * @param object $adId Ad id.
     *
     * @return array
     */
    public function getAdModerateDataArray($adId)
    {
        $adModerateData = array();
        $adModerate     = $this->findOneBy(array('ad' => $adId));

        if ($adModerate) {
            $adModerateData['status_id']            = $adModerate->getStatus() ? $adModerate->getStatus()->getId() : null;
            $adModerateData['value']                = $adModerate->getValue();
            $adModerateData['moderation_result_id'] = $adModerate->getModerationResultId();
            $adModerateData['moderation_result']    = $adModerate->getModerationResult();
            $adModerateData['moderation_response']  = $adModerate->getModerationResponse();
            $adModerateData['moderation_queue']     = $adModerate->getModerationQueue();
            $adModerateData['created_at']           = $adModerate->getCreatedAt();
        }

        return array_filter($adModerateData, 'strlen');
    }

    /**
     * Remove ad moderate by ad id.
     *
     * @param integer $adId Ad id.
     */
    public function removeByAdId($adId)
    {
        $adModerate = $this->getBaseQueryBuilder()
                            ->andWhere(self::ALIAS.'.ad = :adId')
                            ->setParameter('adId', $adId)
                            ->getQuery()
                            ->getOneOrNullResult();

        if ($adModerate) {
            $this->_em->remove($adModerate);
            $this->_em->flush($adModerate);
        }
    }

    /**
     * Apply moderation on live ad.
     *
     * @param integer $adId      Ad id.
     * @param object  $container Object.
     */
    public function applyModerationOnLiveAd($adId, $container, $handleAd = true)
    {
        $returnValueArray =  array();

        // handle ad package.
        $moderationValue['ad_id'] = $adId;
        $moderationValue['moderation_status'] = BaseEntityRepository::AD_STATUS_LIVE_ID;
        $adUserPackageId = $this->handlePackage($adId);
        $moderationValue['ad_user_package_id'] = $adUserPackageId;

        // update ad moderate status
        $adModerate = $this->findOneBy(array('ad' => $adId));
        if ($adModerate) {
            $adModerate->setStatus($this->_em->getReference('FaEntityBundle:Entity', BaseEntityRepository::AD_STATUS_LIVE_ID));
            $this->_em->persist($adModerate);
            $this->_em->flush($adModerate);
        }

        // handle ad.
        if ($handleAd) {
            $this->handleAdFromModerationResult($adId, null, $container);
        } else {
            // update print ad
            $this->_em->getRepository('FaAdBundle:AdPrint')->enablePrintAd($adId);
        }

        // handle privacy yac number for ad.
        $this->handleAdPrivacyNumber($adId, $container);

        // Update ad data to solr
        $ad = $this->_em->getRepository('FaAdBundle:Ad')->find($adId);
        $container->get('fa_ad.entity_listener.ad')->handleSolr($ad);

        //update ad report status
        $moderationResult['adref']            = $adId;
        $moderationResult['moderationresult'] = self::MODERATION_RESULT_OKEY;
        $this->_em->getRepository('FaAdBundle:AdReport')->updateAdModerationStatus($moderationResult, $container);

        if (isset($moderationValue['ad_id']) && isset($moderationValue['ad_user_package_id']) && isset($moderationValue['moderation_status']) && $moderationValue['moderation_status'] == BaseEntityRepository::AD_STATUS_LIVE_ID) {
            $emailTemplateName = $this->_em->getRepository('FaAdBundle:AdUserPackage')->getEmailTemplateIdByAdUserPackageId($moderationValue['ad_user_package_id']);
            $packageId = $this->_em->getRepository('FaAdBundle:AdUserPackage')->getPackageIdByAdUserPackageId($moderationValue['ad_user_package_id']);
            $this->_em->getRepository('FaAdBundle:Ad')->sendLiveAdPackageEmail($emailTemplateName, $moderationValue['ad_id'], $packageId, $container);
        }
    }

    /**
     * Find ad by moderation queue filter.
     *
     * @param mixed $moderationQueueStatus Moderation queue status.
     *
     * @return mixed
     */
    public function findByAdIdAndModerationQueueFilter($adId, $moderationQueueStatus)
    {
        $qb = $this->getBaseQueryBuilder();

        if ($moderationQueueStatus !== null && $adId != null) {
            if (!is_array($moderationQueueStatus)) {
                $moderationQueueStatus = array($moderationQueueStatus);
            }

            if (count($moderationQueueStatus)) {
                $qb->andWhere($this->getRepositoryAlias().'.moderation_queue IN (:moderation_queue)');
                $qb->setParameter('moderation_queue', $moderationQueueStatus);
            }

            $qb->andWhere($this->getRepositoryAlias().'.ad = '.$adId)
            ->addOrderBy(self::ALIAS.'.id', 'DESC')
            ->setMaxResults(1);

            return $qb->getQuery()->getOneOrNullResult();
        }

        return null;
    }

    /**
     * Send ad rejected email.
     *
     * @param object $userReview
     * @param array  $moderationResult Moderation resutl array.
     * @param object $container        Container object.
     */
    public function sendAdRejectedEmail($objAd, $moderationResult, $container)
    {
        $parameters = $this->generateRejectedAdEmailParameters($objAd, $moderationResult, $container);
        $template   = 'ad_is_rejected';
         
        $ads[] = array(
            'text_ad_title' => $objAd->getTitle(),
            'text_ad_description' => $objAd->getDescription(),
            'text_ad_category' => $entityCache->getEntityNameById('FaEntityBundle:Category', $objAd->getCategory()->getId()),
            'url_ad_preview' => $container->get('router')->generate('ad_detail_page_by_id', array('id' => $objAd->getId()), true),
            'url_ad_view' => $container->get('router')->generate('ad_detail_page_by_id', array('id' => $objAd->getId()), true),
            'text_adref' => $objAd->getId(),
            'url_ad_main_photo' => $this->getMainImageThumbUrlFromAd($objAd, $container),
            'url_ad_edit' => $container->get('router')->generate('ad_edit', array('id' => $objAd->getId()), true),
            'text_rejection_message' => $moderationResult['ModerationMessage']
        );

        // receiver email
        $receiverEmail = $objAd->getUser()->getEmail();
        if (count($ads)) {
            $parameters = array(
                'user_first_name' => $user->getFirstName(),
                'user_last_name' => $user->getLastName(),
                'ads' => $ads,
                'total_ads' => (count($ads) - 1),
                'url_account_dashboard' => $container->get('router')->generate('dashboard_home', array(), true),
            );

            $container->get('fa.mail.manager')->send($receiverEmail, $template, $parameters, CommonManager::getCurrentCulture($container), null, array(), array(), array(), null);   
        }
    }

    /**
     * Generate email parameters.
     *
     * @param UserReview $userReview
     * @param array      $moderationResult Moderation resutl array.
     * @param object     $container        Container object.
     *
     * @return array
     */
    public function generateRejectedAdEmailParameters($objAd, $moderationResult, $container)
    {
        $objAdOwner         = $objAd->getUser();
        $adCategoryPath     = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($objAd->getCategory()->getId(), false, $container);
        $categoryBreadcrumb = implode(' >> ', $adCategoryPath);

        $parameters['user_first_name']        = $objAdOwner->getFirstName();
        $parameters['user_last_name']         = $objAdOwner->getLastName();
        $parameters['text_ad_title']          = $objAd->getTitle();
        $parameters['text_ad_category']       = $categoryBreadcrumb;
        $parameters['text_ad_description']    = $objAd->getDescription();
        $parameters['text_rejection_message'] = '';

        if (array_key_exists('ModerationMessage', $moderationResult)) {
            $parameters['text_rejection_message'] = $moderationResult['ModerationMessage'];
        }

        return $parameters;
    }

    /**
     * Find results by ad ids and moderation result.
     *
     * @param array  $adIds ad ids array.
     * @param string $moderationResult text.
     *
     * @return mixed
     */
    public function findResultsByAdIdsAndModerationResult($adIds, $moderationResult)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select(self::ALIAS.'.id', 'IDENTITY('.self::ALIAS.'.ad) as ad_id', self::ALIAS.'.moderation_response');

        if ($adIds !== null) {
            if (!is_array($adIds)) {
                $adIds = array($adIds);
            }

            if (count($adIds)) {
                $qb->andWhere($this->getRepositoryAlias().'.ad IN (:adIds)');
                $qb->setParameter('adIds', $adIds);
            }

            $qb->andWhere($this->getRepositoryAlias().".moderation_result = '".$moderationResult."'");
            $adModerations = $qb->getQuery()->getArrayResult();

            $adModerationArray = array();
            if (count($adModerations)) {
                foreach ($adModerations as $adModeration) {
                    $adModerationArray[$adModeration['ad_id']]['moderation_response'] = unserialize($adModeration['moderation_response']);
                }
            }

            return $adModerationArray;
        }

        return null;
    }

    /**
     * Get in moderation ad ids for live ads.
     *
     * @param array $adIds Ad ids.
     *
     * @return array
     */
    public function getInModerationStatusForLiveAdIds(array $adIds)
    {
        $inModerationLiveAdIds = array();
        $moderationQueueStatus = array(
            self::MODERATION_QUEUE_STATUS_SEND,
            self::MODERATION_QUEUE_STATUS_SENT,
            self::MODERATION_QUEUE_STATUS_MANUAL_MODERATION,
        );
        $qb = $this->getBaseQueryBuilder()
            ->select(self::ALIAS.'.id', 'IDENTITY('.self::ALIAS.'.ad) as ad_id')
            ->andWhere($this->getRepositoryAlias().'.moderation_queue IN (:moderation_queue)')
            ->setParameter('moderation_queue', $moderationQueueStatus)
            ->andWhere($this->getRepositoryAlias().'.ad IN  (:adIds)')
            ->setParameter('adIds', $adIds);

        $adModerationResults = $qb->getQuery()->getArrayResult();

        if ($adModerationResults) {
            foreach ($adModerationResults as $adModerationResult) {
                $inModerationLiveAdIds[] = $adModerationResult['ad_id'];
            }
        }

        return $inModerationLiveAdIds;
    }

    /**
     * Get latest location based on id.
     *
     * @param integer $adId Ad id.
     *
     * @return Doctrine_Object
     */
    public function getLatestLocation($adId)
    {
        $qb = $this->getBaseQueryBuilder()->andWhere(self::ALIAS.'.ad = :adId')->andWhere(self::ALIAS.'.moderation_queue =:moderation_queue')->setParameter('adId', $adId)->setParameter('moderation_queue', self::MODERATION_QUEUE_STATUS_SEND);
        return $qb->getQuery()->getOneOrNullResult();
    }
}
