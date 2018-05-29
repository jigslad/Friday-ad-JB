<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Doctrine;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\IntegerNode;

/**
 * Retrieve the entity listener instance according to its name.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class EntityListenerResolver extends DefaultEntityListenerResolver
{
    /**
     * Container.
     *
     * @var $container
     */
    private $container;

    /**
     * Mapping.
     *
     * @var $mapping
     */
    private $mapping;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->mapping = array();
    }

    /**
     * Add mapping.
     *
     * @param string $className
     * @param string $service
     */
    public function addMapping($className, $service)
    {
        $this->mapping[$className] = $service;
    }

    /**
     * Resolve.
     *
     * @param string $className
     *
     * @see \Doctrine\ORM\Mapping\DefaultEntityListenerResolver::resolve()
     */
    public function resolve($className)
    {
        if (isset($this->mapping[$className]) && $this->container->has($this->mapping[$className])) {
            return $this->container->get($this->mapping[$className]);
        }

        return parent::resolve($className);
    }
}
