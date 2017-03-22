<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SettingsLoaderCompilerPass.
 */
class SettingsLoaderCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('csbill_settings.manager')) {
            return;
        }

        $services = $container->findTaggedServiceIds('settings.loader');

        if (count($services) > 0) {
            foreach ($services as $id => $parameters) {
                $container->getDefinition('csbill_settings.manager')->addMethodCall('addSettingsLoader', [new Reference($id)]);
            }
        }
    }
}
