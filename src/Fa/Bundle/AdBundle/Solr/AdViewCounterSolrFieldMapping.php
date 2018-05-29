<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Solr;

/**
 * This interface is used to define constant for ad view counter solr fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdViewCounterSolrFieldMapping
{
    const ID = 'id';

    const ROOT_CATEGORY_ID = 'a_parent_category_lvl_1_id_i';

    const POSTCODE = 'a_l_postcode_txt';

    const DOMICILE_ID = 'a_l_domicile_id_txt';

    const TOWN_ID = 'a_l_town_id_txt';

    const LOCALITY_ID = 'a_l_locality_id_txt';

    const MAIN_TOWN_ID = 'a_l_main_town_id_i';

    const LATITUDE = 'a_l_latitude_txt';

    const LONGITUDE = 'a_l_longitude_txt';

    const STORE = 'store';

    const GEODIST = 'geodist()';

    const AWAY_FROM_LOCATION = 'away_from_location:geodist()';

    const TOTAL_HITS_LAST_7_DAYS = 'total_hits_last_7_days_i';
}
