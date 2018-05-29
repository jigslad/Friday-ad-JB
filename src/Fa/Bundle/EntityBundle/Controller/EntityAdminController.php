<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Entity\Entity;
use Fa\Bundle\EntityBundle\Form\EntityType;
use Fa\Bundle\EntityBundle\Form\EntitySearchType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This controller is used for entity management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 * @version v1.0
 */
class EntityAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all entities.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaEntityBundle:Entity'), $this->getRepositoryTable('FaEntityBundle:Entity'), 'entity_admin_search');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('entity' => array('id', 'name'), 'category_dimension' => array('name as category_dimension_name'));
        $data['query_joins']    = array('entity' => array('category_dimension' => array('type' => 'left')));
        $data['static_filters'] = \Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository::ALIAS.'.category IS NULL';

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaEntityBundle:Entity'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(EntitySearchType::class, null, array('action' => $this->generateUrl('entity'), 'method' => 'GET', 'em' => $this->getEntityManager()));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'entityTypeArray' => $this->getRepository('FaEntityBundle:CategoryDimension')->getDimesionByCategoryArray(),
            'heading'         => $this->get('translator')->trans('Entities'),
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
        );

        return $this->render('FaEntityBundle:Entity:index.html.twig', $parameters);
    }

    /**
     * Creates a new entity.
     *
     * @param Request $request Request instance.
     *
     * @return Response A Response object.
     */
    public function createAction(Request $request)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Entity();

        $options =  array(
                      'em' => $this->getEntityManager(),
                      'action' => $this->generateUrl('entity_create'),
                      'method' => 'POST'
                    );

        $form = $formManager->createForm(EntityType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            return parent::handleMessage($this->get('translator')->trans('Entity was successfully added.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'entity_new' : ($backUrl ? $backUrl : 'entity')));

        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New entity'),
                      );

        return $this->render('FaEntityBundle:Entity:new.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new entity.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function newAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Entity();

        $form = $formManager->createForm(EntityType::class, $entity, array('action' => $this->generateUrl('entity_create'), 'em' => $this->getEntityManager()));

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New entity'),
                      );

        return $this->render('FaEntityBundle:Entity:new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing entity.
     *
     * @param Request $request A Request object.
     * @param Integer $id      Id.
     *
     * @throws createNotFoundException.
     * @return Response A Response object.
     */
    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaEntityBundle:Entity')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'entity');
        }

        $options =  array(
                      'em' => $this->getEntityManager(),
                      'action' => $this->generateUrl('entity_update', array('id' => $entity->getId())),
                      'method' => 'PUT'
                    );

        $form = $formManager->createForm(EntityType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit entity'),
                      );

        return $this->render('FaEntityBundle:Entity:new.html.twig', $parameters);
    }

    /**
     * Edits an existing entity.
     *
     * @param Request $request Request instance.
     * @param Integer $id      Id.
     *
     * @throws createNotFoundException.
     * @return Response A Response object.
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository('FaEntityBundle:Entity')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'entity');
        }

        $options =  array(
            'em' => $this->getEntityManager(),
            'action' => $this->generateUrl('entity_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(EntityType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            return parent::handleMessage($this->get('translator')->trans('Entity was successfully updated.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'entity_new' : ($backUrl ? $backUrl : 'entity')));
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit entity'),
                       );

        return $this->render('FaEntityBundle:Entity:new.html.twig', $parameters);
    }

    /**
     * Deletes a entity.
     *
     * @param Request $request Request instance.
     * @param Integer $id      Id.
     *
     * @throws createNotFoundException.
     * @return RedirectResponse A RedirectResponse object.
     */
    public function deleteAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);

        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaEntityBundle:Entity')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'entity');
        }

        try {
            $deleteManager->delete($entity);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), 'entity', array(), 'error');
        } catch (\Exception $e) {
              return parent::handleException($e, 'error', 'entity');
        }

        return parent::handleMessage($this->get('translator')->trans('Entity was successfully deleted.', array(), 'success'), ($backUrl ? $backUrl : 'entity'));
    }

    /**
     * Get unset form fields.
     */
    protected function getUnsetFormFields()
    {
        $fields = array();

        return $fields;
    }

    /**
     * Save form fieldse.
     *
      * @param $form Form.
     */
    protected function addFormFields($form)
    {
        $form->add('save', SubmitType::class);
        $form->add('saveAndNew', SubmitType::class);
    }

    /**
     * Return entity array by ajax.
     *
     * @param Request $request.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function entityAjaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $entityArray            = array();
            $entityArray['more']    = false;
            $entityArray['results'] = $this->getRepository('FaEntityBundle:Entity')->getEntityArrayByTextAndType($request->get('term'), $request->get('cd_id'));

            return new JsonResponse($entityArray);
        }

        return new Response();
    }
}
