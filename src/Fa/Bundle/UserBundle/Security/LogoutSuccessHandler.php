<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Security;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This handler is used to perform various activities on logout success.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{

    /**
     * Router.
     *
     * @var object.
     */
    protected $router;

    /**
     * Container.
     *
     * @var object
     */
    protected $container;

    /**
     * Translator.
     *
     * @var object
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param Router    $router     Router object.
     * @param Container $container  Container object.
     */
    public function __construct(Router $router, Container $container)
    {
        $this->router     = $router;
        $this->container  = $container;
        $this->translator = $this->container->get('translator');
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface::onLogoutSuccess()
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function onLogoutSuccess(Request $request)
    {
        $this->container->get('session')->getFlashBag()->add('success', $this->translator->trans('You have successfully logged out.', array(), 'messages'));

        //remove session variable
        $this->container->get('session')->remove('logged_in_admin_id');

        // redirect the user to route which is defined in parameters
        $logoutTarget = $this->container->getParameter('fa.user.logout.target');
        if ($this->container->get('request_stack')->getCurrentRequest()->get('_route') == 'admin_logout') {
            $logoutTarget = $this->container->getParameter('fa.admin.logout.target');
        } else {
            // Remove session to allow login step in paa process
            $this->container->get('session')->remove('paa_skip_login_step');
        }


        $response = new RedirectResponse($this->router->generate($logoutTarget));
        return $response;
    }
}
