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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PaymentFactoryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $payum = $container->getDefinition('payum.static_registry');

        $gatewaysIds = array();
        foreach ($container->findTaggedServiceIds('payum.gateway') as $gatewaysId => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $gatewaysIds[$attributes['gateway']] = $attributes['factory'];
            }
        }

        $payum->addMethodCall('setGatewayFactories', array($gatewaysIds));
    }
}
