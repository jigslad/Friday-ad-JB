<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Search;

use Fa\Bundle\CoreBundle\Search\SolrSearch;

/**
 * This manager is used to add filters, join and sorting for user entity.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'USER';
    }

    /**
     * Add user id filter to existing query object.
     *
     * @param integer $id User id.
     */
    protected function addIdFilter($id = null)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        $solrField = null;
        if ($this->getSolrCoreName() == 'ad') {
            $solrField = 'USER_ID';
        } else {
            $solrField = 'ID';
        }

        if (defined($this->getSolrFieldMappingClass().'::'.$solrField)) {
            $query = constant($this->getSolrFieldMappingClass().'::'.$solrField).':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::'.$solrField).':', $id);
            $query = ' AND ('.$query.')';

            $this->query .= $query;
        }
    }
}
