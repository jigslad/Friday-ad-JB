<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Resource;

/**
 * This interface is used to define rest methods/resources supported by dotmailer.
 * http://api.dotmailer.com/v2/help/wadl
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
interface ResourceInterface
{
    const DATA_FIELDS = 'data-fields';

    const CONTACTS = 'contacts';

    const ADDRESS_BOOKS = 'address-books';
}
