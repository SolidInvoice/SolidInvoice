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
use Brick\Math\Exception\MathException;
use Doctrine\ORM\Exception\NotSupported;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Billing\Discount\DiscountTreatmentFactory;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Enum\TaxArithmeticVersion;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\RandomNumberGenerator;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Calculator\InvoiceTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\LineTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\TaxCalculator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * The fidelity mechanism's own tests: once a document is issued and pinned, nothing
 * {@see TotalCalculator::calculateTotals()} does may move its figures again. The balance is
 * the one exception, since a payment still has to be reflected on a frozen invoice.
 *
 * @see \SolidInvoice\CoreBundle\Billing\TotalCalculator
 */
final class IssuedDocumentFidelityTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    /**
     * Case 11. A pinned invoice keeps the harm-3 inconsistency it was issued with: its own
     * total and its own discount line already disagree (31500 vs 30000 − 5400 + 6000 =
     * 30600), and the freeze must not "fix" that by recalculating — an issued document is a
     * historical record, not a live one.
     *
     * @throws MathException
     * @throws NotSupported
     */
    public function testPinnedInvoiceKeepsItsByteIdenticalFiguresAndItsInconsistentDiscountLine(): void
    {
        $invoice = $this->pinnedInvoiceWithPercentageDiscount();

        $this->updater()->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(30000), $invoice->getBaseTotal());
        self::assertEquals(BigDecimal::of(6000), $invoice->getTax());
        self::assertEquals(BigDecimal::of(31500), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(31500), $invoice->getPayableAmount());
        self::assertEquals(BigDecimal::of(5400), new Calculator()->calculateDiscount($invoice));
    }

    /**
     * Case 12. The guard freezes the total without freezing the balance — a captured payment
     * still has to be reflected, or the fix would break every payment screen for a pinned
     * invoice.
     *
     * @throws MathException
     * @throws NotSupported
     */
    public function testPinnedInvoiceBalanceStillTracksPaymentsWhileTotalStaysFrozen(): void
    {
        $invoice = $this->pinnedInvoiceWithPercentageDiscount();

        $payment = new Payment();
        $payment->setTotalAmount(1500);
        $payment->setStatus(PaymentStatus::Captured);

        $invoice->addPayment($payment);

        $this->em->persist($invoice);
        $this->em->flush();

        $this->updater()->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(31500), $invoice->getTotal());
        self::assertEquals(BigDecimal::of(30000), $invoice->getBalance());
    }

    /**
     * Case 13. An unpinned draft is exactly what the freeze must leave alone: it recomputes
     * every time, and pins nothing, because it has not been issued.
     *
     * @throws MathException
     * @throws NotSupported
     */
    public function testUnpinnedDraftRecalculatesAndPinsNothing(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setQty(1)->setPrice(15000);
        $invoice->addLine($line);

        $this->updater()->calculateTotals($invoice);

        self::assertEquals(BigDecimal::of(15000), $invoice->getBaseTotal());
        self::assertNull($invoice->getTaxArithmeticVersion());
    }

    /**
     * Case 14. The moment a draft is saved issued, it is pinned to the current arithmetic —
     * and an edit back to draft clears the pin, because the document is being corrected
     * before it ever reaches a client again (the `edit` transition in
     * `config/packages/workflow.php` always returns to draft).
     *
     * @throws MathException
     * @throws NotSupported
     */
    public function testDraftIsPinnedOnIssueAndUnpinnedOnEditBackToDraft(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setQty(1)->setPrice(15000);
        $invoice->addLine($line);

        $updater = $this->updater();

        $updater->calculateTotals($invoice);
        self::assertNull($invoice->getTaxArithmeticVersion());

        $invoice->setStatus(InvoiceStatus::Pending);
        $updater->calculateTotals($invoice);
        self::assertSame(TaxArithmeticVersion::Legacy, $invoice->getTaxArithmeticVersion());

        $invoice->setStatus(InvoiceStatus::Draft);
        $updater->calculateTotals($invoice);
        self::assertNull($invoice->getTaxArithmeticVersion());
    }

    /**
     * Case 15. {@see InvoiceCloner} must never copy the pin: a clone is a new draft, and it
     * has to recalculate under whatever arithmetic is current when it is first saved, rather
     * than replay the arithmetic its source was frozen under.
     *
     * The source's base total is deliberately set to an impossible value so the assertion
     * proves a recalculation happened, rather than merely that the copied figure survived.
     *
     * @throws MathException
     * @throws NotSupported
     */
    public function testClonedInvoiceIsUnpinnedAndRecalculatesInsteadOfKeepingTheStaleFigure(): void
    {
        $invoice = $this->pinnedInvoiceWithPercentageDiscount();
        $invoice->setBaseTotal(BigDecimal::of(99999));

        $invoiceManager = M::mock(InvoiceManager::class);
        $invoiceManager->shouldReceive('create');

        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldReceive('get')->once()->with('invoice/id_generation/strategy')->andReturn('random_number');
        $systemConfig->shouldReceive('get')->once()->with('invoice/id_generation/id_prefix')->andReturn('');
        $systemConfig->shouldReceive('get')->once()->with('invoice/id_generation/id_suffix')->andReturn('');

        $cloner = new InvoiceCloner($invoiceManager, new BillingIdGenerator(new ServiceLocator([
            'random_number' => fn () => new RandomNumberGenerator(),
        ]), $systemConfig));

        $clone = $cloner->clone($invoice);

        self::assertNull($clone->getTaxArithmeticVersion());
        self::assertEquals(BigDecimal::of(99999), $clone->getBaseTotal());

        $this->updater()->calculateTotals($clone);

        self::assertEquals(BigDecimal::of(30000), $clone->getBaseTotal());
    }

    /**
     * A pinned invoice issued at 30000/6000/31500 with a 15% percentage discount — the
     * design's harm-3 example: its own total and its own discount line already disagree, and
     * pinning preserves that rather than correcting it.
     *
     * @throws MathException
     */
    private function pinnedInvoiceWithPercentageDiscount(): Invoice
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $invoice->setStatus(InvoiceStatus::Pending);
        $invoice->setTaxArithmeticVersion(TaxArithmeticVersion::Legacy);
        $invoice->setBaseTotal(BigDecimal::of(30000));
        $invoice->setTax(BigDecimal::of(6000));
        $invoice->setTotal(BigDecimal::of(31500));
        $invoice->setPayableAmount(BigDecimal::of(31500));

        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(15);

        $invoice->setDiscount($discount);

        $line = new Line();
        $line->setQty(1)->setPrice(30000);
        $invoice->addLine($line);

        return $invoice;
    }

    /**
     * @throws NotSupported
     */
    private function updater(): TotalCalculator
    {
        return new TotalCalculator(
            $this->em->getRepository(Payment::class),
            new Calculator(),
            new TaxCalculator(new LineTaxCalculator(), new InvoiceTaxCalculator(), new DiscountTreatmentFactory()),
        );
    }
}
