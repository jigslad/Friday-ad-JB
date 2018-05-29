<?php

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormFactory;

/**
 * Fa\Bundle\CoreBundle\Manager\ExceptionManager
 *
 * This manager is used to log error which is used for issue tracking
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class ExceptionManager
{
    /**
     * The request instance
     *
     * @var Request
     */
    protected $request;

    /**
     * The monolog instance
     *
     * @var Monolog
     */
    protected $monolog;

    /**
     * The message string
     *
     * @var string
     */
    protected $message;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Debug the service.
     */
    public function debug()
    {
        echo "<br />Inside vendor debug<br />";
    }

    /**
     * handleException is used to log the error
     *
     * @param Exception $e Exception instance
     * @param string $loglevel level of log
     */
    public function handleException($e, $loglevel = 'error')
    {
        $route = $this->request->get('_route');
        $this->message = $route.'=='.$e->getMessage();
        $this->monolog->$loglevel($this->message);
    }

    /**
     * Set request instance.
     *
     * @param RequestStack $requestStack RequestStack instance
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Set monolog instance.
     *
     * @param Object $monolog Monolog instance
     */
    public function setMonolog($monolog)
    {
        $this->monolog = $monolog;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
