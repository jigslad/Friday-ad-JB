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

/**
 * This file is used to add filters, sorting for ad services solr fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdServicesSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_services';
    }

    /**
     * Add services offered id filter to solr query.
     *
     * @param integer $id.
     */
    protected function addServicesOfferedIdFilter($id = null)
    {
        $this->addDimensionIdFilter('SERVICES_OFFERED_ID', $id);
    }

    /**
     * Add event type id filter to solr query.
     *
     * @param integer $id.
     */
    protected function addEventTypeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('EVENT_TYPE_ID', $id);
    }

    /**
     * Add service type id filter to solr query.
     *
     * @param integer $id.
     */
    protected function addServiceTypeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('SERVICE_TYPE_ID', $id);
    }
}
