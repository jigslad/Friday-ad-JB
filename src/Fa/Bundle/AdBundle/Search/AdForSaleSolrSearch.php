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
 * This file is used to add filters, sorting for ad for sale solr fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdForSaleSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_for_sale';
    }

    /**
     * Add condition id filter to solr query.
     *
     * @param integer $id Condition id.
     */
    protected function addConditionIdFilter($id = null)
    {
        $this->addDimensionIdFilter('CONDITION_ID', $id);
    }

    /**
     * Add age range id filter to solr query.
     *
     * @param integer $id Age range id.
     */
    protected function addAgeRangeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('AGE_RANGE_ID', $id);
    }

    /**
     * Add brand id filter to solr query.
     *
     * @param integer $id Brand id.
     */
    protected function addBrandIdFilter($id = null)
    {
        $this->addDimensionIdFilter('BRAND_ID', $id);
    }

    /**
     * Add brand clothing id filter to solr query.
     *
     * @param integer $id Brand clothing id.
     */
    protected function addBrandClothingIdFilter($id = null)
    {
        $this->addDimensionIdFilter('BRAND_CLOTHING_ID', $id);
    }

    /**
     * Add business type id filter to solr query.
     *
     * @param integer $id Business type id.
     */
    protected function addBusinessTypeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('BUSINESS_TYPE_ID', $id);
    }

    /**
     * Add colour id filter to solr query.
     *
     * @param integer $id Colour id.
     */
    protected function addColourIdFilter($id = null)
    {
        $this->addDimensionIdFilter('COLOUR_ID', $id);
    }

    /**
     * Add main colour id filter to solr query.
     *
     * @param integer $id Main colour id.
     */
    protected function addMainColourIdFilter($id = null)
    {
        $this->addDimensionIdFilter('MAIN_COLOUR_ID', $id);
    }

    /**
     * Add size id filter to solr query.
     *
     * @param integer $id Size id.
     */
    protected function addSizeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('SIZE_ID', $id);
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
