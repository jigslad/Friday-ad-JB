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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Entity\Resource;
use Fa\Bundle\UserBundle\Form\ResourceType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * This controller is used for resource management.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ResourceController extends CoreController implements ResourceAuthorizationController
{

    /**
     * Lists all Resource entities.
     *
     * @return Response A Response object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);

        $parameters = array(
            'heading'        => $this->get('translator')->trans('Resources'),
        );

        return $this->render('FaUserBundle:Resource:index.html.twig', $parameters);
    }

    /**
     * Creates a new Resource entity.
     *
     * @param Request $request   A Request object.
     * @param Integer $parent_id Id of parent resource.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function createAction(Request $request, $parent_id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Resource();

        $options =  array(
            'em'     => $this->getEntityManager(),
            'container' => $this->container,
            'attr'   => array('parent_id' => $parent_id),
            'action' => $this->generateUrl('resource_create', array('parent_id' => $parent_id))
        );

        $form = $formManager->createForm(ResourceType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            return parent::handleMessage($this->get('translator')->trans('Resource was successfully added.'), 'resource');
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New resource'),
        );

        return $this->render('FaUserBundle:Resource:new.html.twig', $parameters);
    }

    /**
     * Creates a new Resource entity.
     *
     * @param Integer $parent_id Id of parent resource.
     *
     * @return Response A Response object.
     */
    public function newAction($parent_id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Resource();

        $options =  array(
            'em'     => $this->getEntityManager(),
            'container' => $this->container,
            'attr'   => array('parent_id' => $parent_id),
            'action' => $this->generateUrl('resource_create', array('parent_id' => $parent_id))
        );

        $form = $formManager->createForm(ResourceType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New resource'),
        );

        return $this->render('FaUserBundle:Resource:new.html.twig', $parameters);
    }

    /**
     * Edits an existing Resource entity.
     *
     * @param integer $id Id.
     *
     * @return Response A Response object.
     */
    public function editAction($id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaUserBundle:Resource')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Resource entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'resource');
        }

        $options =  array(
            'em'     => $this->getEntityManager(),
            'container' => $this->container,
            'action' => $this->generateUrl('resource_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(ResourceType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit resource'),
        );

        return $this->render('FaUserBundle:Resource:new.html.twig', $parameters);
    }

    /**
     * Edits an existing Resource entity.
     *
     * @param Request $request A Request object.
     * @param Integer $id      Resource id.
     *
     * @throws NotFoundHttpException
     * @return Response A Response object.
     */
    public function updateAction(Request $request, $id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaUserBundle:Resource')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Resource entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'resource');
        }

        $options =  array(
            'em'     => $this->getEntityManager(),
            'container' => $this->container,
            'action' => $this->generateUrl('resource_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(ResourceType::class, $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            return parent::handleMessage($this->get('translator')->trans('Resource was successfully updated.'), 'resource');
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit resource'),
        );

        return $this->render('FaUserBundle:Resource:new.html.twig', $parameters);
    }

    /**
     * Deletes a Resource entity.
     *
     * @param Request $request A Request object.
     * @param Integer $id      Resource id.
     *
     * @throws NotFoundHttpException
     * @return RedirectResponse A RedirectResponse object.
     */
    public function deleteAction(Request $request, $id)
    {
        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaUserBundle:Resource')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Resource entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'resource');
        }

        $deleteManager->delete($entity);

        return parent::handleMessage($this->get('translator')->trans('Resource was successfully deleted.'), 'resource');
    }

    /**
     * Get unset form fields.
     *
     * @return array
     */
    protected function getUnsetFormFields()
    {
        $fields = array(
                   'code',
                   'lft',
                   'rgt',
                   'root',
                   'lvl',
                   'created_at',
                   'updated_at',
                   'permission',
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
    }

    /**
     * Get ajax a category nodes.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId             = (int) trim($request->get('id'));
            $childrens          = $this->getRepository('FaUserBundle:Resource')->getChildrenById($nodeId);
            $childrenArray      = array();

            foreach ($childrens as $key => $children) {
                $childrenArray[$key] = array('id' => $children['id'], 'text' => $children['name'], 'children' => ($children['rgt'] - $children['lft'] > 1));
            }
            return new Response(json_encode($childrenArray), 200, array('Content-Type' => 'application/json'));
        } else {
            return new Response();
        }
    }
}
