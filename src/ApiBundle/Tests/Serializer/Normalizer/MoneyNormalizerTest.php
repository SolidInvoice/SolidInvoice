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

namespace SolidInvoice\ApiBundle\Tests\Serializer\Normalizer;

use Mockery as M;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\MoneyNormalizer;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private MoneyNormalizer $normalizer;

    protected function setUp(): void
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency('USD'));

        $this->normalizer = new MoneyNormalizer(new MoneyFormatter('en', $systemConfig), $systemConfig);
    }

    public function testSupportsNormalization(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(new Money(100, new Currency('USD'))));
        self::assertFalse($this->normalizer->supportsNormalization(Money::class));
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(null, Money::class));
        self::assertFalse($this->normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    public function testNormalization(): void
    {
        $money = new Money(10000, new Currency('USD'));

        self::assertSame('$100.00', $this->normalizer->normalize($money));
    }

    public function testDenormalization(): void
    {
        $money = new Money(10000, new Currency('USD'));

        self::assertEquals($money, $this->normalizer->denormalize(100, Money::class));
    }
}
