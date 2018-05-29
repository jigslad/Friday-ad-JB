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
 * This file is used to add filters, sorting for ad jobs solr fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdJobsSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_jobs';
    }

    /**
     * Add contract_type id filter to solr query.
     *
     * @param integer $id contract_type_id.
     */
    protected function addContractTypeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('CONTRACT_TYPE_ID', $id);
    }

    /**
     * Add job of week filter to solr query.
     *
     * @param boolean $isJobOfWeek Ad has job of week upsell or not.
     */
    protected function addIsJobOfWeekFilter($isJobOfWeek = 1)
    {
        if (defined($this->getSolrFieldMappingClass($this->getTableName()).'::IS_JOB_OF_WEEK')) {
            $query        = constant($this->getSolrFieldMappingClass($this->getTableName()).'::IS_JOB_OF_WEEK').':'.$isJobOfWeek;
            $this->query .= ' AND ('.$query.')';
        }
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

    /**
     * Add salary band id filter to solr query.
     *
     * @param integer $id Salary band id.
     */
    protected function addSalaryBandIdFilter($id = null)
    {
        $this->addDimensionIdFilter('SALARY_BAND_ID', $id);
    }
}
