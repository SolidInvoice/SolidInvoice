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

use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\ValidationStage;

final class ValidationStageTest extends TestCase
{
    public function testOrderMatchesTheDocumentedSequence(): void
    {
        $expected = [
            ValidationStage::Schema->value => 0,
            ValidationStage::Syntax->value => 1,
            ValidationStage::Business->value => 2,
            ValidationStage::Archival->value => 3,
        ];

        $actual = [];

        foreach (ValidationStage::cases() as $case) {
            $actual[$case->value] = $case->order();
        }

        self::assertSame($expected, $actual);
    }
}
