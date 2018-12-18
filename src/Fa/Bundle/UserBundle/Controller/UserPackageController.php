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

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\UserBundle\Form\ResetPasswordType;
use Fa\Bundle\UserBundle\Event\FormEvent;
use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Form\UserPackageType;
use Fa\Bundle\UserBundle\Repository\RoleRepository;

/**
 * This controller is used for handling user packages.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPackageController extends CoreController
{


    /**
     * Display package
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function displayPackageAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $formManager = $this->get('fa.formmanager');
        $user = $this->getLoggedInUser();

        if (!$this->checkIsBusinessSaller($user)) {
            return $this->handleMessage($this->get('translator')->trans('You do not have permission to access this resource.'), 'fa_frontend_homepage', array(), 'error');
        }

        $options =  array(
                'action' => $this->generateUrl('user_package_choose_profile', array('id' => $user->getId())),
                'method' => 'PUT'
        );

        $form = $formManager->createForm(UserPackageType::class, array('user_id' => $user->getId()), $options);

        $userBusinessCategoryId = $user->getBusinessCategoryId() > 0 ? $user->getBusinessCategoryId() : CategoryRepository::FOR_SALE_ID;
        $userRoleId = $user->getRole()->getId();
        $currentPackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);
        $shopPackages = $this->getRepository('FaPromotionBundle:Package')->getShopPackageByCategory($userBusinessCategoryId, $userRoleId, $currentPackage);

        $parameters = array(
                'user'  => $user,
                'currentPackage'  => $currentPackage,
                'shopPackages'  => $shopPackages,
                'form'    => $form->createView(),
                'heading' => $this->get('translator')->trans('Assign subscription package'),
        );

        if ('PUT' === $request->getMethod()) {
            if ($formManager->isValid($form)) {
                $package_id   = $form->get('package')->getData();
                $user_id      = $form->get('user_id')->getData();
                $trail_enable = $form->get('trail_enable')->getData();
                $user       = $this->getRepository('FaUserBundle:User')->findOneBy(array('id' => $user_id));
                $package    = $this->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $package_id));

                if ($package->getPrice() > 0 && $trail_enable == 1 && $user->getFreeTrialEnable() == 1 && $package->getTrail()) {
                    return $this->redirectToRoute('cybersource_trail_subscription_checkout');
                } elseif ($package->getPrice() > 0) {
                    return $this->redirectToRoute('cybersource_subscription_checkout');
                } else {
                    $userPackage = $this->getRepository('FaUserBundle:UserPackage')->assignPackageToUser($user, $package, 'choose-package-frontend', null, false, $this->container);
                    return $this->handleMessage($this->get('translator')->trans('You have successfully upgraded to %package-name%. Please check and update your profile information now!.', array('%package-name%' => ($userPackage ? $userPackage->getPackage()->getTitle() : '')), 'frontend-cyber-source'), 'my_profile', array(), 'success');
                }
            }
        }

        return $this->render('FaUserBundle:UserPackage:choose.html.twig', $parameters);
    }
}
