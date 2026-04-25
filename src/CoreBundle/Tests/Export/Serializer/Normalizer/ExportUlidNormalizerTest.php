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

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Export\Serializer\Normalizer\ExportUlidNormalizer;
use Symfony\Component\Uid\Ulid;

/** @covers \SolidInvoice\CoreBundle\Export\Serializer\Normalizer\ExportUlidNormalizer */
final class ExportUlidNormalizerTest extends TestCase
{
    public function testNormalizesUlidToBase58(): void
    {
        $ulid = new Ulid();
        $normalizer = new ExportUlidNormalizer();

        self::assertSame($ulid->toBase58(), $normalizer->normalize($ulid));
    }

    public function testSupportsUlidOnly(): void
    {
        $normalizer = new ExportUlidNormalizer();

        self::assertTrue($normalizer->supportsNormalization(new Ulid()));
        self::assertFalse($normalizer->supportsNormalization('string'));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
