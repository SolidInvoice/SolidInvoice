<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MoneyBundle\Tests;

use Money\Currency;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\MoneyBundle\Entity\Money;

class CalculatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Money::setBaseCurrency('USD');
    }

    public function testCalculateDiscountWithInvalidEntity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"SolidInvoice\MoneyBundle\Calculator::calculateDiscount" expects instance of Quote or Invoice, "string" given.');
        $calculator = new Calculator();
        $calculator->calculateDiscount('');
    }

    public function testCalculateDiscount()
    {
        $calculator = new Calculator();
        $entity = new Invoice();
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(10);
        $entity->setDiscount($discount);
        $entity->setBaseTotal(new \Money\Money(20000, new Currency('USD')));

        $this->assertEquals(new \Money\Money(2000, new Currency('USD')), $calculator->calculateDiscount($entity));
    }

    public function testCalculateDiscountPercentage()
    {
        $calculator = new Calculator();
        $entity = new Invoice();
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(35);
        $entity->setDiscount($discount);
        $entity->setBaseTotal(new \Money\Money(200, new Currency('USD')));

        $this->assertEquals(new \Money\Money(3500, new Currency('USD')), $calculator->calculateDiscount($entity));
    }

    public function testCalculatePercentage()
    {
        $calculator = new Calculator();
        $this->assertSame(0.0, $calculator->calculatePercentage(100));
        $this->assertSame(24.0, $calculator->calculatePercentage(200, 12));
        $this->assertSame(40.0, $calculator->calculatePercentage(new \Money\Money(200, new Currency('USD')), 20));
    }
}
