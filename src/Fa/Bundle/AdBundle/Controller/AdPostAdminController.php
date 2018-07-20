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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdForSale;
// use Fa\Bundle\AdBundle\Form\AdType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\AdBundle\Repository\AdForSaleRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\AdBundle\Form\AdPostCategorySelectAdminType;
use Fa\Bundle\AdBundle\Form\AdUserSearchType;
use Fa\Bundle\AdBundle\Form\AdPostAdultAdminType;
use Fa\Bundle\AdBundle\Form\AdPostForSaleAdminType;
use Fa\Bundle\AdBundle\Form\AdPostServicesAdminType;
use Fa\Bundle\AdBundle\Form\AdPostMotorsAdminType;

/**
 * This controller is used for ad post management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdPostAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * GetTableName.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'ad';
    }

    /**
     * Displays a form to create a new record.
     *
     * @param integer $user_id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction($user_id, Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        if ($request->get('admin_ad_counter')) {
            $adminAdCounter = $request->get('admin_ad_counter');
        } else {
            $adminAdCounter = CommonManager::getAdminAdCounter($this->container);
        }

        // Generate session id for ad id
        if (!$this->container->get('session')->has('admin_ad_id_'.$adminAdCounter)) {
            $this->container->get('session')->set('admin_ad_id_'.$adminAdCounter, CommonManager::generateHash());
        }

        $user = null;
        if ($user_id) {
            $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('id' => $user_id, 'is_half_account' => '0'));
        }

        if (!$user && $user_id != 'no_user') {
            throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user'));
        }

        if ($user && $user->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
            $urlToRedirect = CommonManager::getAdminCancelUrl($this->container);
            $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('You can not post an ad, as user is not active!'), 'error');
            return $this->redirect($urlToRedirect);
        }

        $loggedinUser = $this->getLoggedInUser();
        $loggedinUserRole = $this->getRepository('FaUserBundle:User')->getUserRole($loggedinUser->getId(), $this->container);
        if (!$user && $user_id == 'no_user' && $loggedinUserRole == RoleRepository::ROLE_ADMIN_HIDE_SKIP_PAYMENT) {
            return $this->handleMessage($this->get('translator')->trans('You can not add detached ad.'), 'ad_post_search_user_admin', array(), 'error');
        }

        $formManager = $this->get('fa.formmanager');
        $entity      = new Ad();
        $form        = $formManager->createForm(AdPostCategorySelectAdminType::class, $entity, array('action' => $this->generateUrl('ad_post_new_admin', array('user_id' => $user_id))));
        $parameters  = array(
                           'entity'  => $entity,
                           'form'    => $form->createView(),
                           'heading' => $this->get('translator')->trans('New Ad'),
                           'adminAdCounter' => $adminAdCounter,
                       );

        return $this->render('FaAdBundle:AdPostAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new record.
     *
     * @param integer $user_id
     * @param integer $admin_ad_counter
     * @param integer $category_id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newFromCategoryAction($user_id, $admin_ad_counter, $category_id, Request $request)
    {
        $user = null;
        if ($user_id) {
            $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('id' => $user_id, 'is_half_account' => '0'));
        }

        if (!$user && $user_id != 'no_user') {
            throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user'));
        }

        $formName    = $this->getFormName($category_id);
        $fqn = $this->getFQNForForms($formName);
        $entity      = new Ad();
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm($fqn, $entity, array('action' => $this->generateUrl('ad_post_create_admin', array('user_id' => $user_id, 'admin_ad_counter' => $admin_ad_counter, 'category_id' => $category_id))));

        if ($this->container->get('session')->has('reg_no')) {
            $form->get('reg_no')->setData($this->container->get('session')->get('reg_no'));
            $this->container->get('session')->remove('reg_no');
        }

        $categoryDimensionId = $this->getEntityManager()->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategoryHierarchy($category_id, 'Brand', $this->container);

        $parameters = array(
                          'entity'  => $entity,
                          'form'    => $form->createView(),
                          'heading' => $this->get('translator')->trans('New Ad'),
                          'categoryDimensionId' => $categoryDimensionId,
                      );

        return $this->render($this->getTemplateName($category_id), $parameters);
    }

    /**
     * Creates a new record.
     *
     * @param integer $user_id
     * @param integer $admin_ad_counter
     * @param integer $category_id
     * @param Request $request
     *
     * @throws createNotFoundException
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction($user_id, $admin_ad_counter, $category_id, Request $request)
    {
        $user     = null;
        $category = null;

        if ($user_id) {
            $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('id' => $user_id, 'is_half_account' => '0'));
        }

        if ($category_id) {
            $category     = $this->getRepository('FaEntityBundle:Category')->find($category_id);
            $categoryName = $this->getRootCategoryName($category_id);
        }

        try {
            if (!$user && $user_id != 'no_user') {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user'));
            }

            if (!$category) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find category'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'ad_post_new_admin', array('user_id' => $user_id));
        }

        $formName    = $this->getFormName($category_id);
        $fqn = $this->getFQNForForms($formName);
        $formData    = $request->get($formName);
        $formManager = $this->get('fa.formmanager');
        $entity      = new Ad();
        $options     = array(
                           'action' => $this->generateUrl('ad_post_create_admin', array('user_id' => $user_id, 'admin_ad_counter' => $admin_ad_counter, 'category_id' => $category_id)),
                           'method' => 'POST'
                       );
        
        $form = $formManager->createForm($fqn, $entity, $options);
        if ($formManager->isValid($form)) {
            // save ad
            $adPostManager        = $this->get('fa_ad.manager.ad_post');
            $data                 = $request->get('fa_paa_'.$categoryName.'_admin');
            $data['ad_status_id'] = EntityRepository::AD_STATUS_DRAFT_ID;
            $ad = $adPostManager->saveAd($data, null, true, true);

            // save paypal email address.
            $paymentMethodId = isset($data['payment_method_id']) ? $data['payment_method_id'] : null;
            $paypalEmail     = isset($data['paypal_email']) ? $data['paypal_email'] : null;
            if ($paypalEmail && in_array($paymentMethodId, array(PaymentRepository::PAYMENT_METHOD_PAYPAL_ID, PaymentRepository::PAYMENT_METHOD_PAYPAL_OR_CASH_ID))) {
                if ($ad) {
                    $userObj = $ad->getUser();
                    $userObj->setPaypalEmail($paypalEmail);
                    $userObj->setIsPaypalVefiried(1);
                    $this->getEntityManager()->persist($userObj);
                    $this->getEntityManager()->flush($userObj);
                }
            }
            if (isset($formData['publish'])) {
                return $this->handleMessage($this->get('translator')->trans('Record has been added successfully.', array(), 'success'), 'ad_package_purchase_admin', array('adId' => $ad->getId()));
            } else {
                if ($ad->getId()) {
                    return $this->handleMessage($this->get('translator')->trans('Record has been added successfully.', array(), 'success'), 'ad_admin', array('fa_ad_ad_search_admin' => array('ad__id' => $ad->getId())));
                } elseif (isset($formData['return_url']) && $formData['return_url']) {
                    $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Record has been added successfully.'), 'success');
                    return $this->redirect($formData['return_url']);
                }
            }
        }

        $categoryDimensionId = $this->getEntityManager()->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategoryHierarchy($category_id, 'Brand', $this->container);
        $parameters = array(
                          'entity'  => $entity,
                          'form'    => $form->createView(),
                          'heading' => $this->get('translator')->trans('New Ad'),
                          'categoryDimensionId' => $categoryDimensionId,
                      );

        return $this->render($this->getTemplateName($category_id), $parameters);
    }

    /**
     * Displays a form to create a new record.
     *
     * @param integer $id
     * @param Request $request
     *
     * @throws createNotFoundException
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($id, Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository('FaAdBundle:Ad')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'ad_admin');
        }

        if ($entity && $entity->getUser() && $entity->getUser()->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
            $urlToRedirect = CommonManager::getAdminCancelUrl($this->container);
            $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('You can not edit an ad, as user is not active!'), 'error');
            return $this->redirect($urlToRedirect);
        }
        
        $formName = $this->getFormName($entity->getCategory()->getId());
        $options  =  array(
                         'action' => $this->generateUrl('ad_post_update_admin', array('id' => $entity->getId())),
                         'method' => 'PUT'
                     );

        $fqn = $this->getFQNForForms($formName);
        $form        = $formManager->createForm($fqn, $entity, $options);
        $parameters  = array(
                           'entity'  => $entity,
                           'form'    => $form->createView(),
                           'heading' => $this->get('translator')->trans('Edit Ad'),
                       );

        return $this->render($this->getTemplateName($entity->getCategory()->getId()), $parameters);
    }

    /**
     * Edits an existing record.
     *
     * @param Request $request
     * @param integer $id
     *
     * @throws createNotFoundException
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl     = CommonManager::getAdminBackUrl($this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository('FaAdBundle:Ad')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'ad_admin');
        }

        $formName = $this->getFormName($entity->getCategory()->getId());
        $options  =  array(
                         'action' => $this->generateUrl('ad_post_update_admin', array('id' => $entity->getId())),
                         'method' => 'PUT'
                     );

        $formData = $request->get($formName);
        $fqn = $this->getFQNForForms($formName);
        $form     = $formManager->createForm($fqn, $entity, $options);

        if ($formManager->isValid($form)) {
            //$this->getEntityManager()->flush();

            if (isset($formData['publish'])) {
                return $this->handleMessage($this->get('translator')->trans('Record has been updated successfully.', array(), 'success'), 'ad_package_purchase_admin', array('adId' => $id));
            } else {
                /*
                if ($entity->getId()) {
                    return $this->handleMessage($this->get('translator')->trans('Record has been added successfully.', array(), 'success'), 'ad_admin', array('fa_ad_ad_search_admin' => array('ad__id' => $entity->getId())));
                } elseif (isset($formData['return_url']) && $formData['return_url']) {
                    $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Record has been updated successfully.'), 'success');
                    return $this->redirect($formData['return_url']);
                }
                return $this->handleMessage($this->get('translator')->trans('Record has been updated successfully.', array(), 'success'), 'ad_admin', array('fa_ad_ad_search_admin' => array('ad__id' => $id)));
                */
                $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Record has been updated successfully.'), 'success');
                //if(empty($backUrl))
                $backUrl = $this->generateUrl('ad_admin').'?fa_ad_ad_search_admin[ad__id]='.$id;

                return $this->redirect($backUrl);
            }
        }

        $parameters = array(
                          'entity'  => $entity,
                          'form'    => $form->createView(),
                          'heading' => $this->get('translator')->trans('Edit Ad'),
                      );

        return $this->render($this->getTemplateName($entity->getCategory()->getId()), $parameters);
    }

    /**
     * Find user.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUserAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdUserSearchType::class, null, array('action' => $this->generateUrl('user_admin'), 'method' => 'GET'));

        $parameters = array(
            'heading'     => $this->get('translator')->trans('Search User'),
            'form'        => $form->createView(),
        );

        return $this->render('FaAdBundle:AdPostAdmin:search_user.html.twig', $parameters);
    }

    /**
     * Get category wise ad post form name.
     *
     * @param integer $categoryId Category id.
     */
    private function getFormName($categoryId)
    {
        $formName      = '';
        $categoryPath  = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container);
        $categoryNames = array_values($categoryPath);

        if (isset($categoryNames[0]) && $categoryNames[0]) {
            $formName = 'fa_paa_'.str_replace(' ', '_', strtolower($categoryNames[0])).'_admin';
        }

        return $formName;
    }

    /**
     * Get category wise ad post form name.
     *
     * @param integer $categoryId Category id.
     */
    private function getTemplateName($categoryId)
    {
        $templateName  = '';
        $categoryPath  = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container);
        $categoryNames = array_values($categoryPath);

        if (isset($categoryNames[0]) && $categoryNames[0]) {
            $templateName = 'FaAdBundle:AdPostAdmin:'.lcfirst(str_replace(' ', '', ucwords($categoryNames[0]))).'.html.twig';
        }

        return $templateName;
    }

    /**
     * Get root category name by lowercaseing it.
     *
     * @param integer $categoryId Category id.
     *
     * @return mixed
     *
     */
    private function getRootCategoryName($categoryId)
    {
        $categoryName = $this->getRepository('FaEntityBundle:Category')->getRootCategoryName($categoryId, $this->container);

        if ($categoryName) {
            return $categoryName;
        }

        return null;
    }

    /**
     * This method is used to search event time.
     *
     * @param Request $request Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxEventTimeSearchAction(Request $request)
    {
        $timeArray     = array();
        $responseArray = array();
        if ($request->isXmlHttpRequest() && $request->get('term')) {
            $timeArray = CommonManager::getTimeWithIntervalArray1(5);
            $timeArray = preg_grep('/^'.$request->get('term').'.*/', $timeArray);
            $array = array();
            $responseArray['more'] = false;
            foreach ($timeArray as $time) {
                $array[] = array('id' => $time, 'text' => $time);
            }
            $responseArray['results'] = $array;

            return new JsonResponse($responseArray);
        }

        return new Response();
    }

    /**
     * Change category to carweb category.
     *
     * @param Request $request
     */
    public function changeToCarWebCategoryAction($admin_ad_counter, Request $request)
    {
        $isValid = true;
        $errMsg  = null;
        if ($request->get('category_id') && $request->get('r_no')) {
            $category = $this->getRepository('FaEntityBundle:Category')->find($request->get('category_id'));
            if (!$category) {
                $isValid = false;
                $errMsg  = $this->get('translator')->trans('Invalid category.');
            }

            $hasChildren = $this->getRepository('FaEntityBundle:Category')->hasChildren($request->get('category_id'));
            $carWebData  = $this->get('fa.webcar.manager')->findByVRM($request->get('r_no'));
            // Check category and reg number are valid or not
            if ($hasChildren === true || isset($carWebData['error'])) {
                $isValid = false;
                $errMsg  = $this->get('translator')->trans('Invalid category or reginstration number.');
            }

            if ($isValid) {
                $this->container->get('session')->set('reg_no', $request->get('r_no'));
                return $this->redirect($this->generateUrl('ad_post_new_from_category_admin', array('user_id' => $request->get('user_id'), 'admin_ad_counter' => $admin_ad_counter, 'category_id' => $request->get('category_id'), 'is_cat_edit' => 1)));
            }

            if ($request->headers->get('referer')) {
                $this->handleMessage($errMsg, '', array(), 'error');
                return $this->redirect($request->headers->get('referer'));
            }
        }

        if (!$errMsg) {
            $errMsg = $this->get('translator')->trans('Please provide valid category and registration number.');
        }

        return $this->handleMessage($errMsg, 'ad_post_new_admin', array('user_id' => $request->get('user_id')), 'error');
    }

    /**
     * Displays a form to change category for existing record.
     *
     * @param integer $ad_id
     * @param integer $admin_ad_counter
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function changeAdCategoryAction($ad_id, $admin_ad_counter, Request $request)
    {
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository('FaAdBundle:Ad')->find($ad_id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad'));
            }

            if (!$this->getRepository('FaAdBundle:Ad')->isAdCategoryChangableInEditMode($entity)) {
                throw $this->createNotFoundException($this->get('translator')->trans('Category can not be changed.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'ad_admin');
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdPostCategorySelectAdminType::class, $entity, array('action' => $this->generateUrl('ad_post_change_ad_category_admin', array('ad_id' => $ad_id, 'admin_ad_counter' => $admin_ad_counter))));

        if ('POST' === $request->getMethod()) {
            if ($formManager->isValid($form)) {
                // Remove old vertical entry and ad new based on selected new category.
                $oldCategoryId = $entity->getCategory()->getId();
                $newCategoryId = $form->get('category_id')->getData();
                $adPostManager = $this->get('fa_ad.manager.ad_post');
                $verticalObj   = $adPostManager->getVerticalObject($newCategoryId, $entity, $oldCategoryId);

                $verticalObj->setAd($entity);
                $this->getEntityManager()->persist($verticalObj);

                $category = $this->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $newCategoryId));
                $entity->setCategory($category);

                if ($entity->getStatus()->getId() != EntityRepository::AD_STATUS_DRAFT_ID) {
                    $entity->setEditedAt(time());
                }

                $this->getEntityManager()->persist($entity);

                $this->getEntityManager()->flush();
                return $this->handleMessage($this->get('translator')->trans('Category has been changed successfully.', array(), 'success'), 'ad_post_edit_admin', array('id' => $ad_id));
            }
        }

        $parameters  = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Change ad category'),
            'adminAdCounter' => 0,
        );

        return $this->render('FaAdBundle:AdPostAdmin:new.html.twig', $parameters);
    }

    /**
     * Auto populate brand name from category and ad title.
     *
     * @param integer $cd_id
     * @param integer $ad_title
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response | \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function ajaxAutoPopulateBrandAction($cd_id, $ad_title, Request $request)
    {
        if ($request->isXmlHttpRequest() && 'POST' === $request->getMethod() && $cd_id && $ad_title) {
            $responseArray = array();
            $responseArray['brand_id_autocomplete'] = '';
            $responseArray['brand_id'] = '';
            $responseArray['brand_id_dimension_id'] = '';
            $brandObj = $this->getEntityManager()->getRepository('FaEntityBundle:Entity')->getMatchedEntityByString($ad_title, $cd_id);
            if ($brandObj) {
                $responseArray['brand_id_autocomplete'] = $brandObj->getName();
                $responseArray['brand_id'] = $brandObj->getId();
                $responseArray['brand_id_dimension_id'] = $cd_id;
            }
            return new JsonResponse($responseArray);
        }

        return new Response();
    }
    
    public function getFQNForForms($formName)
    {
        // getName() symfony form function is removed After symfony 3, so to handle dynamic forms we need create array
        $formClassArray = [
            'fa_paa_adult_admin' => AdPostAdultAdminType::class,
            'fa_paa_for_sale_admin' => AdPostForSaleAdminType::class,
            'fa_paa_services_admin' => AdPostServicesAdminType::class,
            'fa_paa_motors_admin' => AdPostMotorsAdminType::class
        ];
        $formName = isset($formClassArray[$formName]) ? $formClassArray[$formName] : $formName;
        return $formName;
    }
}
