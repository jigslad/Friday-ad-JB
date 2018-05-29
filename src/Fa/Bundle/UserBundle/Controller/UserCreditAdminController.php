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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Entity\UserCredit;
use Fa\Bundle\UserBundle\Repository\UserCreditRepository;
use Fa\Bundle\UserBundle\Form\UserCreditSearchAdminType;
use Fa\Bundle\UserBundle\Form\UserCreditAdminType;

/**
 * This controller is used for user credit admin.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserCreditAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all user credits assigend.
     *
     * @param integer $userId  User id.
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function indexAction($userId, Request $request)
    {
        CommonManager::removeAdminBackUrl($this->container);
        $user = $this->getRepository('FaUserBundle:User')->find($userId);
        try {
            if (!$user) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaUserBundle:UserCredit'), $this->getRepositoryTable('FaUserBundle:UserCredit'), 'fa_user_user_credit_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('user_credit' => array('id', 'credit', 'package_sr_no', 'status'), 'category' => array('id as category_id'));
        $data['static_filters'] = UserCreditRepository::ALIAS.'.user = '.$userId;

        $data['query_joins']   = array(
            'user_credit' => array(
                'category' => array('type' => 'left', 'condition_type' => 'WITH'),
            ),
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaUserBundle:UserCredit'), $data);
        $query = $this->get('fa.sqlsearch.manager');
        $query = $query->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();


        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserCreditSearchAdminType::class, null, array('action' => $this->generateUrl('user_credit_admin', array('userId' => $userId)), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'user'             => $user,
            'statusArray'      => EntityRepository::getStatusArray($this->container),
            'heading'          => $this->get('translator')->trans('User detail'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaUserBundle:UserAdmin:show.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new credits.
     *
     * @param integer $userId  User id.
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function newAction($userId, Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new UserCredit();
        $entity->setUser($this->getEntityManager()->getReference('FaUserBundle:User', $userId));

        $form = $formManager->createForm(UserCreditAdminType::class, $entity, array('action' => $this->generateUrl('user_credit_create_admin', array('userId' => $userId))));

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Add credits'),
                      );

        return $this->render('FaUserBundle:UserCreditAdmin:new.html.twig', $parameters);
    }

    /**
     * Creates a new credits.
     *
     * @param integer $userId  User id.
     * @param Request $request A Request object.
     *
     * @return array
     */
    public function createAction($userId, Request $request)
    {
        $backUrl = CommonManager::getAdminCancelUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new UserCredit();
        $entity->setUser($this->getEntityManager()->getReference('FaUserBundle:User', $userId));

        $options =  array(
            'action' => $this->generateUrl('user_credit_create_admin', array('userId' => $userId)),
            'method' => 'POST'
        );

        $form = $formManager->createForm(UserCreditAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            return parent::handleMessage($this->get('translator')->trans('New user credits was successfully added.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'user_credit_new_admin' : ($backUrl ? $backUrl : 'user_admin')));

        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Add credits'),
        );

        return $this->render('FaUserBundle:UserCreditAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing credits.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaUserBundle:UserCredit')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user credit.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'user_credit_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('user_credit_update_admin', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(UserCreditAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit user credit'),
        );

        return $this->render('FaUserBundle:UserCreditAdmin:new.html.twig', $parameters);
    }

    /**
     * Edits an existing credits.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminCancelUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository('FaUserBundle:UserCredit')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user credit.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'user_credit_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('user_credit_update_admin', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(UserCreditAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            return parent::handleMessage($this->get('translator')->trans('User credit was successfully updated.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'user_credit_new_admin' : ($backUrl ? $backUrl : 'user_credit_admin')));
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit user credit'),
        );

        return $this->render('FaUserBundle:UserCreditAdmin:new.html.twig', $parameters);
    }

    /**
     * Deletes a credits.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function deleteAction(Request $request, $id, $userId)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $backUrl = CommonManager::getAdminCancelUrl($this->container);
        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaUserBundle:UserCredit')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user credit.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'user_credit_admin', array('userId' => $userId));
        }

        try {
            $deleteManager->delete($entity);
        } catch (\Doctrine\DBAL\DBALException $e) {
            if ($e->getCode() == 0)
            {
                if ($e->getPrevious()->getCode() == 23000)
                {
                    return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), 'user_credit_admin', array('userId' => $userId), 'error');
                }
                else
                {
                    return parent::handleException($e, 'error', 'user_credit_admin', array('userId' => $userId));
                }
            }
            else
            {
                return parent::handleException($e, 'error', 'user_credit_admin', array('userId' => $userId));
            }
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', 'user_credit_admin', array('userId' => $userId));
        }

        return parent::handleMessage($this->get('translator')->trans('User credit was successfully deleted.', array(), 'success'), ($backUrl ? $backUrl : 'user_credit_admin'), (!$backUrl ? array('userId' => $userId): array()));
    }
}
