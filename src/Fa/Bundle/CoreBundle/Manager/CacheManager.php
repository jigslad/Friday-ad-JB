<?php
namespace Fa\Bundle\CoreBundle\Manager;

/**
 * Fa\Bundle\CoreBundle\Manager\CacheManager
 *
 * This manager is used to set/get/remove cache.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class CacheManager
{
    protected $cache;
    protected $cacheKey;
    protected $container;
    const ALL = 'ALL';

    /**
     * Constructor.
     */
    public function __construct($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    /**
     * Set container object.
     *
     * @param Object $container Container object.
     */
    public function setServiceContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Set cache client.
     *
     * @param RedisCache/MemCache $cache Instance of cache client
     */
    public function setCacheService($cache)
    {
        $this->cache = $cache;
    }

    /**
    * Gets the cache content for a given key.
    *
    * @param string $key     The cache key
    * @param mixed  $default The default value is the key does not exist or not valid anymore
    *
    * @return string The data of the cache
    */
    public function get($key, $default = null)
    {
        //return unserialize($this->cache->get($this->buildCacheKey($key), $default));
        return unserialize($this->container->get('snc_redis.read')->get($this->buildCacheKey($key)));
    }

    /**
    * Returns true if there is a cache for the given key.
    *
    * @param string $key The cache key
    *
    * @return Boolean true if the cache exists, false otherwise
    */
    public function exists($key)
    {
        return $this->cache->exists($this->buildCacheKey($key));
    }

    /**
    * Saves some data in the cache.
    *
    * @param string $key      The cache key
    * @param string $data     The data to put in cache
    * @param int    $lifetime The lifetime
    *
    * @return Boolean true if no problem
    */
    public function set($key, $value, $ttl = null)
    {
        return $this->cache->set($this->buildCacheKey($key), serialize($value), $ttl);
    }

    /**
     * Gets the cache content for a given key.
     *
     * @param string $set
     * @param string $key     The cache key
     * @param mixed  $default The default value is the key does not exist or not valid anymore
     *
     * @return string The data of the cache
     */
    public function hGet($set, $key, $default = null)
    {
        //return unserialize($this->cache->get($this->buildCacheKey($key), $default));
        return unserialize($this->container->get('snc_redis.read')->hGet($this->buildCacheKey($set), $key));
    }

    /**
     * Saves some data in the cache.
     *
     * @param string $set
     * @param string $key      The cache key
     * @param string $data     The data to put in cache
     * @param int    $lifetime The lifetime
     *
     * @return Boolean true if no problem
     */
    public function hSet($set, $key, $value, $ttl = null)
    {
        return $this->cache->hSet($this->buildCacheKey($set), $key, serialize($value), $ttl);
    }


    /**
    * Removes a content from the cache.
    *
    * @param string $key The cache key
    *
    * @return Boolean true if no problem
    */
    public function delete($key)
    {
        return $this->cache->delete($this->buildCacheKey($key));
    }

    /**
    * Removes content from the cache that matches the given pattern.
    *
    * @param string $pattern The cache key pattern
    *
    * @return Boolean true if no problem
    */
    public function removePattern($pattern)
    {
        return $this->cache->removePattern($this->buildCacheKey($pattern));
    }

    /**
     * Removes content from the cache that matches the given pattern.
     *
     * @param string $hash
     * @param string $pattern The cache key pattern
     *
     * @return Boolean true if no problem
     */
    public function removeFromHashByPattern($hash, $pattern)
    {
        return $this->cache->removeFromHashByPattern($this->buildCacheKey($hash), $pattern);
    }

    /**
     * Cleans the cache.
     *
     * @param string $mode The clean mode
     *                     sfCache::ALL: remove all keys (default)
     *                     sfCache::OLD: remove all expired keys
     *
     * @return Boolean true if no problem
     */
    public function clean($mode = self::ALL)
    {
        return $this->cache->flushDB();
    }

    /**
     * Returns the timeout for the given key.
     *
     * @param string $key The cache key
     *
     * @return int The timeout time
     */
    public function getTimeout($key)
    {
        return $this->cache->ttl($this->buildCacheKey($key));
    }

    /**
     * Returns the last modification date of the given key.
     *
     * @param string $key The cache key
     *
     * @return int The last modified time
     */
    public function getLastModified($key)
    {
        return null;
    }

    /**
     * Removes all keys of all databases
     *
     * @return boolean
     */
    public function flushAll()
    {
        return $this->cache->flushAll();
    }

    /**
     * Append cache key
     *
     * @param string $key The cache key
     *
     * @return string
     */
    public function buildCacheKey($key)
    {
        return $this->cacheKey.$key;
    }

    /**
     * Increment counter.
     *
     * @param string $key The cache key.
     */
    public function incr($key)
    {
        return $this->cache->incr($this->buildCacheKey($key));
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
        return $this->cache->keys($this->buildCacheKey($pattern));
    }

    /**
     * Gets the value which is not serialized.
     *
     * @param string $key The cache key
     *
     * @return string The data of the cache
     */
    public function getSimpleKey($key)
    {
        return $this->cache->get($this->buildCacheKey($key));
    }

    /**
     * Increments the score of a member from a sorted set by a given amount.
     *
     * @param string  $key         The cache key.
     * @param integer $incrementBy Increment by.
     * @param string  $value       Value.
     *
     * @return integer
     */
    public function zIncrBy($key, $incrementBy, $value)
    {
        return $this->cache->zIncrBy($this->buildCacheKey($key), $incrementBy, $value);
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
        return $this->cache->zRange($this->buildCacheKey($key), $start, $limit, $withScores);
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
        return $this->cache->zDeleteRangeByRank($this->buildCacheKey($key), $start, $limit);
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
        return $this->cache->zDelete($this->buildCacheKey($key), $value);
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
        return $this->cache->zSize($this->buildCacheKey($key));
    }
}
