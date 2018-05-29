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

use Fa\Bundle\UserBundle\Entity\Resource;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Fa\Bundle\UserBundle\Entity\RoleResourcePermission;
use Fa\Bundle\UserBundle\Form\RoleResourcePermissionType;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;

/**
 * This controller is used for admin side role resource management.
 *
 * @author Atul Kamani <atul@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class RoleResourcePermissionController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Creates a new RoleResourcePermission entity.
     *
     * @param Request $request A Request object.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function createAction(Request $request)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        $this->getDoctrine()->getManager()->getRepository('FaUserBundle:RoleResourcePermission')->saveRoleResourcePermission($request);
        $this->get('session')->getFlashBag()->add(
            'success',
            'Resource was successfully assigned to role.'
        );
        CommonManager::removeCachePattern($this->container, "resource|getResourcesArrayByUserId|*");
        return $this->redirect($backUrl ? $backUrl : $this->generateUrl('role'));
    }

    /**
     * Displays a form to edit an existing RoleResourcePermission entity.
     *
     * @param integer $id Id.
     *
     * @return Response A Response object.
     */
    public function editAction($id)
    {
        CommonManager::setAdminBackUrl($this->get('request'), $this->container);
        $em     = $this->getDoctrine()->getManager();
        $entity = new RoleResourcePermission();
        $objResourceRepo = $em->getRepository('FaUserBundle:Resource');
        $resourcesArray  = $objResourceRepo->getTreeResourcesArray();

        $options = array(
                  'em'             => $em,
                  'action'         => $this->generateUrl('roleresourcepermission_create'),
                  );
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(new RoleResourcePermissionType(), $entity, $options);

        $this->unsetFormFields($form);

        $this->addFormFields($form);

        $selectedResourcesArray = array();
        $objRRPs = $em->getRepository('FaUserBundle:RoleResourcePermission')->getSelectedRecordsByRoleId($id);

        foreach ($objRRPs as $objRRP) {
            $selectedResourcesArray[] = $objRRP->getResource()->getId();
        }

        $options      = array(
                      'decorate' => true,
                      'rootOpen' => '<ul>',
                      'rootClose' => '</ul>',
                      'childOpen' => '<li>',
                      'childClose' => '</li>',
                      );

        $htmlTree = $objResourceRepo->buildTree($resourcesArray, $options);

        $parameters = array(
            'resourceTree'           => $htmlTree,
             'form'                   => $form->createView(),
             'resourcesArray'         => $resourcesArray,
             'selectedResourcesArray' => $selectedResourcesArray,
             'heading'                => 'Resource assignment'
        );

        return $this->render('FaUserBundle:RoleResourcePermission:edit.html.twig', $parameters);
    }

    /**
     * Get unset form fields.
     *
     * @return array
     */
    protected function getUnsetFormFields()
    {
        $fields = array();

        return $fields;
    }

    /**
     * Add fields to form.
     *
     * @param object $form Form object.
     */
    protected function addFormFields($form)
    {
        $form->add('save', 'submit');
    }
}
