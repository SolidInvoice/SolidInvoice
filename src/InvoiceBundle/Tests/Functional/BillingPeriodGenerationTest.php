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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Clock\ClockInterface;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Recurring\BillingPeriodCalculator;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A functional pass over the full generation path: a real {@see RecurringInvoiceFactory}
 * fixture, wired through the real {@see InvoiceManager} and {@see BillingPeriodCalculator}
 * services, proves the derived period survives the container wiring, not just the unit
 * boundary covered by {@see \SolidInvoice\InvoiceBundle\Tests\Manager\InvoiceManagerTest}.
 */
#[CoversClass(InvoiceManager::class)]
#[CoversClass(BillingPeriodCalculator::class)]
final class BillingPeriodGenerationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testGeneratedInvoiceStatesThePreviousCalendarMonth(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);

        $recurringInvoice = RecurringInvoiceFactory::new()
            ->monthlyBilledInArrears()
            ->create([
                'client' => $client,
                'company' => $this->company,
            ]);

        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);
        self::assertInstanceOf(ClockInterface::class, $clock);
        $now = CarbonImmutable::instance($clock->now());

        $invoiceManager = $container->get(InvoiceManager::class);
        self::assertInstanceOf(InvoiceManager::class, $invoiceManager);

        $invoice = $invoiceManager->createFromRecurring($recurringInvoice);

        $expectedStart = $now->subMonthNoOverflow()->startOfMonth()->startOfDay();
        $expectedEnd = $now->subMonthNoOverflow()->endOfMonth()->startOfDay();

        self::assertSame($expectedStart->format('Y-m-d'), $invoice->getPeriod()->getStart()?->format('Y-m-d'));
        self::assertSame($expectedEnd->format('Y-m-d'), $invoice->getPeriod()->getEnd()?->format('Y-m-d'));
    }
}
