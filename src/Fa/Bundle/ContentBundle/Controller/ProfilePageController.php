<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\MessageBundle\Entity\Message;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\ContentBundle\Form\ProfilePageContactUserType;
use Fa\Bundle\UserBundle\Form\UserHalfAccountType;

/**
 * This controller is used for profile page management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ProfilePageController extends CoreController
{
    /**
     * Show private profile action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showPrivateProfilePageAction(Request $request)
    {
        $userId          = CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $request->get('userId'), 'decrypt');
        $userProfileSlug = $this->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);
        $removeSplCharArr[] = "'";
        $removeSplCharArr[] = '"';
        if ($userProfileSlug != $request->get('profileNameSlug')) {
            throw new HttpException(410);
        } else {
            $userDetail = $this->getRepository('FaUserBundle:User')->getPrivateUserProfileDetail($userId, true, $this->container);

            if ($userDetail['user_name']!='') {
                $userName = str_replace($removeSplCharArr, "", $userDetail['user_name']);
            } else {
                $userName = '';
            }

            if (!$userDetail['id']) {
                throw new HttpException(410);
            }

            $parameters = array(
                'userDetail' => $userDetail,
                'userName' => $userName,
            );

            // User view counter
            $this->getRepository('FaUserBundle:UserSiteViewCounter')->updateUserSiteViewCounter($this->container, $userId);

            return $this->render('FaContentBundle:ProfilePage:showPrivateProfilePage.html.twig', $parameters);
        }
    }

    /**
     * Show private user ad action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showPrivateUserAdsAction(Request $request)
    {
        $userId          = CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $request->get('userId'), 'decrypt');
        $userProfileSlug = $this->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);
        if ($userProfileSlug != $request->get('profileNameSlug')) {
            throw new HttpException(410);
        } else {
            $userDetail = $this->getRepository('FaUserBundle:User')->getPrivateUserProfileDetail($userId, false, $this->container);

            if (!$userDetail['id']) {
                throw new HttpException(410);
            }

            $pagination = $this->getUserAds($request, $userId);


            $parameters = array(
                'userDetail' => $userDetail,
                'pagination'      => $pagination,
            );

            return $this->render('FaContentBundle:ProfilePage:showUserAds.html.twig', $parameters);
        }
    }

    /**
     * Show private user ad action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showTiPrivateUserAdsAction(Request $request)
    {
        if ($request->get('userId')) {
            $oldTiUserId = CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $request->get('userId'), 'decrypt');
            $userObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('old_ti_user_id' => $oldTiUserId));
        }

        if ($userObj) {
            $userId = $userObj->getId();
            $userProfileSlug = $this->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);
            $userRole = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);
            if ($userRole == RoleRepository::ROLE_SELLER && $userProfileSlug) {
                $url = $this->get('router')->generate('show_private_user_ads', array('profileNameSlug' => $userProfileSlug, 'pageString' => $request->get('pageString'), 'userId' => CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $userId)), true);
                return $this->redirect(rtrim($url, '/'), 301);
            } elseif (($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) && $userProfileSlug) {
                $url = $this->get('router')->generate('show_business_user_ads', array('profileNameSlug' => $userProfileSlug), true);
                return $this->redirect($url, 301);
            } else {
                $url = $this->get('router')->generate('show_business_user_ads', array('profileNameSlug' => $request->get('profileNameSlug')), true);
                return $this->redirect($url, 301);
            }
        } else {
            $url = $this->get('router')->generate('show_business_user_ads', array('profileNameSlug' => $request->get('profileNameSlug')), true);
            return $this->redirect($url, 301);
        }
    }

    /**
     * Show private user ad action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showShopUserLatestAdsAction($userId, $slider = 0, Request $request)
    {
        $limit = 9;
        $mobileDetectManager = $this->get('fa.mobile.detect.manager');
        if ($mobileDetectManager->isMobile() && !$mobileDetectManager->isTablet()) {
            $limit = 10;
        }
        $pagination = $this->getUserAds($request, $userId, $limit);

        $parameters = array(
            'pagination' => $pagination,
            'userId'     => $userId,
        );

        if ($slider) {
            return $this->render('FaContentBundle:ProfilePage:showShopUserLatestAdsSlider.html.twig', $parameters);
        } else {
            return $this->render('FaContentBundle:ProfilePage:showShopUserLatestAds.html.twig', $parameters);
        }
    }

    /**
     * Get user ads.
     *
     * @param object $request
     * @param integer $userId
     *
     * @return object
     */
    private function getUserAds($request, $userId, $recordsPerPage = 20)
    {
        $data           = array();
        $keywords       = null;
        $page           = 1;

        if (preg_match('/page-\d+\/$/', $request->get('pageString'), $matches)) {
            $page = str_replace(array('page-', '/'), '', $matches[0]);
        }
        //set ad criteria to search
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['user_id']   = $userId;
        $data['query_sorter']                         = array();
        $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
        $solrResponse = $solrSearchManager->getSolrResponse();

        // fetch result set from solr
        $result      = $solrSearchManager->getSolrResponseDocs($solrResponse);
        $resultCount = $solrSearchManager->getSolrResponseDocsCount($solrResponse);

        // initialize pagination manager service and prepare listing with pagination based of solr result
        $this->get('fa.pagination.manager')->init($result, $page, $recordsPerPage, $resultCount);
        $pagination = $this->get('fa.pagination.manager')->getSolrPagination();

        return $pagination;
    }

    /**
     * Show business profile action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectBusinessProfilePageAction(Request $request)
    {
        $params = explode('/', $request->get('page_string'));
        $params = array_filter($params);
        $ad_ref = array_pop($params);

        if (is_numeric($ad_ref)) {
            $ad_ref = array_pop($params);
        }

        $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('ad_ref' => $ad_ref));
        if ($userSite) {
            $url = $this->get('router')->generate('show_business_profile_page', array(
                    'profileNameSlug' => $userSite->getSlug(),
            ), true);
            return $this->redirect($url, 301);
        } else {
            if (preg_match('/[A-Z0-9]{9,10}$/', $ad_ref, $matches)) {
                $url = $this->get('router')->generate('fa_frontend_homepage', array(), true);
                return $this->redirect($url, 301);
            } else {
                throw new NotFoundHttpException('Invalid url.');
            }
        }
    }

    /**
     * Show business profile action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectMotorBusinessProfilePageAction(Request $request)
    {
        $slug = preg_replace('/-N-.+/', '', $request->get('page_string'));
        $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('slug' => $slug));
        if ($userSite) {
            $url = $this->get('router')->generate('show_business_profile_page', array(
                'profileNameSlug' => $userSite->getSlug(),
            ), true);
            return $this->redirect($url, 301);
        } else {
            throw new NotFoundHttpException('Invalid url.');
        }
    }
    /**
     * Show business profile action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showBusinessProfilePageAction(Request $request)
    {
        $removeSplCharArr[] = "'";
        $removeSplCharArr[] = '"';
        $aboutUsWordCount = 0;
        $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('slug' => $request->get('profileNameSlug')));
        if (!$userSite || ($userSite && $userSite->getSlug() != $request->get('profileNameSlug'))) {
            throw new HttpException(410);
        } else {
            $user              = $userSite->getUser();
            $userId            = $user->getId();
            $activeShopPackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);

            $objResponse = null;
            if ($user && $user->getBusinessCategoryId() == CategoryRepository::ADULT_ID) {
                $objResponse = CommonManager::setCacheControlHeaders();
            }

            if ($activeShopPackage && $activeShopPackage->getPackage() && $activeShopPackage->getPackage()->getPackageText() == PackageRepository::SHP_PACKAGE_BASIC_TEXT) {
                $this->getRepository('FaUserBundle:UserSiteViewCounter')->updateUserSiteViewCounter($this->container, $userId);
                $userDetail = $this->getRepository('FaUserBundle:User')->getBusinessUserProfileDetail($userId, true, false, $this->container);

                if ($userDetail['user_name']!='') {
                    $userName = str_replace($removeSplCharArr, "", $userDetail['user_name']);
                } else {
                    $userName = '';
                }
                
                if($userDetail['about_us']!='') {
                    $aboutUsWordCount = str_word_count($userDetail['about_us']);
                }

                if (!$userDetail['id']) {
                    throw new HttpException(410);
                }
                $parameters = array(
                    'userDetail' => $userDetail,
                    'websiteset' => 1,
                    'aboutUsWordCount' => $aboutUsWordCount,
                    'userName' => $userName,
                );
                return $this->render('FaContentBundle:ProfilePage:showBusinessProfilePage.html.twig', $parameters, $objResponse);
            } elseif ($activeShopPackage && $activeShopPackage->getPackage() && $activeShopPackage->getPackage()->getPackageText() != PackageRepository::SHP_PACKAGE_BASIC_TEXT) {
                // assign banner if it not exist.
                if (!$userSite->getBannerPath()) {
                    $this->getRepository('FaUserBundle:UserSiteBanner')->updateUserBanner($user, $this->container);
                }
                $this->getRepository('FaUserBundle:UserSiteViewCounter')->updateUserSiteViewCounter($this->container, $userId);
                $userDetail = $this->getRepository('FaUserBundle:User')->getBusinessUserProfileDetail($userId, true, true, $this->container);

                if ($userDetail['user_name']!='') {
                    $userName = str_replace($removeSplCharArr, "", $userDetail['user_name']);
                } else {
                    $userName = '';
                }
                if($userDetail['about_us']!='') {
                    $aboutUsWordCount = str_word_count($userDetail['about_us']);
                }
                if (!$userDetail['id']) {
                    throw new HttpException(410);
                }
                $parameters = array(
                    'userDetail' => $userDetail,
                    'userSiteObj' => $userSite,
                    'websiteset' => 1,
                    'aboutUsWordCount' => $aboutUsWordCount,
                    'userName' => $userName,
                );

                $mobileDetectManager = $this->get('fa.mobile.detect.manager');
                if ($mobileDetectManager->isMobile() && !$mobileDetectManager->isTablet()) {
                    return $this->render('FaContentBundle:ProfilePage:showBusinessShopProfilePageMobile.html.twig', $parameters, $objResponse);
                } else {
                    return $this->render('FaContentBundle:ProfilePage:showBusinessShopProfilePage.html.twig', $parameters, $objResponse);
                }
            } else {
                return $this->handleMessage($this->get('translator')->trans('User does not have any package assigned.', array(), 'frontend-profile-page'), 'fa_frontend_homepage', array(), 'error');
            }
        }
    }

    /**
     * List of reviews from buyers or sellers.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function showUserReviewAction($userId, $limit = 2, $page = 1, $excludeIds = null, $reviewHeading = null, Request $request)
    {
        $contentHtml = '';
        $excludeIdArray = array();
        if ($excludeIds) {
            $excludeIdArray = explode(',', $excludeIds);
            $excludeIdArray = array_filter($excludeIdArray);
        }

        $query = $this->getRepository('FaUserBundle:UserReview')->getUserReviewsQuery($userId, null, $excludeIdArray);

        $this->get('fa.pagination.manager')->init($query, $page, $limit, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination(false);

        $parameters = array(
            'pagination' => $pagination,
            'userId' => $userId,
            'limit' => $limit,
            'page' => $page,
            'excludeIds' => $excludeIds,
            'reviewHeading' => $reviewHeading,
        );

        if ($request->isXmlHttpRequest()) {
            $contentHtml = $this->renderView('FaContentBundle:ProfilePage:listUserReview.html.twig', $parameters);
            return new JsonResponse(array('contentHtml' => $contentHtml));
        } else {
            return $this->render('FaContentBundle:ProfilePage:showUserReview.html.twig', $parameters);
        }
    }

    /**
     * Contact user profile page.
     *
     * @param string  $userId  Encrypted user id.
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function profilePageContactUserAction($userId, Request $request)
    {
        $error       = '';
        $htmlContent = '';
        $msgDivId    = '';

        if ($request->isXmlHttpRequest()) {
            $msgDivId      = $request->get('msg_div_id');
            $userId        = CommonManager::encryptDecrypt('profilepage', $userId, 'deencrypt');
            $userObj       = $this->getRepository('FaUserBundle:User')->findOneBy(array('id' => $userId));
            $userSiteObj       = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userId));
            $contactUserId = ($userObj ? $userObj->getId() : null);
            //check for user.
            if (!$userObj || ($userObj && $userObj->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID)) {
                $error = $this->get('translator')->trans('Unable to find active user.', array(), 'frontend-search-result');
            } else {
                $loggedinUser = null;
                if ($this->isAuth()) {
                    //check for own user.
                    $loggedinUser = $this->getLoggedInUser();
                    if ($loggedinUser->getId() == $contactUserId) {
                        $error = $this->get('translator')->trans('You are not able to contact your own account.', array(), 'frontend-profile-page');
                        $this->getRepository('FaUserBundle:User')->removeUserCookies();
                    }
                }

                if (!$error) {
                    $formManager = $this->get('fa.formmanager');
                    $message     = new Message();
                    $form        = $formManager->createForm(ProfilePageContactUserType::class, $message);

                    if ('POST' === $request->getMethod()) {
                        $form->handleRequest($request);
                        if ($form->isValid()) {
                            // Create half account in case of user not logged in.
                            if (!$this->isAuth()) {
                                $halfAccountData = array(
                                    'email'      => $form->get('sender_email')->getData(),
                                    'first_name' => $form->get('sender_first_name')->getData(),
                                    '_token'     => $this->get('security.csrf.token_manager')->getToken('fa_user_half_account')->getValue()
                                    //$this->container->get('form.csrf_provider')->generateCsrfToken('fa_user_half_account')
                                );

                                $halfAccountForm = $formManager->createForm(UserHalfAccountType::class, null, array('method' => 'POST'));
                                $halfAccountForm->submit($halfAccountData);

                                $loggedinUser = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('sender_email')->getData()));
                                if (!$loggedinUser) {
                                    $error = $this->get('translator')->trans('Unable to find user.', array(), 'frontend-profile-page');
                                } elseif ($loggedinUser->getStatus() && $loggedinUser->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                                    $error = $this->get('translator')->trans('Your account was blocked.', array(), 'frontend-profile-page');
                                } elseif ($loggedinUser->getId() == $contactUserId) {
                                    $error = $this->get('translator')->trans('You are not able to contact your own account.', array(), 'frontend-profile-page');
                                }
                            }

                            if ($loggedinUser && !$error) {
                                //save information
                                $parent  = $this->getRepository('FaMessageBundle:Message')->getLastNewMessage($form->get('subject')->getData(), $loggedinUser->getId(), $userObj);
                                $message = $this->getRepository('FaMessageBundle:Message')->updateContactUserMessage($message, $parent, $loggedinUser, $userObj, $request->getClientIp());
                                $message = $formManager->save($message);

                                // send message into moderation.
                                try {
                                    $this->getRepository('FaMessageBundle:Message')->sendContactIntoModeration($message, $this->container);
                                } catch (\Exception $e) {
                                    // No need do take any action as we have cron
                                    //  in background to again send the request.
                                }

                                //update email alerts
                                if ($form->get('email_alert')->getData()) {
                                    $loggedinUser->setIsEmailAlertEnabled(1);
                                } else {
                                    $loggedinUser->setIsEmailAlertEnabled(0);
                                }
                                $this->getEntityManager()->persist($loggedinUser);
                                $this->getEntityManager()->flush($loggedinUser);
                            }
                        } elseif ($request->isXmlHttpRequest()) {
                            $htmlContent = $this->renderView('FaContentBundle:ProfilePage:ajaxProfilePageContactUser.html.twig', array('form' => $form->createView(), 'contactUserObj' => $userObj, 'userSiteObj' => $userSiteObj, 'msg_div_id' => $msgDivId));
                        }
                    } else {
                        $htmlContent = $this->renderView('FaContentBundle:ProfilePage:ajaxProfilePageContactUser.html.twig', array('form' => $form->createView(), 'contactUserObj' => $userObj, 'userSiteObj' => $userSiteObj, 'msg_div_id' => $msgDivId));
                    }
                }
            }

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Show business profile action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectTiBusinessProfilePageAction(Request $request)
    {
        $params = explode('/', $request->get('page_string'));
        $params = array_filter($params);
        $ad_ref = array_pop($params);

        if (is_numeric($ad_ref)) {
            $ad_ref = array_pop($params);
        }

        $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('ad_ref' => $ad_ref));
        if ($userSite) {
            $url = $this->get('router')->generate('show_business_profile_page', array(
                'profileNameSlug' => $userSite->getSlug(),
            ), true);
            return $this->redirect($url, 301);
        } else {
            if (preg_match('/[A-Z0-9]{9,10}$/', $ad_ref, $matches)) {
                $url = $this->get('router')->generate('fa_frontend_homepage', array(), true);
                return $this->redirect($url, 301);
            } else {
                $url = $this->get('router')->generate('fa_frontend_homepage', array(), true);
                return $this->redirect($url, 301);
            }
        }
    }

    /**
     * Show business profile action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectTiMotorBusinessProfilePageAction(Request $request)
    {
        $slug = preg_replace('/-N-.+/', '', $request->get('page_string'));
        $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('slug' => $slug));
        if ($userSite) {
            $url = $this->get('router')->generate('show_business_profile_page', array(
                'profileNameSlug' => $userSite->getSlug(),
            ), true);
            return $this->redirect($url, 301);
        } else {
            $url = $this->get('router')->generate('fa_frontend_homepage', array(), true);
            return $this->redirect($url, 301);
        }
    }

    /**
     * Redirect ti profile page action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectTiProfilePageAction(Request $request)
    {
        if ($request->get('userId')) {
            $oldTiUserId = CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $request->get('userId'), 'decrypt');
            $userObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('old_ti_user_id' => $oldTiUserId));
        } elseif ($request->get('profileNameSlug')) {
            $userObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('old_ti_user_slug' => $request->get('profileNameSlug')));
            if (!$userObj) {
                $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('slug' => $request->get('profileNameSlug')));
                if ($userSite) {
                    $userObj = $userSite->getUser();
                }
            }
        }

        if ($userObj) {
            $userId = $userObj->getId();
            $userProfileSlug = $this->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);
            $userRole = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);
            if ($userRole == RoleRepository::ROLE_SELLER && $userProfileSlug) {
                $url = $this->get('router')->generate('show_private_profile_page', array('profileNameSlug' => $userProfileSlug, 'userId' => CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $userId)), true);
                return $this->redirect($url, 301);
            } elseif (($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) && $userProfileSlug) {
                $url = $this->get('router')->generate('show_business_profile_page', array('profileNameSlug' => $userProfileSlug), true);
                return $this->redirect($url, 301);
            } else {
                $url = $this->get('router')->generate('show_business_profile_page', array('profileNameSlug' => $request->get('profileNameSlug')), true);
                return $this->redirect($url, 301);
            }
        } else {
            $url = $this->get('router')->generate('show_business_profile_page', array('profileNameSlug' => $request->get('profileNameSlug')), true);
            return $this->redirect($url, 301);
        }
    }

    /**
     * redirectTiShowBusinessUserAds page action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectTiShowBusinessUserAdsAction(Request $request)
    {
        if ($request->get('profileNameSlug')) {
            $userObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('old_ti_user_slug' => $request->get('profileNameSlug')));
            if (!$userObj) {
                $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('slug' => $request->get('profileNameSlug')));
                if ($userSite) {
                    $userObj = $userSite->getUser();
                }
            }
        }

        if ($userObj) {
            $userId = $userObj->getId();
            $userProfileSlug = $this->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);
            $userRole = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);
            if ($userRole == RoleRepository::ROLE_SELLER && $userProfileSlug) {
                $url = $this->get('router')->generate('show_private_user_ads', array('profileNameSlug' => $userProfileSlug, 'pageString' => 'ads', 'userId' => CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $userId)), true);
                return $this->redirect(rtrim($url, '/'), 301);
            } elseif (($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) && $userProfileSlug) {
                $url = $this->get('router')->generate('show_business_user_ads', array('profileNameSlug' => $userProfileSlug), true);
                return $this->redirect($url, 301);
            } else {
                $url = $this->get('router')->generate('show_business_user_ads', array('profileNameSlug' => $request->get('profileNameSlug')), true);
                return $this->redirect($url, 301);
            }
        } else {
            $url = $this->get('router')->generate('show_business_user_ads', array('profileNameSlug' => $request->get('profileNameSlug')), true);
            return $this->redirect($url, 301);
        }
    }

    /**
     * redirectTiShowBusinessUserAdsPage page action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectTiShowBusinessUserAdsPageAction(Request $request)
    {
        if ($request->get('profileNameSlug')) {
            $userObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('old_ti_user_slug' => $request->get('profileNameSlug')));
            if (!$userObj) {
                $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('slug' => $request->get('profileNameSlug')));
                if ($userSite) {
                    $userObj = $userSite->getUser();
                }
            }
        }

        if ($userObj) {
            $userId = $userObj->getId();
            $userProfileSlug = $this->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);
            $userRole = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);
            if ($userRole == RoleRepository::ROLE_SELLER && $userProfileSlug) {
                $pageString = 'ads';
                if ($request->get('page')) {
                    $pageString .= '/page-'.$request->get('page');
                }
                $url = $this->get('router')->generate('show_private_user_ads', array('profileNameSlug' => $userProfileSlug, 'pageString' => $pageString, 'userId' => CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $userId)), true);
                return $this->redirect(rtrim($url, '/'), 301);
            } elseif (($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) && $userProfileSlug) {
                $url = $this->get('router')->generate('show_business_user_ads_page', array('profileNameSlug' => $userProfileSlug, 'page' => $request->get('page')), true);
                return $this->redirect($url, 301);
            } else {
                $url = $this->get('router')->generate('show_business_user_ads_page', array('profileNameSlug' => $request->get('profileNameSlug'), 'page' => $request->get('page')), true);
                return $this->redirect($url, 301);
            }
        } else {
            $url = $this->get('router')->generate('show_business_user_ads_page', array('profileNameSlug' => $request->get('profileNameSlug'), 'page' => $request->get('page')), true);
            return $this->redirect($url, 301);
        }
    }

    /**
     * redirectTiShowBusinessUserAdsPageLocation page action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectTiShowBusinessUserAdsPageLocationAction(Request $request)
    {
        if ($request->get('profileNameSlug')) {
            $userObj = $this->getRepository('FaUserBundle:User')->findOneBy(array('old_ti_user_slug' => $request->get('profileNameSlug')));
            if (!$userObj) {
                $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('slug' => $request->get('profileNameSlug')));
                if ($userSite) {
                    $userObj = $userSite->getUser();
                }
            }
        }

        if ($userObj) {
            $userId = $userObj->getId();
            $userProfileSlug = $this->getRepository('FaUserBundle:User')->getUserProfileSlug($userId, $this->container);
            $userRole = $this->getRepository('FaUserBundle:User')->getUserRole($userId, $this->container);
            if ($userRole == RoleRepository::ROLE_SELLER && $userProfileSlug) {
                $pageString = 'ads';
                if ($request->get('page_string')) {
                    $pageString .= '/'.$request->get('page_string');
                }
                $url = $this->get('router')->generate('show_private_user_ads', array('profileNameSlug' => $userProfileSlug, 'pageString' => $pageString, 'userId' => CommonManager::encryptDecrypt($this->container->getParameter('profile_page_encryption_key'), $userId)), true);
                return $this->redirect(rtrim($url, '/'), 301);
            } elseif (($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) && $userProfileSlug) {
                $url = $this->get('router')->generate('show_business_user_ads_location', array('profileNameSlug' => $userProfileSlug, 'page_string' => $request->get('page_string'), 'location' => $request->get('location')), true);
                return $this->redirect($url, 301);
            } else {
                $url = $this->get('router')->generate('show_business_user_ads_location', array('profileNameSlug' => $request->get('profileNameSlug'), 'page_string' => $request->get('page_string'), 'location' => $request->get('location')), true);
                return $this->redirect($url, 301);
            }
        } else {
            $url = $this->get('router')->generate('show_business_user_ads_location', array('profileNameSlug' => $request->get('profileNameSlug'), 'page_string' => $request->get('page_string'), 'location' => $request->get('location')), true);
            return $this->redirect($url, 301);
        }
    }
}
