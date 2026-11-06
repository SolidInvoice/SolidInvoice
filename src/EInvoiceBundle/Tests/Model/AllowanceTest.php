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
use SolidInvoice\EInvoiceBundle\Model\Allowance;

#[CoversClass(Allowance::class)]
final class AllowanceTest extends TestCase
{
    public function testConstructionOfADocumentLevelAllowance(): void
    {
        $allowance = new Allowance(
            amount: BigDecimal::of('10.00'),
            baseAmount: BigDecimal::of('100.00'),
            percentage: BigDecimal::of('10'),
            vatCategoryCode: VatCategoryCode::StandardRate,
            vatRate: BigDecimal::of('15'),
            reason: 'Loyalty discount',
            reasonCode: '95',
        );

        self::assertTrue($allowance->amount->isEqualTo(BigDecimal::of('10.00')));
        self::assertTrue($allowance->baseAmount?->isEqualTo(BigDecimal::of('100.00')));
        self::assertTrue($allowance->percentage?->isEqualTo(BigDecimal::of('10')));
        self::assertSame(VatCategoryCode::StandardRate, $allowance->vatCategoryCode);
        self::assertTrue($allowance->vatRate?->isEqualTo(BigDecimal::of('15')));
        self::assertSame('Loyalty discount', $allowance->reason);
        self::assertSame('95', $allowance->reasonCode);
    }

    public function testALineLevelAllowanceCarriesNoVatCategoryOrRate(): void
    {
        $allowance = new Allowance(amount: BigDecimal::of('5.00'));

        self::assertNull($allowance->vatCategoryCode);
        self::assertNull($allowance->vatRate);
        self::assertNull($allowance->baseAmount);
        self::assertNull($allowance->percentage);
        self::assertNull($allowance->reason);
        self::assertNull($allowance->reasonCode);
    }

    public function testPropertiesAreReadonly(): void
    {
        $allowance = new Allowance(amount: BigDecimal::of('5.00'));

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $allowance->amount = BigDecimal::of('6.00');
    }
}
