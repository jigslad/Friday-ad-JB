<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\lib\Carweb\Cache;

/**
 * Fa\Bundle\CoreBundle\lib\Carweb\Cach\CacheInterface
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
interface CacheInterface
{
    /**
     * check if the current item is cached
     *
     * @param $key
     * @return bool
     */
    public function has($key);
    /**
     * gets cached value for current item
     *
     * @param $key
     * @return mixed
     */
    public function get($key);
    /**
     * Saves the value to cache
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public function save($key, $value);
    /**
     * Clears the current value
     *
     * @param $key
     * @return mixed
     */
    public function clear($key);
}
