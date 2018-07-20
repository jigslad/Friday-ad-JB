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
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\UserBundle\Entity\UserConfigRule;
use Fa\Bundle\UserBundle\Repository\UserConfigRuleRepository;
use Fa\Bundle\UserBundle\Form\UserConfigRuleAdminType;

/**
 * This controller is used for entity management.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserConfigRuleAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'user_config_rule';
    }

    /**
     * Lists all config rules.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
    }

    /**
     * Displays a form to create a add/edit record.
     *
     * @param integer $user_id   User id.
     * @param integer $config_id Config id.
     * @param Request $request   A Request object.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function addEditAction($user_id, $config_id, Request $request)
    {
        $user   = $this->getRepository('FaUserBundle:User')->find($user_id);
        $config = $this->getRepository('FaCoreBundle:Config')->find($config_id);

        try {
            if (!$user) {
                throw $this->createNotFoundException('Unable to find user entity.');
            }

            if (!$config) {
                throw $this->createNotFoundException('Unable to find config entity.');
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        //return $this->redirect($this->generateUrl($this->getRouteName(), $params));

        $entity = $this->getRepository('FaUserBundle:UserConfigRule')->findOneBy(array('config' => $config_id, 'user' => $user_id));
        if ($entity) {
            return $this->redirectToRoute('user_config_rule_edit_admin', array('user_id' => $user_id, 'config_id' => $config_id, 'id' => $entity->getId()));
        }

        return $this->redirectToRoute('user_config_rule_new_admin', array('user_id' => $user_id, 'config_id' => $config_id));
    }

    /**
     * Creates a new record.
     *
     * @param Request $request A Request object.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function createAction(Request $request)
    {
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getEntity();
        $options     = array(
            'action' => $this->generateUrl($this->getRouteName('create')),
            'method' => 'POST'
        );
        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form = $formManager->createForm($fqn, $entity, $options);
        if ($formManager->isValid($form)) {
            if (!$this->saveNewUsingForm) {
                $formManager->save($entity);
            }

            return $this->handleMessage($this->get('translator')->trans('Record has been added successfully.', array(), 'success'), 'user_admin');
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Edits an existing record.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function updateAction(Request $request, $id)
    {
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        $options = array(
            'action' => $this->generateUrl($this->getRouteName('update'), array('id' => $entity->getId())),
            'method' => 'PUT'
        );
        
        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form = $formManager->createForm($fqn, $entity, $options);

        if ($formManager->isValid($form)) {
            if (!$this->saveEditUsingForm) {
                $this->getEntityManager()->flush();
            }

            return $this->handleMessage($this->get('translator')->trans('Record has been updated successfully.', array(), 'success'), 'user_admin');
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit %displayword%', array('%displayword%' => $this->getDisplayWord())),
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Deletes a record.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @throws NotFoundHttpException
     * @return RedirectResponse A RedirectResponse object.
     */
    public function deleteAction(Request $request, $id)
    {
        $deleteManager = $this->get('fa.deletemanager');
        $entity        = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        try {
            $deleteManager->delete($entity);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), $this->getRouteName(''), array(), 'error');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', $this->getRouteName(''));
        }

        return parent::handleMessage($this->get('translator')->trans('Record has been deleted successfully.', array(), 'success'), 'user_admin');
    }
    
    public function getFQNForForms($formName)
    {
        // getName() symfony form function is removed After symfony 3, so to handle dynamic forms we need create array
        $formClassArray = [
            'fa_user_user_config_rule_admin' => UserConfigRuleAdminType::class
        ];
        $formName = isset($formClassArray[$formName]) ? $formClassArray[$formName] : $formName;
        return $formName;
    }
}
