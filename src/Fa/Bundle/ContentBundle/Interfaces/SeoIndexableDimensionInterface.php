<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Interfaces;

/**
 * This inteface is used for indexable dimentions.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
interface SeoIndexableDimensionInterface
{
    // common fields.
    const CATEGORY             = 'category';
    const LOCATION             = 'location';
    const AD_TYPE              = 'type_id';

    // for sale fields.
    const COLOUR               = 'colour_id';
    const BRAND                = 'brand_id';
    const BUSINESS_TYPE        = 'business_type_id';
    const SIZE                 = 'size_id';
    const CONDITION            = 'condition_id';
    const AGE_RANGE            = 'age_range_id';

    // motors fields.
    const MAKE                 = 'make_id';
    const FUEL_TYPE            = 'fuel_type_id';
    const MODEL                = 'model_id';
    const BODY_TYPE            = 'body_type_id';
    const TRANSMISSION         = 'transmission_id';
    const PART_OF_VEHICLE      = 'part_of_vehicle_id';
    const PART_MANUFACTURER    = 'part_manufacturer_id';
    const TONNAGE              = 'tonnage_id';
    const REG_YEAR             = 'reg_year';
    const MILEAGE_RANGE        = 'mileage_range';
    const NUMBER_OF_STALLS     = 'number_of_stalls_id';
    const ENGINE_SIZE_RANGE    = 'engine_size_range';
    const MANUFACTURER         = 'manufacturer_id';
    const BERTH                = 'berth_id';

    // animals fields.
    const GENDER               = 'gender_id';
    const BREED                = 'breed_id';
    const SPECIES              = 'species_id';
    const AGE                  = 'age_id';
    const HEIGHT               = 'height_id';

    // property fields.
    const NUMBER_OF_BEDROOMS        = 'number_of_bedrooms_id';
    const NUMBER_OF_BATHROOMS       = 'number_of_bathrooms_id';
    const AMENITIES                 = 'amenities_id';
    const FURNISHING                = 'furnishing_id';
    const ROOM_SIZE                 = 'room_size_id';
    const NUMBER_OF_ROOMS_AVAILABLE = 'number_of_rooms_available_id';

    // community fields.
    const EXPERIENCE_LEVEL     = 'experience_level_id';
    const LEVEL                = 'level_id';

    // job fields.
    const CONTRACT_TYPE        = 'contract_type_id';

    // service fields.
    const SERVICES_OFFERED     = 'services_offered_id';
    const SERVICE_TYPE         = 'service_type_id';
    const EVENT_TYPE           = 'event_type_id';

    // adult fields
    const ETHNICITY = 'ethnicity_id';

}
