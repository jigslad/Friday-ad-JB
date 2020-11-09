<?php

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Intl\Data\Util\ArrayAccessibleResourceBundle;
// use Symfony\Component\Intl\ResourceBundle\Util\ArrayAccessibleResourceBundle;
use Fa\Bundle\CoreBundle\Logger\SolrLogger;

/**
 * Fa\Bundle\CoreBundle\Manager\SolrSearchManager
 *
 * This manager is used to solr search for desktop and api.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class SolrSearchManager
{
    /**
     * Solr core name
     *
     * @var string
     */
    protected $solrCoreName;

    /**
     * Keywords to search
     *
     * @var string
     */
    protected $keywords;

    /**
     * Solr query
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
     * Select fields passed to solr query
     *
     * @var array
     */
    protected $selectFields = array();

    /**
     * Facet fields
     *
     * @var array
     */
    protected $facetFields = array();

    /**
     * Group fields
     *
     * @var array
     */
    protected $groupFields = array();

    /**
     * Summary fields passed to solr query
     *
     * @var array
     */
    protected $summaryFields = array();

    /**
     * Static filters paased to solr query
     *
     * @var array
     */
    protected $staticFilters;

    /**
     * Page number to calculate offset
     *
     * @var integer
     */
    protected $page;

    /**
     * Number of rows to be fetched
     *
     * @var integer
     */
    protected $rows;

    /**
     * Offset paased to solr query
     *
     * @var integer
     */
    protected $staticOffset;

    /**
     * Offset paased to solr query
     *
     * @var boolean
     */
    protected $exactMatch;

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
    private $container;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container, SolrLogger $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Set geo distance query.
     *
     * @param array $geoDistQuery Geo distance query.
     */
    public function setGeoDistQuery(array $geoDistQuery)
    {
        $this->geoDistQuery = $geoDistQuery;
    }

    /**
     * initialise parameters
     *
     * @param string  $solrCoreName  Solr core name
     * @param string  $keywords      Keywords to search
     * @param array   $searchOptions Search options
     * @param integer $page          Page number
     * @param integer $rows          Rows
     * @param integer $staticOffset  Offset
     * @param boolean $exactMatch    Flag for exact match keywords
     *
     * @return void
     */
    public function init($solrCoreName, $keywords = null, $searchOptions = array(), $page = 1, $rows = 30, $staticOffset = 0, $exactMatch = false)
    {
        $this->reset();
        $this->setSolrCoreName($solrCoreName);
        $this->setExactMatch($exactMatch);
        $this->setKeywords($keywords);
        $this->setPage($page);
        $this->setRows($rows);
        $this->setStaticOffset($staticOffset);

        if (isset($searchOptions['query_filters']) && !empty($searchOptions['query_filters'])) {
            $this->addFilters($searchOptions['query_filters']);
        }

        if (isset($searchOptions['query_sorter']) && !empty($searchOptions['query_sorter'])) {
            $this->addSorter($searchOptions['query_sorter']);
        }

        if (isset($searchOptions['select_fields']) && !empty($searchOptions['select_fields'])) {
            $this->addSelectFields($searchOptions['select_fields']);
        }

        if (isset($searchOptions['facet_fields']) && !empty($searchOptions['facet_fields'])) {
            $this->setFacetFields($searchOptions['facet_fields']);
        }

        if (isset($searchOptions['group_fields']) && !empty($searchOptions['group_fields'])) {
            $this->setGroupFields($searchOptions['group_fields']);
        }

        if (isset($searchOptions['summary_fields']) && !empty($searchOptions['summary_fields'])) {
            $this->setSummaryFields($searchOptions['summary_fields']);
        }

        //Add static filters to solr query
        if (isset($searchOptions['static_filters']) && $searchOptions['static_filters']) {
            $this->addStaticFilters($searchOptions['static_filters']);
        }
    }

    /**
     * Reset parameters.
     */
    public function reset()
    {
        $this->query         = null;
        $this->staticFilters = null;
        $this->sortBy        = array();
        $this->selectFields  = array();
        $this->facetFields   = array();
        $this->summaryFields = array();
        $this->geoDistQuery  = array();
        $this->groupFields   = array();
        $this->exactMatch    = false;
    }

    /**
     * Get solr Query
     *
     * @return object
     */
    public function getSolrQuery()
    {
        $solrClientServiceName = 'fa.solr.client.'.$this->getSolrCoreName();
        $solrClient            = $this->container->get($solrClientServiceName);

        if (!$solrClient->ping()) {
            throw  new sfException('solr server is not working');
        }

        //Create SolrQuery instance
        $query = new \SolrQuery();

        //Set keywords to search
        if ($this->keywords) {
            $query->setQuery($this->keywords);
        }

        //Set offset and limit
        if ($this->getStaticOffset()) {
            $offset = $this->getStaticOffset();
        } else {
            $offset = (($this->page - 1) * $this->rows);
        }

        $query->setStart($offset);
        $query->setRows($this->rows);

        //Add filter query
        if ($this->getQuery()) {
            // Add if any static filters need to append
            if ($this->staticFilters) {
                $this->setQuery($this->getQuery().$this->staticFilters);
            }

            $query->addFilterQuery(substr($this->getQuery(), 4, strlen($this->getQuery())));
        } else {
            if ($this->staticFilters) {
                $query->addFilterQuery(substr($this->staticFilters, 4, strlen($this->staticFilters)));
            }
        }

        //Add sorting
        if (count($this->getSortBy())) {
            $tempSortBy = array();
            $fieldOrd   = 0;

            foreach ($this->getSortBy() as $sortField => $sortOrd) {
                $fieldOrd = (isset($sortOrd['field_ord']) ? $sortOrd['field_ord'] : $fieldOrd);
                $tempSortBy[$fieldOrd] = array($sortField => $sortOrd['sort_ord']);
                $fieldOrd = $fieldOrd + 1;
            }

            ksort($tempSortBy);

            foreach ($tempSortBy as $sortFieldOrd) {
                foreach ($sortFieldOrd as $sortField => $sortOrd) {
                    $query->addSortField($sortField, ($sortOrd == 'asc' ? \SolrQuery::ORDER_ASC : \SolrQuery::ORDER_DESC));
                }
            }
        }

        //Add select fields
        $fields = '*';
        if (count($this->getSelectFields())) {
            $fields = implode(', ', $this->getSelectFields());
        }

        // add score to select field if keyword is there.
        if ($this->keywords != '*:* *:*' && strlen(trim($this->keywords))) {
            $fields = $fields.', score';
        }

        if ($this->geoDistQuery && count($this->geoDistQuery)) {
            $distance = (isset($this->geoDistQuery['d']))?$this->geoDistQuery['d']:200;
            $query->addFilterQuery('{!geofilt pt='.$this->geoDistQuery['pt'].' sfield=store d='.$distance.'}&sort=geodist()+asc');
            /*if ($this->getSolrCoreName() == 'ad') {
                $query->addField(\Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping::AWAY_FROM_LOCATION.', '.$fields);
            } elseif ($this->getSolrCoreName() == 'ad.view.counter') {
                $query->addField(\Fa\Bundle\AdBundle\Solr\AdViewCounterSolrFieldMapping::AWAY_FROM_LOCATION.', '.$fields);
            }*/
        } else {
            $query->addField($fields);
        }

        //Add summary fields
        if (count($this->getSummaryFields())) {
            $query->addParam('stats', 'true');

            foreach ($this->getSummaryFields() as $summaryField) {
                $query->addParam('stats.field', $summaryField);

                //Add facet fields if any
                //NOTE: Stats can only facet on single-valued fields, not for multi-valued fields
                /*
                 if (!empty($this->getFacetFields())) {
                foreach ($this->getFacetFields() as $facetField) {
                $query->addParam('f.'.$summaryField.'.stats.facet', $facetField);
                }
                }
                */
            }
        } elseif (count($this->getFacetFields())) {
            //Add summary fields
            $query->addParam('facet', 'true');
            foreach ($this->getFacetFields() as $facetField => $facet) {
                $query->addParam('facet.field', $facetField);
                $query->setFacetSort(\SolrQuery::FACET_SORT_COUNT);

                if (isset($facet['limit']) && $facet['limit']) {
                    $query->setFacetLimit($facet['limit'], $facetField);
                } else {
                    $query->setFacetLimit('-1', $facetField);
                }

                if (isset($facet['min_count']) && $facet['min_count']) {
                    $query->setFacetMinCount($facet['min_count']);
                }
            }
        }
        if (count($this->getGroupFields())) {
            //Add summary fields
            $query->addParam('group', 'true');
            foreach ($this->getGroupFields() as $groupField => $group) {
                $query->addParam('group.field', $groupField);
                if (isset($group['limit']) && $group['limit']) {
                    $query->addParam('group.limit', $group['limit']);
                }
            }
        }

        if ($this->geoDistQuery && count($this->geoDistQuery)) {
            foreach ($this->geoDistQuery as $field => $value) {
                $query->addParam($field, $value);
            }
        }

        $query->addParam('shards.tolerant', 'true');
       // echo $query;
        $startTime = microtime(true);
        $result    = $solrClient->connect()->query($query);
        $duration  = (microtime(true) - $startTime) * 1000;

        $error = false; ///$result instanceof ResponseError ? (string) $result : false;
        $this->logger->logCommand((string) $query, $duration, '', $error);

        
        return $result;
    }

    /**
     * Get solr response
     *
     * @param object $solrQuery solr query object
     *
     * @return object
     */
    public function getSolrResponse($solrQuery = null)
    {
        $solrQuery = $solrQuery ? $solrQuery : $this->getSolrQuery();
        
        return $solrQuery->getResponse();
    }

    /**
     * Get solr response docs (records)
     *
     * @param object $solrResponse solr response object
     *
     * @return mixed
     */
    public function getSolrResponseDocs($solrResponse = null)
    {
        $solrResponse = $solrResponse ? $solrResponse : $this->getSolrResponse();

        if (isset($solrResponse['response']['docs']) && is_array($solrResponse['response']['docs'])) {
            return $solrResponse['response']['docs'];
        }

        return array();
    }

    /**
     * Get solr response docs (records) count
     *
     * @param object $solrResponse solr response object
     *
     * @return integer
     */
    public function getSolrResponseDocsCount($solrResponse = null)
    {
        $solrResponse = $solrResponse ? $solrResponse : $this->getSolrResponse();

        if (isset($solrResponse['response']['numFound'])) {
            return $solrResponse['response']['numFound'];
        }

        return 0;
    }

    /**
     * Get solr response facet fields
     *
     * @param object $solrResponse solr response object
     *
     * @return mixed
     */
    public function getSolrResponseFacetFields($solrResponse = null)
    {
        $solrResponse = $solrResponse ? $solrResponse : $this->getSolrResponse();

        if (isset($solrResponse['facet_counts']['facet_fields'])) {
            return $solrResponse['facet_counts']['facet_fields'];
        }

        return array();
    }

    /**
     * Get solr response group fields.
     *
     * @param object $solrResponse solr response object
     *
     * @return mixed
     */
    public function getSolrResponseGroupFields($solrResponse = null)
    {
        $solrResponse = $solrResponse ? $solrResponse : $this->getSolrResponse();

        if (isset($solrResponse['grouped'])) {
            return $solrResponse['grouped'];
        }

        return array();
    }

    /**
     * Get solr response summary (stats) fields
     *
     * @param object $solrResponse solr response object
     *
     * @return mixed
     */
    public function getSolrResponseSummaryFields($solrResponse = null)
    {
        $solrResponse = $solrResponse ? $solrResponse : $this->getSolrResponse();

        if (isset($solrResponse['stats']['stats_fields'])) {
            return $solrResponse['stats']['stats_fields'];
        }

        return array();
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
            $this->staticFilters = $staticFilters;
        }
    }

    /**
     * Set solr core name
     *
     * @param string $solrCoreName solr core name
     *
     * @return void
     */
    protected function setSolrCoreName($solrCoreName)
    {
        $this->solrCoreName = $solrCoreName;
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
     * Set exact match for keywords to search
     *
     * @param boolean $exactMatch Exact match flag for keywords
     *
     * @return void
     */
    protected function setExactMatch($exactMatch)
    {
        $this->exactMatch = $exactMatch;
    }

    /**
     * Set keywords to search
     *
     * @param string $keywords Keywords to search
     *
     * @return void
     */
    protected function setKeywords($keywords = null)
    {
        $keywords = trim($keywords);
        $this->keywords   = $keywords ? \SolrUtils::escapeQueryChars($keywords) : '*:*';

        if ($this->exactMatch && $this->keywords != '*:*' && strlen(trim($this->keywords))) {
            //$this->keywords = "\"$this->keywords\"";
            if (!preg_match("/(.*)\sOR\s(.*)/", $this->keywords)) {
                $wordsArray  = preg_split("/\s+/", $this->keywords);
                $wordsString = '';

                foreach ($wordsArray as $word) {
                    $wordsString .= "\"$word\""." AND ";
                }


                $this->keywords = rtrim($wordsString, " AND ");
            }
        } else {
            $this->keywords = str_ireplace(array('and', 'or', 'not'), '', $this->keywords);
        }
    }

    /**
     * Get keywords for exact match.
     *
     * @param string $finalKeyWords
     * @param array  $keywords
     *
     * @return string
     */
    private function getKeywordsForExactMatch($finalKeyWords, $keywords)
    {
        if (count($keywords) > 1) {
            foreach ($keywords as $keyword) {
                $finalKeyWords .= $keyword.'+';
            }

            $finalKeyWords = trim($finalKeyWords, '+').' ';
            array_pop($keywords);
        }

        if (count($keywords) > 1) {
            return $this->getKeywordsForExactMatch($finalKeyWords, $keywords);
        }

        return trim($finalKeyWords);
    }

    /**
     * Get searched keywords
     *
     * @return string
     */
    protected function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Set solr query
     *
     * @param string $query solr query
     *
     * @return void
     */
    protected function setQuery($query = null)
    {
        $this->query = $query;
    }

    /**
     * Get solr query
     *
     * @return string
     */
    protected function getQuery()
    {
        return $this->query;
    }

    /**
     * Set sort options
     *
     * @param array $sortBy sort options
     *
     * @return void
     */
    protected function setSortBy($sortBy = array())
    {
        $this->sortBy = array_merge_recursive($this->sortBy, $sortBy);
    }

    /**
     * Get sort options
     *
     * @return string
     */
    protected function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * Set select fields
     *
     * @param array $selectFields select fileds
     *
     * @return void
     */
    protected function setSelectFields($selectFields = array())
    {
        $this->selectFields = array_merge($this->selectFields, $selectFields);
    }

    /**
     * Get select fields
     *
     * @return string
     */
    protected function getSelectFields()
    {
        return $this->selectFields;
    }

    /**
     * Set facet fields
     *
     * @param array $facetFields select fileds
     *
     * @return void
     */
    protected function setFacetFields($facetFields = array())
    {
        $this->facetFields = $facetFields;
    }

    /**
     * Get facet fields
     *
     * @return string
     */
    protected function getFacetFields()
    {
        return $this->facetFields;
    }

    /**
     * Set group fields.
     *
     * @param array $groupFields Group fileds.
     *
     * @return void
     */
    protected function setGroupFields($groupFields = array())
    {
        $this->groupFields = $groupFields;
    }

    /**
     * Get group fields
     *
     * @return string
     */
    protected function getGroupFields()
    {
        return $this->groupFields;
    }

    /**
     * Set summary fields
     *
     * @param array $summaryFields select fileds
     *
     * @return void
     */
    protected function setSummaryFields($summaryFields = array())
    {
        $this->summaryFields = $summaryFields;
    }

    /**
     * Get summary fields
     *
     * @return string
     */
    protected function getSummaryFields()
    {
        return array_unique($this->summaryFields);
    }

    /**
     * Set page number
     *
     * @param integer $page Page number
     *
     * @return void
     */
    protected function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * Get page number
     *
     * @return integer
     */
    protected function getPage()
    {
        return $this->page;
    }

    /**
     * Set Rows
     *
     * @param integer $rows Number of recors to fetch
     *
     * @return void
     */
    protected function setRows($rows)
    {
        $this->rows = $rows;
    }

    /**
     * Get static offset
     *
     * @return integer
     */
    protected function getStaticOffset()
    {
        return $this->staticOffset;
    }

    /**
     * Set static offset
     *
     * @param integer $rows Number of recors to fetch
     *
     * @return void
     */
    protected function setStaticOffset($staticOffset)
    {
        $this->staticOffset = $staticOffset;
    }

    /**
     * Get rows
     *
     * @return integer
     */
    protected function getRows()
    {
        return $this->rows;
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
        if (count($filters)) {
            /*if ($this->solrCoreName == 'ad.new') {
                if (isset($filters['item']['distance'])) {
                    $serviceName = 'fa.ad.solrsearch';
                    if ($this->container->has($serviceName)) {
                        $serviceObj = $this->container->get($serviceName);
                        $serviceObj->init($this->solrCoreName);
                        $serviceObj->addFilters($filters['item']);
                        $this->setQuery($serviceObj->getQuery());

                        if (count($serviceObj->getGeoDistQuery())) {
                            $this->geoDistQuery = $serviceObj->getGeoDistQuery();
                        }
                    }
                }
            } else {*/
                foreach (array_keys($filters) as $service) {
                    $filters[$service] = array_filter($filters[$service], array($this, 'filterEmptyValues'));
                    if (count($filters[$service])) {
                        $serviceName = 'fa.' . str_replace('item', 'ad', $service) . '.solrsearch';
                        if ($this->container->has($serviceName)) {
                            $serviceObj = $this->container->get($serviceName);

                            $serviceObj->init($this->solrCoreName, $this->getQuery());
                            $serviceObj->addFilters($filters[$service]);
                            $this->setQuery($serviceObj->getQuery());

                            if (count($serviceObj->getGeoDistQuery())) {
                                $this->geoDistQuery = $serviceObj->getGeoDistQuery();
                            }
                        }
                    }
                }
            /*}*/
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
        if (count($sort)) {
            if ($this->solrCoreName == 'ad.new') {
                $serviceName = 'fa.ad.solrsearch';
                if ($this->container->has($serviceName)) {
                    $serviceObj = $this->container->get($serviceName);
                    $serviceObj->init($this->solrCoreName);

                    foreach ($sort['item'] as $sortField => $sortOrder) {
                        $sorter = $sort['item'][$sortField];
                        if (! is_array($sorter)) {
                            $sorter = ['sort_ord' => $sorter];
                        }
                        $serviceObj->addSorter();
                        $this->setSortBy([$sortField => $sorter]);
                    }
                }
            } else {
                foreach (array_keys($sort) as $service) {
                    $sort[$service] = array_filter($sort[$service]);
                    if (count($sort[$service])) {
                        $serviceName = 'fa.' . str_replace('item', 'ad', $service) . '.solrsearch';
                        if ($this->container->has($serviceName)) {
                            $serviceObj = $this->container->get($serviceName);

                            $serviceObj->init($this->solrCoreName);
                            $serviceObj->addSorter($sort[$service]);
                            $this->setSortBy($serviceObj->getSortBy());
                        }
                    }
                }
            }
        }
    }

    /**
     * Collect select fields for solr response
     *
     * @param array $selectFields Array of select fields
     *
     * @return void
     */
    protected function addSelectFields($selectFields = array())
    {
        if (count($selectFields)) {
            foreach (array_keys($selectFields) as $service) {
                $selectFields[$service] = array_unique(array_filter($selectFields[$service]));
                if (count($selectFields[$service])) {
                    $serviceName = 'fa.'.str_replace('item', 'ad', $service).'.solrsearch';
                    if ($this->container->has($serviceName)) {
                        $serviceObj  = $this->container->get($serviceName);

                        $serviceObj->init($this->solrCoreName);
                        $serviceObj->addSelectFields($selectFields[$service]);
                        $this->setSelectFields($serviceObj->getSelectFields());
                    }
                }
            }
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
        return ($value !== null && $value !== false && $value !== '');
    }
}
