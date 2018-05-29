<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * FaDabs\Bundle\CoreBundle\Manager\CacheManager
 *
 * This manager is used to set/get/remove cache.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class QueryCacheManager
{
    /**
     *  Container object
     *
     * @var object
     */
    protected $container;

    /**
     * Instance of cache client. RedisCache/MemCache
     *
     * @var object
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     */
    public function __construct(Container $container)
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
        $this->client = $cache;
    }

    /**
     * Returns cache service object
     *
     * @return object
     */
    public function getCacheService()
    {
        return $this->client;
    }

    /**
    * Gets the cache content for a given key.
    *
    * @param string  $key      The cache key
    * @param mixed   $default  The default value is the key does not exist or not valid anymore
    * @param integer $lifetime Cache lifetime in seconds.
    *
    * @return string The data of the cache
    */
    public function init($qb, $cacheKey = null, $lifetime = 3600)
    {
        $qb = $qb->setResultCacheDriver($this->client)
            ->setResultCacheLifetime($lifetime);

        if ($cacheKey) {
            $qb->setResultCacheId($this->buildCacheKey($cacheKey));
        }

        return $qb;
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
        return unserialize($this->client->getRedis()->get($this->buildCacheKey($key)));
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
        return $this->container->getParameter('fa.cache.key').'_'.$key;
    }
}
