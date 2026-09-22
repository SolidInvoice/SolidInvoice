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

use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use function assert;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\CreditNoteWorkflowTest
 */
final readonly class CreditNoteIssuedListener implements EventSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $registry,
        private BillingIdGenerator $billingIdGenerator,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.credit_note.completed.issue' => 'onCreditNoteIssued',
        ];
    }

    /**
     * @param CompletedEvent<CreditNote> $event
     */
    public function onCreditNoteIssued(CompletedEvent $event): void
    {
        $creditNote = $event->getSubject();

        assert($creditNote instanceof CreditNote);

        if ('' === $creditNote->getCreditNoteId()) {
            $creditNote->setCreditNoteId($this->billingIdGenerator->generate($creditNote, ['field' => 'creditNoteId']));
        }

        $creditNote->setCreditNoteDate(CarbonImmutable::now());

        // Balance recalculation on the referenced invoice, and granting any overflow as client
        // credit, are stubbed here. See `design` §5/§6 on SOL-69 — CN-3 fills these in.

        $em = $this->registry->getManager();
        $em->persist($creditNote);
        $em->flush();
    }
}
