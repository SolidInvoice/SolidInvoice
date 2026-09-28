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
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;
use SolidInvoice\EInvoiceBundle\Model\VatBreakdown;

#[CoversClass(VatBreakdown::class)]
final class VatBreakdownTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $breakdown = new VatBreakdown(
            taxAmount: BigDecimal::of('13.50'),
            categoryCode: VatCategoryCode::StandardRate,
            taxableAmount: BigDecimal::of('90.00'),
            rate: BigDecimal::of('15'),
            exemptionReasonText: 'n/a',
            exemptionReasonCode: 'n/a',
        );

        self::assertTrue($breakdown->taxAmount->isEqualTo(BigDecimal::of('13.50')));
        self::assertSame(VatCategoryCode::StandardRate, $breakdown->categoryCode);
        self::assertTrue($breakdown->taxableAmount?->isEqualTo(BigDecimal::of('90.00')));
        self::assertTrue($breakdown->rate?->isEqualTo(BigDecimal::of('15')));
        self::assertSame('n/a', $breakdown->exemptionReasonText);
        self::assertSame('n/a', $breakdown->exemptionReasonCode);
    }

    public function testTaxableAmountIsNullForACompoundOrUnattributedRow(): void
    {
        $breakdown = new VatBreakdown(
            taxAmount: BigDecimal::of('5.00'),
            categoryCode: VatCategoryCode::StandardRate,
        );

        self::assertNull($breakdown->taxableAmount);
        self::assertNull($breakdown->rate);
        self::assertNull($breakdown->exemptionReasonText);
        self::assertNull($breakdown->exemptionReasonCode);
    }

    public function testPropertiesAreReadonly(): void
    {
        $breakdown = new VatBreakdown(BigDecimal::of('5.00'), VatCategoryCode::StandardRate);

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $breakdown->categoryCode = VatCategoryCode::ZeroRated;
    }
}
