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

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
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
        private TotalCalculator $totalCalculator,
        private CreditRepository $creditRepository,
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
     *
     * @throws MathException
     */
    public function onCreditNoteCancelled(CompletedEvent $event): void
    {
        $creditNote = $event->getSubject();

        assert($creditNote instanceof CreditNote);

        // The credit note keeps its number: cancellation is a sequence gap, not a reused id.
        $em = $this->registry->getManager();
        $em->persist($creditNote);
        $em->flush();

        $invoice = $creditNote->getInvoice();

        if ($invoice instanceof Invoice) {
            $invoice->setBalance($this->totalCalculator->calculateBalance($invoice));

            // The same marginal amount this credit note contributed when issued, computed the
            // same way so sequential credit notes on one invoice do not over- or under-reverse
            // client credit. See `design` §5/§6 on SOL-69.
            $overflow = $this->totalCalculator->calculateOverflowContribution($creditNote);

            if ($overflow->isPositive()) {
                $this->creditRepository->deductCredit($invoice->getClient(), $overflow);
            }

            $em->persist($invoice);
            $em->flush();
        }
    }
}
