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
 * This file is used to add filters, sorting for ad community solr fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdCommunitySolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_community';
    }

    /**
     * Add event start filter to existing query object
     *
     * @param string $fromTo from value | to value (pipe saperated)
     *
     * @return void
     */
    protected function addEventStartFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to = CommonManager::getTimeStampFromEndDate($to);
        }

        if ($from || $to) {
            $this->addEventDateFromToFilter($from, $to);
        }
    }

    /**
     * Add experience level id filter to solr query.
     *
     * @param integer $id Condition id.
     */
    protected function addExperienceLevelIdFilter($id = null)
    {
        $this->addDimensionIdFilter('EXPERIENCE_LEVEL_ID', $id);
    }

    /**
     * Add education level id filter to solr query.
     *
     * @param integer $id Age range id.
     */
    protected function addEducationLevelIdFilter($id = null)
    {
        $this->addDimensionIdFilter('EDUCATION_LEVEL_ID', $id);
    }

    /**
     * Add event start within today, tomorrow, week or month filter to solr query.
     *
     * @param string $period Event period.
     */
    protected function addEventStartPeriodFilter($period = null)
    {
        if ($period && $period != 'specific-dates') {
            if ($period == 'today') {
                $date = CommonManager::getTimeStampFromEndDate(date('Y-m-d'));
                $this->addEventDateFromToFilter(null, $date);
            } elseif ($period == 'tomorrow') {
                $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime('1 days')));
                $endDate = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime('1 days')));
                $this->addEventDateFromToFilter($startDate, $endDate);
            } elseif ($period == 'week') {
                $date = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime('1 week')));
                $this->addEventDateFromToFilter(null, $date);
            } elseif ($period == 'month') {
                $date = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime('1 month')));
                $this->addEventDateFromToFilter(null, $date);
            }
        }
    }

    /**
     * Add filter to solr query to find active event for selected event date option like within today, tomorrow, week or month or specific date range.
     *
     * @param integer $from Event from date timestamp.
     * @param integer $to   Event to date timestamp.
     *
     */
    protected function addEventDateFromToFilter($from = null, $to = null)
    {
        if (defined($this->getSolrFieldMappingClass($this->getTableName()).'::EVENT_START') && defined($this->getSolrFieldMappingClass($this->getTableName()).'::EVENT_END') && ($from || $to)) {
            if (!$from) {
                $from = CommonManager::getTimeStampFromStartDate(date('Y-m-d'));
            }

            if (!$to) {
                $to = $from;
            }

            $query = '('.constant($this->getSolrFieldMappingClass($this->getTableName()).'::EVENT_START').':[* TO '.$to.'] AND '.constant($this->getSolrFieldMappingClass($this->getTableName()).'::EVENT_END').':['.$from.' TO *])
                OR ('.constant($this->getSolrFieldMappingClass($this->getTableName()).'::NO_EVENT_END').':1 AND '.constant($this->getSolrFieldMappingClass($this->getTableName()).'::EVENT_START').':[* TO '.$to.'] AND '.constant($this->getSolrFieldMappingClass($this->getTableName()).'::EVENT_START').':['.$from.' TO *])';

            //(start_date <= selected_date_to and end_date >= selected_date_from) or (end_date is null and start_date <= selected_date_to and start_date >= selected_date_from)

            if ($query) {
                $query = ' AND ('.$query.')';
                $this->query .= $query;
            }
        }
    }

    /**
     * Expire event if end date is less or equal from passed date or event has not end date and start date is less or equal from passed.
     *
     * @param integer $date Event from date timestamp.
     *
     */
    protected function addExpireEventDateFilter($date = null)
    {
        //end_date <= passed_date or (start_date <= passed_date and end_date is null)
        $query = '('.constant($this->getSolrFieldMappingClass($this->getTableName()).'::EVENT_END').':[* TO '.$date.'] OR ('.constant($this->getSolrFieldMappingClass($this->getTableName()).'::EVENT_START').':[* TO '.$date.'] AND '.constant($this->getSolrFieldMappingClass($this->getTableName()).'::NO_EVENT_END').':1))';

        $this->query .= ' AND ('.$query.')';
    }

    /**
     * Add cuisine_type_id filter to solr query.
     *
     * @param integer $id Amenities id.
     */
    protected function addCuisineTypeIdFilter($id = null)
    {
        $this->addDimensionIdFilter('CUISINE_TYPE_ID', $id);
    }
}
