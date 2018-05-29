<?php
namespace Fa\Bundle\CoreBundle\Manager;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Fa\Bundle\CoreBundle\Adapter\SolrAdapter;
use Pagerfanta\Pagerfanta;
use Fa\Bundle\CoreBundle\Doctrine\Adapter\DoctrineORMAdapter as CustomDoctrineORMAdapter;

/**
 * Fa\Bundle\CoreBundle\Manager\SearchManager
 *
 * This manager is used to prepare pagination for listing
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class PaginationManager
{
    /**
     * Doctrine query object or result array
     *
     * @var mixed
     */
    protected $result;

    /**
     * Page
     *
     * @var integer
     */
    protected $page;

    /**
     * Max records per page
     *
     * @var integer
     */
    protected $maxPerPage;

    /**
     * Array result count
     *
     * @var integer
     */
    protected $resultCount;

    /**
     * Whether to use custom doctrine orm adapter or not.
     *
     * @var boolean
     */
    protected $customDoctrineORMAdapter;

    /**
     * Initialise parameters
     *
     * @param mixed   $result     Doctrine query object or result array
     * @param integer $page       Page number
     * @param integer $maxPerPage Max records per page
     * @param integer $maxPerPage Total result count
     *
     * @return void
     */
    public function init($result, $page = 1, $maxPerPage = 10, $resultCount = 0, $customDoctrineORMAdapter = false)
    {
        $this->result                   = $result;
        $this->page                     = $page;
        $this->maxPerPage               = $maxPerPage;
        $this->resultCount              = $resultCount;
        $this->customDoctrineORMAdapter = $customDoctrineORMAdapter;
    }

    /**
    * Get pagination
    *
    * @param boolean $fetchJoinCollection Whether the query joins a collection (true by default).
    * @param boolean $useOutputWalkers    Whether to use output walkers pagination mode.
    *
    * @return object
    */
    public function getPagination($fetchJoinCollection = true, $useOutputWalkers = false)
    {
        if ($this->customDoctrineORMAdapter) {
            $adapter = new CustomDoctrineORMAdapter($this->result, $fetchJoinCollection, $useOutputWalkers);
        } else {
            $adapter = new DoctrineORMAdapter($this->result, $fetchJoinCollection, $useOutputWalkers);
        }

        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($this->maxPerPage);
        $pagerfanta->setCurrentPage($this->page);

        return $pagerfanta;
    }

    /**
     * Get solr pagination
     *
     * @return object
     */
    public function getSolrPagination()
    {
        $adapter    = new SolrAdapter($this->result, $this->resultCount);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($this->maxPerPage);
        $pagerfanta->setCurrentPage($this->page);

        return $pagerfanta;
    }
}
