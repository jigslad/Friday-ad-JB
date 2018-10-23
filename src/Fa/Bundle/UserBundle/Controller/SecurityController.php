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
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Facebook\FacebookRequest;
use Doctrine\ORM\EntityRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\User;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\UserBundle\Form\ForgotPasswordType;

/**
 * This controller is used for security management.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class SecurityController extends ThirdPartyLoginController
{
    /**
     * This action is used for login.
     *
     * @param Request $request A Request object.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function loginAction(Request $request)
    {
        $refererUrl = $request->server->get('HTTP_REFERER');
        if ($refererUrl) {
            $urlParams = parse_url($refererUrl);

            if (isset($urlParams['scheme']) && isset($urlParams['host']) && isset($urlParams['path'])) {
                $routeRefererUrl    = str_replace(array($urlParams['scheme'].'://'.$urlParams['host'], $request->getBaseURL()), '', $urlParams['path']);
                try {
                	if(strpos($routeRefererUrl, "zendeskhelp.php") !== FALSE) {
                		$routeRefererUrl = "/";
                	}
                    $prevRouteName = $this->get('router')->match($routeRefererUrl)['_route'];
                } catch (\Exception $e) {
                    $prevRouteName = null;
                }
                if ($prevRouteName && !in_array($prevRouteName, array('login', 'fa_user_register'))) {
                    //set new cookies for redirect after login.
                    $response = new Response();
                    $response->headers->setCookie(new Cookie('frontend_redirect_after_login_path_info', $refererUrl, time() + 3600 * 24 * 7));
                    $response->sendHeaders();
                }
            }
        }

        $alreadyLoggedinRedirectRoute = 'fa_frontend_homepage';
        $template = 'login';
        if ($this->container->get('request_stack')->getCurrentRequest()->get('_route') == 'admin_login') {
            //remove user cookies
            $this->getRepository('FaUserBundle:User')->removeUserCookies();
            $template = 'adminLogin';
            $alreadyLoggedinRedirectRoute = 'fa_admin_homepage';
        }

        //for facebook & google login
        $facebookLoginUrl = '';
        $googleLoginUrl = '';
        if ($this->container->get('request_stack')->getCurrentRequest()->get('_route') == 'login') {
            //facebook
            $fbManager = $this->get('fa.facebook.manager');
            $fbManager->init('facebook_login', array('fbSuccess' => 1));

            $facebookPermissions = array('email');
            $facebookLoginUrl = $fbManager->getFacebookHelper()->getLoginUrl($facebookPermissions);

            //google
            $googleManager = $this->get('fa.google.manager');
            $googlePermissions = array('https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/userinfo.email','https://www.googleapis.com/auth/userinfo.profile');
            $googleManager->init($googlePermissions, 'google_login', array('googleSuccess' => 1));
            $googleLoginUrl = $googleManager->getGoogleClient()->createAuthUrl();
        }

        if ($this->isAuth()) {
            return $this->redirect($this->generateUrl($alreadyLoggedinRedirectRoute));
        }

        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(Security::AUTHENTICATION_ERROR)) {
            $error = $session->get(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        return $this->render(
            'FaUserBundle:Security:'.$template.'.html.twig',
            array
            (
                // last username entered by the user
                'last_username' => (null === $session) ? '' : $session->get(Security::LAST_USERNAME),
                'error'         => $error,
                'facebookLoginUrl' => $facebookLoginUrl,
                'googleLoginUrl'   => $googleLoginUrl,
            )
        );
    }

    /**
     * Facebook login action.
     *
     * @param Request $request A Request object.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function facebookLoginAction(Request $request)
    {
        $redirectAfterLoginUrl = null;
        $this->removeSession('register_user_info');
        if ($this->container->get('request_stack')->getCurrentRequest()->cookies->has('frontend_redirect_after_login_path_info') && $this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info') != CommonManager::COOKIE_DELETED) {
            $redirectAfterLoginUrl = htmlspecialchars_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'));
        }

        $response = $this->processFacebook($request, 'facebook_login', 'fa_frontend_homepage', true, null, false, $redirectAfterLoginUrl);

        if (is_array($response)) {
            $this->container->get('session')->set('register_user_info', $response);
            return $this->redirect($this->generateUrl('fa_user_register'));
        } else if ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Facebook (First Name, Last Name, Email).', array(), 'frontend-register'), 'login', array(), 'error');
        } else if ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('login');
        } else {
            return $response;
        }
    }

    /**
     * Google login action.
     *
     * @param Request $request A Request object.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function googleLoginAction(Request $request)
    {
        $redirectAfterLoginUrl = null;
        $this->removeSession('register_user_info');

        if ($this->container->get('request_stack')->getCurrentRequest()->cookies->has('frontend_redirect_after_login_path_info') && $this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info') != CommonManager::COOKIE_DELETED) {
            $redirectAfterLoginUrl = htmlspecialchars_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'));
        }

        $response = $this->processGoogle($request, 'google_login', 'fa_frontend_homepage', false, true, null, $redirectAfterLoginUrl);

        if (is_array($response)) {
            $this->container->get('session')->set('register_user_info', $response);
            return $this->redirect($this->generateUrl('fa_user_register'));
        } else if ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Google (First Name, Last Name, Email).', array(), 'frontend-register'), 'login', array(), 'error');
        } else if ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('login');
        } else {
            return $response;
        }
    }

    /**
     * This action allows admin to login as a user.
     *
     * @param integer $id       Id.
     * @param integer $admin_id Id of admin.
     * @param integer $key      Key.
     *
     * @throws NotFoundHttpException
     * @return Response A Response object.
     */
    public function logInAsUserAction($id, $admin_id, $key)
    {
        $user      = $this->getDoctrine()->getRepository("FaUserBundle:User")->findOneBy(array('id' => $id));
        $adminUser = $this->getDoctrine()->getRepository("FaUserBundle:User")->findOneBy(array('id' => $admin_id));

        try {
            if (!$user) {
                throw $this->createNotFoundException($this->get('translator')->trans('User does not exist.'));
            } elseif ($user && $user->getStatus()->getId() != BaseEntityRepository::USER_STATUS_ACTIVE_ID) {
                throw $this->createNotFoundException($this->get('translator')->trans('User status is not active.'));
            } elseif (!$adminUser) {
                throw $this->createNotFoundException($this->get('translator')->trans('User does not exist.'));
            } elseif ($user && $user->getEncryptedKey() != $key) {
                throw $this->createNotFoundException($this->get('translator')->trans('Invalid request.'));
            } else {
                //check admin user has permission to logged in as user
                $resourceRoles       = $this->getDoctrine()->getRepository("FaUserBundle:Resource")->getRolesArrayByResource('login_as_user');
                $resourceRoles[]     = 'ROLE_SUPER_ADMIN';
                $adminUserRolesArray = array();

                foreach ($adminUser->getRoles() as $adminUserRole) {
                    $adminUserRolesArray[] = $adminUserRole->getName();
                }

                if (!count(array_intersect($resourceRoles, $adminUserRolesArray))) {
                    throw $this->createNotFoundException($this->get('translator')->trans('You do not have permission to access this resource.'));
                }

                $userRolesArray = array();
                foreach ($user->getRoles() as $userRole) {
                    $userRolesArray[] = $userRole->getName();
                }
                $roleToCheck = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:Role')->getRoleArrayByType('C');

                if (!count(array_intersect($roleToCheck, $userRolesArray))) {
                    throw $this->createNotFoundException($this->get('translator')->trans('You do not have enough credential to login.'));
                }

                $userRoles   = $user->getRoles();
                $userRoles[] = 'ROLE_LOGGED_IN_AS_ADMIN';
                $token       = new UsernamePasswordToken($user, null, "main", $userRoles);
                $this->get("security.token_storage")->setToken($token);

                //set admin_id in session
                $this->get('session')->set('logged_in_admin_id', $admin_id);
                //now dispatch the login event
                $request = $this->container->get('request_stack')->getCurrentRequest();// $this->get("request");
                $event = new InteractiveLoginEvent($request, $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                return $this->handleMessage($this->get('translator')->trans('You are now logged in as the user "%username%" and can edit their account information.', array('%username%' => $user->getFullName())), 'fa_frontend_homepage');
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }
    }

    /**
     * Ajax forgot password action.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxForgotPasswordAction(Request $request)
    {
        $htmlContent = '';
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(ForgotPasswordType::class);
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('username' => $data['email']));
                if ($user) {
                    $encryption_key = $this->container->getParameter('reset_password_encryption_key');
                    $resetPasswordLink = $this->generateUrl('reset_password', array('id' => CommonManager::encryptDecrypt($encryption_key, $user->getId()), 'key' => $user->getEncryptedKey(), 'mail_time' => CommonManager::encryptDecrypt($encryption_key, time())), true);
                    $this->get('fa.mail.manager')->send($user->getEmail(), 'reset_password_link', array('user_first_name' => $user->getFirstName(), 'user_last_name' => $user->getLastName(), 'user_email_address' => $user->getEmail(), 'url_password_reset' => $resetPasswordLink), CommonManager::getCurrentCulture($this->container));
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(array('success' => '1', 'htmlContent' => ''));
                    } else {
                        return $this->handleMessage($this->get('translator')->trans('A mail with reset password link has been sent to your email address.', array(), 'frontend-forgot-password'), 'forgot_password');
                    }
                }
            } elseif ($request->isXmlHttpRequest()) {
                    $htmlContent = $this->renderView('FaUserBundle:Security:ajaxForgotPassword.html.twig', array('form'   => $form->createView()));
                    return new JsonResponse(array('success' => '', 'htmlContent' => $htmlContent));
            }
        }
        $parameters  = array(
            'form'   => $form->createView(),
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('FaUserBundle:Security:ajaxForgotPassword.html.twig', $parameters);
        } else {
            return $this->render('FaUserBundle:Security:forgotPassword.html.twig', $parameters);
        }
    }


    /**
     * update user type.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function updateUserTypeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $requestParams = $request->request->all();
                $userId = $this->getLoggedInUser()->getId();

                $em = $this->getEntityManager();
                $user = $this->getRepository('FaUserBundle:User')->findOneById($userId);
                $userRoleId = ($user->getRole() ? $user->getRole()->getId() : null);
                $user->setIsUserTypeChanged(1);
                $user->setUserTypeChangedAtValue();

                $em->persist($user);
                $em->flush();

                if (!empty($requestParams['type']) && $requestParams['type'] == 'Private' && $userRoleId == RoleRepository::ROLE_BUSINESS_SELLER_ID) {
                    $em->getRepository('FaUserBundle:User')->updateBusinessUserToPrivate($userId, $this->container);
                } elseif (!empty($requestParams['type']) && $requestParams['type'] == 'Business' && $userRoleId == RoleRepository::ROLE_SELLER_ID) {
                    $em->getRepository('FaUserBundle:User')->updatePrivateUserToBusiness($userId, null, $this->container);
                }
            }

            return new JsonResponse();
        }
    }

    /**
     * update user type.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function convertUserToBusinessAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $em = $this->getEntityManager();
                $requestParams = $request->request->all();
                $userId = $this->getLoggedInUser()->getId();
                if ($userId == $requestParams['userId']) {
                    $businessCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($requestParams['businessCategoryId'], $this->container);

                    $em->getRepository('FaUserBundle:User')->updatePrivateUserToBusiness($userId, $businessCategoryId, $this->container);
                    $messageManager = $this->get('fa.message.manager');
                    $messageManager->setFlashMessage('You have successfully upgraded to business user, please select business package.', 'success');
                }
            }
            return new JsonResponse();
        }
    }
}
