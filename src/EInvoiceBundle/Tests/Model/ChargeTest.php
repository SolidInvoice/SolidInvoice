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
use SolidInvoice\EInvoiceBundle\Model\Charge;

#[CoversClass(Charge::class)]
final class ChargeTest extends TestCase
{
    public function testConstructionOfADocumentLevelCharge(): void
    {
        $charge = new Charge(
            amount: BigDecimal::of('10.00'),
            baseAmount: BigDecimal::of('100.00'),
            percentage: BigDecimal::of('10'),
            vatCategoryCode: VatCategoryCode::StandardRate,
            vatRate: BigDecimal::of('15'),
            reason: 'Freight',
            reasonCode: 'FC',
        );

        self::assertTrue($charge->amount->isEqualTo(BigDecimal::of('10.00')));
        self::assertTrue($charge->baseAmount?->isEqualTo(BigDecimal::of('100.00')));
        self::assertTrue($charge->percentage?->isEqualTo(BigDecimal::of('10')));
        self::assertSame(VatCategoryCode::StandardRate, $charge->vatCategoryCode);
        self::assertTrue($charge->vatRate?->isEqualTo(BigDecimal::of('15')));
        self::assertSame('Freight', $charge->reason);
        self::assertSame('FC', $charge->reasonCode);
    }

    public function testALineLevelChargeCarriesNoVatCategoryOrRate(): void
    {
        $charge = new Charge(amount: BigDecimal::of('5.00'));

        self::assertNull($charge->vatCategoryCode);
        self::assertNull($charge->vatRate);
        self::assertNull($charge->baseAmount);
        self::assertNull($charge->percentage);
        self::assertNull($charge->reason);
        self::assertNull($charge->reasonCode);
    }

    public function testPropertiesAreReadonly(): void
    {
        $charge = new Charge(amount: BigDecimal::of('5.00'));

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $charge->amount = BigDecimal::of('6.00');
    }
}
