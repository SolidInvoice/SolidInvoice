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

namespace SolidInvoice\InvoiceBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Reusable service for applying invoice status transitions.
 * Provides clean abstraction for state changes that can be used
 * across different contexts (manual, automated, API, etc.).
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Service\InvoiceStatusTransitionServiceTest
 */
final readonly class InvoiceStatusTransitionService
{
    public function __construct(
        private WorkflowInterface $invoiceStateMachine,
        private ManagerRegistry $registry,
    ) {
    }

    /**
     * Apply a transition to an invoice.
     *
     * @throws InvalidTransitionException If transition cannot be applied
     */
    public function applyTransition(BaseInvoice $invoice, string $transition): void
    {
        if (! $this->invoiceStateMachine->can($invoice, $transition)) {
            throw new InvalidTransitionException($transition);
        }

        $this->invoiceStateMachine->apply($invoice, $transition);

        // Persist changes
        $em = $this->registry->getManager();
        $em->persist($invoice);
        $em->flush();
    }

    /**
     * Check if a transition can be applied.
     */
    public function canApplyTransition(BaseInvoice $invoice, string $transition): bool
    {
        return $this->invoiceStateMachine->can($invoice, $transition);
    }

    /**
     * Get available transitions for an invoice.
     *
     * @return array<int, string>
     */
    public function getAvailableTransitions(BaseInvoice $invoice): array
    {
        return array_map(
            static fn ($transition) => $transition->getName(),
            $this->invoiceStateMachine->getEnabledTransitions($invoice)
        );
    }
}
