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

namespace SolidInvoice\MoneyBundle\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Calculator;

class CalculatorTest extends TestCase
{
    /**
     * @throws MathException
     */
    public function testCalculateDiscount(): void
    {
        $calculator = new Calculator();
        $entity = new Invoice();
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(10);
        $entity->setDiscount($discount);
        $entity->setBaseTotal(20000);

        self::assertEquals(BigDecimal::of(2000), $calculator->calculateDiscount($entity));
    }

    /**
     * @throws MathException
     */
    public function testCalculateDiscountPercentage(): void
    {
        $calculator = new Calculator();
        $entity = new Invoice();
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(35);
        $entity->setDiscount($discount);
        $entity->setBaseTotal(200);

        self::assertEquals(BigInteger::of(35), $calculator->calculateDiscount($entity));
    }

    /**
     * @throws MathException
     */
    public function testCalculatePercentage(): void
    {
        $calculator = new Calculator();
        self::assertSame(0.0, $calculator->calculatePercentage(100));
        self::assertSame(24.0, $calculator->calculatePercentage(200, 12));
        self::assertSame(40.0, $calculator->calculatePercentage(200, 20));
    }
}
