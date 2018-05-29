<?php

namespace Fa\Bundle\CoreBundle\Search;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fa\Bundle\CoreBundle\Search\SolrSearch
 *
 * This class is used for common solr search functionalities.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
abstract class SolrSearch
{
    /**
     * Doctrine query builder object
     *
     * @var object
     */
    protected $query;

    /**
     * Sort by array
     *
     * @var array
     */
    protected $sortBy = array();

    /**
     * Select fields array
     *
     * @var array
     */
    protected $selectFields = array();

    /**
     * Solr core name
     *
     * @var string
     */
    protected $solrCoreName;

    /**
     * Geo distance query
     *
     * @var array
     */
    protected $geoDistQuery = array();

    /**
     * Container service class object
     *
     * @var object
     */
    protected $container;

    /**
     * Entity manager class object.
     *
     * @var object
     */
    private $em;

    /**
     * Add abstract method to get table name associcated with field name
     *
     * @return object
     */
    abstract protected function getTableName();

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
    }

    /**
     * initialise parameters
     *
     * @param string $solrCoreName solr core name
     * @param string $query solr query
     *
     * @return void
    */
    public function init($solrCoreName, $query = null)
    {
        $this->reset();
        $this->solrCoreName = $solrCoreName;
        $this->query        = $query;
    }

    /**
     * Reset parameters.
     */
    public function reset()
    {
        $this->solrCoreName = '';
        $this->query = '';
        $this->sortBy        = array();
        $this->selectFields  = array();
        $this->geoDistQuery  = array();
    }

    /**
     * Get entit manager service object.
     *
     * @return object
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Get container service object.
     *
     * @return object
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get solr query.
     *
     * @return object
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get geo distance solr query.
     *
     * @return object
     */
    public function getGeoDistQuery()
    {
        return $this->geoDistQuery;
    }

    /**
     * Get sort options.
     *
     * @return array
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * Get select fields.
     *
     * @return array
     */
    public function getSelectFields()
    {
        return $this->selectFields;
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
     * Add sorting options
     *
     * @param array $sort Array of sorting parameters
     *
     * @return void
     */
    public function addSorter($sort = array())
    {
        if (!empty($sort)) {
            foreach ($sort as $field => $ord) {
                if (!is_array($ord)) {
                    if (defined($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field))) {
                        if ($field == 'random') {
                            $this->sortBy['random_'.time()] = array('sort_ord' => $ord);
                        } elseif ($field == 'score') {
                            $this->sortBy['score'] = array('sort_ord' => $ord);
                        } else {
                            $this->sortBy[constant($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field))] = array('sort_ord' => $ord);
                        }
                    }
                } else {
                    if (defined($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field))) {
                        if ($field == 'random') {
                            $this->sortBy['random_'.time()] = $ord;
                        } elseif ($field == 'score') {
                            $this->sortBy['score'] = $ord;
                        } else {
                            $this->sortBy[constant($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field))] = $ord;
                        }
                    }
                }
            }
        }
    }

    /**
     * Add select fields
     *
     * @param array $seletFields select fields array
     *
     * @return void
     */
    public function addSelectFields($seletFields = array())
    {
        if (!empty($seletFields)) {
            foreach ($seletFields as $field) {
                if (defined($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field))) {
                    $this->selectFields[] = constant($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field));
                }
            }
        }
    }

    /**
     * Get solr core name
     *
     * @return string
     */
    protected function getSolrCoreName()
    {
        return $this->solrCoreName;
    }

    /**
     * Get solr mapping field class
     *
     * @param string $verical Vertical name.
     *
     * @return string
     */
    protected function getSolrFieldMappingClass($vertical = null)
    {
        if ($vertical) {
            if ($this->getSolrCoreName() == 'ad.view.counter') {
                return 'Fa\Bundle\\AdBundle\\Solr\\AdViewCounterSolrFieldMapping';
            }
            if ($this->getSolrCoreName() == 'user.shop.detail') {
                return 'Fa\Bundle\\UserBundle\\Solr\\UserShopDetailSolrFieldMapping';
            }

            $vertical = str_replace(' ', '', ucwords(str_replace('_', ' ', $vertical)));
            return 'Fa\Bundle\\'.ucfirst($this->getSolrCoreName()).'Bundle\Solr'.'\\'.$vertical.'SolrFieldMapping';
        }

        if ($this->getSolrCoreName() == 'user.shop.detail') {
            return 'Fa\Bundle\\UserBundle\\Solr\\UserShopDetailSolrFieldMapping';
        }

        if ($this->getSolrCoreName() == 'ad.view.counter') {
            return 'Fa\Bundle\\AdBundle\\Solr\\AdViewCounterSolrFieldMapping';
        }

        return 'Fa\Bundle\\'.ucfirst($this->getSolrCoreName()).'Bundle\Solr'.'\\'.ucfirst($this->getSolrCoreName()).'SolrFieldMapping';
    }

    /**
     * Add from to field filter to existing solr query
     *
     * @param string $field Field name
     * @param string $from  From field value
     * @param string $to    To field value
     */
    protected function addFromToFilter($field, $from = null, $to = null)
    {
        if (defined($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field))) {
            $query = '';
            if ($from && $to) {
                $query = constant($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field)).':['.$from.' TO '.$to.']';
            } elseif ($from && !$to) {
                $query = constant($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field)).':['.$from.' TO *]';
            } elseif (!$from && $to) {
                $query = constant($this->getSolrFieldMappingClass($this->getTableName()).'::'.strtoupper($field)).':[* TO '.$to.']';
            }

            if ($query) {
                $query = ' AND ('.$query.')';
                $this->query .= $query;
            }
        }
    }

    /**
     * Add dimension filter to solr query.
     *
     * @param string  $dimension Dimension name.
     * @param integer $id        Condition id.
     */
    protected function addDimensionIdFilter($dimension, $id = null)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        $id = array_filter($id);

        if (count($id)) {
            if (defined($this->getSolrFieldMappingClass($this->getTableName()).'::'.$dimension)) {
                $query = constant($this->getSolrFieldMappingClass($this->getTableName()).'::'.$dimension).':('.implode(' OR ', $id).')';
                $query = ' AND ('.$query.')';

                $this->query .= $query;
            }
        }
    }
}
