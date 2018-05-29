<?php

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Fa\Bundle\CoreBundle\EventListener\ResourceAuthorizationManager
 *
 * This listener is used to autorize the resource.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class ResourceAuthorizationManager
{
    protected $security;

    protected $doctrine;

    protected $container;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * isGranted method checked whether current user has access to resource or not.
     *
     * @param string $resourceIdentifier resource to authenticate
     *
     * @return boolean
     */
    public function isGranted($resourceIdentifier)
    {
        // if admin allow all access.
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // check if resource is in allowed then grant it.
        if (in_array($resourceIdentifier, $this->allowedResources())) {
            return true;
        }

        $resources = array();
        $em = $this->doctrine->getManager();
        if (CommonManager::isAuth($this->container)) {
            $loggedinUser = $this->container->get('security.token_storage')->getToken()->getUser();
            $resources = $em->getRepository('FaUserBundle:Resource')->getResourcesArrayByUserId($loggedinUser->getId(), $this->container);
        }

        if (is_array($resources) && in_array($resourceIdentifier, $resources)) {
            return true;
        }
        //$currentRoute = $event->getRequest()->get('_route');
        /*
        $resource = $this->getResource($resourceIdentifier);
        if ($resource instanceof \Fa\Bundle\UserBundle\Entity\Resource) {
            $roleResourcePermissions = $resource->getRoleResourcePermissions();
            if (count($roleResourcePermissions) > 0) {
                foreach ($roleResourcePermissions as $roleResourcePermission) {
                    $role = $roleResourcePermission->getRole();
                    if ($this->security->isGranted($role->getRole())) {
                        return true;
                    }
                }
            }

        }*/

        return false;
    }

    /**
     * Fetch resource from database.
     *
     * @param string $currentRoute name of current route.
     *
     * @return Fa\Bundle\UserBundle\Entity\Resource The resource
     */
    public function getResource($currentRoute)
    {
        try {
            $em = $this->doctrine->getManager();

            $query = $em
            ->createQueryBuilder()
            ->select('node')
            ->from('FaUserBundle:Resource', 'node')
            ->where('node.resource LIKE :resource')->setParameter('resource', $currentRoute)
            ->getQuery();

            return $query->getSingleResult();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set secutiry context.
     *
     * @param AuthorizationChecker $securityContext AuthorizationChecker instance
     */
    public function setSecurityAuthorizationChecker(AuthorizationChecker $securityContext)
    {
        $this->security = $securityContext;
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

    /**
     * Set container object.
     *
     * @param Object $container Container object.
     */
    public function setServiceContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Allowed resources list.
     *
     * @return array
     */
    public function allowedResources()
    {
        $allowedResources = array(
                                'category_ajax_term_admin',
                            );


        return $allowedResources;
    }
}
