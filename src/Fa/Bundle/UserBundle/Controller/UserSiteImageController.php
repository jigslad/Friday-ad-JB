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
use Fa\Bundle\UserBundle\Entity\USerSiteImage;
use Fa\Bundle\UserBundle\Manager\UserSiteImageManager;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Form\UserSiteImageType;

/**
 * This controller is used for shop image.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSiteImageController extends CoreController
{
    /**
     * Show image uploader for user site image.
     *
     * @param integer $userSiteId User site id.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showUserSiteImageUploaderAction($userSiteId = null, Request $request)
    {
        $userSiteImgCount = $this->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImageCount($userSiteId);

        if ($request->get('is_admin')) {
            return $this->render('FaUserBundle:UserSiteImage:showUserSiteImageUploaderAdmin.html.twig', array('imageLimitRemaining' => $this->container->getParameter('fa.image.user.site.upload_limit') - $userSiteImgCount, 'userSiteId' => $userSiteId, 'is_admin' => 1));
        } else {
            return $this->render('FaUserBundle:UserSiteImage:showUserSiteImageUploader.html.twig', array('imageLimitRemaining' => $this->container->getParameter('fa.image.user.site.upload_limit') - $userSiteImgCount, 'userSiteId' => $userSiteId));
        }
    }

    /**
     * Save uploaded image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxSaveUploadedImageAction(Request $request)
    {
        if ($request->ismethod('post')) {
            $error  = '';
            $params = $request->get('fa_user_user_site_image');

            $userSiteId = $params['userSiteId'];
            $userSite   = $this->getRepository('FaUserBundle:UserSite')->find($userSiteId);
            $userSiteImgCount = $this->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImageCount($userSiteId);
            $imageLimit = $this->container->getParameter('fa.image.user.site.upload_limit', 0);

            //validate user if ad object is found
            if ($userSite && $userSite->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if ($this->get('fa.resource.authorization.manager')->isGranted('ajax_ad_image_save_admin')) {
                        $userSiteId = $userSite->getId();
                    } else {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $userSiteImgCount));
                    }
                } elseif ($this->isAuth()) {
                    $user = $this->getSecurityTokenStorage()->getToken()->getUser();
                    if ($user->getId() != $userSite->getUser()->getId()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $userSiteImgCount));
                    } else {
                        $userSiteId = $userSite->getId();
                    }
                } elseif (!$this->isAuth()) {
                    $error = $this->getInvadidMessage();
                    return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $userSiteImgCount));
                }
            } elseif ($this->isAdminLoggedIn() && !$this->get('fa.resource.authorization.manager')->isGranted('ajax_ad_image_save_admin')) {
                $error = $this->getInvadidMessage();
                return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $userSiteImgCount));
            }

            //check for image limit else upload image
            if ($userSiteImgCount >= $imageLimit) {
                $error = $this->get('translator')->trans('Image upload limit exceeded.');
            } else {
                $formManager = $this->get('fa.formmanager');
                $userSiteImage = new UserSiteImage();
                $form    = $formManager->createForm(UserSiteImageType::class, $userSiteImage);
                $formParams = array('user_site' => $userSite);

                $files = $request->files->get('fa_user_user_site_image');
                $formParams['fileData'] = $files['fileData'];
                $form->submit($formParams);

                try {
                    if ($form->isValid()) {
                        $formManager->save($userSiteImage);
                        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:send:business-user-for-moderation --userId='.$userSite->getUser()->getId().' >/dev/null &');
                    } else {
                        $error = $form->getErrorsAsString();
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }

            $userSiteImgCount = $this->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImageCount($userSiteId);

            return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $userSiteImgCount));
        }

        return new Response();
    }

    /**
     * Get user site images based on user site id using ajax.
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

            if ($request->get('is_admin')) {
                $images = $this->renderView('FaUserBundle:UserSiteImage:renderUploadedUserSiteImageAdmin.html.twig', array('userSiteId' => $userSiteId, 'cache' => $cache));
            } else {
                $images = $this->renderView('FaUserBundle:UserSiteImage:renderUploadedUserSiteImage.html.twig', array('userSiteId' => $userSiteId, 'cache' => $cache));
            }

            return new JsonResponse(array('images' => $images));
        }

        return new Response();
    }

    /**
     * Delete user site image using ajax.
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
            $userSiteId = $request->get('userSiteId');
            $userSite   = $this->getRepository('FaUserBundle:UserSite')->find($userSiteId);
            //validate user if ad object is found
            if ($userSite && $userSite->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_delete_ad_images_admin')) {
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

            //remove image
            $imageId     = $request->get('imageId');
            $hash        = $request->get('imageHash');
            $imageRemove = $this->getRepository('FaUserBundle:UserSiteImage')->removeUserSiteImage($userSiteId, $imageId, $hash, $this->container);
            $imageLimit  = $this->container->getParameter('fa.image.user.site.upload_limit', 0);
            $userSiteImgCount = $this->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImageCount($userSiteId);

            if ($request->get('is_admin')) {
                $images = $this->renderView('FaUserBundle:UserSiteImage:renderUploadedUserSiteImageAdmin.html.twig', array('userSiteId' => $userSiteId));
            } else {
                $images = $this->renderView('FaUserBundle:UserSiteImage:renderUploadedUserSiteImage.html.twig', array('userSiteId' => $userSiteId));
            }

            if (!$imageRemove) {
                $error = $this->get('translator')->trans('Problem in deleting image.');
            } else {
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:send:business-user-for-moderation --userId='.$userSite->getUser()->getId().' >/dev/null &');
                $successMsg = $this->get('translator')->trans('Photo has been deleted successfully.');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg,'images' => $images, 'imageLimitRemaining' => $imageLimit - $userSiteImgCount));
        }

        return new Response();
    }

    /**
     * Chnages order of images.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxChangeUserSiteImageOrderAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';
            $userSiteId = $request->get('userSiteId');
            $userSite   = $this->getRepository('FaUserBundle:UserSite')->find($userSiteId);

            //validate user if ad object is found
            if ($userSite && $userSite->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_change_ad_image_order_admin')) {
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

            //update order of images
            $orders = $request->get('orders');
            if ($orders) {
                $orders = json_decode($orders, true);

                try {
                    foreach ($orders as $imageId => $imageOrd) {
                        $imageId      = explode('_', $imageId);
                        if (isset($imageId[1])) {
                            $userSiteImgReorder = $this->getRepository('FaUserBundle:UserSiteImage')->updateOrder($imageId[1], $imageOrd);
                        }
                    }
                    $successMsg = $this->get('translator')->trans('Photos have been reordered successfully.');
                } catch (\Exception $e) {
                    $error = $this->get('translator')->trans('Problem in reordering photos.');
                }
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
     * Get ad's big image using ajax.
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

            //get big image
            $imageId  = $request->get('imageId');
            $hash     = $request->get('imageHash');
            $userSiteImgObj = $this->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImageQueryByUserSiteIdImageIdHash($userSiteId, $imageId, $hash)->getQuery()->getOneOrNullResult();

            if (!$userSiteImgObj) {
                $error = $this->get('translator')->trans('Problem in loading image.');
            } else {
                if ($request->get('is_admin')) {
                    $image = $this->renderView('FaUserBundle:UserSiteImage:renderUserSiteBigImageAdmin.html.twig', array('userSiteImgObj' => $userSiteImgObj));
                } else {
                    $image = $this->renderView('FaUserBundle:UserSiteImage:renderUserSiteBigImage.html.twig', array('userSiteImgObj' => $userSiteImgObj));
                }
            }

            return new JsonResponse(array('error' => $error, 'image' => $image));
        }

        return new Response();
    }

    /**
     * Crop user site image using ajax.
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

            //crop image
            $imageId  = $request->get('imageId');
            $hash     = $request->get('imageHash');
            $userSiteImgObj = $this->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImageQueryByUserSiteIdImageIdHash($userSiteId, $imageId, $hash)->getQuery()->getOneOrNullResult();
            $oldHash  = $userSiteImgObj->getHash();
            $newHash  = CommonManager::generateHash();
            $userSiteImgObj->setHash($newHash);
            $this->getEntityManager()->persist($userSiteImgObj);
            $this->getEntityManager()->flush();
            $imagePath = $this->get('kernel')->getRootDir().'/../web/'.$userSiteImgObj->getPath();
            //rename org image.
            rename($imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$oldHash.'.jpg', $imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'_org.jpg');
            if ($request->get('show_org')) {
                copy($imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'_org.jpg', $imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'.jpg');
            /*exec('convert -rotate '.($request->get('angle').' -resize '.($request->get('scale') * 100).'% '.$imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'_org1.jpg'.' -crop '.$request->get('w').'x'.$request->get('h').'+'.$request->get('x').'+'.$request->get('y').' '.$imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'.jpg'));
             unlink($imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'_org1.jpg');*/
            } else {
                exec('convert -rotate '.($request->get('angle').' -resize '.($request->get('scale') * 100).'% '.$imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$hash.'_800X600.jpg'.' -crop '.$request->get('w').'x'.$request->get('h').'+'.$request->get('x').'+'.$request->get('y').' '.$imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'.jpg'));
            }
            //regenerate images
            $userSiteImageManagerOld = new UserSiteImageManager($this->container, $userSiteId, $oldHash, $imagePath);
            //remove old thumbnails
            $userSiteImageManagerOld->removeImage();

            $userSiteImageManager = new UserSiteImageManager($this->container, $userSiteId, $newHash, $imagePath);
            //create thumbnails
            $userSiteImageManager->createThumbnail();

            //rename org image.
            rename($imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'_org.jpg', $imagePath.DIRECTORY_SEPARATOR.$userSiteId.'_'.$newHash.'.jpg');

            $userSiteImgObj = $this->getRepository('FaUserBundle:UserSiteImage')->getUserSiteImageQueryByUserSiteIdImageIdHash($userSiteId, $imageId, $newHash)->getQuery()->getOneOrNullResult();
            if (!$userSiteImgObj) {
                $error = $this->get('translator')->trans('Problem in croping photo.');
            } else {
                $successMsg = $this->get('translator')->trans('Photo has been cropped successfully.');
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:send:business-user-for-moderation --userId='.$userSite->getUser()->getId().' >/dev/null &');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        }

        return new Response();
    }

    /**
     * Make main photo.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxMakeMainImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';
            $userSiteId = $request->get('userSiteId');
            $userSite   = $this->getRepository('FaUserBundle:UserSite')->find($userSiteId);

            //validate user if ad object is found
            if ($userSite && $userSite->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_change_ad_image_order_admin')) {
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

            //change order of images
            $imageId      = $request->get('imageId');
            $imageOrd     = $request->get('imageOrd');
            $imageNewOrd  = $request->get('imageNewOrd');
            $userSiteImgReorder = $this->getRepository('FaUserBundle:UserSiteImage')->changeOrder($imageId, $userSiteId, $imageOrd, $imageNewOrd);

            if (!$userSiteImgReorder) {
                $error = $this->get('translator')->trans('Problem in reordering photos.');
            } else {
                $successMsg = $this->get('translator')->trans('Photo has been reordered successfully.');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        }

        return new Response();
    }
}
