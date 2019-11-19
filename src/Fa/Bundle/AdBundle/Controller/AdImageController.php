<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\AdBundle\Entity\AdImage;
use Fa\Bundle\AdBundle\Manager\AdImageManager;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Twig\CoreExtension;
use Fa\Bundle\AdBundle\Listener\AdListener;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\AdBundle\Form\AdImageType;

/**
 * This controller is used for ad post management.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdImageController extends CoreController
{
    /**
     * Show image uploader for ad.
     *
     * @param string  $adIdUserId
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showImageUploaderForNoPhotoAdAction($adIdUserId, Request $request)
    {
        $orgAdIdUserId = $adIdUserId;
        $adIdUserId = CommonManager::encryptDecrypt($this->container->getParameter('add_a_photo_encryption_key'), $adIdUserId, 'decrypt');
        $adId = null;
        $userId = null;
        if ($adIdUserId) {
            list($adId, $userId) = explode('||', $adIdUserId);
        }

        $adImages = $this->getRepository('FaAdBundle:AdImage')->getAdImages($adId);
        $adImgCount = count($adImages);
        $adImgHash = '';
        if ($adImgCount) {
            foreach ($adImages as $adImage) {
                $adImgHash .= $adImage->getHash();
            }
            $adImgHash = md5($adImgHash);
        }

        //validate active ad
        $adObj = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId, 'status' => EntityRepository::AD_STATUS_LIVE_ID));
        if (!$adObj) {
            return $this->handleMessage($this->getInvadidMessage(), 'fa_frontend_homepage', array(), 'error');
        }

        //validate user
        $userObj = ($adObj->getUser() ? $adObj->getUser() : null);
        if (!$userObj) {
            return $this->handleMessage($this->getInvadidMessage(), 'fa_frontend_homepage', array(), 'error');
        }

        //check ad owner and user id
        if ($userObj->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID || $userObj->getId() != $userId) {
            return $this->handleMessage($this->getInvadidMessage(), 'fa_frontend_homepage', array(), 'error');
        }

        $categoryId = $adObj->getCategory()->getId();
        $categoryPath = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container));
        $vertical = CommonManager::getCategoryClassNameById($categoryPath[0]);
        $paaFieldRule = $this->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRuleArrayByCategoryAncestorForOneField($categoryId, 227, $this->container);

        $parameters = array(
            'adId' => $adId,
            'userId' => $userId,
            'vertical' => $vertical,
            'adPaaFieldRule' => (isset($paaFieldRule[0]) ? $paaFieldRule[0] : array()),
            'adIdUserId' => $orgAdIdUserId,
            'adImgHash' => $adImgHash,
        );

        return $this->render('FaAdBundle:AdImage:showImageUploaderForNoPhotoAds.html.twig', $parameters);
    }

    /**
     * Show image uploader for ad.
     *
     * @param integer $adId
     * @param string  $vertical
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showImageUploaderAction($adId = null, $vertical = 'for_sale', $admin_ad_counter = null, $formName = null, $maxValue = null, $userId = null, $from = null, Request $request)
    {
        if (!$adId) {
            if ($request->get('is_admin')) {
                if (!$this->container->get('session')->has('admin_ad_id_'.$admin_ad_counter)) {
                    $this->container->get('session')->set('admin_ad_id_'.$admin_ad_counter, CommonManager::generateHash());
                }

                $adId = $this->container->get('session')->get('admin_ad_id_'.$admin_ad_counter);
            } else {
                if (!$this->container->get('session')->has('paa_image_id') && $request->get('is_paalite')==1) {
                    $this->container->get('session')->set('paa_image_id', CommonManager::generateHash());
                } elseif (!$this->container->get('session')->has('ad_id')) {
                    $this->container->get('session')->set('ad_id', CommonManager::generateHash());
                }

                if ($this->container->get('session')->has('paa_image_id') && $request->get('is_paalite')==1) {
                    $adId = $this->container->get('session')->get('paa_image_id');
                } else {
                    $adId = $this->container->get('session')->get('ad_id');
                }
            }
        }
        $adImgCount = $this->getRepository('FaAdBundle:AdImage')->getAdImageCount($adId);

        if ($request->get('is_admin')) {
            return $this->render('FaAdBundle:AdImage:showImageUploaderAdmin.html.twig', array('imageLimitRemaining' => (($maxValue ? $maxValue : $this->container->getParameter('fa.image.'.$vertical.'_upload_limit')) - $adImgCount), 'adId' => $adId, 'userId' => $userId, 'vertical' => $vertical, 'is_admin' => 1, 'adImgCount' => $adImgCount, 'formName' => $formName, 'maxValue' => $maxValue, 'from' => $from));
        } elseif ($request->get('is_paalite')) {
            return $this->render('FaAdBundle:AdImage:showImageUploaderPaaLite.html.twig', array('imageLimitRemaining' => (($maxValue ? $maxValue : $this->container->getParameter('fa.image.'.$vertical.'_upload_limit')) - $adImgCount), 'adId' => $adId, 'userId' => $userId, 'vertical' => $vertical, 'adImgCount' => $adImgCount, 'formName' => $formName, 'maxValue' => $maxValue, 'from' => $from));
        } else {
            return $this->render('FaAdBundle:AdImage:showImageUploader.html.twig', array('imageLimitRemaining' => (($maxValue ? $maxValue : $this->container->getParameter('fa.image.'.$vertical.'_upload_limit')) - $adImgCount), 'adId' => $adId, 'userId' => $userId, 'vertical' => $vertical, 'adImgCount' => $adImgCount, 'formName' => $formName, 'maxValue' => $maxValue, 'from' => $from));
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
            $params = $request->get('fa_paa_image');

            $adId = trim($params['ad_id']);
            $userId = (isset($params['user_id']) ? trim($params['user_id']) : null);
            if ($adId) {
                $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);
                $adImgCount = $this->getRepository('FaAdBundle:AdImage')->getAdImageCount($adId);
                $imageLimit = ($params['maxValue'] ? $params['maxValue'] : $this->container->getParameter('fa.image.'.$params['vertical'].'_upload_limit', 0));

                //validate user if ad object is found
                if ($ad && $ad->getId()) {
                    if ($this->isAdminLoggedIn()) {
                        if ($this->get('fa.resource.authorization.manager')->isGranted('ajax_ad_image_save_admin')) {
                            $adId = $ad->getId();
                        } else {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $adImgCount, 'adImgCount' => $adImgCount));
                        }
                    } elseif ($user = $this->checkIsUserIsValidToUploadImage($userId)) {
                        if ($user->getId() != $ad->getUser()->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $adImgCount, 'adImgCount' => $adImgCount));
                        } else {
                            $adId = $ad->getId();
                        }
                    } elseif (!$this->isAuth()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $adImgCount, 'adImgCount' => $adImgCount));
                    }
                } elseif ($this->isAdminLoggedIn() && !$this->get('fa.resource.authorization.manager')->isGranted('ajax_ad_image_save_admin')) {
                    $error = $this->getInvadidMessage();
                    return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $adImgCount, 'adImgCount' => $adImgCount));
                }

                //check for image limit else upload image
                if ($adImgCount >= $imageLimit) {
                    $error = $this->get('translator')->trans('Image upload limit exceeded.');
                } else {
                    $formManager = $this->get('fa.formmanager');
                    $adImage = new AdImage();
                    $form    = $formManager->createForm(AdImageType::class, $adImage);

                    if (preg_match('/^\d+$/', $adId)) {
                        $formParams = array('ad' => $ad);
                    } else {
                        $formParams = array('session_id' => $adId);
                    }

                    $files = $request->files->get('fa_paa_image');
                    $formParams['fileData'] = $files['fileData'];
                    $form->submit($formParams);

                    try {
                        if ($form->isValid()) {
                            $isAdminUser = $this->isAdminLoggedIn() ? true : false;
                            $this->getRepository('FaAdBundle:AdImage')->saveImage($adImage, $this->container, $isAdminUser);
                        } else {
                            $error = $form->getErrors(true, false);
                        }
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }
                }

                $adImgCount = $this->getRepository('FaAdBundle:AdImage')->getAdImageCount($adId);

                return new JsonResponse(array('error' => $error, 'imageLimitRemaining' => $imageLimit - $adImgCount, 'adImgCount' => $adImgCount));
            }
        }

        return new Response();
    }

    /**
     * Get ad images based on ad id using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxRenderUploadedImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $adId     = $request->get('adId');
            $vertical = $request->get('vertical');
            $cache    = $request->get('cache');

            if ($request->get('is_admin')) {
                $images = $this->renderView('FaAdBundle:AdImage:renderUploadedImageAdmin.html.twig', array('adId' => $adId, 'vertical' => $vertical, 'cache' => $cache));
            } else {
                $images = $this->renderView('FaAdBundle:AdImage:renderUploadedImage.html.twig', array('adId' => $adId, 'vertical' => $vertical, 'cache' => $cache));
            }

            return new JsonResponse(array('images' => $images));
        }

        return new Response();
    }

    /**
     * Delete ad image using ajax.
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
            $adId       = $request->get('adId');
            $maxValue   = $request->get('maxValue');
            $userId   = $request->get('userId');
            $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);
            //validate user if ad object is found
            if ($ad && $ad->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_delete_ad_images_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    }
                } else {
                    $user = $this->checkIsUserIsValidToUploadImage($userId);
                    if (!$user) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    } else {
                        if ($user->getId() != $ad->getUser()->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                        }
                    }
                }
            }

            //remove image
            $imageId     = $request->get('imageId');
            $hash        = $request->get('imageHash');
            $vertical    = $request->get('vertical');
            $imageRemove = $this->getRepository('FaAdBundle:AdImage')->removeAdImage($adId, $imageId, $hash, $this->container);
            $imageLimit  = ($maxValue ? $maxValue : $this->container->getParameter('fa.image.'.$vertical.'_upload_limit', 0));
            $adImgCount  = $this->getRepository('FaAdBundle:AdImage')->getAdImageCount($adId);

            if ($request->get('is_admin')) {
                $images = $this->renderView('FaAdBundle:AdImage:renderUploadedImageAdmin.html.twig', array('adId' => $adId, 'vertical' => $vertical));
            } else {
                $images = $this->renderView('FaAdBundle:AdImage:renderUploadedImage.html.twig', array('adId' => $adId, 'vertical' => $vertical));
            }

            if (!$imageRemove) {
                $error = $this->get('translator')->trans('Problem in deleting ad image.');
            } else {
                $successMsg = $this->get('translator')->trans('Photo has been deleted successfully.');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg,'images' => $images, 'imageLimitRemaining' => $imageLimit - $adImgCount, 'adImgCount' => $adImgCount));
        }

        return new Response();
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
            $adId     = $request->get('adId');
            $vertical = $request->get('vertical');
            $userId   = $request->get('userId');
            $from     = $request->get('from');
            $ad       = $this->getRepository('FaAdBundle:Ad')->find($adId);

            //validate user if ad object is found
            if ($ad && $ad->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_get_big_ad_image_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'image' => $image));
                    }
                } else {
                    $user = $this->checkIsUserIsValidToUploadImage($userId);
                    if (!$user) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'image' => $image));
                    } else {
                        if ($user->getId() != $ad->getUser()->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'image' => $image));
                        }
                    }
                }
            }

            //get big image
            $imageId  = $request->get('imageId');
            $hash     = $request->get('imageHash');
            $adImgObj = $this->getRepository('FaAdBundle:AdImage')->getAdImageQueryByAdIdImageIdHash($adId, $imageId, $hash)->getQuery()->getOneOrNullResult();

            if (!$adImgObj) {
                $error = $this->get('translator')->trans('Problem in loading ad image.');
            } else {
                if ($request->get('is_admin')) {
                    $image = $this->renderView('FaAdBundle:AdImage:renderBigImageAdmin.html.twig', array('adImgObj' => $adImgObj, 'vertical' => $vertical, 'from' => $from));
                } else {
                    $image = $this->renderView('FaAdBundle:AdImage:renderBigImage.html.twig', array('adImgObj' => $adImgObj, 'vertical' => $vertical, 'from' => $from));
                }
            }

            return new JsonResponse(array('error' => $error, 'image' => $image));
        }

        return new Response();
    }

    /**
     * Crop ad image using ajax.
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
            $adId       = $request->get('adId');
            $userId     = $request->get('userId');
            $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);

            //validate user if ad object is found
            if ($ad && $ad->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_crop_ad_image_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    }
                } else {
                    $user = $this->checkIsUserIsValidToUploadImage($userId);
                    if (!$user) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    } else {
                        if ($user->getId() != $ad->getUser()->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                        }
                    }
                }
            }

            //crop image
            $imageId  = $request->get('imageId');
            $hash     = $request->get('imageHash');
            $adImgObj = $this->getRepository('FaAdBundle:AdImage')->getAdImageQueryByAdIdImageIdHash($adId, $imageId, $hash)->getQuery()->getOneOrNullResult();
            if (empty($adImgObj)) {
                $error = $this->getAdNotFoundMessage();
                return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
            }
            $oldHash  = $adImgObj->getHash();
            $imagePath = $this->get('kernel')->getRootDir().'/../web/'.$adImgObj->getPath();

            if ($adImgObj->getAws() == 1) {
                if ($adImgObj->getImageName() != '') {
                    $oldAwsUrl = CommonManager::getAdImageUrl($this->container, $adId, $adImgObj->getPath(), $adImgObj->getHash(), null, 1, $adImgObj->getImageName());
                }
                $OldOrgimage = $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'.jpg';
                
                if (isset($oldAwsUrl)) {
                    $this->writeDataFromURL($oldAwsUrl, $OldOrgimage);
                }
            } else {
                $OldOrgimage = $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'.jpg';
            }

            $newHash  = CommonManager::generateHash();
            $adImgObj->setHash($newHash);
            $adImgObj->setAws(0);
            $this->getEntityManager()->persist($adImgObj);
            $this->getEntityManager()->flush();

            //rename org image.
            rename($OldOrgimage, $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'_org.jpg');
            exec('convert -rotate '.($request->get('angle').' -resize '.($request->get('scale') * 100).'% '.$imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'_org.jpg'.' -crop '.$request->get('w').'x'.$request->get('h').'+'.$request->get('x').'+'.$request->get('y').' '.$imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'.jpg'));
            //regenerate images
            $adImageManagerOld = new AdImageManager($this->container, $adId, $oldHash, $imagePath, $adImgObj->getImageName(), $adImgObj->getPath());
            //remove old thumbnails
            $adImageManagerOld->removeImage();

            $adImageManager = new AdImageManager($this->container, $adId, $newHash, $imagePath);
            //create thumbnails
            $adImageManager->createThumbnail(false); 
            //create cope thumbnails
            $adImageManager->createCropedThumbnail();

            //rename org image.
            //rename($imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'_org.jpg', $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'.jpg');
            exec('convert '.$imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'_org.jpg'.' -rotate '.$request->get('angle').' '.$imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'.jpg');

            $adImgObj = $this->getRepository('FaAdBundle:AdImage')->getAdImageQueryByAdIdImageIdHash($adId, $imageId, $newHash)->getQuery()->getOneOrNullResult();
            if (!$adImgObj && $adImgObj->getAd()) {
                $adListner = new AdListener($this->container);
                $adListner->handleSolr($adImgObj->getAd());
            }

            if (!$adImgObj) {
                $error = $this->get('translator')->trans('Problem in croping photo.');
            } else {
                $successMsg = $this->get('translator')->trans('Photo has been edited successfully.');
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
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
    public function ajaxChangeAdImageOrderAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';
            $adId       = $request->get('adId');
            $userId     = $request->get('userId');
            $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);

            //validate user if ad object is found
            if ($ad && $ad->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_change_ad_image_order_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    }
                } else {
                    $user = $this->checkIsUserIsValidToUploadImage($userId);
                    if (!$user) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    } else {
                        if ($user->getId() != $ad->getUser()->getId()) {
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
                            $adImgReorder = $this->getRepository('FaAdBundle:AdImage')->updateOrder($imageId[1], $imageOrd);
                        }
                    }
                    $this->getRepository('FaAdBundle:AdImage')->updateImageToSolr($ad, $this->container);
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
            $adId       = $request->get('adId');
            $userId     = $request->get('userId');
            $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);

            //validate user if ad object is found
            if ($ad && $ad->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_change_ad_image_order_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    }
                } else {
                    $user = $this->checkIsUserIsValidToUploadImage($userId);
                    if (!$user) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    } else {
                        if ($user->getId() != $ad->getUser()->getId()) {
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
            $adImgReorder = $this->getRepository('FaAdBundle:AdImage')->changeOrder($imageId, $adId, $imageOrd, $imageNewOrd);

            if (!$adImgReorder) {
                $error = $this->get('translator')->trans('Problem in reordering photos.');
            } else {
                $this->getRepository('FaAdBundle:AdImage')->updateImageToSolr($ad, $this->container);
                $successMsg = $this->get('translator')->trans('Photo has been reordered successfully.');
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
     * GetAdNotFoundMessage.
     *
     * @return string
     */
    private function getAdNotFoundMessage()
    {
        return $this->get('translator')->trans('Advert id not found.');
    }

    /**
     * Reset ad image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxResetImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $image      = '';
            $error      = '';
            $successMsg = '';
            $adId       = $request->get('adId');
            $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);
            //validate user if ad object is found
            if ($ad && $ad->getId()) {
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
                        if ($user->getId() != $ad->getUser()->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                        }
                    }
                }
            }

            //remove image
            $imageId   = $request->get('imageId');
            $hash      = $request->get('imageHash');
            $vertical  = $request->get('vertical');
            $webPath   = $this->container->get('kernel')->getRootDir().'/../web';
            $imagePath = $this->container->getParameter('fa.ad.image.tmp.dir');

            if ($imageId) {
                $adImgObj = $this->getRepository('FaAdBundle:AdImage')->find($imageId);
                if ($adImgObj->getAd()) {
                    $imagePath = $this->container->getParameter('fa.ad.image.dir').'/'.CommonManager::getGroupDirNameById($adId);
                }
            }

            $orgImagePath      = $webPath.DIRECTORY_SEPARATOR.$imagePath;
            $objAdImageManager = new AdImageManager($this->container, $adId, $hash, $orgImagePath);
            $objAdImageManager->removeImage(true);
            $objAdImageManager->saveOriginalJpgImage($adId.'_'.$hash.'.jpg', true);
            $objAdImageManager->createThumbnail(false);

            if ($request->get('is_admin')) {
                $orgImageUrl = $this->generateUrl('ajax_get_big_ad_image_admin', array('adId' => $adId, 'imageId' => $imageId, 'imageHash' => $hash, 'vertical' => $vertical, 'is_admin' => $request->get('is_admin'), 'show_org' => true));
            } else {
                $orgImageUrl = $this->generateUrl('ajax_get_big_ad_image', array('adId' => $adId, 'imageId' => $imageId, 'imageHash' => $hash, 'vertical' => $vertical, 'show_org' => true));
            }

            return new JsonResponse(array('orgImgUrl' => $orgImageUrl));
        }

        return new Response();
    }

    /**
     * Rotate ad image using ajax.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxRotateImageAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';
            $adId       = $request->get('adId');
            $size       = $request->get('size');
            $ad         = $this->getRepository('FaAdBundle:Ad')->find($adId);

            //validate user if ad object is found
            if ($ad && $ad->getId()) {
                if ($this->isAdminLoggedIn()) {
                    if (!$this->get('fa.resource.authorization.manager')->isGranted('ajax_rotate_ad_image_admin')) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    }
                } else {
                    if (!$this->isAuth()) {
                        $error = $this->getInvadidMessage();
                        return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                    } else {
                        $user = $this->getSecurityTokenStorage()->getToken()->getUser();
                        if ($user->getId() != $ad->getUser()->getId()) {
                            $error = $this->getInvadidMessage();
                            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
                        }
                    }
                }
            }

            //rotate image
            $hash     = $request->get('imageHash');
            $adImgObj = $this->getRepository('FaAdBundle:AdImage')->getAdImageQueryByAdIdImageHash($adId, $hash)->getQuery()->getOneOrNullResult();
            $oldHash  = $adImgObj->getHash();
            $imagePath = $this->get('kernel')->getRootDir().'/../web/'.$adImgObj->getPath();
            $thumbSize = $this->container->getParameter('fa.image.thumb_size');
            $thumbSize = array_map('strtoupper', $thumbSize);

            if ($adImgObj->getAws() == 1) {
                if ($adImgObj->getImageName() != '') {
                    $oldAwsUrl = CommonManager::getAdImageUrl($this->container, $adId, $adImgObj->getPath(), $adImgObj->getHash(), null, 1, $adImgObj->getImageName());
                }
                $OldOrgimage = $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'.jpg';
                $this->writeDataFromURL($oldAwsUrl, $OldOrgimage);

                foreach ($thumbSize as $d) {
                    if ($adImgObj->getImageName() != '') {
                        $oldAwsUrl = CommonManager::getAdImageUrl($this->container, $adId, $adImgObj->getPath(), $adImgObj->getHash(), $d, 1, $adImgObj->getImageName());
                    }
                    $OldOrgimage = $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'_'.$d.'.jpg';
                    $this->writeDataFromURL($oldAwsUrl, $OldOrgimage);
                }
            } else {
                $OldOrgimage = $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'.jpg';
            }

            $newHash  = CommonManager::generateHash();
            $adImgObj->setAws(0);
            $adImgObj->setHash($newHash);
            $this->getEntityManager()->persist($adImgObj);
            $this->getEntityManager()->flush();

            //rotate image.
            if ($size == '800X600') {
                exec('convert '.$imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'_'.$size.'.jpg'.' -rotate 90 '.$imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'_'.$size.'.jpg');
            } else {
                exec('convert '.$imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'.jpg'.' -rotate 90 '.$imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'.jpg');
            }

            if (!$adImgObj->getAws()) {
                if (is_array($thumbSize)) {
                    if (file_exists($imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'.jpg')) {
                        rename($imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'.jpg', $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'.jpg');
                    }
                    foreach ($thumbSize as $d) {
                        if (file_exists($imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'_'.$d.'.jpg')) {
                            rename($imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$oldHash.'_'.$d.'.jpg', $imagePath.DIRECTORY_SEPARATOR.$adId.'_'.$newHash.'_'.$d.'.jpg');
                        }
                    }
                }
            } elseif ($adImgObj->getAws() == 1) {
                $adImageManagerOld = new AdImageManager($this->container, $adId, $oldHash, $imagePath, $adImgObj->getImageName(), $adImgObj->getPath());
                //remove old thumbnails
                $adImageManagerOld->removeFromAmazoneS3();

                $adImageManager = new AdImageManager($this->container, $adId, $newHash, $imagePath, $adImgObj->getImageName(), $adImgObj->getPath());
                $adImageManager->uploadImagesToS3($adImgObj);
            }

            if ($size != '800X600') {
                $size = null;
            }
            $adImages = $this->getRepository('FaAdBundle:AdImage')->getAdImages($adId, 1);
            $adImagesArray = array();

            if ($adImgObj && $adImgObj->getAd()) {
                foreach ($adImages as $adImage) {
                    $adImagesArray[] = array(
                        'path' => $adImage->getPath(),
                        'hash' => $adImage->getHash(),
                        'ord' => $adImage->getOrd(),
                        'aws' => $adImage->getAws(),
                        'image_name' => $adImage->getImageName(),
                        'url' => CommonManager::getAdImageUrl($this->container, $adId, $adImage->getPath(), $adImage->getHash(), null, $adImage->getAws(), $adImage->getImageName()),
                    );
                }
                $adListner = new AdListener($this->container);
                $adListner->handleSolr($adImgObj->getAd());
            }

            $imageUrl = CommonManager::getAdImageUrl($this->container, $adId, $adImgObj->getPath(), $adImgObj->getHash(), $size, $adImgObj->getAws(), $adImgObj->getImageName());
            $successMsg = $this->get('translator')->trans('Photo has been rotated successfully.');

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg,'newHash' => $newHash, 'ord' => $adImgObj->getOrd(), 'adImagesArray' => $adImagesArray, 'imageUrl' => $imageUrl.'?'.time()));
        }

        return new Response();
    }

    /**
     * Check is valid user to upload image to ad
     *
     * @param integer $userId User id
     *
     * @return mixed|
     */
    private function checkIsUserIsValidToUploadImage($userId)
    {
        if ($this->isAuth()) {
            return $this->getSecurityTokenStorage()->getToken()->getUser();
            ;
        }
        if ($userId) {
            $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('id' => $userId, 'status' => EntityRepository::USER_STATUS_ACTIVE_ID));
            if ($user) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Ajax validate no image ad uploads.
     *
     * @param string  $adIdUserId
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ajaxValidateImageUploaderForNoPhotoAdAction($adIdUserId, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $error      = '';
            $successMsg = '';
            $adIdUserId = CommonManager::encryptDecrypt($this->container->getParameter('add_a_photo_encryption_key'), $adIdUserId, 'decrypt');
            $adImgHashForm = $request->get('adImgHash');
            $adId = null;
            $userId = null;
            if ($adIdUserId) {
                list($adId, $userId) = explode('||', $adIdUserId);
            }

            //validate active ad
            $adObj = $this->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId, 'status' => EntityRepository::AD_STATUS_LIVE_ID));
            if (!$adObj) {
                $error = $this->getInvadidMessage();
            }

            //validate user
            $userObj = ($adObj->getUser() ? $adObj->getUser() : null);
            if (!$userObj) {
                $error = $this->getInvadidMessage();
            }

            //check ad owner and user id
            if ($userObj->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID || $userObj->getId() != $userId) {
                $error = $this->getInvadidMessage();
            }

            $categoryId = $adObj->getCategory()->getId();
            $categoryPath = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container));
            $vertical = CommonManager::getCategoryClassNameById($categoryPath[0]);
            $paaFieldRule = $this->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRuleArrayByCategoryAncestorForOneField($categoryId, 227, $this->container);

            $adImages = $this->getRepository('FaAdBundle:AdImage')->getAdImages($adId);
            $adImgCount = count($adImages);
            $adImgHash = '';
            if ($adImgCount) {
                foreach ($adImages as $adImage) {
                    $adImgHash .= $adImage->getHash();
                }
                $adImgHash = md5($adImgHash);
            }

            if (isset($paaFieldRule[0]['min_value']) && $paaFieldRule[0]['min_value'] && $paaFieldRule[0]['min_value'] > $adImgCount) {
                if (isset($paaFieldRule[0]['error_text']) && $paaFieldRule[0]['error_text']) {
                    $error = $paaFieldRule[0]['error_text'];
                } else {
                    $error = $this->get('translator')->trans('Please enter minimum %min_image%.', array('%min_image%' => $paaFieldRule[0]['min_value']));
                }
            } elseif (!$error) {
                if ($adImgHashForm == $adImgHash) {
                    $error = $this->get('translator')->trans('Please upload or change any images.');
                } else {
                    $successMsg = 'success';
                    $this->getRepository('FaAdBundle:AdModerate')->sendAdForModeration($adObj, $this->container);
                }
            }

            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg, 'adImgHash' => $adImgHash));
        }

        return new Response();
    }
}
