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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Moderation\ReviewModerationRequestBuild;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\UserReviewRepository;

/**
 * This controller is used for review moderation.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ReviewModerationController extends CoreController
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
            $userReview = $this->getRepository('FaUserBundle:UserReview')->findOneBy(array('id' => $request->get('id')));

            if ($userReview) {
                $buildRequest      = $this->get('fa_user.review_moderation.request_build');
                $moderationRequest = $buildRequest->init($userReview);
                $moderationRequest = json_encode($moderationRequest);
                if ($buildRequest->sendRequest($moderationRequest)) {
                    $userReview->setStatus(UserReviewRepository::MODERATION_QUEUE_STATUS_SENT);
                    $this->getEntityManager()->persist($userReview);
                    $this->getEntityManager()->flush($userReview);
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
            //$response = '{"ModerationResultId": 1,"ModerationResult": "Scam","ModerationMesage": "User has included an email address in \'description\' field","adRef": "73"}';

            $this->getEntityManager()->beginTransaction();

            $response = json_decode($response, true);

            $returnValueArray = $this->getRepository('FaUserBundle:UserReview')->handleModerationResult($response, $this->container);
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->getConnection()->rollback();
            CommonManager::sendErrorMail($this->container, 'Error: Problem in Review Moderation', $e->getMessage(), $e->getTraceAsString());
        }

        return new Response();
    }
}
