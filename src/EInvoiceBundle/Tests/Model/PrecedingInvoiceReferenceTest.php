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

use Carbon\CarbonImmutable;
use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Model\PrecedingInvoiceReference;

#[CoversClass(PrecedingInvoiceReference::class)]
final class PrecedingInvoiceReferenceTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $issueDate = CarbonImmutable::parse('2026-01-15');
        $reference = new PrecedingInvoiceReference('INV-0001', $issueDate);

        self::assertSame('INV-0001', $reference->reference);
        self::assertSame($issueDate, $reference->issueDate);
    }

    public function testIssueDateDefaultsToNull(): void
    {
        $reference = new PrecedingInvoiceReference('INV-0001');

        self::assertNull($reference->issueDate);
    }

    public function testPropertiesAreReadonly(): void
    {
        $reference = new PrecedingInvoiceReference('INV-0001');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $reference->reference = 'INV-0002';
    }
}
