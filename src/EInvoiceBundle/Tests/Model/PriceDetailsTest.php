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
use SolidInvoice\EInvoiceBundle\Model\PriceDetails;

#[CoversClass(PriceDetails::class)]
final class PriceDetailsTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $price = new PriceDetails(
            netPrice: BigDecimal::of('90.00'),
            discount: BigDecimal::of('10.00'),
            grossPrice: BigDecimal::of('100.00'),
            baseQuantity: BigDecimal::of('1'),
            baseQuantityUnitCode: 'C62',
        );

        self::assertTrue($price->netPrice->isEqualTo(BigDecimal::of('90.00')));
        self::assertTrue($price->discount?->isEqualTo(BigDecimal::of('10.00')));
        self::assertTrue($price->grossPrice?->isEqualTo(BigDecimal::of('100.00')));
        self::assertTrue($price->baseQuantity?->isEqualTo(BigDecimal::of('1')));
        self::assertSame('C62', $price->baseQuantityUnitCode);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $price = new PriceDetails(BigDecimal::of('90.00'));

        self::assertNull($price->discount);
        self::assertNull($price->grossPrice);
        self::assertNull($price->baseQuantity);
        self::assertNull($price->baseQuantityUnitCode);
    }

    public function testPropertiesAreReadonly(): void
    {
        $price = new PriceDetails(BigDecimal::of('90.00'));

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $price->netPrice = BigDecimal::of('80.00');
    }
}
