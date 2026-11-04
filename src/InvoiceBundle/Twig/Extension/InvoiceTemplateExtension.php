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

namespace SolidInvoice\InvoiceBundle\Twig\Extension;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use Symfony\Component\Uid\Ulid;
use Twig\Attribute\AsTwigFunction;
use function array_filter;
use function array_values;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Twig\Extension\InvoiceTemplateExtensionTest
 */
final class InvoiceTemplateExtension
{
    #[AsTwigFunction(name: 'invoice_has_outstanding_balance')]
    public function hasOutstandingBalance(Invoice $invoice): bool
    {
        if (! $invoice->getBalance()->isPositive()) {
            return false;
        }

        return $this->capturedPayments($invoice) !== [];
    }

    /**
     * @return list<Payment>
     */
    #[AsTwigFunction(name: 'invoice_captured_payments')]
    public function capturedPayments(Invoice $invoice): array
    {
        return array_values(array_filter(
            $invoice->getPayments()->toArray(),
            static fn (Payment $payment): bool => $payment->getStatus() === PaymentStatus::Captured,
        ));
    }

    #[AsTwigFunction(name: 'invoice_primary_contact')]
    public function primaryContact(Invoice $invoice): ?Contact
    {
        $first = $invoice->getUsers()->first();

        return $first instanceof Contact ? $first : null;
    }

    /**
     * What was still owed on the invoice once this payment landed, so a receipt reads the same
     * whenever it is downloaded. An overpayment went to client credit, so it never goes below zero.
     */
    #[AsTwigFunction(name: 'receipt_balance')]
    public function receiptBalance(Payment $payment): BigNumber
    {
        $invoice = $payment->getInvoice();
        $id = $payment->getId();

        if (! $invoice instanceof Invoice || ! $id instanceof Ulid) {
            return BigDecimal::zero();
        }

        $balance = $invoice->getTotal()->toBigDecimal();

        foreach ($this->capturedPayments($invoice) as $captured) {
            $capturedId = $captured->getId();

            if ($capturedId instanceof Ulid && $capturedId->compare($id) <= 0) {
                $balance = $balance->minus((int) $captured->getTotalAmount());
            }
        }

        return BigDecimal::max($balance, BigDecimal::zero());
    }
}
