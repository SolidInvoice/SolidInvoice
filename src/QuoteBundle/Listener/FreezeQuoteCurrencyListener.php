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

namespace SolidInvoice\QuoteBundle\Listener;

use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

/**
 * Freezes {@see Quote::getCurrencyCode()} the moment a quote leaves Draft/New, so a later
 * change to the client's currency — a client edit, or a default-currency change while
 * `Feature::MultiCurrency` is off — cannot restate an already-issued quote.
 *
 * Mirrors the draft boundary {@see \SolidInvoice\TaxBundle\Listener\SnapshotTaxesOnIssueListener}
 * already uses for tax snapshots. Once frozen here,
 * {@see \SolidInvoice\QuoteBundle\Listener\Doctrine\QuoteCurrencyGuardListener} rejects any
 * further write to the column.
 *
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\FreezeQuoteCurrencyListenerTest
 */
final class FreezeQuoteCurrencyListener implements EventSubscriberInterface
{
    private const array DRAFT_PLACES = [
        QuoteStatus::New->value,
        QuoteStatus::Draft->value,
    ];

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.quote.transition' => 'onTransition',
        ];
    }

    /**
     * @template TSubject of object
     *
     * @param Event<TSubject> $event
     */
    public function onTransition(Event $event): void
    {
        $quote = $event->getSubject();

        if (! $quote instanceof Quote || $quote->getCurrencyCode() !== null) {
            return;
        }

        if (! $this->isLeavingDraft($event->getTransition())) {
            return;
        }

        $quote->setCurrencyCode($quote->getCurrency()->getCode());
    }

    private function isLeavingDraft(?Transition $transition): bool
    {
        if (! $transition instanceof Transition) {
            return false;
        }

        $fromDraft = array_any($transition->getFroms(), fn ($from) => in_array($from, self::DRAFT_PLACES, true));

        if (! $fromDraft) {
            return false;
        }

        return ! array_any($transition->getTos(), fn ($to) => in_array($to, self::DRAFT_PLACES, true));
    }
}
