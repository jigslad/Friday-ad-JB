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

/**
 * Contains all events thrown in the UserBundle.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
final class UserEvents
{
    /**
     * The REGISTRATION_SUCCESS event occurs when the registration form is submitted successfully.
     *
     * This event allows you to set the response instead of using the default one.
     * The event listener method receives a Fa\Bundle\UserBundle\Event\FormEvent instance.
     */
    const REGISTRATION_SUCCESS = 'fa_user.registration.success';

    /**
     * The REGISTRATION_COMPLETED event occurs after saving the user in the registration process.
     *
     * This event allows you to access the response which will be sent.
     * The event listener method receives a Fa\Bundle\UserBundle\Event\FilterUserResponseEvent instance.
     */
    const REGISTRATION_COMPLETED = 'fa_user.registration.completed';
}
