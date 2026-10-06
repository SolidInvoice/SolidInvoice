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

namespace SolidInvoice\QuoteBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\QuoteBundle\Entity\Line;

#[CoversClass(Line::class)]
final class LineTest extends TestCase
{
    public function testUpdateTotalKeepsTheSameInstanceWhenTheNumberIsUnchanged(): void
    {
        $line = new Line();
        $line->setPrice(1000);
        $line->setQty(2);
        $line->updateTotal();

        $total = $line->getTotal();
        $line->updateTotal();

        self::assertSame($total, $line->getTotal());
    }

    public function testUpdateTotalReassignsWhenThePriceChanges(): void
    {
        $line = new Line();
        $line->setPrice(1000);
        $line->setQty(2);
        $line->updateTotal();

        $total = $line->getTotal();
        $line->setPrice(1500);
        $line->updateTotal();

        self::assertNotSame($total, $line->getTotal());
        self::assertTrue($line->getTotal()->isEqualTo(3000));
    }

    public function testUpdateTotalReassignsWhenTheQtyChanges(): void
    {
        $line = new Line();
        $line->setPrice(1000);
        $line->setQty(2);
        $line->updateTotal();

        $total = $line->getTotal();
        $line->setQty(3);
        $line->updateTotal();

        self::assertNotSame($total, $line->getTotal());
        self::assertTrue($line->getTotal()->isEqualTo(3000));
    }
}
