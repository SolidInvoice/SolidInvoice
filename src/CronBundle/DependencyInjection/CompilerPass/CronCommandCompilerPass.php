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

namespace SolidInvoice\CronBundle\DependencyInjection\CompilerPass;

use SolidInvoice\CronBundle\Runner;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CronCommandCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(Runner::class)) {
            return;
        }

        $definition = $container->getDefinition(Runner::class);

        $taggedServices = $container->findTaggedServiceIds('cron.command');

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addCommand', [new Reference($id)]);
        }
    }
}
