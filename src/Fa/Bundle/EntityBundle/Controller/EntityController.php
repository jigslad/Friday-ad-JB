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

/**
 * This controller is used for entity management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class EntityController extends CoreController
{
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
            $entityArray['results'] = $this->getRepository('FaEntityBundle:Entity')->getEntityArrayByTextAndType($request->get('term'), $request->get('cd_id'), $request->get('parent_id'));

            return new JsonResponse($entityArray);
        }

        return new Response();
    }

    /**
     * Get ajax a category nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetOptionsByParentAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $parentId = (int) trim($request->get('parent_id'));
            if ($parentId) {
                $childrens     = $this->getRepository('FaEntityBundle:Entity')->getEntityArrayByParent($parentId, $this->container);
                $childrenArray = array();

                foreach ($childrens as $id => $name) {
                    $childrenArray[] = array('id' => $id, 'text' => $name);
                }

                return new JsonResponse($childrenArray);
            }
        }

        return new Response();
    }
}
