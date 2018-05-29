<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Moderation;

/**
 * This interface is used to define constant for ad moderation fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
interface AdModerationFieldMappingInterface
{
    const SITE_ID = 'siteId';

    const ADREF = 'adRef';

    const CALLBACK_URL = 'callbackUrl';

    const PRIORITY = 'priority';

    const IS_FOR_MANUAL_MODERATION = 'isForManualModeration';

    const MANUAL_MODERATION_REASON = 'manualModerationReason';

    const TITLE = 'title';

    const DESCRIPTIONS = 'descriptions';

    const DESCRIPTION = 'description';

    const TYPE = 'type';

    const PRICE = 'price';

    const CURRENCY = 'currency';

    const EMAIL = 'email';

    const IP_ADDRESS = 'ipAddress';

    const USERNAME = 'username';

    const DATE_CREATED = 'dateCreated';

    const DATE_MODIFIED = 'dateModified';

    const CLASSIFICATION = 'classification';

    const EDIT_CALLBACK_URL = 'EditAdCallbackUrl';

    const EXPECTED_RESPONSE = 'ExpectedResponse';

    const IMAGES = 'images';

    const IMAGE = 'image';

    const KEY = 'key';

    const VALUE = 'value';

    const ID = 'id';

    const ADDITIONAL_FIELDS = 'additionalFields';

    const PHONE_NUMBER = 'phoneNumber';

    const PAID = 'paid';

    const PAID_BEFORE = 'paidBefore';

    const YAC = 'yac';

    const RESPONSE_DELAY_SECONDS = 'ResponseDelaySeconds';

    const DETAIL = 'detail';

    const ISBUYITNOW = 'IsBuyItNow';

    const SUBTITLE = 'Subtitle';

    const AD_TOWN = 'town';

    const AD_POSTCODE = 'postcode';

    const AD_OWNER = 'owner';

    const AD_OWNER_FIRSTNAME = 'firstName';

    const AD_OWNER_LASTNAME = 'lastName';

    const AD_OWNER_USERTYPE = 'userType';

    const AD_OWNER_BUSINESSNAME = 'businessName';

    const AD_OWNER_DATEREGISTERED = 'dateRegistered';
}
