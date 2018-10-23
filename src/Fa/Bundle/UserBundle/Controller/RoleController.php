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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Entity\Role;
use Fa\Bundle\UserBundle\Form\RoleType;
use Fa\Bundle\UserBundle\Form\RoleSearchType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This controller is used for admin side role management.
 *
 * @author Atul Kamani <atul@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class RoleController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists Role entities.
     *
     * @return Response A Response object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaUserBundle:Role'), $this->getRepositoryTable('FaUserBundle:Role'));
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array('role' => array('id', 'name', 'type'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaUserBundle:Role'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(RoleSearchType::class, null, array('action' => $this->generateUrl('role')));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => 'Roles',
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaUserBundle:Role:index.html.twig', $parameters);
    }

    /**
     * Creates a new Role entity.
     *
     * @param Request $request A Request object.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function createAction(Request $request)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Role();

        $options =  array(
                      'action' => $this->generateUrl('role_create'),
                      'method' => 'POST'
                    );

        $form = $formManager->createForm(RoleType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            return parent::handleMessage('Role was successfully added.', ($form->get('saveAndNew')->isClicked() ? 'role_new' : 'role'));

        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => 'New role',
                      );

        return $this->render('FaUserBundle:Role:new.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new Role entity.
     *
     * @return Response A Response object.
     */
    public function newAction()
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Role();

        $form = $formManager->createForm(RoleType::class, $entity, array('action' => $this->generateUrl('role_create')));

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => 'New role',
                      );

        return $this->render('FaUserBundle:Role:new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing Role entity.
     *
     * @param integer $id Id.
     *
     * @return Response A Response object.
     */
    public function editAction($id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaUserBundle:Role')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Role entity.');
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'role');
        }

        if ($entity && $entity->getType() == 'P') {
            throw new AccessDeniedHttpException('You do not have permission to edit this Role.');
        }
        $options =  array(
            'action' => $this->generateUrl('role_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(RoleType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => 'Edit role',
                      );

        return $this->render('FaUserBundle:Role:new.html.twig', $parameters);
    }

    /**
     * Edits an existing Role entity.
     *
     * @param Request $request A Request object.
     * @param Integer $id      Role id.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function updateAction(Request $request, $id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaUserBundle:Role')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Role entity.');
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'role');
        }

        $options =  array(
            'action' => $this->generateUrl('role_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(RoleType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            return parent::handleMessage('Role was successfully added.', ($form->get('saveAndNew')->isClicked() ? 'role_new' : 'role'));
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => 'Edit role',
                      );

        return $this->render('FaUserBundle:Role:new.html.twig', $parameters);
    }

    /**
     * Deletes a Role entity.
     *
     * @param Request $request A Request object.
     * @param Integer $id      Role id.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function deleteAction(Request $request, $id)
    {
        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaUserBundle:Role')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Role entity.');
            }
            if ($entity && $entity->getType() == 'P') {
                throw new AccessDeniedHttpException('You do not have permission to delete this Role.');
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'role');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', 'role');
        }

        $deleteManager->delete($entity);

        return parent::handleMessage('Role was successfully deleted.', 'role');
    }

    /**
     * Get unset form fields.
     *
     * @return array
     */
    protected function getUnsetFormFields()
    {
        $fields = array(
                    'users',
                    'created_at',
                    'updated_at',
                  );

        return $fields;
    }

    /**
     * Add fields to form.
     *
     * @param object $form Form object.
     */
    protected function addFormFields($form)
    {
        $form->add('save', SubmitType::class);
        $form->add('saveAndNew', SubmitType::class);
    }
}
