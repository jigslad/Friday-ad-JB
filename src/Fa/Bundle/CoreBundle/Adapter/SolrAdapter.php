<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Adapter;

use Pagerfanta\Adapter\AdapterInterface;

/**
 * This adapter is used to prepare pagination from solr results.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class SolrAdapter implements AdapterInterface
{
    /**
     * Result array.
     *
     * @var array
     */
    private $result;

    /**
     * Result count.
     *
     * @var integer
     */
    private $resultCount;

    /**
     * Constructor.
     *
     * @param array   $result      Result.
     * @param integer $resultCount Total result.
     *
     */
    public function __construct(array $result, $resultCount = 0)
    {
        $this->result      = $result;
        $this->resultCount = $resultCount;
    }

    /**
     * Returns the array.
     *
     * @return array The array.
     */
    public function getArray()
    {
        return $this->result;
    }

    /**
     * Returns the number of results.
     *
     * @return integer The number of results.
     */
    public function getNbResults()
    {
        if ($this->resultCount) {
            return $this->resultCount;
        }

        return count($this->result);
    }

     /**
     * Returns an slice of the results.
     *
     * @param integer $offset The offset.
     * @param integer $length The length.
     *
     * @return array|\Traversable The slice.
     */
    public function getSlice($offset, $length)
    {
        //NOTE: use offset always 0 because we are fetching max per page records only from solr
        $offset = 0;
        return array_slice($this->result, $offset, $length);
    }
}
