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

interface AdServicesSolrFieldMapping extends AdSolrFieldMapping
{
    const BUSINESS_NAME = 'a_s_business_name_s';

    const SERVICE_TYPE_ID = 'a_s_service_type_id_i';

    const SERVICES_OFFERED_ID = 'a_s_services_offered_id_txt';

    const EVENT_TYPE_ID = 'a_s_event_type_id_i';

    const META_DATA = 'a_p_meta_data_desc';
}
