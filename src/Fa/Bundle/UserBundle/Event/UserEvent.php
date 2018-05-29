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
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * User event.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserEvent extends Event
{
    /**
     * Request object.
     *
     * @var object
     */
    private $request;

    /**
     * User object.
     *
     * @var object
     */
    private $user;

    /**
     * Constructor.
     *
     * @param UserInterface $user      UserInterface.
     * @param Request       $request   A Request object.
     */
    public function __construct(UserInterface $user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }

    /**
     * Get user.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get request objcet.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
