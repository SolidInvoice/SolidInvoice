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

namespace SolidInvoice\EInvoiceBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceStatus;

#[CoversClass(EInvoiceStatus::class)]
final class EInvoiceStatusTest extends TestCase
{
    public function testIsTerminalForAllSevenCases(): void
    {
        $expected = [
            EInvoiceStatus::Pending->value => false,
            EInvoiceStatus::Queued->value => false,
            EInvoiceStatus::Transmitted->value => false,
            EInvoiceStatus::Accepted->value => true,
            EInvoiceStatus::Rejected->value => true,
            EInvoiceStatus::Cancelled->value => true,
            EInvoiceStatus::Failed->value => true,
        ];

        $actual = [];

        foreach (EInvoiceStatus::cases() as $case) {
            $actual[$case->value] = $case->isTerminal();
        }

        self::assertSame($expected, $actual);
    }

    public function testGetLabelIsTotalOverTheEnum(): void
    {
        foreach (EInvoiceStatus::cases() as $case) {
            self::assertNotSame('', $case->getLabel());
        }
    }

    public function testGetColorIsTotalOverTheEnum(): void
    {
        foreach (EInvoiceStatus::cases() as $case) {
            self::assertNotSame('', $case->getColor());
        }
    }
}
