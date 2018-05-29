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

/**
 * Fa\Bundle\CoreBundle\lib\Carweb\Converter
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
interface ConverterInterface
{
    /**
     * Converts string result from API call to something usable
     *
     * @param $string
     * @return mixed
     */
    public function convert($string);
}
