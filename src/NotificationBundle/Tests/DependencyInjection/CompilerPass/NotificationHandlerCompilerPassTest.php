<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Tests\DependencyInjection\CompilerPass;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use SolidInvoice\NotificationBundle\DependencyInjection\CompilerPass\NotificationHandlerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class NotificationHandlerCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testProcessWithNoSender()
    {
        $this->compile();

        $this->assertContainerBuilderNotHasService('notification.sender');
    }

    public function testProcessWithHandlers()
    {
        $collectingService = new Definition();
        $this->setDefinition('notification.sender', $collectingService);

        $collectedService = new Definition();
        $collectedService->addTag('notification.handler');
        $this->setDefinition('collected_service', $collectedService);

        $collectedService2 = new Definition();
        $collectedService2->addTag('notification.handler');
        $this->setDefinition('collected_service2', $collectedService2);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'notification.sender',
            'addHandler',
            [
                new Reference('collected_service'),
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'notification.sender',
            'addHandler',
            [
                new Reference('collected_service2'),
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new NotificationHandlerCompilerPass());
    }
}
