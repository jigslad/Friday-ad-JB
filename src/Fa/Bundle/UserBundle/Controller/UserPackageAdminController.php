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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\UserBundle\Form\ResetPasswordType;
use Fa\Bundle\UserBundle\Event\FormEvent;
use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Form\EventListener\AddCategoryChoiceFieldSubscriber;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvents;
use Fa\Bundle\UserBundle\Form\UserPackageAdminType;

/**
 * This controller is used for handling user packages.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPackageAdminController extends CoreController
{

    /**
     * Display package action.
     *
     * @param integer $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function displayPackageAction($id, Request $request)
    {
        $formManager = $this->get('fa.formmanager');
        $user = $this->getRepository('FaUserBundle:User')->find($id);

        try {
            if (!$user) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'shop_package_update');
        }

        $mainForm = $request->get('fa_user_package_admin');
        $catForm = $request->get('form');
        $selis_auto_renew = $this->getRepository('FaUserBundle:UserPackage')->checkIsAutoRenewedPackage($id);
        
        $catArray = $this->getRepository('FaEntityBundle:Category')->getCategoryByLevelArray(1, $this->container);

        if (isset($catForm['category_1']) && in_array($catForm['category_1'], array_keys($catArray))) {
            $userBusinessCategoryId = $catForm['category_1'];
        } elseif (isset($mainForm['category_id']) && in_array($mainForm['category_id'], array_keys($catArray))) {
            $userBusinessCategoryId = $mainForm['category_id'];
        } else {
            $userBusinessCategoryId = $user->getBusinessCategoryId() > 0 ? $user->getBusinessCategoryId() : CategoryRepository::FOR_SALE_ID;
        }

        if (isset($catForm['is_auto_renew']) && $catForm['is_auto_renew']) {
            $is_auto_renew = $catForm['is_auto_renew'];
        } else {
            $is_auto_renew = ($selis_auto_renew==1)?$selis_auto_renew:0;
        }

        $options =  array(
                'action' => $this->generateUrl('user_package_admin', array('id' => $user->getId())),
                'method' => 'PUT'
        );

        $form = $formManager->createForm(UserPackageAdminType::class, array('user_id' => $user->getId(), 'category_id' => $userBusinessCategoryId, 'is_auto_renew' => $is_auto_renew, $options));
        $userRoleId = $user->getRole()->getId();
        $shopPackages = $this->getRepository('FaPromotionBundle:Package')->getShopPackageByCategory($userBusinessCategoryId, $userRoleId, null, false);
        $currentPackage = $this->getRepository('FaUserBundle:UserPackage')->getCurrentActivePackage($user);

        $cat_options =  array(
                'action' => $this->generateUrl('user_package_admin', array('id' => $user->getId())),
                'method' => 'GET',
                'attr'   => array('id' => 'cat-form')
        );

        $catFormBuilder = $this->createFormBuilder(null, $cat_options)
        ->add('is_auto_renew', ChoiceType::class, array('label' => 'Auto-renew', 'data' => $is_auto_renew, 'choices'  => EntityRepository::getYesNoArray($this->container, false), 'mapped' => false));

        $totalLevel   = 1;
        $categoryPath = array();
        if (in_array($userBusinessCategoryId, array(CategoryRepository::SERVICES_ID, CategoryRepository::ADULT_ID))) {
            $catFormBuilder
            ->add(
                'zip',
                TextType::class,
                array(
                    'mapped' => false,
                    'data' => $user->getZip(),
                    'constraints' => array(
                        new NotBlank(array('message' => "Please enter zip.")),
                    )
                )
            );
            $catFormBuilder->addEventListener(
                FormEvents::SUBMIT,
                function ($event) {
                    $form     = $event->getForm();
                    $postCode = trim($form->get('zip')->getData());
                    if ($postCode) {
                        $postCodeObj = $this->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                        if (!$postCodeObj) {
                            $event->getForm()->get('zip')->addError(new \Symfony\Component\Form\FormError('Please enter valid postcode.'));
                        }
                    }

                    if ($form->get('category_last_level')->getData()) {
                        for ($i = 1; $i <= $form->get('category_last_level')->getData(); $i++) {
                            if (!$form->get('category_'.$i)->getData()) {
                                $event->getForm()->get('category_'.$i)->addError(new \Symfony\Component\Form\FormError('Please select category.'));
                            }
                        }
                    }
                }
            );
            
            $mainForm['zip'] = (isset($catForm['zip']) ? $catForm['zip'] : null);
            $mainForm['is_auto_renew'] = (isset($catForm['is_auto_renew']) ? $catForm['is_auto_renew'] : ($selis_auto_renew==1?$selis_auto_renew:0));
            $mainForm['profile_exposure_category_id'] = (isset($catForm['category_last_level']) && isset($catForm['category_'.$catForm['category_last_level']]) ? $catForm['category_'.$catForm['category_last_level']] : null);
            $totalLevel   = $this->getRepository('FaEntityBundle:Category')->getMaxLevel();
            $userSite     = $this->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user->getId()));
            if ('POST' === $request->getMethod()) {
                if (isset($catForm['category_last_level'])) {
                    for ($i = $catForm['category_last_level']; $i >= 1; $i--) {
                        if (isset($catForm['category_'.$i]) && $catForm['category_'.$i]) {
                            $categoryPath = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($catForm['category_'.$i], false, $this->container));
                            break;
                        }
                    }
                }
            } elseif ($userSite && $userSite->getProfileExposureCategoryId()) {
                $categoryPath = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($userSite->getProfileExposureCategoryId(), false, $this->container));
                if (!in_array($userBusinessCategoryId, $categoryPath)) {
                    $categoryPath = array();
                }
            }
        }
        $catFormBuilder->add('category_last_level', HiddenType::class, array('mapped' => false, 'data' => count($categoryPath)));
        if ($totalLevel) {
            for ($i = 1; $i <= $totalLevel; $i++) {
                if ($i == 1) {
                    $optionArray = array(
                        'placeholder' => 'Please select category',
                        'attr'        => array('class' => 'category category_'.$i),
                        'label'       => 'Category',
                        'data'        => $userBusinessCategoryId,
                        'required'    => true,
                    );
                } else {
                    $choices = array();
                    $data    = null;
                    $constraints = array();
                    if ($totalLevel > 1) {
                        if (!count($categoryPath) && $i == 2) {
                            $choices = array('' => 'Please select subcategory') + $this->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($userBusinessCategoryId);
                            $constraints = array(
                                new NotBlank(array('message' => "Please select category.")),
                            );
                        } elseif (isset($categoryPath[$i-1]) && isset($categoryPath[$i-2])) {
                            $choices = array('' => 'Please select subcategory') + $this->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($categoryPath[$i-2]);
                            $data    = isset($categoryPath[$i-1]) ? $categoryPath[$i-1] : null;
                            $constraints = array(
                                new NotBlank(array('message' => "Please select category.")),
                            );
                        } elseif (isset($categoryPath[$i-2])) {
                            $choices = $this->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($categoryPath[$i-2]);
                            if (count($choices)) {
                                $choices = array('' => 'Please select subcategory') + $choices;
                            }
                        }
                    }
                    $optionArray = array(
                        'placeholder' => 'Please select subcategory',
                        'attr'        => array('class' => 'category category_'.$i),
                        'label'       => 'Sub-category',
                        'choices'     => $choices,
                        'data'        => $data,
                        'constraints' => $constraints,
                        'required'    => true,
                    );
                }
                $catFormBuilder->addEventSubscriber(
                    new AddCategoryChoiceFieldSubscriber(
                        $this->container,
                        $i,
                        'category',
                        $optionArray,
                        null,
                        $totalLevel
                    )
                );
            }
        }
        $catFormObj = $catFormBuilder->getForm();

        if ('POST' === $request->getMethod()) {
            $catFormObj->submit($catForm);

            $isValidCatForm = $catFormObj->isValid();
            $mainForm['isValidCatForm'] = $isValidCatForm;
            $form->submit($mainForm);
            if ($form->isValid() && $isValidCatForm) {
                return $this->handleMessage($this->get('translator')->trans('Subscription package assigned to successfully.'), 'user_admin');
            }
        }

        $parameters = array(
            'user'  => $user,
            'currentPackage'  => $currentPackage,
            'shopPackages'  => $shopPackages,
            'form'    => $form->createView(),
            'catForm'    => $catFormObj->createView(),
            'heading' => $this->get('translator')->trans('Assign subscription package'),
            'totalLevel' => $totalLevel,
            'categoryPath' => $categoryPath,
        );

        return $this->render('FaUserBundle:UserPackageAdmin:edit.html.twig', $parameters);
    }
}
