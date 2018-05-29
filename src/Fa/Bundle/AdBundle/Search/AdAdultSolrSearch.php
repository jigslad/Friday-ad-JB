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
 * This file is used to add filters, sorting for ad adult solr fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdAdultSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_adult';
    }

    /**
     * Add amenities_id filter to solr query.
     *
     * @param integer $id Amenities id.
     */
    protected function addServicesIdFilter($id = null)
    {
        $this->addDimensionIdFilter('SERVICES_ID', $id);
    }

    /**
     * Add travel_arrangements_id filter to solr query.
     *
     * @param integer $id Amenities id.
     */
    protected function addTravelArrangementsIdFilter($id = null)
    {
        $this->addDimensionIdFilter('TRAVEL_ARRANGEMENTS_ID', $id);
    }

    /**
     * Add independent_or_agency_id filter to solr query.
     *
     * @param integer $id Amenities id.
     */
    protected function addIndependentOrAgencyIdFilter($id = null)
    {
        $this->addDimensionIdFilter('INDEPENDENT_OR_AGENCY_ID', $id);
    }

    /**
     * Add has user logo filter to solr query.
     *
     */
    protected function addHasUserLogoFilter($hasUserLogo = 1)
    {
        if (defined($this->getSolrFieldMappingClass($this->getTableName()).'::HAS_USER_LOGO')) {
            $query        = constant($this->getSolrFieldMappingClass($this->getTableName()).'::HAS_USER_LOGO').':'.$hasUserLogo;
            $this->query .= ' AND ('.$query.')';
        }
    }
}
