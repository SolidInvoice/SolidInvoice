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

namespace SolidInvoice\SaasBundle\Tests\Feature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SaasBundle\Feature\FeatureCopy;
use SolidInvoice\SaasBundle\Feature\FeatureCopyRegistry;

#[CoversClass(FeatureCopyRegistry::class)]
#[CoversClass(FeatureCopy::class)]
final class FeatureCopyRegistryTest extends TestCase
{
    /**
     * Every Feature enum case must resolve to copy. If a new feature is added
     * without copy, this test fails the build before the gated page can ship
     * with the generic "This feature requires an upgrade" fallback.
     */
    #[DataProvider('provideFeatures')]
    public function testEveryFeatureHasCopy(Feature $feature): void
    {
        $copy = new FeatureCopyRegistry()->get($feature->value);

        self::assertInstanceOf(FeatureCopy::class, $copy);
        self::assertNotSame('', $copy->icon, 'Icon missing for ' . $feature->value);
        self::assertNotSame('', $copy->headline, 'Headline missing for ' . $feature->value);
        self::assertNotSame('', $copy->description, 'Description missing for ' . $feature->value);
        self::assertNotEmpty($copy->bullets, 'Bullets missing for ' . $feature->value);
        self::assertLessThanOrEqual(5, count($copy->bullets), 'Too many bullets for ' . $feature->value);
    }

    public function testUnknownFeatureReturnsNull(): void
    {
        self::assertNull(new FeatureCopyRegistry()->get('not_a_feature'));
    }

    /**
     * @return iterable<string, array{Feature}>
     */
    public static function provideFeatures(): iterable
    {
        foreach (Feature::cases() as $case) {
            yield $case->value => [$case];
        }
    }
}
