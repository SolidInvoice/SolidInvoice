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

namespace SolidInvoice\InvoiceBundle\Tests\Twig\Extension;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\BillingPeriod;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Twig\Extension\BillingPeriodExtension;

#[CoversClass(BillingPeriodExtension::class)]
final class BillingPeriodExtensionTest extends TestCase
{
    private BillingPeriodExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new BillingPeriodExtension();
    }

    public function testEmptyPeriodRendersAsEmptyString(): void
    {
        $invoice = new Invoice();

        self::assertSame('', $this->extension->formatBillingPeriod($invoice));
    }

    public function testStartOnlyPeriodRendersAsEmptyString(): void
    {
        $invoice = new Invoice()->setPeriod(
            BillingPeriod::of(CarbonImmutable::parse('2026-02-01'), null)
        );

        self::assertSame('', $this->extension->formatBillingPeriod($invoice));
    }

    public function testSameMonthAndYearCollapsesToASingleMonthAndYear(): void
    {
        $invoice = new Invoice()->setPeriod(
            BillingPeriod::of(CarbonImmutable::parse('2026-02-01'), CarbonImmutable::parse('2026-02-28'))
        );

        self::assertSame('1–28 February 2026', $this->extension->formatBillingPeriod($invoice));
    }

    public function testSameYearDifferentMonthCollapsesToASingleYear(): void
    {
        $invoice = new Invoice()->setPeriod(
            BillingPeriod::of(CarbonImmutable::parse('2026-01-15'), CarbonImmutable::parse('2026-02-28'))
        );

        self::assertSame('15 January – 28 February 2026', $this->extension->formatBillingPeriod($invoice));
    }

    public function testDifferentYearsRenderBothDatesInFull(): void
    {
        $invoice = new Invoice()->setPeriod(
            BillingPeriod::of(CarbonImmutable::parse('2025-12-15'), CarbonImmutable::parse('2026-02-28'))
        );

        self::assertSame('15 December 2025 – 28 February 2026', $this->extension->formatBillingPeriod($invoice));
    }

    public function testSingleDayPeriodRendersAsOneDate(): void
    {
        $invoice = new Invoice()->setPeriod(
            BillingPeriod::of(CarbonImmutable::parse('2026-02-01'), CarbonImmutable::parse('2026-02-01'))
        );

        self::assertSame('1 February 2026', $this->extension->formatBillingPeriod($invoice));
    }
}
