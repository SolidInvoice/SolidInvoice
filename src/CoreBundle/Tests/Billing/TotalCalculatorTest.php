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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class TotalCalculatorTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;
    use Factories;

    public function testOnlyAcceptsQuotesOrInvoices(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Invoice or Quote", "stdClass" given');
        $updater->calculateTotals(new stdClass());
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithSingleItem(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company])->object());
        $item = new Item();
        $item->setQty(1)
            ->setPrice(15000);
        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigInteger::of(15000), $invoice->getTotal());
        self::assertEquals(BigInteger::of(15000), $invoice->getBalance());
        self::assertEquals(BigInteger::of(15000), $invoice->getBaseTotal());
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithSingleItemAndMultipleQtys(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company])->object());
        $item = new Item();
        $item->setQty(2)
            ->setPrice(15000);
        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigInteger::of(30000), $invoice->getTotal());
        self::assertEquals(BigInteger::of(30000), $invoice->getBalance());
        self::assertEquals(BigInteger::of(30000), $invoice->getBaseTotal());
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithPercentageDiscount(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company])->object());
        $item = new Item();
        $item->setQty(2)
            ->setPrice(15000);
        $invoice->addItem($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(15);
        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigInteger::of(25500), $invoice->getTotal());
        self::assertEquals(BigInteger::of(25500), $invoice->getBalance());
        self::assertEquals(BigInteger::of(30000), $invoice->getBaseTotal());
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithMonetaryDiscount(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['company' => $this->company])->object());
        $item = new Item();
        $item->setQty(2)
            ->setPrice(15000);
        $invoice->addItem($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(80);
        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigInteger::of(29920), $invoice->getTotal());
        self::assertEquals(BigInteger::of(29920), $invoice->getBalance());
        self::assertEquals(BigInteger::of(30000), $invoice->getBaseTotal());
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithTaxIncl(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $tax = new Tax();
        $tax->setType(Tax::TYPE_INCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company])->object());
        $item = new Item();
        $item->setQty(2)
            ->setPrice(15000)
            ->setTax($tax);

        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigInteger::of(30000), $invoice->getTotal());
        self::assertEquals(BigInteger::of(30000), $invoice->getBalance());
        self::assertEquals(BigInteger::of(25000), $invoice->getBaseTotal());
        self::assertEquals(BigInteger::of(5000), $invoice->getTax());
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithTaxExcl(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $tax = new Tax();
        $tax->setType(Tax::TYPE_EXCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company])->object());
        $item = new Item();
        $item->setQty(2)
            ->setPrice(15000)
            ->setTax($tax);

        $invoice->addItem($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigInteger::of(36000), $invoice->getTotal());
        self::assertEquals(BigInteger::of(36000), $invoice->getBalance());
        self::assertEquals(BigInteger::of(30000), $invoice->getBaseTotal());
        self::assertEquals(BigInteger::of(6000), $invoice->getTax());
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithTaxInclAndPercentageDiscount(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $tax = new Tax();
        $tax->setType(Tax::TYPE_INCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company])->object());
        $item = new Item();
        $item->setQty(2)
            ->setPrice(15000)
            ->setTax($tax);
        $invoice->addItem($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(15);
        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigInteger::of(25500), $invoice->getTotal());
        self::assertEquals(BigInteger::of(25500), $invoice->getBalance());
        self::assertEquals(BigInteger::of(25000), $invoice->getBaseTotal());
        self::assertEquals(BigInteger::of(5000), $invoice->getTax());
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithTaxExclAndMonetaryDiscount(): void
    {
        $updater = new TotalCalculator($this->em->getRepository(Payment::class));

        $tax = new Tax();
        $tax->setType(Tax::TYPE_EXCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company])->object());
        $item = new Item();
        $item->setQty(2)
            ->setPrice(15000)
            ->setTax($tax);
        $invoice->addItem($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(80);
        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigInteger::of(35920), $invoice->getTotal());
        self::assertEquals(BigInteger::of(35920), $invoice->getBalance());
        self::assertEquals(BigInteger::of(30000), $invoice->getBaseTotal());
        self::assertEquals(BigInteger::of(6000), $invoice->getTax());
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateTotalsWithPayments(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company])->object());
        $invoice->setTotal(30000);
        $invoice->setBaseTotal(30000);
        $invoice->setBalance(30000);
        $invoice->setStatus(Graph::STATUS_PENDING);
        $item = new Item();
        $item->setQty(2)
            ->setPrice(15000)
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

        self::assertEquals(BigInteger::of(30000), $invoice->getTotal());
        self::assertEquals(BigInteger::of(29000), $invoice->getBalance());
        self::assertEquals(BigInteger::of(30000), $invoice->getBaseTotal());
    }
}
