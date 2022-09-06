<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\DependencyInjection\Compiler;

use SolidInvoice\CoreBundle\Logger\Dbal\TraceLogger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DbalLoggerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('doctrine.dbal.logger.chain') || !$container->getParameter('kernel.debug')) {
            return;
        }

        $logger = new Definition(TraceLogger::class);

        $definition = $container->getDefinition('doctrine.dbal.logger.chain');
        $definition->addMethodCall('addLogger', [$logger]);
    }
}
