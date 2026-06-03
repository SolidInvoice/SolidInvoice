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

namespace SolidInvoice\CoreBundle\Tests\Telemetry\Listener;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Telemetry\Listener\InvoiceCreatedTelemetryListener;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Tests\Telemetry\CollectingMessageBus;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(InvoiceCreatedTelemetryListener::class)]
final class InvoiceCreatedTelemetryListenerTest extends TestCase
{
    public function testItDispatchesInvoiceCreatedEventOnPostCreate(): void
    {
        $bus = new CollectingMessageBus();

        $telemetry = new Telemetry(
            $bus,
            $this->createConfigWriter(),
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            'build-123',
            true,
            'manual',
            false,
            'en',
            null,
        );

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            InvoiceEvents::INVOICE_POST_CREATE,
            new InvoiceCreatedTelemetryListener($telemetry),
        );

        $eventDispatcher->dispatch(new InvoiceEvent(), InvoiceEvents::INVOICE_POST_CREATE);

        self::assertCount(1, $bus->messages);
        self::assertSame('event', $bus->messages[0]->type);
        self::assertSame('invoice_created', $bus->messages[0]->payload['event']);
        self::assertSame('solidinvoice', $bus->messages[0]->payload['app']);
        self::assertSame('build-123', $bus->messages[0]->payload['build_id']);
    }

    private function createConfigWriter(): ConfigWriter
    {
        $vault = $this->createMock(AbstractVault::class);
        $vault->method('generateKeys')->willReturn(true);

        return new ConfigWriter($vault, '/tmp/solidinvoice-test-config');
    }
}
