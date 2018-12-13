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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Manager\ThumbnailManager;
use Fa\Bundle\UserBundle\Manager\UserImageManager;
use Fa\Bundle\UserBundle\Entity\UserSite;

/**
 * This controller is used for registration management.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class ThirdPartyLoginController extends CoreController
{
    /**
     * This method is used to initialize the facebook.
     *
     * @param string $returnUrl Return url.
     *
     * @return string
     */
    protected function initFacebook($returnUrl)
    {
        $fbManager = $this->get('fa.facebook.manager');
        $fbManager->init($returnUrl, array('fbSuccess' => 1));
        $facebookPermissions = array('email');
        return $fbManager->getFacebookHelper()->getLoginUrl($facebookPermissions);
    }

    /**
     * This method is used to initialize the google.
     *
     * @param string $returnUrl Return url.
     *
     * @return string
     */
    protected function initGoogle($returnUrl)
    {
        $googleManager = $this->get('fa.google.manager');
        $googlePermissions = array('https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/userinfo.email','https://www.googleapis.com/auth/userinfo.profile');
        $googleManager->init($googlePermissions, $returnUrl, array('googleSuccess' => 1));
        return $googleManager->getGoogleClient()->createAuthUrl();
    }

    /**
     * This method is used to process the response return
     * by facebook.
     *
     * @param Request  $request
     * @param string   $redirectRoute
     * @param string   $loginRedirectRoute
     * @param boolean  $updateFbId
     * @param string   $customLoginMsg
     * @param boolean  $getFbDetail
     *
     * @return mixed
     */
    protected function processFacebook(Request $request, $redirectRoute, $loginRedirectRoute, $updateFbId = false, $customLoginMsg = null, $getFbDetail = false, $redirectAfterLoginUrl = null)
    {
        if ($request->get('fbSuccess')) {
            $redirectUrl = str_replace('fbSuccess=1', '', $request->getUri());
            $mobileDetectManager = $this->get('fa.mobile.detect.manager');
            if ($mobileDetectManager->is('Chrome') && $mobileDetectManager->is('iOS')) {
                return $this->redirect($redirectUrl);
            }
            return $this->render(
                'FaCoreBundle:Default:closePopupAndRedirect.html.twig',
                array(
                    'redirectUrl' => $redirectUrl,
                    'redirectRoute' => $redirectRoute,
                )
            );
        } elseif ($request->get('code') && $request->get('state')) {
            $em        = $this->getEntityManager();
            $fbManager = $this->get('fa.facebook.manager');
            $fbManager->init($redirectRoute, array('fbSuccess' => 1));
            if ($fbManager->getFacebookSession()) {
                $response = (new FacebookRequest($fbManager->getFacebookSession(), 'GET', '/me?fields=id,first_name, last_name, email, verified, picture.type(large)'))->execute();
                $fbFieldArray = $response->getGraphObject()->asArray();

                if (isset($fbFieldArray['first_name']) && $fbFieldArray['first_name'] && isset($fbFieldArray['last_name']) && $fbFieldArray['last_name'] && isset($fbFieldArray['email']) && $fbFieldArray['email']) {
                    //Fetch user logo from facebook account.
                    if (is_array($fbFieldArray) && isset($fbFieldArray['picture'])) {
                        $fbPictureArray = (array) $fbFieldArray['picture'];
                        if (is_array($fbPictureArray) && isset($fbPictureArray['data'])) {
                            $fbPictureArray = (array) $fbPictureArray['data'];
                            if (is_array($fbPictureArray) && isset($fbPictureArray['url'])) {
                                if ($redirectRoute == 'facebook_paa_login') {
                                    if (!$this->container->get('session')->has('tempUserIdAP')) {
                                        $tempUserId = CommonManager::generateHash();
                                        $this->container->get('session')->set('tempUserIdAP', $tempUserId);
                                    } else {
                                        $tempUserId = $this->container->get('session')->get('tempUserIdAP');
                                    }
                                } elseif ($redirectRoute == 'facebook_paa_lite_login') {
                                    if (!$this->container->get('session')->has('tempUserIdAPL')) {
                                        $tempUserId = CommonManager::generateHash();
                                        $this->container->get('session')->set('tempUserIdAPL', $tempUserId);
                                    } else {
                                        $tempUserId = $this->container->get('session')->get('tempUserIdAPL');
                                    }
                                } elseif ($redirectRoute == 'facebook_paa_lite_register') {
                                    if (!$this->container->get('session')->has('tempUserIdREGPL')) {
                                        $tempUserId = CommonManager::generateHash();
                                        $this->container->get('session')->set('tempUserIdREGPL', $tempUserId);
                                    } else {
                                        $tempUserId = $this->container->get('session')->get('tempUserIdREGPL');
                                    }
                                } else {
                                    if (!$this->container->get('session')->has('tempUserIdREG')) {
                                        $tempUserId = CommonManager::generateHash();
                                        $this->container->get('session')->set('tempUserIdREG', $tempUserId);
                                    } else {
                                        $tempUserId = $this->container->get('session')->get('tempUserIdREG');
                                    }
                                }
                                $imagePath = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp';
                                $fileContent = file_get_contents($fbPictureArray['url']);
                                $fileReturn = file_put_contents($imagePath.DIRECTORY_SEPARATOR.$tempUserId.'.jpg', $fileContent);

                                if ($fileReturn) {
                                    //upload original image.
                                    $isCompany = 0;
                                    $orgImagePath = $imagePath;
                                    $orgImageName = $tempUserId.'.jpg';

                                    $dimension = getimagesize($imagePath.DIRECTORY_SEPARATOR.$orgImageName);
                                    $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, 90, 'ImageMagickManager');
                                    $origImage->loadFile($imagePath.DIRECTORY_SEPARATOR.$orgImageName);
                                    $origImage->save($imagePath.DIRECTORY_SEPARATOR.$tempUserId.'_original.jpg', 'image/jpeg');

                                    $userImageManager = new UserImageManager($this->container, $tempUserId, $orgImagePath, $isCompany);
                                    $userImageManager->saveOriginalJpgImage($tempUserId.'_original.jpg');
                                }
                            }
                        }
                    }

                    $sessionData = array(
                        'user_email' => $fbFieldArray['email'],
                        'user_first_name' => $fbFieldArray['first_name'],
                        'user_last_name' => $fbFieldArray['last_name'],
                        'user_facebook_id' => $fbFieldArray['id'],
                        'user_is_facebook_verified' => isset($fbFieldArray['verified'])?$fbFieldArray['verified']:'',
                    );

                    if ($getFbDetail) {
                        return $sessionData;
                    }

                    if ($updateFbId && $this->isAuth()) {
                        $loggedinUser = $this->getLoggedInUser();
                        if ($sessionData['user_email'] != $loggedinUser->getEmail()) {
                            return $this->handleMessage($this->get('translator')->trans('Facebook email and your account email is different.', array(), 'frontend'), $loginRedirectRoute, array(), 'error');
                        }
                    }
                    //check user is already registered
                    $fbUserObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('facebook_id' => $fbFieldArray['id'], 'is_half_account' => 0));
                    if ($fbUserObj) {
                        //If user does not have logo then upload his logo from facebook.
                        $userRoleName = $this->getRepository('FaUserBundle:User')->getUserRole($fbUserObj->getId());
                        if ($userRoleName == RoleRepository::ROLE_SELLER) {
                            $hasUserLogo  = CommonManager::getUserLogoByUserId($this->container, $fbUserObj->getId(), false, true);
                            if (!$hasUserLogo) {
                                $userLogoTmpPath = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp';
                                if (($fbUserObj->getId()) && (($this->container->get('session')->has('tempUserIdREG') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdREG').'.jpg')) || ($this->container->get('session')->has('tempUserIdREGPL') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdREGPL').'.jpg')) || ($this->container->get('session')->has('tempUserIdAP') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdAP').'.jpg')) || ($this->container->get('session')->has('tempUserIdAPL') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdAPL').'.jpg')))) {
                                    $this->moveUserLogo($fbUserObj);
                                }
                            }
                        }

                        if (!$fbUserObj->getStatus() || $fbUserObj->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                            return $this->handleMessage($this->get('translator')->trans('Your status is not active.', array(), 'frontend'), $loginRedirectRoute, array(), 'error');
                        }
                        $token = new UsernamePasswordToken($fbUserObj, null, 'main', $fbUserObj->getRoles());
                        $this->get('security.token_storage')->setToken($token);

                        //now dispatch the login event
                        $event = new InteractiveLoginEvent($request, $token);
                        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                        if ($redirectRoute == 'facebook_paa_lite_login' || $redirectRoute == 'facebook_paa_lite_register') {
                            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$this->container->get('session')->get('campaign_name'))));
                        } elseif ($customLoginMsg) {
                            return $this->handleMessage($customLoginMsg, ($redirectAfterLoginUrl ? $redirectAfterLoginUrl : $loginRedirectRoute));
                        } else {
                            if ($redirectAfterLoginUrl) {
                                return $this->redirect($redirectAfterLoginUrl);
                            } else {
                                return $this->redirectToRoute($loginRedirectRoute);
                            }
                        }
                    }
                    //check using email address and update fb id
                    if ($updateFbId) {
                        $userObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $fbFieldArray['email'], 'is_half_account' => 0));
                        if ($userObj) {
                            if (!$userObj->getStatus() || $userObj->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                                return $this->handleMessage($this->get('translator')->trans('Your status is not active.', array(), 'frontend'), $loginRedirectRoute, array(), 'error');
                            }
                            $userObj->setFacebookId($fbFieldArray['id']);
                            //set facebook verified field
                            if (isset($fbFieldArray['verified']) && $fbFieldArray['verified']) {
                                $userObj->setIsFacebookVerified(1);
                            }
                            $em->persist($userObj);
                            $em->flush();

                            $token = new UsernamePasswordToken($userObj, null, 'main', $userObj->getRoles());
                            $this->get('security.token_storage')->setToken($token);

                            //now dispatch the login event
                            $event = new InteractiveLoginEvent($request, $token);
                            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                            if ($redirectRoute == 'facebook_paa_lite_login' || $redirectRoute == 'facebook_paa_lite_register') {
                                return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$this->container->get('session')->get('campaign_name'))));
                            } elseif ($customLoginMsg) {
                                return $this->handleMessage($customLoginMsg, ($redirectAfterLoginUrl ? $redirectAfterLoginUrl : $loginRedirectRoute));
                            } else {
                                if ($redirectAfterLoginUrl) {
                                    return $this->redirect($redirectAfterLoginUrl);
                                } else {
                                    return $this->redirectToRoute($loginRedirectRoute);
                                }
                            }
                        }
                    }

                    return $sessionData;
                } else {
                    return 'MISSINGDATA';
                }
            } else {
                if ($redirectRoute == 'home_page_facebook_login_register' && $this->container->get('session')->has('fbHomePageLoginUrl')) {
                    return $this->render(
                        'FaCoreBundle:Default:openPopupAndRedirect.html.twig',
                        array(
                            'redirectUrl' => $this->container->get('session')->get('fbHomePageLoginUrl'),
                        )
                    );
                }
                return 'MISSINGTOKEN';
            }
        } else {
            return 'MISSINGCODE';
        }
    }

    /**
     * This method is used to process the response return
     * by google.
     *
     * @param Request $request
     * @param string  $redirectRoute
     * @param string  $loginRedirectRoute
     * @param boolean $getGoogleDetail
     *
     * @return mixed
     */
    protected function processGoogle(Request $request, $redirectRoute, $loginRedirectRoute, $getGoogleDetail = false, $updateGoogleId = false, $customLoginMsg = null, $redirectAfterLoginUrl = null)
    {
        if ($request->get('googleSuccess')) {
            $redirectUrl = str_replace('googleSuccess=1', '', $request->getUri());

            return $this->render(
                'FaCoreBundle:Default:closePopupAndRedirect.html.twig',
                array(
                    'redirectUrl' => $redirectUrl,
                )
            );
        } elseif ($request->get('code')) {
            $em            = $this->getEntityManager();
            $googleManager = $this->get('fa.google.manager');
            $googlePermissions = array('https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/userinfo.email','https://www.googleapis.com/auth/userinfo.profile');
            $googleManager->init($googlePermissions, $redirectRoute, array('googleSuccess' => 1));
            $googleClient = $googleManager->getGoogleClient();
            $googleOauth = $googleManager->getGoogleOauth();
            $googleClient->authenticate($request->get('code'));
            if ($googleClient->getAccessToken()) {
                $googleFieldArray = $googleOauth->userinfo->get();

                //Fetch user logo from google account.
                if ($googleFieldArray && isset($googleFieldArray['picture'])) {
                    if ($redirectRoute == 'google_paa_lite_login') {
                        if (!$this->container->get('session')->has('tempUserIdAPL')) {
                            $tempUserId = CommonManager::generateHash();
                            $this->container->get('session')->set('tempUserIdAPL', $tempUserId);
                        } else {
                            $tempUserId = $this->container->get('session')->get('tempUserIdAPL');
                        }
                    } elseif ($redirectRoute == 'google_paa_login') {
                        if (!$this->container->get('session')->has('tempUserIdAP')) {
                            $tempUserId = CommonManager::generateHash();
                            $this->container->get('session')->set('tempUserIdAP', $tempUserId);
                        } else {
                            $tempUserId = $this->container->get('session')->get('tempUserIdAP');
                        }
                    } elseif ($redirectRoute == 'google_paa_lite_register') {
                        if (!$this->container->get('session')->has('tempUserIdREGPL')) {
                            $tempUserId = CommonManager::generateHash();
                            $this->container->get('session')->set('tempUserIdREGPL', $tempUserId);
                        } else {
                            $tempUserId = $this->container->get('session')->get('tempUserIdREGPL');
                        }
                    } else {
                        if (!$this->container->get('session')->has('tempUserIdREG')) {
                            $tempUserId = CommonManager::generateHash();
                            $this->container->get('session')->set('tempUserIdREG', $tempUserId);
                        } else {
                            $tempUserId = $this->container->get('session')->get('tempUserIdREG');
                        }
                    }

                    $imagePath = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp';
                    $fileContent = file_get_contents($googleFieldArray['picture']);
                    $fileReturn = file_put_contents($imagePath.DIRECTORY_SEPARATOR.$tempUserId.'.jpg', $fileContent);

                    if ($fileReturn) {
                        //upload original image.
                        $isCompany = 0;
                        $orgImagePath = $imagePath;
                        $orgImageName = $tempUserId.'.jpg';

                        $dimension = getimagesize($imagePath.DIRECTORY_SEPARATOR.$orgImageName);
                        $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, 90, 'ImageMagickManager');
                        $origImage->loadFile($imagePath.DIRECTORY_SEPARATOR.$orgImageName);
                        $origImage->save($imagePath.DIRECTORY_SEPARATOR.$tempUserId.'_original.jpg', 'image/jpeg');

                        $userImageManager = new UserImageManager($this->container, $tempUserId, $orgImagePath, $isCompany);
                        $userImageManager->saveOriginalJpgImage($tempUserId.'_original.jpg');
                    }
                }

                if (isset($googleFieldArray['givenName']) && $googleFieldArray['givenName'] && isset($googleFieldArray['familyName']) && $googleFieldArray['familyName'] && isset($googleFieldArray['email']) && $googleFieldArray['email']) {
                    $sessionData = array(
                        'user_email' => $googleFieldArray['email'],
                        'user_first_name' => $googleFieldArray['givenName'],
                        'user_last_name' => $googleFieldArray['familyName'],
                        'user_google_id' => $googleFieldArray['id'],
                    );

                    if ($getGoogleDetail) {
                        return $sessionData;
                    }

                    if ($updateGoogleId && $this->isAuth()) {
                        $loggedinUser = $this->getLoggedInUser();
                        if ($sessionData['user_email'] != $loggedinUser->getEmail()) {
                            return $this->handleMessage($this->get('translator')->trans('Facebook email and your account email is different.', array(), 'frontend'), $loginRedirectRoute, array(), 'error');
                        }
                    }
                    //check user is already registered
                    $googleUserObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('google_id' => $googleFieldArray['id'], 'is_half_account' => 0));
                    if ($googleUserObj) {
                        //If user does not have logo then upload his logo from google.
                        $hasUserLogo = CommonManager::getUserLogoByUserId($this->container, $googleUserObj->getId(), false, true);
                        if (!$hasUserLogo) {
                            $userLogoTmpPath = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp';
                            if (($this->container->get('session')->has('tempUserIdREG') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdREG').'.jpg')) || ($this->container->get('session')->has('tempUserIdAP') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdAP').'.jpg'))  || ($this->container->get('session')->has('tempUserIdREGPL') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdREGPL').'.jpg')) || ($this->container->get('session')->has('tempUserIdAPL') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdAPL').'.jpg'))) {
                                $this->moveUserLogo($googleUserObj);
                            }
                        }
                        if (!$googleUserObj->getStatus() || $googleUserObj->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                            return $this->handleMessage($this->get('translator')->trans('Your status is not active.', array(), 'frontend'), $loginRedirectRoute, array(), 'error');
                        }
                        $token = new UsernamePasswordToken($googleUserObj, null, 'main', $googleUserObj->getRoles());
                        $this->get('security.token_storage')->setToken($token);

                        //now dispatch the login event
                        $event = new InteractiveLoginEvent($request, $token);
                        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
                        if ($redirectRoute == 'google_paa_lite_login' || $redirectRoute == 'google_paa_lite_register') {
                            return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$this->container->get('session')->get('campaign_name'))));
                        } elseif ($customLoginMsg) {
                            return $this->handleMessage($customLoginMsg, ($redirectAfterLoginUrl ? $redirectAfterLoginUrl : $loginRedirectRoute));
                        } else {
                            if ($redirectAfterLoginUrl) {
                                return $this->redirect($redirectAfterLoginUrl);
                            } else {
                                return $this->redirectToRoute($loginRedirectRoute);
                            }
                        }
                    }

                    //check using email address and update google id
                    if ($updateGoogleId) {
                        $userObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $googleFieldArray['email'], 'is_half_account' => 0));
                        if ($userObj) {
                            if (!$userObj->getStatus() || $userObj->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                                return $this->handleMessage($this->get('translator')->trans('Your status is not active.', array(), 'frontend'), $loginRedirectRoute, array(), 'error');
                            }
                            $userObj->setGoogleId($googleFieldArray['id']);
                            $em->persist($userObj);
                            $em->flush();

                            $token = new UsernamePasswordToken($userObj, null, 'main', $userObj->getRoles());
                            $this->get('security.token_storage')->setToken($token);

                            //now dispatch the login event
                            $event = new InteractiveLoginEvent($request, $token);
                            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                            if ($redirectRoute == 'google_paa_lite_login' || $redirectRoute == 'google_paa_lite_register') {
                                return $this->redirect($this->generateUrl('paa-lite', array('campaign_name'=>$this->container->get('session')->get('campaign_name'))));
                            } elseif ($customLoginMsg) {
                                return $this->handleMessage($customLoginMsg, ($redirectAfterLoginUrl ? $redirectAfterLoginUrl : $loginRedirectRoute));
                            } else {
                                if ($redirectAfterLoginUrl) {
                                    return $this->redirect($redirectAfterLoginUrl);
                                } else {
                                    return $this->redirectToRoute($loginRedirectRoute);
                                }
                            }
                        }
                    }

                    return $sessionData;
                } else {
                    return 'MISSINGDATA';
                }
            } else {
                return 'MISSINGTOKEN';
            }
        } else {
            return 'MISSINGCODE';
        }
    }

    /**
     * Name of session variable.
     *
     * @param string $name
     *
     * @return boolean
     */
    protected function removeSession($name)
    {
        if ($this->container->get('session')->has($name)) {
            $this->container->get('session')->remove($name);
            return true;
        }
        return false;
    }

    /**
     * Set default value from either facebook or google in
     * user object.
     *
     * @param string $sessionName
     *
     * @return object
     */
    protected function setDefaultValueForUser($sessionName)
    {
        $user = new User();
        $sessionData = $this->container->get('session')->get($sessionName, array());
        if (count($sessionData) > 0) {
            if (isset($sessionData['user_email'])) {
                $user->setEmail($sessionData['user_email']);
            }
            if (isset($sessionData['user_first_name'])) {
                $user->setFirstName($sessionData['user_first_name']);
            }
            if (isset($sessionData['user_last_name'])) {
                $user->setLastName($sessionData['user_last_name']);
            }
        }

        return $user;
    }

    /**
     * Update facebook or google id.
     *
     * @param object $user
     * @param string $sessionName
     *
     * @param object
     */
    protected function updateFacebookGoogleId($user, $sessionName)
    {
        $sessionData = $this->container->get('session')->get($sessionName, array());
        if (count($sessionData) > 0) {
            if (isset($sessionData['user_facebook_id']) && $sessionData['user_facebook_id']) {
                $user->setFacebookId($sessionData['user_facebook_id']);
            }
            $userEmail = $user->getEmail();
            if (isset($sessionData['user_is_facebook_verified']) && $sessionData['user_is_facebook_verified'] && $userEmail == $sessionData['user_email']) {
                $user->setIsFacebookVerified(1);
            }

            if (isset($sessionData['user_google_id']) && $sessionData['user_google_id']) {
                $user->setGoogleId($sessionData['user_google_id']);
            }

            if (isset($sessionData['user_facebook_id']) || isset($sessionData['user_is_facebook_verified']) || isset($sessionData['user_google_id'])) {
                $this->getEntityManager()->persist($user);
                $this->getEntityManager()->flush($user);
            }
        }
        $this->removeSession($sessionName);

        return $user;
    }

    public function moveUserLogo($user)
    {
        $webPath      = $this->container->get('kernel')->getRootDir().'/../web';
        if ($this->container->get('session')->has('tempUserIdREG')) {
            $orgImageName = $this->container->get('session')->get('tempUserIdREG');
        } elseif ($this->container->get('session')->has('tempUserIdREGPL')) {
            $orgImageName = $this->container->get('session')->get('tempUserIdREGPL');
        } elseif ($this->container->get('session')->has('tempUserIdAP')) {
            $orgImageName = $this->container->get('session')->get('tempUserIdAP');
        } elseif ($this->container->get('session')->has('tempUserIdAPL')) {
            $orgImageName = $this->container->get('session')->get('tempUserIdAPL');
        }
        $isCompany    = false;
        $imageObj     = null;
        $userId       = $user->getId();

        $userRoleName = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);

     if ($userRoleName == RoleRepository::ROLE_BUSINESS_SELLER || $userRoleName == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
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
