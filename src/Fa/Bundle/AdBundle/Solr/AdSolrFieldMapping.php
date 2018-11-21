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
 * This interface is used to define constant for ad solr fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdSolrFieldMapping
{
    // Ad fields.

    const ID = 'id';

    const USER_ID = 'a_user_id_i';

    const STATUS_ID = 'a_status_id_i';

    const TYPE_ID = 'a_type_id_i';

    const CATEGORY_LEVEL = 'a_category_level_i';

    const CATEGORY_ID = 'a_category_id_i';

    const ROOT_CATEGORY_ID = 'a_parent_category_lvl_1_id_i';

    const CATEGORY_MAKE_ID = 'a_parent_category_lvl_3_id_i';

    const PRICE = 'a_price_d';

    const IS_NEW = 'a_is_new_i';

    const USE_PRIVACY_NUMBER = 'a_use_privacy_number_b';

    const PRIVACY_NUMBER = 'a_privacy_number_s';

    const USER_PHONE_NUMBER = 'a_user_phone_number_s';

    const TITLE = 'a_title_s';

    const DESCRIPTION = 'a_description_desc';

    const KEYWORD_SEARCH = 'a_keyword_search_desc';

    const HAS_VIDEO = 'a_has_video_b';

    const RENEWED_AT = 'a_renewed_at_i';

    const EXPIRES_AT = 'a_expires_at_i';

    const SOLD_AT = 'a_sold_at_i';

    const SOLD_PRICE = 'a_sold_price_d';

    const CREATED_AT = 'a_created_at_i';

    const PUBLISHED_AT = 'a_published_at_i';

    const UPDATED_AT = 'a_updated_at_i';

    const PERSONALIZED_TITLE = 'a_personalized_title_s';

    const QTY = 'a_qty_i';

    const QTY_SOLD = 'a_qty_sold_i';

    const AD_REF = 'a_ad_ref_s';

    const DELIVERY_METHOD_OPTION_ID = 'a_delivery_method_option_id_i';

    const POSTAGE_PRICE = 'a_postage_price_d';

    const PAYMENT_METHOD_OPTION_ID = 'a_payment_method_option_id_i';

    const WEEKLY_REFRESH_AT = 'a_weekly_refresh_at_i';

    const WEEKLY_REFRESH_PUBLISHED_AT = 'a_weekly_refresh_published_at_i';

    const WEEKLY_REFRESH_COUNT = 'a_weekly_refresh_count_i';

    const IS_TOP_AD = 'a_is_topad_b';

    const IS_URGENT_AD = 'a_is_urgent_ad_b';

    const IS_HOMEPAGE_FEATURE_AD = 'a_is_homepage_feature_ad_b';

    const RANDOM = 'random';

    const SCORE = 'score';

    const IS_TRADE_AD = 'a_is_trade_ad_b';

    const HAS_PROFILE_EXPOSURE = 'a_has_profile_exposure_b';

    const PROFILE_EXPOSURE_MILES = 'a_profile_exposure_miles_s';

    const SHOP_PACKAGE_CATEGORY_ID = 'a_shop_package_category_id_i';

    const SHOP_PACKAGE_PURCHASED_AT = 'a_shop_package_purchased_at_i';

    const AD_USER_BUSINESS_CATEGORY_ID = 'a_ad_user_business_category_id_i';

    // Ad Image fields

    const PATH = 'a_i_path_img';

    const ORD = 'a_i_ord_img';

    const HASH = 'a_i_hash_img';

    const AWS = 'a_i_aws_img';

    const IMAGE_NAME = 'a_i_image_name_img';

    const TOTAL_IMAGES = 'a_total_images_i';

    // Ad Location fields

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

    const PARENT_CATEGORY_LVL_2_ID = 'a_parent_category_lvl_2_id_i';

    const PARENT_CATEGORY_LVL_3_ID = 'a_parent_category_lvl_3_id_i';

    const PARENT_CATEGORY_LVL_4_ID = 'a_parent_category_lvl_4_id_i';

    const YOUTUBE_VIDEO_URL = 'a_youtube_video_url_s';

    // feed ad related fields
    const IS_AFFILIATE_AD = 'a_is_affiliate_b';
    const IMAGE_COUNT     = 'a_img_count_id_i';
    const TRACK_BACK_URL  = 'a_track_back_url_s';
    const AD_SOURCE       = 'a_ad_source_s';
    const IS_FEED_AD      = 'a_is_feed_ad_b';
    
    const AREA_ID 					= 'a_l_area_id_txt';
    const IS_SPECIAL_AREA_LOCATION 	= 'a_is_special_area_location_b';

    const IS_BOOSTED = 'a_is_boosted_b';
}
