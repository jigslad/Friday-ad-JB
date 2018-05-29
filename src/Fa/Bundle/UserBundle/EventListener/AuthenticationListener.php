<?php

/**
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\EventListener;

use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\UserBundle\Event\UserEvent;
use Fa\Bundle\UserBundle\Event\FilterUserResponseEvent;
use Fa\Bundle\UserBundle\Security\UserAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This event listener is used for authentication.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class AuthenticationListener implements EventSubscriberInterface
{
    /**
     * Name of firewall.
     *
     * @var string
     */
    private $firewallName;

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param string             $firewallName Name of firewall.
     * @param ContainerInterface $container    Container.
     */
    public function __construct($firewallName, ContainerInterface $container)
    {
        $this->firewallName = $firewallName;
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
            UserEvents::REGISTRATION_COMPLETED => 'authenticate',
        );
    }

    /**
     * Authenticate.
     *
     * @param FilterUserResponseEvent $event Object of filter user response event.
     */
    public function authenticate(FilterUserResponseEvent $event)
    {
        try {
            $providerKey = 'main'; // your firewall name
            $token = new UsernamePasswordToken($event->getUser(), null, $providerKey, $event->getUser()->getRoles());
            $this->container->get('security.token_storage')->setToken($token);

            //now dispatch the login event
            $event = new InteractiveLoginEvent($event->getRequest(), $token);
            $this->container->get("event_dispatcher")->dispatch("security.interactive_login", $event);
        } catch (AccountStatusException $ex) {
            // We simply do not authenticate users which do not pass the user
            // checker (not enabled, expired, etc.).
        }
    }
}
