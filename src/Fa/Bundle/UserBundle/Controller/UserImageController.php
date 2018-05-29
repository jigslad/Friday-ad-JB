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
use Fa\Bundle\UserBundle\Manager\UserImageManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Manager\ThumbnailManager;
use Fa\Bundle\UserBundle\Form\UserImageType;

/**
 * This controller is used for image upload for user and company.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserImageController extends CoreController
{
    /**
     * Show image uploader for user.
     *
     * @param integer $userId    User id.
     * @param string  $isCompany Is company logo or user image.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showImageUploaderAction($userId = null, $isCompany = false, Request $request)
    {
        if ($request->get('is_admin')) {
            return $this->render('FaUserBundle:UserImage:showImageUploaderAdmin.html.twig', array('imageLimitRemaining' => $this->container->getParameter('fa.image.'.$vertical.'_upload_limit') - $adImgCount, 'adId' => $adId, 'vertical' => $vertical, 'is_admin' => 1));
        } else {
            return $this->render('FaUserBundle:UserImage:showImageUploader.html.twig', array('userId' => $userId, 'isCompany' => $isCompany));
        }
    }

    /**
     * Show profile image uploader for user.
     *
     * @param integer $userId    User id.
     * @param string  $isCompany Is company logo or user image.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showProfileImageUploaderAction($userId = null, $isCompany = false)
    {
        return $this->render('FaUserBundle:UserImage:showProfileImageUploader.html.twig', array('userId' => $userId, 'isCompany' => $isCompany, 'profileImage' => true));
    }

    /**
     * Save uploaded user image or company logo using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxSaveUploadedImageAction(Request $request)
    {
        if ($request->ismethod('post')) {
            $error  = '';
            $params = $request->get('fa_user_image');

            $userId       = $params['user_id'];
            $isCompany    = $params['is_company'];
            $profileImage = (isset($params['profileImage']) ? $params['profileImage'] : null);
            $user         = $this->getRepository('FaUserBundle:User')->find($userId);

            if (!is_numeric($userId)) {
                $files = $request->files->get('fa_user_image');
                $uploadedFile = $files['fileData'];
                $imagePath    = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp';
                $orgImageName = $uploadedFile->getClientOriginalName();
                $orgImageName = str_replace(array('"', "'"), '', $orgImageName);
                $orgImagePath = $imagePath;
                $orgImageName = escapeshellarg($orgImageName);

                //upload original image.
                $uploadedFile->move($orgImagePath, $orgImageName);
                $dimension = getimagesize($imagePath.DIRECTORY_SEPARATOR.$orgImageName);
                $origImage = new ThumbnailManager($dimension[0], $dimension[1], true, false, 90, 'ImageMagickManager');
                $origImage->loadFile($imagePath.DIRECTORY_SEPARATOR.$orgImageName);
                $origImage->save($imagePath.DIRECTORY_SEPARATOR.$userId.'_original.jpg', 'image/jpeg');
                unlink($imagePath.DIRECTORY_SEPARATOR.$orgImageName);

                $userImageManager = new UserImageManager($this->container, $userId, $orgImagePath, $isCompany);
                $userImageManager->saveOriginalJpgImage($userId.'_original.jpg');
                sleep(1);

                return new Response();
            } else if ($user) {
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
            $form        = $formManager->createForm(UserImageType::class);
            $formParams  = array('user_id' => $userId, 'is_company' => $isCompany, 'profileImage' => $profileImage);

            $files = $request->files->get('fa_user_image');
            $formParams['fileData'] = $files['fileData'];
            $form->submit($formParams);

            try {
                if ($form->isValid()) {
                    $this->getRepository('FaMessageBundle:NotificationMessageEvent')->closeNotificationByAdId('no_profile_photo', null, $userId);
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:ad-solr-index --user_id="'.$userId.'" --category="Jobs" update >/dev/null &');
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:ad-solr-index --user_id="'.$userId.'" --category="For sale" update >/dev/null &');
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:ad-solr-index --user_id="'.$userId.'" --category="Adult" update >/dev/null &');
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:send:business-user-for-moderation --userId='.$userId.' >/dev/null &');
                    $culture  = CommonManager::getCurrentCulture($this->container);
                    $cacheKey = 'user|isTrustedUser|'.$user->getId().'|'.$culture;
                    CommonManager::removeCache($this->container, $cacheKey);
                } else {
                    $error = $form->getErrorsAsString();
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            return new JsonResponse(array('error' => $error));
        }

        return new Response();
    }

    /**
     * Get user image or company logo based on user id using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxRenderUploadedImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $userId       = $request->get('userId');
            $isCompany    = $request->get('isCompany');
            $profileImage = $request->get('profileImage');
            $image = CommonManager::getUserLogo($this->container, null, $userId, null, null, true, $isCompany);

            if (!is_numeric($userId)) {
             $imagePath = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp/'.$userId.'.jpg';
             $image = CommonManager::getUserLogo($this->container, $imagePath, $userId, null, null, true, $isCompany);
             return new JsonResponse(array('image' => $image));
            }
            if ($profileImage) {
                $imageObj = null;
                if ($isCompany) {
                    $imageObj = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user'=> $userId));
                    if ($imageObj && $imageObj->getPath()) {
                        $image = CommonManager::getUserLogo($this->container, $imageObj->getPath(), $userId, null, null, true, $isCompany);
                    }
                } else {
                    $imageObj = $this->getRepository('FaUserBundle:User')->find($userId);
                    if ($imageObj && $imageObj->getImage()) {
                        $image = CommonManager::getUserLogo($this->container, $imageObj->getImage(), $userId, null, null, true);
                    }
                }
            } else {
                if ($request->get('is_admin')) {
                    $image = $this->renderView('FaUserBundle:UserImage:renderUploadedImageAdmin.html.twig', array('userId' => $userId, 'isCompany' => $isCompany));
                } else {
                    $image = $this->renderView('FaUserBundle:UserImage:renderUploadedImage.html.twig', array('userId' => $userId, 'isCompany' => $isCompany));
                }
            }

            return new JsonResponse(array('image' => $image));
        }

        return new Response();
    }

    /**
     * Delete user image or company logo using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxDeleteImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';
            $userId     = $request->get('userId');
            $isCompany  = filter_var($request->get('isCompany'), FILTER_VALIDATE_BOOLEAN);
            $user       = $this->getRepository('FaUserBundle:User')->find($userId);
            $profileImage = $request->get('profileImage');

            if ($user) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_delete_user_image_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    }
                } else {
                    if (!$this->isAuth()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    } else {
                        $loggedUser = $this->getSecurityTokenStorage()->getToken()->getUser();
                        if ($loggedUser->getId() != $user->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                        }
                    }
                }
            }

            if ($isCompany) {
                $imageObj = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userId));
            } else {
                $imageObj = $user;
            }

            if (($isCompany && $imageObj && $imageObj->getPath()) || (!$isCompany && $imageObj && $imageObj->getImage())) {
                $webPath = $this->container->get('kernel')->getRootDir().'/../web';

                if ($isCompany) {
                    $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imageObj->getPath();
                    $imageObj->setPath(null);
                } else {
                    $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imageObj->getImage();
                    $imageObj->setImage(null);
                }

                $this->getEntityManager()->persist($imageObj);
                $this->getEntityManager()->flush($imageObj);

                $adImageManager = new UserImageManager($this->container, $userId, $orgImagePath, $isCompany);
                $adImageManager->removeImage();
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:send:business-user-for-moderation --userId='.$userId.' >/dev/null &');

                if ($profileImage) {
                    $image = CommonManager::getUserLogo($this->container, null, $userId, null, null, true, $isCompany);
                } elseif ($request->get('is_admin')) {
                    $image = $this->renderView('FaUserBundle:UserImage:renderUploadedImageAdmin.html.twig', array('userId' => $userId, 'isCompany' => $isCompany));
                } else {
                    $image = $this->renderView('FaUserBundle:UserImage:renderUploadedImage.html.twig', array('userId' => $userId, 'isCompany' => $isCompany));
                }

                if (file_exists($orgImagePath.DIRECTORY_SEPARATOR.$userId.'.jpg')) {
                    if ($isCompany) {
                        $error = $this->get('translator')->trans('Problem in deleting company logo.');
                    } else {
                        $error = $this->get('translator')->trans('Problem in deleting user image.');
                    }
                } else {
                    if ($isCompany) {
                        $successMsg = $this->get('translator')->trans('Company logo has been deleted successfully.');
                    } else {
                        $successMsg = $this->get('translator')->trans('User image has been deleted successfully.');
                    }
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:ad-solr-index --user_id="'.$userId.'" --category="Jobs" update >/dev/null &');
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:ad-solr-index --user_id="'.$userId.'" --category="For sale" update >/dev/null &');
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:update:ad-solr-index --user_id="'.$userId.'" --category="Adult" update >/dev/null &');
                    $culture  = CommonManager::getCurrentCulture($this->container);
                    $cacheKey = 'user|isTrustedUser|'.$user->getId().'_'.$culture;
                    CommonManager::removeCache($this->container, $cacheKey);
                }

                return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg,'image' => $image));
            }

            if ($isCompany) {
                $error = $this->get('translator')->trans('Problem in deleting company logo.');
            } else {
                $error = $this->get('translator')->trans('Problem in deleting user image.');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        }

        return new Response();
    }

    /**
     * Get user image for cropping using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxGetProfileBigImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error       = '';
            $htmlContent = '';
            $userId      = $request->get('userId');
            $isCompany   = filter_var($request->get('isCompany'), FILTER_VALIDATE_BOOLEAN);
            $user        = $this->getRepository('FaUserBundle:User')->find($userId);
            $fromProfilePage = $request->get('fromProfilePage');

            if ($user) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_delete_user_image_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
                    }
                } else {
                    if (!$this->isAuth()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
                    } else {
                        $loggedUser = $this->getSecurityTokenStorage()->getToken()->getUser();
                        if ($loggedUser->getId() != $user->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
                        }
                    }
                }
            }

            if ($isCompany) {
                $imageObj = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userId));
            } else {
                $imageObj = $user;
            }

            if (($isCompany && $imageObj && $imageObj->getPath()) || (!$isCompany && $imageObj && $imageObj->getImage())) {
                $webPath = $this->container->get('kernel')->getRootDir().'/../web';

                if ($isCompany) {
                    $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imageObj->getPath();
                } else {
                    $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imageObj->getImage();
                }

                if (file_exists($orgImagePath.DIRECTORY_SEPARATOR.$userId.'.jpg')) {
                    $imageUrl    = str_replace('.jpg', '_org.jpg', CommonManager::getUserLogoByUserId($this->container, $userId, true, true));
                    $htmlContent = $this->renderView('FaUserBundle:UserImage:renderProfileBigImage.html.twig', array('userId' => $userId, 'isCompany' => $isCompany, 'imageUrl' => $imageUrl, 'fromProfilePage' => $fromProfilePage));
                } else {
                    $error = $this->get('translator')->trans('Problem in loading image.');
                }

                return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
            }

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
        }

        return new Response();
    }

    /**
     * Crop user image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxCropProfileImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error     = '';
            $image     = '';
            $userId    = $request->get('userId');
            $isCompany = filter_var($request->get('isCompany'), FILTER_VALIDATE_BOOLEAN);
            $user      = $this->getRepository('FaUserBundle:User')->find($userId);

            if ($user) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_delete_user_image_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'image' => $image));
                    }
                } else {
                    if (!$this->isAuth()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'image' => $image));
                    } else {
                        $loggedUser = $this->getSecurityTokenStorage()->getToken()->getUser();
                        if ($loggedUser->getId() != $user->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'image' => $image));
                        }
                    }
                }
            }

            if ($isCompany) {
                $imageObj = $this->getEntityManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $userId));
            } else {
                $imageObj = $user;
            }

            if (($isCompany && $imageObj && $imageObj->getPath()) || (!$isCompany && $imageObj && $imageObj->getImage())) {
                $webPath = $this->container->get('kernel')->getRootDir().'/../web';

                if ($isCompany) {
                    $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imageObj->getPath();
                } else {
                    $orgImagePath = $webPath.DIRECTORY_SEPARATOR.$imageObj->getImage();
                }

                if (file_exists($orgImagePath.DIRECTORY_SEPARATOR.$userId.'.jpg')) {
                    exec('convert '.$orgImagePath.DIRECTORY_SEPARATOR.$userId.'_org.jpg'.' -resize '.$request->get('profile_crop_real_w').'x'.$request->get('profile_crop_real_h').'^ -crop '.$request->get('profile_crop_w').'x'.$request->get('profile_crop_h').'+'.($request->get('profile_crop_x') < 0 ? 0 : $request->get('profile_crop_x')).'+'.($request->get('profile_crop_y') < 0 ? 0 : $request->get('profile_crop_y')).' '.$orgImagePath.DIRECTORY_SEPARATOR.$userId.'.jpg');

                    if ($isCompany) {
                        $imageObj = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user'=> $userId));
                        if ($imageObj && $imageObj->getPath()) {
                            $image = CommonManager::getUserLogo($this->container, $imageObj->getPath(), $userId, null, null, true, $isCompany);
                        }
                    } else {
                        $imageObj = $this->getRepository('FaUserBundle:User')->find($userId);
                        if ($imageObj && $imageObj->getImage()) {
                            $image = CommonManager::getUserLogo($this->container, $imageObj->getImage(), $userId, null, null, true);
                        }
                    }
                    exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:send:business-user-for-moderation --userId='.$userId.' >/dev/null &');
                } else {
                    $error = $this->get('translator')->trans('Problem in loading image.');
                }

                return new JsonResponse(array('error' => $error, 'image' => $image));
            }
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
     * Render user image uploader for user.
     *
     * @param integer $userId    User id.
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderUserImageUploaderAction(Request $request)
    {
     if ($request->isXmlHttpRequest()) {
         $userId           = $request->get('userId');
         $isCompany        = $request->get('isCompany');
         $response['html'] =  $this->renderView('FaUserBundle:UserImage:registrationImageUploader.html.twig', array('userId' => $userId, 'isCompany' => $isCompany, 'profileImage' => true));
         return new JsonResponse($response);
     }
    }

    /**
     * Get user image for cropping using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxGetProfileBigImageRegistrationAction(Request $request)
    {
     if ($request->isXmlHttpRequest()) {
      $error        = '';
      $htmlContent  = '';
      $userId       = $request->get('userId');
      $isCompany    = filter_var($request->get('isCompany'), FILTER_VALIDATE_BOOLEAN);
      $webPath      = $this->container->get('kernel')->getRootDir().'/../web';
      $orgImagePath = $webPath.DIRECTORY_SEPARATOR.'uploads/tmp';

       if (file_exists($orgImagePath.DIRECTORY_SEPARATOR.$userId.'_org.jpg')) {
        $imageUrl    = CommonManager::getUserLogoByUserId($this->container, $userId, true, true);
        $htmlContent = $this->renderView('FaUserBundle:UserImage:renderProfileBigImageRegistration.html.twig', array('userId' => $userId, 'isCompany' => $isCompany, 'imageUrl' => $imageUrl));
       } else {
        $error = $this->get('translator')->trans('Problem in loading image.');
       }

       return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent));
     }

     return new Response();
    }

    /**
     * Crop user image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxCropProfileImageRegistrationAction(Request $request)
    {
     if ($request->isXmlHttpRequest()) {
      $error        = '';
      $image        = '';
      $userId       = $request->get('userId');
      $isCompany    = filter_var($request->get('isCompany'), FILTER_VALIDATE_BOOLEAN);
      $webPath      = $this->container->get('kernel')->getRootDir().'/../web';
      $orgImagePath = $webPath.DIRECTORY_SEPARATOR.'uploads/tmp';

       if (file_exists($orgImagePath.DIRECTORY_SEPARATOR.$userId.'.jpg')) {
        exec('convert '.$orgImagePath.DIRECTORY_SEPARATOR.$userId.'_org.jpg'.' -resize '.$request->get('profile_crop_real_w').'x'.$request->get('profile_crop_real_h').'^ -crop '.$request->get('profile_crop_w').'x'.$request->get('profile_crop_h').'+'.($request->get('profile_crop_x') < 0 ? 0 : $request->get('profile_crop_x')).'+'.($request->get('profile_crop_y') < 0 ? 0 : $request->get('profile_crop_y')).' '.$orgImagePath.DIRECTORY_SEPARATOR.$userId.'.jpg');

        if ($isCompany) {
          $image = CommonManager::getUserLogo($this->container, $orgImagePath, $userId, null, null, true, $isCompany);
        } else {
          $image = CommonManager::getUserLogo($this->container, $orgImagePath, $userId, null, null, true);
        }
       } else {
        $error = $this->get('translator')->trans('Problem in loading image.');
       }

       return new JsonResponse(array('error' => $error, 'image' => $image));

     }

     return new Response();
    }

    /**
     * Remove temp user image ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxRemoveTempUserImageAction(Request $request)
    {
        $error            = '';
        $successMsg       = '';
        $privateUserLogo  = '';
        $businessUserLogo = '';
        if ($request->isXmlHttpRequest()) {
           $userId           = $request->get('userId');
           $webPath          = $this->container->get('kernel')->getRootDir().'/../web';
           $privateUserLogo  = CommonManager::getUserLogo($this->container, '', null, null, null, true, false, null, null);
           $businessUserLogo = CommonManager::getUserLogo($this->container, '', null, null, null, true, true, null, null);
           if (file_exists($webPath.'/uploads/tmp/'.$userId.'.jpg')) {
              unlink($webPath.'/uploads/tmp/'.$userId.'.jpg');
              unlink($webPath.'/uploads/tmp/'.$userId.'_org.jpg');
              unlink($webPath.'/uploads/tmp/'.$userId.'_original.jpg');
              $successMsg = $this->get('translator')->trans('Logo has been removed successfully.');
           } else {
             $error = $this->get('translator')->trans('Problem in removing logo.');
           }
        }

        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg, 'privateUserLogo' => $privateUserLogo, 'businessUserLogo' => $businessUserLogo));
    }
}
