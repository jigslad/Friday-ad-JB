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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Entity\Location;
use Fa\Bundle\EntityBundle\Form\LocationType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for admin side location management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 * @version v1.0
 */
class LocationAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all location entities.
     *
     * @return Response A Response object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        $parameters = array(
            'heading'        => $this->get('translator')->trans('Location'),
        );

        return $this->render('FaEntityBundle:Location:index.html.twig', $parameters);
    }

    /**
     * Creates a new location entity.
     *
     * @param Request $request   Request instance.
     * @param Integer $parent_id Id of parent resource.
     *
     * @return Response A Response object.
     */
    public function createAction(Request $request, $parent_id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Location();

        $options =  array(
            'em'     => $this->getEntityManager(),
            'attr'   => array('parent_id' => $parent_id),
            'action' => $this->generateUrl('location_create', array('parent_id' => $parent_id))
        );

        $form = $formManager->createForm(new LocationType(), $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            return parent::handleMessage($this->get('translator')->trans('Location was successfully added.'), 'location');
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New location'),
        );

        return $this->render('FaEntityBundle:Location:new.html.twig', $parameters);
    }

    /**
     * Creates a new location entity.
     *
     * @param Integer $parent_id Id of parent location.
     *
     * @return Response A Response object.
     */
    public function newAction($parent_id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Location();

        $options =  array(
            'em'     => $this->getEntityManager(),
            'attr'   => array('parent_id' => $parent_id),
            'action' => $this->generateUrl('location_create', array('parent_id' => $parent_id))
        );

        $form = $formManager->createForm(new LocationType(), $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New location'),
        );

        return $this->render('FaEntityBundle:Location:new.html.twig', $parameters);
    }

    /**
     * Edits an existing location entity.
     *
     * @param Integer $id Location id.
     *
     * @return Response A Response object.
     */
    public function editAction($id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaEntityBundle:Location')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Location entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'location');
        }

        $options =  array(
            'em'     => $this->getEntityManager(),
            'action' => $this->generateUrl('location_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(new LocationType(), $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit location'),
        );

        return $this->render('FaEntityBundle:Location:new.html.twig', $parameters);
    }

    /**
     * Edits an existing location entity.
     *
     * @param Request $request Request instance.
     * @param Integer $id      Location id.
     *
     * @throws createNotFoundException.
     * @return Response A Response object.
     */
    public function updateAction(Request $request, $id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaEntityBundle:Location')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Location entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'location');
        }

        $options =  array(
            'em'     => $this->getEntityManager(),
            'action' => $this->generateUrl('location_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(new LocationType(), $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            return parent::handleMessage($this->get('translator')->trans('Location was successfully updated.'), 'location');
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit location'),
        );

        return $this->render('FaEntityBundle:Location:new.html.twig', $parameters);
    }

    /**
     * Deletes a location entity.
     *
     * @param Request $request Request instance.
     * @param Integer $id      Location id.
     *
     * @throws createNotFoundException.
     * @return Response A Response object.
     */
    public function deleteAction(Request $request, $id)
    {
        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaEntityBundle:Location')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Location entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'location');
        }

        $deleteManager->delete($entity);

        return parent::handleMessage($this->get('translator')->trans('Location was successfully deleted.'), 'location');
    }

    /**
     * Get ajax a location nodes.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId        = (int) trim($request->get('id'));
            $childrens     = $this->getRepository('FaEntityBundle:Location')->getChildrenById($nodeId);
            $childrenArray = array();

            foreach ($childrens as $children) {
                $childrenArray[] = array('id' => $children['id'], 'text' => $children['name'], 'children' => ($children['rgt'] - $children['lft'] > 1));
            }

            return new JsonResponse($childrenArray);
        } else {
            return new Response();
        }
    }

    /**
     * Get form fields.
     *
     * @return Response A Response object.
     */
    protected function getUnsetFormFields()
    {
        $fields = array(
                   'latitude',
                   'longitude',
                   'lft',
                   'rgt',
                   'root',
                   'parent',
                   'lvl',
                  );

        return $fields;
    }

    /**
     * Save form fields.
     *
     * @param $form Form.
     */
    protected function addFormFields($form)
    {
        $form->add('save', 'submit');
    }

    /**
     * Get ajax a location nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeJsonAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = $request->get('id');
            if ($nodeId) {
                $childrens     = $this->getRepository('FaEntityBundle:Location')->getChildrenKeyValueArrayByParentId($nodeId);
                $childrenArray = array();

                foreach ($childrens as $id => $name) {
                    $childrenArray[] = array('id' => $id, 'text' => $name);
                }

                return new JsonResponse($childrenArray);
            }
        }

        return new Response();
    }

    /**
     * Get ajax a location nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeJsonForLocationGroupAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = $request->get('id');
            $locationGroupId = $request->get('locationGroupId', null);
            $locationGroupType = $request->get('locationGroupType', null);
            $locationField = $request->get('locationField');
            if ($nodeId) {
                $childrens     = $this->getRepository('FaEntityBundle:Location')->getChildrenKeyValueArrayByParentId($nodeId);
                $childrenArray = array();
                $locationGroupFieldIds = array();
                if ($locationField == 'town') {
                    $locationGroupFieldIds = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getTownArrayByLocationGroupType($locationGroupType);
                } elseif ($locationField == 'domicile') {
                    $locationGroupFieldIds = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getDomicileArrayByLocationGroupId($locationGroupId, $locationGroupType);
                }
                foreach ($childrens as $id => $name) {
                    if (!in_array($id, $locationGroupFieldIds)) {
                        $childrenArray[] = array('id' => $id, 'text' => $name);
                    }
                }

                return new JsonResponse($childrenArray);
            }
        }

        return new Response();
    }
}
