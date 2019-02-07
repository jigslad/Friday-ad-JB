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
use Fa\Bundle\UserBundle\Form\UserPrivateProfileType;
use Fa\Bundle\UserBundle\Form\UserBusinessProfileType;
use Fa\Bundle\UserBundle\Form\UserBusinessShopProfileType;
use Fa\Bundle\UserBundle\Form\EditWelcomeMessageType;
use Fa\Bundle\UserBundle\Form\EditContactDetailsType;
use Fa\Bundle\UserBundle\Form\EditSocialProfilesType;
use Fa\Bundle\UserBundle\Form\EditAboutUsType;
use Fa\Bundle\UserBundle\Form\EditVideoType;
use Fa\Bundle\UserBundle\Form\EditLocationType;

/**
 * This controller is used for editing user's account information.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class MyProfileController extends CoreController
{
    /**
     * Show user profile.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function myProfileAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $userRole     = $this->getRepository('FaUserBundle:User')->getUserRole($loggedinUser->getId(), $this->container);
        $formName     = UserPrivateProfileType::class;
        $templateName = 'privateProfile';
        $activeShopPackage = null;
        $shopForm          = null;
        $transcations      = null;

        if ($request->get('transactionId')) {
            $this->get('session')->getFlashBag()->get('error');
            $cart = $this->getRepository('FaPaymentBundle:Cart')->findOneBy(array('cart_code' => $request->get('transactionId'), 'status' => 0, 'user' => $loggedinUser->getId()));

            if ($cart) {
                $transcations = $this->getRepository('FaPaymentBundle:Payment')->getTranscationDetailsForGA($cart->getCartCode(), $loggedinUser);
            }
        }

        if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            $formName     = UserBusinessProfileType::class;
            $templateName = 'businessProfile';
        }
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            $activeShopPackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($loggedinUser);
            $userSite          = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedinUser->getId()));
            if (!$userSite) {
                $userSite = new UserSite();
                $userSite->setUser($loggedinUser);
                $this->getEntityManager()->persist($userSite);
                $this->getEntityManager()->flush($userSite);
            }
            $form = $formManager->createForm($formName, $userSite);
            if ($activeShopPackage && $activeShopPackage->getPackage() && $activeShopPackage->getPackage()->getPackageText() != PackageRepository::SHP_PACKAGE_BASIC_TEXT) {
                // assign banner if it not exist.
                if (!$userSite->getBannerPath()) {
                    $this->getRepository('FaUserBundle:UserSiteBanner')->updateUserBanner($loggedinUser, $this->container);
                }
                $shopForm = $formManager->createForm(UserBusinessShopProfileType::class, $userSite);
            }
        } else {
            $form = $formManager->createForm($formName, $loggedinUser);
        }

        if ('POST' === $request->getMethod() && $request->request->has($form->getName())) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getEntityManager();
                $em->flush();

                $this->updateShopDetailInSolr($loggedinUser, $userRole);

                $this->container->get('session')->getFlashBag()->add('profile_success', $this->get('translator')->trans('Your profile detail updated successfully.', array(), 'frontend-my-profile'));
                return $this->redirectToRoute('my_profile');
            }
        }

        if ('POST' === $request->getMethod() && $shopForm && $request->request->has($shopForm->getName())) {
            $shopForm->handleRequest($request);

            if ($shopForm->isValid()) {
                $em = $this->getEntityManager();
                $em->flush();

                $this->updateShopDetailInSolr($loggedinUser, $userRole);

                $this->container->get('session')->getFlashBag()->add('profile_shop_success', $this->get('translator')->trans('Your shop detail updated successfully.', array(), 'frontend-my-profile'));
                return $this->redirectToRoute('my_profile');
            }
        }

        if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            $parameters = array(
                'form' => $form->createView(),
                'activeShopPackage' => $activeShopPackage,
                'userSiteObj' => $userSite,
            );
            if ($shopForm) {
                $parameters['shopForm'] = $shopForm->createView();
            }
        } else {
            $parameters = array(
                'form' => $form->createView(),
            );
        }

        if ($transcations) {
            $parameters['getTranscationJs'] = CommonManager::getGaTranscationJs($transcations);
            $parameters['getItemJs']        = CommonManager::getGaItemJs($transcations);
            $parameters['ga_transaction']   = $transcations;
        }

        $objResponse = CommonManager::setCacheControlHeaders();

        return $this->render('FaUserBundle:MyProfile:'.$templateName.'.html.twig', $parameters, $objResponse);
    }

    private function updateShopDetailInSolr($loggedinUser, $userRole)
    {
        if ($loggedinUser && $loggedinUser->getBusinessCategoryId() && ($userRole == RoleRepository::ROLE_BUSINESS_SELLER  || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION)) {
            if (in_array($loggedinUser->getBusinessCategoryId(), array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:user-shop-detail-solr-index --id='.$loggedinUser->getId().' >/dev/null &');
            }
        }
    }

    /**
     * edit welcome message.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxEditWelcomeMessageAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $successContent = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $loggedInUser = $this->getLoggedInUser();
                $formManager  = $this->get('fa.formmanager');
                $userSite = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedInUser->getId()));
                $form         = $formManager->createForm(EditWelcomeMessageType::class, $userSite);
                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        //save information
                        $userSite = $formManager->save($userSite);
                        $userDetail = array();
                        $userDetail['company_welcome_message'] = $userSite->getCompanyWelcomeMessage();
                        $successContent = $this->renderView('FaContentBundle:ProfilePage:shopWelcomeMessage.html.twig', array('allowProfileEdit' => true, 'userDetail' => $userDetail));
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditWelcomeMessage.html.twig', array('form' => $form->createView()));
                    }
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditWelcomeMessage.html.twig', array('form' => $form->createView()));
                }
            } else {
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'successContent' => $successContent));
        } else {
            return new Response();
        }
    }

    /**
     * edit contatct details.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxEditContactDetailsAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $successContent = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $loggedInUser = $this->getLoggedInUser();
                $formManager  = $this->get('fa.formmanager');
                $userSite = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedInUser->getId()));
                $form         = $formManager->createForm(EditContactDetailsType::class, $userSite);
                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        //save information
                        $userSite = $formManager->save($userSite);
                        $userDetail = array();
                        $userDetail['company_address'] = $userSite->getCompanyAddress();
                        $userDetail['phone1'] = $userSite->getPhone1();
                        $userDetail['phone2'] = $userSite->getPhone2();
                        $userDetail['website_link'] = $userSite->getWebsiteLink();
                        $userDetail['user_name'] = $loggedInUser->getFullName();
                        $successContent = $this->renderView('FaContentBundle:ProfilePage:showBusinessAddressDetail.html.twig', array('userDetail' => $userDetail, 'allowProfileEdit' => true));
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditContactDetails.html.twig', array('form' => $form->createView()));
                    }
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditContactDetails.html.twig', array('form' => $form->createView()));
                }
            } else {
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'successContent' => $successContent));
        } else {
            return new Response();
        }
    }

    /**
     * edit social profiles.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxEditSocialProfilesAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $successContent = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $loggedInUser = $this->getLoggedInUser();
                $formManager  = $this->get('fa.formmanager');
                $userSite = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedInUser->getId()));
                $form         = $formManager->createForm(EditSocialProfilesType::class, $userSite);
                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        //save information
                        $userSite = $formManager->save($userSite);
                        $userDetail = array();
                        $userDetail['facebook_url'] = $userSite->getFacebookUrl();
                        $userDetail['google_url'] = $userSite->getGoogleUrl();
                        $userDetail['twitter_url'] = $userSite->getTwitterUrl();
                        $userDetail['pinterest_url'] = $userSite->getPinterestUrl();
                        $userDetail['instagram_url'] = $userSite->getInstagramUrl();
                        $userDetail['id'] = $loggedInUser->getId();
                        $successContent = $this->renderView('FaContentBundle:ProfilePage:showSocialIcons.html.twig', array('userDetail' => $userDetail, 'allowProfileEdit' => true));
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditSocialProfiles.html.twig', array('form' => $form->createView()));
                    }
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditSocialProfiles.html.twig', array('form' => $form->createView()));
                }
            } else {
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'successContent' => $successContent));
        } else {
            return new Response();
        }
    }

    /**
     * edit about us.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxEditAboutUsAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $successContent = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $loggedInUser = $this->getLoggedInUser();
                $formManager  = $this->get('fa.formmanager');
                $userSite = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedInUser->getId()));
                $form         = $formManager->createForm(EditAboutUsType::class, $userSite);
                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        //save information
                        $userSite = $formManager->save($userSite);
                        $userDetail = array();
                        $userDetail['about_us'] = $userSite->getAboutUs();
                        $adDescWithReplacedPhone = CommonManager::hideOrRemovePhoneNumber($userDetail['about_us'], 'hide');
                        $adDescWithReplacedPhoneAndEmail = CommonManager::hideOrRemoveEmail($userSite->getId(), $adDescWithReplacedPhone, 'hide');
                        $successContent = $adDescWithReplacedPhoneAndEmail;
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditAboutUs.html.twig', array('form' => $form->createView()));
                    }
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditAboutUs.html.twig', array('form' => $form->createView()));
                }
            } else {
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'successContent' => $successContent));
        } else {
            return new Response();
        }
    }

    /**
     * edit video.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxEditVideoAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $successContent = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $loggedInUser = $this->getLoggedInUser();
                $formManager  = $this->get('fa.formmanager');
                $userSite = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedInUser->getId()));
                $form         = $formManager->createForm(EditVideoType::class, $userSite);
                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        //save information
                        $userSite = $formManager->save($userSite);
                        $userDetail = array();
                        $userDetail['youtube_video_url'] = $userSite->getYoutubeVideoUrl();
                        $successContent = $this->renderView('FaContentBundle:ProfilePage:showVideo.html.twig', array('userDetail' => $userDetail, 'allowProfileEdit' => true));
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditVideo.html.twig', array('form' => $form->createView()));
                    }
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditVideo.html.twig', array('form' => $form->createView()));
                }
            } else {
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'successContent' => $successContent));
        } else {
            return new Response();
        }
    }

    /**
     * edit gallery.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxEditGalleryAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $successContent = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $loggedInUser = $this->getLoggedInUser();
                $userSite = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedInUser->getId()));
                if ('POST' === $request->getMethod()) {
                    //save information
                    $userSiteImages = array();
                    $userDetail['site_id'] = $userSite->getId();
                    $userSiteImages = $this->getEntityManager()->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImages($userSite->getId());
                    $successContent = $this->renderView('FaContentBundle:ProfilePage:showShopGallery.html.twig', array('userSiteImages' => $userSiteImages, 'allowProfileEdit' => true, 'userDetail' => $userDetail));
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditGallery.html.twig', array('userSiteObj' => $userSite));
                }
            } else {
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'successContent' => $successContent));
        } else {
            return new Response();
        }
    }

    /**
     * edit location.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxEditLocationAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';
        $successContent = '';
        $mapContent = '';
        $userDetail = array();

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $loggedInUser = $this->getLoggedInUser();
                $formManager  = $this->get('fa.formmanager');
                $userSite = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedInUser->getId()));
                $form         = $formManager->createForm(EditLocationType::class, $userSite);
                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        //save information
                        $userSite = $formManager->save($userSite);
                        $userDetail['business_category_id'] = $userSite->getUser()->getBusinessCategoryId();
                        $userDetail['show_map'] = $userSite->getShowMap();
                        $userDetail['created_at'] = $userSite->getCreatedAt();
                        $userDetail['town_name'] = ($userSite->getUser()->getLocationTown() ? $userSite->getUser()->getLocationTown()->getName() : null);
                        $userDetail['domicile_name'] = ($userSite->getUser()->getLocationDomicile() ? $userSite->getUser()->getLocationDomicile()->getName() : null);
                        if ($userSite->getUser()->getLocationTown()) {
                            $userDetail['latitude'] = $userSite->getUser()->getLocationTown()->getLatitude();
                            $userDetail['longitude'] = $userSite->getUser()->getLocationTown()->getLongitude();
                        }
                        $mapContent = $this->renderView('FaContentBundle:ProfilePage:showMap.html.twig', array('userDetail' => $userDetail, 'allowProfileEdit' => true));
                        $successContent = $this->renderView('FaContentBundle:ProfilePage:shopLocationDetail.html.twig', array('userDetail' => $userDetail, 'allowProfileEdit' => true, 'entityCacheManager' => $this->container->get('fa.entity.cache.manager')));
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditLocation.html.twig', array('form' => $form->createView()));
                    }
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:MyProfile:ajaxEditLocation.html.twig', array('form' => $form->createView()));
                }
            } else {
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent, 'successContent' => $successContent, 'mapContent' => $mapContent, 'userDetail' => $userDetail));
        } else {
            return new Response();
        }
    }
}
