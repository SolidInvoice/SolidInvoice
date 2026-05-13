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

use DateTimeImmutable;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\TaxBundle\Entity\LineTax;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

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
 */
final class SnapshotTaxesOnIssueListener implements EventSubscriberInterface
{
    private const INVOICE_DRAFT_PLACES = [
        InvoiceStatus::Draft->value,
        InvoiceStatus::New->value,
    ];

    private const INVOICE_ISSUED_PLACES = [
        InvoiceStatus::Pending->value,
        InvoiceStatus::Active->value,
        InvoiceStatus::Paid->value,
        InvoiceStatus::Overdue->value,
    ];

    private const QUOTE_DRAFT_PLACES = [
        QuoteStatus::Draft->value,
        QuoteStatus::New->value,
    ];

    private const QUOTE_ISSUED_PLACES = [
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

        $stamp = new DateTimeImmutable();

        foreach ($subject->getLines() as $line) {
            foreach ($line->getTaxes() as $lineTax) {
                if (! $lineTax instanceof LineTax) {
                    continue;
                }

                if ($lineTax->getSnapshottedAt() !== null) {
                    continue;
                }

                $lineTax->freeze($stamp);
            }
        }
    }

    private function isLeavingDraft(Event $event, BaseInvoice|Quote $subject): bool
    {
        $transition = $event->getTransition();

        if ($transition === null) {
            return false;
        }

        $isQuote = $subject instanceof Quote;
        $draftPlaces = $isQuote ? self::QUOTE_DRAFT_PLACES : self::INVOICE_DRAFT_PLACES;
        $issuedPlaces = $isQuote ? self::QUOTE_ISSUED_PLACES : self::INVOICE_ISSUED_PLACES;

        $fromDraft = false;
        foreach ($transition->getFroms() as $from) {
            if (in_array($from, $draftPlaces, true)) {
                $fromDraft = true;
                break;
            }
        }

        if (! $fromDraft) {
            return false;
        }

        foreach ($transition->getTos() as $to) {
            if (in_array($to, $issuedPlaces, true)) {
                return true;
            }
        }

        return false;
    }
}
