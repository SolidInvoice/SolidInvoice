<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\DependencyInjection\CompilerPass;

use CSBill\PaymentBundle\Factory\PaymentFactories;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PaymentFactoryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = new Definition(PaymentFactories::class);

        $builder = $container->getDefinition('payum.builder');

        $gatewaysIds = [];
        foreach ($builder->getMethodCalls() as $call) {
            if ($call[0] === 'addGateway') {
                $gatewaysIds[$call[1][0]] = $call[1][1]['factory'];
            }
        }

        $definition->addMethodCall('setGatewayFactories', [$gatewaysIds]);

        $container->setDefinition('payum.factories', $definition);
    }
}
