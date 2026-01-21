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

namespace SolidInvoice\InvoiceBundle\Tests\Notification;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Notification\InvoiceReminderStoppedNotification;

/** @covers \SolidInvoice\InvoiceBundle\Notification\InvoiceReminderStoppedNotification */
final class InvoiceReminderStoppedNotificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetSubjectIncludesInvoiceId(): void
    {
        $invoice = $this->createInvoice('INV-123');

        $notification = new InvoiceReminderStoppedNotification([
            'invoice' => $invoice,
            'days_overdue' => 35,
        ]);

        $subject = $notification->getSubject();

        self::assertSame('Final Reminder Sent for Invoice INV-123 - Manual Follow-up Required', $subject);
    }

    public function testGetParametersIncludesInvoiceAndDaysOverdue(): void
    {
        $invoice = $this->createInvoice('INV-999');

        $notification = new InvoiceReminderStoppedNotification([
            'invoice' => $invoice,
            'days_overdue' => 45,
        ]);

        $parameters = $notification->getParameters();

        self::assertArrayHasKey('invoice', $parameters);
        self::assertSame($invoice, $parameters['invoice']);
        self::assertArrayHasKey('days_overdue', $parameters);
        self::assertSame(45, $parameters['days_overdue']);
    }

    private function createInvoice(string $invoiceId): Invoice
    {
        $client = M::mock(Client::class);

        $invoice = M::mock(Invoice::class);
        $invoice->shouldReceive('getInvoiceId')->andReturn($invoiceId);
        $invoice->shouldReceive('getClient')->andReturn($client);

        return $invoice;
    }
}
