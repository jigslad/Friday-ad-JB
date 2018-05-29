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

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\UserBundle\Event\UserEvent;

/**
 * This event is used for user response.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class FilterUserResponseEvent extends UserEvent
{
    /**
     * Response.
     *
     * @var object
     */
    private $response;

    /**
     * Constructor.
     *
     * @param UserInterface $user       UserInterface.
     * @param Request       $request    A Request object.
     * @param Response      $response   A Response object.
     */
    public function __construct(UserInterface $user, Request $request, Response $response)
    {
        parent::__construct($user, $request);
        $this->response = $response;
    }

    /**
     * Response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
