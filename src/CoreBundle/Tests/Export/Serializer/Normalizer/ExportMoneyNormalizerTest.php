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

namespace SolidInvoice\CoreBundle\Tests\Export\Serializer\Normalizer;

use Brick\Math\BigInteger;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Export\Serializer\Normalizer\ExportMoneyNormalizer;
use stdClass;

#[CoversClass(ExportMoneyNormalizer::class)]
final class ExportMoneyNormalizerTest extends TestCase
{
    private ExportMoneyNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ExportMoneyNormalizer();
    }

    public function testNormalizesMoneyObjectToAmountAndCurrency(): void
    {
        $money = new Money(12345, new Currency('USD'));

        self::assertSame(
            ['amount' => '123.45', 'currency' => 'USD'],
            $this->normalizer->normalize($money),
        );
    }

    public function testZeroDecimalCurrencySerializesAsWholeNumber(): void
    {
        $money = new Money(1500, new Currency('JPY'));

        self::assertSame(
            ['amount' => '1500', 'currency' => 'JPY'],
            $this->normalizer->normalize($money),
        );
    }

    public function testNormalizesBigNumberToDecimalString(): void
    {
        $result = $this->normalizer->normalize(BigInteger::of(5000));

        self::assertSame('50.00', $result);
    }

    public function testSupportsMoneyAndBigNumberOnly(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(new Money(1, new Currency('USD'))));
        self::assertTrue($this->normalizer->supportsNormalization(BigInteger::of(1)));
        self::assertFalse($this->normalizer->supportsNormalization('string'));
        self::assertFalse($this->normalizer->supportsNormalization(new stdClass()));
    }
}
