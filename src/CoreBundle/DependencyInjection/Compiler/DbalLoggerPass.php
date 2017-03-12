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

namespace CSBill\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DbalLoggerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('doctrine.dbal.logger.chain') || !$container->getParameter('kernel.debug')) {
            return;
        }

        $logger = new Definition('CSBill\CoreBundle\Logger\Dbal\TraceLogger');

        $definition = $container->getDefinition('doctrine.dbal.logger.chain');
        $definition->addMethodCall('addLogger', [$logger]);
    }
}
