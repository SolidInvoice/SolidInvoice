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

namespace SolidInvoice\InvoiceBundle\Tests\Entity;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;

#[CoversClass(RecurringInvoice::class)]
final class RecurringInvoiceTest extends TestCase
{
    public function testHasInvoiceForDayReturnsTrueWhenInvoiceExistsForDate(): void
    {
        $recurringInvoice = new RecurringInvoice();
        $invoice = new Invoice();
        $invoice->setInvoiceDate(CarbonImmutable::parse('2024-01-15 10:30:00'));

        $recurringInvoice->addInvoice($invoice);

        self::assertTrue($recurringInvoice->hasInvoiceForDay(CarbonImmutable::parse('2024-01-15 15:45:00')));
    }

    public function testHasInvoiceForDayReturnsFalseWhenNoInvoiceExistsForDate(): void
    {
        $recurringInvoice = new RecurringInvoice();
        $invoice = new Invoice();
        $invoice->setInvoiceDate(CarbonImmutable::parse('2024-01-15 10:30:00'));

        $recurringInvoice->addInvoice($invoice);

        self::assertFalse($recurringInvoice->hasInvoiceForDay(CarbonImmutable::parse('2024-01-16 10:30:00')));
    }

    public function testHasInvoiceForDayReturnsFalseWhenNoInvoicesExist(): void
    {
        $recurringInvoice = new RecurringInvoice();

        self::assertFalse($recurringInvoice->hasInvoiceForDay(CarbonImmutable::parse('2024-01-15 10:30:00')));
    }

    public function testHasInvoiceForDayChecksMultipleInvoices(): void
    {
        $recurringInvoice = new RecurringInvoice();

        $invoice1 = new Invoice();
        $invoice1->setInvoiceDate(CarbonImmutable::parse('2024-01-15 10:30:00'));

        $recurringInvoice->addInvoice($invoice1);

        $invoice2 = new Invoice();
        $invoice2->setInvoiceDate(CarbonImmutable::parse('2024-01-16 14:00:00'));

        $recurringInvoice->addInvoice($invoice2);

        $invoice3 = new Invoice();
        $invoice3->setInvoiceDate(CarbonImmutable::parse('2024-01-17 09:00:00'));

        $recurringInvoice->addInvoice($invoice3);

        self::assertTrue($recurringInvoice->hasInvoiceForDay(CarbonImmutable::parse('2024-01-16 23:59:59')));
        self::assertFalse($recurringInvoice->hasInvoiceForDay(CarbonImmutable::parse('2024-01-18 00:00:00')));
    }
}
