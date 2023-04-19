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

namespace SolidInvoice\MenuBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MenuCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('solidinvoice_menu.provider')) {
            return;
        }

        $definition = $container->getDefinition('solidinvoice_menu.provider');

        $taggedServices = $container->findTaggedServiceIds('cs_core.menu');

        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addBuilder',
                    [
                        $container->getDefinition($id)
                            ->setAutowired(true),
                        $attributes['menu'],
                        $attributes['method'],
                        array_key_exists('priority', $attributes) ? $attributes['priority'] : 0, ]
                );
            }
        }
    }
}
