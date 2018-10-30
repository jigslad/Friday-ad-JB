<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\ResourceAuthorizationManager;

/**
 * This listener is used to autorize the resource.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version 1.0
 */
class ResourceAuthorizationListener
{
    protected $resourceAuthorizationManager;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * On kernel controller method to execute before each request.
     *
     * @param FilterControllerEvent $event FilterControllerEvent instance.
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
        * If it is a class, it comes in array format
        */
        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof ResourceAuthorizationController) {
            $currentRoute = $event->getRequest()->get('_route');
            if (!$this->resourceAuthorizationManager->isGranted($currentRoute)) {
                throw new AccessDeniedHttpException('You do not have permission to access this resource.');
            }
        }
    }

    /**
     * Set resource authorization manager.
     *
     * @param ResourceAuthorizationManager $resourceAuthorizationManager ResourceAuthorizationManager instance.
     */
    public function setResourceAuthorizationManager(ResourceAuthorizationManager $resourceAuthorizationManager)
    {
        $this->resourceAuthorizationManager = $resourceAuthorizationManager;
    }
}
