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
use Fa\Bundle\AdBundle\Moderation\AdModerationRequestBuild;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;

/**
 * This controller is used for ad moderation.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdModerationController extends CoreController
{
    /**
     * This action is used to send moderation request.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function requestAction(Request $request)
    {
        try {
            $ad = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $request->get('id')));
            $value = array();

            if ($ad) {
                $adModerate = $this->getRepository('FaAdBundle:AdModerate')->findOneBy(array('ad' => $ad->getId()));
                if ($adModerate) {
                    $buildRequest      = $this->get('fa_ad.moderation.request_build');
                    $moderationRequest = $buildRequest->init($ad, $adModerate->getValue());
                    $moderationRequest = json_encode($moderationRequest);
                    if ($buildRequest->sendRequest($moderationRequest)) {
                        $adModerate->setModerationQueue(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT);
                        $this->getEntityManager()->persist($adModerate);

                        $ad->setType($this->em->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_IN_MODERATION_ID));
                        $this->getEntityManager()->persist($ad);

                        $this->getEntityManager()->flush();
                    }
                }
            }
        } catch (\Exception $e) {
            // LOG or Email
        }

        return new Response();
    }

    /**
     * This action is used to prase moderation response.
     *
     * Example of response includes:
     * $response = '{"ModerationResultId": 1,"ModerationResult": "Rejected","ModerationMesage": "User has included an email address in \'description\' field","adRef": "500197740"}';
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function responseAction(Request $request)
    {
        try {
            $response = file_get_contents("php://input");

            $this->getEntityManager()->beginTransaction();

            $response = json_decode($response, true);

            $returnValueArray = $this->getRepository('FaAdBundle:AdModerate')->handleModerationResult($response, $this->container);
            $this->getEntityManager()->getConnection()->commit();

            try {

                //send ad package email
                $this->sendAdPackageEmail($returnValueArray);

                //update ad report status
                $this->getRepository('FaAdBundle:AdReport')->updateAdModerationStatus($response, $this->container);

            } catch (\Exception $e) {
                CommonManager::sendErrorMail($this->container, 'Error: Problem in nested ad moderation', $e->getMessage(), $e->getTraceAsString());
            }

        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Problem in Ad Moderation', $e->getMessage(), $e->getTraceAsString());
            $this->getEntityManager()->getConnection()->rollback();
        }

        return new Response();
    }

    /**
     * Send ad package email based on moderation value.
     *
     * @param array $moderationValue Moderation value array.
     */
    private function sendAdPackageEmail($moderationValue)
    {
        if (isset($moderationValue['ad_id']) && isset($moderationValue['moderation_status']) && $moderationValue['moderation_status'] == EntityRepository::AD_STATUS_LIVE_ID) {
            if (isset($moderationValue['ad_user_package_id'])) {
                $emailTemplateName = $this->getRepository('FaAdBundle:AdUserPackage')->getEmailTemplateIdByAdUserPackageId($moderationValue['ad_user_package_id']);
                $packageId         = $this->getRepository('FaAdBundle:AdUserPackage')->getPackageIdByAdUserPackageId($moderationValue['ad_user_package_id']);
                $this->getRepository('FaAdBundle:Ad')->sendLiveAdPackageEmail($emailTemplateName, $moderationValue['ad_id'], $packageId, $this->container);

                $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live', $moderationValue['ad_id'], $moderationValue['user_id']);
                $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('share_on_facebook_twitter', $moderationValue['ad_id'], $moderationValue['user_id']);

                $ad = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $moderationValue['ad_id']));

                if (($this->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($ad->getId()) == false) || $ad->getWeeklyRefreshAt() == null) {
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_7_days', $moderationValue['ad_id'], $moderationValue['user_id'], '7d');
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_14_days', $moderationValue['ad_id'], $moderationValue['user_id'], '14d');
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_live_for_21_days', $moderationValue['ad_id'], $moderationValue['user_id'], '21d');
                }
            }

            $adImgCount = $this->getRepository('FaAdBundle:AdImage')->getAdImageCount($moderationValue['ad_id']);

            if (!$adImgCount) {
                $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('no_photos', $moderationValue['ad_id'], $moderationValue['user_id']);
            } else {
                $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('no_photos', $moderationValue['ad_id']);
            }

            $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('advert_rejected', $moderationValue['ad_id']);
        } elseif (isset($moderationValue['ad_id']) && isset($moderationValue['moderation_status']) && in_array($moderationValue['moderation_status'], array(EntityRepository::AD_STATUS_REJECTED_ID, EntityRepository::AD_STATUS_REJECTEDWITHREASON_ID))) {
            //$this->getRepository('FaAdBundle:Ad')->sendRejectAdEmail($moderationValue['ad_id'], $this->container, (isset($moderationValue['moderation_message']) ? $moderationValue['moderation_message'] : null));
            $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_rejected', $moderationValue['ad_id'], $moderationValue['user_id']);
        }
    }

    /**
     * This action is used to apply moderation changes manually by ad id.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function applyChangesManuallyAction(Request $request)
    {
        try {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            if ($user->getEmail() == 'sagar@aspl.in' && $request->get('ad_id')) {
                $ad = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $request->get('ad_id')));
                if ($ad) {
                    $this->getEntityManager()->beginTransaction();
                    $this->getRepository('FaAdBundle:AdModerate')->applyModerationOnLiveAd($request->get('ad_id'), $this->container);
                    $this->getEntityManager()->getConnection()->commit();

                    echo 'Moderation changes applied to ad id : '.$request->get('ad_id');
                } else {
                    echo 'No ad found';
                }
            } else {
                echo 'You can not access this url';
            }
        } catch (\Exception $e) {
            $this->getEntityManager()->getConnection()->rollback();
            $this->handleException($e);
        }

        return new Response();
    }
}
