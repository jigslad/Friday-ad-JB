<?php

namespace Fa\Bundle\CoreBundle;

use Fa\Bundle\CoreBundle\DependencyInjection\Compiler\DoctrineEntityListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FaCoreBundle extends Bundle
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\HttpKernel\Bundle\Bundle::build()
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    
        $container->addCompilerPass(new DoctrineEntityListenerPass());
    }
}
