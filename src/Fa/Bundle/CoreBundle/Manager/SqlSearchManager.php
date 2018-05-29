<?php

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Fa\Bundle\CoreBundle\Manager\SqlSearchManager
 *
 * This manager is used to sql search for desktop and api.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class SqlSearchManager
{
    /**
     * Doctrine query builder object
     *
     * @var object
     */
    protected $queryBuilder;

    /**
     * Doctrine query builder object select
     *
     * @var array
     */
    protected $queryBuilderSelectFields = array();

    /**
     * Repository class object
     *
     * @var object
     */
    protected $repository;

    /**
     * Container service class object
     *
     * @var object
     */
    protected $container;

    /**
     * Doctrine class object
     *
     * @var object
     */
    protected $doctrine;

    /**
     * Array to store joined tabels in query builder
     *
     * @var array
     */
    public $joins = array();

    /**
     * initialise parameters
     *
     * @param object $repository    Repository object
     * @param array  $searchOptions Search options
     *
     * @return void
     */
    public function init($repository, $searchOptions = array())
    {
        $this->reset();
        $this->setRepository($repository);
        $this->prepareQuery($searchOptions);
    }

    /**
     * Reset parameters.
     */
    public function reset()
    {
        $this->queryBuilder             = null;
        $this->queryBuilderSelectFields = array();
        $this->joins                    = array();
    }

    /**
     * Set container service object.
     *
     * @param object $container container service object
     *
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set doctrine object.
     *
     * @param object $doctrine doctrine object
     *
     * @return void
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get doctrine query builder
     *
     * @return object
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Get doctrine query from query builder
     *
     * @param object $queryBuilder  Doctrine query builder
     *
     * @return object
     */
    public function getQuery($queryBuilder = null)
    {
        $queryBuilder = $queryBuilder ? $queryBuilder : $this->getQueryBuilder();

        return $queryBuilder->getQuery();
    }

    /**
     * Get result
     *
     * @param object $query Doctrine query
     *
     * @return object
     */
    public function getResult($query = null)
    {
        $query = $query ? $query : $this->getQuery();

        return $query->getResult();
    }

    /**
     * Get result as array
     *
     * @param object $query Doctrine query
     *
     * @return object
     */
    public function getArrayResult($query = null)
    {
        $query = $query ? $query : $this->getQuery();

        return $query->getArrayResult();
    }

    /**
     * Get result count
     *
     * @param object $queryBuilder  Doctrine query builder
     *
     * @return integer
     */
    public function getResultCount($queryBuilder = null)
    {
        $queryBuilder = $queryBuilder ? $queryBuilder : $this->getQueryBuilder();

        $queryBuilder->select('COUNT('.$queryBuilder->getRootAlias().'.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Get result count with distinct records
     *
     * @param object $queryBuilder  Doctrine query builder
     *
     * @return integer
     */
    public function getDistinctResultCount($queryBuilder = null)
    {
        $queryBuilder = $queryBuilder ? $queryBuilder : $this->getQueryBuilder();

        $queryBuilder->select('COUNT(DISTINCT '.$queryBuilder->getRootAlias().'.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Set doctrine query builder
     *
     * @param object $queryBuilder Doctrine query builder
     *
     * @return void
     */
    protected function setQueryBuilder($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
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
     * Get doctrine entity manager object.
     *
     * @return Object $doctrine entity manager object
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }

    /**
     * Set joins
     *
     * @param array $joins Joins array.
     *
     * @return void
     */
    protected function setJoins($joins)
    {
        $this->joins = array_merge($this->joins, $joins);
    }

    /**
     * Get joins
     *
     * @return array
     */
    protected function getJoins()
    {
        return $this->joins;
    }

    /**
     * Prepare query build based on search filters options
     *
     * @param array $searchOptions Search options
     *
     * @return object
     */
    protected function prepareQuery($searchOptions = array())
    {
        $repository = $this->getRepository();

        //Set base query builder
        $this->setQueryBuilder($repository->getBaseQueryBuilder());

        //Add default root table fields for select
        $this->addSelectField($repository::ALIAS);

        //Add addtional joins to query builder
        if (isset($searchOptions['query_joins']) && !empty($searchOptions['query_joins'])) {
            $this->addJoins($searchOptions['query_joins']);
        }

        //Add filters to query builder
        if (isset($searchOptions['query_filters']) && !empty($searchOptions['query_filters'])) {
            $this->addFilters($searchOptions['query_filters']);
        }

        //Add sorting options to query builder
        if (isset($searchOptions['query_sorter']) && !empty($searchOptions['query_sorter'])) {
            $this->addSorter($searchOptions['query_sorter']);
        }

        //Add addtional select fields to query builder
        if (isset($searchOptions['select_fields']) && !empty($searchOptions['select_fields'])) {
            $this->addSelectFields($searchOptions['select_fields']);
        }

        //Add static filters to query builder
        if (isset($searchOptions['static_filters']) && $searchOptions['static_filters']) {
            $this->addStaticFilters($searchOptions['static_filters']);
        }
    }

    /**
     * Add filters data
     *
     * @param array $filters Array of filters
     *
     * @return void
     */
    protected function addFilters($filters = array())
    {
        if (!empty($filters)) {
            foreach (array_keys($filters) as $nestedService) {
                $filters[$nestedService] = array_filter($filters[$nestedService], array($this, 'filterEmptyValues'));
                if (!empty($filters[$nestedService])) {
                    $joinServices = explode('__', $nestedService);
                    if (end($joinServices) !== false) {
                        $service = end($joinServices);
                    } else {
                        $service = $nestedService;
                    }

                    $serviceName = 'fa.'.$service.'.search';
                    $serviceObj  = $this->container->get($serviceName);

                    $serviceObj->init($this->getQueryBuilder(), $this->getJoins());
                    $serviceObj->addFilters($filters[$nestedService]);
                    $this->setQueryBuilder($serviceObj->getQueryBuilder());

                    //If direct join is not possible with root table then use nested service joins
                    $joinServices = explode('__', $nestedService);
                    if (count($joinServices) > 1) {
                        $className   = $this->getRepository()->getClassName();
                        $rootService = $this->getEntityManager()->getClassMetadata($className)->getTableName();
                        $joins       = array();
                        foreach ($joinServices as $service) {
                            $joins[$rootService] = array($service => array('type' => 'left'));
                            $rootService = $service;
                        }
                        $this->addJoins($joins);
                    } else {
                        $serviceObj->addJoin($this->getRepository());
                        $this->addSelectField($serviceObj->getRepositoryAlias());
                        $this->setJoins($serviceObj->getJoins());
                    }
                }
            }
        }
    }

    /**
     * Add sorting data
     *
     * @param array $sort Array of sort field and sort order
     *
     * @return object
     */
    protected function addSorter($sort = array())
    {
        if (!empty($sort)) {
            foreach (array_keys($sort) as $nestedService) {
                $sort[$nestedService] = array_filter($sort[$nestedService], array($this, 'filterEmptyValues'));
                if (!empty($sort[$nestedService])) {
                    $joinServices = explode('__', $nestedService);
                    if (end($joinServices) !== false) {
                        $service = end($joinServices);
                    } else {
                        $service = $nestedService;
                    }

                    $serviceName = 'fa.'.$service.'.search';
                    $serviceObj  = $this->container->get($serviceName);

                    $serviceObj->init($this->getQueryBuilder(), $this->getJoins());
                    $serviceObj->addSorter($sort[$nestedService]);
                    $this->setQueryBuilder($serviceObj->getQueryBuilder());

                    //If direct join is not possible with root table then use nested service joins
                    $joinServices = explode('__', $nestedService);
                    if (count($joinServices) > 1) {
                        $className   = $this->getRepository()->getClassName();
                        $rootService = $this->getEntityManager()->getClassMetadata($className)->getTableName();
                        $joins       = array();
                        foreach ($joinServices as $service) {
                            $joins[$rootService] = array($service => array('type' => 'left'));
                            $rootService = $service;
                        }
                        $this->addJoins($joins);
                    } else {
                        $serviceObj->addJoin($this->getRepository());
                        $this->addSelectField($serviceObj->getRepositoryAlias());
                        $this->setJoins($serviceObj->getJoins());
                    }
                }
            }
        }
    }

    /**
     * Add joins between tables
     *
     * @param array $joins Array of join tables
     *
     * @return void
     */
    protected function addJoins($joins = array())
    {
        if (!empty($joins)) {
            foreach (array_keys($joins) as $joinOf) {
                $joinOfServiceName = 'fa.'.$joinOf.'.search';
                $joinOfServiceObj  = $this->container->get($joinOfServiceName);

                $this->addSelectField($joinOfServiceObj->getRepositoryAlias());
                $joins[$joinOf] = array_filter($joins[$joinOf]);
                if (!empty($joins[$joinOf])) {
                    foreach ($joins[$joinOf] as $joinWith => $joinOptions) {
                        $joinWithServiceName = 'fa.'.$joinWith.'.search';
                        $joinWithServiceObj  = $this->container->get($joinWithServiceName);

                        $joinWithServiceObj->init($this->getQueryBuilder(), $this->getJoins());
                        $joinWithServiceObj->addJoin($joinOfServiceObj, $joinOptions);

                        $this->addSelectField($joinWithServiceObj->getRepositoryAlias());
                        $this->setQueryBuilder($joinWithServiceObj->getQueryBuilder());
                        $this->setJoins($joinWithServiceObj->getJoins());
                    }
                }
            }
        }
    }

    /**
     * Collect select field for selecting in doctrine query builder
     *
     * @param array $joins Array of join tables
     *
     * @return void
     */
    protected function addSelectField($field = null)
    {
        if ($field) {
            $this->queryBuilderSelectFields[] = $field;
        }
    }

    /**
     * Add select fields in doctrine query builder
     *
     * @param array $selectFields Array of select fields
     *
     * @return void
     */
    protected function addSelectFields($selectFields = array())
    {
        if (!empty($selectFields)) {
            $fields = array();
            foreach (array_keys($selectFields) as $service) {
                $selectFields[$service] = array_unique(array_filter($selectFields[$service]));
                if (!empty($selectFields[$service])) {
                    $serviceName = 'fa.'.$service.'.search';
                    $serviceObj  = $this->container->get($serviceName);

                    foreach ($selectFields[$service] as $selectField) {
                        if (preg_match('/IDENTITY\(([a-zA-Z0-9_]+)\)/i', $selectField)) {
                            $fields[]= preg_replace('/IDENTITY\(([a-zA-Z0-9_]+)\)/i', 'IDENTITY('.$serviceObj->getRepositoryAlias().'.$1)', $selectField);
                        } else {
                            $fields[] = $serviceObj->getRepositoryAlias().'.'.$selectField;
                        }
                    }
                }
            }

            if (!empty($fields)) {
                $this->getQueryBuilder()->select($fields);
            }
        } elseif (!count(array_unique($this->queryBuilderSelectFields))) {
            $this->getQueryBuilder()->select($this->queryBuilderSelectFields);
        }
    }

    /**
     * Add static filters in doctrine query builder
     *
     * @param string $staticFilters Static filters to append
     *
     * @return void
     */
    protected function addStaticFilters($staticFilters = null)
    {
        if ($staticFilters) {
            $this->getQueryBuilder()->andWhere($staticFilters);
        }
    }

    /**
     * Filter empty values
     *
     * @param mixed $value value
     *
     * @return string
     */
    protected function filterEmptyValues($value)
    {
        if ($value === 0) {
            return true;
        }

        return ($value !== null && $value !== false && $value !== '' && $value != '[]');
    }
}
