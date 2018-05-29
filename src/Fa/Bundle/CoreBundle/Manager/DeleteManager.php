<?php

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormFactory;

/**
 * Fa\Bundle\CoreBundle\Manager\DeleteManager
 *
 * This manager is used to handle delete object for desktop and api.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class DeleteManager
{
    /**
     * where to use as api
     *
     * @var boolean
     */
    protected $isApi;

    /**
     * The request instance
     *
     * @var Request
     */
    protected $request;

    /**
     * The doctrine instance
     *
     * @var Doctrine
     */
    protected $doctrine;

    /**
     * Constructor.
     *
     * @param string $isApi
     */
    public function __construct($isApi = false)
    {
        $this->isApi = $isApi;
    }

    /**
     * This method is used do delete object
     *
     * @param Object $entity entity to delete
     */
    public function delete($entity)
    {
        $em = $this->doctrine->getManager();
        $em->remove($entity);
        $em->flush();
    }

    /**
     * Debug the service.
     */
    public function debug()
    {
        echo "<br />Inside vendor debug<br />";
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
     * Set doctrine object.
     *
     * @param Object $doctrine doctrine object
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }
}
