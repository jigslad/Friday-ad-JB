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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Manager\UserSiteBannerManager;
use Fa\Bundle\UserBundle\Form\UserSiteBannerType;

/**
 * This controller is used for image upload for user site banner.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSiteBannerController extends CoreController
{
    /**
     * Show user site banner uploader.
     *
     * @param integer $userSiteId User site id.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showUserSiteBannerUploaderAction($userSiteId = null)
    {
        return $this->render('FaUserBundle:UserSiteBanner:showUserSiteBannerUploader.html.twig', array('userSiteId' => $userSiteId));
    }

    /**
     * Save uploaded user site image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxSaveUploadedImageAction(Request $request)
    {
        if ($request->ismethod('post')) {
            $error  = '';
            $params = $request->get('fa_user_user_site_banner');

            $userSiteId = $params['user_site_id'];
            $userSite   = $this->getRepository('FaUserBundle:UserSite')->find($userSiteId);
            $user       = $userSite->getUser();

            if ($user) {
                if ($this->isAdminLoggedIn()) {
                    if ($this->get('fa.resource.authorization.manager')->isGranted('ajax_user_image_save_admin')) {
                    } else {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error));
                    }
                } elseif ($this->isAuth()) {
                    $loggedUser = $this->getSecurityTokenStorage()->getToken()->getUser();
                    if ($user->getId() != $loggedUser->getId()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error));
                    }
                } elseif (!$this->isAuth()) {
                    $error = $this->getInvadidMessage();
                    return new JsonResponse(array('error' => $error));
                }
            } elseif ($this->isAdminLoggedIn() && !$this->get('fa.resource.authorization.manager')->isGranted('ajax_user_image_save_admin')) {
                $error = $this->getInvadidMessage();
                return new JsonResponse(array('error' => $error));
            }

            $formManager = $this->get('fa.formmanager');
            $form        = $formManager->createForm(UserSiteBannerType::class);
            $formParams  = array('user_site_id' => $userSiteId);

            $files = $request->files->get('fa_user_user_site_banner');
            $formParams['fileData'] = $files['fileData'];
            $form->submit($formParams);

            try {
                if ($form->isValid()) {
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:upload-user-site-banner:image-s3 --user_site_id='.$userSiteId.' >/dev/null &');
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:send:business-user-for-moderation --userId='.$user->getId().' >/dev/null &');
                } else {
                    $error = $form->getErrors(true, false);
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            return new JsonResponse(
                array(
                    'error' => $error,
                )
            );
        }

        return new Response();
    }

    /**
     * Get user site banner image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxRenderUploadedImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $userSiteId = $request->get('userSiteId');
            $cache      = $request->get('cache');
            $fromProfilePage = $request->get('fromProfilePage');
            $userSite   = $this->getRepository('FaUserBundle:UserSite')->find($userSiteId);

            if ($request->get('is_admin')) {
                $images = $this->renderView('FaUserBundle:UserSiteBanner:userSiteBannerAdmin.html.twig', array('userSiteObj' => $userSite, 'cache' => $cache));
            } else {
                $images = $this->renderView('FaUserBundle:UserSiteBanner:userSiteBanner.html.twig', array('userSiteObj' => $userSite, 'cache' => $cache, 'fromProfilePage' => $fromProfilePage));
            }

            return new JsonResponse(array('images' => $images));
        }

        return new Response();
    }

    /**
     * Get user site banner image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxGetBigImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $image    = '';
            $error    = '';
            $userSiteId = $request->get('userSiteId');
            $userSite   = $this->getRepository('FaUserBundle:UserSite')->find($userSiteId);

            //validate user if ad object is found
            if ($userSite && $userSite->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_get_big_ad_image_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'image' => $image));
                    }
                } else {
                    if (!$this->isAuth()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'image' => $image));
                    } else {
                        $user = $this->getSecurityTokenStorage()->getToken()->getUser();
                        if ($user->getId() != $userSite->getUser()->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'image' => $image));
                        }
                    }
                }
            }
            if (!$userSite) {
                $error = $this->get('translator')->trans('Problem in loading banner image.');
            } else {
                if ($request->get('is_admin')) {
                    $image = $this->renderView('FaUserBundle:UserSiteBanner:renderUserSiteBannerBigImageAdmin.html.twig', array('userSiteObj' => $userSite));
                } else {
                    $image = $this->renderView('FaUserBundle:UserSiteBanner:renderUserSiteBannerBigImage.html.twig', array('userSiteObj' => $userSite));
                }
            }

            return new JsonResponse(array('error' => $error, 'image' => $image));
        }

        return new Response();
    }

    /**
     * Crop user site banner image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxCropImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';
            $userSiteId = $request->get('userSiteId');
            $userSite   = $this->getRepository('FaUserBundle:UserSite')->find($userSiteId);

            //validate user if ad object is found
            if ($userSite && $userSite->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_crop_ad_image_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    }
                } else {
                    if (!$this->isAuth()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    } else {
                        $user = $this->getSecurityTokenStorage()->getToken()->getUser();
                        if ($user->getId() != $userSite->getUser()->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                        }
                    }
                }
            }

            //regenerate images
            $imagePath = $this->get('kernel')->getRootDir().'/../web/'.$userSite->getBannerPath();
            
            $fileExistsInAws = 0;
            if(CommonManager::checkImageExistOnAws($this->container,$userSite->getBannerPath().DIRECTORY_SEPARATOR.'banner_'.$userSiteId.'.jpg')) {
                if (!file_exists($imagePath)) {
                    mkdir($imagePath, 0777, true);
                }
                
                $awsImagePath = $this->container->getParameter('fa.static.aws.url').DIRECTORY_SEPARATOR.$userSite->getBannerPath();
                $orgawsurl = $awsImagePath.DIRECTORY_SEPARATOR.'banner_'.$userSiteId.'_org.jpg';
                $orglocalimg = $orgImagePath.DIRECTORY_SEPARATOR.'banner_'.$userSiteId.'_org.jpg';
                file_put_contents($orglocalimg, file_get_contents($orgawsurl));                
                $fileExistsInAws = 1;
            } 
            
            
            $userSiteBannerManager= new UserSiteBannerManager($this->container, $userSiteId, $imagePath);
            //create thumbnails
            $userSiteBannerManager->removeImage(true);

            //crop image
            exec('convert -rotate '.($request->get('banner_angle').' -resize '.($request->get('banner_scale') * 100).'% '.$imagePath.DIRECTORY_SEPARATOR.'banner_'.$userSiteId.'_org.jpg'.' -crop '.$request->get('banner_w').'x'.$request->get('banner_h').'+'.$request->get('banner_x').'+'.$request->get('banner_y').' '.$imagePath.DIRECTORY_SEPARATOR.'banner_'.$userSiteId.'.jpg'));
            
            if($fileExistsInAws==1) {
                $userSiteBannerManager->uploadImagesToS3($userSiteId);                
            }
            
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:send:business-user-for-moderation --userId='.$userSite->getUser()->getId().' >/dev/null &');
            if (!$userSite) {
                $error = $this->get('translator')->trans('Problem in croping banner.');
            } else {
                $successMsg = $this->get('translator')->trans('Banner has been cropped successfully.');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        }

        return new Response();
    }

    /**
     * GetInvadidMessage.
     *
     * @return string
     */
    private function getInvadidMessage()
    {
        return $this->get('translator')->trans('You do not have permission to access this resource.');
    }

    /**
     * Ajax change banner.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxChangeBannerAction(Request $request)
    {
        $htmlContent = '';
        $error       = '';

        if ($this->isAuth() && $request->isXmlHttpRequest()) {
            $loggedinUser = $this->getLoggedInUser();
            $bannerId = $request->get('bannerId');
            $fromProfilePage = $request->get('fromProfilePage');
            $userSiteBanner = $this->getRepository('FaUserBundle:UserSiteBanner')->find($bannerId);
            $userSiteObj = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $loggedinUser->getId()));
            if ($loggedinUser->getId() != $userSiteObj->getUser()->getId()) {
                $error = $this->getInvadidMessage();
                return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
            }
            if ($userSiteObj && $userSiteBanner) {
                $bannerUpdated = $this->getRepository('FaUserBundle:UserSiteBanner')->changeBanner($userSiteObj->getId(), $userSiteBanner, $this->container);
                if ($bannerUpdated) {
                    $htmlContent = $this->renderView('FaUserBundle:UserSiteBanner:userSiteBanner.html.twig', array('userSiteObj' => $userSiteObj, 'fromProfilePage' => $fromProfilePage));
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:send:business-user-for-moderation --userId='.$userSiteObj->getUser()->getId().' >/dev/null &');
                } else {
                    $error = $this->get('translator')->trans('Problem in changing banner.', array(), 'frontend-my-profile');
                }
            } else {
                $error = $this->get('translator')->trans('Invalid banner selected.', array(), 'frontend-my-profile');
            }

            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
        }

        return new Response();
    }
}
