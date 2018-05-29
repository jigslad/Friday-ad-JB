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
 * This file is used to add filters, sorting for ad motors solr fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdMotorsSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_motors';
    }

    /**
     * Add make id filter to solr query.
     *
     * @param integer $id Make id.
     */
    protected function addMakeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('MAKE_ID', $id);
    }

    /**
     * Add model id filter to solr query.
     *
     * @param integer $id Model id.
     */
    protected function addModelIdFilter($id = null)
    {
        $this->addDimensionIdFilter('MODEL_ID', $id);
    }

    /**
     * Add Manufacturer id filter to solr query.
     *
     * @param integer $id Manufacturer id.
     */
    protected function addManufacturerIdFilter($id = null)
    {
        $this->addDimensionIdFilter('MANUFACTURER_ID', $id);
    }

    /**
     * Add fuel type id filter to solr query.
     *
     * @param integer $id Fuel type id.
     */
    protected function addFuelTypeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('FUEL_TYPE_ID', $id);
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
     * Add body type id filter to solr query.
     *
     * @param integer $id Body type id.
     */
    protected function addBodyTypeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('BODY_TYPE_ID', $id);
    }

    /**
     * Add transmission id filter to solr query.
     *
     * @param integer $id Transmission id.
     */
    protected function addTransmissionIdFilter($id = null)
    {
        $this->addDimensionIdFilter('TRANSMISSION_ID', $id);
    }

    /**
     * Add berth id filter to solr query.
     *
     * @param integer $id Berth id.
     */
    protected function addBerthIdFilter($id = null)
    {
        $this->addDimensionIdFilter('BERTH_ID', $id);
    }

    /**
     * Add part of vehicle id filter to solr query.
     *
     * @param integer $id Part of vehicle id.
     */
    protected function addPartOfVehicleIdFilter($id = null)
    {
        $this->addDimensionIdFilter('PART_OF_VEHICLE_ID', $id);
    }

    /**
     * Add part manufacturer id filter to solr query.
     *
     * @param integer $id Part of manufacturer id.
     */
    protected function addPartManufacturerIdFilter($id = null)
    {
        $this->addDimensionIdFilter('PART_MANUFACTURER_ID', $id);
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
     * Add number of stalls id filter to solr query.
     *
     * @param integer $id Number of stalls id.
     */
    protected function addNumberOfStallsIdFilter($id = null)
    {
        $this->addDimensionIdFilter('NUMBER_OF_STALLS_ID', $id);
    }

    /**
     * Add living accommodation id filter to solr query.
     *
     * @param integer $id Living accommodation id.
     */
    protected function addLivingAccommodationIdFilter($id = null)
    {
        $this->addDimensionIdFilter('LIVING_ACCOMMODATION_ID', $id);
    }

    /**
     * Add tonnage id filter to solr query.
     *
     * @param integer $id Tonnage id.
     */
    protected function addTonnageIdFilter($id = null)
    {
        $this->addDimensionIdFilter('TONNAGE_ID', $id);
    }

    /**
     * Add mileage filter to existing solr query.
     *
     * @param string $fromTo
     */
    protected function addMileageFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = null;
        }

        if (!is_numeric($to)) {
            $to = null;
        }

        $this->addFromToFilter('mileage', $from, $to);
    }

    /**
     * Add year filter to existing solr query.
     *
     * @param string $year
     */
    protected function addRegYearFilter($year = null)
    {
        $this->addDimensionIdFilter('REG_YEAR', $year);
    }

    /**
     * Add boat_length filter to existing solr query.
     *
     * @param string $fromTo
     */
    protected function addBoatLengthFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        $from = preg_replace("/[,\s]/", '', $from);
        $to   = preg_replace("/[,\s]/", '', $to);

        if (!is_numeric($from)) {
            $from = null;
        }

        if (!is_numeric($to)) {
            $to = null;
        }

        $this->addFromToFilter('boat_length', $from, $to);
    }

    /**
     * Add mileage range filter to existing solr query.
     *
     * @param string $range
     */
    protected function addMileageRangeFilter($range = null)
    {
        $this->addDimensionIdFilter('MILEAGE_RANGE', $range);
    }

    /**
     * Add engine size range filter to existing solr query.
     *
     * @param string $range
     */
    protected function addEngineSizeRangeFilter($range = null)
    {
        $this->addDimensionIdFilter('ENGINE_SIZE_RANGE', $range);
    }
}
