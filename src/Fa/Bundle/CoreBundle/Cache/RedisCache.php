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

use Fa\Bundle\CoreBundle\Cache\BaseCache;

/**
 * This is the wrapper class used for redis cache.
 * used built in php library.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class RedisCache extends BaseCache
{
    const ALL = 'ALL';
    /**
     * Redis object.
     *
     * @var object
     */
    private $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
    * Gets the cache content for a given key.
    *
    * @param string $key     The cache key.
    * @param mixed  $default The default value is the key does not exist or not valid anymore.
    *
    * @return string The data of the cache.
    */
    public function get($key, $default = null)
    {
        if (!is_array($key)) {
            return $this->redis->get($key);
        }

        $values = array();

        foreach ($key as $keyString) {
            $values[$keyString] = $this->get($keyString);
        }

        return ($values ? $values : $default);
    }

    /**
    * Returns true if there is a cache for the given key.
    *
    * @param string $key The cache key.
    *
    * @return Boolean true If the cache exists, false otherwise.
    */
    public function exists($key)
    {
        return $this->redis->exists($key);
    }

    /**
    * Saves some data in the cache.
    *
    * @param string $key      The cache key.
    * @param string $data     The data to put in cache.
    * @param int    $lifetime The lifetime.
    *
    * @return Boolean true If no problem.
    */
    public function set($key, $value, $ttl = null)
    {
        if (!$ttl) {
            return $this->redis->set($key, $value);
        }

        return $this->redis->set($key, $value, $ttl);
    }

    /**
     * Saves some data in the cache.
     *
     * @param string $set
     * @param string $key      The cache key.
     * @param string $data     The data to put in cache.
     * @param int    $lifetime The lifetime.
     *
     * @return Boolean true If no problem.
     */
    public function hSet($set, $key, $value, $ttl = null)
    {
        $hSet = $this->redis->hset($set, $key, $value);
        if ($ttl) {
            $this->redis->expire($set, $ttl);
        }

        return $hSet;
    }

    /**
     * Gets the cache content for a given key.
     *
     * @param string $set
     * @param string $key     The cache key.
     * @param mixed  $default The default value is the key does not exist or not valid anymore.
     *
     * @return string The data of the cache.
     */
    public function hGet($set, $key, $default = null)
    {
        if (!is_array($key)) {
            return $this->redis->hGet($set, $key);
        }

        return  $this->redis->hmGet($set, $key);
    }

    /**
    * Removes a content from the cache.
    *
    * @param string $key The cache key.
    *
    * @return Boolean true If no problem.
    */
    public function delete($key)
    {
        return $this->redis->delete($key);
    }

    /**
    * Removes content from the cache that matches the given pattern.
    *
    * @param string $pattern The cache key pattern.
    *
    * @return Boolean true If no problem.
    */
    public function removePattern($pattern)
    {
        $key = $this->redis->keys($pattern);

        if (!is_array($key)) {
            $key = array($key);
        }

        return $this->redis->delete($key);
    }

    /**
     * Removes content from the cache that matches the given pattern.
     *
     * @param string $hash
     * @param string $pattern The cache key pattern.
     *
     * @return Boolean true If no problem.
     */
    public function removeFromHashByPattern($hash, $pattern)
    {
        $keysToRemove = array();
        $keysToRemove[] = $hash;
        $it = NULL;
        if ($this->redis instanceof \Redis) {
            $redis = $this->redis;
        } else {
            $redis = $this->redis->getRedis();
        }

        /* Don't ever return an empty array until we're done iterating */
        $redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
        //$params = array($hash, $pattern);
        while($arr_keys = $redis->hScan($hash, $it, $pattern)) {
            foreach($arr_keys as $str_field => $str_value) {
                $keysToRemove[] = $str_field;
            }
        }

        if (count($keysToRemove) > 1) {
            call_user_func_array(array($redis,'hDel'), $keysToRemove);
        }
    }

    /**
    * Cleans the cache.
    *
    * @param string $mode The clean mode.
    *                     sfCache::ALL: Remove all keys (default).
    *                     sfCache::OLD: Remove all expired keys.
    *
    * @return Boolean true If no problem.
    */
    public function clean($mode = self::ALL)
    {
        return $this->redis->flushDB();
    }

    /**
    * Returns the timeout for the given key.
    *
    * @param string $key The cache key.
    *
    * @return int The timeout time.
    */
    public function getTimeout($key)
    {
        return $this->redis->ttl($key);
    }

    /**
    * Returns the last modification date of the given key.
    *
    * @param string $key The cache key.
    *
    * @return int The last modified time.
    */
    public function getLastModified($key)
    {
        return null;
    }

    /**
     * Removes all keys of all databases.
     *
     * @return boolean
     */
    public function flushAll()
    {
        return $this->redis->flushAll();
    }

    /**
     * Increment counter.
     *
     * @param string $key The cache key.
     */
    public function incr($key)
    {
        return $this->redis->incr($key);
    }

    /**
     * Get all keys with matching pattern
     *
     * @param string $pattern The cache key pattern.
     *
     * @return array
     */
    public function keys($pattern)
    {
        return $this->redis->keys($pattern);
    }

    /**
     * Increments the score of a member from a sorted set by a given amount.
     *
     * @param string $key         The cache key.
     * @param string $incrementBy Increment by.
     * @param string $value       Value.
     *
     * @return integer
     */
    public function zIncrBy($key, $incrementBy, $value)
    {
        return $this->redis->zIncrBy($key, $incrementBy, $value);
    }

    /**
     * Returns a range of elements from the ordered set stored at the specified key, with values in the range [start, end].
     *
     * @param string  $key        The cache key.
     * @param integer $start      Start.
     * @param integer $limit      Limit.
     * @param boolean $withScores With scores flag.
     *
     * @return array
     */
    public function zRange($key, $start, $limit, $withScores)
    {
        return $this->redis->zRange($key, $start, $limit, $withScores);
    }

    /**
     * Deletes the elements of the sorted set stored at the specified key which have rank in the range [start,end].
     *
     * @param string  $key   The cache key.
     * @param integer $start Start.
     * @param integer $limit Limit.
     *
     * @return integer
     */
    public function zDeleteRangeByRank($key, $start, $limit)
    {
        return $this->redis->zDeleteRangeByRank($key, $start, $limit);
    }

    /**
     * Deletes a specified member from the ordered set.
     *
     * @param string  $key   The cache key.
     * @param string  $value Value.
     *
     * @return integer
     */
    public function zDelete($key, $value)
    {
        return $this->redis->zDelete($key, $value);
    }

    /**
     * Returns the cardinality of an ordered set.
     *
     * @param string  $key The cache key.
     *
     * @return integer
     */
    public function zSize($key)
    {
        return $this->redis->zSize($key);
    }
}
