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
use Fa\Bundle\UserBundle\Entity\Permission;
use Fa\Bundle\UserBundle\Form\PermissionType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * This controller is used for admin side permission management.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class PermissionController extends CoreController implements ResourceAuthorizationController
{

    /**
     * Constructor.
     *
     * @throws AccessDeniedHttpException
     */
    public function __construct()
    {
        throw new AccessDeniedHttpException('You do not have permission to access this resource.');
    }

    /**
     * Lists all Permission entities.
     *
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('FaUserBundle:Permission')->findAll();

        return array(
            'entities' => $entities,
            'heading' => 'Permissions',
        );
    }
    /**
     * Creates a new Permission entity.
     *
     * @Template("FaUserBundle:Permission:new.html.twig")
     * @Method("POST")
     *
     * @param Request $request A Request object.
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        try {
            $entity = new Permission();
            $attributes = array(
                           'action' => $this->generateUrl('permission_create'),
                          );
            $form = $this->createCreateForm($entity, $attributes);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'Permission was successfully added.'
                );
                return $this->redirect($this->generateUrl($form->get('saveAndNew')->isClicked() ? 'permission_new' : 'permission'));
            }

            return array(
                'entity' => $entity,
                'form'   => $form->createView(),
                'heading' => 'New permission',
            );
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'There is some problem with adding new permission. Please contact administrator for further assitance.'
            );

            return $this->redirect($this->generateUrl('permission'));
        }
    }

    /**
     * Creates a form to create a Permission entity.
     *
     * @param Permission $entity     The entity.
     * @param array      $attributes Attributes of entity.
     *
     * @return object Form object.
     */
    private function createCreateForm(Permission $entity, array $attributes)
    {
        $form = $this->createForm(new PermissionType(), $entity, $attributes);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        return $form;
    }

    /**
     * Displays a form to create a new Permission entity.
     *
     * @Template()
     * @Method("GET")
     *
     * @return array|RedirectResponse Array or RedirectResponse object.
     */
    public function newAction()
    {
        try {
            $entity = new Permission();
            $attributes = array(
                         'action' => $this->generateUrl('permission_create'),
                        );
            $form   = $this->createCreateForm($entity, $attributes);

            return array(
                'entity' => $entity,
                'form'   => $form->createView(),
                'heading' => 'New permission',
            );
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'There is some problem with adding new permission. Please contact administrator for further assitance.'
            );

            return $this->redirect($this->generateUrl('permission'));
        }
    }

    /**
     * Displays a form to edit an existing Permission entity.
     *
     * @Template("FaUserBundle:Permission:new.html.twig")
     *
     * @param integer $id Id.
     *
     * @return array|RedirectResponse Array or RedirectResponse object.
     */
    public function editAction($id)
    {
        try {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('FaUserBundle:Permission')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Permission entity.');
            }

            $attributes = array(
                           'action' => $this->generateUrl('permission_update', array('id' => $entity->getId())),
                          );
            $editForm = $this->createCreateForm($entity, $attributes);

            return array(
                'entity'      => $entity,
                'form'   => $editForm->createView(),
                'heading' => 'Edit permission',
            );
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'Permission not found'
            );
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'There is some problem with updating permission. Please contact administrator for further assitance.'
            );
        }

        return $this->redirect($this->generateUrl('permission'));
    }

    /**
     * Edits an existing Permission entity.
     *
     * @Template("FaUserBundle:Permission:new.html.twig")
     * @Method("PUT")
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return array|RedirectResponse Array or RedirectResponse object.
     */
    public function updateAction(Request $request, $id)
    {
        try {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('FaUserBundle:Permission')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Permission entity.');
            }

            $attributes = array(
                           'action' => $this->generateUrl('permission_update', array('id' => $entity->getId())),
                          );
            $editForm = $this->createCreateForm($entity, $attributes);
            $editForm->handleRequest($request);

            if ($editForm->isValid()) {
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'Permission was successfully saved.'
                );

                return $this->redirect($this->generateUrl($editForm->get('saveAndNew')->isClicked() ? 'permission_new' : 'permission'));
            }

            return array(
                'entity'      => $entity,
                'form'   => $editForm->createView(),
                'heading' => 'Edit permission',
            );
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'Permission not found'
            );
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'There is some problem with updating permission. Please contact administrator for further assitance.'
            );
        }

        return $this->redirect($this->generateUrl('permission'));
    }

    /**
     * Deletes a Permission entity.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return RedirectResponse Array or RedirectResponse object.
     */
    public function deleteAction(Request $request, $id)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FaUserBundle:Permission')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Permission entity.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Permission was successfully deleted.'
            );
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'Permission not found'
            );
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'There is some problem with deleting permission. Please contact administrator for further assitance.'
            );
        }

        return $this->redirect($this->generateUrl('permission'));
    }

    /**
     * Get unset form fields.
     *
     * @return array
     */
    protected function getUnsetFormFields()
    {
        $fields = array(
                 'created_at',
                 'updated_at',
                );

        return $fields;
    }

    /**
     * Add fields to form.
     *
     * @param object $form form object.
     */
    protected function addFormFields($form)
    {
        $form->add('save', 'submit');
        $form->add('saveAndNew', 'submit');
    }
}
