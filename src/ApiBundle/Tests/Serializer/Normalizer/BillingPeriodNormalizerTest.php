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

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\BillingPeriodNormalizer;
use SolidInvoice\CoreBundle\Entity\BillingPeriod;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class BillingPeriodNormalizerTest extends TestCase
{
    private BillingPeriodNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BillingPeriodNormalizer();
    }

    public function testSupportsNormalization(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(new BillingPeriod()));
        self::assertFalse($this->normalizer->supportsNormalization(BillingPeriod::class));
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(null, BillingPeriod::class));
        self::assertFalse($this->normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    public function testNormalizationWithPeriod(): void
    {
        $period = BillingPeriod::of(CarbonImmutable::parse('2026-02-01'), CarbonImmutable::parse('2026-02-28'));

        self::assertSame(
            ['start' => $period->getStart()->format(DateTimeImmutable::ATOM), 'end' => $period->getEnd()->format(DateTimeImmutable::ATOM)],
            $this->normalizer->normalize($period),
        );
    }

    public function testNormalizationWithEmptyPeriod(): void
    {
        self::assertSame(['start' => null, 'end' => null], $this->normalizer->normalize(new BillingPeriod()));
    }

    public function testDenormalization(): void
    {
        $period = $this->normalizer->denormalize(['start' => '2026-02-01', 'end' => '2026-02-28'], BillingPeriod::class);

        self::assertSame('2026-02-01', $period->getStart()?->format('Y-m-d'));
        self::assertSame('2026-02-28', $period->getEnd()?->format('Y-m-d'));
    }

    public function testDenormalizationWithNullValues(): void
    {
        $period = $this->normalizer->denormalize(['start' => null, 'end' => null], BillingPeriod::class);

        self::assertTrue($period->isEmpty());
    }

    public function testDenormalizationWithMissingKeys(): void
    {
        $period = $this->normalizer->denormalize([], BillingPeriod::class);

        self::assertTrue($period->isEmpty());
    }

    public function testDenormalizationWithMalformedDateThrows(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->normalizer->denormalize(['start' => 'not-a-date', 'end' => '2026-02-28'], BillingPeriod::class);
    }

    public function testDenormalizationWithNonArrayDataThrows(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->normalizer->denormalize('2026-02-01', BillingPeriod::class);
    }
}
