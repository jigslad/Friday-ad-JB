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
 * This interface is used to define constant for ad solr fields for motors.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdMotorsSolrFieldMapping extends AdSolrFieldMapping
{
    const MAKE_ID = 'a_m_make_id_i';

    const MODEL_ID = 'a_m_model_id_i';

    const MANUFACTURER_ID = 'a_m_manufacturer_id_i';

    const FUEL_TYPE_ID = 'a_m_fuel_type_id_i';

    const COLOUR_ID = 'a_m_colour_id_i';

    const BODY_TYPE_ID = 'a_m_body_type_id_i';

    const TRANSMISSION_ID = 'a_m_transmission_id_i';

    const BERTH_ID = 'a_m_berth_id_i';

    const PART_OF_VEHICLE_ID = 'a_m_part_of_vehicle_id_i';

    const CONDITION_ID = 'a_m_condition_id_i';

    const NUMBER_OF_STALLS_ID = 'a_m_number_of_stalls_id_i';

    const LIVING_ACCOMMODATION_ID = 'a_m_living_accommodation_id_i';

    const TONNAGE_ID = 'a_m_tonnage_id_i';

    const MILEAGE = 'a_m_mileage_d';

    const REG_YEAR = 'a_m_reg_year_s';

    const BOAT_LENGTH = 'a_m_boat_length_d';

    const META_DATA = 'a_f_meta_data_desc';

    const PART_MANUFACTURER_ID = 'a_m_part_manufacturer_id_i';

    const MILEAGE_RANGE = 'a_m_mileage_range_s';

    const ENGINE_SIZE = 'a_m_engine_size_d';

    const ENGINE_SIZE_RANGE = 'a_m_engine_size_range_s';
}
