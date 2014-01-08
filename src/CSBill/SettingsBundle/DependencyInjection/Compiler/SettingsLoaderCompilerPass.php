<?php

/*
 * This file is part of the CSBillSettingsBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\SettingsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SettingsLoaderCompilerPass
 * @package CSBill\SettingsBundle\DependencyInjection\Compiler
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
                $container->getDefinition('csbill_settings.manager')->addMethodCall('addSettingsLoader', array(new Reference($id)));
            }
        }
    }
}
