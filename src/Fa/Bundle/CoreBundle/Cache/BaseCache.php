<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Cache;

/**
 * This is the base abstract class for all type of cache.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
abstract class BaseCache
{
    const ALL = 'ALL';
    /**
     * Gets the cache content for a given key.
     *
     * @param string $key     The cache key.
     * @param mixed  $default The default value is the key does not exist or not valid anymore.
     *
     * @return string The data of the cache.
     */
    abstract public function get($key, $default = null);

    /**
     * Returns true if there is a cache for the given key.
     *
     * @param string $key The cache key.
     *
     * @return Boolean true If the cache exists, false otherwise.
    */
    abstract public function exists($key);

    /**
     * Saves some data in the cache.
     *
     * @param string $key      The cache key.
     * @param string $value    The data to put in cache.
     * @param int    $ttl      The lifetime.
     *
     * @return Boolean true If no problem.
    */
    abstract public function set($key, $value, $ttl = null);

    /**
     * Removes a content from the cache.
     *
     * @param string $key The cache key.
     *
     * @return Boolean true If no problem.
    */
    abstract public function delete($key);

    /**
     * Removes content from the cache that matches the given pattern.
     *
     * @param string $pattern The cache key pattern.
     *
     * @return Boolean true If no problem.
     *
     * @see patternToRegexp
    */
    abstract public function removePattern($pattern);

    /**
     * Cleans the cache.
     *
     * @param string $mode The clean mode
     *                     sfCache::ALL: Remove all keys (default).
     *                     sfCache::OLD: Remove all expired keys.
     *
     * @return Boolean true If no problem.
    */
    abstract public function clean($mode = self::ALL);

    /**
     * Returns the timeout for the given key.
     *
     * @param string $key The cache key.
     *
     * @return int The timeout time.
    */
    abstract public function getTimeout($key);

    /**
     * Returns the last modification date of the given key.
     *
     * @param string $key The cache key.
     *
     * @return int The last modified time.
    */
    abstract public function getLastModified($key);
}
