<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This controller is used for admin side role management.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CoreController extends Controller
{
    /**
     * This method is used unset form fields.
     *
     * @return array
     */
    protected function getUnsetFormFields()
    {
        return array();
    }

    /**
     * This method is used to unset form fields.
     *
     * @param Object $form Object of form.
     */
    protected function unsetFormFields($form)
    {
        try {
            foreach ($this->getUnsetFormFields() as $field) {
                $form->remove($field);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * This method is used to check whether user is authenticated or not.
     *
     * @return bool
     */
    final protected function isAuth()
    {
        return $this->getSecurityAuthorizationChecker()->isGranted("IS_AUTHENTICATED_REMEMBERED");
    }

    /**
     * This method will check and give secutiry token_storage.
     *
     * @throws Service "security.context" not initialized!
     * @return \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     */
    final protected function getSecurityTokenStorage()
    {
        if (!$this->has('security.token_storage')) {
            throw new \Exception('Service "security.token_storage" not initialized!');
        }

        return $this->get('security.token_storage');
    }

    /**
     * This method will check and give secutiry authorization_checker.
     *
     * @throws Service "security.context" not initialized!
     * @return \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     */
    final protected function getSecurityAuthorizationChecker()
    {
        if (!$this->has('security.authorization_checker')) {
            throw new \Exception('Service "security.authorization_checker" not initialized!');
        }

        return $this->get('security.authorization_checker');
    }

    /**
     * This method will check and give logged in admin user.
     *
     * @return userobj
     */
    final protected function getLoggedInUser()
    {
        if ($this->isAuth()) {
            return $this->getSecurityTokenStorage()->getToken()->getUser();
        }

        return false;
    }

    /**
     * This method will check and give logged in admin user.
     *
     * @return mixed
     */
    final protected function checkIsValidLoggedInUser($request)
    {
        if (!$this->isAuth()) {
            $this->container->get('session')->getFlashBag()->add('info', $this->get('translator')->trans('Please sign in to continue.'));
            $queryParams = $request->query->all();
            $queryParamKeys = array_keys($queryParams);
            $loginQueryParams = array();
            if (count($queryParamKeys)) {
                foreach ($queryParamKeys as $queryParamKey) {
                    if (in_array($queryParamKey, array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content'))) {
                        $loginQueryParams[$queryParamKey] = (isset($queryParams[$queryParamKey]) ? $queryParams[$queryParamKey] : null);
                        if (isset($queryParams[$queryParamKey])) {
                            unset($queryParams[$queryParamKey]);
                        }
                    }
                }
            }
            $queryString = http_build_query($queryParams);
            $loginQueryString = http_build_query($loginQueryParams);

            $response  = new RedirectResponse($this->container->get('router')->generate('login').($loginQueryString ? '?'.$loginQueryString : null));

            if (!$request->isXmlHttpRequest()) {
                $response->headers->setCookie(new Cookie('frontend_redirect_after_login_path_info', $request->getPathInfo().($queryString ? '?'.$queryString : null), time() + 3600 * 24 * 7));
            }
            return $response->send();
        }

        return true;
    }

    /**
     * This method will check and give logged in admin user.
     *
     * @return userobj
     */
    final protected function checkIsValidAdUser($adUserId)
    {
        if (!$this->isAuth() || ($this->isAuth() && $adUserId != $this->getLoggedInUser()->getId())) {
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('You do not have permission to access this resource.'));
            return new RedirectResponse($this->container->get('router')->generate('fa_frontend_homepage'));
        }

        return true;
    }

    /**
     * This method will check and give logged in admin user.
     *
     * @return userobj
     */
    final protected function checkIsBusinessSaller($user)
    {
        if ($this->isAuth() && ($user->getRole() && $user->getRole()->getId() == RoleRepository::ROLE_BUSINESS_SELLER_ID)) {
            return true;
        }

        return false;
    }

    /**
     * This method will check and give logged in admin user.
     *
     * @return userobj
     */
    final protected function isAdminLoggedIn()
    {
        if ($this->isAuth()) {
            $adminRolesArray = $this->getRepository('FaUserBundle:Role')->getRoleArrayByType('A');
            $user = $this->getSecurityTokenStorage()->getToken()->getUser();
            $userRoles = $user->getRoles();
            foreach ($userRoles as $userRole) {
                if (in_array($userRole->getName(), $adminRolesArray)) {
                    return $user;
                }
            }
        }

        return false;
    }

    /**
    * This method is used to handle exception.
    *
    * @param Exception $e               Exception instance.
    * @param string    $loglevel        Log level.
    * @param string    $route           Route.
    * @param array     $routeParameters Route parameters.
    * @param string    $message         Message for error of sucess.
    *
    * @return \Symfony\Component\HttpFoundation\RedirectResponse
    */
    protected function handleException($e, $loglevel = 'error', $route = 'fa_admin_homepage', $routeParameters = array(), $message = null)
    {
        // initialize exception manager service
        $exceptionManager = $this->get('fa.exception.manager');
        $exceptionManager->handleException($e, $loglevel = 'error');
        $messageManager = $this->get('fa.message.manager');

        if ($message) {
            $messageManager->setFlashMessage($message, $loglevel);
        } else {
            $messageManager->setFlashMessage($exceptionManager->getMessage(), $loglevel);
        }

        if ($route) {
            return $this->redirect($this->generateUrl($route, $routeParameters));
        }
    }

    /**
     * This method is used to handle message to display.
     *
     * @param string  $message         Message to display.
     * @param unknown $route           Route.
     * @param array   $routeParameters Route parameters.
     * @param string  $type            Type of message.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function handleMessage($message, $route = null, $routeParameters = array(), $type = 'success', $jsRedirect = false)
    {
        $messageManager = $this->get('fa.message.manager');
        $messageManager->setFlashMessage($message, $type);

        if (preg_match("~^(?:ht)tps?://~i", $route)) {
            if ($jsRedirect) {
                return $this->render(
                    'FaCoreBundle:Default:iframeRedirect.html.twig',
                    array(
                        'redirectUrl' => $route,
                    )
                );
            } else {
                return $this->redirect($route);
            }
        } elseif ($route) {
            if ($jsRedirect) {
                return $this->render(
                    'FaCoreBundle:Default:iframeRedirect.html.twig',
                    array(
                        'redirectUrl' => $this->generateUrl($route, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL),
                    )
                );
            } else {
                return $this->redirect($this->generateUrl($route, $routeParameters));
            }
        }
    }

    /**
     * This method is used to return doctrine entiry manager.
     *
     * @return Object
     */
    protected function getEntityManager($connection = 'default')
    {
        return $this->getDoctrine()->getManager($connection);
    }

    /**
     * This function is used to get repository.
     *
     * @return Object
     */
    protected function getRepository($repository)
    {
        return $this->getEntityManager()->getRepository($repository);
    }

    /**
     * This function is used to get repository.
     *
     * @return Object
     */
    protected function getHistoryRepository($repository)
    {
        return $this->getEntityManager('history')->getRepository($repository);
    }

    /**
     * This function is used to get repository.
     *
     * @return Object
     */
    protected function getTiHistoryRepository($repository)
    {
        return $this->getEntityManager('ti_history')->getRepository($repository);
    }

    /**
     * This function is used to get repository table.
     *
     * @return object
     */
    protected function getRepositoryTable($repository)
    {
        return $this->getEntityManager()->getClassMetadata($repository)->getTableName();
    }

    /**
     * This function is used to get repository table.
     *
     * @return object
     */
    protected function getHistoryRepositoryTable($repository)
    {
        return $this->getEntityManager('history')->getClassMetadata($repository)->getTableName();
    }

    /**
     * This function is used to get repository table.
     *
     * @return object
     */
    protected function getTiHistoryRepositoryTable($repository)
    {
        return $this->getEntityManager('ti_history')->getClassMetadata($repository)->getTableName();
    }

    /**
     * Increment call click.
     *
     * @param integer $adId    Ad id.
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function adEnquiryIncrementAction($variableName, $adId, Request $request)
    {
        $cacheKey = 'ad_enquiry_'.$variableName.'_'.strtotime(date('Y-m-d')).'_'.$adId;
        CommonManager::updateCacheCounter($this->container, $cacheKey);
        return new Response();
    }

    /**
     * This function is used to validate api token
     *
     * @return JsonResponse
     */
    protected function validateApiToken($request, $type)
    {
        if (!$request->get('apiToken')) {
            return new JsonResponse(array('message' => $this->get('translator')->trans('Invalid api token.')));
        } elseif ($request->get('apiToken')) {
            $apiToken = $this->getRepository('FaCoreBundle:ApiToken')->findOneBy(array('token' => $request->get('apiToken'), 'type' => $type));
            if (!$apiToken) {
                return new JsonResponse(array('message' => $this->get('translator')->trans('Invalid api token.')));
            }
        }
    }

    /**
     * Fetch data from url.
     *
     * @param string  $url    Url.
     * @param string  $source Source file name.
     * @param boolean $binary Download as binary.
     */
    public function writeDataFromURL($url, $source, $binary = false)
    {
        $ch = curl_init($url);
        if ($binary == true) {
            $fp = fopen($source, 'wb');
        } else {
            $fp = fopen($source, 'w+');
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        if ($binary == true) {
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        }

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    /**
     * Increment call click.
     *
     * @param integer $userId    user id.
     * @param string  $fieldName field name.
     * @param string  $subField  field name.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function updateUserSiteViewCounterFieldAction($userId, $fieldName, Request $request)
    {
        $subField = $request->get('subField');
        $this->getRepository('FaUserBundle:UserSiteViewCounter')->updateUserSiteViewCounter($this->container, $userId, $fieldName, $subField);
        return new Response();
    }
}
