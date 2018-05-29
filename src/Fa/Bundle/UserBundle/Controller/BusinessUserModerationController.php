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
use Fa\Bundle\AdBundle\Moderation\AdModerationRequestBuild;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;

/**
 * This controller is used for business user moderation.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class BusinessUserModerationController extends CoreController
{
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

            $response = json_decode($response, true);
        } catch (\Exception $e) {
            CommonManager::sendErrorMail($this->container, 'Error: Problem in business user Moderation', $e->getMessage(), $e->getTraceAsString());
            $this->getEntityManager()->getConnection()->rollback();
        }

        return new Response();
    }
}
