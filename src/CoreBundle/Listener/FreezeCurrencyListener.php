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

namespace SolidInvoice\CoreBundle\Listener;

use SolidInvoice\CoreBundle\Contracts\HasFrozenCurrencyInterface;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

/**
 * Freezes {@see HasFrozenCurrencyInterface::getCurrencyCode()} the moment an invoice or
 * quote is issued, so a later change to the client's currency — a client edit, or a
 * default-currency change while `Feature::MultiCurrency` is off — cannot restate an
 * already-issued document. Cancelling or archiving a draft does not issue it, so those
 * transitions leave the currency mutable.
 *
 * The invoice and quote workflows use different places, so each keeps its own
 * draft/issued allowlist here rather than in {@see \SolidInvoice\CoreBundle\Traits\Entity\HasFrozenCurrency}.
 *
 * Mirrors the issue boundary {@see \SolidInvoice\TaxBundle\Listener\SnapshotTaxesOnIssueListener}
 * already uses for tax snapshots. Once frozen here,
 * {@see \SolidInvoice\CoreBundle\Listener\Doctrine\FrozenCurrencyGuardListener} rejects
 * any further write to the column.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Listener\FreezeCurrencyListenerTest
 */
final class FreezeCurrencyListener implements EventSubscriberInterface
{
    private const array INVOICE_DRAFT_PLACES = [
        InvoiceStatus::New->value,
        InvoiceStatus::Draft->value,
    ];

    private const array INVOICE_ISSUED_PLACES = [
        InvoiceStatus::Pending->value,
        InvoiceStatus::Paid->value,
        InvoiceStatus::Overdue->value,
    ];

    private const array QUOTE_DRAFT_PLACES = [
        QuoteStatus::New->value,
        QuoteStatus::Draft->value,
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
            'workflow.invoice.transition' => 'onInvoiceTransition',
            'workflow.quote.transition' => 'onQuoteTransition',
        ];
    }

    /**
     * @template TSubject of object
     *
     * @param Event<TSubject> $event
     */
    public function onInvoiceTransition(Event $event): void
    {
        $this->freeze($event, self::INVOICE_DRAFT_PLACES, self::INVOICE_ISSUED_PLACES);
    }

    /**
     * @template TSubject of object
     *
     * @param Event<TSubject> $event
     */
    public function onQuoteTransition(Event $event): void
    {
        $this->freeze($event, self::QUOTE_DRAFT_PLACES, self::QUOTE_ISSUED_PLACES);
    }

    /**
     * @template TSubject of object
     *
     * @param Event<TSubject> $event
     * @param list<string> $draftPlaces
     * @param list<string> $issuedPlaces
     */
    private function freeze(Event $event, array $draftPlaces, array $issuedPlaces): void
    {
        $subject = $event->getSubject();

        if (! $subject instanceof HasFrozenCurrencyInterface || $subject->getCurrencyCode() !== null) {
            return;
        }

        if (! $this->isLeavingDraft($event->getTransition(), $draftPlaces, $issuedPlaces)) {
            return;
        }

        $subject->setCurrencyCode($subject->getCurrency()->getCode());
    }

    /**
     * @param list<string> $draftPlaces
     * @param list<string> $issuedPlaces
     */
    private function isLeavingDraft(?Transition $transition, array $draftPlaces, array $issuedPlaces): bool
    {
        if (! $transition instanceof Transition) {
            return false;
        }

        $fromDraft = array_any($transition->getFroms(), fn ($from) => in_array($from, $draftPlaces, true));

        if (! $fromDraft) {
            return false;
        }

        return array_any($transition->getTos(), fn ($to) => in_array($to, $issuedPlaces, true));
    }
}
