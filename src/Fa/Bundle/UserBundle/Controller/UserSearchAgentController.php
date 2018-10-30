<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Controller;

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\UserBundle\Repository\UserSearchAgentRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for save search agent.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSearchAgentController extends CoreController
{
    /**
     * Save search agent.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function saveSearchAgentAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $searchParams       = unserialize($request->get('searchParams'));
            $entityCacheManager = $this->container->get('fa.entity.cache.manager');
            $searchParams       = $this->filterSeachAgentParams($searchParams);
            $userSearchAgentId  = null;
            $userId             = $request->get('userId', null);
            $halfAccountUser    = null;

            if ($this->isAuth()) {
                $userId = $this->getLoggedInUser()->getId();
            } elseif ($userId) {
                $halfAccountUser = $this->getRepository('FaUserBundle:User')->find($userId);
                if (!$halfAccountUser) {
                    $error = $this->get('translator')->trans('Unable to find user.', array(), 'frontend-save-search');
                } elseif ($halfAccountUser->getStatus() && $halfAccountUser->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                    $error = $this->get('translator')->trans('Your account was blocked.', array(), 'frontend-save-search');
                }
            }

            if (!$error && ($this->isAuth() || $halfAccountUser)) {
                //check for same search agent using search params.
                $userSearchAgent = $this->getRepository('FaUserBundle:UserSearchAgent')->getUserSearchAgentByParameters($searchParams, $userId);
                if (!$userSearchAgent) {
                    //get search name from search params.
                    $searchAgentName = '';
                    if (isset($searchParams['search']['keywords']) && $searchParams['search']['keywords']) {
                        $searchAgentName = $searchParams['search']['keywords'];
                    }
                    if (isset($searchParams['search']['item__category_id']) && $searchParams['search']['item__category_id']) {
                        if (isset($searchParams['search']['keywords']) && $searchParams['search']['keywords']) {
                            $searchAgentName .= ' in ';
                        }
                        $searchAgentName .= $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $searchParams['search']['item__category_id']);
                    }

                    // fetch location from cookie.
                    $cookieLocation  = $this->container->get('request_stack')->getCurrentRequest()->cookies->get('location');
                    if ($cookieLocation && $cookieLocation != CommonManager::COOKIE_DELETED) {
                        $cookieLocation = get_object_vars(json_decode($cookieLocation));
                        if (isset($cookieLocation['locality'])) {
                            $searchAgentName .= ', '.$cookieLocation['locality'];
                        }
                        if (isset($cookieLocation['town'])) {
                            $searchAgentName .= ', '.$cookieLocation['town'];
                        }
                        if (isset($cookieLocation['county'])) {
                            $searchAgentName .= ', '.$cookieLocation['county'];
                        }
                    } else {
                        $searchAgentName .= ', United Kingdom';
                    }
                    $searchAgentName = trim($searchAgentName, ', ');
                    //if no nam
                    if (!strlen($searchAgentName)) {
                        $searchAgentName = 'Saved search';
                    }
                    $userSearchAgent = $this->getRepository('FaUserBundle:UserSearchAgent')->addSearchAgent($searchAgentName, $userId, $searchParams);
                }

                if ($this->isAuth()) {
                    $htmlContent = $this->renderView('FaUserBundle:UserSearchAgent:ajaxShowSaveSearchAgent.html.twig', array('userSearchAgent' => $userSearchAgent));
                } elseif ($halfAccountUser) {
                    $htmlContent = $this->renderView('FaUserBundle:UserSearchAgent:ajaxShowSaveSearchAgent.html.twig', array('userSearchAgent' => $userSearchAgent, 'halfAccountUser' => $halfAccountUser));
                }
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Remove unwanted search parameters.
     *
     * @param array $searchParams Search param array.
     *
     * @return array
     */
    private function filterSeachAgentParams($searchParams)
    {
        // remove unwanted search parameters.
        if (isset($searchParams['search']) && count($searchParams['search'])) {
            //unset map flag.
            if (isset($searchParams['search']['map'])) {
                unset($searchParams['search']['map']);
            }
            //unset sorting.
            if (isset($searchParams['search']['sort_field'])) {
                unset($searchParams['search']['sort_field']);
            }
            if (isset($searchParams['search']['sort_ord'])) {
                unset($searchParams['search']['sort_ord']);
            }
            if (isset($searchParams['search']['page'])) {
                unset($searchParams['search']['page']);
            }
        }

        return $searchParams;
    }
    /**
     * Save search agent.
     *
     * @param $userSearchAgentId
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function updateSearchAgentAction($userSearchAgentId, Request $request)
    {
        $error = '';

        if ($request->isXmlHttpRequest()) {
            $userId = null;

            if ($request->get('guid')) {
                $loggedinUser = $this->getRepository('FaUserBundle:User')->findOneBy(array('guid' => $request->get('guid')));
                if ($loggedinUser) {
                    $userId = $loggedinUser->getId();
                }
            } else {
                if ($this->isAuth()) {
                    $userId = $this->getLoggedInUser()->getId();
                }
            }

            if (!$userId) {
                $error = $this->get('translator')->trans('You are trying to do unauthorized access.', array(), 'frontend-search-result');
                return new JsonResponse(array('error' => $error, 'success' => ''));
            }

            $userSearchAgent = $this->getRepository('FaUserBundle:UserSearchAgent')->find($userSearchAgentId);
            $emailAlert      = $request->get('emailAlert', 1);

            if (!$userSearchAgent) {
                $error = $this->get('translator')->trans('No saved search found.', array(), 'frontend-save-search');
            } elseif ($userId != $userSearchAgent->getUser()->getId()) {
                $error = $this->get('translator')->trans('You are trying to do unauthorized access.', array(), 'frontend-search-result');
            } else {
                $userSearchAgent->setIsEmailAlerts($emailAlert);
                $this->getEntityManager()->persist($userSearchAgent);
                $this->getEntityManager()->flush($userSearchAgent);
                return new JsonResponse(array('error' => '', 'success' => $this->get('translator')->trans('Your save search email alerts has been updated successfully.', array(), 'frontend-search-result')));
            }
            return new JsonResponse(array('error' => $error, 'success' => ''));
        } else {
            return new Response();
        }
    }

    /**
     * Show user saved searches.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function listSearchAgentAction(Request $request)
    {
        $guid         = null;
        $loggedinUser = null;
        $numberOfRecordsPerPage = 10;
        //Reverted Code that did for FFR-2995
        if ($request->query->get('guid')) {
            $loggedinUser = $this->getRepository('FaUserBundle:User')->findOneBy(array('guid' => $request->query->get('guid')));
        } else {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $loggedinUser = $this->getLoggedInUser();
        }

        if ($request->query->get('userSearchAgentId')) {
            $recordPositionArray = $this->getRepository('FaUserBundle:UserSearchAgent')->getPageNumberBySearchAgentId($loggedinUser->getId(), $request->get('userSearchAgentId'), $this->container);
            $recordPage = ceil($recordPositionArray['position'] / $numberOfRecordsPerPage);
            if ($recordPage > 1 && !$request->get('page')) {
                $queryStringParams = $request->query->all();
                $neededParams      = array('page' => $recordPage, 'userSearchAgentId' => $request->get('userSearchAgentId'));
                $routeParams       = array_merge($queryStringParams, $neededParams);
                return $this->redirect($this->generateUrl('list_search_agent', $routeParams));
            }
        }

        if ($loggedinUser) {
            // initialize search filter manager service and prepare filter data for searching
            $this->get('fa.searchfilters.manager')->init($this->getRepository('FaUserBundle:UserSearchAgent'), $this->getRepositoryTable('FaUserBundle:UserSearchAgent'));
            $data = $this->get('fa.searchfilters.manager')->getFiltersData();

            $data['select_fields']  = array('user_search_agent' => array('id', 'name', 'criteria', 'is_email_alerts'));
            $data['static_filters'] = UserSearchAgentRepository::ALIAS.'.user = '.$loggedinUser->getId();
            $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaUserBundle:UserSearchAgent'), $data);
            $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
            $query        = $queryBuilder->getQuery();

            // initialize pagination manager service and prepare listing with pagination based of data
            $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
            $this->get('fa.pagination.manager')->init($query, $page, $numberOfRecordsPerPage);
            $pagination = $this->get('fa.pagination.manager')->getPagination();

            $parameters = array(
                'pagination'  => $pagination,
            );

            if ($request->query->get('guid')) {
                $parameters['guid'] = $request->query->get('guid');
            }

            if ($request->query->get('userSearchAgentId')) {
                $parameters['userSearchAgentId'] = $request->query->get('userSearchAgentId');
            }

            return $this->render('FaUserBundle:UserSearchAgent:listSearchAgent.html.twig', $parameters);
        } else {
            $currentUrl = urldecode($request->getUri());
            if (substr_count($currentUrl, '?') > 1) {
                $redirectUrl = substr_replace($currentUrl, '&', strrpos($currentUrl, '?'), 1);
                $response  = new RedirectResponse($redirectUrl);
                return $response->send();
            }
            // redirect to home page
            $this->container->get('session')->getFlashBag()->add('error', $this->get('translator')->trans("You don't have permission to access this resource."));
            $response  = new RedirectResponse($this->container->get('router')->generate('fa_frontend_homepage'));
            return $response->send();
        }
    }

    /**
     * Create alert.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxCreateAlertAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $adId   = $request->get('adId');
            $userId = $request->get('userId', null);
            $ad     = $this->getRepository('FaAdBundle:Ad')->find($adId);
            //check for ad.
            if (!$ad || ($ad && $ad->getStatus()->getId() != EntityRepository::AD_STATUS_LIVE_ID)) {
                $error = $this->get('translator')->trans('Unable to find Live Ad.', array(), 'frontend-create-alert');
            } elseif ($this->isAuth()) {
                $loggedinUser    = $this->getLoggedInUser();
                $userSearchAgent = $this->getRepository('FaUserBundle:UserSearchAgent')->saveUserSearch($ad, $loggedinUser, $this->container);
                if ($request->isXmlHttpRequest()) {
                    $htmlContent = $this->renderView('FaUserBundle:UserSearchAgent:ajaxShowCreatedAlert.html.twig', array('userSearchAgent' => $userSearchAgent));
                }
            } elseif ($userId) {
                $halfAccountUser = $this->getRepository('FaUserBundle:User')->find($userId);
                if (!$halfAccountUser) {
                    $error = $this->get('translator')->trans('Unable to find user.', array(), 'frontend-create-alert');
                } elseif ($halfAccountUser->getStatus() && $halfAccountUser->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                    $error = $this->get('translator')->trans('Your account was blocked.', array(), 'frontend-create-alert');
                }

                if (!$error) {
                    $userSearchAgent = $this->getRepository('FaUserBundle:UserSearchAgent')->saveUserSearch($ad, $halfAccountUser, $this->container);
                    if ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaUserBundle:UserSearchAgent:ajaxShowCreatedAlert.html.twig', array('userSearchAgent' => $userSearchAgent, 'halfAccountUser' => $halfAccountUser));
                    }
                }
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Save search agent.
     *
     * @param $userSearchAgentId
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function deleteSearchAgentAction($userSearchAgentId, Request $request)
    {
        $error      = '';
        $successMsg = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $userSearchAgent = $this->getRepository('FaUserBundle:UserSearchAgent')->find($userSearchAgentId);
                $userId          = $this->getLoggedInUser()->getId();
                $emailAlert      = $request->get('emailAlert', 1);
                $deleteManager   = $this->get('fa.deletemanager');
                $messageManager  = $this->get('fa.message.manager');
                $isFlashMessage  = $request->get('isFlashMessage', 1);

                if (!$userSearchAgent) {
                    $error = $this->get('translator')->trans('No saved search found.', array(), 'frontend-save-search');
                    $messageManager->setFlashMessage($error, 'error');
                } elseif ($userId != $userSearchAgent->getUser()->getId()) {
                    $error = $this->get('translator')->trans('You are trying to do unauthorized access.', array(), 'frontend-search-result');
                    $messageManager->setFlashMessage($error, 'error');
                }

                try {
                    $deleteManager->delete($userSearchAgent);
                    if ($isFlashMessage == 1) {
                        $successMsg = $this->get('translator')->trans('Saved search has been deleted successfully..', array(), 'frontend-my-saved-searches');
                        $messageManager->setFlashMessage($successMsg, 'success');
                    }
                } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
                    $error = $this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'frontend-my-saved-searches');
                    $messageManager->setFlashMessage($error, 'error');
                } catch (\Exception $e) {
                    $error = $this->get('translator')->trans("Problem in deleting saved search.", array(), 'frontend-my-saved-searches');
                    $messageManager->setFlashMessage($error, 'error');
                }
            }
            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        } else {
            return new Response();
        }
    }

    /**
     * Save search agent.
     *
     * @param $userSearchAgentId
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function renameSearchAgentAction($userSearchAgentId, Request $request)
    {
        $error      = '';
        $successMsg = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $objSearchAgent = $this->getRepository('FaUserBundle:UserSearchAgent')->find($userSearchAgentId);
                $userId         = $this->getLoggedInUser()->getId();
                $title          = $request->get('userSearchAgentTitle');

                if (!$objSearchAgent) {
                    $error = $this->get('translator')->trans('No saved search found.', array(), 'frontend-save-search');
                } elseif ($userId != $objSearchAgent->getUser()->getId()) {
                    $error = $this->get('translator')->trans('You are trying to do unauthorized access.', array(), 'frontend-search-result');
                }

                try {
                    $objSearchAgent->setName($title);
                    $this->getEntityManager()->persist($objSearchAgent);
                    $this->getEntityManager()->flush($objSearchAgent);
                    $successMsg = $this->get('translator')->trans('Saved search has been renamed successfully..', array(), 'frontend-my-saved-searches');
                } catch (\Exception $e) {
                    $error = $this->get('translator')->trans("Problem in editing saved search.", array(), 'frontend-my-saved-searches');
                }
            }
            return new JsonResponse(array('error' => $error, 'successMsg' => $successMsg));
        } else {
            return new Response();
        }
    }
}
