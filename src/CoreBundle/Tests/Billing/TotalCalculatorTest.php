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

namespace SolidInvoice\CoreBundle\Tests\Billing;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Exception\UnexpectedTypeException;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Model\Status;
use SolidInvoice\TaxBundle\Entity\Tax;
use stdClass;

class TotalCalculatorTest extends TestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        \SolidInvoice\MoneyBundle\Entity\Money::setBaseCurrency('USD');
    }

    public function testOnlyAcceptsQuotesOrInvoices()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Invoice or Quote", "stdClass" given');
        $updater->calculateTotals(new stdClass());
    }

    public function testUpdateWithSingleItem()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(1)
            ->setPrice(new Money(15000, new Currency('USD')));
        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(15000, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(15000, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(15000, new Currency('USD')), $invoice->getBaseTotal());
    }

    public function testUpdateWithSingleItemAndMultipleQtys()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')));
        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
    }

    public function testUpdateWithPercentageDiscount()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')));
        $invoice->addItem($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(15);
        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(25500, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(25500, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
    }

    public function testUpdateWithMonetaryDiscount()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')));
        $invoice->addItem($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(80);
        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(22000, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(22000, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
    }

    public function testUpdateWithTaxIncl()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $tax = new Tax();
        $tax->setType(Tax::TYPE_INCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')))
            ->setTax($tax);

        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(25000, new Currency('USD')), $invoice->getBaseTotal());
        self::assertEquals(new Money(5000, new Currency('USD')), $invoice->getTax());
    }

    public function testUpdateWithTaxExcl()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $tax = new Tax();
        $tax->setType(Tax::TYPE_EXCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')))
            ->setTax($tax);

        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(36000, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(36000, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
        self::assertEquals(new Money(6000, new Currency('USD')), $invoice->getTax());
    }

    public function testUpdateWithTaxInclAndPercentageDiscount()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $tax = new Tax();
        $tax->setType(Tax::TYPE_INCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')))
            ->setTax($tax);
        $invoice->addItem($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(15);
        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(25500, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(25500, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(25000, new Currency('USD')), $invoice->getBaseTotal());
        self::assertEquals(new Money(5000, new Currency('USD')), $invoice->getTax());
    }

    public function testUpdateWithTaxExclAndMonetaryDiscount()
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $tax = new Tax();
        $tax->setType(Tax::TYPE_EXCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')))
            ->setTax($tax);
        $invoice->addItem($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(80);
        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(28000, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(28000, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
        self::assertEquals(new Money(6000, new Currency('USD')), $invoice->getTax());
    }

    public function testUpdateTotalsWithPayments()
    {
        $invoice = new Invoice();
        $invoice->setTotal(new Money(30000, new Currency('USD')));
        $invoice->setBaseTotal(new Money(30000, new Currency('USD')));
        $invoice->setBalance(new Money(30000, new Currency('USD')));
        $invoice->setStatus(Graph::STATUS_PENDING);
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')))
            ->setDescription('foobar');
        $invoice->addItem($item);

        $payment = new Payment();
        $payment->setTotalAmount(1000);
        $payment->setStatus(Status::STATUS_CAPTURED);

        $invoice->addPayment($payment);
        $this->em->persist($invoice);
        $this->em->flush();

        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $updater->calculateTotals($invoice);

        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getTotal());
        self::assertEquals(new Money(29000, new Currency('USD')), $invoice->getBalance());
        self::assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
    }
}
