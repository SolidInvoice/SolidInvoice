<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MenuCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('csbill_menu.provider')) {
            return;
        }

        $definition = $container->getDefinition('csbill_menu.provider');

        $taggedServices = $container->findTaggedServiceIds('cs_core.menu');

        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addBuilder',
                    [
                        new Reference($id),
                        $attributes['menu'],
                        $attributes['method'],
                        array_key_exists('priority', $attributes) ? $attributes['priority'] : 0, ]
                );
            }
        }
    }
}
