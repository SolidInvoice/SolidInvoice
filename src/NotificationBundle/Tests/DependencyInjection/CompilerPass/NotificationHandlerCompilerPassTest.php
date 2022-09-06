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

namespace SolidInvoice\NotificationBundle\Tests\DependencyInjection\CompilerPass;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Namshi\Notificator\Manager;
use SolidInvoice\NotificationBundle\DependencyInjection\CompilerPass\NotificationHandlerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class NotificationHandlerCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testProcessWithNoSender(): void
    {
        $this->compile();

        $this->assertContainerBuilderNotHasService(Manager::class);
    }

    public function testProcessWithHandlers(): void
    {
        $collectingService = new Definition();
        $this->setDefinition(Manager::class, $collectingService);

        $collectedService = new Definition();
        $collectedService->addTag('notification.handler');
        $this->setDefinition('collected_service', $collectedService);

        $collectedService2 = new Definition();
        $collectedService2->addTag('notification.handler');
        $this->setDefinition('collected_service2', $collectedService2);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            Manager::class,
            'addHandler',
            [
                new Reference('collected_service'),
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            Manager::class,
            'addHandler',
            [
                new Reference('collected_service2'),
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new NotificationHandlerCompilerPass());
    }
}
