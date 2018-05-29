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

interface UserSolrFieldMapping
{
    const ID = 'id';

    const NAME = 'u_full_name_s';

    const PHONE = 'u_phone_s';

    const MOBILE = 'u_mobile_s';

    const FAX = 'u_fax_s';

    const COMPANY = 'u_company_s';

    const URL = 'u_url_s';

    const EMAIL = 'u_email_s';

    const LOGO = 'u_logo_s';

    const IS_PRIVATE_PHONE_NUMBER = 'u_is_private_phone_number_b';

    const IS_PAYPAL_VERIFIED = 'u_is_paypal_vefiried_b';

    const IS_EMAIL_VERIFIED = 'u_is_email_verified_b';

    const CONTACT_THROUGH_PHONE = 'u_contact_through_phone_b';

    const CONTACT_THROUGH_EMAIL = 'u_contact_through_email_b';

    const PROFILE_USERNAME = 'u_profile_username_b';

    const POSTCODE = 'u_postcode_s';

    const DOMICILE_ID = 'u_domicile_id_i';

    const TOWN_ID = 'u_town_id_i';
}
