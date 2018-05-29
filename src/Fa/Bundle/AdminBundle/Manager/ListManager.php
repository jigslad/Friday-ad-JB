<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdminBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List manager.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ListManager
{

    /**
     * Request stack.
     *
     * @var object
     */
    protected $requestStack;

    /**
     * Session.
     *
     * @var object
     */
    protected $session;

    /**
     * Query builder.
     *
     * @var object
     */
    protected $queryBuilder;

    /**
     * Repository.
     *
     * @var object
     */
    protected $repository;

    /**
     * Max per page.
     *
     * @var integer
     */
    protected $maxPerPage;

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Construct.
     *
     * @param RequestStack $requestStack
     * @param Session      $session
     * @param number       $maxPerPage
     */
    public function __construct(RequestStack $requestStack, Session $session, $maxPerPage = 10, ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->session      = $session;
        $this->maxPerPage   = $maxPerPage;
        $this->container    = $container;

        $this->session->start();

        $this->setData();
    }

    /**
     * Init.
     *
     * @param object $repository
     */
    public function init($repository)
    {
        $this->setRepository($repository);

        $this->setData();

        $this->setQueryBuilder($this->prepareQueryBuilder());
    }

    /**
     * Set repository.
     *
     * @param object $repository
     */
    protected function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get repository.
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set query builder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Get query builder.
     */
    protected function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Get request.
     */
    protected function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Get session.
     */
    protected function getSession()
    {
        return $this->session;
    }

    /**
     * Get current route.
     */
    protected function getCurrentRoute()
    {
        return $this->container->get('request_stack')->getCurrentRequest()->get('_route');
    }

    /**
     * Get key.
     */
    protected function getKey()
    {
        return $this->getCurrentRoute();
    }

    /**
     * Set data.
     */
    protected function setData()
    {
        $data = $this->getAllData();

        $key = $this->getKey();

        $data[$key] = array(
                'sorter' => $this->getSorterData(),
                'pager' => $this->getPagerData(),
                'search' => $this->getSearchData(),
        );

        $this->getSession()->set('list_manager', serialize($data));
    }

    /**
     * Get all data.
     *
     * @return mixed
     */
    protected function getAllData()
    {
        return $this->getSession()->get('list_manager') ? unserialize($this->getSession()->get('list_manager')) : null;
    }

    /**
     * Get data.
     *
     * @param string $subkey
     *
     * @return mixed
     */
    public function getData($subkey = null)
    {
        $data = $this->getAllData();

        $key = $this->getKey();

        return $subkey ? (isset($data[$key][$subkey]) ? $data[$key][$subkey] : null) : (isset($data[$key]) ? $data[$key] : null);
    }

    /**
     * Get sorter data.
     *
     * @return array
     */
    protected function getSorterData()
    {
        $data = $this->getData('sorter');

        return array(
                'field' => $this->container->get('request_stack')->getCurrentRequest()->get('field', (isset($data['field']) && $data['field']) ? $data['field'] : 'id'),
                'sort'  => $this->container->get('request_stack')->getCurrentRequest()->get('sort', (isset($data['sort']) && $data['sort']) ? $data['sort'] : 'desc'),
        );
    }

    /**
     * Get pager data.
     *
     * @return array
     */
    protected function getPagerData()
    {
        $data = $this->getData('pager');

        return array('page' => $this->container->get('request_stack')->getCurrentRequest()->get('page', (isset($data['page']) && $data['page'] && !$this->doResetPage()) ? $data['page'] : 1));
    }

    /**
     * Do reset page.
     *
     * @return boolean
     */
    protected function doResetPage()
    {
        return ($this->container->get('request_stack')->getCurrentRequest()->get('search') || $this->container->get('request_stack')->getCurrentRequest()->get('reset') || $this->container->get('request_stack')->getCurrentRequest()->get('deleted'));
    }

    /**
     * Get search data.
     *
     * @return array
     */
    protected function getSearchData()
    {
        $data = $this->getData('search');

        $searchData = $this->container->get('request_stack')->getCurrentRequest()->get('search', (isset($data) && $data) ? $data : array());

        return isset($searchData['reset']) ? array() : $searchData;
    }

    /**
     * Prepare query builder.
     */
    protected function prepareQueryBuilder()
    {
        return $this->getRepository()->getQueryBuilder($this->getData());
    }

    /**
     * Get query.
     */
    protected function getQuery()
    {
        return $this->getQueryBuilder()->getQuery();
    }

    /**
     * Get pagerfanta.
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getPagerfanta()
    {
        $adapter = new DoctrineORMAdapter($this->getQuery());

        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($this->maxPerPage);

        $pagerData = $this->getData('pager');

        $pagerfanta->setCurrentPage($pagerData['page']);

        return $pagerfanta;
    }
}
