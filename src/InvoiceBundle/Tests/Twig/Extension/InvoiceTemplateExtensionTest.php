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

namespace SolidInvoice\InvoiceBundle\Tests\Twig\Extension;

use Brick\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Twig\Extension\InvoiceTemplateExtension;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;

/**
 * @covers \SolidInvoice\InvoiceBundle\Twig\Extension\InvoiceTemplateExtension
 */
final class InvoiceTemplateExtensionTest extends TestCase
{
    private InvoiceTemplateExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new InvoiceTemplateExtension();
    }

    public function testHasOutstandingBalanceIsFalseForFullyPaidInvoice(): void
    {
        $invoice = (new Invoice())->setBalance(BigInteger::zero());
        $invoice->addPayment($this->capturedPayment());

        self::assertFalse($this->extension->hasOutstandingBalance($invoice));
    }

    public function testHasOutstandingBalanceIsFalseForOverpaidInvoice(): void
    {
        // Negative balance can occur when a payment exceeds the invoice total —
        // it is a credit, not money owed by the client.
        $invoice = (new Invoice())->setBalance(BigInteger::of(-50000));
        $invoice->addPayment($this->capturedPayment());

        self::assertFalse($this->extension->hasOutstandingBalance($invoice));
    }

    public function testHasOutstandingBalanceIsFalseForUnpaidInvoiceWithNoCapturedPayments(): void
    {
        // Brand-new invoice with no payments yet — the full balance is owed
        // but there is no captured payment to reconcile against.
        $invoice = (new Invoice())->setBalance(BigInteger::of(150000));

        self::assertFalse($this->extension->hasOutstandingBalance($invoice));
    }

    public function testHasOutstandingBalanceIsFalseWhenOnlyPendingPayments(): void
    {
        // Pending/failed payments must not flag a "balance due" — only
        // captured payments count toward partial-payment reconciliation.
        $invoice = (new Invoice())->setBalance(BigInteger::of(150000));
        $invoice->addPayment($this->payment(PaymentStatus::New));

        self::assertFalse($this->extension->hasOutstandingBalance($invoice));
    }

    public function testHasOutstandingBalanceIsTrueForPartiallyPaidInvoice(): void
    {
        $invoice = (new Invoice())->setBalance(BigInteger::of(50000));
        $invoice->addPayment($this->capturedPayment());

        self::assertTrue($this->extension->hasOutstandingBalance($invoice));
    }

    public function testCapturedPaymentsFiltersByStatus(): void
    {
        $captured = $this->capturedPayment();
        $pending = $this->payment(PaymentStatus::New);
        $failed = $this->payment(PaymentStatus::Failed);

        $invoice = new Invoice();
        $invoice->addPayment($pending);
        $invoice->addPayment($captured);
        $invoice->addPayment($failed);

        $result = $this->extension->capturedPayments($invoice);

        self::assertCount(1, $result);
        self::assertSame($captured, $result[0]);
    }

    public function testCapturedPaymentsReturnsEmptyArrayWhenNoneCaptured(): void
    {
        $invoice = new Invoice();
        $invoice->addPayment($this->payment(PaymentStatus::New));

        self::assertSame([], $this->extension->capturedPayments($invoice));
    }

    public function testPrimaryContactReturnsFirstUser(): void
    {
        $jane = (new Contact())->setFirstName('Jane');
        $john = (new Contact())->setFirstName('John');

        $invoice = new Invoice();
        $invoice->addUser($jane);
        $invoice->addUser($john);

        self::assertSame($jane, $this->extension->primaryContact($invoice));
    }

    public function testPrimaryContactReturnsNullWhenNoUsers(): void
    {
        self::assertNull($this->extension->primaryContact(new Invoice()));
    }

    public function testGetFunctionsExposesExpectedTwigFunctions(): void
    {
        $names = array_map(
            static fn ($function): string => $function->getName(),
            $this->extension->getFunctions(),
        );

        self::assertSame(
            ['invoice_has_outstanding_balance', 'invoice_captured_payments', 'invoice_primary_contact'],
            $names,
        );
    }

    private function capturedPayment(): Payment
    {
        return $this->payment(PaymentStatus::Captured);
    }

    private function payment(PaymentStatus $status): Payment
    {
        return (new Payment())->setStatus($status);
    }
}
