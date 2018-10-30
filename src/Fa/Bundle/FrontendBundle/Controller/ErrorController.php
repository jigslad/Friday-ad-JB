<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This is error page controller for front side.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ErrorController extends CoreController
{
    /**
     * Error page 404.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function error404Action(Request $request)
    {
        return $this->render('FaFrontendBundle:Error:error404.html.twig', array());
    }
}
