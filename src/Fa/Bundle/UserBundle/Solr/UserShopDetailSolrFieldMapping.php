<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Solr;

/**
 * This interface is used to define constant for user solr fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */

interface UserShopDetailSolrFieldMapping
{
    const ID = 'id';

    const COMPANY_WELCOME_MESSAGE = 'u_s_d_welcome_message_s';

    const ABOUT_US = 'u_s_d_about_us_s';

    const PROFILE_EXPOSURE_CATEGORY_ID = 'u_s_d_profile_exposure_category_id_i';

    const POSTCODE = 'u_s_d_postcode_s';

    const DOMICILE_ID = 'u_s_d_domicile_id_i';

    const TOWN_ID = 'u_s_d_town_id_i';

    const USER_STATUS_ID = 'u_s_d_user_status_id_i';

    const USER_PROFILE_NAME = 'u_s_d_user_profile_name_s';

    const USER_COMPANY_LOGO_PATH = 'u_s_d_user_company_logo_path_s';

    const PROFILE_EXPOSURE_MILES = 'u_s_d_profile_exposure_miles_s';

    const PARENT_PROFILE_EXPOSURE_CATEGORY_LVL_1_ID = 'u_s_d_parent_profile_exposure_category_lvl_1_id_i';

    const PARENT_PROFILE_EXPOSURE_CATEGORY_LVL_2_ID = 'u_s_d_parent_profile_exposure_category_lvl_2_id_i';

    const PARENT_PROFILE_EXPOSURE_CATEGORY_LVL_3_ID = 'u_s_d_parent_profile_exposure_category_lvl_3_id_i';

    const PARENT_PROFILE_EXPOSURE_CATEGORY_LVL_4_ID = 'u_s_d_parent_profile_exposure_category_lvl_4_id_i';

    const STORE = 'store';

    const RANDOM = 'random';

    const SHOP_PACKAGE_PURCHASED_AT = 'a_shop_package_purchased_at_i';
    
    const USER_LIVE_ADS_COUNT = 'u_live_ad_count_i';
}
