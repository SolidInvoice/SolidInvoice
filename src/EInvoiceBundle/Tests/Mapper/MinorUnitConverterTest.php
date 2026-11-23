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

namespace SolidInvoice\EInvoiceBundle\Tests\Mapper;

use Brick\Math\BigDecimal;
use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Mapper\MinorUnitConverter;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;

#[CoversClass(MinorUnitConverter::class)]
final class MinorUnitConverterTest extends TestCase
{
    private MinorUnitConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new MinorUnitConverter(new CurrencyScale());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function exactnessProvider(): iterable
    {
        yield '2-decimal currency (EUR)' => ['EUR', '1234', '12.34'];
        yield '0-decimal currency (JPY)' => ['JPY', '1234', '1234'];
        yield '3-decimal currency (BHD)' => ['BHD', '1234', '1.234'];
    }

    #[DataProvider('exactnessProvider')]
    public function testToMajorUnitIsExact(string $currencyCode, string $minorUnits, string $expected): void
    {
        $result = $this->converter->toMajorUnit(BigDecimal::of($minorUnits), new Currency($currencyCode));

        self::assertTrue($result->isEqualTo(BigDecimal::of($expected)));
        self::assertSame($expected, (string) $result);
    }

    public function testExcessPrecisionIsKeptRatherThanRounded(): void
    {
        $result = $this->converter->toMajorUnit(BigDecimal::of('1234.5'), new Currency('EUR'));

        self::assertSame('12.345', (string) $result);
    }
}
