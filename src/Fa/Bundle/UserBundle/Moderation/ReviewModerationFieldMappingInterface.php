<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Moderation;

/**
 * This interface is used to define constant for ad moderation fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
interface ReviewModerationFieldMappingInterface
{
    const SITE_ID = 'siteId';

    const REVIEW_ID = 'adRef';

    const CALLBACK_URL = 'callbackUrl';

    const IS_FOR_MANUAL_MODERATION = 'isForManualModeration';

    const TITLE = 'title';

    const DESCRIPTIONS = 'descriptions';

    const DESCRIPTION = 'description';

    const TYPE = 'type';

    const DETAIL = 'detail';

    const PRICE = 'price';

    const CURRENCY = 'currency';

    const EMAIL = 'email';

    const IP_ADDRESS = 'idAddress';

    const USERNAME = 'username';

    const DATE_CREATED = 'dateCreated';

    const CLASSIFICATION = 'classification';

    const KEY = 'key';

    const VALUE = 'value';

    const ADDITIONAL_FIELDS = 'additionalFields';
}
