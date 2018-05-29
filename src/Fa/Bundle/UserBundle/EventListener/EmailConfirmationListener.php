<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\EventListener;

use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\UserBundle\Event\FormEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Email confirmation listener.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class EmailConfirmationListener implements EventSubscriberInterface
{
    /**
     * Container identifier.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param Container $container Container.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get subscribed events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            UserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
        );
    }

    /**
     * This method will be called on registration success to send email.
     *
     * @param FormEvent $event FormEvent object.
     */
    public function onRegistrationSuccess(FormEvent $event)
    {
        $user = $event->getForm()->getData();
        $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->sendUserRegistrationEmail($user, $this->container);
    }
}
