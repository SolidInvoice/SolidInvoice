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

namespace CSBill\PaymentBundle\DependencyInjection;

use CSBill\PaymentBundle\Factory\PaymentFactories;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PaymentExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition(PaymentFactories::class);

        $factories = [];
        $forms = [];
        foreach ($config['gateways'] as $gateway => $gatewayConfig) {
            $factories[$gateway] = $gatewayConfig['factory'];

            if (isset($gatewayConfig['form'])) {
                $forms[$gateway] = $gatewayConfig['form'];
            }
        }

        $definition->addMethodCall('setGatewayFactories', [$factories]);
        $definition->addMethodCall('setGatewayForms', [$forms]);

        $container->setDefinition('payum.factories', $definition);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'payment';
    }
}
