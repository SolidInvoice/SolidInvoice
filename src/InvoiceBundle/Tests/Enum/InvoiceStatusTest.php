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

namespace SolidInvoice\InvoiceBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;

final class InvoiceStatusTest extends TestCase
{
    public function testActiveIsNotAValidStatus(): void
    {
        self::assertNull(InvoiceStatus::tryFrom('active'));
    }

    public function testCasesAreExactlyTheExpectedSet(): void
    {
        $values = array_map(static fn (InvoiceStatus $case): string => $case->value, InvoiceStatus::cases());

        self::assertSame(
            ['new', 'draft', 'pending', 'paid', 'overdue', 'cancelled', 'archived'],
            $values,
        );
    }
}
