<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Cache;

use Snc\RedisBundle\Client\Phpredis\Client;

/**
 * phpredis client wrapper
 */
class RedisClient extends Client
{
    public function getRedis()
    {
        return $this->redis;
    }
}
