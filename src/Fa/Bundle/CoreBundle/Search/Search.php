<?php

namespace Fa\Bundle\CoreBundle\Search;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Fa\Bundle\CoreBundle\Search\Search
 *
 * This class is used for common sql search functionalities.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
trait Search
{
    /**
     * Repository alias
     *
     * @var string
     */
    public $associationName;

    /**
     * Repository alias
     *
     * @var string
     */
    public $repositoryAlias;

    /**
     * Flag for table is joined or not in query builder
     *
     * @var boolean
     */
    public $isJoined = false;

    /**
     * Flag for table is joined or not in query builder
     *
     * @var array
     */
    public $joins = array();

    /**
     * Doctrine query builder object
     *
     * @var object
     */
    protected $queryBuilder;

    /**
     * initialise parameters
     *
     * @param object $queryBuilder
     *
     * @return void
     */
    public function init($queryBuilder, $joins = array())
    {
        $this->queryBuilder = $queryBuilder;
        $this->joins        = $joins;
    }

    /**
     * Get doctrine query builder.
     *
     * @return object
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Get current repository alias.
     *
     * @return string
     */
    public function getRepositoryAlias()
    {
        return $this->repositoryAlias ? $this->repositoryAlias : self::ALIAS;
    }

    /**
     * Set repository alias.
     *
     * @param string $alias repository alias
     *
     * @return void
     */
    public function setRepositoryAlias($alias = null)
    {
        $this->repositoryAlias = $alias;
    }

    /**
     * Get joins
     *
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * Get association name of current repository with passed repository.
     *
     * @param object $repository repository instance
     *
     * @return string
     */
    public function getAssociationNameWithRepository($repository)
    {
        // If associationName set from service then use it.
        if ($this->associationName) {
            return $this->associationName;
        }

        $associationName = $this->getClassMetadata()->getTableName();
        $associations    = $repository->getClassMetadata()->getAssociationNames();

        if (in_array($associationName.'s', $associations)) {
            $associationName .= 's';
        }

        return $associationName;
    }

    /**
     * Check association of current repository with passed repository.
     *
     * @param object $repository repository instance
     *
     * @return boolean
     */
    public function checkAssociationWithRepository($repository)
    {
        $associationName = $this->associationName ? $this->associationName : $this->getClassMetadata()->getTableName();
        $associations    = $repository->getClassMetadata()->getAssociationNames();

        if (in_array($associationName, $associations) || in_array($associationName.'s', $associations)) {
            return true;
        }

        return false;
    }

    /**
     * Set static association name.
     *
     * @param string $associationName Association name
     *
     * @return void
     */
    public function setAssociationName($associationName = null)
    {
        $this->associationName = $associationName;
    }

    /**
     * Add role filters to existing query object
     *
     * @param array $filters Array of filters
     *
     * @return void
     */
    public function addFilters($filters = array())
    {
        if (!empty($filters)) {
            foreach ($filters as $filterName => $filterVal) {
                $methodName = 'add'.str_replace(' ', '', ucwords(str_replace('_', ' ', $filterName))).'Filter';

                if (method_exists($this, $methodName) === true && $filterVal !== null && $filterVal !== false && $filterVal !== '') {
                    call_user_func(array($this, $methodName), $filterVal);
                }
            }
        }
    }

    /**
     * Add sorting to existing query object
     *
     * @param array $sort Array of sorting parameters
     *
     * @return void
     */
    public function addSorter($sort = array())
    {
        if (!empty($sort)) {
            foreach ($sort as $field => $ord) {
                $this->queryBuilder->addOrderBy(sprintf('%s.%s', $this->getRepositoryAlias(), $field), $ord);
            }
        }
    }

    /**
     * Add join with role table to existing query object
     *
     * @param object $repository  Repositroy class instance
     * @param array  $joinOptions Join options
     *
     * @return void
     */
    public function addJoin($repository, $joinOptions = array())
    {
        // No need to join if two repositories are same and no assosiation between tables and if already joined
        if ($repository !== $this && ($this->isJoined($repository) === false)) {
            $joinType          = (isset($joinOptions['type']) && $joinOptions['type']) ? $joinOptions['type'] : 'left';
            $joinConidtionType = (isset($joinOptions['condition_type']) && $joinOptions['condition_type']) ? $joinOptions['condition_type'] : null;
            $joinConidtion     = (isset($joinOptions['condition']) && $joinOptions['condition']) ? $joinOptions['condition'] : null;
            $joinIndexBy       = (isset($joinOptions['index_by']) && $joinOptions['index_by']) ? $joinOptions['index_by'] : null;

            $association = null;
            if ($this->checkAssociationWithRepository($repository) === true) {
                $association = $repository::ALIAS.'.'.$this->getAssociationNameWithRepository($repository);
            } else {
                $association = $this->getClassName();
                if ($joinConidtion == null) {
                    $joinConidtion = $this->getRepositoryAlias().'.'.$repository->getClassMetadata()->getTableName().' = '.$repository::ALIAS.'.id';
                    $joinConidtionType = (isset($joinOptions['condition_type']) && $joinOptions['condition_type']) ? $joinOptions['condition_type'] : 'WITH';
                }
            }

            call_user_func(
                array($this->queryBuilder, $joinType.'Join'),
                $association,
                $this->getRepositoryAlias(),
                $joinConidtionType,
                $joinConidtion,
                $joinIndexBy
            );

            // Join can be done only once, so collect relation for check next time
            $this->joins[$repository::ALIAS.'_'.$this->getRepositoryAlias()] = true;
        }
    }

