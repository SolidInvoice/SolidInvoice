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

namespace SolidInvoice\NotificationBundle\DependencyInjection\CompilerPass;

use SolidInvoice\NotificationBundle\Factory\NotificationTransportFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class NotificationTransportConfigCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach (['chatter.transport_factory', 'chatter.transport_factory'] as $factory) {
            if (! $container->hasDefinition($factory)) {
                continue;
            }

            $definition = new Definition(NotificationTransportFactory::class);
            $definition->setDecoratedService($factory);
            $definition->addArgument(new Reference(NotificationTransportFactory::class . '.inner'));
            $definition->setAutowired(true);
            $container->setDefinition(NotificationTransportFactory::class, $definition);
        }
    }
}
