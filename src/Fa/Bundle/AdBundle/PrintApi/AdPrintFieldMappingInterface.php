<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\PrintApi;

/**
 * This interface is used to define constant for ad Print fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
  */
interface AdPrintFieldMappingInterface
{
    const ADREF = 'Adref';

    const DESCRIPTION = 'Description';
    
    const TITLE = 'Title';

    const EDITION_CODE = 'EditionCode';

    const INSERT_DATE = 'InsertDate';

    const TOWN = 'Town';

    const POSTCODE = 'Postcode';

    const PRICE = 'Price';

    const PRICE_DESCRIPTOR = 'PriceDescriptor';

    const CURRENCY = 'Currency';

    const DATE_CREATED = 'DateCreated';

    const DATE_MODIFIED = 'DateModified';

    const STYLE = 'Style';

    const CATEGORY = 'Category';

    const CLASSIFICATION = 'Classification';

    const IMAGES = 'Images';

    const ADDITIONAL_FIELDS = 'AdditionalFields';
    
    const IS_PRIVATE_ADVERT = 'IsPrivateAdvert';
    
    const UPSELLS = 'Upsells';
    
    const PAID_INSERT = 'PaidInsert';
    
    const ADVERTISER_ID = 'AdvertiserID';
    
    const EMAIL = 'Email';
    
    const PHONE_NUMBER = 'PhoneNumber';

    const ADVERT_DETAILS_URL = 'AdvertDetailsURL';
}
