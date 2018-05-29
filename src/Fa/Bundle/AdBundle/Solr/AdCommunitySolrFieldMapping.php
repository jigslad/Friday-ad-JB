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
 * This interface is used to define constant for ad solr fields for community.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdCommunitySolrFieldMapping extends AdSolrFieldMapping
{
    const EVENT_START = 'a_c_event_start_i';

    const EVENT_END = 'a_c_event_end_i';

    const EXPERIENCE_LEVEL_ID = 'a_c_experience_level_id_i';

    const EDUCATION_LEVEL_ID = 'a_c_education_level_id_i';

    const META_DATA = 'a_c_meta_data_desc';

    const NO_EVENT_END = 'a_c_no_event_end_b';

    const CUISINE_TYPE_ID = 'a_c_cuisine_type_id_i';
}
