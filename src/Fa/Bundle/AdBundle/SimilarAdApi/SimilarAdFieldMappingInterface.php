<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\SimilarAdApi;

/**
 * This interface is used to define constant for similar ad fields.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
  */
interface SimilarAdFieldMappingInterface
{
    const TITLE = 'Title';

    const AD_URL = 'AdURL';

    const DESCRIPTION = 'Description';

    const IMAGE_THUMB_URL = 'ImageThumbURL';

    const NUMBER_OF_IMAGES = 'NumberOfImages';

    const PRICE = 'Price';

    const TOWN = 'Town';
}