    /**
     * Check joins exists between two tables in query or not
     *
     * @param object $joinOfRepository repository for checking join of current repository
     *
     * @return void
     */
    protected function isJoined($joinOfRepository)
    {
        return isset($this->joins[$joinOfRepository::ALIAS.'_'.$this->getRepositoryAlias()]) ? true : false;
    }

    /**
     * Add user id filter to existing query object
     *
     * @param integer $id user id
     *
     * @return void
     */
    protected function addIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            $id = array_filter($id);

            if (count($id)) {
                $this->queryBuilder->andWhere($this->getRepositoryAlias().'.id IN (:'.$this->getRepositoryAlias().'_id'.')');
                $this->queryBuilder->setParameter($this->getRepositoryAlias().'_id', $id);
            }
        }
    }

    /**
     * Add entity name filter to existing query object
     *
     * @param string $name entity name
     *
     * @return void
     */
    protected function addNameFilter($name = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.name LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $name));
    }

    /**
     * Add from field filter to existing query object
     *
     * @param string $field Field name
     * @param string $from  From field value
     *
     * @return void
     */
    protected function addFromFilter($field, $from = null)
    {
        if ($from) {
            $this->queryBuilder->andWhere($this->getRepositoryAlias().'.'.$field.' >= :'.$this->getRepositoryAlias().'_'.$field.'_from')->setParameter($this->getRepositoryAlias().'_'.$field.'_from', $from);
        }
    }

    /**
     * Add to field filter to existing query object
     *
     * @param string $field Field name
     * @param string $to    To field value
     *
     * @return void
     */
    protected function addToFilter($field, $to = null)
    {
        if ($to) {
            $this->queryBuilder->andWhere($this->getRepositoryAlias().'.'.$field.' <= :'.$this->getRepositoryAlias().'_'.$field.'_to')->setParameter($this->getRepositoryAlias().'_'.$field.'_to', $to);
        }
    }


    /**
     * Add from to field filter to existing query object
     *
     * @param string $field Field name
     * @param string $from  From field value
     * @param string $to    To field value
     *
     * @return void
     */
    protected function addFromToFilter($field, $from = null, $to = null)
    {
        $this->addFromFilter($field, $from);
        $this->addToFilter($field, $to);
    }

    /**
     * Add created_at_from_to filter to existing query object
     *
     * @param string $fromTo from value | to value (pipe saperated)
     *
     * @return void
     */
    protected function addCreatedAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to   = CommonManager::getTimeStampFromEndDate($to);
        }

        $this->addFromToFilter('created_at', $from, $to);
    }

    /**
     * Add expires_at_from_to filter to existing query object
     *
     * @param string $fromTo from value | to value (pipe saperated)
     *
     * @return void
     */
    protected function addExpiresAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to   = CommonManager::getTimeStampFromEndDate($to);
        }

        $this->addFromToFilter('expires_at', $from, $to);
    }

    /**
     * Add updated_at_from_to filter to existing query object
     *
     * @param string $fromTo from value | to value (pipe saperated)
     *
     * @return void
     */
    protected function addUpdatedAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to   = CommonManager::getTimeStampFromEndDate($to);
        }

        $this->addFromToFilter('updated_at', $from, $to);
    }

    /**
     * Add xxx_id filter to existing query object
     *
     * @param string $field Field name.
     * @param mixed  $id    Ids.
     * @param string $alias Field alias.
     *
     * @return void
     */
    protected function addWhereInFilter($field, $id = null, $alias = null)
    {
        if ($field && $id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            $id = array_filter($id);

            if (count($id)) {
                if (!$alias) {
                    $alias = $field;
                }

                $this->queryBuilder->andWhere($this->getRepositoryAlias().'.'.$field.' IN (:'.$this->getRepositoryAlias().'_'.$alias.')');
                $this->queryBuilder->setParameter($this->getRepositoryAlias().'_'.$alias, $id);
            }
        }
    }
}
