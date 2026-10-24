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
use SolidInvoice\EInvoiceBundle\Model\LineVatInformation;

#[CoversClass(LineVatInformation::class)]
final class LineVatInformationTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $vat = new LineVatInformation(VatCategoryCode::StandardRate, BigDecimal::of('15'));

        self::assertSame(VatCategoryCode::StandardRate, $vat->categoryCode);
        self::assertTrue($vat->rate?->isEqualTo(BigDecimal::of('15')));
    }

    public function testRateDefaultsToNull(): void
    {
        $vat = new LineVatInformation(VatCategoryCode::ZeroRated);

        self::assertNull($vat->rate);
    }

    public function testPropertiesAreReadonly(): void
    {
        $vat = new LineVatInformation(VatCategoryCode::ZeroRated);

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $vat->categoryCode = VatCategoryCode::StandardRate;
    }
}
