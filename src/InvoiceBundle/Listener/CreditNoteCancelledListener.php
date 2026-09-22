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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use function assert;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\CreditNoteWorkflowTest
 */
final readonly class CreditNoteCancelledListener implements EventSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $registry,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.credit_note.completed.cancel' => 'onCreditNoteCancelled',
        ];
    }

    /**
     * @param CompletedEvent<CreditNote> $event
     */
    public function onCreditNoteCancelled(CompletedEvent $event): void
    {
        $creditNote = $event->getSubject();

        assert($creditNote instanceof CreditNote);

        // The credit note keeps its number: cancellation is a sequence gap, not a reused id.
        // Reversing the invoice balance and any granted client credit is stubbed here.
        // See `design` §5/§6 on SOL-69 — CN-3 fills these in.

        $em = $this->registry->getManager();
        $em->persist($creditNote);
        $em->flush();
    }
}
