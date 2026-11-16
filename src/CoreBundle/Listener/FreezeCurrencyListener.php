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
use SolidInvoice\CoreBundle\Currency\FrozenCurrencyThaw;
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
 * Also thaws a frozen document the moment a transition returns it to Draft, unless
 * {@see HasFrozenCurrencyInterface::retainsFrozenCurrency()} says it must stay frozen —
 * an invoice carrying payment rows follows this branch and is never thawed. Direction is
 * read from the transition's destination and the subject's own state, not from the
 * transition's source: `InvoiceStatus::Cancelled` is not an issued place, so a document
 * reopened from Cancelled reaches Draft through a transition whose only `from` is
 * already a non-issued place, and a froms-based check would miss it.
 *
 * The invoice and quote workflows use different places, so each keeps its own
 * draft/issued allowlist here rather than in {@see \SolidInvoice\CoreBundle\Traits\Entity\HasFrozenCurrency}.
 *
 * Mirrors the issue boundary {@see \SolidInvoice\TaxBundle\Listener\SnapshotTaxesOnIssueListener}
 * already uses for tax snapshots. Once frozen here,
 * {@see \SolidInvoice\CoreBundle\Listener\Doctrine\FrozenCurrencyGuardListener} rejects
 * any further write to the column, except the thaw this class sanctions through
 * {@see FrozenCurrencyThaw}.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Listener\FreezeCurrencyListenerTest
 */
final readonly class FreezeCurrencyListener implements EventSubscriberInterface
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

    public function __construct(
        private FrozenCurrencyThaw $thaw,
    ) {
    }

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
        $this->apply($event, self::INVOICE_DRAFT_PLACES, self::INVOICE_ISSUED_PLACES);
    }

    /**
     * @template TSubject of object
     *
     * @param Event<TSubject> $event
     */
    public function onQuoteTransition(Event $event): void
    {
        $this->apply($event, self::QUOTE_DRAFT_PLACES, self::QUOTE_ISSUED_PLACES);
    }

    /**
     * @template TSubject of object
     *
     * @param Event<TSubject> $event
     * @param list<string> $draftPlaces
     * @param list<string> $issuedPlaces
     */
    private function apply(Event $event, array $draftPlaces, array $issuedPlaces): void
    {
        $subject = $event->getSubject();

        if (! $subject instanceof HasFrozenCurrencyInterface) {
            return;
        }

        $transition = $event->getTransition();

        if (! $transition instanceof Transition) {
            return;
        }

        if ($subject->getCurrencyCode() === null) {
            if ($this->isLeavingDraft($transition, $draftPlaces, $issuedPlaces)) {
                $subject->setCurrencyCode($subject->getCurrency()->getCode());
            }

            return;
        }

        if (! $this->isReturningToDraft($transition, $draftPlaces) || $subject->retainsFrozenCurrency()) {
            return;
        }

        $this->thaw->sanction($subject);
        $subject->setCurrencyCode(null);
    }

    /**
     * @param list<string> $draftPlaces
     * @param list<string> $issuedPlaces
     */
    private function isLeavingDraft(Transition $transition, array $draftPlaces, array $issuedPlaces): bool
    {
        $fromDraft = array_any($transition->getFroms(), fn ($from) => in_array($from, $draftPlaces, true));

        if (! $fromDraft) {
            return false;
        }

        return array_any($transition->getTos(), fn ($to) => in_array($to, $issuedPlaces, true));
    }

    /**
     * @param list<string> $draftPlaces
     */
    private function isReturningToDraft(Transition $transition, array $draftPlaces): bool
    {
        return array_any($transition->getTos(), fn ($to) => in_array($to, $draftPlaces, true));
    }
}
