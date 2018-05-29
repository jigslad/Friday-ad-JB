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
 * This interface is used to define constant for ad solr fields for forsale.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdForSaleSolrFieldMapping extends AdSolrFieldMapping
{
    const CONDITION_ID = 'a_f_condition_id_i';

    const CONDITION_NEW = 'a_f_condition_new_b';

    const AGE_RANGE_ID = 'a_f_age_range_id_i';

    const BRAND_ID = 'a_f_brand_id_i';

    const BRAND_NAME = 'a_f_brand_name_s';

    const BRAND_CLOTHING_ID = 'a_f_brand_clothing_id_i';

    const BUSINESS_TYPE_ID = 'a_f_business_type_id_i';

    const COLOUR_ID = 'a_f_colour_id_i';

    const MAIN_COLOUR_ID = 'a_f_main_colour_id_i';

    const SIZE_ID = 'a_f_size_id_i';

    const META_DATA = 'a_f_meta_data_desc';

    const HAS_USER_LOGO = 'a_f_has_user_logo_b';
}
