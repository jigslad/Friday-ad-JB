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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\UserBundle\Entity\User;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Form\RegistrationType;
use Fa\Bundle\UserBundle\Event\FormEvent;
use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\UserBundle\Event\FilterUserResponseEvent;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Facebook\FacebookRequest;
use Fa\Bundle\UserBundle\Controller\ThirdPartyLoginController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\UserBundle\Manager\UserImageManager;
use Fa\Bundle\UserBundle\Entity\UserSite;

/**
 * This controller is used for registration management.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class RegistrationController extends ThirdPartyLoginController
{
    /**
     * This action is used for registration.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function registerAction(Request $request)
    {
        $response = new Response();
        $response->headers->clearCookie('frontend_redirect_after_login_path_info');
        $response->sendHeaders();
        $isCompany    = 0;
        $logoUploaded = 0;
        $userLogoTmpPath = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp';

        if (!$this->isAuth()) {
            if (!$this->container->get('session')->has('tempUserIdREG')) {
                $tempUserId = CommonManager::generateHash();
                $this->container->get('session')->set('tempUserIdREG', $tempUserId);
            }

            $privateUserLogo  = CommonManager::getUserLogo($this->container, '', null, null, null, true, false, null, null);
            $businessUserLogo = CommonManager::getUserLogo($this->container, '', null, null, null, true, true, null, null);

            if ('POST' === $request->getMethod()) {
                $formData = $request->get('user_registration');
                if ($formData['email']) {
                    // check if email address is of half account then, update that account, no need to do new entry.
                    $halfAccount = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $formData['email'], 'is_half_account' => 1));
                    if ($halfAccount) {
                        $user = $halfAccount;
                        $user->setIsHalfAccount(0);
                    } else {
                        $user = $this->setDefaultValueForUser('register_user_info');
                    }
                } else {
                    $user = $this->setDefaultValueForUser('register_user_info');
                }
            } else {
                $user = $this->setDefaultValueForUser('register_user_info');
            }

            $formManager = $this->get('fa.formmanager');
            $form        = $formManager->createForm(RegistrationType::class, $user);
            $dispatcher  = $this->container->get('event_dispatcher');

            // init facebook
            $facebookLoginUrl = $this->initFacebook('facebook_register');
            // init google
            $googleLoginUrl = $this->initGoogle('google_register');

            if ('POST' === $request->getMethod()) {
                $requestParams = $request->request->all();
                if (isset($requestParams) && isset($requestParams['user_registration']) && $requestParams['user_registration']['user_roles'] && $requestParams['user_registration']['user_roles'] == 'ROLE_BUSINESS_SELLER') {
                    $isCompany = 1;
                }
                $form->handleRequest($request);
                $privateUserLogo  = CommonManager::getUserLogo($this->container, '', $this->container->get('session')->get('tempUserIdREG'), null, null, true, false, null, null);
                $businessUserLogo = CommonManager::getUserLogo($this->container, '', $this->container->get('session')->get('tempUserIdREG'), null, null, true, true, null, null);

                if ($form->isValid()) {
                    $event = new FormEvent($form, $request);
                    $dispatcher->dispatch(UserEvents::REGISTRATION_SUCCESS, $event);
                    $user = $formManager->save($user);

                    if ($this->container->get('session')->has('tempUserIdREG') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdREG').'.jpg')) {
                        $this->moveUserLogo($user);
                    }

                    // update facebook/google id
                    $user = $this->updateFacebookGoogleId($user, 'register_user_info');

                    if ($this->container->get('request_stack')->getCurrentRequest()->cookies->has('frontend_redirect_after_login_path_info') && $this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info') != CommonManager::COOKIE_DELETED) {
                        $response = new RedirectResponse(htmlspecialchars_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info')));
                    } elseif (null === $response = $event->getResponse()) {
                        $response = $this->redirect($this->generateUrl('fa_frontend_homepage'));
                    }

                    $dispatcher->dispatch(UserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('no_facebook_signup', null, $user->getId());
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('no_profile_photo', null, $user->getId());
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('if_profile_incomplete', null, $user->getId());

                    if (($user->getRole() && $user->getRole()->getId() == RoleRepository::ROLE_BUSINESS_SELLER_ID)) {
                        $this->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($user, 'reg_back', $this->container);
                        return $this->handleMessage($this->get('translator')->trans('Hi %first_name%, welcome to Friday-Ad!', array('%first_name%' => $user->getFirstName())), 'user_package_choose_profile');
                    } else {
                        return $this->handleMessage($this->get('translator')->trans('Hi %first_name%, welcome to Friday-Ad!', array('%first_name%' => $user->getFirstName())), 'fa_frontend_homepage');
                    }

                    return $response;
                }
            }

            if ($this->container->get('session')->has('tempUserIdREG') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdREG').'.jpg')) {
                $privateUserLogo  = CommonManager::getUserLogo($this->container, '', $this->container->get('session')->get('tempUserIdREG'), null, null, true, false, null, null);
                $businessUserLogo = CommonManager::getUserLogo($this->container, '', $this->container->get('session')->get('tempUserIdREG'), null, null, true, true, null, null);
                $logoUploaded     = 1;
            }

            return $this->render('FaUserBundle:Registration:register.html.twig', array(
                'entity' => $user,
                'form'   => $form->createView(),
                'facebookLoginUrl' => $facebookLoginUrl,
                'googleLoginUrl'   => $googleLoginUrl,
                'tempUserId'       => $this->container->get('session')->get('tempUserIdREG'),
                'privateUserLogo'  => $privateUserLogo,
                'businessUserLogo' => $businessUserLogo,
                'isCompany'        => $isCompany,
                'logoUploaded'     => $logoUploaded,
            ));
        } else {
            $user = $this->getLoggedInUser();
            return $this->handleMessage($this->get('translator')->trans('You are already logged in.', array()), 'fa_frontend_homepage', array(), 'error');
        }
    }

    /**
     * This action is used for registration through facebook.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function facebookRegisterAction(Request $request)
    {
        $this->removeSession('register_user_info');

        $response = $this->processFacebook($request, 'facebook_register', 'fa_frontend_homepage', true);

        if (is_array($response)) {
            $this->container->get('session')->set('register_user_info', $response);
            return $this->redirect($this->generateUrl('fa_user_register'));
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of the fields required to connect to Facebook is missing.', array(), 'frontend-register'), 'fa_user_register', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('fa_user_register');
        } else {
            return $response;
        }
    }

    /**
     * This action is used for registration through google.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function googleRegisterAction(Request $request)
    {
        $this->removeSession('register_user_info');

        $response = $this->processGoogle($request, 'google_register', 'fa_frontend_homepage', false, true);

        if (is_array($response)) {
            $this->container->get('session')->set('register_user_info', $response);
            return $this->redirect($this->generateUrl('fa_user_register'));
        } elseif ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Google (First Name, Last Name, Email).', array(), 'frontend-register'), 'fa_user_register', array(), 'error');
        } elseif ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('fa_user_register');
        } else {
            return $response;
        }
    }

    public function moveUserLogo($user)
    {
        $webPath      = $this->container->get('kernel')->getRootDir().'/../web';
        $orgImageName = $this->container->get('session')->get('tempUserIdREG');
        $isCompany    = false;
        $imageObj     = null;
        $userId       = $user->getId();

        $userRoleName = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);

        if ($userRoleName == RoleRepository::ROLE_BUSINESS_SELLER) {
            $isCompany = true;
        }

        if ($isCompany) {
            $imagePath = $this->container->getParameter('fa.company.image.dir').'/'.CommonManager::getGroupDirNameById($userId, 5000);
            $imageObj  = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userId));
            CommonManager::createGroupDirectory($webPath.DIRECTORY_SEPARATOR.$this->container->getParameter('fa.company.image.dir'), $userId, 5000);
        } else {
            $imagePath = $this->container->getParameter('fa.user.image.dir').'/'.CommonManager::getGroupDirNameById($userId, 5000);
            $imageObj  = $user;
            CommonManager::createGroupDirectory($webPath.DIRECTORY_SEPARATOR.$this->container->getParameter('fa.user.image.dir'), $userId, 5000);
        }

        // Check if user site entry not found then create first
        if (!$imageObj && $isCompany) {
            $imageObj = new UserSite();
            $imageObj->setUser($user);
        }

        if ($isCompany) {
            $imageObj->setPath($imagePath);
        } else {
            $imageObj->setImage($imagePath);
        }

        if (file_exists($webPath.'/uploads/tmp/'.$orgImageName.'.jpg')) {
            rename($webPath.'/uploads/tmp/'.$orgImageName.'.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'.jpg');
            rename($webPath.'/uploads/tmp/'.$orgImageName.'_org.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'_org.jpg');
            rename($webPath.'/uploads/tmp/'.$orgImageName.'_original.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'_original.jpg');
        }

        $userImageManager = new UserImageManager($this->container, $userId, $imagePath, $isCompany);
        $userImageManager->createThumbnail();
    }
}
