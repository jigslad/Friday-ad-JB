<?php

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fa\Bundle\CoreBundle\Manager\SearchFiltersManager
 *
 * This manager is used to prepare search filters to prepare query builder.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class SearchFiltersManager
{
    /**
     * Requerst object
     *
     * @var object
     */
    protected $requestStack;

    /**
     * Repository class object
     *
     * @var object
     */
    protected $repository;

    /**
     * Repository table name
     *
     * @var string
     */
    protected $repositoryTable;

    /**
     * Search form name
     *
     * @var string
     */
    protected $searchName;

    /**
     * Filter data
     *
     * @var string
     */
    private $filtersData;

    /**
     * Search params passed manually if not want from request
     *
     * @var array
     */
    private $searchParams;

    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     */
    public function __construct(RequestStack $requestStack, ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->container    = $container;
    }

    /**
     * Initialise parameters
     *
     * @param object $repository      repository class
     * @param string $repositoryTable repository table name
     *
     * @return void
     */
    public function init($repository, $repositoryTable, $searchName = 'search', $searchParams = array())
    {
        $this->searchName = $searchName;

        $this->setRepository($repository);
        $this->setRepositoryTable($repositoryTable);

        $this->searchParams = $searchParams;

        $this->filtersData  = array();
        $this->setFiltersData();
    }

    /**
     * Set repository
     *
     * @param object $repository repository class
     *
     * @return void
     */
    protected function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get repository
     *
     * @return object
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set repository table name
     *
     * @param object $repository repository class
     *
     * @return void
     */
    protected function setRepositoryTable($repositoryTable)
    {
        $this->repositoryTable = $repositoryTable;
    }

    /**
     * Get repository table name
     *
     * @return string
     */
    protected function getRepositoryTable()
    {
        return $this->repositoryTable;
    }

    /**
     * Get repository object
     *
     * @return object
     */
    protected function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Set filter data
     *
     * @return void
     */
    protected function setFiltersData()
    {
        $this->filtersData = array(
                                'sorter'       => $this->getSorterData(),
                                'pager'        => $this->getPagerData(),
                                'search'       => $this->getSearchData(),
                                'query_filters' => $this->getQueryFilterData(),
                                'query_sorter' => $this->getQuerySorterData(),
        );
    }

    /**
     * Get filter data
     *
     * @return array
     */
    public function getFiltersData()
    {
        return $this->filtersData;
    }

    /**
     * Get filter data
     *
     * @return array
     */
    public function getData($key = null)
    {
        return ($key ? (isset($this->filtersData[$key]) ? $this->filtersData[$key] : null) : null);
    }

    /**
     * Get sorting data for preparing query builder
     *
     * @return array
     */
    protected function getQuerySorterData()
    {
        $data      = $this->getData('sorter');
        $sortField = $this->getSearchParamsDataByKey('sort_field');
        $sortOrd   = $this->getSearchParamsDataByKey('sort_ord');

        $sortField = $sortField ? $sortField : ((isset($data['sort_field']) && $data['sort_field']) ? $data['sort_field'] : $this->getRepositoryTable().'__id');
        $sortOrd   = $sortOrd ? $sortOrd : ((isset($data['sort_ord']) && $data['sort_ord']) ? $data['sort_ord'] : 'desc');

        if ($sortField && $sortOrd) {
            $sortData   = array();
            $tableField = explode('__', $sortField);
            $field      = null;
            if (end($tableField) !== false) {
                $field = end($tableField);
            }
            array_pop($tableField);
            $table = join('__', $tableField);
            if ($table && $field) {
                $sortData[$table][$field] = $sortOrd;
            }

            $data = $sortData;
        }

        return $data;
    }

    /**
     * Get sorter data
     *
     * @return array
     */
    protected function getSorterData()
    {
        $data      = $this->getData('sorter');
        $sortField = $this->getSearchParamsDataByKey('sort_field');
        $sortOrd   = $this->getSearchParamsDataByKey('sort_ord');
        return array(
                'sort_field' => $sortField ? $sortField :((isset($data['sort_field']) && $data['sort_field']) ? $data['sort_field'] : $this->getRepositoryTable().'__id'),
                'sort_ord'   => $sortOrd ? $sortOrd : ((isset($data['sort_ord']) && $data['sort_ord']) ? $data['sort_ord'] : 'desc'),
        );
    }

    /**
     * Get pager data for preparing query builder
     *
     * @return array
     */
    protected function getPagerData()
    {
        $data = $this->getData('pager');
        $page = $this->getSearchParamsDataByKey('page');
        return array('page' => $page ? $page : ((isset($data['page']) && $data['page']) ? $data['page'] : 1));
    }

    /**
     * Get filter data for preparing query builder
     *
     * @return array
     */
    protected function getQueryFilterData()
    {
        $searchData = $this->getSearchParamsDataByKey($this->searchName);
        $searchData = $searchData ? $searchData : ($this->getData('search') ? $this->getData('search') : array());
        if (!empty($searchData)) {
            $filters = array();
            foreach ($searchData as $field => $value) {
                $tableField = explode('__', $field);
                $field      = null;
                if (end($tableField) !== false) {
                    $field = end($tableField);
                }
                array_pop($tableField);
                $table      = join('__', $tableField);
                $table      = ($table != 'search' && $table != '_token')? $table : '';
                if ($table && $field) {
                    $filters[$table][$field] = $value;
                }
            }

            $searchData = $this->checkFromToFieldInFilters($filters);
        }

        return $searchData;
    }

    /**
     * Get filter data for populate search form
     *
     * @return array
     */
    protected function getSearchData()
    {
        $data       = $this->getData('search');
        $searchData = $this->getSearchParamsDataByKey($this->searchName);
        $searchData = $searchData ? $searchData : ((isset($data) && $data) ? $data : array());

        return isset($searchData['reset']) ? array() : $searchData;
    }

    /**
     * Check range field for query filter data.
     *
     * @return array
     */
    protected function checkFromToFieldInFilters($filters)
    {
        $searchData = $filters;

        foreach ($searchData as $table => $fields) {
            foreach ($fields as $field => $value) {
                $tempFromField = strstr($field, '_from', true);
                $tempToField = strstr($field, '_to', true);
                $tempField = $tempFromField ? $tempFromField : ($tempToField ? $tempToField : '');
                if ($tempField) {
                    $fromField      = $tempField.'_from';
                    $toField        = $tempField.'_to';
                    $fromToField    = $tempField.'_from_to';
                    $fromToFieldVal = (isset($fields[$fromField]) ? $fields[$fromField] : '').'|'.(isset($fields[$toField]) ? $fields[$toField] : '');

                    if ($fromToFieldVal !== '|') {
                        $filters[$table][$fromToField] = $fromToFieldVal;
                        unset($filters[$table][$fromField], $filters[$table][$toField]);
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * Get filter data for preparing query builder
     *
     * @return array
     */
    protected function getSearchParamsDataByKey($key)
    {
        if ($this->searchParams && count($this->searchParams)) {
            if (isset($this->searchParams[$key])) {
                return $this->searchParams[$key];
            }
        } else {
            if ($this->container->get('request_stack')->getCurrentRequest()) {
                return $this->container->get('request_stack')->getCurrentRequest()->get($key, null);
            }
        }

        return null;
    }
}
