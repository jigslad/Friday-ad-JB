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
 * This interface is used to define constant for ad solr fields for property.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdPropertySolrFieldMapping extends AdSolrFieldMapping
{
    const NUMBER_OF_BEDROOMS_ID = 'a_p_number_of_bedrooms_id_i';

    const NUMBER_OF_BATHROOMS_ID = 'a_p_number_of_bathrooms_id_i';

    const AMENITIES_ID = 'a_p_amenities_id_txt';

    const FURNISHING_ID = 'a_p_furnishing_id_i';

    const RENT_PER_ID = 'a_p_rent_per_id_i';

    const DATE_AVAILABLE = 'a_p_date_available_s';

    const DATE_AVAILABLE_INT = 'a_p_date_available_int_i';

    const NUMBER_OF_ROOMS_AVAILABLE_ID = 'a_p_number_of_rooms_available_id_i';

    const ROOM_SIZE_ID = 'a_p_room_size_id_i';

    const META_DATA = 'a_p_meta_data_desc';
}
