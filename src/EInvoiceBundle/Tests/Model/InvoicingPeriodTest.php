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
use SolidInvoice\EInvoiceBundle\Model\InvoicingPeriod;

#[CoversClass(InvoicingPeriod::class)]
final class InvoicingPeriodTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $start = CarbonImmutable::parse('2026-01-01');
        $end = CarbonImmutable::parse('2026-01-31');
        $period = new InvoicingPeriod($start, $end);

        self::assertSame($start, $period->startDate);
        self::assertSame($end, $period->endDate);
    }

    public function testAllPropertiesDefaultToNull(): void
    {
        $period = new InvoicingPeriod();

        self::assertNull($period->startDate);
        self::assertNull($period->endDate);
    }

    public function testPropertiesAreReadonly(): void
    {
        $period = new InvoicingPeriod();

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $period->startDate = CarbonImmutable::parse('2026-02-01');
    }
}
