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

use DateTimeImmutable;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class InvoiceTemplateExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('invoice_days_until_due', $this->daysUntilDue(...)),
            new TwigFunction('invoice_has_outstanding_balance', $this->hasOutstandingBalance(...)),
            new TwigFunction('invoice_captured_payments', $this->capturedPayments(...)),
            new TwigFunction('invoice_primary_contact', $this->primaryContact(...)),
        ];
    }

    public function daysUntilDue(Invoice $invoice): ?int
    {
        $due = $invoice->getDue();

        if ($due === null) {
            return null;
        }

        $now = new DateTimeImmutable('today');
        $dueDate = DateTimeImmutable::createFromInterface($due)->setTime(0, 0);
        $diff = $now->diff($dueDate);

        return (int) $diff->format('%r%a');
    }

    public function hasOutstandingBalance(Invoice $invoice): bool
    {
        if ($invoice->getPayments()->count() === 0) {
            return false;
        }

        return ! $invoice->getBalance()->isZero();
    }

    /**
     * @return list<Payment>
     */
    public function capturedPayments(Invoice $invoice): array
    {
        $captured = [];
        foreach ($invoice->getPayments() as $payment) {
            if ($payment->getStatus() === PaymentStatus::Captured) {
                $captured[] = $payment;
            }
        }

        return $captured;
    }

    public function primaryContact(Invoice $invoice): ?Contact
    {
        $first = $invoice->getUsers()->first();

        return $first instanceof Contact ? $first : null;
    }
}
