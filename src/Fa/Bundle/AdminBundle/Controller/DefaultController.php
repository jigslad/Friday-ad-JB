<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdminBundle\Controller;

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Entity\Resource;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\RoleRepository;

/**
 * Default controller.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DefaultController extends CoreController
{

    /**
     * Index action.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        if (!$this->isAuth()) {
            return $this->redirect($this->generateUrl('admin_login'));
        }

        $loggedinUser = $this->getLoggedInUser();
        $resources = $this->getRepository('FaUserBundle:Resource')->getResourcesArrayByUserId($loggedinUser->getId(), $this->container);

        $em = $this->getDoctrine()->getManager();
        $menus = $em->getRepository('FaUserBundle:Resource')->getActiveMenus(false);
        $menusWithActiveChild = $em->getRepository('FaUserBundle:Resource')->getMenusWithActiveChild($this->container);

        return $this->render('FaAdminBundle:Default:index.html.twig', array('menus' => $menus, 'menusWithActiveChild' => $menusWithActiveChild, 'resources' => $resources));
    }

    /**
     * Render menu action.
     *
     * @param object $currentRoute
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderMenuAction($currentRoute)
    {
        if (!$this->isAuth()) {
            return $this->redirect($this->generateUrl('admin_login'));
        }

        $loggedinUser = $this->getLoggedInUser();
        $resources    = array();
        $userRole     = $this->getRepository('FaUserBundle:User')->getUserRole($loggedinUser->getId(), $this->container);

        if ($userRole != RoleRepository::ROLE_SUPER_ADMIN) {
            $resources = $this->getRepository('FaUserBundle:Resource')->getResourcesArrayByUserId($loggedinUser->getId(), $this->container);
        }

        $em = $this->getDoctrine()->getManager();
        $menus = $em->getRepository('FaUserBundle:Resource')->getActiveMenus(true);
        $menusWithActiveChild = $em->getRepository('FaUserBundle:Resource')->getMenusWithActiveChild($this->container);

        return $this->render('FaAdminBundle:Default:renderMenu.html.twig', array('menus' => $menus, 'currentRoute' => $currentRoute, 'resources' => $resources, 'menusWithActiveChild' => $menusWithActiveChild));
    }
}
