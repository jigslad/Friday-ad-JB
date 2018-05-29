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
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This file is used to add filters, sorting for ad property solr fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdPropertySolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_property';
    }

    /**
     * Add number_of_bedrooms_id filter to solr query.
     *
     * @param integer $id Number of bedrooms id.
     */
    protected function addNumberOfBedroomsIdFilter($id = null)
    {
        $this->addDimensionIdFilter('NUMBER_OF_BEDROOMS_ID', $id);
    }

    /**
     * Add number_of_bathrooms_id filter to solr query.
     *
     * @param integer $id Number of bathrooms id.
     */
    protected function addNumberOfBathroomsIdFilter($id = null)
    {
        $this->addDimensionIdFilter('NUMBER_OF_BATHROOMS_ID', $id);
    }

    /**
     * Add amenities_id filter to solr query.
     *
     * @param integer $id Amenities id.
     */
    protected function addAmenitiesIdFilter($id = null)
    {
        $this->addDimensionIdFilter('AMENITIES_ID', $id);
    }

    /**
     * Add room_size_id filter to solr query.
     *
     * @param integer $id Room size id.
     */
    protected function addRoomSizeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('ROOM_SIZE_ID', $id);
    }

    /**
     * Add furnishing_id filter to solr query.
     *
     * @param integer $id Furnishing id.
     */
    protected function addFurnishingIdFilter($id = null)
    {
        $this->addDimensionIdFilter('FURNISHING_ID', $id);
    }

    /**
     * Add rent_per_id filter to solr query.
     *
     * @param integer $id Rent per id.
     */
    protected function addRentPerIdFilter($id = null)
    {
        $this->addDimensionIdFilter('RENT_PER_ID', $id);
    }

    /**
     * Add number_of_rooms_available_id filter to solr query.
     *
     * @param integer $id Number of rooms available id.
     */
    protected function addNumberOfRoomsAvailableIdFilter($id = null)
    {
        $this->addDimensionIdFilter('NUMBER_OF_ROOMS_AVAILABLE_ID', $id);
    }

    /**
     * Add date available range filter to solr query.
     *
     * @param string $fromTo Date available from to.
     */
    protected function addDateAvailableFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to = CommonManager::getTimeStampFromEndDate($to);
        }

        $this->addFromToFilter('DATE_AVAILABLE_INT', $from, $to);
    }

    /**
     * Add date available within week or month filter to solr query.
     *
     * @param integer $id Date available.
     */
    protected function addDateAvailablePeriodFilter($period = null)
    {
        if ($period && $period != 'specific-dates') {
            $date = null;
            if ($period == 'week') {
                $date = strtotime('1 week');
            } else if ($period == 'month') {
                $date = strtotime('1 month');
            }

            if ($date) {
                $date = CommonManager::getTimeStampFromStartDate(date('Y-m-d', $date));
                $this->addFromToFilter('DATE_AVAILABLE_INT', time(), $date);
            }
        }
    }
}
