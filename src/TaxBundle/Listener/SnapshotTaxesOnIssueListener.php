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

namespace SolidInvoice\TaxBundle\Listener;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

/**
 * Stamps `snapshotted_at` on every {@see LineTax} of an invoice or quote when the
 * document leaves a draft state (Draft/New → Pending/Active/Paid for invoices, or
 * Draft/New → Pending/Accepted for quotes).
 *
 * Once frozen, downstream {@see \SolidInvoice\TaxBundle\Calculator\TaxCalculator}
 * passes must not overwrite the snapshot fields — see
 * {@see self::isLeavingDraft()} for the gating logic. Re-running the calculator on a
 * snapshotted document only updates the computed {@see LineTax::$amount}; it never
 * re-snapshots from the source {@see \SolidInvoice\TaxBundle\Entity\Tax}.
 * @see \SolidInvoice\TaxBundle\Tests\Listener\SnapshotTaxesOnIssueListenerTest
 */
final class SnapshotTaxesOnIssueListener implements EventSubscriberInterface
{
    private const array INVOICE_DRAFT_PLACES = [
        InvoiceStatus::Draft->value,
        InvoiceStatus::New->value,
    ];

    private const array INVOICE_ISSUED_PLACES = [
        InvoiceStatus::Pending->value,
        InvoiceStatus::Active->value,
        InvoiceStatus::Paid->value,
        InvoiceStatus::Overdue->value,
    ];

    private const array QUOTE_DRAFT_PLACES = [
        QuoteStatus::Draft->value,
        QuoteStatus::New->value,
    ];

    private const array QUOTE_ISSUED_PLACES = [
        QuoteStatus::Pending->value,
        QuoteStatus::Accepted->value,
        QuoteStatus::Declined->value,
    ];

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.invoice.transition' => 'onTransition',
            'workflow.quote.transition' => 'onTransition',
        ];
    }

    public function onTransition(Event $event): void
    {
        $subject = $event->getSubject();

        if (! $subject instanceof BaseInvoice && ! $subject instanceof Quote) {
            return;
        }

        if (! $this->isLeavingDraft($event, $subject)) {
            return;
        }

        $stamp = CarbonImmutable::now();

        foreach ($subject->getLines() as $line) {
            foreach ($line->getTaxes() as $lineTax) {
                if (! $lineTax instanceof LineTax) {
                    continue;
                }

                if ($lineTax->getSnapshottedAt() instanceof DateTimeInterface) {
                    continue;
                }

                $lineTax->freeze($stamp);
            }
        }

        if ($subject instanceof Invoice || $subject instanceof Quote) {
            foreach ($subject->getInvoiceTaxes() as $invoiceTax) {
                if (! $invoiceTax instanceof InvoiceTax) {
                    continue;
                }

                if ($invoiceTax->getSnapshottedAt() instanceof DateTimeInterface) {
                    continue;
                }

                $invoiceTax->freeze($stamp);
            }
        }
    }

    private function isLeavingDraft(Event $event, BaseInvoice|Quote $subject): bool
    {
        $transition = $event->getTransition();

        if (! $transition instanceof Transition) {
            return false;
        }

        $isQuote = $subject instanceof Quote;
        $draftPlaces = $isQuote ? self::QUOTE_DRAFT_PLACES : self::INVOICE_DRAFT_PLACES;
        $issuedPlaces = $isQuote ? self::QUOTE_ISSUED_PLACES : self::INVOICE_ISSUED_PLACES;
        $fromDraft = array_any($transition->getFroms(), fn ($from) => in_array($from, $draftPlaces, true));

        if (! $fromDraft) {
            return false;
        }

        return array_any($transition->getTos(), fn ($to) => in_array($to, $issuedPlaces, true));
    }
}
