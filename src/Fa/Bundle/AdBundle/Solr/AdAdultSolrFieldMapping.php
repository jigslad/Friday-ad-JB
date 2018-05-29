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
 * This interface is used to define constant for ad solr fields for services.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdAdultSolrFieldMapping extends AdSolrFieldMapping
{
    const BUSINESS_NAME = 'a_s_business_name_s';

    const META_DATA = 'a_a_meta_data_desc';

    const SERVICES_ID = 'a_a_services_id_txt';

    const HAS_USER_LOGO = 'a_a_has_user_logo_b';

    const TRAVEL_ARRANGEMENTS_ID = 'a_a_travel_arrangements_id_i';

    const INDEPENDENT_OR_AGENCY_ID = 'a_a_independent_or_agency_id_i';
}
