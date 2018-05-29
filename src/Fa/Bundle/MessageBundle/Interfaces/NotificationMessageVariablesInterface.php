<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Interfaces;

/**
 * This inteface is used for indexable dimentions.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
interface NotificationMessageVariablesInterface
{
    // variables
    const ADVERT_TITLE                   = 'advert title';
    const USER_NAME_BUYER                = 'user name buyer';
    const USER_NAME_SELLER               = 'user name seller';
    const PRINT_DATE                     = 'print date';
    const URL_FACEBOOK_SHARE             = 'url Facebook share';
    const URL_TWITTER_SHARE              = 'url Twitter share';
    const URL_TO_MARK_ADVERT_AS_SOLD     = 'url to mark advert as sold';
    const URL_TO_REFRESH_ADVERT          = 'url to refresh advert';
    const URL_TO_EDIT_ADVERT             = 'url to edit advert';
    const URL_TO_UPGRADE_ADVERT          = 'url to upgrade advert';
    const URL_UPLOAD_PHOTOS              = 'url upload photos';
    const URL_TO_MANAGE_MY_ADS           = 'url to manage my ads';
    const URL_TO_MESSAGE_INBOX           = 'url to message inbox';
    const URL_TO_PROFILE_OR_EDIT_PROFILE = 'url to profile or edit profile';
    const URL_TO_USER_REVIEWS            = "url to user's reviews";
    const URL_TO_VIEW_ORDERS             = 'url to view orders';
    const URL_TO_LEAVE_REVIEW            = 'url to leave review';
}
