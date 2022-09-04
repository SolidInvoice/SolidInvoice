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

namespace SolidInvoice\MailerBundle\DependencyInjection\CompilerPass;

use SolidInvoice\MailerBundle\Factory\MailerConfigFactory;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class MailerTransportConfigCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('mailer.transport_factory')) {
            return;
        }

        $definition = new Definition(MailerConfigFactory::class);
        $definition->setDecoratedService('mailer.transport_factory');
        $definition->addArgument(new Reference(MailerConfigFactory::class . '.inner'));
        $definition->setArgument('$transports', new TaggedIteratorArgument('solidinvoice_mailer.transport.configurator'));
        $definition->setAutowired(true);
        $container->setDefinition(MailerConfigFactory::class, $definition);
    }
}
