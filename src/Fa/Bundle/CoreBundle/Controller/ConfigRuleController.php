<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\CoreBundle\Controller\CoreController;

/**
 * This controller is used for admin side location management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class ConfigRuleController extends CoreController
{
    /**
     * Get ajax payapl commision.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetPaypalCommissionAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $userId  = trim($request->get('user_id'));
            if ($userId) {
                $loggedinUser = $this->getRepository('FaUserBundle:User')->find($userId);
            } else {
                $loggedinUser = $this->getLoggedInUser();
            }

            if ($loggedinUser) {
                $paypalEmail  = trim($request->get('paypal_email'));
                $paypalFirstName = trim($request->get('paypal_first_name'));
                $paypalLastName = trim($request->get('paypal_last_name'));
                $isPaypalVerifiedEmail = false;
                if ($paypalEmail && $paypalFirstName && $paypalLastName) {
                    $isPaypalVerifiedEmail = $this->container->get('fa.paypal.account.verification.manager')->verifyPaypalAccountByEmail($paypalEmail, 'NAME', $paypalFirstName, $paypalLastName);
                }
                // get user paypal commission.
                $paypalCommission = $this->getRepository('FaUserBundle:UserConfigRule')->getActivePaypalCommission($loggedinUser->getId(), $this->container);

                // get global paypal commission.
                if (!$paypalCommission) {
                    $townId             = (int) trim($request->get('town_id'));
                    $adLocationGroupIds = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getLocationGroupIdByTownDomicile(array($townId));
                    $paypalCommission = $this->getRepository('FaCoreBundle:ConfigRule')->getActiveHighestPaypalCommission($adLocationGroupIds, $this->container);
                }
                return new JsonResponse(array('paypalCommission' => $paypalCommission, 'isPaypalVerifiedEmail' => $isPaypalVerifiedEmail));
            } else {
                return new Response();
            }
        } else {
            return new Response();
        }
    }
}
