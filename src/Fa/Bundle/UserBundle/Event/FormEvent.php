<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FormEvent form.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class FormEvent extends Event
{
    /**
     * Form object.
     *
     * @var object
     */
    private $form;

    /**
     * Request object.
     *
     * @var object
     */
    private $request;

    /**
     * Response object.
     *
     * @var object
     */
    private $response;

    /**
     * Constructor.
     *
     * @param FormInterface $form       FormInterface.
     * @param Request       $request    A Request object.
     */
    public function __construct(FormInterface $form, Request $request)
    {
        $this->form = $form;
        $this->request = $request;
    }

    /**
     * Get form object.
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Get request object.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set response.
     *
     * @param Response $response A Response objcet.
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get response object.
     *
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
