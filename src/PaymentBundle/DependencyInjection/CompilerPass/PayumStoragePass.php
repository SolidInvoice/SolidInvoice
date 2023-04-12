<?php
declare(strict_types=1);

namespace SolidInvoice\PaymentBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use SolidInvoice\PaymentBundle\Payum\Storage\DoctrineStorage;

final class PayumStoragePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('payum.storage.doctrine.orm')) {
            return;
        }

        $definition = $container->getDefinition('payum.storage.doctrine.orm');

        $definition->setClass(DoctrineStorage::class);
    }
}
