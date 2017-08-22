<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DashboardBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DashboardWidgetCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('dashboard.widget.factory')) {
            return;
        }

        $definition = $container->getDefinition('dashboard.widget.factory');
        $taggedServices = $container->findTaggedServiceIds('dashboard.widget');

        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                if (!isset($attributes['location'])) {
                    $attributes['location'] = null;
                }

                if (!isset($attributes['priority'])) {
                    $attributes['priority'] = null;
                }

                $definition->addMethodCall(
                    'add',
                    [new Reference($id), $attributes['location'], $attributes['priority']]
                );
            }
        }
    }
}
