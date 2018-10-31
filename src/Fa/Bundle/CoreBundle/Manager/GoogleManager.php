<?php
namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Fa\Bundle\CoreBundle\Manager\GoogleManager
 *
 * This manager is used to get facebbok information.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class GoogleManager
{
    /**
     * Container service class object
     *
     * @var object
     */
    private $container;
    
    /**
     * google client id
     *
     * @var string
     */
    private $client_id;

    /**
     * google client secret
     *
     * @var string
     */
    private $client_secret;
    
    /**
     * google client
     *
     * @var object
     */
    private $client;
    
    /**
     * google oauth
     *
     * @var object
     */
    private $oauth;
    
    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container   = $container;
        $googleAppParams = $this->container->getParameter('fa.google');
        $this->client_id      = $googleAppParams['client_id'];
        $this->client_secret  = $googleAppParams['client_secret'];
    }

    /**
     * google api initialization
     *
     * @param string $redirectRoute Google redirect route name
     * @param array  $routeParams   Parameters of route
     *
     * @return boolean
     */
    public function init($scopes, $redirectRoute, $routeParams = array())
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->container->get('session')->start();
        }
        $this->client = new \Google_Client();
        $this->client->setClientId($this->client_id);
        $this->client->setClientSecret($this->client_secret);
        $this->client->setRedirectUri($this->container->getParameter('base_url').$this->container->get('router')->generate($redirectRoute, $routeParams, true));
        $this->client->setScopes($scopes);
        
        $this->oauth = new \Google_Service_Oauth2($this->client);
    }
    
    /**
     * get google client.
     *
     */
    public function getGoogleClient()
    {
        return $this->client;
    }
    
    /**
     * get google oauth.
     *
     */
    public function getGoogleOauth()
    {
        return $this->oauth;
    }
}
