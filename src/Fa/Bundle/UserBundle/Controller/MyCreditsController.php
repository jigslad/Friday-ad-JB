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
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This controller is used to show user's credits
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class MyCreditsController extends CoreController
{
    /**
     * Show user profile.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function myCreditsAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $businessCategoryId = $loggedinUser->getBusinessCategoryId();
        $userCredits = $this->getRepository('FaUserBundle:UserCredit')->getActiveCreditDetailForUser($loggedinUser->getId());

        if ($businessCategoryId && isset($userCredits[$businessCategoryId])) {
            $userCredits = array($businessCategoryId => $userCredits[$businessCategoryId]) + $userCredits;
        }

        $parameters = array(
            'userCredits' => $userCredits,
            'businessCategoryId' => $businessCategoryId,
        );

        return $this->render('FaUserBundle:MyCredits:myCredits.html.twig', $parameters);
    }
}
