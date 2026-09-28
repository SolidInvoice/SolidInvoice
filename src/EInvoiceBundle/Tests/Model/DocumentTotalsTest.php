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

namespace SolidInvoice\EInvoiceBundle\Tests\Model;

use Brick\Math\BigDecimal;
use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Model\DocumentTotals;

#[CoversClass(DocumentTotals::class)]
final class DocumentTotalsTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $totals = new DocumentTotals(
            sumOfLineNetAmounts: BigDecimal::of('100.00'),
            totalWithoutVat: BigDecimal::of('90.00'),
            vatAmount: BigDecimal::of('13.50'),
            totalWithVat: BigDecimal::of('103.50'),
            sumOfAllowances: BigDecimal::of('10.00'),
            sumOfCharges: BigDecimal::of('0.00'),
            vatAmountInAccountingCurrency: BigDecimal::of('13.50'),
            paidAmount: BigDecimal::of('50.00'),
            roundingAmount: BigDecimal::of('0.01'),
            amountDueForPayment: BigDecimal::of('53.51'),
        );

        self::assertTrue($totals->sumOfLineNetAmounts->isEqualTo(BigDecimal::of('100.00')));
        self::assertTrue($totals->totalWithoutVat->isEqualTo(BigDecimal::of('90.00')));
        self::assertTrue($totals->vatAmount->isEqualTo(BigDecimal::of('13.50')));
        self::assertTrue($totals->totalWithVat->isEqualTo(BigDecimal::of('103.50')));
        self::assertTrue($totals->sumOfAllowances?->isEqualTo(BigDecimal::of('10.00')));
        self::assertTrue($totals->sumOfCharges?->isEqualTo(BigDecimal::of('0.00')));
        self::assertTrue($totals->vatAmountInAccountingCurrency?->isEqualTo(BigDecimal::of('13.50')));
        self::assertTrue($totals->paidAmount?->isEqualTo(BigDecimal::of('50.00')));
        self::assertTrue($totals->roundingAmount?->isEqualTo(BigDecimal::of('0.01')));
        self::assertTrue($totals->amountDueForPayment?->isEqualTo(BigDecimal::of('53.51')));
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $totals = new DocumentTotals(
            sumOfLineNetAmounts: BigDecimal::of('100.00'),
            totalWithoutVat: BigDecimal::of('90.00'),
            vatAmount: BigDecimal::of('13.50'),
            totalWithVat: BigDecimal::of('103.50'),
        );

        self::assertNull($totals->sumOfAllowances);
        self::assertNull($totals->sumOfCharges);
        self::assertNull($totals->vatAmountInAccountingCurrency);
        self::assertNull($totals->paidAmount);
        self::assertNull($totals->roundingAmount);
        self::assertNull($totals->amountDueForPayment);
    }

    public function testPropertiesAreReadonly(): void
    {
        $totals = new DocumentTotals(
            sumOfLineNetAmounts: BigDecimal::of('100.00'),
            totalWithoutVat: BigDecimal::of('90.00'),
            vatAmount: BigDecimal::of('13.50'),
            totalWithVat: BigDecimal::of('103.50'),
        );

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $totals->totalWithVat = BigDecimal::of('200.00');
    }
}
