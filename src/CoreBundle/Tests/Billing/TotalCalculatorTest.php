<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Tests\Billing;

use CSBill\CoreBundle\Billing\TotalCalculator;
use CSBill\CoreBundle\Entity\Discount;
use CSBill\CoreBundle\Exception\UnexpectedTypeException;
use CSBill\CoreBundle\Test\Traits\DoctrineTestTrait;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Entity\Item;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Entity\Payment;
use CSBill\PaymentBundle\Model\Status;
use CSBill\TaxBundle\Entity\Tax;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class TotalCalculatorTest extends TestCase
{
    use DoctrineTestTrait,
        MockeryPHPUnitIntegration;

    protected function setUp()
    {
        parent::setUp();
        \CSBill\MoneyBundle\Entity\Money::setBaseCurrency('USD');
    }

    public function testOnlyAcceptsQuotesOrInvoices()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Invoice or Quote", "stdClass" given');
        $updater->calculateTotals(new \stdClass());
    }

    public function testUpdateWithSingleItem()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(1)
            ->setPrice(new Money(15000, new Currency('USD')));
        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        $this->assertEquals(new Money(15000, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(15000, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(15000, new Currency('USD')), $invoice->getBaseTotal());
    }

    public function testUpdateWithSingleItemAndMultipleQtys()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

        $invoice = new Invoice();
        $invoice->setTotal(new Money(0, new Currency('USD')));
        $item = new Item();
        $item->setQty(2)
            ->setPrice(new Money(15000, new Currency('USD')));
        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
    }

    public function testUpdateWithPercentageDiscount()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

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

        $this->assertEquals(new Money(25500, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(25500, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
    }

    public function testUpdateWithMonetaryDiscount()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

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

        $this->assertEquals(new Money(22000, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(22000, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
    }

    public function testUpdateWithTaxIncl()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

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

        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(25000, new Currency('USD')), $invoice->getBaseTotal());
        $this->assertEquals(new Money(5000, new Currency('USD')), $invoice->getTax());
    }

    public function testUpdateWithTaxExcl()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

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

        $this->assertEquals(new Money(36000, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(36000, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
        $this->assertEquals(new Money(6000, new Currency('USD')), $invoice->getTax());
    }

    public function testUpdateWithTaxInclAndPercentageDiscount()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

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

        $this->assertEquals(new Money(25500, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(25500, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(25000, new Currency('USD')), $invoice->getBaseTotal());
        $this->assertEquals(new Money(5000, new Currency('USD')), $invoice->getTax());
    }

    public function testUpdateWithTaxExclAndMonetaryDiscount()
    {
        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

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

        $this->assertEquals(new Money(28000, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(28000, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
        $this->assertEquals(new Money(6000, new Currency('USD')), $invoice->getTax());
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

        $updater = new TotalCalculator($this->em->getRepository('CSBillPaymentBundle:Payment'));

        $updater->calculateTotals($invoice);

        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getTotal());
        $this->assertEquals(new Money(29000, new Currency('USD')), $invoice->getBalance());
        $this->assertEquals(new Money(30000, new Currency('USD')), $invoice->getBaseTotal());
    }

    protected function getEntityNamespaces()
    {
        return [
            'CSBillInvoiceBundle' => 'CSBill\\InvoiceBundle\\Entity',
            'CSBillPaymentBundle' => 'CSBill\\PaymentBundle\\Entity',
        ];
    }

    protected function getEntities()
    {
        return [
            'CSBillInvoiceBundle:Invoice',
            'CSBillInvoiceBundle:Item',
            'CSBillPaymentBundle:Payment',
        ];
    }
}
