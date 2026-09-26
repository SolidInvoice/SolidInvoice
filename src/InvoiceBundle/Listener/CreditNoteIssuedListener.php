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
use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
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
            'workflow.credit_note.completed.issue' => 'onCreditNoteIssued',
        ];
    }

    /**
     * @param CompletedEvent<CreditNote> $event
     *
     * @throws MathException
     */
    public function onCreditNoteIssued(CompletedEvent $event): void
    {
        $creditNote = $event->getSubject();

        assert($creditNote instanceof CreditNote);

        if ('' === $creditNote->getCreditNoteId()) {
            $creditNote->setCreditNoteId($this->billingIdGenerator->generate($creditNote, ['field' => 'creditNoteId']));
        }

        $creditNote->setCreditNoteDate(CarbonImmutable::now());

        $em = $this->registry->getManager();
        $em->persist($creditNote);
        $em->flush();

        $invoice = $creditNote->getInvoice();

        if ($invoice instanceof Invoice) {
            $invoice->setBalance($this->totalCalculator->calculateBalance($invoice));

            $overflow = $this->totalCalculator->calculateOverflowContribution($creditNote);

            if ($overflow->isPositive()) {
                $this->creditRepository->addCredit($invoice->getClient(), $overflow);
            }

            $em->persist($invoice);
            $em->flush();
        }
    }
}
