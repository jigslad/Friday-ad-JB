<?php

/**
 * This file is part of twig-cache-extension.
 *
 * (c) Alexander <iam.asm89@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Cache;

use Asm89\Twig\CacheExtension\CacheProviderInterface;
use Doctrine\Common\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Adapter class to use the cache classes provider by Doctrine.
 *
 * @author Alexander <iam.asm89@gmail.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class RedisCacheAdapter implements CacheProviderInterface
{
    /**
     * Container object.
     *
     * @var object
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function fetch($key)
    {
        $env = $this->container->getParameter('kernel.environment');
        if ($env == 'dev' || $env == 'live_dev') {
            $key = $key.'__DEV__';
        }
        $value =  CommonManager::getCacheVersion($this->container, $key);

        if ($value) {
            return gzuncompress($value);
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function save($key, $value, $lifetime = 0)
    {
        $env = $this->container->getParameter('kernel.environment');
        if ($env == 'dev' || $env == 'live_dev') {
            $key = $key.'__DEV__';
        }
        return CommonManager::setCacheVersion($this->container, $key, gzcompress($value), $lifetime);
    }
}
