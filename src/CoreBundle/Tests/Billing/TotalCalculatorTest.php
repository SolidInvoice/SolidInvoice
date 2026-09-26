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

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\Exception\NotSupported;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\CreditNoteLine;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\TaxBundle\Calculator\InvoiceTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\LineTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\TaxCalculator;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TotalCalculatorTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    private function createUpdater(): TotalCalculator
    {
        return new TotalCalculator(
            $this->em->getRepository(Payment::class),
            new Calculator(),
            new TaxCalculator(new LineTaxCalculator(), new InvoiceTaxCalculator()),
            $this->em->getRepository(CreditNote::class),
        );
    }

    /**
     * A line, not a direct {@see Invoice::setTotal()} call: {@see \SolidInvoice\InvoiceBundle\Listener\Doctrine\InvoiceSaveListener}
     * runs {@see TotalCalculator::calculateTotals()} on every persist/update of a
     * {@see \SolidInvoice\InvoiceBundle\Entity\BaseInvoice}, which recomputes `total` from the
     * line collection and would silently zero out a directly-set total on a lineless invoice.
     */
    private function persistInvoice(Client $client, int $total): Invoice
    {
        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setStatus(InvoiceStatus::Pending);

        $line = new Line();
        $line->setQty(1);
        $line->setPrice($total);

        $invoice->addLine($line);

        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    private static int $creditNoteSequence = 0;

    private function persistCreditNote(Client $client, Invoice $invoice, int $total, CreditNoteStatus $status = CreditNoteStatus::Issued): CreditNote
    {
        $creditNote = new CreditNote();
        $creditNote->setClient($client);
        $creditNote->setInvoice($invoice);
        $creditNote->setStatus($status);
        // Direct persists bypass CreditNoteIssuedListener, which is what normally assigns the
        // number; the unique (company_id, credit_note_id) constraint still needs a distinct
        // value whenever a test creates more than one credit note.
        $creditNote->setCreditNoteId('CN-TEST-' . ++self::$creditNoteSequence);

        $line = new CreditNoteLine();
        $line->setQty(1);
        $line->setPrice($total);

        $creditNote->addLine($line);

        $this->em->persist($creditNote);
        $this->em->flush();

        return $creditNote;
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testUpdateWithSingleItem(): void
    {
        $updater = $this->createUpdater();

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));

        $item = new Line();
        $item->setQty(1)
            ->setPrice(15000);
        $invoice->addLine($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(15000), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(15000), $invoice->getBalance());
        self::assertEquals(BigDecimal::of(15000), $invoice->getBaseTotal());
    }

    public function testUpdateWithSingleItemAndMultipleQtys(): void
    {
        $updater = $this->createUpdater();

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000);
        $invoice->addLine($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(30000), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBalance());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBaseTotal());
    }

    public function testUpdateWithPercentageDiscount(): void
    {
        $updater = $this->createUpdater();

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000);
        $invoice->addLine($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(15);

        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(25500), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(25500), $invoice->getBalance());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBaseTotal());
    }

    public function testUpdateWithMonetaryDiscount(): void
    {
        $updater = $this->createUpdater();

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne());

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000);
        $invoice->addLine($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(80);

        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(29920), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(29920), $invoice->getBalance());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBaseTotal());
    }

    public function testUpdateWithTaxIncl(): void
    {
        $updater = $this->createUpdater();

        $tax = new Tax();
        $tax->setType(Tax::TYPE_INCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000);
        $lineTax = new LineTax();
        $lineTax->snapshotFrom($tax);

        $item->addTax($lineTax);

        $invoice->addLine($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(30000), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBalance());
        self::assertEquals(BigDecimal::of('25000.00'), $invoice->getBaseTotal());
        self::assertEquals(BigDecimal::of('5000.00'), $invoice->getTax());
    }

    public function testUpdateWithTaxFlat(): void
    {
        $updater = $this->createUpdater();

        $tax = new Tax();
        $tax->setType(Tax::TYPE_FLAT_RATE)
            ->setRate(2);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000);
        $lineTax = new LineTax();
        $lineTax->snapshotFrom($tax);

        $item->addTax($lineTax);

        $invoice->addLine($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(30200), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(30200), $invoice->getBalance());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBaseTotal());
        self::assertEquals(BigDecimal::of(200), $invoice->getTax());
    }

    public function testUpdateWithTaxExcl(): void
    {
        $updater = $this->createUpdater();

        $tax = new Tax();
        $tax->setType(Tax::TYPE_EXCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000);
        $lineTax = new LineTax();
        $lineTax->snapshotFrom($tax);

        $item->addTax($lineTax);

        $invoice->addLine($item);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of('36000'), $invoice->getTotal());
        self::assertEquals(BigDecimal::of('36000'), $invoice->getBalance());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBaseTotal());
        self::assertEquals(BigDecimal::of('6000'), $invoice->getTax());
    }

    public function testUpdateWithTaxInclAndPercentageDiscount(): void
    {
        $updater = $this->createUpdater();

        $tax = new Tax();
        $tax->setType(Tax::TYPE_INCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000);
        $lineTax = new LineTax();
        $lineTax->snapshotFrom($tax);

        $item->addTax($lineTax);
        $invoice->addLine($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(1500);

        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(26250), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(26250), $invoice->getBalance());
        self::assertEquals(BigDecimal::of('25000.00'), $invoice->getBaseTotal());
        self::assertEquals(BigDecimal::of('5000.00'), $invoice->getTax());
    }

    public function testUpdateWithTaxExclAndMonetaryDiscount(): void
    {
        $updater = $this->createUpdater();

        $tax = new Tax();
        $tax->setType(Tax::TYPE_EXCLUSIVE)
            ->setRate(20);

        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000);
        $lineTax = new LineTax();
        $lineTax->snapshotFrom($tax);

        $item->addTax($lineTax);
        $invoice->addLine($item);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(80);

        $invoice->setDiscount($discount);

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of('35920'), $invoice->getTotal());
        self::assertEquals(BigDecimal::of('35920'), $invoice->getBalance());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBaseTotal());
        self::assertEquals(BigDecimal::of('6000'), $invoice->getTax());
    }

    public function testUpdateTotalsWithPayments(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $invoice->setTotal(30000);
        $invoice->setBaseTotal(30000);
        $invoice->setBalance(30000);
        $invoice->setStatus(InvoiceStatus::Pending);

        $item = new Line();
        $item->setQty(2)
            ->setPrice(15000)
            ->setDescription('foobar');
        $invoice->addLine($item);

        $payment = new Payment();
        $payment->setTotalAmount(1000);
        $payment->setStatus(PaymentStatus::Captured);

        $invoice->addPayment($payment);
        $this->em->persist($invoice);
        $this->em->flush();

        $updater = $this->createUpdater();

        $updater->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(30000), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(29000), $invoice->getBalance());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBaseTotal());
    }

    /**
     * Test for the rounding issue that causes RoundingNecessaryException
     * with specific price and tax combinations (issue #1824)
     *
     * @throws MathException
     */
    public function testUpdateWithTaxExclRoundingIssue(): void
    {
        $updater = $this->createUpdater();

        $tax = new Tax();
        $tax->setType(Tax::TYPE_EXCLUSIVE)
            ->setRate(21);

        // Test case 1: 3.32 EUR with 21% tax (problematic case from issue)
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'EUR']));

        $item = new Line();
        $item->setQty(1)
            ->setPrice(332); // 3.32 EUR (stored as cents)
        $lineTax = new LineTax();
        $lineTax->snapshotFrom($tax);

        $item->addTax($lineTax);

        $invoice->addLine($item);

        $updater->calculateTotals($invoice);

        // Verify that the calculation completes without RoundingNecessaryException
        self::assertEquals(BigDecimal::of(332), $invoice->getBaseTotal());
        self::assertEquals(BigDecimal::of('70'), $invoice->getTax()); // 3.32 * 0.21 = 0.6972, rounded to 70 cents
        self::assertEquals(BigDecimal::of('402'), $invoice->getTotal()); // 332 + 69.72 = 402 cents

        // Test case 2: 3.33 EUR with 21% tax (another problematic case)
        $invoice2 = new Invoice();
        $invoice2->setClient(ClientFactory::createOne(['currencyCode' => 'EUR']));

        $item2 = new Line();
        $item2->setQty(1)
            ->setPrice(333); // 3.33 EUR (stored as cents)
        $lineTax2 = new LineTax();
        $lineTax2->snapshotFrom($tax);

        $item2->addTax($lineTax2);

        $invoice2->addLine($item2);

        $updater->calculateTotals($invoice2);

        // Verify that the calculation completes without RoundingNecessaryException
        self::assertEquals(BigDecimal::of(333), $invoice2->getBaseTotal());
        self::assertEquals(BigDecimal::of('70'), $invoice2->getTax()); // 3.33 * 0.21 = 0.6993, rounded to 70 cents
        self::assertEquals(BigDecimal::of('403'), $invoice2->getTotal()); // 333 + 69.93 = 403 cents
    }

    /**
     * @throws MathException
     */
    public function testCalculateBalanceWithPartialCredit(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = $this->persistInvoice($client, 10000);
        $this->persistCreditNote($client, $invoice, 3000);

        self::assertTrue(BigDecimal::of(7000)->isEqualTo($this->createUpdater()->calculateBalance($invoice)));
    }

    /**
     * @throws MathException
     */
    public function testCalculateBalanceWithRepeatedCredits(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = $this->persistInvoice($client, 10000);
        $this->persistCreditNote($client, $invoice, 3000);
        $this->persistCreditNote($client, $invoice, 2000);

        self::assertTrue(BigDecimal::of(5000)->isEqualTo($this->createUpdater()->calculateBalance($invoice)));
    }

    /**
     * @throws MathException
     */
    public function testCalculateBalanceWithCreditEqualToTotal(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = $this->persistInvoice($client, 10000);
        $this->persistCreditNote($client, $invoice, 10000);

        self::assertTrue(BigInteger::zero()->isEqualTo($this->createUpdater()->calculateBalance($invoice)));
    }

    /**
     * @throws MathException
     */
    public function testCalculateBalanceExcludesCancelledCredit(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = $this->persistInvoice($client, 10000);
        $this->persistCreditNote($client, $invoice, 3000, CreditNoteStatus::Cancelled);

        self::assertTrue(BigDecimal::of(10000)->isEqualTo($this->createUpdater()->calculateBalance($invoice)));
    }

    /**
     * @throws MathException
     */
    public function testCalculateOverflowContributionOnAnAlreadyPaidInvoice(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = $this->persistInvoice($client, 10000);

        $payment = new Payment();
        $payment->setTotalAmount(10000);
        $payment->setStatus(PaymentStatus::Captured);

        $invoice->addPayment($payment);
        $this->em->persist($payment);
        $this->em->flush();

        $creditNote = $this->persistCreditNote($client, $invoice, 3000);

        $updater = $this->createUpdater();

        self::assertTrue(BigDecimal::of(3000)->isEqualTo($updater->calculateOverflowContribution($creditNote)));
        self::assertTrue(BigInteger::zero()->isEqualTo($updater->calculateBalance($invoice)));
    }

    /**
     * @throws MathException
     */
    public function testCalculateOverflowContributionIsMarginalAcrossRepeatedCredits(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = $this->persistInvoice($client, 10000);

        $payment = new Payment();
        $payment->setTotalAmount(10000);
        $payment->setStatus(PaymentStatus::Captured);

        $invoice->addPayment($payment);
        $this->em->persist($payment);
        $this->em->flush();

        $updater = $this->createUpdater();

        $first = $this->persistCreditNote($client, $invoice, 5000);
        self::assertTrue(BigDecimal::of(5000)->isEqualTo($updater->calculateOverflowContribution($first)));

        $second = $this->persistCreditNote($client, $invoice, 3000);
        // Marginal, not cumulative: the first credit note's 5000 already counted, so the second
        // contributes only its own 3000 rather than the cumulative 8000 overflow.
        self::assertTrue(BigDecimal::of(3000)->isEqualTo($updater->calculateOverflowContribution($second)));
    }
}
