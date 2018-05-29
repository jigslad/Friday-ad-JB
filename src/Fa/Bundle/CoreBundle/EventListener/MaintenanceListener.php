<?php

namespace Fa\Bundle\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

class MaintenanceListener
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
		$this->container       = $container;
	}
	
    

    public function onKernelRequest(GetResponseEvent $event)
    {	
        $maintenance = $this->container->hasParameter('maintenance') ? $this->container->getParameter('maintenance') : false;

        $debug = in_array($this->container->get('kernel')->getEnvironment(), array('prod', 'live_prod'));

        if ($maintenance && $debug) { 
        	$response = $this->templateEngine->render(
        			'FaCoreBundle:Exception:error503.html.twig');
        	
        	$event->setResponse(new Response($response));
        }

    }
}