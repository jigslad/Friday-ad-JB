<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\lib\Carweb\Converter;

use Fa\Bundle\CoreBundle\lib\Util\XML2Array;

/**
 * Fa\Bundle\CoreBundle\lib\Carweb\Converter
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class DefaultConverter implements ConverterInterface
{
    /**
     * Converts string result from API call to something usable
     *
     * @param $string
     * @return mixed
     */
    public function convert($string)
    {
        if (class_exists('Fa\Bundle\CoreBundle\lib\Util\XML2Array')) {
            return XML2Array::createArray($string);
        } else {
            return $string;
        }
    }
}
