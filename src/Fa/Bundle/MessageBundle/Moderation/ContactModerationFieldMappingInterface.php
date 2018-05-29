<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Moderation;

/**
 * This interface is used to define constant for contact moderation fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
interface ContactModerationFieldMappingInterface
{
    const SITE_ID = 'OriginatingSiteId';

    const SENDER = 'Sender';

    const RECIPIENT = 'Recipient';

    const SUBJECT = 'Subject';

    const BODY = 'Body';

    const THREAD_ID = 'ThreadId';

    const IP_ADDRESS = 'IPAddress';

    const CALLBACK_URL = 'callbackUrl';

    const CATEGORIES = 'categories';

    const SUPPLEMENTARY_INFORMATION = 'SupplementaryInformation';

    const ADREF = 'AdRef';

    const COMMENT = 'Comment';

    const REPORTED_EMAIL_ADDRESS = 'ReportedEmailAddress';

    const X_THREAD_ID = 'X-Thread-Id';

    const MESSAGE_ATTACHMENTS = 'Attachments';
}
