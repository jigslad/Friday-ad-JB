<?php
namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;

/**
 * Fa\Bundle\CoreBundle\Manager\FacebookManager
 *
 * This manager is used to get facebbok information.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class FacebookManager
{
    /**
     * Container service class object
     *
     * @var object
     */
    private $container;

    /**
     * facebook app id
     *
     * @var string
     */
    private $app_id;

    /**
     * facebook app secret
     *
     * @var string
     */
    private $app_secret;

    /**
     * facebook login helper
     *
     * @var object
     */
    private $helper;

    /**
     * facebook session
     *
     * @var object
     */
    private $session;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container   = $container;
        $facebookAppParams = $this->container->getParameter('fa.facebook');
        $this->app_id      = $facebookAppParams['app_id'];
        $this->app_secret  = $facebookAppParams['app_secret'];
    }

    /**
     * facebook initialization
     *
     * @param string $redirectRoute Facebook redirect route name
     * @param array  $routeParams   Parameters of route
     *
     * @return boolean
     */
    public function init($redirectRoute, $routeParams = array())
    {
        //set default app & secret
        FacebookSession::setDefaultApplication($this->app_id, $this->app_secret);
        $this->helper = new FacebookRedirectLoginHelper($this->container->getParameter('base_url').$this->container->get('router')->generate($redirectRoute, $routeParams, true));

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->container->get('session')->start();
        }

        try {
            $this->session = $this->helper->getSessionFromRedirect();
        } catch (FacebookRequestException $ex) {
            $this->container->get('session')->getFlashBag()->add('error', $ex->getMessage());
            return new RedirectResponse($this->container->get('router')->generate($redirectRoute, $routeParams));
        } catch (\Exception $ex) {
            $this->container->get('session')->getFlashBag()->add('error', $ex->getMessage());
            return new RedirectResponse($this->container->get('router')->generate($redirectRoute, $routeParams));
        }

        if (isset($this->session)) {
            // Save the session
            $this->session = new FacebookSession($this->session->getToken());
        }
    }

    /**
     * get facebook session.
     *
     */
    public function getFacebookSession()
    {
        return $this->session;
    }

    /**
     * get facebook helper.
     *
     */
    public function getFacebookHelper()
    {
        return $this->helper;
    }
}
