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

use InvalidArgumentException;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Calculator;

class CalculatorTest extends TestCase
{
    public function testCalculateDiscountWithInvalidEntity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"SolidInvoice\MoneyBundle\Calculator::calculateDiscount" expects instance of Quote or Invoice, "string" given.');
        $calculator = new Calculator();
        $calculator->calculateDiscount('');
    }

    public function testCalculateDiscount(): void
    {
        $calculator = new Calculator();
        $entity = new Invoice();
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(10);
        $entity->setDiscount($discount);
        $entity->setBaseTotal(new \Money\Money(20000, new Currency('USD')));

        self::assertEquals(new \Money\Money(2000, new Currency('USD')), $calculator->calculateDiscount($entity));
    }

    public function testCalculateDiscountPercentage(): void
    {
        $calculator = new Calculator();
        $entity = new Invoice();
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(35);
        $entity->setDiscount($discount);
        $entity->setBaseTotal(new \Money\Money(200, new Currency('USD')));

        self::assertEquals(new \Money\Money(3500, new Currency('USD')), $calculator->calculateDiscount($entity));
    }

    public function testCalculatePercentage(): void
    {
        $calculator = new Calculator();
        self::assertSame(0.0, $calculator->calculatePercentage(100));
        self::assertSame(24.0, $calculator->calculatePercentage(200, 12));
        self::assertSame(40.0, $calculator->calculatePercentage(new \Money\Money(200, new Currency('USD')), 20));
    }
}
