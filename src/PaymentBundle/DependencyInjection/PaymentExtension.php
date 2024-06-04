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

namespace SolidInvoice\PaymentBundle\DependencyInjection;

use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PaymentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition(PaymentFactories::class);

        $factories = [];
        $forms = [];

        foreach ($config['gateways'] as $gatewayConfig) {
            $factories[$gatewayConfig['name']] = $gatewayConfig['factory'];

            if (isset($gatewayConfig['form'])) {
                $forms[$gatewayConfig['name']] = $gatewayConfig['form'];
            }
        }

        $definition->addMethodCall('setGatewayFactories', [$factories]);
        $definition->addMethodCall('setGatewayForms', [$forms]);

        $container->setDefinition($definition->getClass(), $definition);
    }

    public function getAlias(): string
    {
        return 'payment';
    }
}
