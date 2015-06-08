<?php
/**
 * This file is part of the MiWay Business Insurance project.
 *
 * @author      MiWay Developer Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\CronBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class CronCommandCompilerPass
 *
 * @package CSBill\CronBundle\DependencyInjection\CompilerPass
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
            $definition->addMethodCall('addCommand', array(new Reference($id)));
        }
    }
}