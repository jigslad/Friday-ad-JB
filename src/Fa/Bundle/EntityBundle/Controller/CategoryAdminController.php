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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Entity\Category;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Form\CategoryAdminType;

/**
 * This controller is used for admin side category management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CategoryAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     */
    protected function getTableName()
    {
        return 'category';
    }

    /**
     * Lists all Category entities.
     *
     * @return Response A Response object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        $parameters = array(
            'heading'        => $this->get('translator')->trans('Categories'),
        );

        return $this->render('FaEntityBundle:CategoryAdmin:index.html.twig', $parameters);
    }

    /**
     * Creates a new category entity.
     *
     * @param Request $request Request instance.
     *
     * @return Response A Response object.
     */
    public function createAction(Request $request)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Category();

        $options =  array(
            'action' => $this->generateUrl('category_create_admin', array('parent_id' => $request->get('parent_id')))
        );

        $form = $formManager->createForm(CategoryAdminType::class, $entity, $options);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            return parent::handleMessage($this->get('translator')->trans('Category was successfully added.'), 'category_admin');
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New Category'),
        );

        return $this->render('FaEntityBundle:CategoryAdmin:new.html.twig', $parameters);
    }

    /**
     * Creates a new category entity.
     *
     * @return Response A Response object.
     */
    public function newAction(Request $request)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Category();

        $options =  array(
            'action' => $this->generateUrl('category_create_admin', array('parent_id' => $request->get('parent_id')))
        );

        $form = $formManager->createForm(CategoryAdminType::class, $entity, $options);

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New Category'),
        );

        return $this->render('FaEntityBundle:CategoryAdmin:new.html.twig', $parameters);
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
            $childrens          = $this->getRepository('FaEntityBundle:Category')->getChildrenById($nodeId);
            $childrenArray      = array();
            $otherCategoryArray = array();

            foreach ($childrens as $key => $children) {
                if (preg_match('/^other/i', $children['name'])) {
                    $otherCategoryArray[$key] = array('id' => $children['id'], 'text' => $children['name'], 'children' => ($children['rgt'] - $children['lft'] > 1));
                } else {
                    $childrenArray[$key] = array('id' => $children['id'], 'text' => $children['name'], 'children' => ($children['rgt'] - $children['lft'] > 1));
                }
            }

            $childrenArray = array_merge($childrenArray, $otherCategoryArray);
            return new Response(json_encode($childrenArray), 200, array('Content-Type' => 'application/json'));
        } else {
            return new Response();
        }
    }

    /**
     * Get ajax a category nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeJsonAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = (int) trim($request->get('id'));
            if ($nodeId) {
                $childrens     = $this->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($nodeId);
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
     * Move node.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxMoveNodeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId   = (int) trim($request->get('id'));
            $parentId = (int) trim($request->get('parent_id'));
            $position = (int) trim($request->get('position'));

            if ($nodeId && $parentId) {
                $node = $this->getRepository('FaEntityBundle:Category')->moveNode($nodeId, $parentId, $position);
                return new JsonResponse('true');
            }
        }

        return new Response();
    }
}
