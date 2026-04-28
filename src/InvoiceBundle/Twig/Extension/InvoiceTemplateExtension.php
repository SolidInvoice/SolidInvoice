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

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function array_filter;
use function array_values;

final class InvoiceTemplateExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('invoice_has_outstanding_balance', $this->hasOutstandingBalance(...)),
            new TwigFunction('invoice_captured_payments', $this->capturedPayments(...)),
            new TwigFunction('invoice_primary_contact', $this->primaryContact(...)),
        ];
    }

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
    public function capturedPayments(Invoice $invoice): array
    {
        return array_values(array_filter(
            $invoice->getPayments()->toArray(),
            static fn (Payment $payment): bool => $payment->getStatus() === PaymentStatus::Captured,
        ));
    }

    public function primaryContact(Invoice $invoice): ?Contact
    {
        $first = $invoice->getUsers()->first();

        return $first instanceof Contact ? $first : null;
    }
}
