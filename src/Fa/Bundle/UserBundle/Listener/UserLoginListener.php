<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Listener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This user login listener allows to update last login date & login counter etc...
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserLoginListener
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container object.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * On login.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onLogin(InteractiveLoginEvent $event)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser(); // getting the user
        //set last login and update total login count, move tmp favorite ads.
        $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->updateLoginTimeAndFavoriteAds($this->container->get('request_stack')->getCurrentRequest()->cookies->get('add_to_fav_session_id'), $user, $this->container);
        //set message for add to fav.
        if (strpos($this->container->get('request_stack')->getCurrentRequest()->getUri(), '/admin/') !== true && strpos($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'), '/admin/') !== true && $this->container->get('request_stack')->getCurrentRequest()->cookies->has('save_add_to_fav_flag') && $this->container->get('request_stack')->getCurrentRequest()->cookies->get('save_add_to_fav_flag') && $this->container->get('request_stack')->getCurrentRequest()->cookies->get('save_add_to_fav_flag') != CommonManager::COOKIE_DELETED) {
            $messageManager = $this->container->get('fa.message.manager');
            $messageManager->setFlashMessage($this->container->get('translator')->trans('Ad marked as favorite successfully.', array(), 'frontend-search-result'), 'success');
        }
        //redirect to search page if found.
        if ($this->container->get('request_stack')->getCurrentRequest()->cookies->has('frontend_redirect_after_login_path_info') && $this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info') != CommonManager::COOKIE_DELETED) {
            if ((strpos($this->container->get('request_stack')->getCurrentRequest()->getUri(), '/admin/') === true && strpos($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'), '/admin/') === true) || (strpos($this->container->get('request_stack')->getCurrentRequest()->getUri(), '/admin/') === false && strpos($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info'), '/admin/') === false)) {
                $response = new RedirectResponse(htmlspecialchars_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('frontend_redirect_after_login_path_info')));
                $response->headers->clearCookie('frontend_redirect_after_login_path_info');
                $response->headers->clearCookie('add_to_fav_session_id');
                $response->headers->clearCookie('save_add_to_fav_flag');
                $response->headers->clearCookie('buy_now_flag');
                $response->send();
            }
        }
    }
}
