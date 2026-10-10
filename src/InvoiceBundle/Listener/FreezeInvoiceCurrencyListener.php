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

namespace SolidInvoice\InvoiceBundle\Listener;

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

/**
 * Freezes {@see Invoice::getCurrencyCode()} the moment an invoice is issued (Draft/New
 * into Pending, Active, Overdue or Paid), so a later change to the client's currency — a
 * client edit, or a default-currency change while `Feature::MultiCurrency` is off — cannot
 * restate an already-issued invoice. Cancelling or archiving a draft does not issue it, so
 * those transitions leave the currency mutable.
 *
 * Mirrors the issue boundary {@see \SolidInvoice\TaxBundle\Listener\SnapshotTaxesOnIssueListener}
 * already uses for tax snapshots. Once frozen here,
 * {@see \SolidInvoice\InvoiceBundle\Listener\Doctrine\InvoiceCurrencyGuardListener} rejects
 * any further write to the column.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\FreezeInvoiceCurrencyListenerTest
 */
final class FreezeInvoiceCurrencyListener implements EventSubscriberInterface
{
    private const array DRAFT_PLACES = [
        InvoiceStatus::New->value,
        InvoiceStatus::Draft->value,
    ];

    private const array ISSUED_PLACES = [
        InvoiceStatus::Pending->value,
        InvoiceStatus::Paid->value,
        InvoiceStatus::Overdue->value,
    ];

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.invoice.transition' => 'onTransition',
        ];
    }

    /**
     * @template TSubject of object
     *
     * @param Event<TSubject> $event
     */
    public function onTransition(Event $event): void
    {
        $invoice = $event->getSubject();

        if (! $invoice instanceof Invoice || $invoice->getCurrencyCode() !== null) {
            return;
        }

        if (! $this->isLeavingDraft($event->getTransition())) {
            return;
        }

        $invoice->setCurrencyCode($invoice->getCurrency()->getCode());
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

        return array_any($transition->getTos(), fn ($to) => in_array($to, self::ISSUED_PLACES, true));
    }
}
