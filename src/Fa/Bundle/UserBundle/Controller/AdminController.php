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

/**
 * This controller is used for admin.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdminController extends Controller
{
    /**
     * This is welcome action.
     *
     * @return Response A Response object.
     */
    public function welcomeAction()
    {
        return $this->render('FaUserBundle:Admin:welcome.html.twig');
    }
}
