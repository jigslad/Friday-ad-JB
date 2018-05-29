<?php

/*
 * This file is part of twig-cache-extension.
 *
 * (c) Alexander <iam.asm89@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Cache;

use Asm89\Twig\CacheExtension\CacheProviderInterface;
use Asm89\Twig\CacheExtension\CacheStrategyInterface;
use Asm89\Twig\CacheExtension\CacheStrategy\LifetimeCacheStrategy as BaseLifetimeCacheStrategy;

/**
 * Strategy for caching with a pre-defined lifetime.
 *
 * The value passed to the strategy is the lifetime of the cache block in
 * seconds.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class LifetimeCacheStrategy extends BaseLifetimeCacheStrategy
{
    /**
     * {@inheritDoc}
     */
    public function generateKey($annotation, $value)
    {
        if (! is_numeric($value)) {
            //todo: specialized exception
            throw new \RuntimeException('Value is not a valid lifetime.');
        }

        return array(
            'lifetime' => $value,
            'key' => $annotation,
        );
    }
}
