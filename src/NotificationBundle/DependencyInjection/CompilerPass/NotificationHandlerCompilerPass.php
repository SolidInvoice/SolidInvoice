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

use Namshi\Notificator\Manager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class NotificationHandlerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(Manager::class)) {
            return;
        }

        $definition = $container->getDefinition(Manager::class);

        $services = $container->findTaggedServiceIds('notification.handler');

        foreach (array_keys($services) as $id) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}
