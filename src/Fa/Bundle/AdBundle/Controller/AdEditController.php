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
use Symfony\Component\BrowserKit\Response;
use Fa\Bundle\UserBundle\Event\FormEvent;
use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\UserBundle\Event\FilterUserResponseEvent;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Symfony\Component\Form\FormError;

/**
 * This controller is used for ad edit.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdEditController extends CoreController
{
     /**
     * Displays a form to create a new record.
     *
     * @param Request $request  A Request object.
     * @param integer $id       Ad id.
     *
     */
    public function editAction(Request $request, $id)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $loggedinUser = $this->getLoggedInUser();
        $ad           = $this->getRepository('FaAdBundle:Ad')->find($id);

        if (!$ad) {
            return $this->handleMessage($this->get('translator')->trans('No ad exists which you want to edit.', array(), 'frontend-ad-edit'), 'manage_my_ads_active', array(), 'error');
        }

        if ($loggedinUser->getId() != $ad->getUser()->getId()) {
            return $this->handleMessage($this->get('translator')->trans('You can not edit other user\'s ad.', array(), 'frontend-ad-edit'), 'manage_my_ads_active', array(), 'error');
        }

        if ($ad->getStatus()->getId() == EntityRepository::AD_STATUS_INACTIVE_ID) {
            return $this->handleMessage($this->get('translator')->trans('Ad has been deleted which you want to edit.', array(), 'frontend-ad-edit'), 'manage_my_ads_active', array(), 'error');
        }

        if ($ad->getFuturePublishAt()) {
            return $this->handleMessage($this->get('translator')->trans('You can not edit future ads, please contact administrator.', array(), 'frontend-ad-edit'), 'manage_my_ads_active', array(), 'error');
        }

        $adCategoryId = $ad->getCategory()->getId();
        $formManager  = $this->get('fa.formmanager');
        $formName     = $this->getFormName($adCategoryId);
        $form         = $formManager->createForm($formName, array('ad' => $ad), array('action' => $this->generateUrl('ad_edit', array('id' => $ad->getId()))));

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
            	//check location is valid
            	$locationExist = $this->getEntityManager()->getRepository('FaAdBundle:AdLocation')->findLastAdLocationById($ad->getId());
            	if($locationExist == null && $form->has('location_autocomplete')) { 
            		$form->get('location_autocomplete')->addError(new FormError('Location is invalid.'));
            	} else {
                // Rediret to ad for package selection.
	                if (in_array($ad->getStatus()->getId(), $this->getRepository('FaAdBundle:Ad')->getRepostButtonInEditAdStatus()) || $ad->getStatus()->getId() == EntityRepository::AD_STATUS_DRAFT_ID) {
	                    return $this->handleMessage($this->get('translator')->trans('Your advert %advert_title% has been updated.', array('%advert_title%' => '<i>'.$ad->getTitle().'</i>'), 'success'), 'ad_package_purchase', array('adId' => $ad->getId()));
	                }
	
	                if (in_array($ad->getStatus()->getId(), array(EntityRepository::AD_STATUS_LIVE_ID, EntityRepository::AD_STATUS_IN_MODERATION_ID))) {
	                    return $this->handleMessage($this->get('translator')->trans('Your advert %advert_title% has been updated.', array('%advert_title%' => '<i>'.$ad->getTitle().'</i>'), 'success'), 'manage_my_ads_active');
	                }
	
	                return $this->handleMessage($this->get('translator')->trans('Your advert %advert_title% has been updated.', array('%advert_title%' => '<i>'.$ad->getTitle().'</i>'), 'success'), 'manage_my_ads_active');
            	}
            }
        }

        $parameters  = array(
                           'ad'      => $ad,
                           'form'    => $form->createView(),
                           'heading' => $this->get('translator')->trans('Edit ad', array(), 'frontend-ad-edit'),
        );

        return $this->render($this->getTemplateName($adCategoryId), $parameters);
    }

    /**
     * Get category wise edit ad form name.
     *
     * @param integer $categoryId Category id.
     * @return string
     */
    private function getFormName($categoryId)
    {
        $formName      = '';
        $categoryName  = $this->getRootCategoryName($categoryId);

        if ($categoryName) {
            $formName = 'fa_paa_edit_'.$categoryName;
        }

        return $formName;
    }

    /**
     * Get root category name by lowercaseing it.
     *
     * @param integer $categoryId Category id.
     *
     * @return mixed
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
     *
     * @return string
     */
    private function getTemplateName($categoryId)
    {
        $templateName = '';
        $categoryName = $this->getRepository('FaEntityBundle:Category')->getRootCategoryName($categoryId, $this->container);

        if ($categoryName) {
            $templateName = 'FaAdBundle:AdEdit:'.str_replace('_','', $categoryName).'.html.twig';
        }

        return $templateName;
    }
}
