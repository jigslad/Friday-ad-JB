<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Interfaces;

/**
 * This inteface is used for indexable dimentions.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
interface IndexableDimensionInterface
{
    // common fields.
    const CATEGORY             = 'CATEGORY_ID';
    const LOCATION             = 'MAIN_TOWN_ID';
    const AD_TYPE              = 'TYPE_ID';
    const AD_STATUS            = 'STATUS_ID';
    const AD_TITLE             = 'TITLE';
    const AD_DESCRIPTION_SHORT = 'DESCRIPTION_SHORT';
    const AD_DESCRIPTION_LONG  = 'DESCRIPTION';

    // for sale fields.
    const COLOUR               = 'COLOUR_ID';
    const BRAND                = 'BRAND_ID';
    const BUSINESS_TYPE        = 'BUSINESS_TYPE_ID';
    const AGE_RANGE_KIDS       = 'AGE_RANGE_ID';
    const SIZE_WOMENS_CLOTHES  = 'WOMENS_CLOTHES_SIZE_ID';
    const SIZE_MENS_CLOTHES    = 'MENS_CLOTHES_SIZE_ID';
    const ADULT_SHOE_SIZE      = 'ADULT_SHOE_SIZE_ID';
    const KIDS_SHOE_SIZE       = 'KIDS_SHOE_SIZE_ID';

    // motors fields.
    const MAKE                 = 'MAKE_ID';
    const FUEL_TYPE            = 'FUEL_TYPE_ID';
    const MODEL                = 'MODEL_ID';
    const BODY_TYPE            = 'BODY_TYPE_ID';
    const TRANSMISSION         = 'TRANSMISSION_ID';
    const BERTH                = 'BERTH_ID';
    const PART_OF_VEHICLE      = 'PART_OF_VEHICLE_ID';
    const PART_MANUFACTURER    = 'PART_MANUFACTURER_ID';
    const TONNAGE              = 'TONNAGE_ID';
    const MANUFACTURER         = 'MANUFACTURER_ID';

    // animals fields.
    const GENDER               = 'GENDER_ID';
    const BREED                = 'BREED_ID';
    const SPECIES              = 'SPECIES_ID';

    // property fields.
    const NUMBER_OF_BEDROOMS   = 'NUMBER_OF_BEDROOMS_ID';
    const AMENITIES            = 'AMENITIES_ID';
    const FURNISHING           = 'FURNISHING_ID';
    const ROOM_SIZE            = 'ROOM_SIZE_ID';

    // community fields.
    const EXPERIENCE_LEVEL     = 'EXPERIENCE_LEVEL_ID';
    const EDUCATION_LEVEL      = 'EDUCATION_LEVEL_ID';

    // job fields.
    const CONTRACT_TYPE        = 'CONTRACT_TYPE_ID';

    // service fields.
    const SERVICES_OFFERED     = 'SERVICES_OFFERED_ID';

    //dealer or private
    const SELLER               = 'IS_TRADE_AD';

    // adult fields
    const ETHNICITY               = 'ETHNICITY_ID';

}
