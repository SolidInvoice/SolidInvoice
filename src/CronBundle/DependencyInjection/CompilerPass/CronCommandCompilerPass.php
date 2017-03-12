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

namespace CSBill\CronBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class CronCommandCompilerPass.
 */
class CronCommandCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('cron.runner')) {
            return;
        }

        $definition = $container->getDefinition('cron.runner');

        $taggedServices = $container->findTaggedServiceIds('cron.command');

        foreach ($taggedServices as $id => $tagAttributes) {
            $definition->addMethodCall('addCommand', [new Reference($id)]);
        }
    }
}
