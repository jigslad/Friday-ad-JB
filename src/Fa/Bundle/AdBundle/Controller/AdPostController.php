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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Fa\Bundle\UserBundle\Entity\User;
use Facebook\FacebookRequest;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Manager\AdPostManager;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\UserBundle\Event\FormEvent;
use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\UserBundle\Event\FilterUserResponseEvent;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Controller\ThirdPartyLoginController;
use Fa\Bundle\UserBundle\Manager\UserImageManager;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\Form\FormError;
use Fa\Bundle\AdBundle\Form\AdPostCategorySelectType;
use Fa\Bundle\AdBundle\Form\AdPostLoginType;
use Fa\Bundle\AdBundle\Form\AdPostRegistrationType;
use Fa\Bundle\UserBundle\Form\UserSiteType;
use Fa\Bundle\AdBundle\Form\AdPostSecondStepForSaleType;
use Fa\Bundle\AdBundle\Form\AdPostFourthStepForSaleType;
use Fa\Bundle\AdBundle\Form\AdPostSecondStepMotorsType;
use Fa\Bundle\AdBundle\Form\AdPostFourthStepMotorsType;
use Fa\Bundle\AdBundle\Form\AdPostSecondStepJobsType;
use Fa\Bundle\AdBundle\Form\AdPostFourthStepJobsType;
use Fa\Bundle\AdBundle\Form\AdPostSecondStepServicesType;
use Fa\Bundle\AdBundle\Form\AdPostFourthStepServicesType;
use Fa\Bundle\AdBundle\Form\AdPostSecondStepPropertyType;
use Fa\Bundle\AdBundle\Form\AdPostFourthStepPropertyType;
use Fa\Bundle\AdBundle\Form\AdPostSecondStepAnimalsType;
use Fa\Bundle\AdBundle\Form\AdPostFourthStepAnimalsType;
use Fa\Bundle\AdBundle\Form\AdPostSecondStepCommunityType;
use Fa\Bundle\AdBundle\Form\AdPostFourthStepCommunityType;
use Fa\Bundle\AdBundle\Form\AdPostSecondStepAdultType;
use Fa\Bundle\AdBundle\Form\AdPostFourthStepAdultType;

