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

namespace SolidInvoice\PaymentBundle\DependencyInjection\CompilerPass;

use SolidInvoice\PaymentBundle\Payum\Storage\DoctrineStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PayumStoragePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('payum.storage.doctrine.orm')) {
            return;
        }

        $definition = $container->getDefinition('payum.storage.doctrine.orm');

        $definition->setClass(DoctrineStorage::class);
    }
}
