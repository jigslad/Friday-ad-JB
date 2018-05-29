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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for admin side role management.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class DefaultController extends Controller
{
    /**
     * This is index action.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        return $this->redirect($this->generateUrl("admin_login"));
    }

    /**
     * This is home page action.
     *
     * @return Response A Response object.
     */
    public function homeAction()
    {
        return $this->render('FaUserBundle:Default:index.html.twig');
    }
}