/**
 * This controller is used for ad post management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdPostController extends ThirdPartyLoginController
{
    /**
     * Ad post first step action.
     *
     * @param Request $request
     */
    public function firstStepAction(Request $request)
    {
        $objDraftAd   = NULL;
        $categoryPath = NULL;
        $regNo        = NULL;

        $cartURL    = $this->container->getParameter('base_url') . $this->generateUrl('show_cart');
        $refererURL = $request->headers->get('referer');

        //remove add to fav cookies
        $this->getRepository('FaUserBundle:User')->removeUserCookies();

        if (!$this->isAuth()) {
            $this->container->get('session')->remove('paa_skip_login_step');
        } else if (!$request->get('is_edit') && $refererURL != $cartURL) {
            $objDraftAd = $this->getRepository('FaAdBundle:Ad')->getLastDraftAdByUser($this->getUser()->getId());
        }

        $firstStepData = $request->get('fa_paa_category_select', array());
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdPostCategorySelectType::class, (isset($firstStepData['category_id']) ? array('categoryId' => $firstStepData['category_id']) : null));

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($form->get('category_id')->getData()) {  
                	$firstStepData['category_id'] = $form->get('category_id')->getData();
					
                	//check old category and new choosed category are same or not
                	$getOldFirstStepData = $this->getStepSessionData('first');
                	//if not same remove the fourth step session data bcz of getting old category fields at fourth step
                	if(isset($getOldFirstStepData['category_id']) && $getOldFirstStepData['category_id'] != $firstStepData['category_id']) {
                		$this->container->get('session')->remove('paa_fourth_step_data');
                	}
                	
                    $category = $this->getRepository('FaEntityBundle:Category')->findOneBy(array('id' => $form->get('category_id')->getData()));
                    if ($category) {
                        $firstStepData['category_id_autocomplete'] = $category->getName();
                    }

                    // Set first step data into session
                    unset($firstStepData['save'], $firstStepData['_token'], $firstStepData['category_1'], $firstStepData['category_2'], $firstStepData['category_3'], $firstStepData['category_4'], $firstStepData['category_5'], $firstStepData['category_6']);

                    // Set second step data into session
                    $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($firstStepData['category_id'], $this->container);
                    $this->setStepSessionData($firstStepData, 'first');
                    if ($rootCategoryId == CategoryRepository::MOTORS_ID) {
                        $secondStepData = $firstStepData;
                        unset($secondStepData['category_id'], $secondStepData['category_id_autocomplete']);
                        if (count($secondStepData) > 1) {
                            $this->setStepSessionData($secondStepData + $this->getStepSessionData('second'), 'second');
                        }
                    }

                    // remove session for selected category on is_edit mode if category is changed
                    $firstStepCategory = $this->container->get('session')->get('paa_first_step_category', null);
                    if (($firstStepData['category_id'] != $firstStepCategory)) {
                        $this->container->get('session')->remove('paa_first_step_category');
                    }

                    return $this->redirect($this->generateUrl('ad_post_second_step'));
                }
            } elseif (isset($firstStepData['category_id']) && $firstStepData['category_id']) {
                $categoryPath = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($firstStepData['category_id']);
            }
        } else {
            if ($request->get('is_edit')) {
                $firstStepData = $this->getStepSessionData('first');
                
                if (count($firstStepData)) {
//                     $csrfToken     = $this->container->get('form.csrf_provider')->generateCsrfToken('fa_paa_category_select');
                    $csrfToken      = $this->get('security.csrf.token_manager')->getToken('fa_paa_category_select')->getValue();
                    $firstStepData = $firstStepData + array('_token' => $csrfToken);
                    if ($form->has('has_reg_no') && isset($firstStepData['first_step_ordered_fields']) && count(explode(',', $firstStepData['first_step_ordered_fields'])) && !isset($firstStepData['has_reg_no'])) {
                        $options = $form->get('has_reg_no')->getConfig()->getOptions();
                        $firstStepData['has_reg_no'] = (isset($options['data']) ? $options['data'] : false);
                    }
                    if ($form->has('has_reg_no') && isset($firstStepData['first_step_ordered_fields']) && count(explode(',', $firstStepData['first_step_ordered_fields'])) && isset($firstStepData['colour_id_dimension_id']) && !$firstStepData['colour_id_dimension_id']) {
                        $options = $form->get('colour_id_dimension_id')->getConfig()->getOptions();
                        $firstStepData['colour_id_dimension_id'] = (isset($options['data']) ? $options['data'] : false);
                    }
                    // Bind first step data from session
                    $form->submit($firstStepData);

                    // Set selected category in session for populating second step data if user not edit on first step
                    $this->container->get('session')->set('paa_first_step_category', $firstStepData['category_id']);

                    $categoryPath = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($firstStepData['category_id']);
                }
            } else {
                // Remove paa step data from session
                $this->container->get('session')->remove('paa_first_step_data');
                $this->container->get('session')->remove('paa_second_step_data');
                $this->container->get('session')->remove('paa_third_step_data');
                $this->container->get('session')->remove('paa_fourth_step_data');
                $this->container->get('session')->remove('ad_id');
                $this->container->get('session')->remove('paa_show_business_step');
                $this->container->get('session')->remove('paa_fourth_step_brand');

                if ($this->isAuth()) {
                    $this->container->get('session')->set('paa_skip_login_step', true);
                }

                if ($request->get('categoryName')) {
                    $firstStepData = array();
                    $objCategory   = $this->getRepository('FaEntityBundle:Category')->findOneByName($request->get('categoryName'));
                    if ($objCategory) {
                        $hasChildren             = $this->getRepository('FaEntityBundle:Category')->hasChildren($objCategory->getId(), $this->container);
                        $regNoRequiredCategories = array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID, CategoryRepository::MOTORBIKES_ID);
                        if (in_array($objCategory->getId(), $regNoRequiredCategories)) {
                            $categoryPath = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($objCategory->getId(), false, $this->container);
                            if ($request->get('regNo')) {
                                $regNo                       = $request->get('regNo');
                                $categoryPath                = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($objCategory->getId(), false, $this->container);
                                $firstStepData['has_reg_no'] = true;
                            }
                        } else {
                            if (!$hasChildren) {
                                $firstStepData['category_id']              = $objCategory->getId();
                                $firstStepData['category_id_autocomplete'] = $objCategory->getName();

                                // Set first step data into session
                                $this->setStepSessionData($firstStepData, 'first');
                                return $this->redirect($this->generateUrl('ad_post_second_step'));
                            } else {
                                $categoryPath = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($objCategory->getId(), false, $this->container);
                            }
                        }
                    }
                }
            }
        }

        $parameters  = array(
                        'form'         => $form->createView(),
                        'objDraftAd'   => $objDraftAd,
                        'categoryPath' => $categoryPath,
                        'regNo'        => $regNo,
                        );
        $objResponse = CommonManager::setCacheControlHeaders();
        return $this->render('FaAdBundle:AdPost:firstStep.html.twig', $parameters, $objResponse);
    }

    /**
     * Ad post first step action.
     *
     * @param Request $request
     */
    public function ajaxFirstStepMotorsRegNoFieldsAction(Request $request)
    {
        $categoryId = $request->get('categoryId');
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdPostCategorySelectType::class, array('categoryId' => $categoryId));

        $parameters  = array(
            'form' => $form->createView(),
            'categoryId' => $categoryId,
        );

        return $this->render('FaAdBundle:AdPost:ajaxFirstStepMotorsRegNofields.html.twig', $parameters);
    }

    /**
     * Ad post second step action.
     *
     * @param Request $request
     */
    public function secondStepAction(Request $request)
    {
        $response = $this->validateStepAndRedirect('second');
        if ($response !== false) {
            return $response;
        }

        $firstStepData = $this->getStepSessionData('first');
        $formManager   = $this->get('fa.formmanager');
        $formName      = $this->getFormName($firstStepData['category_id'], 'second');
        $form          = $formManager->createForm($formName, null, array('action' => $this->generateUrl('ad_post_second_step')));
        $gaStr         = '';

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $categoryPath = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($firstStepData['category_id'], false, $this->container);
                $categoryPath = implode(' - ', $categoryPath);
                $gaStr        = $categoryPath;
                $categoryName = $this->getRootCategoryName($firstStepData['category_id']);

                $secondStepData = $request->get('fa_paa_second_step_'.$categoryName);
                unset($secondStepData['save'], $secondStepData['_token']);

                //set first step data for motor reg no.
                if (in_array('has_reg_no', array_keys($secondStepData))) {
                    $firstStepDataForMotorsRegNo = array();
                    $motorsRegNoFields = $this->getMotorRegNoFields();
                    foreach ($secondStepData as $secondStepDataKey => $secondStepDataValue) {
                        if (in_array($secondStepDataKey, $motorsRegNoFields)) {
                            $firstStepDataForMotorsRegNo[$secondStepDataKey] = $secondStepDataValue;
                        }
                    }

                    if (count($firstStepDataForMotorsRegNo)) {
                        $firstStepDataForMotorsRegNo['first_step_ordered_fields'] = implode(',', array_keys($firstStepDataForMotorsRegNo));
                        $firstStepDataForMotorsRegNo = $firstStepDataForMotorsRegNo + $this->getStepSessionData('first');
                        $this->setStepSessionData($firstStepDataForMotorsRegNo, 'first');
                    }
                }
                // Set second step data into session
                $this->setStepSessionData($secondStepData, 'second');
                //get brand entity for selected category.
                $categoryDimensionId = $this->getEntityManager()->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategoryHierarchy($firstStepData['category_id'], 'Brand', $this->container);
                if ($categoryDimensionId && isset($secondStepData['title'])) {
                    $brandObj = $this->getEntityManager()->getRepository('FaEntityBundle:Entity')->getMatchedEntityByString($secondStepData['title'], $categoryDimensionId);
                    if ($brandObj) {
                        $fourthStepData = $this->getStepSessionData('fourth');
                        $fourthStepData['brand_id_autocomplete'] = $brandObj->getName();
                        $fourthStepData['brand_id'] = $brandObj->getId();
                        $fourthStepData['brand_id_dimension_id'] = $categoryDimensionId;
                        $this->setStepSessionData($fourthStepData, 'fourth');
                        $this->container->get('session')->set('paa_fourth_step_brand', $brandObj->getId());
                    } else {
                        $this->container->get('session')->remove('paa_fourth_step_brand');
                        $this->removeAutopopulatedBrandData();
                    }
                } else {
                    $this->container->get('session')->remove('paa_fourth_step_brand');
                    $this->removeAutopopulatedBrandData();
                }

                return $this->redirect($this->generateUrl('ad_post_third_step'));
            } else {
                $formErrors    = $formManager->getFormSimpleErrors($form, 'label');
                $categoryPath  = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($firstStepData['category_id'], false, $this->container);
                $categoryPath  = implode(' - ', $categoryPath);
                $gaStr         = $categoryPath;
                $errorMessages = '';
                foreach ($formErrors as $fieldName => $errorMessage) {
                    if ($errorMessages != '') {
                        $errorMessages = $errorMessages . ' | ' . $fieldName . ': ' . $errorMessage[0];
                    } else {
                        $errorMessages = $fieldName . ': ' . $errorMessage[0];
                    }
                }
                $gaStr = $gaStr . ' // ' . $errorMessages;
            }
        } else {
            if ($request->get('is_edit') || $request->get('is_cat_edit')) {
                // Set selected category in session for populating fourth step data if user comes to directly to second step without changing category
                $this->container->get('session')->set('paa_first_step_category', $firstStepData['category_id']);
            }

            $request->request->set('is_copy', 1);

            // second step data from session if exist either category is same or changed in first step in edit mode
            $secondStepData = $this->getStepSessionData('second');
            if (count($secondStepData)) {
//                 $csrfToken      = $this->container->get('form.csrf_provider')->generateCsrfToken($formName); 
                $csrfToken      = $this->get('security.csrf.token_manager')->getToken($formName)->getValue();
                
                $secondStepData = $secondStepData + array('_token' => $csrfToken);

                $formFields            = array();
                $secondStepSessionData = $secondStepData;
                foreach ($form->all() as $field) {
                    $fieldData = $form->get($field->getName())->getData();
                    $formFields[$field->getName()] = $fieldData;
                }

                // unset fields from session data which  are not in form fields
                foreach ($secondStepSessionData as $field => $fieldData) {
                    if (($field != '_token' && !in_array($field, array_keys($formFields))) || $field == 'second_step_ordered_fields') {
                        unset($secondStepData[$field]);
                    }
                }

                // Add form fields data to session data if form field has default value set in form.
                foreach ($formFields as $field => $fieldData) {
                    if (!isset($secondStepData[$field])) {
                        if (strlen($fieldData)) {
                            $secondStepData[$field] = $fieldData;
                        }
                    }
                }

                if ($request->get('is_copy')) {
                    foreach ($form->all() as $field) {
                        if (!isset($secondStepData[$field->getName()])) {
                            if ($fieldData = $form->get($field->getName())->getData()) {
                                $secondStepData[$field->getName()] = $fieldData;
                            }
                        }
                    }
                }

                // Bind second step data from session
                $form->submit($secondStepData);
            }
        }

        $parameters = array(
                          'form'            => $form->createView(),
                          'first_step_data' => $firstStepData,
                          'gaStr'           => $gaStr,
                      );

        return $this->render($this->getTemplateName($firstStepData['category_id'], 'second'), $parameters);
    }

    /**
     * Ad post third step action.
     *
     * @param Request $request
     */
    public function thirdStepAction(Request $request)
    {
        $response = $this->validateStepAndRedirect('third');
        if ($response !== false) {
            return $response;
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdPostLoginType::class);

        $isErrors      = false;
        $gaStr         = '';
        $firstStepData = $this->getStepSessionData('first');
        $categoryPath  = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($firstStepData['category_id'], false, $this->container);
        $categoryPath  = implode(' - ', $categoryPath);
        $gaStr         = $categoryPath;

        //if has user info in session then remove it
        if ($this->container->get('session')->has('paa_user_info')) {
            $this->container->get('session')->remove('paa_user_info');
        }

        //for facebook & google login
        $facebookLoginUrl = '';
        $googleLoginUrl = '';
        //facebook
        $fbManager = $this->get('fa.facebook.manager');
        $fbManager->init('facebook_paa_login', array('fbSuccess' => 1));

        $facebookPermissions = array('email');
        $facebookLoginUrl = $fbManager->getFacebookHelper()->getLoginUrl($facebookPermissions);

        //google
        $googleManager = $this->get('fa.google.manager');
        $googlePermissions = array('https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/userinfo.email','https://www.googleapis.com/auth/userinfo.profile');
        $googleManager->init($googlePermissions, 'google_paa_login', array('googleSuccess' => 1));
        $googleLoginUrl = $googleManager->getGoogleClient()->createAuthUrl();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                if ($data['user_type'] == 1) {
                    $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('username' => $data['username']));
                    if (!$user) {
                        return $this->handleMessage($this->get('translator')->trans('Invalid email or password.', array(), 'validators'), 'ad_post_third_step', array(), 'error');
                    } else {
                        $token = new UsernamePasswordToken(
                            $user,
                            $user->getPassword(),
                            'main',
                            $user->getRoles()
                        );
                        $this->get("security.token_storage")->setToken($token);

                        //now dispatch the login event
                        $event = new InteractiveLoginEvent($request, $token);
                        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                        return $this->handleMessage($this->get('translator')->trans('%username%, You have successfully logged in to Friday Ad.', array('%username%' => $user->getUserFullName()), 'frontend-paa-login'), 'ad_post_save_draft_ad');
                    }
                } else {
                    $this->container->get('session')->set('paa_user_info', array('user_email' => $data['username']));
                    return $this->redirect($this->generateUrl('ad_post_third_step_registration'));
                }

                $dispatcher->dispatch(UserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));
                return $response;
            } else {
                $formErrors    = $formManager->getFormSimpleErrors($form, 'label');
                $errorMessages = '';
                $gaStr         = '';
                $isErrors      = true;
                foreach ($formErrors as $fieldName => $errorMessage) {
                    if ($errorMessages != '') {
                        $errorMessages = $errorMessages . ' | ' . $fieldName . ': ' . $errorMessage[0];
                    } else {
                        $errorMessages = $fieldName . ': ' . $errorMessage[0];
                    }
                }
                $gaStr = $errorMessages;
            }
        }

        $parameters = array(
            'form'   => $form->createView(),
            'facebookLoginUrl' => $facebookLoginUrl,
            'googleLoginUrl'   => $googleLoginUrl,
            'gaStr'            => $gaStr,
            'isErrors'         => $isErrors,
        );

        return $this->render('FaAdBundle:AdPost:thirdStep.html.twig', $parameters);
    }

    /**
     * Ad post third step registration action.
     *
     * @param Request $request
     */
    public function thirdStepRegistrationAction(Request $request)
    {
        $isCompany        = 0;
        $response         = $this->validateStepAndRedirect('third');
        $logoUploaded     = 0;
        $privateUserLogo  = CommonManager::getUserLogo($this->container, '', null, null, null, true, false, null, null);
        $businessUserLogo = CommonManager::getUserLogo($this->container, '', null, null, null, true, true, null, null);
        $userLogoTmpPath  = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp';
        $gaStr            = '';

        if ($response !== false) {
            return $response;
        }

        $tempUserId = CommonManager::generateHash();
        if (!$this->container->get('session')->has('tempUserIdAP')) {
         $this->container->get('session')->set('tempUserIdAP', $tempUserId);
        }

        if ('POST' === $request->getMethod()) {
            $formData = $request->get('fa_paa_registration');

            if ($formData && isset($formData['user_roles']) && $formData['user_roles'] == 'ROLE_BUSINESS_SELLER') {
                $isCompany = 1;
            }

            if ($formData['email']) {
                // check if email address is of half account then, update that account, no need to do new entry.
                $halfAccount = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $formData['email'], 'is_half_account' => 1));
                if ($halfAccount) {
                    $user = $halfAccount;
                    $user->setIsHalfAccount(0);
                } else {
                    $user = $this->setDefaultValueForUser('paa_user_info');
                }
            } else {
                $user = $this->setDefaultValueForUser('paa_user_info');
            }
        } else {
            $user = $this->setDefaultValueForUser('paa_user_info');
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdPostRegistrationType::class, $user);
        $dispatcher  = $this->container->get('event_dispatcher');

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(UserEvents::REGISTRATION_SUCCESS, $event);
                $formManager->save($user);

                if ($this->container->get('session')->has('tempUserIdAP') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdAP').'.jpg')) {
                    $this->moveUserLogo($user);
                }

                if (null === $response = $event->getResponse()) {
                    $response = $this->redirect($this->generateUrl('ad_post_save_draft_ad'));
                }

                $dispatcher->dispatch(UserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                if (($user->getRole() && $user->getRole()->getId() == RoleRepository::ROLE_BUSINESS_SELLER_ID)) {
                    $this->getRepository('FaUserBundle:UserPackage')->assignFreePackageToUser($user, null, $this->container);
                }

                $firstStepData = $this->getStepSessionData('first');
                $businessCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($firstStepData['category_id'], $this->container);

                if ($businessCategoryId && ($businessCategoryId == CategoryRepository::SERVICES_ID || $businessCategoryId == CategoryRepository::ADULT_ID)) {
                    // Set session to show business details page in running PAA process
                    $this->container->get('session')->set('paa_show_business_step', true);
                }

                return $this->handleMessage($this->get('translator')->trans('%username%, You have successfully logged in to Friday Ad.', array('%username%' => $user->getUserFullName()), 'frontend-paa-login'), 'ad_post_save_draft_ad');
            } else {
                $formErrors    = $formManager->getFormSimpleErrors($form, 'label');
                $errorMessages = '';
                foreach ($formErrors as $fieldName => $errorMessage) {
                    if ($errorMessages != '') {
                        $errorMessages = $errorMessages . ' | ' . $fieldName . ': ' . $errorMessage[0];
                    } else {
                        $errorMessages = $fieldName . ': ' . $errorMessage[0];
                    }
                }
                $gaStr = $gaStr . ' // ' . $errorMessages;
            }
        } else {
            $firstStepData = $this->getStepSessionData('first');
            $businessCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($firstStepData['category_id'], $this->container);

            if ($businessCategoryId) {
                $form->getData()->setBusinessCategoryId($businessCategoryId);
                $form->setData($form->getData());
            }

            if ($businessCategoryId && ($businessCategoryId == CategoryRepository::SERVICES_ID || $businessCategoryId == CategoryRepository::ADULT_ID)) {
                $form->get('user_roles')->setData(RoleRepository::ROLE_BUSINESS_SELLER);
            } else {
                $form->get('user_roles')->setData(RoleRepository::ROLE_SELLER);
            }
        }

        $userLogoTmpPath = $this->container->get('kernel')->getRootDir().'/../web/uploads/tmp';
        if ($this->container->get('session')->has('tempUserIdAP') && file_exists($userLogoTmpPath.'/'.$this->container->get('session')->get('tempUserIdAP').'.jpg')) {
         $privateUserLogo  = CommonManager::getUserLogo($this->container, '', $this->container->get('session')->get('tempUserIdAP'), null, null, true, false, null, null);
         $businessUserLogo = CommonManager::getUserLogo($this->container, '', $this->container->get('session')->get('tempUserIdAP'), null, null, true, true, null, null);
         $logoUploaded     = 1;
        }

        $parameters = array(
            'form'             => $form->createView(),
            'tempUserId'       => $this->container->get('session')->get('tempUserIdAP'),
            'isCompany'        => $isCompany,
            'privateUserLogo'  => $privateUserLogo,
            'businessUserLogo' => $businessUserLogo,
            'logoUploaded'     => $logoUploaded,
            'gaStr'            => $gaStr,
        );

        return $this->render('FaAdBundle:AdPost:thirdStepRegistration.html.twig', $parameters);
    }

    /**
     * Facebook login action.
     *
     * @param Request $request
     */
    public function facebookPaaLoginAction(Request $request)
    {
        $this->removeSession('paa_user_info');

        $response = $this->processFacebook($request, 'facebook_paa_login', 'ad_post_fourth_step', true, $this->get('translator')->trans('You have successfully logged in.', array(), 'frontend-paa-login'));

        if (is_array($response)) {
            $this->container->get('session')->set('paa_user_info', $response);
            return $this->redirect($this->generateUrl('ad_post_third_step_registration'));
        } else if ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Facebook (First Name, Last Name, Email).', array(), 'frontend-register'), 'ad_post_third_step', array(), 'error');
        } else if ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('ad_post_third_step');
        } else {
            return $response;
        }
    }

    /**
     * Google login action.
     *
     * @param Request $request
     */
    public function googlePaaLoginAction(Request $request)
    {
        $this->removeSession('paa_user_info');

        $response = $this->processGoogle($request, 'google_paa_login', 'ad_post_fourth_step', false, true, $this->get('translator')->trans('You have successfully logged in.', array(), 'frontend-paa-login'));

        if (is_array($response)) {
            $this->container->get('session')->set('paa_user_info', $response);
            return $this->redirect($this->generateUrl('ad_post_third_step_registration'));
        } else if ($response == 'MISSINGDATA') {
            return $this->handleMessage($this->get('translator')->trans('One of field is missing from Google (First Name, Last Name, Email).', array(), 'frontend-register'), 'ad_post_third_step', array(), 'error');
        } else if ($response == 'MISSINGTOKEN' || $response == 'MISSINGCODE') {
            return $this->redirectToRoute('ad_post_third_step');
        } else {
            return $response;
        }
    }

    /**
     * Save ad as draft after third step.
     *
     * @param Request $request
     */
    public function saveDraftAdAction(Request $request)
    {
        $response = $this->validateStepAndRedirect('save_draft_ad');
        if ($response !== false) {
            return $response;
        }

        $adPostManager        = $this->get('fa_ad.manager.ad_post');
        $user                 = $this->container->get('security.token_storage')->getToken()->getUser();
        $data                 = array_merge($this->getStepSessionData('first'), $this->getStepSessionData('second'));
        $data['user_id']      = $user->getId();
        $data['ad_status_id'] = EntityRepository::AD_STATUS_DRAFT_ID;
        if ($this->container->get('session')->has('ad_id')) {
            $ad = $adPostManager->saveAd($data, $this->container->get('session')->get('ad_id'));
        } else {
            $ad = $adPostManager->saveAd($data);
        }

        $firstStepData = $this->getStepSessionData('first');
        $categoryName  = $this->getRootCategoryName($firstStepData['category_id']);

        // Show business details page if user is business user and for service and adult ads only
        if (in_array($categoryName, array('adult', 'services'))
            && ($user->getRole() && $user->getRole()->getId() == RoleRepository::ROLE_BUSINESS_SELLER_ID)
            && (!$user->getBusinessName() || $this->container->get('session')->has('paa_show_business_step'))) {

            // Set session to show business details page in running PAA process
            $this->container->get('session')->set('paa_show_business_step', true);

            // Redirect to add user business details step after draft ad saved.
            return $this->redirect($this->generateUrl('add_user_business_details'));
        } else {
            // Redirect to fourth step after draft ad saved.
            $this->container->get('session')->remove('paa_show_business_step');
            return $this->redirect($this->generateUrl('ad_post_fourth_step'));
        }
    }

    /**
     * Add user business detail page after save draft ad if category is adult or sevices selected.
     *
     * @param Request $request
     */
    public function addUserBusinessDetailsAction(Request $request)
    {
        $response = $this->validateStepAndRedirect('add_user_business_details');
        if ($response !== false) {
            return $response;
        }

        $user     = $this->container->get('security.token_storage')->getToken()->getUser();
        $userSite = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
        if (!$userSite) {
            $userSite = new UserSite();
        }

        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserSiteType::class, $userSite, array('action' => $this->generateUrl('add_user_business_details')));

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                return $this->redirect($this->generateUrl('ad_post_fourth_step'));
            }
        } else {
            $form->get('company_name')->setData($user->getBusinessName());
            if (!$form->getData()->getId()) {
                $form->get('phone1')->setData($user->getPhone());
            }
        }

        return $this->render('FaAdBundle:AdPost:userBusinessDetails.html.twig', array('form' => $form->createView()));
    }

    /**
     * Ad post fourth step action after draft ad saved.
     *
     * @param Request $request
     */
    public function fourthStepAction(Request $request)
    {
        $refererUrl = $request->server->get('HTTP_REFERER');
        $response   = $this->validateStepAndRedirect('fourth');
        $gaStr      = '';

        if ($response !== false) {
            return $response;
        }

        $ad            = $this->getRepository('FaAdBundle:Ad')->find($this->container->get('session')->get('ad_id'));
        $firstStepData = $this->getStepSessionData('first');
        $formManager   = $this->get('fa.formmanager');
        $formName      = $this->getFormName($firstStepData['category_id'], 'fourth');
        $form          = $formManager->createForm($formName);
        $categoryPath  = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($firstStepData['category_id'], false, $this->container);
        $categoryPath  = implode(' - ', $categoryPath);
        $gaStr         = $categoryPath;

        if ('POST' === $request->getMethod()) {
            $this->container->get('session')->remove('paa_fourth_step_brand');
            $form->handleRequest($request);
            if ($form->isValid()) {
                $isPreview = false;
                $isSave    = false;
                
                $categoryName   = $this->getRootCategoryName($firstStepData['category_id']);
                $fourthStepData = $request->get('fa_paa_fourth_step_'.$categoryName);

                if (isset($fourthStepData['preview'])) {
                    $isPreview = true;
                }
                if (isset($fourthStepData['save'])) {
                 $isSave = true;
                }
                unset($fourthStepData['save'], $fourthStepData['preview'], $fourthStepData['_token']);

                // Set fourth step data into session
                $this->setStepSessionData($fourthStepData, 'fourth');

                $data                 = array_merge($this->getStepSessionData('first'), $this->getStepSessionData('second'), $this->getStepSessionData('fourth'));
                $data['user_id']      = $this->container->get('security.token_storage')->getToken()->getUser()->getId();
                $data['ad_status_id'] = EntityRepository::AD_STATUS_DRAFT_ID;
                $ad = $this->get('fa_ad.manager.ad_post')->saveAd($data, $this->container->get('session')->get('ad_id'), true);
                //check location is updated or Not
                if($ad) {
                	$locationExist = $this->getEntityManager()->getRepository('FaAdBundle:AdLocation')->findLastAdLocationById($ad->getId());
                	if($locationExist == null && $form->has('location_autocomplete')) {
                		$form->get('location_autocomplete')->addError(new FormError('Location is invalid.'));                		
                	} else {
                		if ($this->container->get('session')->has('paa_edit_url') && !$isSave && !$isPreview) {
                			return $this->redirect($this->container->get('session')->get('paa_edit_url'));
                		}
                		if ($isPreview) {
                			$this->container->get('session')->set('back_url_from_ad_package_page', $this->generateUrl('show_draft_ad', array('adId' => $ad->getId()), true));
                			return $this->redirect($this->generateUrl('show_draft_ad', array('adId' => $ad->getId())));
                		} else {
                			$this->container->get('session')->set('back_url_from_ad_package_page', $this->generateUrl('ad_post_fourth_step', array('is_edit' => 1), true));
                			return $this->redirect($this->generateUrl('ad_package_purchase', array('adId' => $ad->getId())));
                		}
                	}
                }
                
            } else {
                $formErrors    = $formManager->getFormSimpleErrors($form, 'label');
                $categoryPath  = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($firstStepData['category_id'], false, $this->container);
                $categoryPath  = implode(' - ', $categoryPath);
                $gaStr         = $categoryPath;
                $errorMessages = '';
                $categoryName   = $this->getRootCategoryName($firstStepData['category_id']);
                $fourthStepData = $request->get('fa_paa_fourth_step_'.$categoryName);
                foreach ($formErrors as $fieldName => $errorMessage) {
                    if ($errorMessages != '') {
                        $errorMessages = $errorMessages . ' | ' . $fieldName . ': ' . $errorMessage[0];
                    } else {
                        $errorMessages = $fieldName . ': ' . $errorMessage[0];
                    }
                    if($fieldName=='Location') {
                        $errorMessages .=  ' - '.$fourthStepData['location'];
                    }
                }
                $gaStr = $gaStr . ' // ' . $errorMessages;
            }
        } else {
            // Bind fourth step data from session if category is not changed in is_edit mode in first step
            $firstStepCategory = $this->container->get('session')->get('paa_first_step_category', null);
            if ($request->get('is_edit') || ($firstStepData['category_id'] == $firstStepCategory) || $this->container->get('session')->has('paa_fourth_step_brand')) {
                $fourthStepData = $this->getStepSessionData('fourth');
                if ($this->container->get('session')->has('paa_fourth_step_brand')) {
                    $formFields            = array();
                    $fourthStepSessionData = $fourthStepData;
                    foreach ($form->all() as $field) {
                        $fieldData = $form->get($field->getName())->getData();
                        $formFields[$field->getName()] = $fieldData;
                    }

                    // unset fields from session data which  are not in form fields
                    foreach ($fourthStepSessionData as $field => $fieldData) {
                        if (($field != '_token' && !in_array($field, array_keys($formFields))) || $field == 'second_step_ordered_fields') {
                            unset($fourthStepData[$field]);
                        }
                    }

                    // Add form fields data to session data if form field has default value set in form.
                    foreach ($formFields as $field => $fieldData) {
                        if (!isset($fourthStepData[$field])) {
                            if (strlen($fieldData)) {
                                $fourthStepData[$field] = $fieldData;
                            }
                        }
                    }

                    foreach ($form->all() as $field) {
                        if (!isset($fourthStepData[$field->getName()])) {
                            if ($fieldData = $form->get($field->getName())->getData()) {
                                $fourthStepData[$field->getName()] = $fieldData;
                            }
                        }
                    }
                }

                if (count($fourthStepData)) {
                    $csrfToken      = $this->get('security.csrf.token_manager')->getToken($formName)->getValue();
                    $fourthStepData = $fourthStepData + array('_token' => $csrfToken);

                    if (isset($fourthStepData['location_autocomplete']) && $fourthStepData['location_autocomplete']) {
                        $fourthStepData['location_text'] = $fourthStepData['location_autocomplete'];
                    }

                    // Bind fourth step data from session
                    $form->submit($fourthStepData);
                }
            } elseif (!empty($this->getStepSessionData('fourth'))) {
            	$fourthStepData = $this->getStepSessionData('fourth');
            	
            	// Remove fourth step data from session
            	$this->container->get('session')->remove('paa_fourth_step_data');
            	
            	if (count($fourthStepData)) {
            	    $csrfToken      = $this->get('security.csrf.token_manager')->getToken($formName)->getValue();
            		$fourthStepData = $fourthStepData + array('_token' => $csrfToken);
            		
            		if (isset($fourthStepData['location_autocomplete']) && $fourthStepData['location_autocomplete']) {
            			$fourthStepData['location_text'] = $fourthStepData['location_autocomplete'];
            		}
            		// Bind fourth step data from session
            		$form->submit($fourthStepData);
            		
            	}
            }else {            
                // Remove fourth step data from session
                $this->container->get('session')->remove('paa_fourth_step_data');
            }
        }

        $parameters = array(
                          'form'       => $form->createView(),
                          'ad'         => $ad,
                          'refererUrl' => $refererUrl,
                          'gaStr'      => $gaStr,
                      );

        return $this->render($this->getTemplateName($firstStepData['category_id'], 'fourth'), $parameters);
    }

    /**
     * Get price suggestion based on ad title and selected category.
     *
     * @param Request $request
     */
    public function ajaxPriceSuggestionAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $categoryId = $request->get('category_id');
            $title      = $request->get('title');
            $parameters = $this->getRepository('FaAdBundle:Ad')->getPaaSimilarAdverts($this->container, $categoryId, $title, 1, 1);

            // Get lower and upper price suggestion
            if (isset($parameters['totalAds']) && $parameters['totalAds'] >= 5) {
                $lower = round($parameters['totalAds'] * 0.25);
                $upper = round($parameters['totalAds'] * 0.75);

                $lowerPrice = $this->getRepository('FaAdBundle:Ad')->getPriceSuggestion($this->container, $categoryId, $title, 1, 1, ($lower - 1));
                $upperPrice = $this->getRepository('FaAdBundle:Ad')->getPriceSuggestion($this->container, $categoryId, $title, 1, 1, ($upper - 1));

                 $priceSuggestion = array(
                     'lowerPrice' => $lowerPrice,
                     'upperPrice' => $upperPrice,
                 );

                    $parameters = $parameters + $priceSuggestion;
            }

            return $this->render('FaAdBundle:AdPost:ajaxPriceSuggestion.html.twig', $parameters);
        }

        return $this->render('FaAdBundle:AdPost:ajaxPriceSuggestion.html.twig');
    }

    /**
     * Get similar adverts based on ad title and selected category.
     *
     * @param Request $request
     */
    public function ajaxSimilarAdvertsAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $categoryId = $request->get('category_id');
            $title      = $request->get('title');
            $page       = $request->get('page', 1);
            $parameters = $this->getRepository('FaAdBundle:Ad')->getPaaSimilarAdverts($this->container, $categoryId, $title, $page);
            $parameters = $parameters + array('page' => $page);

            if ($page == 1) {
                return $this->render('FaAdBundle:AdPost:ajaxSimilarAdverts.html.twig', $parameters);
            } else {
                return $this->render('FaAdBundle:AdPost:listSimilarAdverts.html.twig', $parameters);
            }
        }

        return $this->render('FaAdBundle:AdPost:ajaxSimilarAdverts.html.twig');
    }

    /**
     * Get category wise ad post form name.
     *
     * @param integer $categoryId Category id.
     * @param string  $step       Step.
     *
     */
    private function getFormName($categoryId, $step)
    {
        $formName      = '';
        $categoryName  = $this->getRootCategoryName($categoryId);

        if ($categoryName && $step) {
            $formName = 'fa_paa_'.$step.'_step_'.$categoryName;
        }
        
        // getName() symfony form function is removed After symfony 3, so to handle dynamic forms we need create array
        $formClassArray = [
            'fa_paa_second_step_for_sale' => AdPostSecondStepForSaleType::class, 
            'fa_paa_fourth_step_for_sale' => AdPostFourthStepForSaleType::class,
            'fa_paa_second_step_motors' => AdPostSecondStepMotorsType::class,
            'fa_paa_fourth_step_motors' => AdPostFourthStepMotorsType::class,
            'fa_paa_second_step_jobs' => AdPostSecondStepJobsType::class,
            'fa_paa_fourth_step_jobs' => AdPostFourthStepJobsType::class,
            'fa_paa_second_step_services' => AdPostSecondStepServicesType::class,
            'fa_paa_fourth_step_services' => AdPostFourthStepServicesType::class,
            'fa_paa_second_step_property' => AdPostSecondStepPropertyType::class,
            'fa_paa_fourth_step_property' => AdPostFourthStepPropertyType::class,
            'fa_paa_second_step_animals' => AdPostSecondStepAnimalsType::class,
            'fa_paa_fourth_step_animals' => AdPostFourthStepAnimalsType::class,
            'fa_paa_second_step_community' => AdPostSecondStepCommunityType::class,
            'fa_paa_fourth_step_community' => AdPostFourthStepCommunityType::class,
            'fa_paa_second_step_adult' => AdPostSecondStepAdultType::class,
            'fa_paa_fourth_step_adult' => AdPostFourthStepAdultType::class
        ];
        $formName = isset($formClassArray[$formName]) ? $formClassArray[$formName] : $formName;
        return $formName;
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
     * Get category wise ad post form name.
     *
     * @param integer $categoryId Category id.
     * @param string  $step       Step.
     *
     */
    private function getTemplateName($categoryId, $step)
    {
        $templateName  = '';
        $categoryName = $this->getRepository('FaEntityBundle:Category')->getRootCategoryName($categoryId, $this->container, true);

        if ($categoryName && $step) {
            $templateName = 'FaAdBundle:AdPost:'.$step.'Step'.$categoryName.'.html.twig';
        }

        return $templateName;
    }

    /**
     * Validate ad post steps.
     *
     * @param string $step Step.
     *
     */
    private function validateStepAndRedirect($step)
    {
        if (!$this->isAuth()) {
            $this->container->get('session')->remove('paa_skip_login_step');
        }

        if ($step == 'second') {
            if (!$this->container->get('session')->has('paa_first_step_data')) {
                return $this->redirect($this->generateUrl('ad_post_first_step'));
            }
        } elseif ($step == 'third') {
            if (!$this->container->get('session')->has('paa_first_step_data')) {
                return $this->redirect($this->generateUrl('ad_post_first_step'));
            }

            if (!$this->container->get('session')->has('paa_second_step_data')) {
                return $this->redirect($this->generateUrl('ad_post_second_step'));
            }

            if ($this->isAuth()) {
                return $this->redirect($this->generateUrl('ad_post_save_draft_ad'));
            }
        } elseif ($step == 'save_draft_ad') {
            if (!$this->container->get('session')->has('paa_first_step_data')) {
                return $this->redirect($this->generateUrl('ad_post_first_step'));
            }

            if (!$this->container->get('session')->has('paa_second_step_data')) {
                return $this->redirect($this->generateUrl('ad_post_second_step'));
            }

            if (!$this->isAuth()) {
                return $this->redirect($this->generateUrl('ad_post_third_step'));
            }
        } elseif ($step == 'fourth') {
            if (!$this->isAuth()) {
                return $this->redirect($this->generateUrl('ad_post_third_step'));
            }

            if (!$this->container->get('session')->has('ad_id')) {
                return $this->redirect($this->generateUrl('ad_post_save_draft_ad'));
            }
        } elseif ($step == 'add_user_business_details') {
            if (!$this->container->get('session')->has('paa_first_step_data')) {
                return $this->redirect($this->generateUrl('ad_post_first_step'));
            }

            if (!$this->container->get('session')->has('paa_second_step_data')) {
                return $this->redirect($this->generateUrl('ad_post_second_step'));
            }

            if (!$this->isAuth()) {
                return $this->redirect($this->generateUrl('ad_post_third_step'));
            }

            $firstStepData = $this->getStepSessionData('first');
            $categoryName  = $this->getRootCategoryName($firstStepData['category_id']);
            $user          = $this->container->get('security.token_storage')->getToken()->getUser();

            // if category is not adult or services then redirect to fourth step directly.
            if (!in_array($categoryName, array('adult', 'services'))) {
                return $this->redirect($this->generateUrl('ad_post_fourth_step'));
            }

            // Show business detail page for business user only.
            if ($user->getRole() && $user->getRole()->getId() != RoleRepository::ROLE_BUSINESS_SELLER_ID) {
                return $this->redirect($this->generateUrl('ad_post_fourth_step'));
            }

            // if user has business name entered already then redirect to fourth step directly.
            if ($user->getBusinessName() && !$this->container->get('session')->has('paa_show_business_step')) {
                return $this->redirect($this->generateUrl('ad_post_fourth_step'));
            }
        }

        return false;
    }


    /**
     *  Set the step wise data to session.
     *
     * @param array  $data Step data array.
     * @param string $step Ad post step.
     */
    private function setStepSessionData($data, $step)
    {
        $this->container->get('session')->set('paa_'.$step.'_step_data', serialize($data));
    }

    /**
     * Get the step wise data from session.
     *
     * @param string $step Ad post step.
     */
    private function getStepSessionData($step)
    {
        $data = array();
        if ($this->container->get('session')->has('paa_'.$step.'_step_data')) {
            $data = unserialize($this->container->get('session')->get('paa_'.$step.'_step_data'));
        }

        return $data;
    }

    /**
     * Post similar ad.
     *
     * @param integer $adId    Ad id.
     * @param Request $request Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxPostSimilarAdAction($adId, Request $request)
    {
        $error         = '';
        $redirectToUrl = '';

        if ($request->isXmlHttpRequest()) {
            // Remove paa step data from session
            $this->container->get('session')->remove('paa_first_step_data');
            $this->container->get('session')->remove('paa_second_step_data');
            $this->container->get('session')->remove('paa_third_step_data');
            $this->container->get('session')->remove('paa_fourth_step_data');
            $this->container->get('session')->remove('ad_id');

            $ad = $this->getRepository('FaAdBundle:Ad')->find($adId);
            //check for ad.
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                $error = $this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-show-ad');
            } else {
                if ($ad->getCategory() && $ad->getCategory()->getId()) {
                    $firstStepData                             = array();
                    $firstStepData['category_id']              = $ad->getCategory()->getId();
                    $firstStepData['category_id_autocomplete'] = $ad->getCategory()->getName();

                    // Set first step data into session
                    $this->setStepSessionData($firstStepData, 'first');

                    $secondStepData          = array();
                    $secondStepData['title'] = $ad->getTitle();

                    // Set second step data into session
                    $this->setStepSessionData($secondStepData, 'second');

                    $redirectToUrl = $this->container->get('router')->generate('ad_post_second_step', array('is_copy' => 1));
                } else {
                    $error = $this->get('translator')->trans('Ad category not found.', array(), 'frontend-show-ad');
                }
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl));
        } else {
            return new Response();
        }
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
            $timeArray = CommonManager::getTimeWithIntervalArray1(30);

            if ($request->get('term') != 'all') {
                $timeArray = preg_grep('/^'.$request->get('term').'.*/', $timeArray);
            }

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
    public function changeToCarWebCategoryAction(Request $request)
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
                $firstStepData                             = array();
                $firstStepData['category_id']              = $request->get('category_id');
                $firstStepData['category_id_autocomplete'] = $category->getName();

                $secondStepData = $this->getStepSessionData('second');

                if ($request->get('r_no')) {
                    $secondStepData['reg_no']     = $request->get('r_no');
                    $secondStepData['has_reg_no'] = 1;
                    $firstStepDataForMotorsRegNo = array();
                    $motorsRegNoFields = $this->getMotorRegNoFields();
                    foreach ($motorsRegNoFields as $motorsRegNoField) {
                        if (array_key_exists($motorsRegNoField, $secondStepData)) {
                            $firstStepData[$motorsRegNoField] = $secondStepData[$motorsRegNoField];
                        } else {
                            $firstStepData[$motorsRegNoField] = null;
                        }
                    }
                }

                // Set first step data into session
                $this->setStepSessionData($firstStepData, 'first');

                // Set second step data into session
                $this->setStepSessionData($secondStepData, 'second');

                return $this->redirect($this->generateUrl('ad_post_second_step', array('is_cat_edit' => 1)));
            }

            if ($request->headers->get('referer')) {
                $this->handleMessage($errMsg, '', array(), 'error');
                return $this->redirect($request->headers->get('referer'));
            }
        }

        if (!$errMsg) {
            $errMsg = $this->get('translator')->trans('Please provide valid category and registration number.');
        }

        return $this->handleMessage($errMsg, 'ad_post_first_step', array(), 'error');
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
     * Remove auto populated brand data
     */
    private function removeAutopopulatedBrandData()
    {
        $fourthStepData = $this->getStepSessionData('fourth');
        if (isset($fourthStepData['brand_id_autocomplete'])) {
            unset($fourthStepData['brand_id_autocomplete']);
        }
        if (isset($fourthStepData['brand_id'])) {
            unset($fourthStepData['brand_id']);
        }
        if (isset($fourthStepData['brand_id_dimension_id'])) {
            unset($fourthStepData['brand_id_dimension_id']);
        }
        $this->setStepSessionData($fourthStepData, 'fourth');
    }

    public function moveUserLogo($user)
    {
     $webPath      = $this->container->get('kernel')->getRootDir().'/../web';
     $orgImageName = $this->container->get('session')->get('tempUserIdAP');
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

     rename($webPath.'/uploads/tmp/'.$orgImageName.'.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'.jpg');
     rename($webPath.'/uploads/tmp/'.$orgImageName.'_org.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'_org.jpg');
     rename($webPath.'/uploads/tmp/'.$orgImageName.'_original.jpg', $webPath.DIRECTORY_SEPARATOR.$imagePath.DIRECTORY_SEPARATOR.$userId.'_original.jpg');

     $userImageManager = new UserImageManager($this->container, $userId, $imagePath, $isCompany);
     $userImageManager->createThumbnail();
    }

    /**
     * Edit from paa fourth step
     *
     * @param Request $request
     */
    public function ajaxEditFromPaaFourthStepAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $this->container->get('session')->set('paa_edit_url', $request->get('url'));
            return new JsonResponse(array('result' => true));
        }

        return new Response();
    }

    /**
     * Set step session data
     *
     * @param integer $adId Ad id.
     */
    public function setStepSessionDataAction($adId)
    {
        $objAd     = $this->getRepository('FaAdBundle:Ad')->find($adId);
        $className = NULL;

        if ($objAd) {
            if ($objAd->getCategory()) {
             $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($objAd->getCategory()->getId());

             // Set first step data into session
             $firstStepData                             = array();
             $firstStepData['category_id']              = $objAd->getCategory()->getId();
             $firstStepData['category_id_autocomplete'] = $objAd->getCategory()->getName();
             $this->container->get('session')->set('ad_id', $objAd->getId());
             $this->setStepSessionData($firstStepData, 'first');

             // Set fourth step data into session
             $className             = NULL;
             $secondStepFieldsArray = $this->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryAncestor($objAd->getCategory()->getId(), $this->container, 2);
             $motorsRegNoFields = $this->getMotorRegNoFields();
             if ($secondStepFieldsArray && count($secondStepFieldsArray)) {
                 foreach ($secondStepFieldsArray as $key => $valueArray) {
                     if ($valueArray['status'] == TRUE) {
                         $fieldName            = $valueArray['paa_field']['field'];
                         $fieldType            = $valueArray['paa_field']['field_type'];
                         $fieldLabel           = $valueArray['paa_field']['label'];
                         $fieldNameInCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldName)));
                         $fieldGetFunction     = 'get'.$fieldNameInCamelCase;

                         if ($fieldName == 'ad_type_id') {
                          $secondStepData[$fieldName] = ($objAd->getType() ? $objAd->getType()->getId() : null);
                         } else if ($fieldName == 'location') {
                             $objAdLocation = $this->getRepository('FaAdBundle:AdLocation')->findLocationByAdId($adId);
                                 if ($objAdLocation) {
                                     $objAdLocation = $objAdLocation[0];
                                     $secondStepData['location_lat_lng'] = $objAdLocation->getLatitude().', '.$objAdLocation->getLongitude();
                                     if ($objAdLocation->getPostcode()) {
                                         $secondStepData['location']              = $objAdLocation->getPostcode();
                                         $secondStepData['location_autocomplete'] = $objAdLocation->getPostcode();
                                     } else {
                                         $locationStr = $objAdLocation->getLocationTown()->getName() . ', ' . $objAdLocation->getLocationDomicile()->getName();
                                         $secondStepData['location']              = $objAdLocation->getLocationTown()->getId();
                                         $secondStepData['location_autocomplete'] = $locationStr;
                                     }
                                 }
                         } else if ($fieldName == 'delivery_method_option_id') {
                             $secondStepData[$fieldName] = $objAd->$fieldGetFunction();
                             if ($objAd->getPostagePrice()) {
                                 $secondStepData['postage_price'] = $objAd->getPostagePrice();
                             }
                         }  else if ($fieldName == 'payment_method_id') {
                             $secondStepData[$fieldName] = $objAd->$fieldGetFunction();
                             if ($objAd->getUser()->getPaypalEmail()) {
                                 $secondStepData['paypal_email'] = $objAd->getUser()->getPaypalEmail();
                             }
                             if ($objAd->getUser()->getPaypalFirstName()) {
                              $secondStepData['paypal_first_name'] = $objAd->getUser()->getPaypalFirstName();
                             }
                             if ($objAd->getUser()->getPaypalLastName()) {
                              $secondStepData['paypal_last_name'] = $objAd->getUser()->getPaypalLastName();
                             }
                         } else {
                             if (method_exists($objAd, $fieldGetFunction) === true) {
                                 $secondStepData[$fieldName] = $objAd->$fieldGetFunction();
                             } else {
                                   if ($className == NULL) {
                                       $className               = CommonManager::getCategoryClassNameById($rootCategoryId, true);
                                       $objAdCategoryRepository = $this->getRepository('FaAdBundle:Ad'.$className);
                                       $objAdCategory           = null;
                                       $objAdCategory           = $objAdCategoryRepository->findOneBy(array('ad' => $adId));
                                       $metaData                = ($objAdCategory->getMetaData() ? unserialize($objAdCategory->getMetaData()) : null);
                                   }

                                   if ($fieldType == 'text_autosuggest') {
                                       $fieldValue = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, true);
                                       if ($fieldValue != NULL) {
                                           $secondStepData[$fieldName.'_autocomplete'] = $fieldValue;
                                           $secondStepData[$fieldName] = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, false);
                                           $categoryDimensionId = $this->getEntityManager()->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategoryHierarchy($firstStepData['category_id'], $fieldLabel, $this->container);
                                           $secondStepData[$fieldName.'_dimension_id'] = $categoryDimensionId;

                                           if (is_array($secondStepData[$fieldName]) && count($secondStepData[$fieldName])) {
                                               $secondStepData[$fieldName] = $secondStepData[$fieldName][0];
                                           }
                                       }
                                   } else {
                                       $fieldValue = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, false);
                                       if ($fieldValue != NULL) {
                                           $secondStepData[$fieldName] = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, false);
                                       }
                                   }

                                   if ($valueArray['paa_field']['field_type'] == 'choice_single') {
                                       $dataArray = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, false);
                                       if ($dataArray && count($dataArray)) {
                                           $dataStr = $secondStepData[$fieldName] = implode(',', $dataArray);
                                           $secondStepData[$fieldName] = $dataStr;
                                       }
                                   }
                               }
                         }

                         if ($fieldName == 'location' || $fieldType == 'text_autosuggest') {
                             $secondStepData['second_step_ordered_fields'][] = $fieldName.'_autocomplete';
                         } else {
                             $secondStepData['second_step_ordered_fields'][] = $fieldName;
                         }
                     }
                   }

              $secondStepData['second_step_ordered_fields'] = implode(',', $secondStepData['second_step_ordered_fields']);
              $this->setStepSessionData($secondStepData, 'second');
             }

             // Set fourth step data into session
             $className             = NULL;
             $fourthStepFieldsArray = $this->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryAncestor($objAd->getCategory()->getId(), $this->container, 4);
             if ($fourthStepFieldsArray && count($fourthStepFieldsArray)) {
                 foreach ($fourthStepFieldsArray as $key => $valueArray) {
                     if ($valueArray['status'] == TRUE) {
                         $fieldName            = $valueArray['paa_field']['field'];
                         $fieldType            = $valueArray['paa_field']['field_type'];
                         $fieldLabel           = $valueArray['paa_field']['label'];
                         $fieldNameInCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldName)));
                         $fieldGetFunction     = 'get'.$fieldNameInCamelCase;
                         if ($fieldName == 'location') {
                             $objAdLocation = $this->getRepository('FaAdBundle:AdLocation')->findLocationByAdId($adId);
                             if ($objAdLocation) {
                                 $objAdLocation = $objAdLocation[0];
                                 $fourthStepData['location_lat_lng'] = $objAdLocation->getLatitude().', '.$objAdLocation->getLongitude();
                                 if ($objAdLocation->getPostcode()) {
                                     $fourthStepData['location']              = $objAdLocation->getPostcode();
                                     $fourthStepData['location_autocomplete'] = $objAdLocation->getPostcode();
                                 } else {
                                     $locationStr = $objAdLocation->getLocationTown()->getName() . ', ' . $objAdLocation->getLocationDomicile()->getName();
                                     $fourthStepData['location']              = $objAdLocation->getLocationTown()->getId();
                                     $fourthStepData['location_autocomplete'] = $locationStr;
                                 }
                             } else {
                                 $fourthStepData['location_lat_lng'] = '55.37874009999999, -3.4612489999999525';
                             }
                         } else if ($fieldName == 'delivery_method_option_id') {
                             if ($objAd->$fieldGetFunction()) {
                                 $fourthStepData[$fieldName] = $objAd->$fieldGetFunction();
                             } else {
                                 $fourthStepData[$fieldName] = DeliveryMethodOptionRepository::COLLECTION_ONLY_ID;
                             }
                             if ($objAd->getPostagePrice()) {
                                 $fourthStepData['postage_price'] = $objAd->getPostagePrice();
                             }
                         }  else if ($fieldName == 'payment_method_id') {
                             if ($objAd->$fieldGetFunction()) {
                                 $fourthStepData[$fieldName] = $objAd->$fieldGetFunction();
                             } else {
                                 $fourthStepData[$fieldName] = PaymentRepository::PAYMENT_METHOD_CASH_ON_COLLECTION_ID;
                             }
                             if ($objAd->getUser()->getPaypalEmail()) {
                                 $fourthStepData['paypal_email'] = $objAd->getUser()->getPaypalEmail();
                             }
                             if ($objAd->getUser()->getPaypalFirstName()) {
                              $fourthStepData['paypal_first_name'] = $objAd->getUser()->getPaypalFirstName();
                             }
                             if ($objAd->getUser()->getPaypalLastName()) {
                              $fourthStepData['paypal_last_name'] = $objAd->getUser()->getPaypalLastName();
                             }
                         } else if ($fieldName == 'qty') {
                             if ($objAd->$fieldGetFunction()) {
                                 $fourthStepData[$fieldName] = $objAd->$fieldGetFunction();
                             } else {
                                 $fourthStepData[$fieldName] = 1;
                             }
                         } else {
                             if (method_exists($objAd, $fieldGetFunction) === true) {
                                 $fourthStepData[$fieldName] = $objAd->$fieldGetFunction();
                             } else {
                                   if ($className == NULL) {
                                       $className               = CommonManager::getCategoryClassNameById($rootCategoryId, true);
                                       $objAdCategoryRepository = $this->getRepository('FaAdBundle:Ad'.$className);
                                       $objAdCategory           = null;
                                       $objAdCategory           = $objAdCategoryRepository->findOneBy(array('ad' => $adId));
                                       $metaData                = ($objAdCategory->getMetaData() ? unserialize($objAdCategory->getMetaData()) : null);
                                   }

                                   if ($fieldType == 'text_autosuggest') {
                                       $fieldValue = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, true);
                                       if ($fieldValue != NULL) {
                                           $fourthStepData[$fieldName.'_autocomplete'] = $fieldValue;
                                           $fourthStepData[$fieldName] = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, false);
                                           $categoryDimensionId = $this->getEntityManager()->getRepository('FaEntityBundle:CategoryDimension')->getDimensionIdByNameAndCategoryHierarchy($firstStepData['category_id'], $fieldLabel, $this->container);
                                           $fourthStepData[$fieldName.'_dimension_id'] = $categoryDimensionId;
                                           if (is_array($fourthStepData[$fieldName]) && count($fourthStepData[$fieldName])) {
                                               $fourthStepData[$fieldName] = $fourthStepData[$fieldName][0];
                                           }
                                       }
                                   } else {
                                       $fieldValue = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, false);
                                       if ($fieldValue != NULL) {
                                           $fourthStepData[$fieldName] = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, false);
                                       }
                                   }

                                   if ($valueArray['paa_field']['field_type'] == 'choice_single') {
                                       $dataArray = $this->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($fieldName, $objAdCategory, $metaData, $this->container, $className, false, false);
                                       if ($dataArray && count($dataArray)) {
                                           $dataStr = $fourthStepData[$fieldName] = implode(',', $dataArray);
                                           $fourthStepData[$fieldName] = $dataStr;
                                       }
                                   }
                               }
                         }

                         if ($fieldName == 'location' || $fieldType == 'text_autosuggest') {
                             $fourthStepData['fourth_step_ordered_fields'][] = $fieldName.'_autocomplete';
                         } else {
                             $fourthStepData['fourth_step_ordered_fields'][] = $fieldName;
                         }
                     }
                   }
                   //set first step data for motor reg no.
                   if (in_array('has_reg_no', array_keys($secondStepData))) {
                       $firstStepDataForMotorsRegNo = array();
                       $motorsRegNoFields = $this->getMotorRegNoFields();
                       foreach ($motorsRegNoFields as $motorsRegNoField) {
                           if (array_key_exists($motorsRegNoField, $secondStepData)) {
                               $firstStepDataForMotorsRegNo[$motorsRegNoField] = $secondStepData[$motorsRegNoField];
                           } else {
                               $firstStepDataForMotorsRegNo[$motorsRegNoField] = null;
                           }
                       }

                       if (count($firstStepDataForMotorsRegNo)) {
                           $firstStepDataForMotorsRegNo['first_step_ordered_fields'] = implode(',', array_keys($firstStepDataForMotorsRegNo));
                           $firstStepDataForMotorsRegNo = $firstStepDataForMotorsRegNo + $this->getStepSessionData('first');
                           $this->setStepSessionData($firstStepDataForMotorsRegNo, 'first');
                       }
                   }
              $fourthStepData['fourth_step_ordered_fields'] = implode(',', $fourthStepData['fourth_step_ordered_fields']);
              $this->setStepSessionData($fourthStepData, 'fourth');
             }

             return $this->redirect($this->generateUrl('ad_post_second_step', array('is_edit' => 1)));
           }
        }
    }

    /**
     * This action is used to remember draft ad popup is opened in last one hour in cookies.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function openDraftAdPopupAjaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new Response();
            $response->headers->setCookie(new Cookie('draft_ad_popup',1, time() + (3600 * 1)));
            $response->sendHeaders();
            return new JsonResponse(array('response' => TRUE));
        }

        return new JsonResponse(array('response' => FALSE));
    }

    /**
     * Get step data to render on template.
     *
     * @return array
     */
    public function getMotorRegNoFields()
    {
        return array(
            'has_reg_no',
            'reg_no',
            'colour_id_autocomplete',
            'colour_id_dimension_id',
            'colour_id',
            'body_type_id',
            'reg_year',
            'fuel_type_id',
            'transmission_id',
            'engine_size',
            'no_of_doors',
            'no_of_seats',
            'fuel_economy',
            '062mph',
            'top_speed',
            'ncap_rating',
            'co2_emissions',
            'first_step_ordered_fields',
        );
    }
    
    /**
     * This action is used to change the Adult Escort Category to Gay Male Escort Category when gender is Male and My service id is Men.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function changeAdultCategoryFourthStepAction(Request $request) {
    	if ($request->isXmlHttpRequest()) {
    		$response = new Response();
    		$em       = $this->getEntityManager();
    		//change Category From Escort Service to Gay Male Escort Category
    		$firstSessionData = unserialize($this->container->get('session')->get('paa_first_step_data'));
    		//updating Session Category
    		if(isset($firstSessionData['category_id']) && !empty($firstSessionData)) {
    			$categoryObj = $this->getRepository('FaEntityBundle:Category')->getCategorybyName(CategoryRepository::GAY_MALE_ESCORT_NAME);
    			if(!empty($categoryObj)) {
    				//update Ad Category in DB
    				$ad = $this->getRepository('FaAdBundle:Ad')->find($this->container->get('session')->get('ad_id'));
    				if(!empty($ad)) {
    					$ad->setCategory($this->getEntityManager()->getReference('FaEntityBundle:Category', $categoryObj['id']));
    					$em->persist($ad);
    					$em->flush();
		    			$firstSessionData['category_id'] = $categoryObj['id'];
		    			$firstSessionData['category_id_autocomplete'] = $categoryObj['name'];
		    			//Set first step data into session
		    			$this->setStepSessionData($firstSessionData, 'first');
		    			return new JsonResponse(array('response' => TRUE));
    				}
    			}
    		}
    		
    	}
    	
    	
    	return new JsonResponse(array('response' => FALSE));
    }
}
