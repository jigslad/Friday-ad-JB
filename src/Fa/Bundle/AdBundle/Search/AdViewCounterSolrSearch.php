<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Search;

use Fa\Bundle\CoreBundle\Search\SolrSearch;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;

/**
 * This file is used to add filters, sorting for ad solr fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdViewCounterSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_view_counter';
    }

    /**
     * Add id filter to solr query.
     *
     * @param integer $id User id.
     *
     */
    protected function addIdFilter($id = null)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        if (defined($this->getSolrFieldMappingClass().'::ID')) {
            $query = constant($this->getSolrFieldMappingClass().'::ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::ID').':', $id);
            $query = ' AND ('.$query.')';

            $this->query .= $query;
        }
    }

    /**
     * Add root category id filter to solr query.
     *
     * @param integer $id Root category id.
     */
    protected function addRootCategoryIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            if (defined($this->getSolrFieldMappingClass().'::ROOT_CATEGORY_ID')) {
                $id = array_filter($id);
                if (count($id)) {
                    $query = constant($this->getSolrFieldMappingClass().'::ROOT_CATEGORY_ID').':('.implode(' OR ', $id).')';
                    $query = ' AND ('.$query.')';
                    $this->query .= $query;
                }
            }
        }
    }
}
