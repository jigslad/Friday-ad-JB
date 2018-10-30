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

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class is used for.
 *
 * @author sagar lotiya<sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DoctrineExtensionListener implements ContainerAwareInterface
{
    /**
     * Container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Set container.
     *
     * @param ContainerInterface $container
     *
     * @see \Symfony\Component\DependencyInjection\ContainerAwareInterface::setContainer()
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * On late kernel request.
     *
     * @param GetResponseEvent $event
     */
    public function onLateKernelRequest(GetResponseEvent $event)
    {
        $translatable = $this->container->get('gedmo.listener.translatable');
        $translatable->setTranslatableLocale($event->getRequest()->getLocale());
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (Kernel::MAJOR_VERSION == 2 && Kernel::MINOR_VERSION < 6) {
            $securityContext = $this->container->get('security.context', ContainerInterface::NULL_ON_INVALID_REFERENCE);
            if (null !== $securityContext && null !== $securityContext->getToken() && $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                $adminRolesArray = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:Role')->getRoleArrayByType('A', $this->container);
                $user = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
                if (is_object($user)) {
                    $userRole = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserRole($user->getId(), $this->container);
                    if (in_array($userRole, $adminRolesArray)) {
                        $loggable = $this->container->get('gedmo.listener.loggable');
                        $loggable->setUsername($securityContext->getToken()->getUsername());
                    }
                }
            }
        } else {
            $tokenStorage = $this->container->get('security.token_storage')->getToken();
            $authorizationChecker = $this->container->get('security.authorization_checker');
            if (null !== $tokenStorage && $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                $adminRolesArray = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:Role')->getRoleArrayByType('A', $this->container);
                $user = CommonManager::getSecurityTokenStorage($this->container)->getToken()->getUser();
                if (is_object($user)) {
                    $userRole = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserRole($user->getId(), $this->container);
                    if (in_array($userRole, $adminRolesArray)) {
                        $loggable = $this->container->get('gedmo.listener.loggable');
                        $loggable->setUsername($tokenStorage->getUser());
                    }
                }
            }
        }
    }
}
