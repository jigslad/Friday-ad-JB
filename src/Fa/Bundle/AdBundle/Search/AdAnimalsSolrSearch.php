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
 * This file is used to add filters, sorting for ad animals solr fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdAnimalsSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad_animals';
    }

    /**
     * Add breed id filter to solr query.
     *
     * @param integer $id Breed id.
     */
    protected function addBreedIdFilter($id = null)
    {
        $this->addDimensionIdFilter('BREED_ID', $id);
    }

    /**
     * Add gender id filter to solr query.
     *
     * @param integer $id Gender id.
     */
    protected function addGenderIdFilter($id = null)
    {
        $this->addDimensionIdFilter('GENDER_ID', $id);
    }

    /**
     * Add species id filter to solr query.
     *
     * @param integer $id Species id.
     */
    protected function addSpeciesIdFilter($id = null)
    {
        $this->addDimensionIdFilter('SPECIES_ID', $id);
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
}
