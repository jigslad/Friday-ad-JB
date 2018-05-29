<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\MessageBundle\Moderation\ContactModerationRequestBuild;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for contact moderation.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ContactModerationController extends CoreController
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
            $message = $this->getRepository('FaMessageBundle:Message')->findOneBy(array('id' => $request->get('id')));

            if ($message) {
                $buildRequest      = $this->get('fa_message.contact_moderation.request_build');
                $moderationRequest = $buildRequest->init($message);
                $moderationRequest = json_encode($moderationRequest);
                if ($buildRequest->sendRequest($moderationRequest)) {
                }
            }
        } catch (\Exception $e) {
            // LOG or Email
        }

        return new Response();
    }

    /**
     * This action is used to parse moderation response.
     * $response = '{"Sender": "b6067f323b52@contactmoderator.dummy-site.co.uk","Recipient": "adam.feather@fridaymediagroup.com","Subject": "Is your bicycle still for sale?","Body": "I\'ve seen your bicycle on Frida-Ad. Is it still available?","MostRecentMessage": "I\'ve seen your bicycle on Frida-Ad. Is it still available?","ThreadId": "123","ModerationDecision": "ModeratedOkay"}';
     *
     * Example of response includes:
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

            if (count($response) > 0) {
                $response['fa.static.url'] = $this->container->getParameter('fa.static.url');
            }

            $this->getRepository('FaMessageBundle:Message')->handleModerationResult($response, $this->container);

            $this->getEntityManager()->getConnection()->commit();

        } catch (\Exception $e) {
            $this->getEntityManager()->getConnection()->rollback();
            CommonManager::sendErrorMail($this->container, 'Error: Problem in Contact Moderation', $e->getMessage(), $e->getTraceAsString());
            // LOG or Email
        }

        return new Response();
    }
}
