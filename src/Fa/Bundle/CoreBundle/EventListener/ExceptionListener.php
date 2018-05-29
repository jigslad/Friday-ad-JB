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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Kernal exception listener.
 * ExceptionLister is used for global exception handling.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ExceptionListener
{
    /**
     * The template engine.
     *
     * @var EngineInterface
     */
    private $kernal;

    /**
     * The kernal.
     *
     * @var KernelInterface
     */
    private $templateEngine;

    /**
     * The security context.
     *
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * The ContainerInterface handler.
     *
     * @var container
     */
    private $container;

    /**
     * Constructor.
     *
     * @param EngineInterface $templateEngine The template engine
     * @param KernelInterface $kernal         The kernal interface
     */
    public function __construct(EngineInterface $templateEngine, KernelInterface $kernal, ContainerInterface $container)
    {
        $this->templateEngine  = $templateEngine;
        $this->kernal          = $kernal;
        $this->securityContext = $container->get('security.authorization_checker');
        $this->container       = $container;
    }

    /**
     * On kernel exception method to execute when global exception thrown.
     *
     * @param GetResponseForExceptionEvent $event GetResponseForExceptionEvent instance.
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        if (in_array($this->kernal->getEnvironment(), array('prod', 'live_prod')) && method_exists($exception, 'getStatusCode') && $exception->getStatusCode() == '403') {
            if (!$this->securityContext->isGranted("IS_AUTHENTICATED_REMEMBERED") && strpos($event->getRequest()->getPathInfo(), '/admin/') !== false) {
                $this->container->get('session')->getFlashBag()->add('error', $exception->getMessage());
                $response  = new RedirectResponse($this->container->get('router')->generate('admin_login'));
                if (!$event->getRequest()->isXmlHttpRequest()) {
                    $response->headers->setCookie(new Cookie('redirect_path_info', $event->getRequest()->getPathInfo(), time() + 3600 * 24 * 7));
                }
                $response->send();
            } else if (!$this->securityContext->isGranted("IS_AUTHENTICATED_REMEMBERED") && !(strpos($event->getRequest()->getPathInfo(), '/admin/') !== false)) {
                $this->container->get('session')->getFlashBag()->add('error', $exception->getMessage());
                $response  = new RedirectResponse($this->container->get('router')->generate('login'));
                if (!$event->getRequest()->isXmlHttpRequest()) {
                    $response->headers->setCookie(new Cookie('redirect_path_info', $event->getRequest()->getPathInfo(), time() + 3600 * 24 * 7));
                }
                $response->send();
            } else {
                $response = $this->templateEngine->render(
                    'FaCoreBundle:Exception:error403.html.twig',
                    array('status_text' => $exception->getMessage())
                );
            }

            $event->setResponse(new Response($response));
        } elseif (in_array($this->kernal->getEnvironment(), array('prod', 'live_prod')) && method_exists($exception, 'getStatusCode') && $exception->getStatusCode() == '500') {
            CommonManager::sendErrorMail($this->container, 'Error: Live debugging 500 issue', $exception->getMessage(), $exception->getTraceAsString());
            $response = $this->templateEngine->render(
                'FaCoreBundle:Exception:error500.html.twig',
                array('status_text' => $event->getException()->getMessage())
            );

            $event->setResponse(new Response($response));
        } elseif (in_array($this->kernal->getEnvironment(), array('prod', 'live_prod')) && method_exists($exception, 'getStatusCode') && $exception->getStatusCode() == '404') {
            $response = $this->templateEngine->render(
                'FaCoreBundle:Exception:error404.html.twig',
                array('status_text' => $event->getException()->getMessage(), 'status_code' => $exception->getStatusCode())
            );

            $event->setResponse(new Response($response));
        } elseif (in_array($this->kernal->getEnvironment(), array('prod', 'live_prod')) && method_exists($exception, 'getStatusCode') && $exception->getStatusCode() == '410') {
            $response = $this->templateEngine->render(
                'FaCoreBundle:Exception:error410.html.twig',
                array('status_text' => $event->getException()->getMessage(), 'status_code' => $exception->getStatusCode())
            );

            $event->setResponse(new Response($response));
        } elseif (in_array($this->kernal->getEnvironment(), array('prod', 'live_prod'))) {
            if ($exception instanceof \Pagerfanta\Exception\OutOfRangeCurrentPageException && $this->securityContext->isGranted("IS_AUTHENTICATED_REMEMBERED") && strpos($event->getRequest()->getPathInfo(), '/admin/') !== false) {
                $currentPage = $this->container->get('request_stack')->getCurrentRequest()->get('page');
                $uri = preg_replace('/page-(\d+)/', 'page-'.($currentPage-1), $this->container->get('request_stack')->getCurrentRequest()->getUri());
                $uri = preg_replace('/page=(\d+)/', 'page='.($currentPage-1), $uri);
                $response  = new RedirectResponse($uri);
                $response->send();
            } else {
                if ($exception instanceof \Pagerfanta\Exception\OutOfRangeCurrentPageException) {
                    $uri = preg_replace('/page-(\d+)/', 'page-1', $this->container->get('request_stack')->getCurrentRequest()->getUri());
                    $uri = preg_replace('/page=(\d+)/', 'page=1', $uri);
                    $response  = new RedirectResponse($uri);
                    $response->send();
                } else {
                    $errorOccuredAt = date("Y-m-d H:i:s", time())."\n";
                    $ip = "Ip: ".$this->container->get('request_stack')->getCurrentRequest()->getClientIp()."\n";
                    $userAgent = "User agent: ".$this->container->get('request_stack')->getCurrentRequest()->headers->get('User-Agent');
                    $stack = $this->container->get('request_stack')->getCurrentRequest()->getUri()."\n";
                    $stack = $errorOccuredAt."===".$ip."===".$userAgent."===".$stack." ".$exception->getTraceAsString();
                    CommonManager::sendErrorMail($this->container, 'Error: Live debugging 500 issue manully thrown', $exception->getMessage(), $stack);
                }
                $template = $this->templateEngine->render(
                    'FaCoreBundle:Exception:error500.html.twig',
                    array('status_text' => $event->getException()->getMessage(), 'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR)
                );
                $response = new Response($template);
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                $event->setResponse($response);
            }
        }
    }
}
