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
 * This interface is used to define constant for ad solr fields for jobs.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdJobsSolrFieldMapping extends AdSolrFieldMapping
{
    const CONTRACT_TYPE_ID = 'a_j_contract_type_id_txt';

    const META_DATA = 'a_f_meta_data_desc';

    const IS_FEATURED_EMPLOYER = 'a_j_is_featured_employer_b';

    const IS_JOB_OF_WEEK = 'a_j_is_job_of_week_b';

    const HAS_USER_LOGO = 'a_j_has_user_logo_b';

    const SALARY_BAND_ID = 'a_j_salary_band_id_i';

    const FEED_AD_SALARY = 'a_f_feed_ad_salary_s';
}
